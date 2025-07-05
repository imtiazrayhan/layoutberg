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
	private $api_endpoint;

	/**
	 * Current provider (openai or claude).
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string
	 */
	private $provider;

	/**
	 * OpenAI API key.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string
	 */
	private $openai_api_key;

	/**
	 * Claude API key.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string
	 */
	private $claude_api_key;

	/**
	 * Current API key in use.
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

		// Get and decrypt OpenAI API key.
		$openai_encrypted = isset( $options['openai_api_key'] ) ? $options['openai_api_key'] : '';
		if ( empty( $openai_encrypted ) && isset( $options['api_key'] ) ) {
			// Backward compatibility
			$openai_encrypted = $options['api_key'];
		}
		if ( ! empty( $openai_encrypted ) ) {
			$decrypted = $this->security->decrypt_api_key( $openai_encrypted );
			$this->openai_api_key = $decrypted !== false ? $decrypted : '';
		} else {
			$this->openai_api_key = '';
		}

		// Get and decrypt Claude API key.
		$claude_encrypted = isset( $options['claude_api_key'] ) ? $options['claude_api_key'] : '';
		if ( ! empty( $claude_encrypted ) ) {
			$decrypted = $this->security->decrypt_api_key( $claude_encrypted );
			$this->claude_api_key = $decrypted !== false ? $decrypted : '';
		} else {
			$this->claude_api_key = '';
		}

		// Get other settings.
		$this->model       = isset( $options['model'] ) ? $options['model'] : 'gpt-3.5-turbo';
		$this->max_tokens  = isset( $options['max_tokens'] ) ? intval( $options['max_tokens'] ) : 2000;
		// Ensure max tokens doesn't exceed model limit
		if ( $this->max_tokens > 4096 ) {
			$this->max_tokens = 4096;
		}
		$this->temperature = isset( $options['temperature'] ) ? floatval( $options['temperature'] ) : 0.7;
		
		// Determine provider based on model
		$this->set_provider_from_model( $this->model );
	}
	
	/**
	 * Set provider and API key based on model.
	 *
	 * @since 1.0.0
	 * @param string $model Model name.
	 */
	private function set_provider_from_model( $model ) {
		if ( strpos( $model, 'claude' ) === 0 ) {
			$this->provider = 'claude';
			$this->api_key = $this->claude_api_key;
			$this->api_endpoint = 'https://api.anthropic.com/v1/messages';
		} else {
			$this->provider = 'openai';
			$this->api_key = $this->openai_api_key;
			$this->api_endpoint = 'https://api.openai.com/v1/chat/completions';
		}
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
			$provider_name = $this->provider === 'claude' ? 'Claude' : 'OpenAI';
			return new \WP_Error( 'no_api_key', sprintf( __( '%s API key is not configured.', 'layoutberg' ), $provider_name ) );
		}

		// Rate limiting removed - unlimited generations allowed

		// Override settings with options if provided
		if ( isset( $options['model'] ) ) {
			$this->model = $options['model'];
			// Update provider based on new model
			$this->set_provider_from_model( $this->model );
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

		// Add user prompt length to options for token management
		$options['user_prompt_length'] = strlen( $prompt );
		
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
		
		// Estimate token usage
		$system_tokens = $this->prompt_engineer->estimate_token_count( $system_prompt );
		$user_tokens = $this->prompt_engineer->estimate_token_count( $user_prompt );
		$total_prompt_tokens = $system_tokens + $user_tokens;
		
		// Log token estimates
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Estimated tokens - System: ' . $system_tokens . ', User: ' . $user_tokens . ', Total: ' . $total_prompt_tokens );
		}
		
		// Check against model limits using Model Config
		$model_config = \DotCamp\LayoutBerg\Model_Config::get_model( $this->model );
		if ( ! $model_config ) {
			return new \WP_Error(
				'invalid_model',
				sprintf( __( 'Invalid model selected: %s', 'layoutberg' ), $this->model )
			);
		}
		
		// Calculate safe max tokens for generation
		$max_tokens = \DotCamp\LayoutBerg\Model_Config::calculate_max_tokens(
			$this->model, 
			$total_prompt_tokens,
			500 // Buffer
		);
		
		if ( $max_tokens < 500 ) {
			return new \WP_Error(
				'prompt_too_long',
				__( 'Your prompt is too long for the selected model. Try a shorter description or use a model with a larger context window.', 'layoutberg' )
			);
		}
		
		// Update max_tokens
		$this->max_tokens = min( $max_tokens, $options['max_tokens'] ?? 4096 );
		
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Model: ' . $this->model . ', Context window: ' . $model_config['context_window'] . ', Max output: ' . $model_config['max_output'] );
			error_log( 'Adjusted max_tokens to: ' . $this->max_tokens );
		}

		// Prepare request body based on provider.
		if ( $this->provider === 'claude' ) {
			// Claude format - system prompt is separate
			$request_body = array(
				'model'       => $this->model,
				'max_tokens'  => $this->max_tokens,
				'temperature' => $this->temperature,
				'system'      => $system_prompt,
				'messages'    => array(
					array(
						'role'    => 'user',
						'content' => $user_prompt,
					),
				),
			);
		} else {
			// OpenAI format - system prompt in messages
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
		}

		// Make API request with retry logic.
		$response = $this->make_api_request_with_retry( $request_body );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Debug token usage
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && isset( $response['usage'] ) ) {
			error_log( 'LayoutBerg Token Usage:' );
			if ( $this->provider === 'claude' ) {
				error_log( '- Input tokens: ' . ( $response['usage']['input_tokens'] ?? 'not set' ) );
				error_log( '- Output tokens: ' . ( $response['usage']['output_tokens'] ?? 'not set' ) );
				error_log( '- Total tokens: ' . ( ( $response['usage']['input_tokens'] ?? 0 ) + ( $response['usage']['output_tokens'] ?? 0 ) ) );
			} else {
				error_log( '- Prompt tokens: ' . ( $response['usage']['prompt_tokens'] ?? 'not set' ) );
				error_log( '- Completion tokens: ' . ( $response['usage']['completion_tokens'] ?? 'not set' ) );
				error_log( '- Total tokens: ' . ( $response['usage']['total_tokens'] ?? 'not set' ) );
			}
		}

		// Track usage.
		$this->track_usage( $response, $prompt );

		return array(
			'content' => $response['content'],
			'usage'   => isset( $response['usage'] ) ? $response['usage'] : array(),
			'model'   => $this->model,
			'prompts' => array(
				'system' => $system_prompt,
				'user'   => $user_prompt,
				'original_user' => $prompt,
			),
		);
	}
	
	/**
	 * Generate layout using simplified approach.
	 *
	 * @since 1.0.0
	 * @param string $prompt  User prompt.
	 * @param array  $options Generation options.
	 * @return array|WP_Error Generated result or error.
	 */
	public function generate_layout_simple( $prompt, $options = array() ) {
		// Check API key is set based on provider.
		if ( empty( $this->api_key ) ) {
			$provider_name = $this->provider === 'claude' ? 'Claude' : 'OpenAI';
			return new \WP_Error( 'no_api_key', sprintf( __( '%s API key is not configured.', 'layoutberg' ), $provider_name ) );
		}
		
		// Use Simple_Prompt_Engineer for minimal prompt
		if ( ! class_exists( '\DotCamp\LayoutBerg\Simple_Prompt_Engineer' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simple-prompt-engineer.php';
		}
		$simple_engineer = new Simple_Prompt_Engineer();
		
		// Build simple system prompt
		$system_prompt = $simple_engineer->build_system_prompt( $options );
		
		// Validate user prompt
		$validation = $simple_engineer->validate_prompt( $prompt );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		// Use lower temperature for consistency (like the working plugin)
		$temperature = 0.2;
		
		// Fixed max tokens (like the working plugin)
		$max_tokens = 2000;
		
		// Prepare request body based on provider
		if ( $this->provider === 'claude' ) {
			$request_body = array(
				'model'       => $this->model,
				'max_tokens'  => $max_tokens,
				'temperature' => $temperature,
				'system'      => $system_prompt,
				'messages'    => array(
					array(
						'role'    => 'user',
						'content' => $prompt, // Use original prompt directly
					),
				),
			);
		} else {
			// OpenAI format
			$request_body = array(
				'model'      => $this->model,
				'messages'   => array(
					array(
						'role'    => 'system',
						'content' => $system_prompt,
					),
					array(
						'role'    => 'user',
						'content' => $prompt, // Use original prompt directly
					),
				),
				'max_tokens' => $max_tokens,
				'temperature' => $temperature,
			);
		}
		
		// Make API request with retry logic
		$response = $this->make_api_request_with_retry( $request_body );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		
		// Track usage
		$this->track_usage( $response, $prompt );
		
		return array(
			'content' => $response['content'],
			'usage'   => isset( $response['usage'] ) ? $response['usage'] : array(),
			'model'   => $this->model,
			'prompts' => array(
				'system' => $system_prompt,
				'user'   => $prompt,
			),
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

			// Prepare headers based on provider.
			$headers = array( 'Content-Type' => 'application/json' );
			if ( $this->provider === 'claude' ) {
				$headers['x-api-key'] = $this->api_key;
				$headers['anthropic-version'] = '2023-06-01';
			} else {
				$headers['Authorization'] = 'Bearer ' . $this->api_key;
			}

			// Make API request.
			$response = wp_remote_post(
				$this->api_endpoint,
				array(
					'timeout' => $this->timeout,
					'headers' => $headers,
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
				$error_code = isset( $data['error']['code'] ) ? $data['error']['code'] : 'unknown';
				
				// Handle specific error types
				if ( $error_code === 'context_length_exceeded' ) {
					return new \WP_Error( 
						'context_length_exceeded', 
						__( 'Your request is too long. Please try a shorter description or switch to GPT-4 Turbo which supports longer requests.', 'layoutberg' )
					);
				}
				
				// Retry on certain error types
				if ( in_array( $error_type, array( 'server_error', 'engine_error' ), true ) && $attempt < $this->max_retries ) {
					$last_error = new \WP_Error( 'api_error', $error_message );
					sleep( $this->retry_delay * $attempt );
					continue;
				}
				
				$this->security->log_security_event( 'api_error', array( 'error' => $error_message, 'type' => $error_type ) );
				return new \WP_Error( 'api_error', $error_message );
			}

			// Extract generated content based on provider.
			if ( $this->provider === 'claude' ) {
				// Claude response format
				if ( ! isset( $data['content'][0]['text'] ) ) {
					return new \WP_Error( 'invalid_response', __( 'Invalid Claude API response format.', 'layoutberg' ) );
				}
				
				// Debug full usage data
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && isset( $data['usage'] ) ) {
					error_log( 'LayoutBerg Raw API Usage: ' . json_encode( $data['usage'] ) );
				}
				
				return array(
					'content' => $data['content'][0]['text'],
					'usage'   => isset( $data['usage'] ) ? $data['usage'] : array(),
					'raw_response' => $data,
				);
			} else {
				// OpenAI response format
				if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
					return new \WP_Error( 'invalid_response', __( 'Invalid OpenAI API response format.', 'layoutberg' ) );
				}
				
				// Debug full usage data
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG && isset( $data['usage'] ) ) {
					error_log( 'LayoutBerg Raw API Usage: ' . json_encode( $data['usage'] ) );
				}
				
				return array(
					'content' => $data['choices'][0]['message']['content'],
					'usage'   => isset( $data['usage'] ) ? $data['usage'] : array(),
					'raw_response' => $data,
				);
			}
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
		$provider_name = $this->provider === 'claude' ? 'Claude' : 'OpenAI';
		switch ( $status_code ) {
			case 401:
				return sprintf( __( 'Invalid API key. Please check your %s API key.', 'layoutberg' ), $provider_name );
			case 429:
				return __( 'Rate limit exceeded. Please try again later.', 'layoutberg' );
			case 500:
			case 502:
			case 503:
			case 504:
				return sprintf( __( '%s server error. Please try again later.', 'layoutberg' ), $provider_name );
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
		// This method is deprecated - using Prompt_Engineer class instead
		// Kept for backward compatibility
		$prompt = "You are a WordPress Gutenberg layout designer. Generate valid Gutenberg block markup following these rules:\n";
		$prompt .= "1. Use only core WordPress blocks\n";
		$prompt .= "2. Follow exact block comment syntax: <!-- wp:block-name {\"attributes\"} -->\n";
		$prompt .= "3. Ensure all blocks are properly closed: <!-- /wp:block-name -->\n";
		$prompt .= "4. Use wp-block-heading class for all headings\n";
		$prompt .= "5. Use wp-element-button class for all button links\n";
		$prompt .= "6. Output only valid block markup, no explanations\n";
		$prompt .= "7. Use proper cover block structure with image, background dim span, and inner container\n";
		$prompt .= "8. For images use Unsplash or placeholder services\n";

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

		$user_id = get_current_user_id();
		
		// Calculate total tokens based on provider
		$tokens_used = 0;
		if ( $this->provider === 'claude' ) {
			// Claude uses input_tokens and output_tokens
			$input_tokens = isset( $usage['input_tokens'] ) ? intval( $usage['input_tokens'] ) : 0;
			$output_tokens = isset( $usage['output_tokens'] ) ? intval( $usage['output_tokens'] ) : 0;
			$tokens_used = $input_tokens + $output_tokens;
		} else {
			// OpenAI uses total_tokens or we calculate from prompt + completion
			if ( isset( $usage['total_tokens'] ) ) {
				$tokens_used = intval( $usage['total_tokens'] );
			} elseif ( isset( $usage['prompt_tokens'] ) && isset( $usage['completion_tokens'] ) ) {
				$tokens_used = intval( $usage['prompt_tokens'] ) + intval( $usage['completion_tokens'] );
			}
		}

		// Calculate cost.
		$cost = $this->calculate_cost( $tokens_used, $usage );

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
	 * @param array $usage Usage data with input/output tokens.
	 * @return float Cost in USD.
	 */
	private function calculate_cost( $tokens, $usage = null ) {
		// Use Model Config for accurate cost calculation
		if ( $usage && isset( $usage['input_tokens'] ) && isset( $usage['output_tokens'] ) ) {
			// Use separate input/output tokens for more accurate pricing
			$input_tokens = intval( $usage['input_tokens'] );
			$output_tokens = intval( $usage['output_tokens'] );
			
			return \DotCamp\LayoutBerg\Model_Config::estimate_cost( $this->model, $input_tokens, $output_tokens );
		}
		
		// Fallback to combined token calculation
		$model_config = \DotCamp\LayoutBerg\Model_Config::get_model( $this->model );
		if ( $model_config ) {
			// Estimate input/output split (typically 70/30 for generation tasks)
			$input_tokens = intval( $tokens * 0.7 );
			$output_tokens = intval( $tokens * 0.3 );
			
			return \DotCamp\LayoutBerg\Model_Config::estimate_cost( $this->model, $input_tokens, $output_tokens );
		}
		
		// Fallback to old method if model not found
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
	 * Validate API key with provider.
	 *
	 * @since 1.0.0
	 * @param string $api_key API key to validate.
	 * @param string $provider Provider name (openai or claude).
	 * @return bool|WP_Error True if valid, WP_Error on failure.
	 */
	public static function validate_api_key( $api_key, $provider = 'openai' ) {
		// Determine endpoint and headers based on provider
		if ( $provider === 'claude' ) {
			$endpoint = 'https://api.anthropic.com/v1/messages';
			$headers = array(
				'x-api-key' => $api_key,
				'anthropic-version' => '2023-06-01',
				'Content-Type' => 'application/json',
			);
			// Claude doesn't have a simple GET endpoint, so we'll make a minimal POST request
			$response = wp_remote_post(
				$endpoint,
				array(
					'timeout' => 30,
					'headers' => $headers,
					'body' => wp_json_encode( array(
						'model' => 'claude-3-haiku-20240307',
						'max_tokens' => 1,
						'messages' => array(
							array( 'role' => 'user', 'content' => 'test' )
						),
					) ),
				)
			);
		} else {
			// OpenAI validation
			$response = wp_remote_get(
				'https://api.openai.com/v1/models',
				array(
					'timeout' => 30,
					'headers' => array(
						'Authorization' => 'Bearer ' . $api_key,
					),
				)
			);
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		// Claude returns 200 for success, but with usage exceeded it might be different
		$valid_codes = $provider === 'claude' ? array( 200 ) : array( 200 );
		if ( ! in_array( $code, $valid_codes, true ) ) {
			$error_message = __( 'Invalid API key.', 'layoutberg' );
			
			// Try to get more specific error message from response
			if ( ! empty( $body ) ) {
				$decoded = json_decode( $body, true );
				if ( isset( $decoded['error']['message'] ) ) {
					$error_message = $decoded['error']['message'];
				}
			}
			
			// Common error codes
			$provider_name = $provider === 'claude' ? 'Claude' : 'OpenAI';
			if ( 401 === $code ) {
				$error_message = sprintf( __( 'Invalid API key. Please check your %s API key.', 'layoutberg' ), $provider_name );
			} elseif ( 429 === $code ) {
				$error_message = __( 'Rate limit exceeded. Please try again later.', 'layoutberg' );
			} elseif ( 403 === $code ) {
				$error_message = sprintf( __( 'Access denied. Please check your %s account status.', 'layoutberg' ), $provider_name );
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
		// Use Model Config for consistent model information
		$models = \DotCamp\LayoutBerg\Model_Config::get_all_models();
		$formatted_models = array();
		
		foreach ( $models as $model_id => $config ) {
			$formatted_models[ $model_id ] = array(
				'name'        => $config['name'],
				'description' => $config['description'],
				'max_tokens'  => $config['max_output'],
				'context_window' => $config['context_window'],
				'cost_per_1k_input' => $config['cost_per_1k_input'],
				'cost_per_1k_output' => $config['cost_per_1k_output'],
				'provider'    => $config['provider'],
			);
		}
		
		return $formatted_models;
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