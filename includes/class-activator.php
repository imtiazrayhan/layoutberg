<?php
/**
 * Fired during plugin activation.
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
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since 1.0.0
 */
class Activator {

	/**
	 * Plugin activation handler.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		// Load required files
		require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-upgrade.php';

		// Create/upgrade database tables.
		$upgrade = new Upgrade();
		$upgrade->create_tables();
		$upgrade->run();

		// Set default options.
		self::set_default_options();

		// Add capabilities.
		self::add_capabilities();

		// Schedule cron events.
		self::schedule_events();

		// Create upload directory.
		self::create_upload_directory();

		// Clear rewrite rules.
		flush_rewrite_rules();

		// Set activation flag.
		set_transient( 'layoutberg_activated', true, 30 );
	}


	/**
	 * Set default plugin options.
	 *
	 * @since 1.0.0
	 */
	private static function set_default_options() {
		$default_options = array(
			'api_key'          => '',
			'model'            => 'gpt-3.5-turbo',
			'max_tokens'       => 2000,
			'temperature'      => 0.7,
			'cache_enabled'    => true,
			'cache_duration'   => 3600,
			'rate_limit'       => array(
				'free'     => array(
					'hour' => 5,
					'day'  => 10,
				),
				'pro'      => array(
					'hour' => 20,
					'day'  => 100,
				),
				'business' => array(
					'hour' => 50,
					'day'  => 500,
				),
			),
			'style_defaults'   => array(
				'style'       => 'modern',
				'colors'      => 'brand',
				'layout'      => 'single-column',
				'density'     => 'balanced',
			),
			'block_restrictions' => array(),
			'analytics_enabled'  => true,
		);

		// Only set if not already exists.
		if ( false === get_option( 'layoutberg_options' ) ) {
			add_option( 'layoutberg_options', $default_options );
		}

		// Set plugin version.
		update_option( 'layoutberg_version', LAYOUTBERG_VERSION );
	}

	/**
	 * Add plugin capabilities to roles.
	 *
	 * @since 1.0.0
	 */
	private static function add_capabilities() {
		$capabilities = array(
			'administrator' => array(
				'layoutberg_generate',
				'layoutberg_manage_templates',
				'layoutberg_view_analytics',
				'layoutberg_configure',
			),
			'editor'        => array(
				'layoutberg_generate',
				'layoutberg_manage_templates',
			),
			'author'        => array(
				'layoutberg_generate',
			),
		);

		foreach ( $capabilities as $role_name => $caps ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( $caps as $cap ) {
					$role->add_cap( $cap );
				}
			}
		}
	}

	/**
	 * Schedule cron events.
	 *
	 * @since 1.0.0
	 */
	private static function schedule_events() {
		// Schedule daily cleanup.
		if ( ! wp_next_scheduled( 'layoutberg_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'layoutberg_daily_cleanup' );
		}

		// Schedule usage reset (monthly).
		if ( ! wp_next_scheduled( 'layoutberg_usage_reset' ) ) {
			wp_schedule_event( strtotime( 'first day of next month' ), 'monthly', 'layoutberg_usage_reset' );
		}
	}

	/**
	 * Create plugin upload directory.
	 *
	 * @since 1.0.0
	 */
	private static function create_upload_directory() {
		$upload_dir = wp_upload_dir();
		$plugin_dir = $upload_dir['basedir'] . '/layoutberg';

		if ( ! file_exists( $plugin_dir ) ) {
			wp_mkdir_p( $plugin_dir );

			// Create .htaccess to protect directory.
			$htaccess_content = "Options -Indexes\n";
			$htaccess_content .= "<FilesMatch '\.(php|php3|php4|php5|php7|phps|phtml|pl|py|jsp|asp|sh|cgi)$'>\n";
			$htaccess_content .= "    Order Deny,Allow\n";
			$htaccess_content .= "    Deny from all\n";
			$htaccess_content .= "</FilesMatch>\n";

			file_put_contents( $plugin_dir . '/.htaccess', $htaccess_content );
		}
	}
}