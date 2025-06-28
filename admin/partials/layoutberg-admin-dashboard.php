<?php
/**
 * Main admin dashboard display
 *
 * @package    LayoutBerg
 * @subpackage Admin/Partials
 * @since      1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current user data.
$current_user = wp_get_current_user();
$user_id = get_current_user_id();

// Get usage statistics.
global $wpdb;
$table_usage = $wpdb->prefix . 'layoutberg_usage';
$table_generations = $wpdb->prefix . 'layoutberg_generations';
$today = current_time( 'Y-m-d' );
$this_month = current_time( 'Y-m' );

// Get today's usage.
$today_usage = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT generations_count, tokens_used FROM $table_usage WHERE user_id = %d AND date = %s",
		$user_id,
		$today
	)
);

// Get this month's usage.
$month_usage = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT SUM(generations_count) as total_generations, SUM(tokens_used) as total_tokens 
		FROM $table_usage 
		WHERE user_id = %d AND date LIKE %s",
		$user_id,
		$wpdb->esc_like( $this_month ) . '%'
	)
);

// Debug: Check if table exists and has data
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_usage'" );
	error_log( 'LayoutBerg Debug - Table exists: ' . ( $table_exists ? 'yes' : 'no' ) );
	
	if ( $table_exists ) {
		$all_usage = $wpdb->get_results( "SELECT * FROM $table_usage ORDER BY date DESC LIMIT 10" );
		error_log( 'LayoutBerg Debug - Usage records: ' . print_r( $all_usage, true ) );
		
		// Check table structure
		$columns = $wpdb->get_results( "SHOW COLUMNS FROM $table_usage" );
		error_log( 'LayoutBerg Debug - Table columns: ' . print_r( $columns, true ) );
	}
	
	error_log( 'LayoutBerg Debug - This month: ' . $this_month );
	error_log( 'LayoutBerg Debug - User ID: ' . $user_id );
	
	// Check database version
	$db_version = get_option( 'layoutberg_db_version', 'not set' );
	error_log( 'LayoutBerg Debug - DB Version: ' . $db_version );
	
	// Test generation count from generations table
	$gen_count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM $table_generations WHERE user_id = %d AND created_at >= %s",
			$user_id,
			$this_month . '-01'
		)
	);
	error_log( 'LayoutBerg Debug - Generations this month: ' . $gen_count );
}

// Get recent generations.
$recent_generations = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM $table_generations 
		WHERE user_id = %d 
		ORDER BY created_at DESC 
		LIMIT 5",
		$user_id
	)
);

// Get API key status.
$options = get_option( 'layoutberg_options', array() );
$has_api_key = ! empty( $options['api_key'] );

// Get saved templates count.
$table_templates = $wpdb->prefix . 'layoutberg_templates';
$templates_count = $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM $table_templates WHERE created_by = %d",
		$user_id
	)
);
?>

<div class="layoutberg-admin-page">
	<!-- Header -->
	<div class="layoutberg-header">
		<div class="layoutberg-header-content">
			<div class="layoutberg-title">
				<div class="layoutberg-logo">LB</div>
				<div>
					<h1><?php esc_html_e( 'LayoutBerg Dashboard', 'layoutberg' ); ?></h1>
					<p><?php esc_html_e( 'AI-powered layout designer for WordPress', 'layoutberg' ); ?></p>
				</div>
			</div>
			<div class="layoutberg-header-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=layoutberg-settings' ) ); ?>" class="layoutberg-btn layoutberg-btn-secondary">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php esc_html_e( 'Settings', 'layoutberg' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>" class="layoutberg-btn layoutberg-btn-primary">
					<span class="dashicons dashicons-plus-alt"></span>
					<?php esc_html_e( 'Create New Layout', 'layoutberg' ); ?>
				</a>
			</div>
		</div>
	</div>

	<!-- Main Content -->
	<div class="layoutberg-container">
		<!-- Alert if no API key -->
		<?php if ( ! $has_api_key ) : ?>
			<div class="layoutberg-alert layoutberg-alert-warning layoutberg-mb-4">
				<span class="dashicons dashicons-warning"></span>
				<div>
					<strong><?php esc_html_e( 'API Key Required', 'layoutberg' ); ?></strong>
					<p class="layoutberg-mt-1"><?php esc_html_e( 'Please configure your OpenAI API key to start generating layouts.', 'layoutberg' ); ?></p>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=layoutberg-settings' ) ); ?>" class="layoutberg-btn layoutberg-btn-warning layoutberg-btn-sm">
					<?php esc_html_e( 'Configure Now', 'layoutberg' ); ?>
				</a>
			</div>
		<?php endif; ?>

		<!-- Welcome Message -->
		<div class="layoutberg-card layoutberg-fade-in">
			<div class="layoutberg-flex layoutberg-items-center layoutberg-gap-3">
				<div style="flex: 1;">
					<h2 class="layoutberg-card-title">
						<?php
						/* translators: %s: User display name */
						printf( esc_html__( 'Welcome back, %s!', 'layoutberg' ), esc_html( $current_user->display_name ) );
						?>
					</h2>
					<p class="layoutberg-card-subtitle">
						<?php esc_html_e( 'Ready to create amazing layouts with AI? Start by creating a new page or post and use the LayoutBerg block.', 'layoutberg' ); ?>
					</p>
				</div>
				<div>
					<a href="https://docs.layoutberg.com" target="_blank" class="layoutberg-btn layoutberg-btn-secondary">
						<span class="dashicons dashicons-book"></span>
						<?php esc_html_e( 'Documentation', 'layoutberg' ); ?>
					</a>
				</div>
			</div>
		</div>

		<!-- Stats Grid -->
		<div class="layoutberg-grid layoutberg-grid-4 layoutberg-mb-4">
			<!-- Today's Generations -->
			<div class="layoutberg-stat-card layoutberg-fade-in">
				<div class="layoutberg-stat-icon primary">
					<span class="dashicons dashicons-calendar-alt"></span>
				</div>
				<p class="layoutberg-stat-value"><?php echo esc_html( $today_usage ? $today_usage->generations_count : 0 ); ?></p>
				<p class="layoutberg-stat-label"><?php esc_html_e( 'Today\'s Generations', 'layoutberg' ); ?></p>
			</div>

			<!-- Monthly Generations -->
			<div class="layoutberg-stat-card layoutberg-fade-in">
				<div class="layoutberg-stat-icon success">
					<span class="dashicons dashicons-chart-area"></span>
				</div>
				<p class="layoutberg-stat-value"><?php echo esc_html( $month_usage ? $month_usage->total_generations : 0 ); ?></p>
				<p class="layoutberg-stat-label"><?php esc_html_e( 'This Month', 'layoutberg' ); ?></p>
				<?php if ( $month_usage && $month_usage->total_generations > 0 ) : ?>
					<span class="layoutberg-stat-trend up">+12%</span>
				<?php endif; ?>
				<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ( ! $month_usage || $month_usage->total_generations == 0 ) ) : ?>
					<div style="margin-top: 10px; font-size: 11px;">
						<a href="<?php echo admin_url( 'admin.php?page=layoutberg-upgrade-db' ); ?>" style="color: #007cba;">Run DB upgrade</a> | 
						<a href="<?php echo admin_url( 'admin.php?page=layoutberg-test-usage' ); ?>" style="color: #007cba;">Test tracking</a>
					</div>
				<?php endif; ?>
			</div>

			<!-- Saved Templates -->
			<div class="layoutberg-stat-card layoutberg-fade-in">
				<div class="layoutberg-stat-icon warning">
					<span class="dashicons dashicons-archive"></span>
				</div>
				<p class="layoutberg-stat-value"><?php echo esc_html( $templates_count ); ?></p>
				<p class="layoutberg-stat-label"><?php esc_html_e( 'Saved Templates', 'layoutberg' ); ?></p>
			</div>

			<!-- API Status -->
			<div class="layoutberg-stat-card layoutberg-fade-in">
				<div class="layoutberg-stat-icon <?php echo $has_api_key ? 'success' : 'danger'; ?>">
					<span class="dashicons dashicons-<?php echo $has_api_key ? 'yes-alt' : 'dismiss'; ?>"></span>
				</div>
				<p class="layoutberg-stat-value"><?php echo $has_api_key ? esc_html__( 'Active', 'layoutberg' ) : esc_html__( 'Inactive', 'layoutberg' ); ?></p>
				<p class="layoutberg-stat-label"><?php esc_html_e( 'API Status', 'layoutberg' ); ?></p>
			</div>
		</div>

		<!-- Two Column Layout -->
		<div class="layoutberg-grid layoutberg-grid-2">
			<!-- Quick Actions -->
			<div class="layoutberg-card layoutberg-fade-in">
				<div class="layoutberg-card-header">
					<h3 class="layoutberg-card-title"><?php esc_html_e( 'Quick Actions', 'layoutberg' ); ?></h3>
				</div>
				
				<div class="layoutberg-grid layoutberg-grid-2 layoutberg-gap-2">
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>" class="layoutberg-btn layoutberg-btn-primary layoutberg-btn-lg" style="width: 100%; justify-content: center;">
						<span class="dashicons dashicons-layout"></span>
						<?php esc_html_e( 'New Page Layout', 'layoutberg' ); ?>
					</a>
					
					<a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" class="layoutberg-btn layoutberg-btn-primary layoutberg-btn-lg" style="width: 100%; justify-content: center;">
						<span class="dashicons dashicons-welcome-write-blog"></span>
						<?php esc_html_e( 'New Blog Post', 'layoutberg' ); ?>
					</a>
					
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=layoutberg-templates' ) ); ?>" class="layoutberg-btn layoutberg-btn-secondary layoutberg-btn-lg" style="width: 100%; justify-content: center;">
						<span class="dashicons dashicons-category"></span>
						<?php esc_html_e( 'Browse Templates', 'layoutberg' ); ?>
					</a>
					
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=layoutberg-analytics' ) ); ?>" class="layoutberg-btn layoutberg-btn-secondary layoutberg-btn-lg" style="width: 100%; justify-content: center;">
						<span class="dashicons dashicons-chart-bar"></span>
						<?php esc_html_e( 'View Analytics', 'layoutberg' ); ?>
					</a>
				</div>

				<div class="layoutberg-mt-3">
					<h4 class="layoutberg-mb-2"><?php esc_html_e( 'Popular Templates', 'layoutberg' ); ?></h4>
					<div class="layoutberg-grid layoutberg-gap-1">
						<?php
						$templates = array(
							'hero' => __( 'Hero Section', 'layoutberg' ),
							'features' => __( 'Features Grid', 'layoutberg' ),
							'testimonials' => __( 'Testimonials', 'layoutberg' ),
							'pricing' => __( 'Pricing Table', 'layoutberg' ),
						);
						foreach ( $templates as $key => $name ) :
						?>
							<button class="layoutberg-btn layoutberg-btn-secondary layoutberg-btn-sm" data-template="<?php echo esc_attr( $key ); ?>">
								<?php echo esc_html( $name ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Recent Generations -->
			<div class="layoutberg-card layoutberg-fade-in">
				<div class="layoutberg-card-header">
					<h3 class="layoutberg-card-title"><?php esc_html_e( 'Recent Generations', 'layoutberg' ); ?></h3>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=layoutberg-history' ) ); ?>" class="layoutberg-btn layoutberg-btn-secondary layoutberg-btn-sm">
						<?php esc_html_e( 'View All', 'layoutberg' ); ?>
					</a>
				</div>
				
				<?php if ( ! empty( $recent_generations ) ) : ?>
					<div class="layoutberg-list">
						<?php foreach ( $recent_generations as $generation ) : ?>
							<div class="layoutberg-list-item" style="padding: 1rem 0; border-bottom: 1px solid var(--lberg-gray-200);">
								<div class="layoutberg-flex layoutberg-items-center layoutberg-justify-between">
									<div>
										<p style="margin: 0; font-weight: 500;">
											<?php echo esc_html( substr( $generation->prompt, 0, 50 ) . '...' ); ?>
										</p>
										<p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: var(--lberg-gray-600);">
											<?php
											/* translators: %s: Time ago */
											printf( esc_html__( '%s ago', 'layoutberg' ), human_time_diff( strtotime( $generation->created_at ) ) );
											?>
										</p>
									</div>
									<div class="layoutberg-flex layoutberg-items-center layoutberg-gap-2">
										<span class="layoutberg-badge layoutberg-badge-<?php echo esc_attr( $generation->status === 'completed' ? 'success' : 'danger' ); ?>">
											<?php echo esc_html( ucfirst( $generation->status ) ); ?>
										</span>
										<span style="font-size: 0.75rem; color: var(--lberg-gray-500);">
											<?php echo esc_html( number_format( $generation->tokens_used ) ); ?> tokens
										</span>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<div class="layoutberg-empty-state">
						<div class="layoutberg-empty-icon">
							<span class="dashicons dashicons-clock"></span>
						</div>
						<h4 class="layoutberg-empty-title"><?php esc_html_e( 'No generations yet', 'layoutberg' ); ?></h4>
						<p class="layoutberg-empty-description"><?php esc_html_e( 'Start creating layouts to see your history here.', 'layoutberg' ); ?></p>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>" class="layoutberg-btn layoutberg-btn-primary">
							<?php esc_html_e( 'Create Your First Layout', 'layoutberg' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Getting Started Guide -->
		<div class="layoutberg-card layoutberg-mt-4 layoutberg-fade-in">
			<div class="layoutberg-card-header">
				<h3 class="layoutberg-card-title"><?php esc_html_e( 'Getting Started', 'layoutberg' ); ?></h3>
				<button class="layoutberg-btn layoutberg-btn-secondary layoutberg-btn-sm" id="layoutberg-dismiss-guide">
					<?php esc_html_e( 'Dismiss', 'layoutberg' ); ?>
				</button>
			</div>
			
			<div class="layoutberg-grid layoutberg-grid-3">
				<div class="layoutberg-text-center">
					<div class="layoutberg-stat-icon primary layoutberg-mb-2" style="margin: 0 auto 1rem; width: 64px; height: 64px;">
						<span class="dashicons dashicons-admin-network" style="font-size: 2rem;"></span>
					</div>
					<h4><?php esc_html_e( '1. Configure API Key', 'layoutberg' ); ?></h4>
					<p style="color: var(--lberg-gray-600); font-size: 0.875rem;">
						<?php esc_html_e( 'Add your OpenAI API key in the settings to enable AI-powered layout generation.', 'layoutberg' ); ?>
					</p>
				</div>
				
				<div class="layoutberg-text-center">
					<div class="layoutberg-stat-icon success layoutberg-mb-2" style="margin: 0 auto 1rem; width: 64px; height: 64px;">
						<span class="dashicons dashicons-edit-page" style="font-size: 2rem;"></span>
					</div>
					<h4><?php esc_html_e( '2. Create New Page', 'layoutberg' ); ?></h4>
					<p style="color: var(--lberg-gray-600); font-size: 0.875rem;">
						<?php esc_html_e( 'Create a new page or post and add the LayoutBerg AI Layout block.', 'layoutberg' ); ?>
					</p>
				</div>
				
				<div class="layoutberg-text-center">
					<div class="layoutberg-stat-icon warning layoutberg-mb-2" style="margin: 0 auto 1rem; width: 64px; height: 64px;">
						<span class="dashicons dashicons-admin-customizer" style="font-size: 2rem;"></span>
					</div>
					<h4><?php esc_html_e( '3. Generate Layout', 'layoutberg' ); ?></h4>
					<p style="color: var(--lberg-gray-600); font-size: 0.875rem;">
						<?php esc_html_e( 'Describe your desired layout in natural language and let AI create it for you.', 'layoutberg' ); ?>
					</p>
				</div>
			</div>
		</div>

		<!-- Resources -->
		<div class="layoutberg-card layoutberg-mt-4 layoutberg-fade-in">
			<h3 class="layoutberg-card-title layoutberg-mb-3"><?php esc_html_e( 'Resources & Support', 'layoutberg' ); ?></h3>
			
			<div class="layoutberg-grid layoutberg-grid-4">
				<a href="https://docs.layoutberg.com" target="_blank" class="layoutberg-btn layoutberg-btn-secondary" style="width: 100%; justify-content: center;">
					<span class="dashicons dashicons-media-document"></span>
					<?php esc_html_e( 'Documentation', 'layoutberg' ); ?>
				</a>
				
				<a href="https://layoutberg.com/tutorials" target="_blank" class="layoutberg-btn layoutberg-btn-secondary" style="width: 100%; justify-content: center;">
					<span class="dashicons dashicons-video-alt3"></span>
					<?php esc_html_e( 'Video Tutorials', 'layoutberg' ); ?>
				</a>
				
				<a href="https://layoutberg.com/support" target="_blank" class="layoutberg-btn layoutberg-btn-secondary" style="width: 100%; justify-content: center;">
					<span class="dashicons dashicons-sos"></span>
					<?php esc_html_e( 'Get Support', 'layoutberg' ); ?>
				</a>
				
				<a href="https://layoutberg.com/community" target="_blank" class="layoutberg-btn layoutberg-btn-secondary" style="width: 100%; justify-content: center;">
					<span class="dashicons dashicons-groups"></span>
					<?php esc_html_e( 'Community', 'layoutberg' ); ?>
				</a>
			</div>
		</div>
	</div>
</div>

<style>
/* Additional Dashboard-specific styles */
.layoutberg-list-item:last-child {
	border-bottom: none !important;
}

.layoutberg-list-item:hover {
	background: var(--lberg-gray-50);
	margin: 0 -1rem;
	padding: 1rem;
}
</style>

<script>
jQuery(document).ready(function($) {
	// Dismiss guide
	$('#layoutberg-dismiss-guide').on('click', function() {
		$(this).closest('.layoutberg-card').fadeOut();
		// Save dismissal in user meta
		$.post(ajaxurl, {
			action: 'layoutberg_dismiss_guide',
			_ajax_nonce: '<?php echo wp_create_nonce( 'layoutberg_dismiss_guide' ); ?>'
		});
	});

	// Template quick actions
	$('[data-template]').on('click', function() {
		var template = $(this).data('template');
		// Redirect to new page with template parameter
		window.location.href = '<?php echo esc_url( admin_url( 'post-new.php?post_type=page&layoutberg_template=' ) ); ?>' + template;
	});
});
</script>