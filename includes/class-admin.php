<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    LayoutBerg
 * @subpackage Admin
 * @since      1.0.0
 */

namespace DotCamp\LayoutBerg;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @since 1.0.0
 */
class Admin {

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $version The version of this plugin.
	 */
	public function __construct( $version ) {
		$this->version = $version;
		add_action('wp_ajax_layoutberg_render_templates_grid', array($this, 'ajax_render_templates_grid'));
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		// Only enqueue on our admin pages, NOT in the block editor.
		if ( ! $this->is_layoutberg_admin_page() ) {
			return;
		}

		wp_enqueue_style(
			'layoutberg-admin',
			LAYOUTBERG_PLUGIN_URL . 'admin/css/layoutberg-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		// Handle admin scripts and editor scripts separately.
		if ( $this->is_block_editor() ) {
			// Load only editor scripts in the block editor.
			$this->enqueue_editor_scripts();
			return;
		}

		// Only enqueue admin scripts on our admin pages.
		if ( ! $this->is_layoutberg_admin_page() ) {
			return;
		}

		wp_enqueue_script(
			'layoutberg-admin',
			LAYOUTBERG_PLUGIN_URL . 'admin/js/layoutberg-admin.js',
			array( 'jquery', 'wp-i18n', 'wp-api-request' ),
			$this->version,
			true
		);

		// Enqueue template preview script on templates page
		$screen = get_current_screen();
		if ( $screen && 'layoutberg_page_layoutberg-templates' === $screen->id ) {
			// Check if build file exists for template preview
			$preview_asset_file = LAYOUTBERG_PLUGIN_DIR . 'build/admin/template-preview.asset.php';
			$preview_asset      = file_exists( $preview_asset_file )
				? include $preview_asset_file
				: array(
					'dependencies' => array(),
					'version'      => $this->version,
				);

			// Ensure wp-blocks is in the dependencies
			$dependencies = $preview_asset['dependencies'];
			if ( ! in_array( 'wp-blocks', $dependencies ) ) {
				$dependencies[] = 'wp-blocks';
			}
			if ( ! in_array( 'wp-block-library', $dependencies ) ) {
				$dependencies[] = 'wp-block-library';
			}

			wp_enqueue_script(
				'layoutberg-template-preview',
				LAYOUTBERG_PLUGIN_URL . 'build/admin/template-preview.js',
				$dependencies,
				$preview_asset['version'],
				true
			);

			// Enqueue template preview styles
			wp_enqueue_style(
				'layoutberg-template-preview',
				LAYOUTBERG_PLUGIN_URL . 'build/admin/template-preview.css',
				array( 'wp-components' ),
				$preview_asset['version']
			);
		}

		// Localize script.
		wp_localize_script(
			'layoutberg-admin',
			'layoutbergAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'layoutberg_admin_nonce' ),
				'apiUrl'    => rest_url( 'layoutberg/v1' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'strings'   => array(
					'confirmDelete'  => __( 'Are you sure you want to delete this template?', 'layoutberg' ),
					'saving'         => __( 'Saving...', 'layoutberg' ),
					'saved'          => __( 'Saved!', 'layoutberg' ),
					'error'          => __( 'An error occurred. Please try again.', 'layoutberg' ),
					'apiKeyRequired' => __( 'API key is required.', 'layoutberg' ),
					'generating'     => __( 'Generating layout...', 'layoutberg' ),
					'generated'      => __( 'Layout generated successfully!', 'layoutberg' ),
				),
			)
		);

		// Set script translations.
		wp_set_script_translations( 'layoutberg-admin', 'layoutberg' );

		// Enqueue onboarding scripts on onboarding page
		if ( $screen && 'admin_page_layoutberg-onboarding' === $screen->id ) {
			// Check if build file exists for onboarding
			$onboarding_asset_file = LAYOUTBERG_PLUGIN_DIR . 'build/admin/onboarding.asset.php';
			$onboarding_asset      = file_exists( $onboarding_asset_file )
				? include $onboarding_asset_file
				: array(
					'dependencies' => array(),
					'version'      => $this->version,
				);

			wp_enqueue_script(
				'layoutberg-onboarding',
				LAYOUTBERG_PLUGIN_URL . 'build/admin/onboarding.js',
				$onboarding_asset['dependencies'],
				$onboarding_asset['version'],
				true
			);

			// Enqueue onboarding styles
			wp_enqueue_style(
				'layoutberg-onboarding',
				LAYOUTBERG_PLUGIN_URL . 'build/admin/onboarding.css',
				array( 'wp-components' ),
				$onboarding_asset['version']
			);

			// Localize onboarding script
			wp_localize_script(
				'layoutberg-onboarding',
				'layoutbergOnboarding',
				array(
					'apiUrl'       => rest_url( 'layoutberg/v1' ),
					'restNonce'    => wp_create_nonce( 'wp_rest' ),
					'adminUrl'     => admin_url(),
					'pluginUrl'    => LAYOUTBERG_PLUGIN_URL,
					'settingsUrl'  => admin_url( 'admin.php?page=layoutberg-settings' ),
					'editorUrl'    => admin_url( 'post-new.php?post_type=page' ),
					'dashboardUrl' => admin_url( 'admin.php?page=layoutberg' ),
					'plugins'      => array(
						'ultimate-blocks' => array(
							'slug'        => 'ultimate-blocks',
							'name'        => 'Ultimate Blocks',
							'description' => __( 'A collection of essential blocks to supercharge your content creation.', 'layoutberg' ),
							'installed'   => file_exists( WP_PLUGIN_DIR . '/ultimate-blocks/ultimate-blocks.php' ),
							'active'      => is_plugin_active( 'ultimate-blocks/ultimate-blocks.php' ),
						),
						'tableberg'       => array(
							'slug'        => 'tableberg',
							'name'        => 'TableBerg',
							'description' => __( 'Create beautiful, responsive tables with advanced features.', 'layoutberg' ),
							'installed'   => file_exists( WP_PLUGIN_DIR . '/tableberg/tableberg.php' ),
							'active'      => is_plugin_active( 'tableberg/tableberg.php' ),
						),
					),
					'strings'      => array(
						'welcome'           => __( 'Welcome to LayoutBerg', 'layoutberg' ),
						'next'              => __( 'Next', 'layoutberg' ),
						'back'              => __( 'Back', 'layoutberg' ),
						'skip'              => __( 'Skip', 'layoutberg' ),
						'finish'            => __( 'Finish Setup', 'layoutberg' ),
						'installing'        => __( 'Installing...', 'layoutberg' ),
						'activating'        => __( 'Activating...', 'layoutberg' ),
						'installed'         => __( 'Installed', 'layoutberg' ),
						'active'            => __( 'Active', 'layoutberg' ),
						'error'             => __( 'An error occurred. Please try again.', 'layoutberg' ),
						'connectionError'   => __( 'Failed to connect. Please check your API key.', 'layoutberg' ),
						'connectionSuccess' => __( 'Connected successfully!', 'layoutberg' ),
					),
				)
			);

			// Set script translations
			wp_set_script_translations( 'layoutberg-onboarding', 'layoutberg' );
		}
	}

	/**
	 * Register the JavaScript for the block editor.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_editor_scripts() {
		// Check if build file exists.
		$editor_asset_file = LAYOUTBERG_PLUGIN_DIR . 'build/editor.asset.php';
		$editor_asset      = file_exists( $editor_asset_file )
			? include $editor_asset_file
			: array(
				'dependencies' => array(),
				'version'      => $this->version,
			);

		wp_enqueue_script(
			'layoutberg-editor',
			LAYOUTBERG_PLUGIN_URL . 'build/editor.js',
			$editor_asset['dependencies'],
			$editor_asset['version'],
			true
		);

		wp_enqueue_style(
			'layoutberg-editor',
			LAYOUTBERG_PLUGIN_URL . 'build/editor.css',
			array( 'wp-edit-blocks' ),
			$this->version
		);

		// Get available template categories based on user's plan
		$template_manager     = new Template_Manager();
		$available_categories = $template_manager->get_categories();

		// Format categories for JavaScript
		$categories = array();
		foreach ( $available_categories as $value => $label ) {
			$categories[] = array(
				'label' => $label,
				'value' => $value,
			);
		}

		// Localize script for the editor.
		wp_localize_script(
			'layoutberg-editor',
			'layoutbergEditor',
			array(
				'apiUrl'       => rest_url( 'layoutberg/v1' ),
				'restNonce'    => wp_create_nonce( 'wp_rest' ),
				'nonce'        => wp_create_nonce( 'layoutberg_nonce' ),
				'templatesUrl' => admin_url( 'admin.php?page=layoutberg-templates' ),
				'settings'     => $this->get_default_settings(),
				'models'       => $this->get_available_models(),
				'categories'   => $categories,
				'strings'      => array(
					'generating'     => __( 'Generating layout...', 'layoutberg' ),
					'generated'      => __( 'Layout generated successfully!', 'layoutberg' ),
					'error'          => __( 'An error occurred. Please try again.', 'layoutberg' ),
					'promptRequired' => __( 'Please enter a prompt to generate a layout.', 'layoutberg' ),
					'replaceMode'    => __( 'Replace selected blocks', 'layoutberg' ),
					'insertMode'     => __( 'Insert new layout', 'layoutberg' ),
				),
			)
		);

		// Set script translations.
		wp_set_script_translations( 'layoutberg-editor', 'layoutberg' );
	}

	/**
	 * Get default settings for the editor.
	 *
	 * @since 1.0.0
	 * @return array Default settings.
	 */
	private function get_default_settings() {
		$options = get_option( 'layoutberg_options', array() );

		return array(
			'model'       => $options['model'] ?? 'gpt-3.5-turbo',
			'temperature' => floatval( $options['temperature'] ?? 0.7 ),
			'maxTokens'   => intval( $options['max_tokens'] ?? 2000 ),
			'style'       => $options['style_defaults']['style'] ?? 'modern',
			'layout'      => $options['style_defaults']['layout'] ?? 'single-column',
		);
	}

	/**
	 * Get available AI models based on configured API keys.
	 *
	 * @since 1.0.0
	 * @return array Available models grouped by provider.
	 */
	private function get_available_models() {
		$models  = array();
		$options = get_option( 'layoutberg_options', array() );

		// Check API key status
		$openai_key_status = $this->check_openai_key_status();
		$claude_key_status = $this->check_claude_key_status();

		try {
			// Use Model Config for consistent model information
			$model_config = new \DotCamp\LayoutBerg\Model_Config();
			$all_models   = $model_config->get_all_models();

			// Group models by provider
			$openai_models = array();
			$claude_models = array();

			foreach ( $all_models as $model_id => $config ) {
				if ( $config['provider'] === 'openai' ) {
					$openai_models[ $model_id ] = array(
						'label'              => $config['name'] . ' (' . $config['description'] . ')',
						'name'               => $config['name'],
						'description'        => $config['description'],
						'context_window'     => $config['context_window'],
						'max_output'         => $config['max_output'],
						'cost_per_1k_input'  => $config['cost_per_1k_input'],
						'cost_per_1k_output' => $config['cost_per_1k_output'],
						'supports_json_mode' => $config['supports_json_mode'],
						'supports_functions' => $config['supports_functions'],
					);
				} elseif ( $config['provider'] === 'claude' ) {
					$claude_models[ $model_id ] = array(
						'label'              => $config['name'] . ' (' . $config['description'] . ')',
						'name'               => $config['name'],
						'description'        => $config['description'],
						'context_window'     => $config['context_window'],
						'max_output'         => $config['max_output'],
						'cost_per_1k_input'  => $config['cost_per_1k_input'],
						'cost_per_1k_output' => $config['cost_per_1k_output'],
						'supports_json_mode' => $config['supports_json_mode'],
						'supports_functions' => $config['supports_functions'],
					);
				}
			}

			// Only show OpenAI models if API key is configured
			if ( $openai_key_status === 'valid' || ! empty( $options['api_key'] ) ) {
				// Check user's plan for model access
				if ( ! LayoutBerg_Licensing::can_use_premium_code() ) {
					// Expired monthly - only show GPT-3.5 Turbo
					$models['openai'] = array(
						'label'  => __( 'OpenAI Models', 'layoutberg' ),
						'models' => array(
							'gpt-3.5-turbo' => $openai_models['gpt-3.5-turbo'],
						),
					);
				} elseif ( LayoutBerg_Licensing::is_starter_plan() ) {
					// Starter plan - only show GPT-3.5 Turbo
					$models['openai'] = array(
						'label'  => __( 'OpenAI Models', 'layoutberg' ),
						'models' => array(
							'gpt-3.5-turbo' => $openai_models['gpt-3.5-turbo'],
						),
					);
				} else {
					// Professional and Agency - show all OpenAI models
					$models['openai'] = array(
						'label'  => __( 'OpenAI Models', 'layoutberg' ),
						'models' => $openai_models,
					);
				}
			}

			// Only show Claude models if API key is configured AND user has appropriate plan
			if ( $claude_key_status === 'valid' && LayoutBerg_Licensing::can_use_all_models() ) {
				$models['claude'] = array(
					'label'  => __( 'Claude Models', 'layoutberg' ),
					'models' => $claude_models,
				);
			}
		} catch ( Exception $e ) {
			// Fallback to hardcoded models if Model Config fails
			if ( $openai_key_status === 'valid' || ! empty( $options['api_key'] ) ) {
				$fallback_openai_models = array(
					'gpt-3.5-turbo' => array(
						'label'              => __( 'GPT-3.5 Turbo (Fast & Affordable)', 'layoutberg' ),
						'name'               => 'GPT-3.5 Turbo',
						'description'        => 'Fast & Affordable',
						'context_window'     => 16385,
						'max_output'         => 4096,
						'cost_per_1k_input'  => 0.0005,
						'cost_per_1k_output' => 0.0015,
						'supports_json_mode' => true,
						'supports_functions' => true,
					),
					'gpt-4'         => array(
						'label'              => __( 'GPT-4 (Most Capable)', 'layoutberg' ),
						'name'               => 'GPT-4',
						'description'        => 'Most Capable',
						'context_window'     => 8192,
						'max_output'         => 4096,
						'cost_per_1k_input'  => 0.03,
						'cost_per_1k_output' => 0.06,
						'supports_json_mode' => true,
						'supports_functions' => true,
					),
					'gpt-4-turbo'   => array(
						'label'              => __( 'GPT-4 Turbo (Fast & Capable)', 'layoutberg' ),
						'name'               => 'GPT-4 Turbo',
						'description'        => 'Fast & Capable',
						'context_window'     => 128000,
						'max_output'         => 4096,
						'cost_per_1k_input'  => 0.01,
						'cost_per_1k_output' => 0.03,
						'supports_json_mode' => true,
						'supports_functions' => true,
					),
				);

				// Apply plan restrictions to fallback models
				if ( ! LayoutBerg_Licensing::can_use_premium_code() || LayoutBerg_Licensing::is_starter_plan() ) {
					// Expired monthly or Starter - only show GPT-3.5 Turbo
					$models['openai'] = array(
						'label'  => __( 'OpenAI Models', 'layoutberg' ),
						'models' => array(
							'gpt-3.5-turbo' => $fallback_openai_models['gpt-3.5-turbo'],
						),
					);
				} else {
					// Professional and Agency - show all OpenAI models
					$models['openai'] = array(
						'label'  => __( 'OpenAI Models', 'layoutberg' ),
						'models' => $fallback_openai_models,
					);
				}
			}

			// Only show Claude models if API key is configured AND user has Professional or Agency plan
			if ( $claude_key_status === 'valid' && LayoutBerg_Licensing::can_use_all_models() ) {
				$models['claude'] = array(
					'label'  => __( 'Claude Models', 'layoutberg' ),
					'models' => array(
						'claude-3-opus-20240229'     => array(
							'label'              => __( 'Claude 3 Opus (Most Powerful)', 'layoutberg' ),
							'name'               => 'Claude 3 Opus',
							'description'        => 'Most Powerful',
							'context_window'     => 200000,
							'max_output'         => 4096,
							'cost_per_1k_input'  => 0.015,
							'cost_per_1k_output' => 0.075,
							'supports_json_mode' => false,
							'supports_functions' => false,
						),
						'claude-3-5-sonnet-20241022' => array(
							'label'              => __( 'Claude 3.5 Sonnet (Latest & Fast)', 'layoutberg' ),
							'name'               => 'Claude 3.5 Sonnet',
							'description'        => 'Latest & Fast',
							'context_window'     => 200000,
							'max_output'         => 8192,
							'cost_per_1k_input'  => 0.003,
							'cost_per_1k_output' => 0.015,
							'supports_json_mode' => false,
							'supports_functions' => false,
						),
						'claude-3-haiku-20240307'    => array(
							'label'              => __( 'Claude 3 Haiku (Fast & Light)', 'layoutberg' ),
							'name'               => 'Claude 3 Haiku',
							'description'        => 'Fast & Light',
							'context_window'     => 200000,
							'max_output'         => 4096,
							'cost_per_1k_input'  => 0.00025,
							'cost_per_1k_output' => 0.00125,
							'supports_json_mode' => false,
							'supports_functions' => false,
						),
					),
				);
			}
		}

		return $models;
	}

	/**
	 * Check OpenAI API key status.
	 *
	 * @since 1.0.0
	 * @return string Status of the OpenAI API key.
	 */
	private function check_openai_key_status() {
		$options = get_option( 'layoutberg_options', array() );

		if ( ! empty( $options['openai_api_key'] ) ) {
			$security  = new \DotCamp\LayoutBerg\Security_Manager();
			$decrypted = $security->decrypt_api_key( $options['openai_api_key'] );
			if ( $decrypted ) {
				return 'valid';
			}
		} elseif ( ! empty( $options['api_key'] ) ) {
			// Backward compatibility
			$security  = new \DotCamp\LayoutBerg\Security_Manager();
			$decrypted = $security->decrypt_api_key( $options['api_key'] );
			if ( $decrypted ) {
				return 'valid';
			}
		}

		return '';
	}

	/**
	 * Check Claude API key status.
	 *
	 * @since 1.0.0
	 * @return string Status of the Claude API key.
	 */
	private function check_claude_key_status() {
		$options = get_option( 'layoutberg_options', array() );

		if ( ! empty( $options['claude_api_key'] ) ) {
			$security  = new \DotCamp\LayoutBerg\Security_Manager();
			$decrypted = $security->decrypt_api_key( $options['claude_api_key'] );
			if ( $decrypted ) {
				return 'valid';
			}
		}

		return '';
	}

	/**
	 * Get base64 encoded menu icon.
	 *
	 * @since 1.0.0
	 * @return string Base64 encoded icon data URI.
	 */
	private function get_menu_icon() {
		// For now, use dashicon and we'll override with CSS
		return 'dashicons-layout';
	}

	/**
	 * Add admin menu items.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		// Main menu.
		add_menu_page(
			__( 'LayoutBerg', 'layoutberg' ),
			__( 'LayoutBerg', 'layoutberg' ),
			'layoutberg_view_analytics',
			'layoutberg',
			array( $this, 'display_dashboard_page' ),
			$this->get_menu_icon(),
			30
		);

		// Dashboard submenu.
		add_submenu_page(
			'layoutberg',
			__( 'Dashboard', 'layoutberg' ),
			__( 'Dashboard', 'layoutberg' ),
			'layoutberg_view_analytics',
			'layoutberg',
			array( $this, 'display_dashboard_page' )
		);

		// Templates submenu.
		add_submenu_page(
			'layoutberg',
			__( 'Templates', 'layoutberg' ),
			__( 'Templates', 'layoutberg' ),
			'layoutberg_manage_templates',
			'layoutberg-templates',
			array( $this, 'display_templates_page' )
		);

		// Settings submenu.
		add_submenu_page(
			'layoutberg',
			__( 'Settings', 'layoutberg' ),
			__( 'Settings', 'layoutberg' ),
			'layoutberg_configure',
			'layoutberg-settings',
			array( $this, 'display_settings_page' )
		);

		// Usage Analytics submenu.
		add_submenu_page(
			'layoutberg',
			__( 'Usage Analytics', 'layoutberg' ),
			__( 'Usage Analytics', 'layoutberg' ),
			'layoutberg_view_analytics',
			'layoutberg-analytics',
			array( $this, 'display_analytics_page' )
		);

		// Debug submenu (temporary - remove in production).
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			add_submenu_page(
				'layoutberg',
				__( 'Debug', 'layoutberg' ),
				__( 'Debug', 'layoutberg' ),
				'manage_options',
				'layoutberg-debug',
				array( $this, 'display_debug_page' )
			);
		}

		// History submenu.
		add_submenu_page(
			'layoutberg',
			__( 'Generation History', 'layoutberg' ),
			__( 'History', 'layoutberg' ),
			'layoutberg_view_analytics',
			'layoutberg-history',
			array( $this, 'display_history_page' )
		);

		// Temporary: Add hidden upgrade page
		add_submenu_page(
			null, // Hidden from menu
			__( 'Upgrade DB', 'layoutberg' ),
			__( 'Upgrade DB', 'layoutberg' ),
			'manage_options',
			'layoutberg-upgrade-db',
			array( $this, 'display_upgrade_db_page' )
		);

		// Temporary: Add hidden test usage page
		add_submenu_page(
			null, // Hidden from menu
			__( 'Test Usage', 'layoutberg' ),
			__( 'Test Usage', 'layoutberg' ),
			'manage_options',
			'layoutberg-test-usage',
			array( $this, 'display_test_usage_page' )
		);

		// Temporary: Add hidden reset data page
		add_submenu_page(
			null, // Hidden from menu
			__( 'Reset Data', 'layoutberg' ),
			__( 'Reset Data', 'layoutberg' ),
			'manage_options',
			'layoutberg-reset-data',
			array( $this, 'display_reset_data_page' )
		);

		// Add hidden generation details page
		add_submenu_page(
			null, // Hidden from menu
			__( 'Generation Details', 'layoutberg' ),
			__( 'Generation Details', 'layoutberg' ),
			'manage_options',
			'layoutberg-generation-details',
			array( $this, 'display_generation_details_page' )
		);

		// Add hidden onboarding page
		add_submenu_page(
			null, // Hidden from menu
			__( 'Welcome to LayoutBerg', 'layoutberg' ),
			__( 'Welcome', 'layoutberg' ),
			'manage_options',
			'layoutberg-onboarding',
			array( $this, 'display_onboarding_page' )
		);
	}

	/**
	 * Display the dashboard page.
	 *
	 * @since 1.0.0
	 */
	public function display_dashboard_page() {
		require_once LAYOUTBERG_PLUGIN_DIR . 'admin/partials/layoutberg-admin-dashboard.php';
	}

	/**
	 * Display the templates page.
	 *
	 * @since 1.0.0
	 */
	public function display_templates_page() {
		require_once LAYOUTBERG_PLUGIN_DIR . 'admin/partials/layoutberg-admin-templates.php';
	}

	/**
	 * Display the history page.
	 *
	 * @since 1.0.0
	 */
	public function display_history_page() {
		require_once LAYOUTBERG_PLUGIN_DIR . 'admin/partials/layoutberg-admin-history.php';
	}

	/**
	 * Display the settings page.
	 *
	 * @since 1.0.0
	 */
	public function display_settings_page() {
		require_once LAYOUTBERG_PLUGIN_DIR . 'admin/partials/layoutberg-admin-settings.php';
	}

	/**
	 * Display the analytics page.
	 *
	 * @since 1.0.0
	 */
	public function display_analytics_page() {
		require_once LAYOUTBERG_PLUGIN_DIR . 'admin/partials/layoutberg-admin-analytics.php';
	}

	/**
	 * Display the debug page.
	 *
	 * @since 1.0.0
	 */
	public function display_debug_page() {
		require_once LAYOUTBERG_PLUGIN_DIR . 'admin/partials/layoutberg-admin-debug.php';
	}

	/**
	 * Display the upgrade DB page.
	 *
	 * @since 1.0.0
	 */
	public function display_upgrade_db_page() {
		require_once LAYOUTBERG_PLUGIN_DIR . 'admin/partials/layoutberg-admin-upgrade-db.php';
	}

	/**
	 * Display the test usage page.
	 *
	 * @since 1.0.0
	 */
	public function display_test_usage_page() {
		require_once LAYOUTBERG_PLUGIN_DIR . 'admin/partials/layoutberg-admin-test-usage.php';
	}

	/**
	 * Display the reset data page.
	 *
	 * @since 1.0.0
	 */
	public function display_reset_data_page() {
		require_once LAYOUTBERG_PLUGIN_DIR . 'admin/partials/layoutberg-admin-reset-data.php';
	}

	/**
	 * Display the generation details page.
	 *
	 * @since 1.0.0
	 */
	public function display_generation_details_page() {
		require_once LAYOUTBERG_PLUGIN_DIR . 'admin/partials/layoutberg-admin-generation-details.php';
	}

	/**
	 * Display the onboarding page.
	 *
	 * @since 1.0.0
	 */
	public function display_onboarding_page() {
		require_once LAYOUTBERG_PLUGIN_DIR . 'admin/partials/layoutberg-admin-onboarding.php';
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting(
			'layoutberg_settings',
			'layoutberg_options',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);

		// We're using a custom tabbed interface in the settings page,
		// so we don't need to register individual fields here.
		// The register_setting is still needed for the options.php handler.
	}

	/**
	 * Sanitize settings.
	 *
	 * @since 1.0.0
	 * @param array $input Settings input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// Get existing options.
		$existing_options = get_option( 'layoutberg_options', array() );
		$security         = new Security_Manager();

		// Handle OpenAI API key.
		if ( isset( $input['openai_api_key'] ) ) {
			$openai_key = sanitize_text_field( $input['openai_api_key'] );

			// Check if the API key is masked (contains asterisks) or empty when we have a stored key.
			if ( ( strpos( $openai_key, '*' ) !== false ) || ( empty( $openai_key ) && isset( $input['has_openai_key'] ) && $input['has_openai_key'] == '1' ) ) {
				// Keep the existing encrypted key.
				if ( isset( $existing_options['openai_api_key'] ) ) {
					$sanitized['openai_api_key'] = $existing_options['openai_api_key'];
				} elseif ( isset( $existing_options['api_key'] ) ) {
					// Migrate from old api_key to openai_api_key
					$sanitized['openai_api_key'] = $existing_options['api_key'];
				}
			} elseif ( ! empty( $openai_key ) ) {
				// New API key - encrypt it.
				$sanitized['openai_api_key'] = $security->encrypt_api_key( $openai_key );
			} elseif ( empty( $openai_key ) && ! isset( $input['has_openai_key'] ) ) {
				// User cleared the API key field intentionally
				$sanitized['openai_api_key'] = '';
			}
		} else {
			// If OpenAI key field is not in the input, preserve the existing one
			if ( isset( $existing_options['openai_api_key'] ) ) {
				$sanitized['openai_api_key'] = $existing_options['openai_api_key'];
			} elseif ( isset( $existing_options['api_key'] ) ) {
				// Migrate from old api_key to openai_api_key
				$sanitized['openai_api_key'] = $existing_options['api_key'];
			}
		}

		// Handle Claude API key.
		if ( isset( $input['claude_api_key'] ) ) {
			$claude_key = sanitize_text_field( $input['claude_api_key'] );

			// Check if the API key is masked (contains asterisks) or empty when we have a stored key.
			if ( ( strpos( $claude_key, '*' ) !== false ) || ( empty( $claude_key ) && isset( $input['has_claude_key'] ) && $input['has_claude_key'] == '1' ) ) {
				// Keep the existing encrypted key.
				if ( isset( $existing_options['claude_api_key'] ) ) {
					$sanitized['claude_api_key'] = $existing_options['claude_api_key'];
				}
			} elseif ( ! empty( $claude_key ) ) {
				// New API key - encrypt it.
				$sanitized['claude_api_key'] = $security->encrypt_api_key( $claude_key );
			} elseif ( empty( $claude_key ) && ! isset( $input['has_claude_key'] ) ) {
				// User cleared the API key field intentionally
				$sanitized['claude_api_key'] = '';
			}
		} else {
			// If Claude key field is not in the input, preserve the existing one
			if ( isset( $existing_options['claude_api_key'] ) ) {
				$sanitized['claude_api_key'] = $existing_options['claude_api_key'];
			}
		}

		// Clean up old api_key field if migration happened
		if ( isset( $sanitized['openai_api_key'] ) && isset( $existing_options['api_key'] ) ) {
			// We'll let it be removed by not including it in sanitized
		}

		// Sanitize model.
		if ( isset( $input['model'] ) ) {
			try {
				$allowed_models = array_keys( \DotCamp\LayoutBerg\Model_Config::get_all_models() );
			} catch ( Exception $e ) {
				// Fallback to hardcoded models if Model Config fails
				$allowed_models = array(
					// OpenAI models
					'gpt-3.5-turbo',
					'gpt-4',
					'gpt-4-turbo',
					// Claude models
					'claude-3-opus-20240229',
					'claude-3-5-sonnet-20241022',
					'claude-3-sonnet-20240229',
					'claude-3-haiku-20240307',
				);
			}

			if ( in_array( $input['model'], $allowed_models, true ) ) {
				$sanitized['model'] = $input['model'];
			}
		}

		// Sanitize max tokens.
		if ( isset( $input['max_tokens'] ) ) {
			$max_tokens = absint( $input['max_tokens'] );
			// Ensure max tokens doesn't exceed 4096 for GPT-3.5-turbo
			$sanitized['max_tokens'] = min( 4096, $max_tokens );
		}

		// Handle other settings that might be present in the form.
		// Temperature.
		if ( isset( $input['temperature'] ) ) {
			$sanitized['temperature'] = floatval( $input['temperature'] );
			$sanitized['temperature'] = max( 0, min( 2, $sanitized['temperature'] ) );
		}

		// Cache settings.
		$sanitized['cache_enabled'] = isset( $input['cache_enabled'] ) && $input['cache_enabled'] == '1';

		if ( isset( $input['cache_duration'] ) ) {
			$sanitized['cache_duration'] = absint( $input['cache_duration'] );
		}

		// Style defaults.
		if ( isset( $input['style_defaults'] ) && is_array( $input['style_defaults'] ) ) {
			$sanitized['style_defaults'] = array(
				'style'   => sanitize_text_field( $input['style_defaults']['style'] ?? 'modern' ),
				'colors'  => sanitize_text_field( $input['style_defaults']['colors'] ?? 'brand' ),
				'layout'  => sanitize_text_field( $input['style_defaults']['layout'] ?? 'single-column' ),
				'density' => sanitize_text_field( $input['style_defaults']['density'] ?? 'balanced' ),
			);
		}

		// Advanced settings.
		$sanitized['allow_custom_blocks']       = isset( $input['allow_custom_blocks'] ) && $input['allow_custom_blocks'] == '1';
		$sanitized['analytics_enabled']         = isset( $input['analytics_enabled'] ) && $input['analytics_enabled'] == '1';
		$sanitized['debug_mode']                = isset( $input['debug_mode'] ) && $input['debug_mode'] == '1';
		$sanitized['use_simplified_generation'] = isset( $input['use_simplified_generation'] ) && $input['use_simplified_generation'] == '1';

		// Merge with existing options.
		$options = get_option( 'layoutberg_options', array() );
		return wp_parse_args( $sanitized, $options );
	}

	/**
	 * Render API settings section.
	 *
	 * @since 1.0.0
	 * @deprecated No longer used - using custom tabbed interface
	 */
	public function render_api_settings_section() {
		echo '<p>' . esc_html__( 'Configure your OpenAI API settings.', 'layoutberg' ) . '</p>';
	}

	/**
	 * Render API key field.
	 *
	 * @since 1.0.0
	 * @deprecated No longer used - using custom tabbed interface
	 */
	public function render_api_key_field() {
		$options       = get_option( 'layoutberg_options', array() );
		$encrypted_key = isset( $options['api_key'] ) ? $options['api_key'] : '';

		// Decrypt the API key for display.
		$api_key = '';
		if ( ! empty( $encrypted_key ) ) {
			$security  = new Security_Manager();
			$decrypted = $security->decrypt_api_key( $encrypted_key );
			if ( $decrypted ) {
				// Mask the API key for security.
				$api_key = substr( $decrypted, 0, 7 ) . str_repeat( '*', 20 ) . substr( $decrypted, -4 );
			}
		}
		?>
		<input 
			type="password" 
			id="layoutberg_api_key" 
			name="layoutberg_options[api_key]" 
			value="<?php echo esc_attr( $api_key ); ?>" 
			class="regular-text"
			placeholder="sk-..."
		/>
		<p class="description">
			<?php
			printf(
				/* translators: %s: OpenAI platform URL */
				esc_html__( 'Enter your OpenAI API key. Get one from %s', 'layoutberg' ),
				'<a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render model field.
	 *
	 * @since 1.0.0
	 * @deprecated No longer used - using custom tabbed interface
	 */
	public function render_model_field() {
		$options = get_option( 'layoutberg_options', array() );
		$model   = isset( $options['model'] ) ? $options['model'] : 'gpt-3.5-turbo';
		?>
		<select id="layoutberg_model" name="layoutberg_options[model]">
			<option value="gpt-3.5-turbo" <?php selected( $model, 'gpt-3.5-turbo' ); ?>>
				<?php esc_html_e( 'GPT-3.5 Turbo (Fast & Affordable)', 'layoutberg' ); ?>
			</option>
			<option value="gpt-4" <?php selected( $model, 'gpt-4' ); ?>>
				<?php esc_html_e( 'GPT-4 (Most Capable)', 'layoutberg' ); ?>
			</option>
			<option value="gpt-4-turbo" <?php selected( $model, 'gpt-4-turbo' ); ?>>
				<?php esc_html_e( 'GPT-4 Turbo (Fast & Capable)', 'layoutberg' ); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Select the AI model to use for layout generation.', 'layoutberg' ); ?>
		</p>
		<?php
	}

	/**
	 * Render generation settings section.
	 *
	 * @since 1.0.0
	 * @deprecated No longer used - using custom tabbed interface
	 */
	public function render_generation_settings_section() {
		echo '<p>' . esc_html__( 'Configure layout generation settings.', 'layoutberg' ) . '</p>';
	}

	/**
	 * Render max tokens field.
	 *
	 * @since 1.0.0
	 * @deprecated No longer used - using custom tabbed interface
	 */
	public function render_max_tokens_field() {
		$options    = get_option( 'layoutberg_options', array() );
		$max_tokens = isset( $options['max_tokens'] ) ? $options['max_tokens'] : 2000;
		?>
		<input 
			type="number" 
			id="layoutberg_max_tokens" 
			name="layoutberg_options[max_tokens]" 
			value="<?php echo esc_attr( $max_tokens ); ?>" 
			min="100" 
			max="8000" 
			step="100"
		/>
		<p class="description">
			<?php esc_html_e( 'Maximum number of tokens to use for generation (100-8000).', 'layoutberg' ); ?>
		</p>
		<?php
	}

	/**
	 * Display admin notices.
	 *
	 * @since 1.0.0
	 */
	public function display_admin_notices() {

		// Check if API key is configured.
		if ( $this->is_layoutberg_admin_page() ) {
			$options = get_option( 'layoutberg_options', array() );
			if ( empty( $options['api_key'] ) ) {
				?>
				<div class="notice notice-warning">
					<p>
						<?php
						printf(
							/* translators: %s: Settings page URL */
							esc_html__( 'LayoutBerg requires an OpenAI API key to function. Please %s.', 'layoutberg' ),
							'<a href="' . esc_url( admin_url( 'admin.php?page=layoutberg-settings' ) ) . '">' . esc_html__( 'add your API key', 'layoutberg' ) . '</a>'
						);
						?>
					</p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Add plugin action links.
	 *
	 * @since 1.0.0
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function add_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=layoutberg-settings' ) . '">' . __( 'Settings', 'layoutberg' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	/**
	 * Handle AJAX layout generation.
	 *
	 * @since 1.0.0
	 */
	public function ajax_generate_layout() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'layoutberg_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'layoutberg' ) );
		}

		// Check capabilities.
		if ( ! current_user_can( 'layoutberg_generate' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'layoutberg' ) );
		}

		// Get and validate prompt.
		$prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
		if ( empty( $prompt ) ) {
			wp_send_json_error( __( 'Prompt is required.', 'layoutberg' ) );
		}

		// Get options.
		$options = isset( $_POST['options'] ) ? json_decode( wp_unslash( $_POST['options'] ), true ) : array();

		// Generate layout.
		$generator = new Block_Generator();
		$result    = $generator->generate( $prompt, $options );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle AJAX template save.
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_template() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'layoutberg_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'layoutberg' ) );
		}

		// Check capabilities.
		if ( ! current_user_can( 'layoutberg_manage_templates' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'layoutberg' ) );
		}

		// Get template data.
		$name        = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$content     = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$category    = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : 'general';

		if ( empty( $name ) || empty( $content ) ) {
			wp_send_json_error( __( 'Template name and content are required.', 'layoutberg' ) );
		}

		// Save template.
		$template_manager = new Template_Manager();
		$result           = $template_manager->save_template(
			array(
				'name'        => $name,
				'content'     => $content,
				'description' => $description,
				'category'    => $category,
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle AJAX get templates.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_templates() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'layoutberg_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'layoutberg' ) );
		}

		// Get parameters.
		$category = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : '';
		$search   = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';

		// Get templates.
		$template_manager = new Template_Manager();
		$templates        = $template_manager->get_templates(
			array(
				'category' => $category,
				'search'   => $search,
			)
		);

		wp_send_json_success( $templates );
	}

	/**
	 * Handle AJAX clear cache request.
	 *
	 * @since 1.0.0
	 */
	public function ajax_clear_cache() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'layoutberg_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed.', 'layoutberg' ) );
		}

		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'layoutberg' ) );
		}

		// Clear the cache.
		$cache_manager = new Cache_Manager();
		$result        = $cache_manager->flush();

		if ( $result ) {
			// Get cache stats after clearing.
			$stats = $cache_manager->get_stats();

			wp_send_json_success(
				array(
					'message' => __( 'Cache cleared successfully!', 'layoutberg' ),
					'stats'   => $stats,
				)
			);
		} else {
			wp_send_json_error( __( 'Failed to clear cache.', 'layoutberg' ) );
		}
	}

	/**
	 * Check if current page is a LayoutBerg admin page.
	 *
	 * @since 1.0.0
	 * @return bool True if on LayoutBerg admin page.
	 */
	private function is_layoutberg_admin_page() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		return strpos( $screen->id, 'layoutberg' ) !== false;
	}

	/**
	 * Check if current page is block editor.
	 *
	 * @since 1.0.0
	 * @return bool True if on block editor.
	 */
	private function is_block_editor() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		return $screen->is_block_editor();
	}

	/**
	 * AJAX handler to get a single template.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_template() {
		// Check nonce.
		if ( ! check_ajax_referer( 'layoutberg_nonce', '_wpnonce', false ) ) {
			wp_send_json_error( __( 'Invalid security token.', 'layoutberg' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'layoutberg_manage_templates' ) ) {
			wp_send_json_error( __( 'You do not have permission to view templates.', 'layoutberg' ) );
		}

		// Get template ID.
		$template_id = isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : 0;
		if ( ! $template_id ) {
			wp_send_json_error( __( 'Invalid template ID.', 'layoutberg' ) );
		}

		// Check if usage should be incremented.
		$increment_usage = isset( $_GET['increment_usage'] ) && $_GET['increment_usage'] == '1';

		// Get template.
		$template_manager = new Template_Manager();
		$template         = $template_manager->get_template( $template_id, $increment_usage );

		if ( is_wp_error( $template ) ) {
			wp_send_json_error( $template->get_error_message() );
		}

		// No longer generating HTML preview as we're using Gutenberg's BlockPreview component
		// The React component will render the preview directly from the block content

		wp_send_json_success( $template );
	}

	/**
	 * Generate HTML preview for template content.
	 *
	 * @since 1.0.0
	 * @param string $content Block content.
	 * @return string Generated HTML preview.
	 */
	private function generate_template_preview_html( $content ) {
		// Parse the block content.
		$blocks = parse_blocks( $content );

		if ( empty( $blocks ) ) {
			return '<p>' . __( 'No blocks found in template.', 'layoutberg' ) . '</p>';
		}

		// Generate HTML using WordPress render_block function.
		$html = '';
		foreach ( $blocks as $block ) {
			$html .= render_block( $block );
		}

		// Wrap in a container with some basic styling for preview.
		$preview_html  = '<div class="layoutberg-template-preview-wrapper">';
		$preview_html .= '<style>
			.layoutberg-template-preview-wrapper {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
				line-height: 1.6;
				color: #23282d;
				background: #fff;
				max-width: 100%;
				overflow-x: auto;
			}
			.layoutberg-template-preview-wrapper h1,
			.layoutberg-template-preview-wrapper h2,
			.layoutberg-template-preview-wrapper h3,
			.layoutberg-template-preview-wrapper h4,
			.layoutberg-template-preview-wrapper h5,
			.layoutberg-template-preview-wrapper h6 {
				color: #1e1e1e;
				margin-top: 0;
				margin-bottom: 0.5em;
			}
			.layoutberg-template-preview-wrapper p {
				margin-bottom: 1em;
			}
			.layoutberg-template-preview-wrapper img {
				max-width: 100%;
				height: auto;
			}
			.layoutberg-template-preview-wrapper .wp-block-group,
			.layoutberg-template-preview-wrapper .wp-block-columns {
				margin-bottom: 1em;
			}
			.layoutberg-template-preview-wrapper .wp-block-column {
				padding: 0 1em;
			}
			.layoutberg-template-preview-wrapper .wp-block-button {
				margin-bottom: 0.5em;
			}
			.layoutberg-template-preview-wrapper .wp-block-button__link {
				background-color: #007cba;
				color: #fff;
				padding: 8px 16px;
				text-decoration: none;
				border-radius: 3px;
				display: inline-block;
			}
		</style>';
		$preview_html .= $html;
		$preview_html .= '</div>';

		return $preview_html;
	}

	/**
	 * AJAX handler to update a template.
	 *
	 * @since 1.0.0
	 */
	public function ajax_update_template() {
		// Check nonce.
		if ( ! check_ajax_referer( 'layoutberg_nonce', '_wpnonce', false ) ) {
			wp_send_json_error( __( 'Invalid security token.', 'layoutberg' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'layoutberg_manage_templates' ) ) {
			wp_send_json_error( __( 'You do not have permission to update templates.', 'layoutberg' ) );
		}

		// Get template data.
		$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
		if ( ! $template_id ) {
			wp_send_json_error( __( 'Invalid template ID.', 'layoutberg' ) );
		}

		$data = array(
			'name'        => isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '',
			'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '',
			'category'    => isset( $_POST['category'] ) ? sanitize_text_field( $_POST['category'] ) : 'custom',
			'tags'        => isset( $_POST['tags'] ) ? array_map( 'trim', explode( ',', sanitize_text_field( $_POST['tags'] ) ) ) : array(),
			'is_public'   => isset( $_POST['is_public'] ) && $_POST['is_public'] === '1' ? 1 : 0,
		);

		// Update template.
		$template_manager = new Template_Manager();
		$result           = $template_manager->update_template( $template_id, $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( __( 'Template updated successfully.', 'layoutberg' ) );
	}

	/**
	 * AJAX handler to import a template.
	 *
	 * @since 1.0.0
	 */
	public function ajax_import_template() {
		// Check nonce.
		if ( ! check_ajax_referer( 'layoutberg_nonce', '_wpnonce', false ) ) {
			wp_send_json_error( __( 'Invalid security token.', 'layoutberg' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'layoutberg_manage_templates' ) ) {
			wp_send_json_error( __( 'You do not have permission to import templates.', 'layoutberg' ) );
		}

		// Check if user can import templates (Professional or Agency plan).
		if ( ! LayoutBerg_Licensing::can_export_templates() ) {
			$message = LayoutBerg_Licensing::is_expired_monthly()
				? __( 'Your subscription has expired. Please renew to import templates.', 'layoutberg' )
				: __( 'Template import is available in the Professional and Agency plans. Please upgrade to import templates.', 'layoutberg' );
			wp_send_json_error( $message );
		}

		// Check file upload.
		if ( ! isset( $_FILES['import_file'] ) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( __( 'No file uploaded or upload error.', 'layoutberg' ) );
		}

		// Validate file type - check the file extension manually since wp_check_filetype might not recognize .json
		$filename       = $_FILES['import_file']['name'];
		$file_extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

		if ( $file_extension !== 'json' ) {
			wp_send_json_error( __( 'Invalid file type. Please upload a JSON file.', 'layoutberg' ) );
		}

		// Read and validate file content
		$content = file_get_contents( $_FILES['import_file']['tmp_name'] );
		if ( ! $content ) {
			wp_send_json_error( __( 'Failed to read file content.', 'layoutberg' ) );
		}

		// Parse and validate JSON
		$template_data = json_decode( $content, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( __( 'Invalid JSON format. Please upload a valid JSON file.', 'layoutberg' ) );
		}

		// Import template.
		try {
			$template_manager = new Template_Manager();
			$template_id      = $template_manager->import_template( $template_data );

			if ( is_wp_error( $template_id ) ) {
				wp_send_json_error( $template_id->get_error_message() );
			}

			wp_send_json_success( __( 'Template imported successfully.', 'layoutberg' ) );
		} catch ( Exception $e ) {
			error_log( 'LayoutBerg Import Error: ' . $e->getMessage() );
			error_log( 'Stack trace: ' . $e->getTraceAsString() );
			wp_send_json_error( __( 'Import failed: ', 'layoutberg' ) . $e->getMessage() );
		}
	}

	/**
	 * AJAX handler to export a template.
	 *
	 * @since 1.0.0
	 */
	public function ajax_export_template() {
		// Check nonce.
		if ( ! check_ajax_referer( 'layoutberg_nonce', '_wpnonce', false ) ) {
			wp_send_json_error( __( 'Invalid security token.', 'layoutberg' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'layoutberg_manage_templates' ) ) {
			wp_send_json_error( __( 'You do not have permission to export templates.', 'layoutberg' ) );
		}

		// Check if user can export templates (Professional or Agency plan).
		if ( ! LayoutBerg_Licensing::can_export_templates() ) {
			$message = LayoutBerg_Licensing::is_expired_monthly()
				? __( 'Your subscription has expired. Please renew to export templates.', 'layoutberg' )
				: __( 'Template export is available in the Professional and Agency plans. Please upgrade to export templates.', 'layoutberg' );
			wp_send_json_error( $message );
		}

		// Get template ID.
		$template_id = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
		if ( ! $template_id ) {
			wp_send_json_error( __( 'Invalid template ID.', 'layoutberg' ) );
		}

		// Export template.
		$template_manager = new Template_Manager();
		$template_data    = $template_manager->export_template( $template_id );

		if ( is_wp_error( $template_data ) ) {
			wp_send_json_error( $template_data->get_error_message() );
		}

		// Return template data for download.
		wp_send_json_success( $template_data );
	}

	/**
	 * AJAX handler to get generation result for preview.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_generation_result() {
		// Check nonce.
		if ( ! check_ajax_referer( 'layoutberg_nonce', '_wpnonce', false ) ) {
			wp_send_json_error( __( 'Invalid security token.', 'layoutberg' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'layoutberg_view_analytics' ) ) {
			wp_send_json_error( __( 'You do not have permission to view generation results.', 'layoutberg' ) );
		}

		// Get generation ID.
		$generation_id = isset( $_POST['generation_id'] ) ? absint( $_POST['generation_id'] ) : 0;
		if ( ! $generation_id ) {
			wp_send_json_error( __( 'Invalid generation ID.', 'layoutberg' ) );
		}

		// Get generation from database.
		global $wpdb;
		$table_generations = $wpdb->prefix . 'layoutberg_generations';

		$generation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_generations} WHERE id = %d AND user_id = %d",
				$generation_id,
				get_current_user_id()
			)
		);

		if ( ! $generation ) {
			wp_send_json_error( __( 'Generation not found.', 'layoutberg' ) );
		}

		// Prepare response data.
		$response_data = array(
			'id'      => $generation->id,
			'prompt'  => $generation->prompt,
			'status'  => $generation->status,
			'content' => '',
		);

		// If generation was successful, process the result.
		if ( $generation->status === 'completed' && ! empty( $generation->result_data ) ) {
			$result_data = json_decode( $generation->result_data, true );

			if ( $result_data && isset( $result_data['serialized'] ) ) {
				$response_data['content'] = $result_data['serialized'];

				// Generate HTML preview.
				$response_data['html_preview'] = $this->generate_generation_preview_html( $result_data['serialized'] );
			}
		}

		wp_send_json_success( $response_data );
	}

	/**
	 * Generate HTML preview for generation result.
	 *
	 * @since 1.0.0
	 * @param string $content Block content.
	 * @return string Generated HTML preview.
	 */
	private function generate_generation_preview_html( $content ) {
		// Parse the block content.
		$blocks = parse_blocks( $content );

		if ( empty( $blocks ) ) {
			return '<p>' . __( 'No blocks found in generation result.', 'layoutberg' ) . '</p>';
		}

		// Generate HTML using WordPress render_block function.
		$html = '';
		foreach ( $blocks as $block ) {
			$html .= render_block( $block );
		}

		// Wrap in a container with some basic styling for preview.
		$preview_html  = '<div class="layoutberg-generation-preview-wrapper">';
		$preview_html .= '<style>
			.layoutberg-generation-preview-wrapper {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
				line-height: 1.6;
				color: #23282d;
				background: #fff;
				max-width: 100%;
				overflow-x: auto;
			}
			.layoutberg-generation-preview-wrapper h1,
			.layoutberg-generation-preview-wrapper h2,
			.layoutberg-generation-preview-wrapper h3,
			.layoutberg-generation-preview-wrapper h4,
			.layoutberg-generation-preview-wrapper h5,
			.layoutberg-generation-preview-wrapper h6 {
				color: #1e1e1e;
				margin-top: 0;
				margin-bottom: 0.5em;
			}
			.layoutberg-generation-preview-wrapper p {
				margin-bottom: 1em;
			}
			.layoutberg-generation-preview-wrapper img {
				max-width: 100%;
				height: auto;
			}
			.layoutberg-generation-preview-wrapper .wp-block-group,
			.layoutberg-generation-preview-wrapper .wp-block-columns {
				margin-bottom: 1em;
			}
			.layoutberg-generation-preview-wrapper .wp-block-column {
				padding: 0 1em;
			}
			.layoutberg-generation-preview-wrapper .wp-block-button {
				margin-bottom: 0.5em;
			}
			.layoutberg-generation-preview-wrapper .wp-block-button__link {
				background-color: #007cba;
				color: #fff;
				padding: 8px 16px;
				text-decoration: none;
				border-radius: 3px;
				display: inline-block;
			}
		</style>';
		$preview_html .= $html;
		$preview_html .= '</div>';

		return $preview_html;
	}

	/**
	 * Render pricing modal in admin footer.
	 *
	 * @since 1.0.0
	 */
	public function render_pricing_modal() {
		// Only show on LayoutBerg pages
		if ( ! $this->is_layoutberg_admin_page() ) {
			return;
		}

		$pricing_data = LayoutBerg_Licensing::get_pricing_data();
		$current_plan = LayoutBerg_Licensing::get_plan_name();
		$action_url   = LayoutBerg_Licensing::get_action_url();
		$is_expired   = LayoutBerg_Licensing::is_expired_monthly();
		?>
		<div id="layoutberg-pricing-modal" class="layoutberg-modal" style="display: none;">
			<div class="layoutberg-modal-backdrop"></div>
			<div class="layoutberg-modal-content layoutberg-pricing-modal-content">
				<div class="layoutberg-modal-header">
					<h2><?php esc_html_e( 'Choose Your Plan', 'layoutberg' ); ?></h2>
					<button type="button" class="layoutberg-modal-close">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
				<div class="layoutberg-modal-body">
					<?php if ( $is_expired ) : ?>
						<div class="layoutberg-pricing-intro">
							<div class="layoutberg-alert layoutberg-alert-warning">
								<span class="dashicons dashicons-warning"></span>
								<?php esc_html_e( 'Your subscription has expired. Renew now to continue using premium features.', 'layoutberg' ); ?>
							</div>
						</div>
					<?php endif; ?>
					
					<div class="layoutberg-pricing-grid">
						<?php foreach ( $pricing_data as $plan_key => $plan ) : ?>
							<?php
							$is_current   = stripos( $current_plan, $plan['name'] ) !== false;
							$plan_classes = 'layoutberg-pricing-plan';
							if ( ! empty( $plan['popular'] ) ) {
								$plan_classes .= ' layoutberg-pricing-popular';
							}
							if ( $is_current ) {
								$plan_classes .= ' layoutberg-pricing-current';
							}
							?>
							<div class="<?php echo esc_attr( $plan_classes ); ?>" data-plan="<?php echo esc_attr( $plan_key ); ?>">
								<?php if ( ! empty( $plan['popular'] ) ) : ?>
									<div class="layoutberg-pricing-badge"><?php esc_html_e( 'Most Popular', 'layoutberg' ); ?></div>
								<?php endif; ?>
								<?php if ( $is_current ) : ?>
									<div class="layoutberg-pricing-current-badge"><?php esc_html_e( 'Current Plan', 'layoutberg' ); ?></div>
								<?php endif; ?>
								
								<div class="layoutberg-pricing-header">
									<h3><?php echo esc_html( $plan['name'] ); ?></h3>
									<div class="layoutberg-pricing-price">
										<span class="layoutberg-price-amount"><?php echo esc_html( $plan['price'] ); ?></span>
										<span class="layoutberg-price-period"><?php echo esc_html( $plan['period'] ); ?></span>
									</div>
								</div>
								
								<div class="layoutberg-pricing-features">
									<h4><?php esc_html_e( 'Features:', 'layoutberg' ); ?></h4>
									<ul>
										<?php foreach ( $plan['features'] as $feature ) : ?>
											<li>
												<span class="dashicons dashicons-yes"></span>
												<?php echo esc_html( $feature ); ?>
											</li>
										<?php endforeach; ?>
									</ul>
									
									<?php if ( ! empty( $plan['limitations'] ) ) : ?>
										<h4><?php esc_html_e( 'Limitations:', 'layoutberg' ); ?></h4>
										<ul class="layoutberg-pricing-limitations">
											<?php foreach ( $plan['limitations'] as $limitation ) : ?>
												<li>
													<span class="dashicons dashicons-no"></span>
													<?php echo esc_html( $limitation ); ?>
												</li>
											<?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</div>
								
								<div class="layoutberg-pricing-action">
									<?php if ( $is_current && ! $is_expired ) : ?>
										<button class="button" disabled><?php esc_html_e( 'Your Current Plan', 'layoutberg' ); ?></button>
									<?php else : ?>
										<a href="<?php echo esc_url( $action_url ); ?>" class="button button-primary layoutberg-upgrade-button" data-plan="<?php echo esc_attr( $plan_key ); ?>">
											<?php echo $is_expired ? esc_html__( 'Renew Now', 'layoutberg' ) : esc_html__( 'Upgrade Now', 'layoutberg' ); ?>
										</a>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					
					<div class="layoutberg-pricing-footer">
						<p><?php esc_html_e( 'All plans include priority support and regular updates.', 'layoutberg' ); ?></p>
						<p><small><?php esc_html_e( 'Cancel anytime. No hidden fees.', 'layoutberg' ); ?></small></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function ajax_render_templates_grid() {
		if ( ! current_user_can( 'layoutberg_manage_templates' ) ) {
			wp_send_json_error( __( 'You do not have permission to view templates.', 'layoutberg' ) );
		}
		$category = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : '';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'created_at';

		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-template-manager.php';
		$template_manager = new \DotCamp\LayoutBerg\Template_Manager();
		$args = array(
			'category' => $category,
			'search'   => $search,
			'page'     => $paged,
			'per_page' => 20,
			'orderby'  => $orderby,
			'order'    => 'DESC',
		);
		if ( ! current_user_can( 'manage_options' ) ) {
			$args['user_id'] = get_current_user_id();
		}
		$result = $template_manager->get_templates( $args );
		$templates = $result['templates'];
		$categories = $template_manager->get_categories();

		ob_start();
		if ( empty( $templates ) ) : ?>
			<div class="layoutberg-templates-empty">
				<div class="empty-illustration">
					<span class="dashicons dashicons-layout" style="font-size: 64px; color: #94a3b8; margin-bottom: 16px; display: block;"></span>
				</div>
				<h3><?php esc_html_e( 'No templates yet', 'layoutberg' ); ?></h3>
				<p><?php esc_html_e( 'Templates let you save and reuse your AI-generated layouts.', 'layoutberg' ); ?></p>
				<p><?php esc_html_e( 'To create your first template:', 'layoutberg' ); ?></p>
				<ol>
					<li><?php esc_html_e( 'Click "Add New Template" to open the editor', 'layoutberg' ); ?></li>
					<li><?php esc_html_e( 'Add a LayoutBerg AI Layout block', 'layoutberg' ); ?></li>
					<li><?php esc_html_e( 'Generate a layout with your prompt', 'layoutberg' ); ?></li>
					<li><?php esc_html_e( 'Save it as a template for future use', 'layoutberg' ); ?></li>
				</ol>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=post&layoutberg_create_template=1' ) ); ?>" class="button button-primary button-hero">
					<?php esc_html_e( 'Create Your First Template', 'layoutberg' ); ?>
				</a>
			</div>
		<?php else : ?>
			<div class="layoutberg-templates-grid">
			<?php foreach ( $templates as $template ) : ?>
				<div class="layoutberg-template-card" data-template-id="<?php echo esc_attr( $template->id ); ?>">
					<div class="template-preview">
						<?php if ( ! empty( $template->thumbnail_url ) ) : ?>
							<img src="<?php echo esc_url( $template->thumbnail_url ); ?>" alt="<?php echo esc_attr( $template->name ); ?>">
						<?php else : ?>
							<div class="template-placeholder">
								<span class="dashicons dashicons-layout"></span>
							</div>
						<?php endif; ?>
						<div class="template-actions">
							<button class="button button-primary layoutberg-use-template" data-template-id="<?php echo esc_attr( $template->id ); ?>">
								<?php esc_html_e( 'Use Template', 'layoutberg' ); ?>
							</button>
							<button class="button layoutberg-preview-template" data-template-id="<?php echo esc_attr( $template->id ); ?>">
								<?php esc_html_e( 'Preview', 'layoutberg' ); ?>
							</button>
						</div>
					</div>
					<div class="template-details">
						<h3 class="template-name"><?php echo esc_html( $template->name ); ?></h3>
						<?php if ( ! empty( $template->description ) ) : ?>
							<p class="template-description"><?php echo esc_html( $template->description ); ?></p>
						<?php endif; ?>
						<div class="template-meta">
							<span class="template-category <?php echo esc_attr( $template->category ); ?>">
								<?php echo esc_html( $categories[ $template->category ] ?? $template->category ); ?>
							</span>
							<?php if ( $template->usage_count > 0 ) : ?>
								<span class="template-usage">
									<span class="usage-icon">📊</span>
									<?php
									printf(
										esc_html( _n( '%s use', '%s uses', $template->usage_count, 'layoutberg' ) ),
										number_format_i18n( $template->usage_count )
									);
									?>
								</span>
							<?php endif; ?>
							<?php if ( ! empty( $template->tags ) ) : ?>
								<span class="template-tags">
									<span class="tags-icon">🏷️</span>
									<?php 
									$tags = is_array( $template->tags ) ? $template->tags : explode( ',', $template->tags );
									$tags = array_slice( $tags, 0, 2 ); // Show only first 2 tags
									echo esc_html( implode( ', ', $tags ) );
									if ( count( is_array( $template->tags ) ? $template->tags : explode( ',', $template->tags ) ) > 2 ) {
										echo ' +' . ( count( is_array( $template->tags ) ? $template->tags : explode( ',', $template->tags ) ) - 2 );
									}
									?>
								</span>
							<?php endif; ?>
						</div>
						<div class="template-footer">
							<span class="template-date">
								<?php
								printf(
									esc_html__( 'Created %s', 'layoutberg' ),
									human_time_diff( strtotime( $template->created_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'layoutberg' )
								);
								?>
							</span>
							<?php if ( current_user_can( 'layoutberg_manage_templates' ) && ( current_user_can( 'manage_options' ) || $template->created_by == get_current_user_id() ) ) : ?>
								<span class="template-actions-footer">
									<a href="#" class="edit-template" data-template-id="<?php echo esc_attr( $template->id ); ?>">
										<?php esc_html_e( 'Edit', 'layoutberg' ); ?>
									</a>
									|
									<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'template_id' => $template->id ), admin_url( 'admin.php?page=layoutberg-templates' ) ), 'delete_template_' . $template->id ) ); ?>" class="delete-template" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this template?', 'layoutberg' ); ?>');">
										<?php esc_html_e( 'Delete', 'layoutberg' ); ?>
									</a>
									|
									<?php 
									// Check if user can export templates (Professional or Agency plan)
									$can_export = false;
									if ( function_exists( 'layoutberg_fs' ) ) {
										$can_export = \layoutberg_fs()->can_use_premium_code() && 
													 ( \layoutberg_fs()->is_plan('professional') || \layoutberg_fs()->is_plan('agency') );
									}
									if ( $can_export ) : 
									?>
										<a href="#" class="export-template" data-template-id="<?php echo esc_attr( $template->id ); ?>">
											<?php esc_html_e( 'Export', 'layoutberg' ); ?>
										</a>
									<?php else : ?>
										<?php 
										// Show locked export option if Freemius is available
										if ( function_exists( 'layoutberg_fs' ) ) :
											$button_text = ! \layoutberg_fs()->can_use_premium_code() 
												? __( 'Renew to export', 'layoutberg' )
												: __( 'Upgrade to export', 'layoutberg' );
											$button_url = ! \layoutberg_fs()->can_use_premium_code() 
												? \layoutberg_fs()->get_account_url() 
												: \layoutberg_fs()->get_upgrade_url();
										?>
											<a href="<?php echo esc_url( $button_url ); ?>" class="export-template-locked" title="<?php echo esc_attr( $button_text ); ?>">
												<?php esc_html_e( 'Export', 'layoutberg' ); ?> <span class="dashicons dashicons-lock" style="font-size: 12px; vertical-align: middle;"></span>
											</a>
										<?php endif; ?>
									<?php endif; ?>
								</span>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
			</div>
		<?php endif;
		$html = ob_get_clean();
		wp_send_json_success([ 'html' => $html ]);
	}
}