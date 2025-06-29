<?php
/**
 * Onboarding functionality for first-time users.
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
 * Onboarding class.
 *
 * Handles the onboarding wizard for first-time plugin activation.
 *
 * @since 1.0.0
 */
class Onboarding {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Check if we need to redirect to onboarding.
		add_action( 'admin_init', array( $this, 'maybe_redirect_to_onboarding' ) );
		
		// Register REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Check if onboarding should be shown.
	 *
	 * @since 1.0.0
	 * @return bool True if onboarding is needed.
	 */
	public function should_show_onboarding() {
		// Don't show during AJAX requests.
		if ( wp_doing_ajax() ) {
			return false;
		}

		// Don't show on network admin.
		if ( is_network_admin() ) {
			return false;
		}

		// Check if onboarding is already completed.
		if ( get_option( 'layoutberg_onboarding_completed', false ) ) {
			return false;
		}

		// Check if user has capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		// Check if we're doing a bulk plugin activation.
		if ( isset( $_GET['activate-multi'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Maybe redirect to onboarding page.
	 *
	 * @since 1.0.0
	 */
	public function maybe_redirect_to_onboarding() {
		// Check if we should redirect.
		if ( ! get_transient( 'layoutberg_onboarding_redirect' ) ) {
			return;
		}

		// Delete the redirect transient.
		delete_transient( 'layoutberg_onboarding_redirect' );

		// Don't redirect if onboarding shouldn't be shown.
		if ( ! $this->should_show_onboarding() ) {
			return;
		}

		// Don't redirect if we're already on the onboarding page.
		if ( isset( $_GET['page'] ) && 'layoutberg-onboarding' === $_GET['page'] ) {
			return;
		}

		// Redirect to onboarding page.
		wp_safe_redirect( admin_url( 'admin.php?page=layoutberg-onboarding' ) );
		exit;
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 */
	public function register_rest_routes() {
		// Save onboarding progress.
		register_rest_route(
			'layoutberg/v1',
			'/onboarding/progress',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_progress' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
				'args'                => array(
					'step' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'data' => array(
						'required' => false,
						'type'     => 'object',
					),
				),
			)
		);

		// Complete onboarding.
		register_rest_route(
			'layoutberg/v1',
			'/onboarding/complete',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'complete_onboarding' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		// Skip onboarding.
		register_rest_route(
			'layoutberg/v1',
			'/onboarding/skip',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'skip_onboarding' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		// Get onboarding data.
		register_rest_route(
			'layoutberg/v1',
			'/onboarding/data',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_onboarding_data' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		// Install recommended plugin.
		register_rest_route(
			'layoutberg/v1',
			'/onboarding/install-plugin',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'install_plugin' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
				'args'                => array(
					'slug' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * REST API permission check.
	 *
	 * @since 1.0.0
	 * @return bool True if user has permission.
	 */
	public function rest_permission_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Save onboarding progress.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response object.
	 */
	public function save_progress( $request ) {
		$step = $request->get_param( 'step' );
		$data = $request->get_param( 'data' );

		// Get current progress.
		$progress = get_option( 'layoutberg_onboarding_progress', array() );

		// Update progress.
		$progress[ $step ] = array(
			'completed' => true,
			'timestamp' => current_time( 'timestamp' ),
			'data'      => $data,
		);

		// Save progress.
		update_option( 'layoutberg_onboarding_progress', $progress );

		// Handle specific step data.
		$this->handle_step_data( $step, $data );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Progress saved successfully.', 'layoutberg' ),
			)
		);
	}

	/**
	 * Handle specific step data.
	 *
	 * @since 1.0.0
	 * @param string $step Step name.
	 * @param array  $data Step data.
	 */
	private function handle_step_data( $step, $data ) {
		$options = get_option( 'layoutberg_options', array() );

		switch ( $step ) {
			case 'api-setup':
				if ( ! empty( $data['openai_api_key'] ) ) {
					$security = new Security_Manager();
					$options['openai_api_key'] = $security->encrypt_api_key( sanitize_text_field( $data['openai_api_key'] ) );
				}
				break;

			case 'claude-api-setup':
				if ( ! empty( $data['claude_api_key'] ) ) {
					$security = new Security_Manager();
					$options['claude_api_key'] = $security->encrypt_api_key( sanitize_text_field( $data['claude_api_key'] ) );
				}
				break;

			case 'site-context':
				if ( ! empty( $data['site_type'] ) ) {
					$options['site_context']['type'] = sanitize_text_field( $data['site_type'] );
				}
				if ( ! empty( $data['style_preference'] ) ) {
					$options['style_defaults']['style'] = sanitize_text_field( $data['style_preference'] );
				}
				if ( ! empty( $data['color_preference'] ) ) {
					$options['style_defaults']['colors'] = sanitize_text_field( $data['color_preference'] );
				}
				if ( ! empty( $data['layout_density'] ) ) {
					$options['style_defaults']['density'] = sanitize_text_field( $data['layout_density'] );
				}
				break;

			case 'model-selection':
				if ( ! empty( $data['default_model'] ) ) {
					$options['model'] = sanitize_text_field( $data['default_model'] );
				}
				break;
		}

		update_option( 'layoutberg_options', $options );
	}

	/**
	 * Complete onboarding.
	 *
	 * @since 1.0.0
	 * @return \WP_REST_Response Response object.
	 */
	public function complete_onboarding() {
		// Mark onboarding as completed.
		update_option( 'layoutberg_onboarding_completed', true );
		update_option( 'layoutberg_onboarding_completed_at', current_time( 'timestamp' ) );

		// Clear progress data.
		delete_option( 'layoutberg_onboarding_progress' );

		return rest_ensure_response(
			array(
				'success'      => true,
				'message'      => __( 'Onboarding completed successfully!', 'layoutberg' ),
				'redirect_url' => admin_url( 'admin.php?page=layoutberg' ),
			)
		);
	}

	/**
	 * Skip onboarding.
	 *
	 * @since 1.0.0
	 * @return \WP_REST_Response Response object.
	 */
	public function skip_onboarding() {
		// Mark onboarding as skipped.
		update_option( 'layoutberg_onboarding_completed', true );
		update_option( 'layoutberg_onboarding_skipped', true );

		// Clear progress data.
		delete_option( 'layoutberg_onboarding_progress' );

		return rest_ensure_response(
			array(
				'success'      => true,
				'message'      => __( 'Onboarding skipped.', 'layoutberg' ),
				'redirect_url' => admin_url( 'admin.php?page=layoutberg-settings' ),
			)
		);
	}

	/**
	 * Get onboarding data.
	 *
	 * @since 1.0.0
	 * @return \WP_REST_Response Response object.
	 */
	public function get_onboarding_data() {
		$progress = get_option( 'layoutberg_onboarding_progress', array() );
		$options  = get_option( 'layoutberg_options', array() );

		// Check if API keys are already set.
		$has_api_key = ! empty( $options['openai_api_key'] ) || ! empty( $options['api_key'] );
		$has_claude_key = ! empty( $options['claude_api_key'] );

		return rest_ensure_response(
			array(
				'progress'        => $progress,
				'has_api_key'     => $has_api_key,
				'has_claude_key'  => $has_claude_key,
				'site_url'        => get_site_url(),
				'admin_email'     => get_option( 'admin_email' ),
				'site_title'      => get_option( 'blogname' ),
			)
		);
	}

	/**
	 * Install recommended plugin.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response object.
	 */
	public function install_plugin( $request ) {
		$slug = $request->get_param( 'slug' );

		// Validate plugin slug.
		$allowed_plugins = array(
			'ultimate-blocks' => array(
				'name' => 'Ultimate Blocks',
				'file' => 'ultimate-blocks/ultimate-blocks.php',
			),
			'tableberg' => array(
				'name' => 'TableBerg',
				'file' => 'tableberg/tableberg.php',
			),
		);

		if ( ! isset( $allowed_plugins[ $slug ] ) ) {
			return new \WP_Error(
				'invalid_plugin',
				__( 'Invalid plugin slug.', 'layoutberg' ),
				array( 'status' => 400 )
			);
		}

		// Check if plugin is already installed.
		$plugin_file = $allowed_plugins[ $slug ]['file'];
		if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
			// Activate if not already active.
			if ( ! is_plugin_active( $plugin_file ) ) {
				$result = activate_plugin( $plugin_file );
				if ( is_wp_error( $result ) ) {
					return new \WP_Error(
						'activation_failed',
						$result->get_error_message(),
						array( 'status' => 500 )
					);
				}
			}

			return rest_ensure_response(
				array(
					'success' => true,
					'message' => sprintf(
						/* translators: %s: Plugin name */
						__( '%s is already installed and activated.', 'layoutberg' ),
						$allowed_plugins[ $slug ]['name']
					),
				)
			);
		}

		// Install plugin.
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		// Get plugin info from WordPress.org.
		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $slug,
				'fields' => array(
					'short_description' => true,
					'sections'          => false,
					'requires'          => false,
					'rating'            => false,
					'ratings'           => false,
					'downloaded'        => false,
					'last_updated'      => false,
					'added'             => false,
					'tags'              => false,
					'homepage'          => false,
					'donate_link'       => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			return new \WP_Error(
				'plugin_info_failed',
				$api->get_error_message(),
				array( 'status' => 500 )
			);
		}

		// Install the plugin.
		$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
		$result   = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) ) {
			return new \WP_Error(
				'installation_failed',
				$result->get_error_message(),
				array( 'status' => 500 )
			);
		}

		// Activate the plugin.
		$activate_result = activate_plugin( $plugin_file );
		if ( is_wp_error( $activate_result ) ) {
			return new \WP_Error(
				'activation_failed',
				$activate_result->get_error_message(),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => sprintf(
					/* translators: %s: Plugin name */
					__( '%s installed and activated successfully!', 'layoutberg' ),
					$allowed_plugins[ $slug ]['name']
				),
			)
		);
	}

	/**
	 * Reset onboarding (for testing or allowing users to restart).
	 *
	 * @since 1.0.0
	 */
	public function reset_onboarding() {
		delete_option( 'layoutberg_onboarding_completed' );
		delete_option( 'layoutberg_onboarding_skipped' );
		delete_option( 'layoutberg_onboarding_progress' );
		delete_option( 'layoutberg_onboarding_completed_at' );
		
		// Set redirect transient.
		set_transient( 'layoutberg_onboarding_redirect', true, 30 );
	}
}