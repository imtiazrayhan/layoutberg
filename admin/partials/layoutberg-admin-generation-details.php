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
		"SELECT id, prompt, model, tokens_used, created_at, status
		FROM $table_generations 
		WHERE user_id = %d 
		ORDER BY created_at DESC 
		LIMIT 20",
		$user_id
	)
);

// Calculate costs
$costs = array(
	'gpt-3.5-turbo' => 0.002,
	'gpt-4'         => 0.03,
	'gpt-4-turbo'   => 0.01,
);
$cost_per_1k = isset( $costs[ $generation->model ] ) ? $costs[ $generation->model ] : 0.002;
$total_cost = $generation ? ( $generation->tokens_used / 1000 ) * $cost_per_1k : 0;
?>

<div class="layoutberg-admin-page">
	<!-- Header -->
	<div class="layoutberg-header">
		<div class="layoutberg-header-content">
			<div class="layoutberg-title">
				<div class="layoutberg-logo">LB</div>
				<div>
					<h1><?php esc_html_e( 'Generation Details', 'layoutberg' ); ?></h1>
					<p>
						<?php if ( $generation ) : ?>
							<?php 
							/* translators: %s: Generation ID */
							printf( esc_html__( 'Viewing generation #%s', 'layoutberg' ), $generation_id ); 
							?>
						<?php else : ?>
							<?php esc_html_e( 'View detailed information about your AI generations', 'layoutberg' ); ?>
						<?php endif; ?>
					</p>
				</div>
			</div>
			<div class="layoutberg-header-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=layoutberg-analytics' ) ); ?>" class="layoutberg-btn layoutberg-btn-secondary">
					<span class="dashicons dashicons-chart-bar"></span>
					<?php esc_html_e( 'Analytics', 'layoutberg' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=layoutberg' ) ); ?>" class="layoutberg-btn layoutberg-btn-secondary">
					<span class="dashicons dashicons-arrow-left-alt"></span>
					<?php esc_html_e( 'Dashboard', 'layoutberg' ); ?>
				</a>
			</div>
		</div>
	</div>

	<!-- Main Content -->
	<div class="layoutberg-container">
		<div class="layoutberg-grid" style="grid-template-columns: 1fr 300px; gap: 1.5rem;">
			<!-- Main Content Area -->
			<div>
				<?php if ( $generation ) : ?>
					<!-- Generation Overview -->
					<div class="layoutberg-card layoutberg-fade-in layoutberg-mb-4">
						<div class="layoutberg-card-header">
							<h3 class="layoutberg-card-title"><?php esc_html_e( 'Generation Overview', 'layoutberg' ); ?></h3>
							<span class="layoutberg-badge layoutberg-badge-<?php echo esc_attr( $generation->status === 'completed' ? 'success' : 'danger' ); ?>">
								<?php echo esc_html( ucfirst( $generation->status ) ); ?>
							</span>
						</div>
						
						<div class="layoutberg-grid layoutberg-grid-4">
							<div>
								<p class="layoutberg-label"><?php esc_html_e( 'Created', 'layoutberg' ); ?></p>
								<p class="layoutberg-value">
									<?php echo esc_html( date( 'M j, Y g:i A', strtotime( $generation->created_at ) ) ); ?>
									<br>
									<small class="layoutberg-text-muted">
										<?php 
										/* translators: %s: Time ago */
										printf( esc_html__( '%s ago', 'layoutberg' ), human_time_diff( strtotime( $generation->created_at ) ) );
										?>
									</small>
								</p>
							</div>
							<div>
								<p class="layoutberg-label"><?php esc_html_e( 'Model', 'layoutberg' ); ?></p>
								<p class="layoutberg-value">
									<span class="layoutberg-badge layoutberg-badge-primary">
										<?php echo esc_html( $generation->model ); ?>
									</span>
								</p>
							</div>
							<div>
								<p class="layoutberg-label"><?php esc_html_e( 'Tokens Used', 'layoutberg' ); ?></p>
								<p class="layoutberg-value">
									<strong><?php echo esc_html( number_format( $generation->tokens_used ) ); ?></strong>
									<br>
									<small class="layoutberg-text-muted">
										<?php esc_html_e( 'Total tokens', 'layoutberg' ); ?>
									</small>
								</p>
							</div>
							<div>
								<p class="layoutberg-label"><?php esc_html_e( 'Cost', 'layoutberg' ); ?></p>
								<p class="layoutberg-value">
									<strong>$<?php echo esc_html( number_format( $total_cost, 4 ) ); ?></strong>
									<br>
									<small class="layoutberg-text-muted">
										$<?php echo esc_html( number_format( $cost_per_1k, 3 ) ); ?>/1k tokens
									</small>
								</p>
							</div>
						</div>
					</div>
					<!-- Prompt -->
					<div class="layoutberg-card layoutberg-fade-in layoutberg-mb-4">
						<div class="layoutberg-card-header">
							<h3 class="layoutberg-card-title"><?php esc_html_e( 'User Prompt', 'layoutberg' ); ?></h3>
						</div>
						<div class="layoutberg-code-block">
							<pre><?php echo esc_html( $generation->prompt ); ?></pre>
						</div>
					</div>
					
					<?php if ( $generation->error_message ) : ?>
						<!-- Error Message -->
						<div class="layoutberg-card layoutberg-fade-in layoutberg-mb-4" style="border-color: var(--lberg-danger);">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title" style="color: var(--lberg-danger);">
									<span class="dashicons dashicons-warning"></span>
									<?php esc_html_e( 'Error Details', 'layoutberg' ); ?>
								</h3>
							</div>
							<div class="layoutberg-code-block" style="background: #ffebe8;">
								<pre style="color: var(--lberg-danger);"><?php echo esc_html( $generation->error_message ); ?></pre>
							</div>
						</div>
					<?php endif; ?>
					
					<!-- Generated Response -->
					<div class="layoutberg-card layoutberg-fade-in layoutberg-mb-4">
						<div class="layoutberg-card-header">
							<h3 class="layoutberg-card-title"><?php esc_html_e( 'Generated Response', 'layoutberg' ); ?></h3>
							<span class="layoutberg-text-muted"><?php echo esc_html( sprintf( __( '%d characters', 'layoutberg' ), strlen( $generation->response ) ) ); ?></span>
						</div>
						<div class="layoutberg-code-block" style="max-height: 400px; overflow-y: auto;">
							<pre><?php echo esc_html( $generation->response ); ?></pre>
						</div>
					</div>
					
					<!-- Token Analysis -->
					<div class="layoutberg-card layoutberg-fade-in">
						<div class="layoutberg-card-header">
							<h3 class="layoutberg-card-title"><?php esc_html_e( 'Token Analysis', 'layoutberg' ); ?></h3>
						</div>
						
						<div class="layoutberg-stats-grid">
							<div class="layoutberg-stat-item">
								<span class="layoutberg-stat-icon">
									<span class="dashicons dashicons-editor-code"></span>
								</span>
								<div>
									<p class="layoutberg-stat-value"><?php echo esc_html( number_format( $generation->tokens_used ) ); ?></p>
									<p class="layoutberg-stat-label"><?php esc_html_e( 'Total Tokens', 'layoutberg' ); ?></p>
								</div>
							</div>
							
							<div class="layoutberg-stat-item">
								<span class="layoutberg-stat-icon">
									<span class="dashicons dashicons-text"></span>
								</span>
								<div>
									<p class="layoutberg-stat-value">~<?php echo esc_html( number_format( strlen( $generation->prompt ) / 4 ) ); ?></p>
									<p class="layoutberg-stat-label"><?php esc_html_e( 'Prompt Tokens (est)', 'layoutberg' ); ?></p>
								</div>
							</div>
							
							<div class="layoutberg-stat-item">
								<span class="layoutberg-stat-icon">
									<span class="dashicons dashicons-media-text"></span>
								</span>
								<div>
									<p class="layoutberg-stat-value">~<?php echo esc_html( number_format( strlen( $generation->response ) / 4 ) ); ?></p>
									<p class="layoutberg-stat-label"><?php esc_html_e( 'Response Tokens (est)', 'layoutberg' ); ?></p>
								</div>
							</div>
						</div>
						
						<div class="layoutberg-alert layoutberg-alert-info layoutberg-mt-3">
							<span class="dashicons dashicons-info"></span>
							<div>
								<p><?php esc_html_e( 'Token counts are provided by the OpenAI API. The prompt/response breakdown is estimated based on character count (1 token ≈ 4 characters). Actual token usage includes system prompts and formatting.', 'layoutberg' ); ?></p>
							</div>
						</div>
					</div>
				<?php else : ?>
					<div class="layoutberg-card layoutberg-fade-in">
						<div class="layoutberg-empty-state">
							<div class="layoutberg-empty-icon">
								<span class="dashicons dashicons-search"></span>
							</div>
							<h4 class="layoutberg-empty-title"><?php esc_html_e( 'No Generation Selected', 'layoutberg' ); ?></h4>
							<p class="layoutberg-empty-description"><?php esc_html_e( 'Select a generation from the list on the right to view details.', 'layoutberg' ); ?></p>
						</div>
					</div>
				<?php endif; ?>
			</div>
			
			<!-- Sidebar -->
			<div>
				<div class="layoutberg-card layoutberg-fade-in">
					<div class="layoutberg-card-header">
						<h3 class="layoutberg-card-title"><?php esc_html_e( 'Recent Generations', 'layoutberg' ); ?></h3>
					</div>
					<div class="layoutberg-generation-list">
						<?php foreach ( $recent_generations as $gen ) : ?>
							<a href="?page=layoutberg-generation-details&id=<?php echo $gen->id; ?>" 
							   class="layoutberg-generation-item <?php echo $gen->id == $generation_id ? 'active' : ''; ?>">
								<div class="layoutberg-flex layoutberg-items-start layoutberg-gap-2">
									<span class="layoutberg-generation-id">#<?php echo $gen->id; ?></span>
									<div style="flex: 1;">
										<p class="layoutberg-generation-prompt">
											<?php echo esc_html( substr( $gen->prompt, 0, 50 ) ); ?>...
										</p>
										<div class="layoutberg-generation-meta">
											<span class="layoutberg-badge layoutberg-badge-secondary layoutberg-badge-sm">
												<?php echo esc_html( $gen->model ); ?>
											</span>
											<span class="layoutberg-text-muted">•</span>
											<span class="layoutberg-text-muted"><?php echo esc_html( number_format( $gen->tokens_used ) ); ?> tokens</span>
											<span class="layoutberg-text-muted">•</span>
											<span class="layoutberg-text-muted"><?php echo human_time_diff( strtotime( $gen->created_at ) ); ?> ago</span>
										</div>
									</div>
									<span class="layoutberg-status-indicator layoutberg-status-<?php echo esc_attr( $gen->status ); ?>"></span>
								</div>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
/* Generation Details Specific Styles */
.layoutberg-label {
	margin: 0 0 0.25rem 0;
	font-size: 0.75rem;
	font-weight: 600;
	color: var(--lberg-gray-600);
	text-transform: uppercase;
	letter-spacing: 0.05em;
}

.layoutberg-value {
	margin: 0;
	font-size: 1rem;
	color: var(--lberg-gray-900);
}

.layoutberg-text-muted {
	color: var(--lberg-gray-500);
	font-size: 0.875rem;
}

.layoutberg-code-block {
	background: var(--lberg-gray-50);
	border: 1px solid var(--lberg-gray-200);
	border-radius: 4px;
	padding: 1rem;
	overflow-x: auto;
}

.layoutberg-code-block pre {
	margin: 0;
	white-space: pre-wrap;
	word-wrap: break-word;
	font-family: 'Monaco', 'Consolas', 'Courier New', monospace;
	font-size: 0.875rem;
	line-height: 1.5;
	color: var(--lberg-gray-800);
}

.layoutberg-stats-grid {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 1.5rem;
	margin-bottom: 1rem;
}

.layoutberg-stat-item {
	display: flex;
	align-items: center;
	gap: 1rem;
}

.layoutberg-stat-icon {
	width: 48px;
	height: 48px;
	background: var(--lberg-primary-light);
	border-radius: 8px;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
}

.layoutberg-stat-icon .dashicons {
	font-size: 24px;
	color: var(--lberg-primary);
}

.layoutberg-generation-list {
	max-height: 600px;
	overflow-y: auto;
}

.layoutberg-generation-item {
	display: block;
	padding: 1rem;
	border-bottom: 1px solid var(--lberg-gray-200);
	text-decoration: none;
	color: inherit;
	transition: background-color 0.15s ease;
}

.layoutberg-generation-item:hover {
	background: var(--lberg-gray-50);
}

.layoutberg-generation-item.active {
	background: var(--lberg-primary-light);
	border-left: 3px solid var(--lberg-primary);
	padding-left: calc(1rem - 3px);
}

.layoutberg-generation-item:last-child {
	border-bottom: none;
}

.layoutberg-generation-id {
	font-weight: 600;
	color: var(--lberg-gray-700);
	font-size: 0.875rem;
}

.layoutberg-generation-prompt {
	margin: 0 0 0.5rem 0;
	font-size: 0.875rem;
	line-height: 1.4;
	color: var(--lberg-gray-800);
}

.layoutberg-generation-meta {
	display: flex;
	align-items: center;
	gap: 0.5rem;
	font-size: 0.75rem;
}

.layoutberg-status-indicator {
	width: 8px;
	height: 8px;
	border-radius: 50%;
	flex-shrink: 0;
}

.layoutberg-status-indicator.layoutberg-status-completed {
	background: var(--lberg-success);
}

.layoutberg-status-indicator.layoutberg-status-failed {
	background: var(--lberg-danger);
}

.layoutberg-badge-sm {
	font-size: 0.625rem;
	padding: 0.125rem 0.375rem;
}

@media (max-width: 1200px) {
	.layoutberg-container > .layoutberg-grid {
		grid-template-columns: 1fr !important;
	}
	
	.layoutberg-stats-grid {
		grid-template-columns: 1fr;
	}
}
</style>