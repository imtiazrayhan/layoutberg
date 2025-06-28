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
		// Create database tables.
		self::create_tables();

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
	 * Create plugin database tables.
	 *
	 * @since 1.0.0
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Settings table.
		$table_settings = $wpdb->prefix . 'layoutberg_settings';
		$sql_settings   = "CREATE TABLE IF NOT EXISTS $table_settings (
			id INT AUTO_INCREMENT PRIMARY KEY,
			setting_key VARCHAR(255) UNIQUE,
			setting_value LONGTEXT,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		) $charset_collate;";

		// Generation history table.
		$table_generations = $wpdb->prefix . 'layoutberg_generations';
		$sql_generations   = "CREATE TABLE IF NOT EXISTS $table_generations (
			id INT AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT,
			prompt TEXT,
			model VARCHAR(50),
			tokens_used INT,
			cost DECIMAL(10,4),
			response LONGTEXT,
			status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_user_id (user_id),
			INDEX idx_created_at (created_at)
		) $charset_collate;";

		// Templates table.
		$table_templates = $wpdb->prefix . 'layoutberg_templates';
		$sql_templates   = "CREATE TABLE IF NOT EXISTS $table_templates (
			id INT AUTO_INCREMENT PRIMARY KEY,
			name VARCHAR(255),
			slug VARCHAR(255) UNIQUE,
			description TEXT,
			content LONGTEXT,
			category VARCHAR(100),
			tags TEXT,
			usage_count INT DEFAULT 0,
			created_by BIGINT,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_category (category),
			INDEX idx_created_by (created_by)
		) $charset_collate;";

		// Usage tracking table.
		$table_usage = $wpdb->prefix . 'layoutberg_usage';
		$sql_usage   = "CREATE TABLE IF NOT EXISTS $table_usage (
			id INT AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT,
			date DATE,
			generations_count INT DEFAULT 0,
			tokens_used INT DEFAULT 0,
			UNIQUE KEY user_date (user_id, date),
			INDEX idx_date (date)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_settings );
		dbDelta( $sql_generations );
		dbDelta( $sql_templates );
		dbDelta( $sql_usage );

		// Store database version.
		update_option( 'layoutberg_db_version', '1.0.0' );
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