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
	 * Security manager instance.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    Security_Manager
	 */
	private $security;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->security = new Security_Manager();
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
		$this->api_key = $this->security->decrypt_api_key( $encrypted_key );

		// Get other settings.
		$this->model       = isset( $options['model'] ) ? $options['model'] : 'gpt-3.5-turbo';
		$this->max_tokens  = isset( $options['max_tokens'] ) ? intval( $options['max_tokens'] ) : 2000;
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
		// Check if API key is set.
		if ( empty( $this->api_key ) ) {
			return new \WP_Error( 'no_api_key', __( 'OpenAI API key is not configured.', 'layoutberg' ) );
		}

		// Build system prompt.
		$system_prompt = $this->build_system_prompt( $options );

		// Build user prompt.
		$user_prompt = $this->enhance_prompt( $prompt, $options );

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

		// Log request in debug mode.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'LayoutBerg API Request: ' . wp_json_encode( $request_body ) );
		}

		// Make API request.
		$response = wp_remote_post(
			$this->api_endpoint,
			array(
				'timeout' => 60,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $request_body ),
			)
		);

		// Handle errors.
		if ( is_wp_error( $response ) ) {
			$this->security->log_security_event( 'api_error', array( 'error' => $response->get_error_message() ) );
			return $response;
		}

		// Get response body.
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Log response in debug mode.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'LayoutBerg API Response: ' . $body );
		}

		// Check for API errors.
		if ( isset( $data['error'] ) ) {
			$error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Unknown API error.', 'layoutberg' );
			$this->security->log_security_event( 'api_error', array( 'error' => $error_message ) );
			return new \WP_Error( 'api_error', $error_message );
		}

		// Extract generated content.
		if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
			return new \WP_Error( 'invalid_response', __( 'Invalid API response format.', 'layoutberg' ) );
		}

		$generated_content = $data['choices'][0]['message']['content'];

		// Track usage.
		$this->track_usage( $data );

		return array(
			'content' => $generated_content,
			'usage'   => isset( $data['usage'] ) ? $data['usage'] : array(),
			'model'   => $this->model,
		);
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
	 */
	private function track_usage( $response_data ) {
		if ( ! isset( $response_data['usage'] ) ) {
			return;
		}

		$usage      = $response_data['usage'];
		$user_id    = get_current_user_id();
		$tokens_used = isset( $usage['total_tokens'] ) ? intval( $usage['total_tokens'] ) : 0;

		// Calculate cost.
		$cost = $this->calculate_cost( $tokens_used );

		// Save to database.
		global $wpdb;
		$table_name = $wpdb->prefix . 'layoutberg_generations';

		$wpdb->insert(
			$table_name,
			array(
				'user_id'     => $user_id,
				'model'       => $this->model,
				'tokens_used' => $tokens_used,
				'cost'        => $cost,
				'status'      => 'completed',
			),
			array( '%d', '%s', '%d', '%f', '%s' )
		);

		// Update daily usage.
		$this->update_daily_usage( $user_id, $tokens_used );
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
	 */
	private function update_daily_usage( $user_id, $tokens_used ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'layoutberg_usage';
		$today      = current_time( 'Y-m-d' );

		// Try to update existing record.
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE $table_name 
				SET generations_count = generations_count + 1, 
				    tokens_used = tokens_used + %d 
				WHERE user_id = %d AND date = %s",
				$tokens_used,
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
				),
				array( '%d', '%s', '%d', '%d' )
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
	public function validate_api_key( $api_key ) {
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

		if ( 200 !== $code ) {
			return new \WP_Error( 'invalid_api_key', __( 'Invalid API key.', 'layoutberg' ) );
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
}