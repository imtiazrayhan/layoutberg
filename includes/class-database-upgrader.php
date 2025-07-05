<?php
/**
 * Database upgrader class for LayoutBerg.
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
 * Database upgrader class.
 *
 * @since 1.0.0
 */
class Database_Upgrader {

	/**
	 * Current database version
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private static $db_version = '1.1.0';

	/**
	 * Run database upgrade
	 *
	 * @since 1.0.0
	 */
	public static function upgrade() {
		global $wpdb;

		$current_version = get_option( 'layoutberg_db_version', '1.0.0' );

		if ( version_compare( $current_version, '1.1.0', '<' ) ) {
			self::upgrade_to_1_1_0();
		}

		update_option( 'layoutberg_db_version', self::$db_version );
	}

	/**
	 * Upgrade to version 1.1.0
	 *
	 * @since 1.0.0
	 */
	private static function upgrade_to_1_1_0() {
		global $wpdb;

		// Add indexes to generations table
		$table_generations = $wpdb->prefix . 'layoutberg_generations';
		
		// Check if indexes already exist to avoid errors
		$indexes = $wpdb->get_results( "SHOW INDEX FROM {$table_generations}" );
		$existing_indexes = array();
		foreach ( $indexes as $index ) {
			$existing_indexes[] = $index->Key_name;
		}

		$queries = array();

		if ( ! in_array( 'idx_user_status', $existing_indexes ) ) {
			$queries[] = "ALTER TABLE {$table_generations} ADD INDEX idx_user_status (user_id, status)";
		}
		if ( ! in_array( 'idx_created_at', $existing_indexes ) ) {
			$queries[] = "ALTER TABLE {$table_generations} ADD INDEX idx_created_at (created_at)";
		}
		if ( ! in_array( 'idx_status', $existing_indexes ) ) {
			$queries[] = "ALTER TABLE {$table_generations} ADD INDEX idx_status (status)";
		}

		// Execute queries
		foreach ( $queries as $query ) {
			$wpdb->query( $query );
		}

		// Add indexes to usage table
		$table_usage = $wpdb->prefix . 'layoutberg_usage';
		
		$indexes = $wpdb->get_results( "SHOW INDEX FROM {$table_usage}" );
		$existing_indexes = array();
		foreach ( $indexes as $index ) {
			$existing_indexes[] = $index->Key_name;
		}

		$queries = array();

		if ( ! in_array( 'idx_user_date', $existing_indexes ) ) {
			$queries[] = "ALTER TABLE {$table_usage} ADD INDEX idx_user_date (user_id, date)";
		}
		if ( ! in_array( 'idx_model', $existing_indexes ) ) {
			$queries[] = "ALTER TABLE {$table_usage} ADD INDEX idx_model (model)";
		}

		// Execute queries
		foreach ( $queries as $query ) {
			$wpdb->query( $query );
		}

		// Add indexes to templates table
		$table_templates = $wpdb->prefix . 'layoutberg_templates';
		
		$indexes = $wpdb->get_results( "SHOW INDEX FROM {$table_templates}" );
		$existing_indexes = array();
		foreach ( $indexes as $index ) {
			$existing_indexes[] = $index->Key_name;
		}

		$queries = array();

		if ( ! in_array( 'idx_user_id', $existing_indexes ) ) {
			$queries[] = "ALTER TABLE {$table_templates} ADD INDEX idx_user_id (user_id)";
		}
		if ( ! in_array( 'idx_category', $existing_indexes ) ) {
			$queries[] = "ALTER TABLE {$table_templates} ADD INDEX idx_category (category)";
		}
		if ( ! in_array( 'idx_created_at', $existing_indexes ) ) {
			$queries[] = "ALTER TABLE {$table_templates} ADD INDEX idx_created_at (created_at)";
		}
		if ( ! in_array( 'idx_user_category', $existing_indexes ) ) {
			$queries[] = "ALTER TABLE {$table_templates} ADD INDEX idx_user_category (user_id, category)";
		}

		// Execute queries
		foreach ( $queries as $query ) {
			$wpdb->query( $query );
		}

		// Log the upgrade
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'LayoutBerg: Database upgraded to version 1.1.0' );
		}
	}

	/**
	 * Get current database version
	 *
	 * @since 1.0.0
	 * @return string Current database version.
	 */
	public static function get_current_version() {
		return get_option( 'layoutberg_db_version', '1.0.0' );
	}

	/**
	 * Check if upgrade is needed
	 *
	 * @since 1.0.0
	 * @return bool True if upgrade is needed.
	 */
	public static function needs_upgrade() {
		$current_version = self::get_current_version();
		return version_compare( $current_version, self::$db_version, '<' );
	}

	/**
	 * Get database tables
	 *
	 * @since 1.0.0
	 * @return array Array of table names.
	 */
	public static function get_tables() {
		global $wpdb;

		return array(
			'generations' => $wpdb->prefix . 'layoutberg_generations',
			'usage' => $wpdb->prefix . 'layoutberg_usage',
			'templates' => $wpdb->prefix . 'layoutberg_templates',
		);
	}

	/**
	 * Check if tables exist
	 *
	 * @since 1.0.0
	 * @return bool True if all tables exist.
	 */
	public static function tables_exist() {
		global $wpdb;

		$tables = self::get_tables();
		
		foreach ( $tables as $table ) {
			$result = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
			if ( $result !== $table ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get table indexes
	 *
	 * @since 1.0.0
	 * @param string $table_name Table name.
	 * @return array Array of index names.
	 */
	public static function get_table_indexes( $table_name ) {
		global $wpdb;

		$indexes = $wpdb->get_results( "SHOW INDEX FROM {$table_name}" );
		$index_names = array();
		
		foreach ( $indexes as $index ) {
			$index_names[] = $index->Key_name;
		}

		return array_unique( $index_names );
	}
} 