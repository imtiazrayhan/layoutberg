<?php
/**
 * Fired during plugin deactivation.
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
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since 1.0.0
 */
class Deactivator {

	/**
	 * Plugin deactivation handler.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		// Clear scheduled hooks.
		wp_clear_scheduled_hook( 'layoutberg_daily_cleanup' );
		wp_clear_scheduled_hook( 'layoutberg_usage_reset' );

		// Clear transients.
		self::clear_transients();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Clear all plugin transients.
	 *
	 * @since 1.0.0
	 */
	private static function clear_transients() {
		global $wpdb;

		// Delete all transients with our prefix.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_layoutberg_%' 
			OR option_name LIKE '_transient_timeout_layoutberg_%'"
		);

		// Clear object cache if available.
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
	}
}