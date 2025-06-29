<?php
/**
 * REST API handler.
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
 * REST API handler class.
 *
 * @since 1.0.0
 */
class API_Handler {

	/**
	 * API namespace.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string
	 */
	private $namespace = 'layoutberg/v1';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Constructor can be used for initialization if needed.
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {
		// Generate layout endpoint.
		register_rest_route(
			$this->namespace,
			'/generate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generate_layout' ),
				'permission_callback' => array( $this, 'check_generate_permission' ),
				'args'                => $this->get_generate_args(),
			)
		);

		// Get templates endpoint.
		register_rest_route(
			$this->namespace,
			'/templates',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_templates' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_templates_args(),
			)
		);

		// Save template endpoint.
		register_rest_route(
			$this->namespace,
			'/templates',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_template' ),
				'permission_callback' => array( $this, 'check_template_permission' ),
				'args'                => $this->get_save_template_args(),
			)
		);

		// Get single template endpoint.
		register_rest_route(
			$this->namespace,
			'/templates/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_template' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// Delete template endpoint.
		register_rest_route(
			$this->namespace,
			'/templates/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_template' ),
				'permission_callback' => array( $this, 'check_template_permission' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// Get usage statistics endpoint.
		register_rest_route(
			$this->namespace,
			'/usage',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_usage' ),
				'permission_callback' => array( $this, 'check_analytics_permission' ),
			)
		);

		// Validate API key endpoint.
		register_rest_route(
			$this->namespace,
			'/validate-key',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'validate_api_key' ),
				'permission_callback' => array( $this, 'check_settings_permission' ),
				'args'                => array(
					'api_key' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Generate layout endpoint callback.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function generate_layout( $request ) {
		try {
			$prompt           = $request->get_param( 'prompt' );
			$settings         = $request->get_param( 'settings' );
			$replace_selected = $request->get_param( 'replace_selected' );

			// Normalize options for backward compatibility.
			$options = array();
			if ( ! empty( $settings ) ) {
				$options = array(
					'style'        => $settings['style'] ?? 'modern',
					'layout'       => $settings['layout'] ?? 'single-column',
					'model'        => $settings['model'] ?? 'gpt-3.5-turbo',
					'temperature'  => $settings['temperature'] ?? 0.7,
					'max_tokens'   => $settings['maxTokens'] ?? 2000,
					'color_scheme' => $settings['color_scheme'] ?? null,
					'density'      => $settings['density'] ?? null,
					'audience'     => $settings['audience'] ?? null,
					'industry'     => $settings['industry'] ?? null,
				);
			}

			// Add replace mode context.
			if ( $replace_selected ) {
				$options['replace_mode'] = true;
			}

			// Rate limiting removed - unlimited generations allowed

			// Generate layout.
			$generator = new Block_Generator();
			$result    = $generator->generate( $prompt, $options );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			// Format response for the editor.
			// The result from Block_Generator already contains structured data
			$response = array(
				'success' => true,
				'data'    => array(
					'blocks'       => isset( $result['serialized'] ) ? $result['serialized'] : $result['blocks'],
					'html'         => isset( $result['html'] ) ? $result['html'] : '',
					'raw'          => isset( $result['raw'] ) ? $result['raw'] : '',
					'prompt'       => $prompt,
					'settings'     => $settings,
					'timestamp'    => current_time( 'mysql' ),
					'replace_mode' => $replace_selected,
					'usage'        => isset( $result['usage'] ) ? $result['usage'] : null,
					'model'        => isset( $result['model'] ) ? $result['model'] : null,
					'metadata'     => isset( $result['metadata'] ) ? $result['metadata'] : null,
				),
			);

			return rest_ensure_response( $response );
		} catch ( \Exception $e ) {
			// Log the error for debugging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'LayoutBerg Generate Error: ' . $e->getMessage() );
				error_log( 'Stack trace: ' . $e->getTraceAsString() );
			}
			
			return new \WP_Error(
				'generation_error',
				__( 'An error occurred while generating the layout: ', 'layoutberg' ) . $e->getMessage(),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Get templates endpoint callback.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_templates( $request ) {
		$category = $request->get_param( 'category' );
		$search   = $request->get_param( 'search' );
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );

		$template_manager = new Template_Manager();
		$templates        = $template_manager->get_templates(
			array(
				'category' => $category,
				'search'   => $search,
				'page'     => $page,
				'per_page' => $per_page,
			)
		);

		return rest_ensure_response( $templates );
	}

	/**
	 * Get single template endpoint callback.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function get_template( $request ) {
		$template_id = $request->get_param( 'id' );
		$increment_usage = $request->get_param( 'increment_usage' );

		$template_manager = new Template_Manager();
		$template = $template_manager->get_template( $template_id, $increment_usage );

		if ( is_wp_error( $template ) ) {
			return $template;
		}

		return rest_ensure_response( $template );
	}

	/**
	 * Save template endpoint callback.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function save_template( $request ) {
		$template_data = array(
			'name'        => $request->get_param( 'name' ),
			'content'     => $request->get_param( 'content' ),
			'description' => $request->get_param( 'description' ),
			'category'    => $request->get_param( 'category' ),
			'tags'        => $request->get_param( 'tags' ),
			'prompt'      => $request->get_param( 'prompt' ),
			'is_public'   => $request->get_param( 'is_public' ),
		);

		$template_manager = new Template_Manager();
		$result           = $template_manager->save_template( $template_data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( array( 
			'id' => $result,
			'name' => $template_data['name'],
			'message' => __( 'Template saved successfully!', 'layoutberg' ) 
		) );
	}

	/**
	 * Delete template endpoint callback.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function delete_template( $request ) {
		$template_id = $request->get_param( 'id' );

		$template_manager = new Template_Manager();
		$result           = $template_manager->delete_template( $template_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( array( 'deleted' => true ) );
	}

	/**
	 * Get usage statistics endpoint callback.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_usage( $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$period  = $request->get_param( 'period' ) ?: 'month';

		// Calculate date range.
		$end_date   = current_time( 'Y-m-d' );
		$start_date = date( 'Y-m-d', strtotime( '-1 ' . $period ) );

		// Get usage data.
		$table_name = $wpdb->prefix . 'layoutberg_usage';
		$usage_data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT date, generations_count, tokens_used 
				FROM $table_name 
				WHERE user_id = %d 
				AND date BETWEEN %s AND %s 
				ORDER BY date ASC",
				$user_id,
				$start_date,
				$end_date
			),
			ARRAY_A
		);

		// Get total statistics.
		$totals = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					SUM(generations_count) as total_generations,
					SUM(tokens_used) as total_tokens
				FROM $table_name 
				WHERE user_id = %d 
				AND date BETWEEN %s AND %s",
				$user_id,
				$start_date,
				$end_date
			),
			ARRAY_A
		);

		return rest_ensure_response(
			array(
				'usage'  => $usage_data,
				'totals' => $totals,
				'period' => $period,
			)
		);
	}

	/**
	 * Validate API key endpoint callback.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function validate_api_key( $request ) {
		$api_key = $request->get_param( 'api_key' );

		// If 'use_stored' is passed, use the stored API key.
		if ( 'use_stored' === $api_key ) {
			$options = get_option( 'layoutberg_options', array() );
			if ( ! empty( $options['api_key'] ) ) {
				$security = new Security_Manager();
				$decrypted = $security->decrypt_api_key( $options['api_key'] );
				if ( $decrypted ) {
					$api_key = $decrypted;
				} else {
					return new \WP_Error( 'decrypt_failed', __( 'Failed to decrypt stored API key.', 'layoutberg' ) );
				}
			} else {
				return new \WP_Error( 'no_api_key', __( 'No API key configured.', 'layoutberg' ) );
			}
		}

		// Use static method to avoid instantiation issues.
		$result = API_Client::validate_api_key( $api_key );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( array( 'valid' => true ) );
	}

	/**
	 * Check permission for generate endpoint.
	 *
	 * @since 1.0.0
	 * @return bool True if user has permission.
	 */
	public function check_generate_permission() {
		return current_user_can( 'layoutberg_generate' );
	}

	/**
	 * Check permission for template endpoints.
	 *
	 * @since 1.0.0
	 * @return bool True if user has permission.
	 */
	public function check_template_permission() {
		return current_user_can( 'layoutberg_manage_templates' );
	}

	/**
	 * Check permission for analytics endpoints.
	 *
	 * @since 1.0.0
	 * @return bool True if user has permission.
	 */
	public function check_analytics_permission() {
		return current_user_can( 'layoutberg_view_analytics' );
	}

	/**
	 * Check permission for settings endpoints.
	 *
	 * @since 1.0.0
	 * @return bool True if user has permission.
	 */
	public function check_settings_permission() {
		return current_user_can( 'layoutberg_configure' );
	}

	/**
	 * Get arguments for generate endpoint.
	 *
	 * @since 1.0.0
	 * @return array Endpoint arguments.
	 */
	private function get_generate_args() {
		return array(
			'prompt' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => array( new Security_Manager(), 'sanitize_prompt' ),
				'validate_callback' => function( $param ) {
					return ! empty( $param );
				},
			),
			'settings' => array(
				'required' => false,
				'type'     => 'object',
				'default'  => array(),
				'properties' => array(
					'model'        => array( 'type' => 'string' ),
					'temperature'  => array( 'type' => 'number' ),
					'maxTokens'    => array( 'type' => 'integer' ),
					'style'        => array( 'type' => 'string' ),
					'layout'       => array( 'type' => 'string' ),
					'color_scheme' => array( 'type' => 'string' ),
					'density'      => array( 'type' => 'string' ),
					'audience'     => array( 'type' => 'string' ),
					'industry'     => array( 'type' => 'string' ),
				),
			),
			'replace_selected' => array(
				'required' => false,
				'type'     => 'boolean',
				'default'  => false,
			),
			// Keep backward compatibility with old 'options' parameter.
			'options' => array(
				'required' => false,
				'type'     => 'object',
				'default'  => array(),
				'properties' => array(
					'style'      => array( 'type' => 'string' ),
					'colors'     => array( 'type' => 'string' ),
					'layout'     => array( 'type' => 'string' ),
					'page_type'  => array( 'type' => 'string' ),
					'industry'   => array( 'type' => 'string' ),
					'audience'   => array( 'type' => 'string' ),
				),
			),
		);
	}

	/**
	 * Get arguments for templates endpoint.
	 *
	 * @since 1.0.0
	 * @return array Endpoint arguments.
	 */
	private function get_templates_args() {
		return array(
			'category' => array(
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'search' => array(
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'page' => array(
				'required' => false,
				'type'     => 'integer',
				'default'  => 1,
				'minimum'  => 1,
			),
			'per_page' => array(
				'required' => false,
				'type'     => 'integer',
				'default'  => 20,
				'minimum'  => 1,
				'maximum'  => 100,
			),
		);
	}

	/**
	 * Get arguments for save template endpoint.
	 *
	 * @since 1.0.0
	 * @return array Endpoint arguments.
	 */
	private function get_save_template_args() {
		return array(
			'name' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'content' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'wp_kses_post',
			),
			'description' => array(
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'category' => array(
				'required'          => false,
				'type'              => 'string',
				'default'           => 'general',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'tags' => array(
				'required' => false,
				'type'     => 'array',
				'items'    => array(
					'type' => 'string',
				),
			),
			'prompt' => array(
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'is_public' => array(
				'required' => false,
				'type'     => 'boolean',
				'default'  => false,
			),
		);
	}
}