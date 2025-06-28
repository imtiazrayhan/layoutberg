<?php
/**
 * OpenAI API client.
 *
 * @package    LayoutBerg
 * @subpackage Core
 * @since      1.0.0
 */

namespace DotCamp\LayoutBerg;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OpenAI API client class.
 *
 * @since 1.0.0
 */
class API_Client {

	/**
	 * API endpoint.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string
	 */
	private $api_endpoint = 'https://api.openai.com/v1/chat/completions';

	/**
	 * API key.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string
	 */
	private $api_key;

	/**
	 * Model to use.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string
	 */
	private $model;

	/**
	 * Maximum tokens.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    int
	 */
	private $max_tokens;

	/**
	 * Temperature.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    float
	 */
	private $temperature;

	/**
	 * Maximum retry attempts.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    int
	 */
	private $max_retries = 3;

	/**
	 * Retry delay in seconds.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    int
	 */
	private $retry_delay = 2;

	/**
	 * Request timeout in seconds.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    int
	 */
	private $timeout = 60;

	/**
	 * Security manager instance.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    Security_Manager
	 */
	private $security;

	/**
	 * Prompt engineer instance.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    Prompt_Engineer
	 */
	private $prompt_engineer;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->security = new Security_Manager();
		$this->prompt_engineer = new Prompt_Engineer();
		$this->load_settings();
	}

	/**
	 * Load settings from options.
	 *
	 * @since 1.0.0
	 */
	private function load_settings() {
		$options = get_option( 'layoutberg_options', array() );

		// Get and decrypt API key.
		$encrypted_key = isset( $options['api_key'] ) ? $options['api_key'] : '';
		if ( ! empty( $encrypted_key ) ) {
			$decrypted = $this->security->decrypt_api_key( $encrypted_key );
			$this->api_key = $decrypted !== false ? $decrypted : '';
		} else {
			$this->api_key = '';
		}

		// Get other settings.
		$this->model       = isset( $options['model'] ) ? $options['model'] : 'gpt-3.5-turbo';
		$this->max_tokens  = isset( $options['max_tokens'] ) ? intval( $options['max_tokens'] ) : 2000;
		// Ensure max tokens doesn't exceed model limit
		if ( $this->max_tokens > 4096 ) {
			$this->max_tokens = 4096;
		}
		$this->temperature = isset( $options['temperature'] ) ? floatval( $options['temperature'] ) : 0.7;
	}

	/**
	 * Generate layout from prompt.
	 *
	 * @since 1.0.0
	 * @param string $prompt  User prompt.
	 * @param array  $options Generation options.
	 * @return array|WP_Error Response data or error.
	 */
	public function generate_layout( $prompt, $options = array() ) {
		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'LayoutBerg API_Client::generate_layout called' );
			error_log( 'API key present: ' . ( ! empty( $this->api_key ) ? 'yes' : 'no' ) );
			error_log( 'Options received: ' . print_r( $options, true ) );
		}
		
		// Check if API key is set.
		if ( empty( $this->api_key ) ) {
			return new \WP_Error( 'no_api_key', __( 'OpenAI API key is not configured.', 'layoutberg' ) );
		}

		// Rate limiting removed - unlimited generations allowed

		// Override settings with options if provided
		if ( isset( $options['model'] ) ) {
			$this->model = $options['model'];
		}
		if ( isset( $options['temperature'] ) ) {
			$this->temperature = floatval( $options['temperature'] );
		}
		if ( isset( $options['max_tokens'] ) ) {
			$this->max_tokens = intval( $options['max_tokens'] );
		}

		// Debug logging after override
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Using model: ' . $this->model );
			error_log( 'Using temperature: ' . $this->temperature );
			error_log( 'Using max_tokens: ' . $this->max_tokens );
			error_log( 'Style: ' . ( isset( $options['style'] ) ? $options['style'] : 'not set' ) );
			error_log( 'Layout: ' . ( isset( $options['layout'] ) ? $options['layout'] : 'not set' ) );
		}

		// Build system prompt using prompt engineer.
		$system_prompt = $this->prompt_engineer->build_system_prompt( $options );

		// Debug log system prompt
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'System prompt length: ' . strlen( $system_prompt ) );
			// Log first 500 chars to see if style/layout instructions are included
			error_log( 'System prompt preview: ' . substr( $system_prompt, 0, 500 ) . '...' );
		}

		// Validate and enhance user prompt.
		$validation = $this->prompt_engineer->validate_prompt( $prompt );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		$user_prompt = $this->prompt_engineer->enhance_user_prompt( $prompt, $options );

		// Prepare request body.
		$request_body = array(
			'model'      => $this->model,
			'messages'   => array(
				array(
					'role'    => 'system',
					'content' => $system_prompt,
				),
				array(
					'role'    => 'user',
					'content' => $user_prompt,
				),
			),
			'max_tokens' => $this->max_tokens,
			'temperature' => $this->temperature,
		);

		// Make API request with retry logic.
		$response = $this->make_api_request_with_retry( $request_body );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Track usage.
		$this->track_usage( $response, $prompt );

		return array(
			'content' => $response['content'],
			'usage'   => isset( $response['usage'] ) ? $response['usage'] : array(),
			'model'   => $this->model,
		);
	}

	/**
	 * Make API request with retry logic.
	 *
	 * @since 1.0.0
	 * @param array $request_body Request body.
	 * @return array|WP_Error Response data or error.
	 */
	private function make_api_request_with_retry( $request_body ) {
		$attempt = 0;
		$last_error = null;

		while ( $attempt < $this->max_retries ) {
			$attempt++;

			// Log request in debug mode.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'LayoutBerg API Request (Attempt %d/%d): %s', $attempt, $this->max_retries, wp_json_encode( $request_body ) ) );
			}

			// Make API request.
			$response = wp_remote_post(
				$this->api_endpoint,
				array(
					'timeout' => $this->timeout,
					'headers' => array(
						'Authorization' => 'Bearer ' . $this->api_key,
						'Content-Type'  => 'application/json',
					),
					'body'    => wp_json_encode( $request_body ),
				)
			);

			// Handle connection errors.
			if ( is_wp_error( $response ) ) {
				$last_error = $response;
				$this->security->log_security_event( 'api_connection_error', array( 
					'error' => $response->get_error_message(),
					'attempt' => $attempt,
				) );

				// Don't retry on certain errors
				if ( in_array( $response->get_error_code(), array( 'http_request_failed' ), true ) ) {
					if ( $attempt < $this->max_retries ) {
						sleep( $this->retry_delay * $attempt ); // Exponential backoff
						continue;
					}
				}
				
				return $response;
			}

			// Get response code and body.
			$response_code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			// Log response in debug mode.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'LayoutBerg API Response (Attempt %d/%d): %s', $attempt, $this->max_retries, $body ) );
			}

			// Handle rate limiting (429) and server errors (5xx).
			if ( in_array( $response_code, array( 429, 500, 502, 503, 504 ), true ) ) {
				$error_message = $this->get_error_message_from_response( $data, $response_code );
				$last_error = new \WP_Error( 'api_error_' . $response_code, $error_message );
				
				$this->security->log_security_event( 'api_rate_limit_or_server_error', array(
					'error' => $error_message,
					'code' => $response_code,
					'attempt' => $attempt,
				) );

				if ( $attempt < $this->max_retries ) {
					// For rate limiting, check if we have a retry-after header
					$retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
					$wait_time = $retry_after ? intval( $retry_after ) : ( $this->retry_delay * $attempt );
					
					sleep( $wait_time );
					continue;
				}
			}

			// Handle client errors (4xx except 429).
			if ( $response_code >= 400 && $response_code < 500 && $response_code !== 429 ) {
				$error_message = $this->get_error_message_from_response( $data, $response_code );
				$this->security->log_security_event( 'api_client_error', array(
					'error' => $error_message,
					'code' => $response_code,
				) );
				return new \WP_Error( 'api_error_' . $response_code, $error_message );
			}

			// Handle API errors in response.
			if ( isset( $data['error'] ) ) {
				$error_message = $this->get_error_message_from_response( $data );
				$error_type = isset( $data['error']['type'] ) ? $data['error']['type'] : 'unknown';
				
				// Retry on certain error types
				if ( in_array( $error_type, array( 'server_error', 'engine_error' ), true ) && $attempt < $this->max_retries ) {
					$last_error = new \WP_Error( 'api_error', $error_message );
					sleep( $this->retry_delay * $attempt );
					continue;
				}
				
				$this->security->log_security_event( 'api_error', array( 'error' => $error_message, 'type' => $error_type ) );
				return new \WP_Error( 'api_error', $error_message );
			}

			// Extract generated content.
			if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
				return new \WP_Error( 'invalid_response', __( 'Invalid API response format.', 'layoutberg' ) );
			}

			// Success!
			return array(
				'content' => $data['choices'][0]['message']['content'],
				'usage'   => isset( $data['usage'] ) ? $data['usage'] : array(),
				'raw_response' => $data,
			);
		}

		// All retries exhausted.
		if ( $last_error ) {
			return $last_error;
		}

		return new \WP_Error( 'max_retries_exceeded', __( 'Maximum retry attempts exceeded.', 'layoutberg' ) );
	}

	/**
	 * Get error message from API response.
	 *
	 * @since 1.0.0
	 * @param array $data Response data.
	 * @param int   $status_code HTTP status code.
	 * @return string Error message.
	 */
	private function get_error_message_from_response( $data, $status_code = 0 ) {
		// Check for error message in response
		if ( isset( $data['error']['message'] ) ) {
			return $data['error']['message'];
		}

		// Provide default messages based on status code
		switch ( $status_code ) {
			case 401:
				return __( 'Invalid API key. Please check your OpenAI API key.', 'layoutberg' );
			case 429:
				return __( 'Rate limit exceeded. Please try again later.', 'layoutberg' );
			case 500:
			case 502:
			case 503:
			case 504:
				return __( 'OpenAI server error. Please try again later.', 'layoutberg' );
			default:
				return __( 'Unknown API error occurred.', 'layoutberg' );
		}
	}

	/**
	 * Build system prompt for layout generation.
	 *
	 * @since 1.0.0
	 * @param array $options Generation options.
	 * @return string System prompt.
	 */
	private function build_system_prompt( $options ) {
		$prompt = "You are a WordPress Gutenberg layout designer. Generate valid Gutenberg block markup following these rules:\n";
		$prompt .= "1. Use only core WordPress blocks or specified custom blocks\n";
		$prompt .= "2. Follow proper block comment syntax: <!-- wp:block-name {\"attributes\"} -->\n";
		$prompt .= "3. Ensure all blocks are properly nested and closed\n";
		$prompt .= "4. Include responsive design considerations\n";
		$prompt .= "5. Follow accessibility best practices\n";
		$prompt .= "6. Output only valid block markup, no explanations\n";
		$prompt .= "7. Use semantic HTML structure\n";
		$prompt .= "8. Include appropriate ARIA labels where needed\n";

		// Add style preferences if provided.
		if ( isset( $options['style'] ) ) {
			$prompt .= "\nStyle preference: " . $options['style'];
		}

		// Add color scheme if provided.
		if ( isset( $options['colors'] ) ) {
			$prompt .= "\nColor scheme: " . $options['colors'];
		}

		// Add layout type if provided.
		if ( isset( $options['layout'] ) ) {
			$prompt .= "\nLayout type: " . $options['layout'];
		}

		return $prompt;
	}

	/**
	 * Enhance user prompt with additional context.
	 *
	 * @since 1.0.0
	 * @param string $prompt  Original prompt.
	 * @param array  $options Generation options.
	 * @return string Enhanced prompt.
	 */
	private function enhance_prompt( $prompt, $options ) {
		$enhanced = $prompt;

		// Add context about the page type if available.
		if ( isset( $options['page_type'] ) ) {
			$enhanced .= "\n\nThis is for a " . $options['page_type'] . " page.";
		}

		// Add industry context if available.
		if ( isset( $options['industry'] ) ) {
			$enhanced .= "\nThe website is for the " . $options['industry'] . " industry.";
		}

		// Add target audience if available.
		if ( isset( $options['audience'] ) ) {
			$enhanced .= "\nTarget audience: " . $options['audience'];
		}

		// Add content guidelines.
		$enhanced .= "\n\nGenerate appropriate placeholder content that:";
		$enhanced .= "\n- Matches the layout context";
		$enhanced .= "\n- Includes SEO-friendly headings";
		$enhanced .= "\n- Uses Lorem Ipsum for body text";
		$enhanced .= "\n- Suggests image placements with descriptive alt text";
		$enhanced .= "\n- Includes proper heading hierarchy";

		return $enhanced;
	}

	/**
	 * Track API usage.
	 *
	 * @since 1.0.0
	 * @param array $response_data API response data.
	 * @param string $prompt The original prompt.
	 */
	private function track_usage( $response_data, $prompt = '' ) {
		// Handle both old format and new format
		$usage = null;
		if ( isset( $response_data['usage'] ) ) {
			$usage = $response_data['usage'];
		} elseif ( isset( $response_data['raw_response']['usage'] ) ) {
			$usage = $response_data['raw_response']['usage'];
		}

		if ( ! $usage ) {
			return;
		}

		$user_id    = get_current_user_id();
		$tokens_used = isset( $usage['total_tokens'] ) ? intval( $usage['total_tokens'] ) : 0;

		// Calculate cost.
		$cost = $this->calculate_cost( $tokens_used );

		// Save to generations table.
		global $wpdb;
		$table_name = $wpdb->prefix . 'layoutberg_generations';

		$wpdb->insert(
			$table_name,
			array(
				'user_id'     => $user_id,
				'prompt'      => $prompt,
				'response'    => isset( $response_data['content'] ) ? $response_data['content'] : '',
				'model'       => $this->model,
				'tokens_used' => $tokens_used,
				'status'      => 'completed',
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s' )
		);

		// Update daily usage.
		$this->update_daily_usage( $user_id, $tokens_used, $cost );
	}

	/**
	 * Calculate cost based on tokens used.
	 *
	 * @since 1.0.0
	 * @param int $tokens Number of tokens used.
	 * @return float Cost in USD.
	 */
	private function calculate_cost( $tokens ) {
		// Cost per 1000 tokens.
		$costs = array(
			'gpt-3.5-turbo' => 0.002,
			'gpt-4'         => 0.03,
			'gpt-4-turbo'   => 0.01,
		);

		$cost_per_1k = isset( $costs[ $this->model ] ) ? $costs[ $this->model ] : 0.002;

		return ( $tokens / 1000 ) * $cost_per_1k;
	}

	/**
	 * Update daily usage statistics.
	 *
	 * @since 1.0.0
	 * @param int $user_id     User ID.
	 * @param int $tokens_used Tokens used.
	 * @param float $cost      Cost of generation.
	 */
	private function update_daily_usage( $user_id, $tokens_used, $cost = 0 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'layoutberg_usage';
		$today      = current_time( 'Y-m-d' );

		// Try to update existing record.
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE $table_name 
				SET generations_count = generations_count + 1, 
				    tokens_used = tokens_used + %d,
				    cost = cost + %f
				WHERE user_id = %d AND date = %s",
				$tokens_used,
				$cost,
				$user_id,
				$today
			)
		);

		// If no record exists, insert new one.
		if ( 0 === $updated ) {
			$wpdb->insert(
				$table_name,
				array(
					'user_id'           => $user_id,
					'date'              => $today,
					'generations_count' => 1,
					'tokens_used'       => $tokens_used,
					'cost'              => $cost,
				),
				array( '%d', '%s', '%d', '%d', '%f' )
			);
		}
	}

	/**
	 * Validate API key with OpenAI.
	 *
	 * @since 1.0.0
	 * @param string $api_key API key to validate.
	 * @return bool|WP_Error True if valid, WP_Error on failure.
	 */
	public static function validate_api_key( $api_key ) {
		// Make a simple API call to validate the key.
		$response = wp_remote_get(
			'https://api.openai.com/v1/models',
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 !== $code ) {
			$error_message = __( 'Invalid API key.', 'layoutberg' );
			
			// Try to get more specific error message from response
			if ( ! empty( $body ) ) {
				$decoded = json_decode( $body, true );
				if ( isset( $decoded['error']['message'] ) ) {
					$error_message = $decoded['error']['message'];
				}
			}
			
			// Common error codes
			if ( 401 === $code ) {
				$error_message = __( 'Invalid API key. Please check your OpenAI API key.', 'layoutberg' );
			} elseif ( 429 === $code ) {
				$error_message = __( 'Rate limit exceeded. Please try again later.', 'layoutberg' );
			} elseif ( 403 === $code ) {
				$error_message = __( 'Access denied. Please check your OpenAI account status.', 'layoutberg' );
			}
			
			return new \WP_Error( 'invalid_api_key', $error_message );
		}

		return true;
	}

	/**
	 * Get available models.
	 *
	 * @since 1.0.0
	 * @return array Available models.
	 */
	public function get_available_models() {
		return array(
			'gpt-3.5-turbo' => array(
				'name'        => 'GPT-3.5 Turbo',
				'description' => __( 'Fast and affordable', 'layoutberg' ),
				'max_tokens'  => 4096,
				'cost_per_1k' => 0.002,
			),
			'gpt-4' => array(
				'name'        => 'GPT-4',
				'description' => __( 'Most capable model', 'layoutberg' ),
				'max_tokens'  => 8192,
				'cost_per_1k' => 0.03,
			),
			'gpt-4-turbo' => array(
				'name'        => 'GPT-4 Turbo',
				'description' => __( 'Fast and capable', 'layoutberg' ),
				'max_tokens'  => 128000,
				'cost_per_1k' => 0.01,
			),
		);
	}

	/**
	 * Check rate limits for current user.
	 *
	 * @since 1.0.0
	 * @return bool|WP_Error True if within limits, WP_Error if exceeded.
	 */
	private function check_rate_limit() {
		$user_id = get_current_user_id();
		
		// Get user tier
		$user_tier = $this->get_user_tier();
		
		// Get rate limits from options
		$options = get_option( 'layoutberg_options', array() );
		$rate_limits = isset( $options['rate_limit'] ) ? $options['rate_limit'] : array(
			'free' => array(
				'hour' => 5,
				'day'  => 10,
			),
			'pro' => array(
				'hour' => 20,
				'day'  => 100,
			),
			'business' => array(
				'hour' => 50,
				'day'  => 500,
			),
		);

		// Get limits for user tier
		$tier_limits = isset( $rate_limits[ $user_tier ] ) ? $rate_limits[ $user_tier ] : $rate_limits['free'];
		
		// Check hourly limit
		$hourly_count = $this->get_generation_count( $user_id, 'hour' );
		if ( $hourly_count >= $tier_limits['hour'] ) {
			return new \WP_Error( 
				'rate_limit_hourly', 
				sprintf( 
					__( 'Hourly rate limit exceeded. You have used %d of %d generations this hour.', 'layoutberg' ),
					$hourly_count,
					$tier_limits['hour']
				)
			);
		}

		// Check daily limit
		$daily_count = $this->get_generation_count( $user_id, 'day' );
		if ( $daily_count >= $tier_limits['day'] ) {
			return new \WP_Error( 
				'rate_limit_daily', 
				sprintf( 
					__( 'Daily rate limit exceeded. You have used %d of %d generations today.', 'layoutberg' ),
					$daily_count,
					$tier_limits['day']
				)
			);
		}

		return true;
	}

	/**
	 * Get user tier.
	 *
	 * @since 1.0.0
	 * @return string User tier (free, pro, or business).
	 */
	private function get_user_tier() {
		// Check for license
		$license = get_option( 'layoutberg_license_key' );
		if ( empty( $license ) ) {
			return 'free';
		}

		// Check license type
		$license_data = get_option( 'layoutberg_license_data' );
		if ( isset( $license_data['tier'] ) ) {
			return $license_data['tier'];
		}

		// Default to free if no tier found
		return 'free';
	}

	/**
	 * Get generation count for a user within a time period.
	 *
	 * @since 1.0.0
	 * @param int    $user_id User ID.
	 * @param string $period  Time period (hour or day).
	 * @return int Generation count.
	 */
	private function get_generation_count( $user_id, $period = 'day' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'layoutberg_generations';

		if ( 'hour' === $period ) {
			$time_ago = date( 'Y-m-d H:i:s', strtotime( '-1 hour' ) );
		} else {
			$time_ago = date( 'Y-m-d 00:00:00' );
		}

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name 
				WHERE user_id = %d 
				AND created_at >= %s 
				AND status = 'completed'",
				$user_id,
				$time_ago
			)
		);

		return intval( $count );
	}

	/**
	 * Count tokens in text (approximate).
	 *
	 * @since 1.0.0
	 * @param string $text Text to count tokens for.
	 * @return int Approximate token count.
	 */
	public function count_tokens( $text ) {
		// Simple approximation: 1 token ≈ 4 characters
		// This is a rough estimate; actual tokenization is more complex
		return ceil( strlen( $text ) / 4 );
	}

	/**
	 * Estimate cost for a generation.
	 *
	 * @since 1.0.0
	 * @param string $prompt Prompt text.
	 * @param array  $options Generation options.
	 * @return float Estimated cost in USD.
	 */
	public function estimate_cost( $prompt, $options = array() ) {
		// Count prompt tokens
		$prompt_tokens = $this->count_tokens( $prompt );
		
		// Estimate completion tokens (use max_tokens or default)
		$max_tokens = isset( $options['max_tokens'] ) ? $options['max_tokens'] : $this->max_tokens;
		
		// Total tokens
		$total_tokens = $prompt_tokens + $max_tokens;
		
		// Calculate cost
		return $this->calculate_cost( $total_tokens );
	}
}