<?php
/**
 * PHPUnit bootstrap file for LayoutBerg plugin tests.
 *
 * @package LayoutBerg
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

// Define plugin constants
define( 'LAYOUTBERG_PLUGIN_DIR', dirname( dirname( __DIR__ ) ) . '/' );
define( 'LAYOUTBERG_PLUGIN_URL', 'http://example.org/wp-content/plugins/layoutberg/' );
define( 'LAYOUTBERG_VERSION', '1.0.0' );

// Load Composer autoloader
require_once LAYOUTBERG_PLUGIN_DIR . 'vendor/autoload.php';

// Initialize Brain Monkey
\Brain\Monkey\setUp();

// Mock WordPress functions if not available
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$args = get_object_vars( $args );
		} elseif ( ! is_array( $args ) ) {
			wp_parse_str( $args, $args );
		}

		if ( is_array( $defaults ) && $defaults ) {
			return array_merge( $defaults, $args );
		}
		return $args;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return filter_var( $str, FILTER_SANITIZE_STRING );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return filter_var( $str, FILTER_SANITIZE_STRING );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) {
		return $data; // Simplified for testing
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ) {
		return 'test_nonce_' . md5( $action );
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action = -1 ) {
		return $nonce === wp_create_nonce( $action );
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		return true; // Allow all capabilities for testing
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 1; // Test user ID
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		// Return default options for testing
		$options = array(
			'layoutberg_options' => array(
				'api_key'    => 'sk-test-key',
				'model'      => 'gpt-3.5-turbo',
				'max_tokens' => 2000,
			),
		);
		
		return isset( $options[ $option ] ) ? $options[ $option ] : $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value ) {
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $option ) {
		return true;
	}
}

if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( $url, $args = array() ) {
		return array(
			'response' => array(
				'code' => 200,
				'message' => 'OK',
			),
			'body' => json_encode( array(
				'choices' => array(
					array(
						'message' => array(
							'content' => '<!-- wp:paragraph --><p>Generated content</p><!-- /wp:paragraph -->',
						),
					),
				),
			)),
		);
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		return $response['response']['code'];
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		return $response['body'];
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title, $fallback_title = '', $context = 'save' ) {
		return strtolower( str_replace( ' ', '-', trim( $title ) ) );
	}
}

if ( ! function_exists( 'wp_salt' ) ) {
	function wp_salt( $scheme = 'auth' ) {
		return 'test-salt-for-phpunit-testing-' . $scheme;
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $errors = array();
		public $error_data = array();

		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( empty( $code ) ) {
				return;
			}

			$this->errors[ $code ][] = $message;

			if ( ! empty( $data ) ) {
				$this->error_data[ $code ] = $data;
			}
		}

		public function get_error_code() {
			$codes = $this->get_error_codes();
			if ( empty( $codes ) ) {
				return '';
			}
			return $codes[0];
		}

		public function get_error_codes() {
			if ( empty( $this->errors ) ) {
				return array();
			}
			return array_keys( $this->errors );
		}

		public function get_error_message( $code = '' ) {
			if ( empty( $code ) ) {
				$code = $this->get_error_code();
			}
			if ( isset( $this->errors[ $code ] ) ) {
				return $this->errors[ $code ][0];
			}
			return '';
		}
	}
}

// Mock global $wpdb
global $wpdb;
if ( ! isset( $wpdb ) ) {
	$wpdb = new stdClass();
	$wpdb->prefix = 'wp_';
	$wpdb->insert_id = 1;
}

// Teardown Brain Monkey after tests
register_shutdown_function( function() {
	\Brain\Monkey\tearDown();
});