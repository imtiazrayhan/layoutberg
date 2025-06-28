<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package LayoutBerg
 * @since   1.0.0
 */

// Exit if uninstall not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check user capabilities.
if ( ! current_user_can( 'activate_plugins' ) ) {
	return;
}

// Get plugin options.
$options_to_delete = array(
	'layoutberg_version',
	'layoutberg_options',
	'layoutberg_api_key',
	'layoutberg_db_version',
);

// Delete options.
foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}

// Delete transients.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_layoutberg_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_layoutberg_%'" );

// Drop custom tables.
$table_names = array(
	$wpdb->prefix . 'layoutberg_settings',
	$wpdb->prefix . 'layoutberg_generations',
	$wpdb->prefix . 'layoutberg_templates',
	$wpdb->prefix . 'layoutberg_usage',
);

foreach ( $table_names as $table_name ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
}

// Delete user meta.
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'layoutberg_%'" );

// Clear scheduled hooks.
$hooks_to_clear = array(
	'layoutberg_daily_cleanup',
	'layoutberg_usage_reset',
);

foreach ( $hooks_to_clear as $hook ) {
	wp_clear_scheduled_hook( $hook );
}

// Remove capabilities.
$capabilities = array(
	'layoutberg_generate',
	'layoutberg_manage_templates',
	'layoutberg_view_analytics',
	'layoutberg_configure',
);

$roles = wp_roles()->get_names();
foreach ( array_keys( $roles ) as $role_name ) {
	$role = get_role( $role_name );
	if ( $role ) {
		foreach ( $capabilities as $cap ) {
			$role->remove_cap( $cap );
		}
	}
}

// Clean up any cached data.
wp_cache_flush();