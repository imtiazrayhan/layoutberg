<?php
/**
 * Database upgrade and migration handler
 *
 * @package    LayoutBerg
 * @subpackage LayoutBerg/includes
 */

namespace DotCamp\LayoutBerg;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles database upgrades and migrations
 *
 * @since      1.0.0
 * @package    LayoutBerg
 * @subpackage LayoutBerg/includes
 * @author     DotCamp <support@dotcamp.com>
 */
class Upgrade {

	/**
	 * The database version option name.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $db_version_option    The database version option name.
	 */
	private $db_version_option = 'layoutberg_db_version';

	/**
	 * Current database version.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $current_db_version    Current database version.
	 */
	private $current_db_version = '1.3.0';

	/**
	 * Run upgrade routines.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$installed_version = get_option( $this->db_version_option, '0' );

		// If this is a fresh install, just set the version
		if ( '0' === $installed_version ) {
			$this->set_db_version();
			return;
		}

		// Run upgrades if needed
		if ( version_compare( $installed_version, $this->current_db_version, '<' ) ) {
			$this->run_upgrades( $installed_version );
		}
	}

	/**
	 * Run all necessary upgrades.
	 *
	 * @since    1.0.0
	 * @param    string $from_version The version to upgrade from.
	 */
	private function run_upgrades( $from_version ) {
		// Get all upgrade methods
		$upgrades = $this->get_upgrades();

		foreach ( $upgrades as $version => $method ) {
			// Only run upgrades newer than the installed version
			if ( version_compare( $from_version, $version, '<' ) ) {
				$this->$method();
			}
		}

		// Update the database version
		$this->set_db_version();
	}

	/**
	 * Get all available upgrades.
	 *
	 * @since    1.0.0
	 * @return   array    Array of version => method name.
	 */
	private function get_upgrades() {
		return array(
			'1.1.0' => 'upgrade_1_1_0',
			'1.2.0' => 'upgrade_1_2_0',
			'1.3.0' => 'upgrade_1_3_0',
		);
	}

	/**
	 * Set the database version.
	 *
	 * @since    1.0.0
	 */
	private function set_db_version() {
		update_option( $this->db_version_option, $this->current_db_version );
	}

	/**
	 * Upgrade to version 1.1.0 - Fix usage table structure
	 *
	 * @since    1.1.0
	 */
	private function upgrade_1_1_0() {
		global $wpdb;

		// Recreate the usage table with the correct structure
		$table_name = $wpdb->prefix . 'layoutberg_usage';
		$charset_collate = $wpdb->get_charset_collate();
		
		// Drop the old table if it exists
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
		
		// Create the new table with the correct structure
		$sql = "CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			date date NOT NULL,
			generations_count int UNSIGNED DEFAULT 0,
			tokens_used int UNSIGNED DEFAULT 0,
			cost decimal(10,6) DEFAULT 0.000000,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_date (user_id, date),
			KEY user_id (user_id),
			KEY date (date)
		) $charset_collate;";
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Upgrade to version 1.2.0 - Fix templates table schema
	 *
	 * @since    1.2.0
	 */
	private function upgrade_1_2_0() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'layoutberg_templates';
		
		// Check if user_id column exists and created_by doesn't
		if ( $this->column_exists( 'layoutberg_templates', 'user_id' ) && 
		     ! $this->column_exists( 'layoutberg_templates', 'created_by' ) ) {
			
			// Rename user_id to created_by
			$wpdb->query( "ALTER TABLE $table_name CHANGE COLUMN user_id created_by bigint(20) UNSIGNED NOT NULL" );
		}
		
		// Add tags column if it doesn't exist
		if ( ! $this->column_exists( 'layoutberg_templates', 'tags' ) ) {
			$wpdb->query( "ALTER TABLE $table_name ADD COLUMN tags text NULL AFTER category" );
		}
		
		// Add metadata column if it doesn't exist
		if ( ! $this->column_exists( 'layoutberg_templates', 'metadata' ) ) {
			$wpdb->query( "ALTER TABLE $table_name ADD COLUMN metadata longtext NULL AFTER thumbnail_url" );
		}
	}

	/**
	 * Upgrade to version 1.3.0.
	 * 
	 * Adds debug logs table for agency users.
	 *
	 * @since    1.3.0
	 */
	private function upgrade_1_3_0() {
		global $wpdb;

		// Create debug logs table if it doesn't exist
		if ( ! $this->table_exists( 'layoutberg_debug_logs' ) ) {
			$charset_collate = $wpdb->get_charset_collate();
			$table_name = $wpdb->prefix . 'layoutberg_debug_logs';
			
			$sql = "CREATE TABLE $table_name (
				id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id bigint(20) UNSIGNED NOT NULL,
				log_type varchar(50) NOT NULL DEFAULT 'api_request',
				log_level varchar(20) NOT NULL DEFAULT 'info',
				provider varchar(50) NULL,
				model varchar(100) NULL,
				request_data longtext NULL,
				response_data longtext NULL,
				error_message text NULL,
				tokens_used int UNSIGNED DEFAULT 0,
				processing_time float DEFAULT 0,
				metadata longtext NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY user_id (user_id),
				KEY log_type (log_type),
				KEY log_level (log_level),
				KEY created_at (created_at)
			) $charset_collate;";
			
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/**
	 * Check if a table exists.
	 *
	 * @since    1.0.0
	 * @param    string $table_name The table name without prefix.
	 * @return   bool True if table exists.
	 */
	public function table_exists( $table_name ) {
		global $wpdb;
		
		$full_table_name = $wpdb->prefix . $table_name;
		
		return $wpdb->get_var( 
			$wpdb->prepare(
				"SHOW TABLES LIKE %s",
				$full_table_name
			)
		) === $full_table_name;
	}

	/**
	 * Check if a column exists in a table.
	 *
	 * @since    1.0.0
	 * @param    string $table_name The table name without prefix.
	 * @param    string $column_name The column name.
	 * @return   bool True if column exists.
	 */
	public function column_exists( $table_name, $column_name ) {
		global $wpdb;
		
		$full_table_name = $wpdb->prefix . $table_name;
		
		$column = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
				WHERE TABLE_SCHEMA = %s 
				AND TABLE_NAME = %s 
				AND COLUMN_NAME = %s",
				DB_NAME,
				$full_table_name,
				$column_name
			)
		);

		return ! empty( $column );
	}

	/**
	 * Create or update database tables.
	 *
	 * This is called during activation and upgrades.
	 *
	 * @since    1.0.0
	 */
	public function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Generations table
		$table_name = $wpdb->prefix . 'layoutberg_generations';
		$sql = "CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			prompt text NOT NULL,
			response longtext NOT NULL,
			model varchar(50) NOT NULL,
			tokens_used int UNSIGNED DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'completed',
			error_message text NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY status (status),
			KEY created_at (created_at)
		) $charset_collate;";

		// Templates table
		$table_name = $wpdb->prefix . 'layoutberg_templates';
		$sql .= "CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			created_by bigint(20) UNSIGNED NOT NULL,
			name varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			description text NULL,
			category varchar(100) NOT NULL DEFAULT 'custom',
			tags text NULL,
			content longtext NOT NULL,
			prompt text NULL,
			thumbnail_url varchar(500) NULL,
			metadata longtext NULL,
			is_public tinyint(1) NOT NULL DEFAULT 0,
			usage_count int UNSIGNED NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug),
			KEY created_by (created_by),
			KEY category (category),
			KEY is_public (is_public)
		) $charset_collate;";

		// Settings table
		$table_name = $wpdb->prefix . 'layoutberg_settings';
		$sql .= "CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			setting_name varchar(255) NOT NULL,
			setting_value longtext NULL,
			autoload varchar(20) NOT NULL DEFAULT 'yes',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY setting_name (setting_name),
			KEY autoload (autoload)
		) $charset_collate;";

		// Usage tracking table
		$table_name = $wpdb->prefix . 'layoutberg_usage';
		$sql .= "CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			date date NOT NULL,
			generations_count int UNSIGNED DEFAULT 0,
			tokens_used int UNSIGNED DEFAULT 0,
			cost decimal(10,6) DEFAULT 0.000000,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_date (user_id, date),
			KEY user_id (user_id),
			KEY date (date)
		) $charset_collate;";

		// Debug logs table
		$table_name = $wpdb->prefix . 'layoutberg_debug_logs';
		$sql .= "CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			log_type varchar(50) NOT NULL DEFAULT 'api_request',
			log_level varchar(20) NOT NULL DEFAULT 'info',
			provider varchar(50) NULL,
			model varchar(100) NULL,
			request_data longtext NULL,
			response_data longtext NULL,
			error_message text NULL,
			tokens_used int UNSIGNED DEFAULT 0,
			processing_time float DEFAULT 0,
			metadata longtext NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY log_type (log_type),
			KEY log_level (log_level),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Set the database version
		$this->set_db_version();
	}

	/**
	 * Drop all plugin tables.
	 *
	 * This is called during uninstall.
	 *
	 * @since    1.0.0
	 */
	public function drop_tables() {
		global $wpdb;

		$tables = array(
			'layoutberg_generations',
			'layoutberg_templates',
			'layoutberg_settings',
			'layoutberg_usage',
			'layoutberg_debug_logs',
		);

		foreach ( $tables as $table ) {
			$table_name = $wpdb->prefix . $table;
			$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
		}

		// Remove database version
		delete_option( $this->db_version_option );
	}

	/**
	 * Run a custom SQL query.
	 *
	 * @since    1.0.0
	 * @param    string $sql The SQL query.
	 * @return   mixed Query result.
	 */
	public function run_query( $sql ) {
		global $wpdb;
		return $wpdb->query( $sql );
	}

	/**
	 * Get the current database version.
	 *
	 * @since    1.0.0
	 * @return   string The current database version.
	 */
	public function get_current_version() {
		return $this->current_db_version;
	}

	/**
	 * Get the installed database version.
	 *
	 * @since    1.0.0
	 * @return   string The installed database version.
	 */
	public function get_installed_version() {
		return get_option( $this->db_version_option, '0' );
	}

	/**
	 * Check if upgrade is needed.
	 *
	 * @since    1.0.0
	 * @return   bool True if upgrade is needed.
	 */
	public function needs_upgrade() {
		$installed = $this->get_installed_version();
		return version_compare( $installed, $this->current_db_version, '<' );
	}
}