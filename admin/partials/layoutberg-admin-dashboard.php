<?php
/**
 * Admin dashboard page.
 *
 * @package    LayoutBerg
 * @subpackage Admin/Partials
 * @since      1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get usage statistics.
global $wpdb;
$user_id = get_current_user_id();
$today   = current_time( 'Y-m-d' );

// Get today's usage.
$today_usage = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT generations_count, tokens_used 
		FROM {$wpdb->prefix}layoutberg_usage 
		WHERE user_id = %d AND date = %s",
		$user_id,
		$today
	)
);

// Get this month's usage.
$month_start = date( 'Y-m-01' );
$month_usage = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT SUM(generations_count) as generations, SUM(tokens_used) as tokens 
		FROM {$wpdb->prefix}layoutberg_usage 
		WHERE user_id = %d AND date >= %s",
		$user_id,
		$month_start
	)
);

// Get recent generations.
$recent_generations = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}layoutberg_generations 
		WHERE user_id = %d 
		ORDER BY created_at DESC 
		LIMIT 5",
		$user_id
	)
);

// Get popular templates.
$popular_templates = $wpdb->get_results(
	"SELECT * FROM {$wpdb->prefix}layoutberg_templates 
	ORDER BY usage_count DESC 
	LIMIT 5"
);

// Check API key status.
$options = get_option( 'layoutberg_options', array() );
$has_api_key = ! empty( $options['api_key'] );
?>

<div class="wrap layoutberg-dashboard">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( ! $has_api_key ) : ?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Welcome to LayoutBerg!', 'layoutberg' ); ?></strong>
				<?php 
				printf(
					/* translators: %s: Settings page URL */
					esc_html__( 'To start generating AI-powered layouts, please %s.', 'layoutberg' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=layoutberg-settings' ) ) . '">' . esc_html__( 'configure your OpenAI API key', 'layoutberg' ) . '</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<div class="layoutberg-dashboard-grid">
		<!-- Quick Actions -->
		<div class="layoutberg-card">
			<h2><?php esc_html_e( 'Quick Actions', 'layoutberg' ); ?></h2>
			<div class="layoutberg-quick-actions">
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>" class="button button-primary">
					<span class="dashicons dashicons-plus-alt"></span>
					<?php esc_html_e( 'Create New Page', 'layoutberg' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=layoutberg-templates' ) ); ?>" class="button">
					<span class="dashicons dashicons-layout"></span>
					<?php esc_html_e( 'Browse Templates', 'layoutberg' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=layoutberg-settings' ) ); ?>" class="button">
					<span class="dashicons dashicons-admin-settings"></span>
					<?php esc_html_e( 'Settings', 'layoutberg' ); ?>
				</a>
			</div>
		</div>

		<!-- Usage Statistics -->
		<div class="layoutberg-card">
			<h2><?php esc_html_e( 'Usage Statistics', 'layoutberg' ); ?></h2>
			<div class="layoutberg-stats">
				<div class="stat-item">
					<span class="stat-value"><?php echo esc_html( $today_usage->generations_count ?? 0 ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Generations Today', 'layoutberg' ); ?></span>
				</div>
				<div class="stat-item">
					<span class="stat-value"><?php echo esc_html( $month_usage->generations ?? 0 ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'This Month', 'layoutberg' ); ?></span>
				</div>
				<div class="stat-item">
					<span class="stat-value"><?php echo esc_html( number_format( $month_usage->tokens ?? 0 ) ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Tokens Used', 'layoutberg' ); ?></span>
				</div>
			</div>
		</div>

		<!-- Recent Generations -->
		<div class="layoutberg-card layoutberg-card-wide">
			<h2><?php esc_html_e( 'Recent Generations', 'layoutberg' ); ?></h2>
			<?php if ( ! empty( $recent_generations ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Prompt', 'layoutberg' ); ?></th>
							<th><?php esc_html_e( 'Model', 'layoutberg' ); ?></th>
							<th><?php esc_html_e( 'Tokens', 'layoutberg' ); ?></th>
							<th><?php esc_html_e( 'Status', 'layoutberg' ); ?></th>
							<th><?php esc_html_e( 'Date', 'layoutberg' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent_generations as $generation ) : ?>
							<tr>
								<td><?php echo esc_html( wp_trim_words( $generation->prompt, 10 ) ); ?></td>
								<td><?php echo esc_html( $generation->model ); ?></td>
								<td><?php echo esc_html( number_format( $generation->tokens_used ) ); ?></td>
								<td>
									<span class="status-<?php echo esc_attr( $generation->status ); ?>">
										<?php echo esc_html( ucfirst( $generation->status ) ); ?>
									</span>
								</td>
								<td><?php echo esc_html( human_time_diff( strtotime( $generation->created_at ) ) ); ?> ago</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p><?php esc_html_e( 'No generations yet. Start creating amazing layouts!', 'layoutberg' ); ?></p>
			<?php endif; ?>
		</div>

		<!-- Popular Templates -->
		<div class="layoutberg-card">
			<h2><?php esc_html_e( 'Popular Templates', 'layoutberg' ); ?></h2>
			<?php if ( ! empty( $popular_templates ) ) : ?>
				<ul class="layoutberg-template-list">
					<?php foreach ( $popular_templates as $template ) : ?>
						<li>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=layoutberg-templates&action=view&id=' . $template->id ) ); ?>">
								<?php echo esc_html( $template->name ); ?>
							</a>
							<span class="usage-count"><?php echo esc_html( sprintf( __( '%d uses', 'layoutberg' ), $template->usage_count ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p><?php esc_html_e( 'No templates created yet.', 'layoutberg' ); ?></p>
			<?php endif; ?>
		</div>

		<!-- Getting Started Guide -->
		<div class="layoutberg-card">
			<h2><?php esc_html_e( 'Getting Started', 'layoutberg' ); ?></h2>
			<ol class="layoutberg-steps">
				<li>
					<strong><?php esc_html_e( 'Configure API Key', 'layoutberg' ); ?></strong>
					<p><?php esc_html_e( 'Add your OpenAI API key in settings.', 'layoutberg' ); ?></p>
				</li>
				<li>
					<strong><?php esc_html_e( 'Create or Edit a Page', 'layoutberg' ); ?></strong>
					<p><?php esc_html_e( 'Open the WordPress block editor.', 'layoutberg' ); ?></p>
				</li>
				<li>
					<strong><?php esc_html_e( 'Click LayoutBerg Button', 'layoutberg' ); ?></strong>
					<p><?php esc_html_e( 'Find it in the editor toolbar.', 'layoutberg' ); ?></p>
				</li>
				<li>
					<strong><?php esc_html_e( 'Enter Your Prompt', 'layoutberg' ); ?></strong>
					<p><?php esc_html_e( 'Describe the layout you want.', 'layoutberg' ); ?></p>
				</li>
				<li>
					<strong><?php esc_html_e( 'Generate & Apply', 'layoutberg' ); ?></strong>
					<p><?php esc_html_e( 'Review and apply the generated layout.', 'layoutberg' ); ?></p>
				</li>
			</ol>
		</div>

		<!-- Support -->
		<div class="layoutberg-card">
			<h2><?php esc_html_e( 'Need Help?', 'layoutberg' ); ?></h2>
			<ul class="layoutberg-links">
				<li>
					<a href="https://docs.dotcamp.com/layoutberg" target="_blank">
						<span class="dashicons dashicons-book"></span>
						<?php esc_html_e( 'Documentation', 'layoutberg' ); ?>
					</a>
				</li>
				<li>
					<a href="https://wordpress.org/support/plugin/layoutberg" target="_blank">
						<span class="dashicons dashicons-sos"></span>
						<?php esc_html_e( 'Support Forum', 'layoutberg' ); ?>
					</a>
				</li>
				<li>
					<a href="https://dotcamp.com/layoutberg/pro" target="_blank">
						<span class="dashicons dashicons-star-filled"></span>
						<?php esc_html_e( 'Upgrade to Pro', 'layoutberg' ); ?>
					</a>
				</li>
			</ul>
		</div>
	</div>
</div>

<style>
.layoutberg-dashboard-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.layoutberg-card {
	background: #fff;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
	padding: 20px;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.layoutberg-card-wide {
	grid-column: span 2;
}

.layoutberg-card h2 {
	margin-top: 0;
	margin-bottom: 15px;
	font-size: 18px;
}

.layoutberg-quick-actions {
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
}

.layoutberg-quick-actions .button {
	display: inline-flex;
	align-items: center;
	gap: 5px;
}

.layoutberg-stats {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 15px;
	text-align: center;
}

.stat-item {
	display: flex;
	flex-direction: column;
}

.stat-value {
	font-size: 32px;
	font-weight: 600;
	color: #2271b1;
}

.stat-label {
	font-size: 12px;
	color: #646970;
	margin-top: 5px;
}

.layoutberg-template-list {
	list-style: none;
	padding: 0;
	margin: 0;
}

.layoutberg-template-list li {
	padding: 8px 0;
	border-bottom: 1px solid #f0f0f1;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.layoutberg-template-list li:last-child {
	border-bottom: none;
}

.usage-count {
	font-size: 12px;
	color: #646970;
}

.layoutberg-steps {
	margin: 0;
	padding-left: 20px;
}

.layoutberg-steps li {
	margin-bottom: 15px;
}

.layoutberg-steps strong {
	display: block;
	margin-bottom: 5px;
}

.layoutberg-steps p {
	margin: 0;
	color: #646970;
	font-size: 13px;
}

.layoutberg-links {
	list-style: none;
	padding: 0;
	margin: 0;
}

.layoutberg-links li {
	margin-bottom: 10px;
}

.layoutberg-links a {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	text-decoration: none;
}

.layoutberg-links a:hover {
	color: #2271b1;
}

.status-completed {
	color: #00a32a;
}

.status-failed {
	color: #d63638;
}

.status-pending {
	color: #f0b849;
}

@media (max-width: 782px) {
	.layoutberg-card-wide {
		grid-column: span 1;
	}
	
	.layoutberg-stats {
		grid-template-columns: 1fr;
	}
}
</style>