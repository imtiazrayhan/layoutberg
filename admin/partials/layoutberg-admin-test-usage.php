<?php
/**
 * Test usage tracking
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
$today = current_time( 'Y-m-d' );
$message = '';

// If reset button clicked
if ( isset( $_POST['reset_data'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'reset_data' ) ) {
	// Delete all data for current user
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM %i WHERE user_id = %d",
			$table_usage,
			$user_id
		)
	);
	
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM %i WHERE user_id = %d",
			$table_generations,
			$user_id
		)
	);
	
	$message = 'All usage data has been reset successfully!';
}

// If test button clicked
if ( isset( $_POST['test_tracking'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'test_tracking' ) ) {
	// Insert test generation record
	$wpdb->insert(
		$table_generations,
		array(
			'user_id'     => $user_id,
			'prompt'      => 'Test prompt',
			'response'    => '<!-- wp:paragraph --><p>Test response</p><!-- /wp:paragraph -->',
			'model'       => 'gpt-3.5-turbo',
			'tokens_used' => 100,
			'status'      => 'completed',
		),
		array( '%d', '%s', '%s', '%s', '%d', '%s' )
	);
	$generation_id = $wpdb->insert_id;
	
	// Update daily usage
	$updated = $wpdb->query(
		$wpdb->prepare(
			"UPDATE %i 
			SET generations_count = generations_count + 1, 
			    tokens_used = tokens_used + 100,
			    cost = cost + 0.002
			WHERE user_id = %d AND date = %s",
			$table_usage,
			$user_id,
			$today
		)
	);
	
	if ( 0 === $updated ) {
		$wpdb->insert(
			$table_usage,
			array(
				'user_id'           => $user_id,
				'date'              => $today,
				'generations_count' => 1,
				'tokens_used'       => 100,
				'cost'              => 0.002,
			),
			array( '%d', '%s', '%d', '%d', '%f' )
		);
	}
	
	$message = 'Test generation added successfully! Generation ID: ' . $generation_id;
}

// Get current data
$today_usage = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT * FROM %i WHERE user_id = %d AND date = %s",
		$table_usage,
		$user_id,
		$today
	)
);

$this_month = current_time( 'Y-m' );
$first_day_of_month = $this_month . '-01';
$last_day_of_month = date( 'Y-m-t', strtotime( $first_day_of_month ) );
$month_usage = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT SUM(generations_count) as total_generations, SUM(tokens_used) as total_tokens, SUM(cost) as total_cost
		FROM %i 
		WHERE user_id = %d AND date >= %s AND date <= %s",
		$table_usage,
		$user_id,
		$first_day_of_month,
		$last_day_of_month
	)
);

// Get all usage records
$all_usage = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM %i WHERE user_id = %d ORDER BY date DESC LIMIT 30",
		$table_usage,
		$user_id
	)
);

// Check table structure
$columns = $wpdb->get_results( "SHOW COLUMNS FROM $table_usage" );
?>

<div class="wrap">
	<h1>LayoutBerg Usage Tracking Test</h1>
	
	<?php if ( $message ) : ?>
		<div class="notice notice-success">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	<?php endif; ?>
	
	<div class="card">
		<h2>Table Structure</h2>
		<table class="widefat">
			<thead>
				<tr>
					<th>Column</th>
					<th>Type</th>
					<th>Key</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $columns as $column ) : ?>
					<tr>
						<td><?php echo esc_html( $column->Field ); ?></td>
						<td><?php echo esc_html( $column->Type ); ?></td>
						<td><?php echo esc_html( $column->Key ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	
	<div class="card">
		<h2>Current Stats</h2>
		<p><strong>Today:</strong> <?php echo $today_usage ? $today_usage->generations_count : 0; ?> generations</p>
		<p><strong>This Month (<?php echo esc_html( $this_month ); ?>):</strong> <?php echo $month_usage ? $month_usage->total_generations : 0; ?> generations</p>
	</div>
	
	<div class="card">
		<h2>Add Test Generation</h2>
		<form method="post">
			<?php wp_nonce_field( 'test_tracking' ); ?>
			<p>
				<input type="submit" name="test_tracking" class="button button-primary" value="Add Test Generation">
			</p>
		</form>
	</div>
	
	<div class="card" style="margin-top: 20px; border-color: #d63638;">
		<h2 style="color: #d63638;">Reset Data</h2>
		<p><strong>Warning:</strong> This will delete all usage data and generation history for your user account.</p>
		<form method="post" onsubmit="return confirm('Are you sure you want to reset all usage data? This cannot be undone.');">
			<?php wp_nonce_field( 'reset_data' ); ?>
			<p>
				<input type="submit" name="reset_data" class="button button-secondary" value="Reset All Data" style="color: #d63638; border-color: #d63638;">
			</p>
		</form>
	</div>
	
	<div class="card">
		<h2>Usage Records</h2>
		<table class="widefat">
			<thead>
				<tr>
					<th>Date</th>
					<th>Generations</th>
					<th>Tokens</th>
					<th>Cost</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $all_usage ) : ?>
					<?php foreach ( $all_usage as $usage ) : ?>
						<tr>
							<td><?php echo esc_html( $usage->date ); ?></td>
							<td><?php echo esc_html( $usage->generations_count ); ?></td>
							<td><?php echo esc_html( $usage->tokens_used ); ?></td>
							<td>$<?php echo esc_html( number_format( $usage->cost, 4 ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="4">No usage records found</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	
	<p><a href="<?php echo admin_url( 'admin.php?page=layoutberg' ); ?>" class="button">Back to Dashboard</a></p>
</div>