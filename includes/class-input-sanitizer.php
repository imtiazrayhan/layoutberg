<?php
/**
 * Input sanitizer class for comprehensive input validation and sanitization.
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
 * Input sanitizer class.
 *
 * @since 1.0.0
 */
class Input_Sanitizer {

	/**
	 * Maximum prompt length.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    int
	 */
	private $max_prompt_length = 2000;

	/**
	 * Minimum prompt length.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    int
	 */
	private $min_prompt_length = 10;

	/**
	 * Allowed HTML tags for content.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $allowed_html_tags = array();

	/**
	 * Blocked keywords and patterns.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $blocked_patterns = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init_allowed_html_tags();
		$this->init_blocked_patterns();
	}

	/**
	 * Sanitize prompt input.
	 *
	 * @since 1.0.0
	 * @param string $prompt Raw prompt input.
	 * @return string|WP_Error Sanitized prompt or error.
	 */
	public function sanitize_prompt( $prompt ) {
		// Basic sanitization.
		$prompt = sanitize_textarea_field( $prompt );
		$prompt = trim( $prompt );

		// Length validation.
		$length_check = $this->validate_prompt_length( $prompt );
		if ( is_wp_error( $length_check ) ) {
			return $length_check;
		}

		// Content validation.
		$content_check = $this->validate_prompt_content( $prompt );
		if ( is_wp_error( $content_check ) ) {
			return $content_check;
		}

		// Remove potentially harmful content.
		$prompt = $this->remove_harmful_content( $prompt );

		// Normalize whitespace.
		$prompt = $this->normalize_whitespace( $prompt );

		// Apply filters.
		$prompt = apply_filters( 'layoutberg_sanitized_prompt', $prompt );

		return $prompt;
	}

	/**
	 * Sanitize generation settings.
	 *
	 * @since 1.0.0
	 * @param array $settings Raw settings array.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $settings ) {
		$defaults = array(
			'model'       => 'gpt-3.5-turbo',
			'temperature' => 0.7,
			'maxTokens'   => 4000,
			'style'       => 'modern',
			'layout'      => 'single-column',
		);

		$settings = wp_parse_args( $settings, $defaults );

		// Sanitize individual settings.
		$settings['model'] = $this->sanitize_model( $settings['model'] );
		$settings['temperature'] = $this->sanitize_temperature( $settings['temperature'] );
		$settings['maxTokens'] = $this->sanitize_max_tokens( $settings['maxTokens'] );
		$settings['style'] = $this->sanitize_style( $settings['style'] );
		$settings['layout'] = $this->sanitize_layout( $settings['layout'] );

		// Remove any unknown settings.
		$allowed_keys = array_keys( $defaults );
		$settings = array_intersect_key( $settings, array_flip( $allowed_keys ) );

		return $settings;
	}

	/**
	 * Sanitize API key input.
	 *
	 * @since 1.0.0
	 * @param string $api_key Raw API key.
	 * @return string|WP_Error Sanitized API key or error.
	 */
	public function sanitize_api_key( $api_key ) {
		// Remove whitespace.
		$api_key = trim( $api_key );

		// Basic sanitization.
		$api_key = sanitize_text_field( $api_key );

		// Validate format (OpenAI API keys start with 'sk-').
		// Updated to be more flexible with key format as OpenAI has changed formats over time
		if ( ! empty( $api_key ) && ! preg_match( '/^sk-[a-zA-Z0-9\-_]{20,}$/', $api_key ) ) {
			return new \WP_Error(
				'invalid_api_key_format',
				__( 'Invalid API key format. OpenAI API keys should start with "sk-".', 'layoutberg' )
			);
		}

		return $api_key;
	}

	/**
	 * Sanitize template data.
	 *
	 * @since 1.0.0
	 * @param array $template_data Raw template data.
	 * @return array|WP_Error Sanitized template data or error.
	 */
	public function sanitize_template_data( $template_data ) {
		$sanitized = array();

		// Required fields.
		$required_fields = array( 'name', 'content' );
		foreach ( $required_fields as $field ) {
			if ( empty( $template_data[ $field ] ) ) {
				return new \WP_Error(
					'missing_required_field',
					sprintf( __( 'Required field "%s" is missing.', 'layoutberg' ), $field )
				);
			}
		}

		// Sanitize name.
		$sanitized['name'] = sanitize_text_field( $template_data['name'] );
		if ( strlen( $sanitized['name'] ) > 100 ) {
			return new \WP_Error(
				'name_too_long',
				__( 'Template name must be 100 characters or less.', 'layoutberg' )
			);
		}

		// Sanitize content.
		$sanitized['content'] = wp_kses( $template_data['content'], $this->allowed_html_tags );

		// Sanitize optional fields.
		$sanitized['description'] = ! empty( $template_data['description'] ) 
			? sanitize_textarea_field( $template_data['description'] ) 
			: '';

		$sanitized['category'] = ! empty( $template_data['category'] ) 
			? sanitize_text_field( $template_data['category'] ) 
			: 'general';

		$sanitized['tags'] = ! empty( $template_data['tags'] ) 
			? $this->sanitize_tags( $template_data['tags'] ) 
			: array();

		return $sanitized;
	}

	/**
	 * Sanitize user options.
	 *
	 * @since 1.0.0
	 * @param array $options Raw options array.
	 * @return array Sanitized options.
	 */
	public function sanitize_user_options( $options ) {
		$sanitized = array();

		// API key.
		if ( isset( $options['api_key'] ) ) {
			$api_key_result = $this->sanitize_api_key( $options['api_key'] );
			if ( ! is_wp_error( $api_key_result ) ) {
				$sanitized['api_key'] = $api_key_result;
			}
		}

		// Model preferences.
		if ( isset( $options['model'] ) ) {
			$sanitized['model'] = $this->sanitize_model( $options['model'] );
		}

		// Cache settings.
		if ( isset( $options['cache_enabled'] ) ) {
			$sanitized['cache_enabled'] = (bool) $options['cache_enabled'];
		}

		if ( isset( $options['cache_duration'] ) ) {
			$duration = intval( $options['cache_duration'] );
			$sanitized['cache_duration'] = max( 300, min( 86400, $duration ) ); // 5 min to 24 hours.
		}

		// Rate limiting.
		if ( isset( $options['rate_limit'] ) && is_array( $options['rate_limit'] ) ) {
			$sanitized['rate_limit'] = $this->sanitize_rate_limits( $options['rate_limit'] );
		}

		// Debug settings.
		if ( isset( $options['debug_enabled'] ) ) {
			$sanitized['debug_enabled'] = (bool) $options['debug_enabled'];
		}

		return $sanitized;
	}

	/**
	 * Validate prompt length.
	 *
	 * @since 1.0.0
	 * @param string $prompt Prompt to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_prompt_length( $prompt ) {
		$length = strlen( $prompt );

		if ( $length < $this->min_prompt_length ) {
			return new \WP_Error(
				'prompt_too_short',
				sprintf(
					__( 'Prompt must be at least %d characters long.', 'layoutberg' ),
					$this->min_prompt_length
				)
			);
		}

		if ( $length > $this->max_prompt_length ) {
			return new \WP_Error(
				'prompt_too_long',
				sprintf(
					__( 'Prompt must be %d characters or less.', 'layoutberg' ),
					$this->max_prompt_length
				)
			);
		}

		return true;
	}

	/**
	 * Validate prompt content.
	 *
	 * @since 1.0.0
	 * @param string $prompt Prompt to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_prompt_content( $prompt ) {
		// Check for blocked patterns.
		foreach ( $this->blocked_patterns as $pattern => $message ) {
			if ( preg_match( $pattern, $prompt ) ) {
				return new \WP_Error( 'blocked_content', $message );
			}
		}

		// Check for excessive repetition.
		if ( $this->has_excessive_repetition( $prompt ) ) {
			return new \WP_Error(
				'excessive_repetition',
				__( 'Prompt contains excessive repetition. Please rephrase.', 'layoutberg' )
			);
		}

		// Check for potential injection attempts.
		if ( $this->has_injection_patterns( $prompt ) ) {
			return new \WP_Error(
				'potential_injection',
				__( 'Prompt contains potentially harmful content.', 'layoutberg' )
			);
		}

		return true;
	}

	/**
	 * Remove harmful content from prompt.
	 *
	 * @since 1.0.0
	 * @param string $prompt Prompt to clean.
	 * @return string Cleaned prompt.
	 */
	private function remove_harmful_content( $prompt ) {
		// Remove HTML tags.
		$prompt = wp_strip_all_tags( $prompt );

		// Remove script-like patterns.
		$prompt = preg_replace( '/\b(javascript|script|eval|function)\s*[\(\:]/i', '', $prompt );

		// Remove SQL-like patterns.
		$prompt = preg_replace( '/\b(select|insert|update|delete|drop|union)\s+/i', '', $prompt );

		// Remove file path patterns.
		$prompt = preg_replace( '/[\/\\\\][a-zA-Z0-9_\-\.\/\\\\]+/', '', $prompt );

		// Remove excessive special characters.
		$prompt = preg_replace( '/[^\w\s\.\,\!\?\:\;\-\(\)\'\"]+/u', ' ', $prompt );

		return $prompt;
	}

	/**
	 * Normalize whitespace in prompt.
	 *
	 * @since 1.0.0
	 * @param string $prompt Prompt to normalize.
	 * @return string Normalized prompt.
	 */
	private function normalize_whitespace( $prompt ) {
		// Replace multiple whitespace characters with single space.
		$prompt = preg_replace( '/\s+/', ' ', $prompt );

		// Trim whitespace.
		$prompt = trim( $prompt );

		return $prompt;
	}

	/**
	 * Check for excessive repetition in text.
	 *
	 * @since 1.0.0
	 * @param string $text Text to check.
	 * @return bool True if excessive repetition found.
	 */
	private function has_excessive_repetition( $text ) {
		$words = str_word_count( $text, 1 );
		$word_count = array_count_values( $words );

		$total_words = count( $words );
		$max_repetition_ratio = 0.3; // 30% repetition threshold.

		foreach ( $word_count as $word => $count ) {
			if ( strlen( $word ) > 3 && ( $count / $total_words ) > $max_repetition_ratio ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check for potential injection patterns.
	 *
	 * @since 1.0.0
	 * @param string $text Text to check.
	 * @return bool True if injection patterns found.
	 */
	private function has_injection_patterns( $text ) {
		$injection_patterns = array(
			'/\b(eval|exec|system|shell_exec|passthru|file_get_contents)\s*\(/i',
			'/\$\{[^}]*\}/', // Variable expansion.
			'/\<\?php/i',
			'/\<script/i',
			'/on\w+\s*=/i', // Event handlers.
		);

		foreach ( $injection_patterns as $pattern ) {
			if ( preg_match( $pattern, $text ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Sanitize AI model selection.
	 *
	 * @since 1.0.0
	 * @param string $model Model name.
	 * @return string Sanitized model.
	 */
	private function sanitize_model( $model ) {
		$allowed_models = array( 'gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo' );
		
		$model = sanitize_text_field( $model );
		
		return in_array( $model, $allowed_models, true ) ? $model : 'gpt-3.5-turbo';
	}

	/**
	 * Sanitize temperature setting.
	 *
	 * @since 1.0.0
	 * @param mixed $temperature Temperature value.
	 * @return float Sanitized temperature.
	 */
	private function sanitize_temperature( $temperature ) {
		$temperature = floatval( $temperature );
		return max( 0, min( 2, $temperature ) );
	}

	/**
	 * Sanitize max tokens setting.
	 *
	 * @since 1.0.0
	 * @param mixed $max_tokens Max tokens value.
	 * @return int Sanitized max tokens.
	 */
	private function sanitize_max_tokens( $max_tokens ) {
		$max_tokens = intval( $max_tokens );
		// GPT-3.5-turbo supports max 4096 tokens
		// GPT-4 models support more, but we'll use 4096 as safe default
		return max( 500, min( 4096, $max_tokens ) );
	}

	/**
	 * Sanitize style preference.
	 *
	 * @since 1.0.0
	 * @param string $style Style preference.
	 * @return string Sanitized style.
	 */
	private function sanitize_style( $style ) {
		$allowed_styles = array( 'modern', 'classic', 'minimal', 'bold', 'corporate', 'creative' );
		
		$style = sanitize_text_field( $style );
		
		return in_array( $style, $allowed_styles, true ) ? $style : 'modern';
	}

	/**
	 * Sanitize layout preference.
	 *
	 * @since 1.0.0
	 * @param string $layout Layout preference.
	 * @return string Sanitized layout.
	 */
	private function sanitize_layout( $layout ) {
		$allowed_layouts = array( 'single-column', 'sidebar', 'grid', 'asymmetric' );
		
		$layout = sanitize_text_field( $layout );
		
		return in_array( $layout, $allowed_layouts, true ) ? $layout : 'single-column';
	}

	/**
	 * Sanitize tags array.
	 *
	 * @since 1.0.0
	 * @param array $tags Tags array.
	 * @return array Sanitized tags.
	 */
	private function sanitize_tags( $tags ) {
		if ( ! is_array( $tags ) ) {
			return array();
		}

		$sanitized_tags = array();
		foreach ( $tags as $tag ) {
			$tag = sanitize_text_field( $tag );
			if ( ! empty( $tag ) && strlen( $tag ) <= 50 ) {
				$sanitized_tags[] = $tag;
			}
		}

		// Limit to 10 tags maximum.
		return array_slice( $sanitized_tags, 0, 10 );
	}

	/**
	 * Sanitize rate limit settings.
	 *
	 * @since 1.0.0
	 * @param array $rate_limits Rate limit settings.
	 * @return array Sanitized rate limits.
	 */
	private function sanitize_rate_limits( $rate_limits ) {
		$sanitized = array();

		$tiers = array( 'free', 'pro', 'business' );
		foreach ( $tiers as $tier ) {
			if ( isset( $rate_limits[ $tier ] ) && is_array( $rate_limits[ $tier ] ) ) {
				$sanitized[ $tier ] = array(
					'hour' => max( 1, min( 1000, intval( $rate_limits[ $tier ]['hour'] ?? 5 ) ) ),
					'day'  => max( 1, min( 10000, intval( $rate_limits[ $tier ]['day'] ?? 10 ) ) ),
				);
			}
		}

		return $sanitized;
	}

	/**
	 * Initialize allowed HTML tags.
	 *
	 * @since 1.0.0
	 */
	private function init_allowed_html_tags() {
		$this->allowed_html_tags = array(
			'p'      => array(),
			'br'     => array(),
			'strong' => array(),
			'em'     => array(),
			'b'      => array(),
			'i'      => array(),
			'u'      => array(),
			'a'      => array(
				'href'   => array(),
				'title'  => array(),
				'target' => array(),
			),
		);

		// Apply filters to allow customization.
		$this->allowed_html_tags = apply_filters( 'layoutberg_allowed_html_tags', $this->allowed_html_tags );
	}

	/**
	 * Initialize blocked patterns.
	 *
	 * @since 1.0.0
	 */
	private function init_blocked_patterns() {
		$this->blocked_patterns = array(
			'/\b(hack|crack|exploit|vulnerability|inject)\b/i' => __( 'Content contains prohibited security-related terms.', 'layoutberg' ),
			'/\b(porn|adult|xxx|sex)\b/i' => __( 'Content contains adult material references.', 'layoutberg' ),
			'/\b(spam|scam|phishing|fraud)\b/i' => __( 'Content contains prohibited promotional terms.', 'layoutberg' ),
			'/\b(download|torrent|pirate|illegal)\b/i' => __( 'Content contains prohibited download references.', 'layoutberg' ),
		);

		// Apply filters to allow customization.
		$this->blocked_patterns = apply_filters( 'layoutberg_blocked_patterns', $this->blocked_patterns );
	}

	/**
	 * Validate file upload.
	 *
	 * @since 1.0.0
	 * @param array $file File upload array.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	public function validate_file_upload( $file ) {
		// Check if file was uploaded.
		if ( ! isset( $file['tmp_name'] ) || empty( $file['tmp_name'] ) ) {
			return new \WP_Error( 'no_file_uploaded', __( 'No file was uploaded.', 'layoutberg' ) );
		}

		// Check for upload errors.
		if ( isset( $file['error'] ) && $file['error'] !== UPLOAD_ERR_OK ) {
			return new \WP_Error( 'upload_error', __( 'File upload error occurred.', 'layoutberg' ) );
		}

		// Validate file type.
		$allowed_types = array( 'json', 'txt' );
		$file_type = wp_check_filetype( $file['name'] );
		
		if ( ! in_array( $file_type['ext'], $allowed_types, true ) ) {
			return new \WP_Error( 
				'invalid_file_type', 
				sprintf( 
					__( 'Invalid file type. Allowed types: %s', 'layoutberg' ), 
					implode( ', ', $allowed_types ) 
				) 
			);
		}

		// Check file size (max 1MB).
		$max_size = 1048576; // 1MB in bytes.
		if ( $file['size'] > $max_size ) {
			return new \WP_Error( 
				'file_too_large', 
				sprintf( __( 'File size must be less than %s.', 'layoutberg' ), size_format( $max_size ) ) 
			);
		}

		return true;
	}

	/**
	 * Get sanitization summary.
	 *
	 * @since 1.0.0
	 * @return array Sanitization configuration summary.
	 */
	public function get_sanitization_summary() {
		return array(
			'max_prompt_length' => $this->max_prompt_length,
			'min_prompt_length' => $this->min_prompt_length,
			'allowed_html_tags' => array_keys( $this->allowed_html_tags ),
			'blocked_patterns_count' => count( $this->blocked_patterns ),
			'validation_rules' => array(
				'prompt_length_check' => true,
				'content_validation' => true,
				'injection_detection' => true,
				'repetition_check' => true,
				'html_sanitization' => true,
				'whitespace_normalization' => true,
			),
		);
	}
}