<?php
/**
 * Reset database data
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

global $wpdb;
$user_id = get_current_user_id();
$table_usage = $wpdb->prefix . 'layoutberg_usage';
$table_generations = $wpdb->prefix . 'layoutberg_generations';
$table_templates = $wpdb->prefix . 'layoutberg_templates';
$message = '';
$error = '';

// Process reset actions
if ( isset( $_POST['action'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'reset_data' ) ) {
	switch ( $_POST['action'] ) {
		case 'reset_usage':
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $table_usage WHERE user_id = %d",
					$user_id
				)
			);
			$message = sprintf( 'Deleted %d usage records.', $deleted );
			break;
			
		case 'reset_generations':
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $table_generations WHERE user_id = %d",
					$user_id
				)
			);
			$message = sprintf( 'Deleted %d generation records.', $deleted );
			break;
			
		case 'reset_templates':
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $table_templates WHERE user_id = %d",
					$user_id
				)
			);
			$message = sprintf( 'Deleted %d template records.', $deleted );
			break;
			
		case 'reset_all':
			$usage_deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $table_usage WHERE user_id = %d",
					$user_id
				)
			);
			$gen_deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $table_generations WHERE user_id = %d",
					$user_id
				)
			);
			$template_deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $table_templates WHERE user_id = %d",
					$user_id
				)
			);
			$message = sprintf( 
				'Reset complete! Deleted: %d usage records, %d generation records, %d templates.', 
				$usage_deleted, 
				$gen_deleted, 
				$template_deleted 
			);
			break;
			
		case 'reset_all_users':
			// Only super admin can do this
			if ( ! is_super_admin() ) {
				$error = 'Only super administrators can reset data for all users.';
			} else {
				$usage_deleted = $wpdb->query( "TRUNCATE TABLE $table_usage" );
				$gen_deleted = $wpdb->query( "TRUNCATE TABLE $table_generations" );
				$template_deleted = $wpdb->query( "TRUNCATE TABLE $table_templates" );
				$message = 'All data for all users has been reset!';
			}
			break;
	}
}

// Get current counts
$usage_count = $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM $table_usage WHERE user_id = %d",
		$user_id
	)
);

$generation_count = $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM $table_generations WHERE user_id = %d",
		$user_id
	)
);

$template_count = $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM $table_templates WHERE user_id = %d",
		$user_id
	)
);

// Get total counts (all users)
$total_usage = $wpdb->get_var( "SELECT COUNT(*) FROM $table_usage" );
$total_generations = $wpdb->get_var( "SELECT COUNT(*) FROM $table_generations" );
$total_templates = $wpdb->get_var( "SELECT COUNT(*) FROM $table_templates" );
?>

<div class="wrap">
	<h1>LayoutBerg Data Reset</h1>
	
	<?php if ( $message ) : ?>
		<div class="notice notice-success">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	<?php endif; ?>
	
	<?php if ( $error ) : ?>
		<div class="notice notice-error">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php endif; ?>
	
	<div class="card">
		<h2>Current Data</h2>
		<table class="widefat">
			<thead>
				<tr>
					<th>Table</th>
					<th>Your Records</th>
					<th>Total Records</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><strong>Usage Statistics</strong></td>
					<td><?php echo number_format( $usage_count ); ?></td>
					<td><?php echo number_format( $total_usage ); ?></td>
				</tr>
				<tr>
					<td><strong>Generations</strong></td>
					<td><?php echo number_format( $generation_count ); ?></td>
					<td><?php echo number_format( $total_generations ); ?></td>
				</tr>
				<tr>
					<td><strong>Templates</strong></td>
					<td><?php echo number_format( $template_count ); ?></td>
					<td><?php echo number_format( $total_templates ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>
	
	<div class="card" style="margin-top: 20px;">
		<h2>Reset Options</h2>
		<p>Choose what data to reset. This will only affect your user account unless you're a super admin.</p>
		
		<form method="post" style="display: inline-block; margin-right: 10px;">
			<?php wp_nonce_field( 'reset_data' ); ?>
			<input type="hidden" name="action" value="reset_usage">
			<input type="submit" class="button button-secondary" value="Reset Usage Stats" 
				   onclick="return confirm('Reset all usage statistics?');">
		</form>
		
		<form method="post" style="display: inline-block; margin-right: 10px;">
			<?php wp_nonce_field( 'reset_data' ); ?>
			<input type="hidden" name="action" value="reset_generations">
			<input type="submit" class="button button-secondary" value="Reset Generations" 
				   onclick="return confirm('Reset all generation history?');">
		</form>
		
		<form method="post" style="display: inline-block; margin-right: 10px;">
			<?php wp_nonce_field( 'reset_data' ); ?>
			<input type="hidden" name="action" value="reset_templates">
			<input type="submit" class="button button-secondary" value="Reset Templates" 
				   onclick="return confirm('Reset all saved templates?');">
		</form>
		
		<form method="post" style="display: inline-block;">
			<?php wp_nonce_field( 'reset_data' ); ?>
			<input type="hidden" name="action" value="reset_all">
			<input type="submit" class="button button-primary" value="Reset All My Data" 
				   onclick="return confirm('This will reset ALL your LayoutBerg data. Are you sure?');" 
				   style="background: #d63638; border-color: #d63638;">
		</form>
	</div>
	
	<?php if ( is_super_admin() ) : ?>
	<div class="card" style="margin-top: 20px; border-color: #d63638;">
		<h2 style="color: #d63638;">Super Admin Options</h2>
		<p><strong>DANGER:</strong> This will reset data for ALL users on this site!</p>
		
		<form method="post">
			<?php wp_nonce_field( 'reset_data' ); ?>
			<input type="hidden" name="action" value="reset_all_users">
			<input type="submit" class="button button-secondary" value="Reset ALL Users Data" 
				   onclick="return confirm('This will DELETE all LayoutBerg data for ALL USERS! Are you absolutely sure?') && confirm('This action CANNOT be undone. Please confirm again.');" 
				   style="color: #fff; background: #d63638; border-color: #d63638;">
		</form>
	</div>
	<?php endif; ?>
	
	<div style="margin-top: 20px;">
		<a href="<?php echo admin_url( 'admin.php?page=layoutberg' ); ?>" class="button">Back to Dashboard</a>
		<a href="<?php echo admin_url( 'admin.php?page=layoutberg-test-usage' ); ?>" class="button">Test Usage Tracking</a>
	</div>
</div>