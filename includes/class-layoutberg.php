<?php
/**
 * The core plugin class.
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
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since 1.0.0
 */
class LayoutBerg {

	/**
	 * The single instance of the class.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    LayoutBerg
	 */
	private static $instance = null;

	/**
	 * The current version of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string
	 */
	protected $version;

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Loader
	 */
	protected $loader;

	/**
	 * The dependency injection container.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Container
	 */
	protected $container;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->version = LAYOUTBERG_VERSION;
		$this->load_dependencies();
		$this->check_for_upgrades();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_block_hooks();
		$this->define_api_hooks();
		$this->define_onboarding_hooks();
	}

	/**
	 * Get the single instance of the class.
	 *
	 * @since  1.0.0
	 * @return LayoutBerg
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {
		// Load the container class first.
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-container.php';

		// Load the loader class.
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-loader.php';

		// Load admin classes.
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-admin.php';

		// Load public classes.
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-public.php';

		// Load API classes.
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-api-client.php';
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-api-handler.php';

		// Load block generator and related classes.
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-block-generator.php';
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-block-serializer.php';
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-prompt-engineer.php';
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-pattern-variations.php';
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-block-variations.php';
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-content-randomizer.php';

		// Load template manager.
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-template-manager.php';

		// Load cache manager.
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-cache-manager.php';

		// Load security manager.
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-security-manager.php';

		// Load model configuration.
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-model-config.php';

		// Load upgrade handler.
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-upgrade.php';

		// Load onboarding handler.
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-onboarding.php';

		// Load licensing helper.
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-layoutberg-licensing.php';

		// Get container instance and loader.
		$this->container = Container::get_instance();
		$this->loader = $this->container->make( 'DotCamp\LayoutBerg\Loader' );
	}

	/**
	 * Check for database upgrades.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function check_for_upgrades() {
		// Only run on admin requests
		if ( ! is_admin() ) {
			return;
		}

		$upgrade = new Upgrade();
		$upgrade->run();
	}

	/**
	 * Register all of the hooks related to the admin area functionality.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {
		$admin = $this->container->make( 'DotCamp\LayoutBerg\Admin' );

		// Admin scripts and styles.
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );

		// Admin menu.
		$this->loader->add_action( 'admin_menu', $admin, 'add_admin_menu' );

		// Admin notices.
		$this->loader->add_action( 'admin_notices', $admin, 'display_admin_notices' );

		// Settings link on plugins page.
		$this->loader->add_filter( 'plugin_action_links_' . LAYOUTBERG_PLUGIN_BASENAME, $admin, 'add_action_links' );

		// AJAX handlers.
		$this->loader->add_action( 'wp_ajax_layoutberg_generate', $admin, 'ajax_generate_layout' );
		$this->loader->add_action( 'wp_ajax_layoutberg_save_template', $admin, 'ajax_save_template' );
		$this->loader->add_action( 'wp_ajax_layoutberg_get_templates', $admin, 'ajax_get_templates' );
		$this->loader->add_action( 'wp_ajax_layoutberg_get_template', $admin, 'ajax_get_template' );
		$this->loader->add_action( 'wp_ajax_layoutberg_update_template', $admin, 'ajax_update_template' );
		$this->loader->add_action( 'wp_ajax_layoutberg_import_template', $admin, 'ajax_import_template' );
		$this->loader->add_action( 'wp_ajax_layoutberg_get_generation_result', $admin, 'ajax_get_generation_result' );
		$this->loader->add_action( 'wp_ajax_layoutberg_clear_cache', $admin, 'ajax_clear_cache' );

		// Settings save.
		$this->loader->add_action( 'admin_init', $admin, 'register_settings' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_public_hooks() {
		$public = $this->container->make( 'DotCamp\LayoutBerg\PublicFacing' );

		// Public scripts and styles.
		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_scripts' );
	}

	/**
	 * Register all of the hooks related to Gutenberg blocks.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_block_hooks() {
		// Register block categories.
		$this->loader->add_filter( 'block_categories_all', $this, 'add_block_category', 10, 2 );

		// Register blocks.
		$this->loader->add_action( 'init', $this, 'register_blocks' );

		// Enqueue block editor assets.
		$this->loader->add_action( 'enqueue_block_editor_assets', $this, 'enqueue_block_editor_assets' );
	}

	/**
	 * Register all of the hooks related to the REST API.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_api_hooks() {
		$api_handler = $this->container->make( 'DotCamp\LayoutBerg\API_Handler' );

		// Register REST routes.
		$this->loader->add_action( 'rest_api_init', $api_handler, 'register_routes' );
	}

	/**
	 * Register all of the hooks related to the onboarding functionality.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_onboarding_hooks() {
		$onboarding = $this->container->make( 'DotCamp\LayoutBerg\Onboarding' );

		// Onboarding is initialized in the constructor, so we just need to ensure it's created.
		// The hooks are registered within the Onboarding class itself.
	}

	/**
	 * Add custom block category.
	 *
	 * @since 1.0.0
	 * @param array                   $block_categories Array of block categories.
	 * @param WP_Block_Editor_Context $block_editor_context The block editor context.
	 * @return array Modified block categories.
	 */
	public function add_block_category( $block_categories, $block_editor_context ) {
		return array_merge(
			array(
				array(
					'slug'  => 'layoutberg',
					'title' => __( 'LayoutBerg', 'layoutberg' ),
					'icon'  => 'layout',
				),
			),
			$block_categories
		);
	}

	/**
	 * Register Gutenberg blocks.
	 *
	 * @since 1.0.0
	 */
	public function register_blocks() {
		// Register AI Layout block.
		register_block_type(
			LAYOUTBERG_PLUGIN_DIR . 'build/blocks/ai-layout',
			array(
				'render_callback' => array( $this, 'render_ai_layout_block' ),
			)
		);
	}

	/**
	 * Render the AI Layout block.
	 *
	 * @since 1.0.0
	 * @param array  $attributes Block attributes.
	 * @param string $content    Block content.
	 * @return string Rendered block HTML.
	 */
	public function render_ai_layout_block( $attributes, $content ) {
		// The block saves its content, so just return it.
		return $content;
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_block_editor_assets() {
		// Load asset dependencies.
		$asset_file = LAYOUTBERG_PLUGIN_DIR . 'build/editor.asset.php';
		$asset = file_exists( $asset_file ) ? include $asset_file : array(
			'dependencies' => array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components', 'wp-data' ),
			'version'      => $this->version,
		);

		// Enqueue editor script.
		wp_enqueue_script(
			'layoutberg-editor',
			LAYOUTBERG_PLUGIN_URL . 'build/editor.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// Enqueue editor styles.
		wp_enqueue_style(
			'layoutberg-editor',
			LAYOUTBERG_PLUGIN_URL . 'build/editor.css',
			array( 'wp-edit-blocks' ),
			$asset['version']
		);
		
		// Enqueue debug helper in development mode
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			wp_enqueue_script(
				'layoutberg-debug-helper',
				LAYOUTBERG_PLUGIN_URL . 'assets/js/debug-helper.js',
				array(),
				$this->version,
				true
			);
		}

		// Get available models
		$available_models = $this->get_available_models();
		
		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'LayoutBerg models passed to JS: ' . wp_json_encode( $available_models ) );
		}
		
		// Localize script.
		$localized_data = array(
			'apiUrl'     => rest_url( 'layoutberg/v1' ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			'pluginUrl'  => LAYOUTBERG_PLUGIN_URL,
			'isPro'      => $this->is_pro(),
			'models'     => $available_models,
			'strings'    => array(
				'generateLayout'   => __( 'Generate Layout', 'layoutberg' ),
				'generating'       => __( 'Generating...', 'layoutberg' ),
				'error'            => __( 'Error', 'layoutberg' ),
				'tryAgain'         => __( 'Try Again', 'layoutberg' ),
				'selectTemplate'   => __( 'Select a Template', 'layoutberg' ),
				'customPrompt'     => __( 'Custom Prompt', 'layoutberg' ),
				'enterPrompt'      => __( 'Describe the layout you want to create...', 'layoutberg' ),
				'advancedOptions'  => __( 'Advanced Options', 'layoutberg' ),
				'aiModel'          => __( 'AI Model', 'layoutberg' ),
				'style'            => __( 'Style', 'layoutberg' ),
				'colorScheme'      => __( 'Color Scheme', 'layoutberg' ),
				'layoutType'       => __( 'Layout Type', 'layoutberg' ),
			),
		);
		
		// Additional debug output
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'LayoutBerg localizing script with data: ' . wp_json_encode( $localized_data ) );
		}
		
		wp_localize_script(
			'layoutberg-editor',
			'layoutbergEditor',
			$localized_data
		);
	}

	/**
	 * Check if pro version is active.
	 *
	 * @since 1.0.0
	 * @return bool True if pro version is active.
	 */
	private function is_pro() {
		$license = get_option( 'layoutberg_license_key' );
		return ! empty( $license ) && $this->validate_license( $license );
	}

	/**
	 * Validate license key.
	 *
	 * @since 1.0.0
	 * @param string $license License key.
	 * @return bool True if license is valid.
	 */
	private function validate_license( $license ) {
		// TODO: Implement license validation.
		return true;
	}

	/**
	 * Get available AI models.
	 *
	 * @since 1.0.0
	 * @return array Available models.
	 */
	private function get_available_models() {
		$models = array();
		$options = get_option( 'layoutberg_options', array() );
		
		// Debug logging - let's see what's in options
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'LayoutBerg get_available_models - Raw options:' );
			error_log( print_r( $options, true ) );
		}
		
		// Check for OpenAI API key
		$has_openai_key = false;
		if ( ! empty( $options['openai_api_key'] ) || ! empty( $options['api_key'] ) ) {
			$has_openai_key = true;
		}
		
		// Check for Claude API key
		$has_claude_key = ! empty( $options['claude_api_key'] );
		
		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'LayoutBerg get_available_models:' );
			error_log( '- Options keys: ' . implode( ', ', array_keys( $options ) ) );
			error_log( '- Has OpenAI key: ' . ( $has_openai_key ? 'yes' : 'no' ) );
			error_log( '- Has Claude key: ' . ( $has_claude_key ? 'yes' : 'no' ) );
		}
		
		// Get full model configurations from Model_Config
		$model_config = new \DotCamp\LayoutBerg\Model_Config();
		$all_models = $model_config->get_all_models();
		
		// Group models by provider with full configuration data
		$openai_models = array();
		$claude_models = array();
		
		foreach ( $all_models as $model_id => $config ) {
			if ( $config['provider'] === 'openai' ) {
				$openai_models[ $model_id ] = array(
					'label' => $config['name'] . ' (' . $config['description'] . ')',
					'name' => $config['name'],
					'description' => $config['description'],
					'context_window' => $config['context_window'],
					'max_output' => $config['max_output'],
					'cost_per_1k_input' => $config['cost_per_1k_input'],
					'cost_per_1k_output' => $config['cost_per_1k_output'],
					'supports_json_mode' => $config['supports_json_mode'],
					'supports_functions' => $config['supports_functions'],
				);
			} elseif ( $config['provider'] === 'claude' ) {
				$claude_models[ $model_id ] = array(
					'label' => $config['name'] . ' (' . $config['description'] . ')',
					'name' => $config['name'],
					'description' => $config['description'],
					'context_window' => $config['context_window'],
					'max_output' => $config['max_output'],
					'cost_per_1k_input' => $config['cost_per_1k_input'],
					'cost_per_1k_output' => $config['cost_per_1k_output'],
					'supports_json_mode' => $config['supports_json_mode'],
					'supports_functions' => $config['supports_functions'],
				);
			}
		}
		
		// For now, always show all models for testing
		// TODO: Revert this after testing
		$models['openai'] = array(
			'label' => __( 'OpenAI Models', 'layoutberg' ),
			'models' => $openai_models,
		);
		
		$models['claude'] = array(
			'label' => __( 'Claude Models', 'layoutberg' ),
			'models' => $claude_models,
		);

		return $models;
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  1.0.0
	 * @return Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  1.0.0
	 * @return string The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get the dependency injection container.
	 *
	 * @since  1.0.0
	 * @return Container The dependency injection container.
	 */
	public function get_container() {
		return $this->container;
	}
}