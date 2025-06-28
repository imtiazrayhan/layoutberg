<?php
/**
 * Handle plugin security operations.
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
 * Security manager class.
 *
 * @since 1.0.0
 */
class Security_Manager {

	/**
	 * Encryption method.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string
	 */
	private $cipher = 'AES-256-CBC';

	/**
	 * Input sanitizer instance.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    Input_Sanitizer
	 */
	private $input_sanitizer;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->input_sanitizer = new Input_Sanitizer();
	}

	/**
	 * Get encryption key.
	 *
	 * @since 1.0.0
	 * @return string Encryption key.
	 */
	private function get_encryption_key() {
		$key = wp_salt( 'auth' );
		// Ensure key is exactly 32 bytes for AES-256.
		return substr( hash( 'sha256', $key ), 0, 32 );
	}

	/**
	 * Encrypt API key.
	 *
	 * @since 1.0.0
	 * @param string $api_key API key to encrypt.
	 * @return string|false Encrypted API key or false on failure.
	 */
	public function encrypt_api_key( $api_key ) {
		if ( empty( $api_key ) ) {
			return false;
		}

		$key   = $this->get_encryption_key();
		$ivlen = openssl_cipher_iv_length( $this->cipher );
		$iv    = openssl_random_pseudo_bytes( $ivlen );

		$encrypted = openssl_encrypt(
			$api_key,
			$this->cipher,
			$key,
			0,
			$iv
		);

		if ( false === $encrypted ) {
			return false;
		}

		// Combine encrypted data with IV for storage.
		return base64_encode( $encrypted . '::' . base64_encode( $iv ) );
	}

	/**
	 * Decrypt API key.
	 *
	 * @since 1.0.0
	 * @param string $encrypted_key Encrypted API key.
	 * @return string|false Decrypted API key or false on failure.
	 */
	public function decrypt_api_key( $encrypted_key ) {
		if ( empty( $encrypted_key ) ) {
			return false;
		}

		$key  = $this->get_encryption_key();
		$data = base64_decode( $encrypted_key );

		if ( false === $data ) {
			return false;
		}

		// Extract encrypted data and IV.
		$parts = explode( '::', $data );
		if ( count( $parts ) !== 2 ) {
			return false;
		}

		list( $encrypted, $iv_base64 ) = $parts;
		$iv = base64_decode( $iv_base64 );

		if ( false === $iv ) {
			return false;
		}

		$decrypted = openssl_decrypt(
			$encrypted,
			$this->cipher,
			$key,
			0,
			$iv
		);

		return $decrypted;
	}

	/**
	 * Validate nonce.
	 *
	 * @since 1.0.0
	 * @param string $nonce  Nonce to verify.
	 * @param string $action Action name.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate_nonce( $nonce, $action ) {
		return wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Create nonce.
	 *
	 * @since 1.0.0
	 * @param string $action Action name.
	 * @return string Nonce.
	 */
	public function create_nonce( $action ) {
		return wp_create_nonce( $action );
	}

	/**
	 * Validate user capability.
	 *
	 * @since 1.0.0
	 * @param string $capability Capability to check.
	 * @param int    $user_id    Optional. User ID. Default current user.
	 * @return bool True if user has capability.
	 */
	public function validate_capability( $capability, $user_id = null ) {
		if ( null === $user_id ) {
			return current_user_can( $capability );
		}

		return user_can( $user_id, $capability );
	}

	/**
	 * Sanitize prompt input.
	 *
	 * @since 1.0.0
	 * @param string $prompt Prompt to sanitize.
	 * @return string|WP_Error Sanitized prompt or error.
	 */
	public function sanitize_prompt( $prompt ) {
		return $this->input_sanitizer->sanitize_prompt( $prompt );
	}

	/**
	 * Sanitize settings array.
	 *
	 * @since 1.0.0
	 * @param array $settings Settings to sanitize.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $settings ) {
		return $this->input_sanitizer->sanitize_settings( $settings );
	}

	/**
	 * Sanitize API key.
	 *
	 * @since 1.0.0
	 * @param string $api_key API key to sanitize.
	 * @return string|WP_Error Sanitized API key or error.
	 */
	public function sanitize_api_key( $api_key ) {
		return $this->input_sanitizer->sanitize_api_key( $api_key );
	}

	/**
	 * Sanitize user options.
	 *
	 * @since 1.0.0
	 * @param array $options Options to sanitize.
	 * @return array Sanitized options.
	 */
	public function sanitize_user_options( $options ) {
		return $this->input_sanitizer->sanitize_user_options( $options );
	}

	/**
	 * Validate API key format.
	 *
	 * @since 1.0.0
	 * @param string $api_key API key to validate.
	 * @return bool True if valid format.
	 */
	public function validate_api_key_format( $api_key ) {
		// OpenAI API keys start with 'sk-'
		// More flexible validation as OpenAI key format has changed over time
		return preg_match( '/^sk-[a-zA-Z0-9\-_]{20,}$/', $api_key );
	}

	/**
	 * Rate limit check.
	 *
	 * @since 1.0.0
	 * @param int    $user_id User ID.
	 * @param string $action  Action being rate limited.
	 * @param int    $limit   Rate limit.
	 * @param int    $window  Time window in seconds.
	 * @return bool True if within rate limit.
	 */
	public function check_rate_limit( $user_id, $action, $limit, $window ) {
		$transient_key = 'layoutberg_rate_' . $action . '_' . $user_id;
		$attempts      = get_transient( $transient_key );

		if ( false === $attempts ) {
			set_transient( $transient_key, 1, $window );
			return true;
		}

		if ( $attempts >= $limit ) {
			return false;
		}

		set_transient( $transient_key, $attempts + 1, $window );
		return true;
	}

	/**
	 * Generate secure token.
	 *
	 * @since 1.0.0
	 * @param int $length Token length.
	 * @return string Secure token.
	 */
	public function generate_token( $length = 32 ) {
		return wp_generate_password( $length, false );
	}

	/**
	 * Validate file upload.
	 *
	 * @since 1.0.0
	 * @param array $file $_FILES array element.
	 * @return bool|WP_Error True if valid, WP_Error on failure.
	 */
	public function validate_file_upload( $file ) {
		// Check if file was uploaded.
		if ( ! isset( $file['tmp_name'] ) || empty( $file['tmp_name'] ) ) {
			return new \WP_Error( 'no_file', __( 'No file uploaded.', 'layoutberg' ) );
		}

		// Check file size (max 5MB).
		$max_size = 5 * 1024 * 1024; // 5MB in bytes.
		if ( $file['size'] > $max_size ) {
			return new \WP_Error( 'file_too_large', __( 'File size exceeds 5MB limit.', 'layoutberg' ) );
		}

		// Check file type.
		$allowed_types = array( 'jpg', 'jpeg', 'png', 'gif', 'json' );
		$file_type     = wp_check_filetype( $file['name'] );

		if ( ! in_array( $file_type['ext'], $allowed_types, true ) ) {
			return new \WP_Error( 'invalid_file_type', __( 'Invalid file type.', 'layoutberg' ) );
		}

		// Additional MIME type check.
		$file_mime = mime_content_type( $file['tmp_name'] );
		$allowed_mimes = array(
			'image/jpeg',
			'image/png',
			'image/gif',
			'application/json',
		);

		if ( ! in_array( $file_mime, $allowed_mimes, true ) ) {
			return new \WP_Error( 'invalid_mime_type', __( 'Invalid MIME type.', 'layoutberg' ) );
		}

		return true;
	}

	/**
	 * Log security event.
	 *
	 * @since 1.0.0
	 * @param string $event   Event type.
	 * @param array  $details Event details.
	 */
	public function log_security_event( $event, $details = array() ) {
		$log_entry = array(
			'event'      => $event,
			'user_id'    => get_current_user_id(),
			'ip_address' => $this->get_client_ip(),
			'timestamp'  => current_time( 'mysql' ),
			'details'    => $details,
		);

		// Log to error log in development.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'LayoutBerg Security Event: ' . wp_json_encode( $log_entry ) );
		}

		// Hook for external logging.
		do_action( 'layoutberg_security_event', $log_entry );
	}

	/**
	 * Get client IP address.
	 *
	 * @since 1.0.0
	 * @return string IP address.
	 */
	private function get_client_ip() {
		$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );

		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				$ips = explode( ',', $_SERVER[ $key ] );
				foreach ( $ips as $ip ) {
					$ip = trim( $ip );
					if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
						return $ip;
					}
				}
			}
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
	}
}