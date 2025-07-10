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
					'provider' => array(
						'required'          => false,
						'default'           => 'openai',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function( $value ) {
							return in_array( $value, array( 'openai', 'claude' ), true );
						},
					),
				),
			)
		);

		// Prompt templates endpoints (Agency plan only).
		// Get all prompt templates.
		register_rest_route(
			$this->namespace,
			'/prompt-templates',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_prompt_templates' ),
				'permission_callback' => array( $this, 'check_agency_permission' ),
			)
		);

		// Create new prompt template.
		register_rest_route(
			$this->namespace,
			'/prompt-templates',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_prompt_template' ),
				'permission_callback' => array( $this, 'check_agency_permission' ),
				'args'                => $this->get_prompt_template_args(),
			)
		);

		// Update prompt template.
		register_rest_route(
			$this->namespace,
			'/prompt-templates/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_prompt_template' ),
				'permission_callback' => array( $this, 'check_agency_permission' ),
				'args'                => array_merge(
					array(
						'id' => array(
							'required'          => true,
							'validate_callback' => function( $param ) {
								return is_numeric( $param );
							},
						),
					),
					$this->get_prompt_template_args()
				),
			)
		);

		// Delete prompt template.
		register_rest_route(
			$this->namespace,
			'/prompt-templates/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_prompt_template' ),
				'permission_callback' => array( $this, 'check_agency_permission' ),
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

			// Extract only AI-related options.
			$options = array();
			if ( ! empty( $settings ) ) {
				$options = array(
					'model'        => $settings['model'] ?? 'gpt-3.5-turbo',
					'temperature'  => $settings['temperature'] ?? 0.7,
					'max_tokens'   => $settings['maxTokens'] ?? 2000,
					'useVariations' => $settings['useVariations'] ?? false,
					'variationStyle' => $settings['variationStyle'] ?? null,
					'templateKey' => $settings['templateKey'] ?? null,
				);
			}
			
			// Validate model access based on user's plan
			$requested_model = $options['model'] ?? 'gpt-3.5-turbo';
			
			// Check if user can access the requested model
			if ( ! $this->validate_model_access( $requested_model ) ) {
				return new WP_Error(
					'invalid_model_access',
					__( 'You do not have access to this model. Please upgrade your plan to use premium models.', 'layoutberg' ),
					array( 'status' => 403 )
				);
			}
			
			// Validate advanced options access
			if ( ! LayoutBerg_Licensing::can_use_advanced_options() ) {
				// Reset advanced options to defaults for Starter/Expired plans
				$options['temperature'] = 0.7;
				$options['max_tokens'] = 2000;
				
				// Log if user tried to use advanced options without access
				if ( ( isset( $settings['temperature'] ) && $settings['temperature'] != 0.7 ) ||
				     ( isset( $settings['maxTokens'] ) && $settings['maxTokens'] != 2000 ) ) {
					error_log( 'LayoutBerg: User attempted to use advanced options without proper plan access.' );
				}
			}
			
			// Get stored user preferences from onboarding
			$stored_options = get_option( 'layoutberg_options', array() );
			
			// Add site context if available
			if ( ! empty( $stored_options['site_context']['type'] ) ) {
				$options['site_type'] = $stored_options['site_context']['type'];
			}
			
			// Add style preferences if available
			if ( ! empty( $stored_options['style_defaults'] ) ) {
				if ( ! empty( $stored_options['style_defaults']['style'] ) ) {
					$options['style'] = $stored_options['style_defaults']['style'];
				}
				if ( ! empty( $stored_options['style_defaults']['colors'] ) ) {
					$options['colors'] = $stored_options['style_defaults']['colors'];
				}
				if ( ! empty( $stored_options['style_defaults']['density'] ) ) {
					$options['density'] = $stored_options['style_defaults']['density'];
				}
			}

			// Add replace mode context.
			if ( $replace_selected ) {
				$options['replace_mode'] = true;
			}

			// Rate limiting removed - unlimited generations allowed

			// Check if simplified generation is enabled
			$use_simplified = isset( $stored_options['use_simplified_generation'] ) && 
			                 $stored_options['use_simplified_generation'] === '1';

			// Generate layout using appropriate generator
			if ( $use_simplified ) {
				// Use simplified generator with minimal validation
				if ( ! class_exists( '\DotCamp\LayoutBerg\Simple_Block_Generator' ) ) {
					require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-simple-block-generator.php';
				}
				$generator = new Simple_Block_Generator();
			} else {
				// Use standard generator with full validation
				$generator = new Block_Generator();
			}
			
			$result = $generator->generate( $prompt, $options );

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
					'prompts'      => isset( $result['prompts'] ) ? $result['prompts'] : null,
				),
			);

			return rest_ensure_response( $response );
		} catch ( \Exception $e ) {
			// Log the error for debugging
			error_log( 'LayoutBerg Generate Error: ' . $e->getMessage() );
			error_log( 'Stack trace: ' . $e->getTraceAsString() );
			
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
		$provider = $request->get_param( 'provider' ) ?: 'openai';

		// If 'use_stored' is passed, use the stored API key.
		if ( 'use_stored' === $api_key ) {
			$options = get_option( 'layoutberg_options', array() );
			$security = new Security_Manager();
			
			// Determine which key to use based on provider
			$key_field = $provider === 'claude' ? 'claude_api_key' : 'openai_api_key';
			
			// Check new field first, then fall back to old 'api_key' for OpenAI
			if ( ! empty( $options[ $key_field ] ) ) {
				$decrypted = $security->decrypt_api_key( $options[ $key_field ] );
				if ( $decrypted ) {
					$api_key = $decrypted;
				} else {
					return new \WP_Error( 'decrypt_failed', __( 'Failed to decrypt stored API key.', 'layoutberg' ) );
				}
			} elseif ( $provider === 'openai' && ! empty( $options['api_key'] ) ) {
				// Backward compatibility for OpenAI
				$decrypted = $security->decrypt_api_key( $options['api_key'] );
				if ( $decrypted ) {
					$api_key = $decrypted;
				} else {
					return new \WP_Error( 'decrypt_failed', __( 'Failed to decrypt stored API key.', 'layoutberg' ) );
				}
			} else {
				$provider_name = $provider === 'claude' ? 'Claude' : 'OpenAI';
				return new \WP_Error( 'no_api_key', sprintf( __( 'No %s API key configured.', 'layoutberg' ), $provider_name ) );
			}
		}

		// Use static method to avoid instantiation issues.
		$result = API_Client::validate_api_key( $api_key, $provider );

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
	 * Check permission for agency-only endpoints.
	 *
	 * @since 1.0.0
	 * @return bool True if user has permission.
	 */
	public function check_agency_permission() {
		return current_user_can( 'layoutberg_configure' ) && LayoutBerg_Licensing::is_agency_plan();
	}

	/**
	 * Validate if user has access to the requested model.
	 *
	 * @since 1.0.0
	 * @param string $model Model ID to validate.
	 * @return bool True if user can access the model.
	 */
	private function validate_model_access( $model ) {
		// List of models that require Professional or Agency plan
		$premium_models = array(
			'gpt-4',
			'gpt-4-turbo-preview',
			'gpt-4-1106-preview',
			'gpt-4-0125-preview',
			'claude-3-opus-20240229',
			'claude-3-sonnet-20240229',
			'claude-3-haiku-20240307',
		);

		// Check if the requested model is a premium model
		$is_premium_model = false;
		foreach ( $premium_models as $premium_model ) {
			if ( strpos( $model, $premium_model ) !== false ) {
				$is_premium_model = true;
				break;
			}
		}

		// If it's a premium model, check if user has access
		if ( $is_premium_model ) {
			return LayoutBerg_Licensing::can_use_all_models();
		}

		// Non-premium models (like gpt-3.5-turbo) are available to all plans
		return true;
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
				),
			),
			'replace_selected' => array(
				'required' => false,
				'type'     => 'boolean',
				'default'  => false,
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

	/**
	 * Get arguments for prompt template endpoints.
	 *
	 * @since 1.0.0
	 * @return array Endpoint arguments.
	 */
	private function get_prompt_template_args() {
		return array(
			'name' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function( $param ) {
					return ! empty( $param ) && strlen( $param ) <= 100;
				},
			),
			'category' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function( $param ) {
					return in_array( $param, array( 'hero', 'features', 'testimonials', 'cta', 'pricing', 'about', 'contact', 'other' ), true );
				},
			),
			'prompt' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'validate_callback' => function( $param ) {
					return ! empty( $param );
				},
			),
			'variables' => array(
				'required' => false,
				'type'     => 'object',
				'default'  => null,
			),
		);
	}

	/**
	 * Get all prompt templates.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function get_prompt_templates( $request ) {
		$user_id = get_current_user_id();
		
		// Get templates from user meta
		$templates = get_user_meta( $user_id, 'layoutberg_prompt_templates', true );
		if ( ! is_array( $templates ) ) {
			$templates = array();
		}

		return rest_ensure_response( array(
			'templates' => $templates,
			'count'     => count( $templates ),
		) );
	}

	/**
	 * Create a new prompt template.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function create_prompt_template( $request ) {
		$user_id = get_current_user_id();
		
		// Get existing templates
		$templates = get_user_meta( $user_id, 'layoutberg_prompt_templates', true );
		if ( ! is_array( $templates ) ) {
			$templates = array();
		}

		// Create new template
		$template = array(
			'id'        => uniqid( 'pt_' ),
			'name'      => $request->get_param( 'name' ),
			'category'  => $request->get_param( 'category' ),
			'prompt'    => $request->get_param( 'prompt' ),
			'variables' => $request->get_param( 'variables' ),
			'created'   => current_time( 'mysql' ),
			'updated'   => current_time( 'mysql' ),
		);

		// Add to templates array
		$templates[] = $template;

		// Save back to user meta
		update_user_meta( $user_id, 'layoutberg_prompt_templates', $templates );

		return rest_ensure_response( array(
			'success'  => true,
			'template' => $template,
		) );
	}

	/**
	 * Update an existing prompt template.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function update_prompt_template( $request ) {
		$user_id     = get_current_user_id();
		$template_id = $request->get_param( 'id' );
		
		// Get existing templates
		$templates = get_user_meta( $user_id, 'layoutberg_prompt_templates', true );
		if ( ! is_array( $templates ) ) {
			return new WP_Error( 'template_not_found', __( 'Template not found', 'layoutberg' ), array( 'status' => 404 ) );
		}

		// Find and update the template
		$found = false;
		foreach ( $templates as &$template ) {
			if ( $template['id'] === $template_id ) {
				$template['name']      = $request->get_param( 'name' );
				$template['category']  = $request->get_param( 'category' );
				$template['prompt']    = $request->get_param( 'prompt' );
				$template['variables'] = $request->get_param( 'variables' );
				$template['updated']   = current_time( 'mysql' );
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			return new WP_Error( 'template_not_found', __( 'Template not found', 'layoutberg' ), array( 'status' => 404 ) );
		}

		// Save back to user meta
		update_user_meta( $user_id, 'layoutberg_prompt_templates', $templates );

		return rest_ensure_response( array(
			'success' => true,
			'message' => __( 'Template updated successfully', 'layoutberg' ),
		) );
	}

	/**
	 * Delete a prompt template.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object.
	 */
	public function delete_prompt_template( $request ) {
		$user_id     = get_current_user_id();
		$template_id = $request->get_param( 'id' );
		
		// Get existing templates
		$templates = get_user_meta( $user_id, 'layoutberg_prompt_templates', true );
		if ( ! is_array( $templates ) ) {
			return new WP_Error( 'template_not_found', __( 'Template not found', 'layoutberg' ), array( 'status' => 404 ) );
		}

		// Filter out the template to delete
		$filtered_templates = array_filter( $templates, function( $template ) use ( $template_id ) {
			return $template['id'] !== $template_id;
		} );

		// Check if template was actually deleted
		if ( count( $filtered_templates ) === count( $templates ) ) {
			return new WP_Error( 'template_not_found', __( 'Template not found', 'layoutberg' ), array( 'status' => 404 ) );
		}

		// Re-index array
		$filtered_templates = array_values( $filtered_templates );

		// Save back to user meta
		update_user_meta( $user_id, 'layoutberg_prompt_templates', $filtered_templates );

		return rest_ensure_response( array(
			'success' => true,
			'message' => __( 'Template deleted successfully', 'layoutberg' ),
		) );
	}
}