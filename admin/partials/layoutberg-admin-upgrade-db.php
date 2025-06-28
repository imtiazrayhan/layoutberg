<?php
/**
 * Force database upgrade
 *
 * @package    LayoutBerg
 * @subpackage Admin/Partials
 * @since      1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only allow admin users
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Unauthorized' );
}

// Load upgrade class
require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-upgrade.php';

// Force database upgrade
$upgrade = new \DotCamp\LayoutBerg\Upgrade();

// First, let's reset the DB version to force upgrade
delete_option( 'layoutberg_db_version' );

// Run the upgrade
$upgrade->create_tables();
$upgrade->run();

// Get the results
$db_version = get_option( 'layoutberg_db_version', 'not set' );
$message = 'Database upgrade completed. Current version: ' . $db_version;

// Check if usage table was created correctly
global $wpdb;
$table_usage = $wpdb->prefix . 'layoutberg_usage';
$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_usage'" );

if ( $table_exists ) {
	$columns = $wpdb->get_results( "SHOW COLUMNS FROM $table_usage" );
	$message .= '<br><br>Usage table columns:<br>';
	foreach ( $columns as $column ) {
		$message .= '- ' . $column->Field . ' (' . $column->Type . ')<br>';
	}
}

?>
<div class="wrap">
	<h1>LayoutBerg Database Upgrade</h1>
	<div class="notice notice-success">
		<p><?php echo $message; ?></p>
	</div>
	<p><a href="<?php echo admin_url( 'admin.php?page=layoutberg' ); ?>" class="button button-primary">Back to Dashboard</a></p>
</div>