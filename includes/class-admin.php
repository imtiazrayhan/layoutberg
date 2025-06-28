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
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		// Only enqueue on our admin pages or Gutenberg editor.
		if ( ! $this->is_layoutberg_admin_page() && ! $this->is_block_editor() ) {
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
		// Only enqueue on our admin pages or Gutenberg editor.
		if ( ! $this->is_layoutberg_admin_page() && ! $this->is_block_editor() ) {
			return;
		}

		wp_enqueue_script(
			'layoutberg-admin',
			LAYOUTBERG_PLUGIN_URL . 'admin/js/layoutberg-admin.js',
			array( 'jquery', 'wp-i18n' ),
			$this->version,
			true
		);

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
					'confirmDelete'   => __( 'Are you sure you want to delete this template?', 'layoutberg' ),
					'saving'          => __( 'Saving...', 'layoutberg' ),
					'saved'           => __( 'Saved!', 'layoutberg' ),
					'error'           => __( 'An error occurred. Please try again.', 'layoutberg' ),
					'apiKeyRequired'  => __( 'API key is required.', 'layoutberg' ),
					'generating'      => __( 'Generating layout...', 'layoutberg' ),
					'generated'       => __( 'Layout generated successfully!', 'layoutberg' ),
				),
			)
		);

		// Set script translations.
		wp_set_script_translations( 'layoutberg-admin', 'layoutberg' );

		// Enqueue editor script for Gutenberg.
		if ( $this->is_block_editor() ) {
			$this->enqueue_editor_scripts();
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
		$editor_asset = file_exists( $editor_asset_file ) 
			? include $editor_asset_file 
			: array( 'dependencies' => array(), 'version' => $this->version );

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

		// Localize script for the editor.
		wp_localize_script(
			'layoutberg-editor',
			'layoutbergEditor',
			array(
				'apiUrl'    => rest_url( 'layoutberg/v1' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'settings'  => $this->get_default_settings(),
				'strings'   => array(
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
			'temperature' => $options['temperature'] ?? 0.7,
			'maxTokens'   => $options['max_tokens'] ?? 2000,
			'style'       => $options['style_defaults']['style'] ?? 'modern',
			'layout'      => $options['style_defaults']['layout'] ?? 'single-column',
		);
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
			'dashicons-layout',
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

		// Sanitize and encrypt API key.
		if ( isset( $input['api_key'] ) ) {
			$api_key = sanitize_text_field( $input['api_key'] );
			
			// Check if the API key is masked (contains asterisks).
			if ( strpos( $api_key, '*' ) !== false ) {
				// Keep the existing encrypted key.
				$existing_options = get_option( 'layoutberg_options', array() );
				if ( isset( $existing_options['api_key'] ) ) {
					$sanitized['api_key'] = $existing_options['api_key'];
				}
			} elseif ( ! empty( $api_key ) ) {
				// New API key - encrypt it.
				$security = new Security_Manager();
				$sanitized['api_key'] = $security->encrypt_api_key( $api_key );
			}
		}

		// Sanitize model.
		if ( isset( $input['model'] ) ) {
			$allowed_models = array( 'gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo' );
			if ( in_array( $input['model'], $allowed_models, true ) ) {
				$sanitized['model'] = $input['model'];
			}
		}

		// Sanitize max tokens.
		if ( isset( $input['max_tokens'] ) ) {
			$sanitized['max_tokens'] = absint( $input['max_tokens'] );
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
		$sanitized['allow_custom_blocks'] = isset( $input['allow_custom_blocks'] ) && $input['allow_custom_blocks'] == '1';
		$sanitized['analytics_enabled'] = isset( $input['analytics_enabled'] ) && $input['analytics_enabled'] == '1';
		$sanitized['debug_mode'] = isset( $input['debug_mode'] ) && $input['debug_mode'] == '1';

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
		$options = get_option( 'layoutberg_options', array() );
		$encrypted_key = isset( $options['api_key'] ) ? $options['api_key'] : '';
		
		// Decrypt the API key for display.
		$api_key = '';
		if ( ! empty( $encrypted_key ) ) {
			$security = new Security_Manager();
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
		// Check if plugin was just activated.
		if ( get_transient( 'layoutberg_activated' ) ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php 
					printf(
						/* translators: %s: Settings page URL */
						esc_html__( 'LayoutBerg activated successfully! Please %s to get started.', 'layoutberg' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=layoutberg-settings' ) ) . '">' . esc_html__( 'configure your settings', 'layoutberg' ) . '</a>'
					); 
					?>
				</p>
			</div>
			<?php
			delete_transient( 'layoutberg_activated' );
		}

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
}