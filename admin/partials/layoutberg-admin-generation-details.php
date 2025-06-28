<?php
/**
 * Generation details page
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
$table_generations = $wpdb->prefix . 'layoutberg_generations';

// Get generation ID from query string
$generation_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

// Get generation details
$generation = null;
if ( $generation_id ) {
	$generation = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_generations WHERE id = %d AND user_id = %d",
			$generation_id,
			$user_id
		)
	);
}

// Get recent generations for navigation
$recent_generations = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT id, prompt, model, tokens_used, created_at 
		FROM $table_generations 
		WHERE user_id = %d 
		ORDER BY created_at DESC 
		LIMIT 20",
		$user_id
	)
);
?>

<div class="wrap">
	<h1>Generation Details</h1>
	
	<div style="display: flex; gap: 20px;">
		<!-- Main Content -->
		<div style="flex: 1;">
			<?php if ( $generation ) : ?>
				<div class="card">
					<h2>Generation #<?php echo esc_html( $generation->id ); ?></h2>
					
					<table class="widefat" style="margin-bottom: 20px;">
						<tbody>
							<tr>
								<th style="width: 150px;">Created</th>
								<td><?php echo esc_html( $generation->created_at ); ?> (<?php echo human_time_diff( strtotime( $generation->created_at ) ); ?> ago)</td>
							</tr>
							<tr>
								<th>Model</th>
								<td><code><?php echo esc_html( $generation->model ); ?></code></td>
							</tr>
							<tr>
								<th>Status</th>
								<td>
									<span style="color: <?php echo $generation->status === 'completed' ? '#00a32a' : '#d63638'; ?>">
										<?php echo esc_html( ucfirst( $generation->status ) ); ?>
									</span>
								</td>
							</tr>
							<tr>
								<th>Tokens Used</th>
								<td>
									<strong><?php echo number_format( $generation->tokens_used ); ?></strong> tokens
									<?php if ( $generation->tokens_used > 0 ) : ?>
										<br>
										<small>
											Cost: $<?php 
											$costs = array(
												'gpt-3.5-turbo' => 0.002,
												'gpt-4'         => 0.03,
												'gpt-4-turbo'   => 0.01,
											);
											$cost_per_1k = isset( $costs[ $generation->model ] ) ? $costs[ $generation->model ] : 0.002;
											echo number_format( ( $generation->tokens_used / 1000 ) * $cost_per_1k, 4 );
											?>
										</small>
									<?php endif; ?>
								</td>
							</tr>
						</tbody>
					</table>
					
					<h3>Prompt</h3>
					<div style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
						<pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;"><?php echo esc_html( $generation->prompt ); ?></pre>
					</div>
					
					<?php if ( $generation->error_message ) : ?>
						<h3>Error</h3>
						<div style="background: #ffebe8; border: 1px solid #d63638; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
							<pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;"><?php echo esc_html( $generation->error_message ); ?></pre>
						</div>
					<?php endif; ?>
					
					<h3>Generated Response</h3>
					<div style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin-bottom: 20px; max-height: 400px; overflow-y: auto;">
						<pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;"><?php echo esc_html( $generation->response ); ?></pre>
					</div>
					
					<h3>Token Analysis</h3>
					<div style="background: #f6f7f7; padding: 15px; border-radius: 4px;">
						<p><strong>Total Tokens:</strong> <?php echo number_format( $generation->tokens_used ); ?></p>
						<p><strong>Estimated Breakdown:</strong></p>
						<ul>
							<li>System Prompt: ~<?php echo number_format( strlen( $generation->prompt ) / 4 ); ?> tokens (estimated)</li>
							<li>User Prompt: Included in system prompt</li>
							<li>Completion: ~<?php echo number_format( strlen( $generation->response ) / 4 ); ?> tokens (estimated)</li>
						</ul>
						<p><small>Note: Token counts are provided by OpenAI. The breakdown is estimated based on character count (1 token ≈ 4 characters).</small></p>
					</div>
				</div>
			<?php else : ?>
				<div class="card">
					<h2>No Generation Selected</h2>
					<p>Select a generation from the list on the right to view details.</p>
				</div>
			<?php endif; ?>
		</div>
		
		<!-- Sidebar -->
		<div style="width: 300px;">
			<div class="card">
				<h3>Recent Generations</h3>
				<div style="max-height: 600px; overflow-y: auto;">
					<?php foreach ( $recent_generations as $gen ) : ?>
						<div style="padding: 10px; border-bottom: 1px solid #ddd;">
							<a href="?page=layoutberg-generation-details&id=<?php echo $gen->id; ?>" 
							   style="text-decoration: none; color: <?php echo $gen->id == $generation_id ? '#007cba' : 'inherit'; ?>">
								<strong>#<?php echo $gen->id; ?></strong> - 
								<?php echo esc_html( substr( $gen->prompt, 0, 30 ) ); ?>...
								<br>
								<small style="color: #666;">
									<?php echo $gen->model; ?> • 
									<?php echo number_format( $gen->tokens_used ); ?> tokens • 
									<?php echo human_time_diff( strtotime( $gen->created_at ) ); ?> ago
								</small>
							</a>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			
			<div style="margin-top: 20px;">
				<a href="<?php echo admin_url( 'admin.php?page=layoutberg-analytics' ); ?>" class="button">View Analytics</a>
				<a href="<?php echo admin_url( 'admin.php?page=layoutberg' ); ?>" class="button">Dashboard</a>
			</div>
		</div>
	</div>
</div>