<?php
/**
 * Usage Analytics page
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
$user_id      = get_current_user_id();

// Get date ranges
$today      = current_time( 'Y-m-d' );
$this_month = current_time( 'Y-m' );
$last_month = date( 'Y-m', strtotime( '-1 month' ) );
$this_year  = current_time( 'Y' );

// Database tables
global $wpdb;
$table_usage       = $wpdb->prefix . 'layoutberg_usage';
$table_generations = $wpdb->prefix . 'layoutberg_generations';

// Get history days limit based on plan
$history_days_limit = \DotCamp\LayoutBerg\LayoutBerg_Licensing::get_history_days();
$is_limited_plan = $history_days_limit !== PHP_INT_MAX;

// Get period from query string (default to month)
$period = isset( $_GET['period'] ) ? sanitize_text_field( $_GET['period'] ) : 'month';

// If on limited plan, restrict available periods
if ( $is_limited_plan ) {
	// For limited plans, only allow periods within the 30-day limit
	if ( in_array( $period, array( 'year', 'all' ), true ) ) {
		$period = 'month'; // Default to month if trying to access restricted periods
	}
}

// Calculate date ranges based on period
switch ( $period ) {
	case 'today':
		$start_date   = $today;
		$end_date     = $today;
		$period_label = __( 'Today', 'layoutberg' );
		break;
	case 'week':
		$start_date   = date( 'Y-m-d', strtotime( '-6 days' ) );
		$end_date     = $today;
		$period_label = __( 'Last 7 Days', 'layoutberg' );
		break;
	case 'month':
		$start_date   = date( 'Y-m-01' );
		$end_date     = date( 'Y-m-t' );
		$period_label = __( 'This Month', 'layoutberg' );
		break;
	case 'last_month':
		$start_date   = date( 'Y-m-01', strtotime( '-1 month' ) );
		$end_date     = date( 'Y-m-t', strtotime( '-1 month' ) );
		$period_label = __( 'Last Month', 'layoutberg' );
		// Check if last month is within the 30-day limit
		if ( $is_limited_plan ) {
			$days_ago = ( strtotime( $today ) - strtotime( $start_date ) ) / ( 60 * 60 * 24 );
			if ( $days_ago > $history_days_limit ) {
				// Adjust start date to be within the limit
				$start_date = date( 'Y-m-d', strtotime( '-' . $history_days_limit . ' days' ) );
			}
		}
		break;
	case 'year':
		$start_date   = date( 'Y-01-01' );
		$end_date     = date( 'Y-12-31' );
		$period_label = __( 'This Year', 'layoutberg' );
		break;
	case 'all':
		$start_date   = '2000-01-01';
		$end_date     = $today;
		$period_label = __( 'All Time', 'layoutberg' );
		break;
	default:
		$start_date   = date( 'Y-m-01' );
		$end_date     = date( 'Y-m-t' );
		$period_label = __( 'This Month', 'layoutberg' );
}

// Enforce history limit for limited plans
if ( $is_limited_plan ) {
	$max_start_date = date( 'Y-m-d', strtotime( '-' . $history_days_limit . ' days' ) );
	if ( strtotime( $start_date ) < strtotime( $max_start_date ) ) {
		$start_date = $max_start_date;
	}
}

// Get overall statistics
$total_stats = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT 
			SUM(generations_count) as total_generations,
			SUM(tokens_used) as total_tokens,
			SUM(cost) as total_cost
		FROM $table_usage 
		WHERE user_id = %d AND date >= %s AND date <= %s",
		$user_id,
		$start_date,
		$end_date
	)
);

// Get daily usage for chart
$daily_usage = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT date, generations_count, tokens_used, cost 
		FROM $table_usage 
		WHERE user_id = %d AND date >= %s AND date <= %s
		ORDER BY date ASC",
		$user_id,
		$start_date,
		$end_date
	)
);

// Get model usage statistics
$model_stats = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT 
			model,
			COUNT(*) as count,
			SUM(tokens_used) as tokens,
			AVG(tokens_used) as avg_tokens
		FROM $table_generations 
		WHERE user_id = %d AND created_at >= %s AND created_at <= %s AND status = 'completed'
		GROUP BY model
		ORDER BY count DESC",
		$user_id,
		$start_date . ' 00:00:00',
		$end_date . ' 23:59:59'
	)
);

// Get top prompts (first 50 chars)
$top_prompts = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT 
			LEFT(prompt, 50) as prompt_preview,
			COUNT(*) as count
		FROM $table_generations 
		WHERE user_id = %d AND created_at >= %s AND created_at <= %s AND status = 'completed'
		GROUP BY prompt_preview
		ORDER BY count DESC
		LIMIT 5",
		$user_id,
		$start_date . ' 00:00:00',
		$end_date . ' 23:59:59'
	)
);

// Get hourly distribution
$hourly_stats = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT 
			HOUR(created_at) as hour,
			COUNT(*) as count
		FROM $table_generations 
		WHERE user_id = %d AND created_at >= %s AND created_at <= %s AND status = 'completed'
		GROUP BY hour
		ORDER BY hour",
		$user_id,
		$start_date . ' 00:00:00',
		$end_date . ' 23:59:59'
	)
);

// Calculate some derived stats
$avg_daily_generations     = $total_stats->total_generations ?
	round( $total_stats->total_generations / max( 1, count( $daily_usage ) ) ) : 0;
$avg_tokens_per_generation = $total_stats->total_generations && $total_stats->total_tokens ?
	round( $total_stats->total_tokens / $total_stats->total_generations ) : 0;
$avg_cost_per_generation   = $total_stats->total_generations && $total_stats->total_cost ?
	$total_stats->total_cost / $total_stats->total_generations : 0;

// Prepare chart data
$chart_labels      = array();
$chart_generations = array();
$chart_tokens      = array();
$chart_costs       = array();

foreach ( $daily_usage as $day ) {
	$chart_labels[]      = date( 'M j', strtotime( $day->date ) );
	$chart_generations[] = (int) $day->generations_count;
	$chart_tokens[]      = (int) $day->tokens_used;
	$chart_costs[]       = (float) $day->cost;
}

// Prepare hourly chart data
$hourly_labels = array();
$hourly_counts = array();
for ( $i = 0; $i < 24; $i++ ) {
	$hourly_labels[]     = sprintf( '%02d:00', $i );
	$hourly_counts[ $i ] = 0;
}
foreach ( $hourly_stats as $hour ) {
	$hourly_counts[ $hour->hour ] = (int) $hour->count;
}
?>

<div class="layoutberg-admin-page">
	<!-- Header -->
	<div class="layoutberg-header">
		<div class="layoutberg-header-content">
			<div class="layoutberg-title">
				<div class="layoutberg-logo">
					<img src="<?php echo esc_url( LAYOUTBERG_PLUGIN_URL . 'assets/images/layoutberg-logo.png' ); ?>" alt="<?php esc_attr_e( 'LayoutBerg Logo', 'layoutberg' ); ?>" />
				</div>
				<div>
					<h1><?php esc_html_e( 'Usage Analytics', 'layoutberg' ); ?></h1>
					<p><?php esc_html_e( 'Track your AI layout generation usage and statistics', 'layoutberg' ); ?></p>
				</div>
			</div>
			<div class="layoutberg-header-actions" style="display: flex; gap: 1rem; align-items: center;">
				<select id="period-selector" class="layoutberg-select" style="width: 150px;">
					<option value="today" <?php selected( $period, 'today' ); ?>><?php esc_html_e( 'Today', 'layoutberg' ); ?></option>
					<option value="week" <?php selected( $period, 'week' ); ?>><?php esc_html_e( 'Last 7 Days', 'layoutberg' ); ?></option>
					<option value="month" <?php selected( $period, 'month' ); ?>><?php esc_html_e( 'This Month', 'layoutberg' ); ?></option>
					<option value="last_month" <?php selected( $period, 'last_month' ); ?>><?php esc_html_e( 'Last Month', 'layoutberg' ); ?></option>
					<?php if ( ! $is_limited_plan ) : ?>
						<option value="year" <?php selected( $period, 'year' ); ?>><?php esc_html_e( 'This Year', 'layoutberg' ); ?></option>
						<option value="all" <?php selected( $period, 'all' ); ?>><?php esc_html_e( 'All Time', 'layoutberg' ); ?></option>
					<?php else : ?>
						<option value="year" disabled title="<?php esc_attr_e( 'Upgrade to Professional or Agency plan for yearly analytics', 'layoutberg' ); ?>">
							<?php esc_html_e( 'This Year', 'layoutberg' ); ?> 🔒
						</option>
						<option value="all" disabled title="<?php esc_attr_e( 'Upgrade to Professional or Agency plan for all-time analytics', 'layoutberg' ); ?>">
							<?php esc_html_e( 'All Time', 'layoutberg' ); ?> 🔒
						</option>
					<?php endif; ?>
				</select>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=layoutberg' ) ); ?>" class="layoutberg-btn layoutberg-btn-secondary">
					<span class="dashicons dashicons-arrow-left-alt"></span>
					<?php esc_html_e( 'Back to Dashboard', 'layoutberg' ); ?>
				</a>
			</div>
		</div>
	</div>

	<!-- Main Content -->
	<div class="layoutberg-container">
		<!-- Plan Limitation Notice -->
		<?php if ( $is_limited_plan ) : ?>
			<div class="layoutberg-alert layoutberg-alert-warning layoutberg-mb-4">
				<span class="dashicons dashicons-warning"></span>
				<div>
					<strong><?php esc_html_e( 'Limited Analytics History', 'layoutberg' ); ?></strong>
					<p>
						<?php 
						printf(
							/* translators: %1$s: plan name, %2$d: days limit */
							esc_html__( 'Your %1$s plan includes analytics for the last %2$d days. Upgrade to Professional or Agency plan for unlimited analytics history.', 'layoutberg' ),
							esc_html( \DotCamp\LayoutBerg\LayoutBerg_Licensing::get_plan_name() ),
							$history_days_limit
						);
						?>
					</p>
					<a href="<?php echo esc_url( \DotCamp\LayoutBerg\LayoutBerg_Licensing::get_action_url() ); ?>" class="layoutberg-btn layoutberg-btn-sm layoutberg-btn-primary layoutberg-mt-2">
						<?php esc_html_e( 'Upgrade Plan', 'layoutberg' ); ?>
					</a>
				</div>
			</div>
		<?php endif; ?>
		
		<!-- Data Accuracy Notice -->
		<div class="layoutberg-alert layoutberg-alert-info layoutberg-mb-4">
			<span class="dashicons dashicons-info"></span>
			<div>
				<strong><?php esc_html_e( 'Data Accuracy Notice', 'layoutberg' ); ?></strong>
				<p><?php esc_html_e( 'The usage data shown here is tracked locally and may not be 100% accurate. For precise usage statistics and billing information, please refer to your AI provider dashboards.', 'layoutberg' ); ?></p>
				<div class="layoutberg-flex layoutberg-gap-2 layoutberg-mt-2">
					<a href="https://platform.openai.com/usage" target="_blank" class="layoutberg-btn layoutberg-btn-sm layoutberg-btn-secondary">
						<span class="dashicons dashicons-external"></span>
						<?php esc_html_e( 'OpenAI Dashboard', 'layoutberg' ); ?>
					</a>
					<a href="https://console.anthropic.com/usage" target="_blank" class="layoutberg-btn layoutberg-btn-sm layoutberg-btn-secondary">
						<span class="dashicons dashicons-external"></span>
						<?php esc_html_e( 'Claude Dashboard', 'layoutberg' ); ?>
					</a>
				</div>
			</div>
		</div>

		<!-- Overview Stats -->
		<div class="layoutberg-grid layoutberg-grid-4 layoutberg-mb-4">
			<!-- Total Generations -->
			<div class="layoutberg-stat-card layoutberg-fade-in">
				<div class="layoutberg-stat-icon primary">
					<span class="dashicons dashicons-admin-page"></span>
				</div>
				<p class="layoutberg-stat-value"><?php echo esc_html( number_format( $total_stats->total_generations ?? 0 ) ); ?></p>
				<p class="layoutberg-stat-label"><?php esc_html_e( 'Total Generations', 'layoutberg' ); ?></p>
				<p class="layoutberg-stat-sublabel"><?php echo esc_html( $period_label ); ?></p>
			</div>

			<!-- Total Tokens -->
			<div class="layoutberg-stat-card layoutberg-fade-in">
				<div class="layoutberg-stat-icon success">
					<span class="dashicons dashicons-editor-code"></span>
				</div>
				<p class="layoutberg-stat-value"><?php echo esc_html( number_format( $total_stats->total_tokens ?? 0 ) ); ?></p>
				<p class="layoutberg-stat-label"><?php esc_html_e( 'Tokens Used', 'layoutberg' ); ?></p>
				<p class="layoutberg-stat-sublabel"><?php echo esc_html( sprintf( __( 'Avg %s/generation', 'layoutberg' ), number_format( $avg_tokens_per_generation ) ) ); ?></p>
			</div>

			<!-- Total Cost -->
			<div class="layoutberg-stat-card layoutberg-fade-in">
				<div class="layoutberg-stat-icon warning">
					<span class="dashicons dashicons-chart-area"></span>
				</div>
				<p class="layoutberg-stat-value">$<?php echo esc_html( number_format( $total_stats->total_cost ?? 0, 2 ) ); ?></p>
				<p class="layoutberg-stat-label"><?php esc_html_e( 'Total Cost', 'layoutberg' ); ?></p>
				<p class="layoutberg-stat-sublabel"><?php echo esc_html( sprintf( __( 'Avg $%s/generation', 'layoutberg' ), number_format( $avg_cost_per_generation, 4 ) ) ); ?></p>
			</div>

			<!-- Average Daily -->
			<div class="layoutberg-stat-card layoutberg-fade-in">
				<div class="layoutberg-stat-icon info">
					<span class="dashicons dashicons-calendar-alt"></span>
				</div>
				<p class="layoutberg-stat-value"><?php echo esc_html( $avg_daily_generations ); ?></p>
				<p class="layoutberg-stat-label"><?php esc_html_e( 'Daily Average', 'layoutberg' ); ?></p>
				<p class="layoutberg-stat-sublabel"><?php esc_html_e( 'Generations per day', 'layoutberg' ); ?></p>
			</div>
		</div>

		<!-- Charts Row -->
		<div class="layoutberg-grid layoutberg-grid-2 layoutberg-mb-4">
			<!-- Daily Usage Chart -->
			<div class="layoutberg-card layoutberg-fade-in">
				<div class="layoutberg-card-header">
					<h3 class="layoutberg-card-title"><?php esc_html_e( 'Daily Usage', 'layoutberg' ); ?></h3>
				</div>
				<div style="height: 300px; padding: 20px;">
					<canvas id="daily-usage-chart"></canvas>
				</div>
			</div>

			<!-- Model Distribution -->
			<div class="layoutberg-card layoutberg-fade-in">
				<div class="layoutberg-card-header">
					<h3 class="layoutberg-card-title"><?php esc_html_e( 'Model Usage', 'layoutberg' ); ?></h3>
				</div>
				<div style="height: 300px; padding: 20px;">
					<canvas id="model-usage-chart"></canvas>
				</div>
			</div>
		</div>

		<!-- Additional Stats -->
		<div class="layoutberg-grid layoutberg-grid-3 layoutberg-mb-4">
			<!-- Top Prompts -->
			<div class="layoutberg-card layoutberg-fade-in">
				<div class="layoutberg-card-header">
					<h3 class="layoutberg-card-title"><?php esc_html_e( 'Top Prompts', 'layoutberg' ); ?></h3>
				</div>
				<?php if ( ! empty( $top_prompts ) ) : ?>
					<div class="layoutberg-list">
						<?php foreach ( $top_prompts as $prompt ) : ?>
							<div class="layoutberg-list-item" style="padding: 0.75rem 0; border-bottom: 1px solid var(--lberg-gray-200);">
								<div class="layoutberg-flex layoutberg-items-center layoutberg-justify-between">
									<div style="flex: 1; margin-right: 1rem;">
										<p style="margin: 0; font-size: 0.875rem; color: var(--lberg-gray-700);">
											<?php echo esc_html( $prompt->prompt_preview ); ?>...
										</p>
									</div>
									<div>
										<span class="layoutberg-badge layoutberg-badge-primary">
											<?php echo esc_html( $prompt->count ); ?>
										</span>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<div class="layoutberg-empty-state" style="padding: 2rem;">
						<p><?php esc_html_e( 'No prompts yet', 'layoutberg' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<!-- Hourly Distribution -->
			<div class="layoutberg-card layoutberg-fade-in">
				<div class="layoutberg-card-header">
					<h3 class="layoutberg-card-title"><?php esc_html_e( 'Hourly Activity', 'layoutberg' ); ?></h3>
				</div>
				<div style="height: 250px; padding: 20px;">
					<canvas id="hourly-chart"></canvas>
				</div>
			</div>

			<!-- Model Details -->
			<div class="layoutberg-card layoutberg-fade-in">
				<div class="layoutberg-card-header">
					<h3 class="layoutberg-card-title"><?php esc_html_e( 'Model Details', 'layoutberg' ); ?></h3>
				</div>
				<?php if ( ! empty( $model_stats ) ) : ?>
					<table class="layoutberg-table" style="width: 100%;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Model', 'layoutberg' ); ?></th>
								<th><?php esc_html_e( 'Uses', 'layoutberg' ); ?></th>
								<th><?php esc_html_e( 'Avg Tokens', 'layoutberg' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $model_stats as $model ) : ?>
								<tr>
									<td>
										<span class="layoutberg-badge layoutberg-badge-secondary">
											<?php echo esc_html( $model->model ); ?>
										</span>
									</td>
									<td><?php echo esc_html( $model->count ); ?></td>
									<td><?php echo esc_html( number_format( $model->avg_tokens ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<div class="layoutberg-empty-state" style="padding: 2rem;">
						<p><?php esc_html_e( 'No model data yet', 'layoutberg' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Export Section -->
		<div class="layoutberg-card layoutberg-fade-in">
			<div class="layoutberg-card-header">
				<h3 class="layoutberg-card-title"><?php esc_html_e( 'Export Data', 'layoutberg' ); ?></h3>
			</div>
			<div class="layoutberg-flex layoutberg-gap-3">
				<?php if ( \DotCamp\LayoutBerg\LayoutBerg_Licensing::can_export_csv() ) : ?>
					<button class="layoutberg-btn layoutberg-btn-secondary" id="export-csv">
						<span class="dashicons dashicons-download"></span>
						<?php esc_html_e( 'Export as CSV', 'layoutberg' ); ?>
					</button>
				<?php else : ?>
					<?php
					echo \DotCamp\LayoutBerg\LayoutBerg_Licensing::get_locked_button(
						__( 'Export as CSV', 'layoutberg' ),
						__( 'CSV Export', 'layoutberg' ),
						'agency'
					);
					?>
				<?php endif; ?>
				<button class="layoutberg-btn layoutberg-btn-secondary" id="print-report">
					<span class="dashicons dashicons-printer"></span>
					<?php esc_html_e( 'Print Report', 'layoutberg' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
jQuery(document).ready(function($) {
	// Period selector
	$('#period-selector').on('change', function() {
		var selectedPeriod = $(this).val();
		var selectedOption = $(this).find('option:selected');
		
		// Check if the selected option is disabled
		if (selectedOption.prop('disabled')) {
			// Reset to the previous value
			var urlParams = new URLSearchParams(window.location.search);
			var currentPeriod = urlParams.get('period') || 'month';
			$(this).val(currentPeriod);
			
			// Show upgrade notice
			alert('<?php echo esc_js( __( 'This feature requires Professional or Agency plan. Please upgrade to access full analytics history.', 'layoutberg' ) ); ?>');
			
			// Optionally redirect to upgrade page
			if (confirm('<?php echo esc_js( __( 'Would you like to upgrade your plan now?', 'layoutberg' ) ); ?>')) {
				window.location.href = '<?php echo esc_url( \DotCamp\LayoutBerg\LayoutBerg_Licensing::get_action_url() ); ?>';
			}
			return;
		}
		
		var currentUrl = new URL(window.location.href);
		currentUrl.searchParams.set('period', selectedPeriod);
		window.location.href = currentUrl.toString();
	});
	
	// Ensure the correct period is selected on page load
	$(document).ready(function() {
		var urlParams = new URLSearchParams(window.location.search);
		var periodParam = urlParams.get('period');
		if (periodParam) {
			$('#period-selector').val(periodParam);
		}
	});

	// Chart configuration
	Chart.defaults.color = '#6b7280';
	Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';

	// Daily Usage Chart
	const dailyCtx = document.getElementById('daily-usage-chart').getContext('2d');
	new Chart(dailyCtx, {
		type: 'line',
		data: {
			labels: <?php echo json_encode( $chart_labels ); ?>,
			datasets: [{
				label: '<?php esc_html_e( 'Generations', 'layoutberg' ); ?>',
				data: <?php echo json_encode( $chart_generations ); ?>,
				borderColor: '#007cba',
				backgroundColor: 'rgba(0, 124, 186, 0.1)',
				tension: 0.4
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: {
					display: false
				}
			},
			scales: {
				y: {
					beginAtZero: true,
					ticks: {
						stepSize: 1
					}
				}
			}
		}
	});

	// Model Usage Chart
	const modelCtx = document.getElementById('model-usage-chart').getContext('2d');
	const modelData = 
	<?php
		$model_names  = array();
		$model_counts = array();
	foreach ( $model_stats as $model ) {
		$model_names[]  = $model->model;
		$model_counts[] = $model->count;
	}
		echo json_encode(
			array(
				'labels' => $model_names,
				'data'   => $model_counts,
			)
		);
		?>
	;
	
	new Chart(modelCtx, {
		type: 'doughnut',
		data: {
			labels: modelData.labels,
			datasets: [{
				data: modelData.data,
				backgroundColor: [
					'#007cba',
					'#00a32a',
					'#d63638',
					'#f0b849'
				]
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: {
					position: 'bottom'
				}
			}
		}
	});

	// Hourly Chart
	const hourlyCtx = document.getElementById('hourly-chart').getContext('2d');
	new Chart(hourlyCtx, {
		type: 'bar',
		data: {
			labels: <?php echo json_encode( $hourly_labels ); ?>,
			datasets: [{
				label: '<?php esc_html_e( 'Generations', 'layoutberg' ); ?>',
				data: <?php echo json_encode( array_values( $hourly_counts ) ); ?>,
				backgroundColor: 'rgba(0, 124, 186, 0.5)',
				borderColor: '#007cba',
				borderWidth: 1
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: {
					display: false
				}
			},
			scales: {
				y: {
					beginAtZero: true,
					ticks: {
						stepSize: 1
					}
				},
				x: {
					ticks: {
						maxRotation: 45,
						minRotation: 45
					}
				}
			}
		}
	});

	// Export CSV
	$('#export-csv').on('click', function() {
		var $button = $(this);
		var originalText = $button.html();
		
		// Show loading state
		$button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e( 'Exporting...', 'layoutberg' ); ?>');
		
		// Make AJAX request to server
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'layoutberg_export_csv',
				_wpnonce: '<?php echo wp_create_nonce( 'layoutberg_nonce' ); ?>',
				period: '<?php echo esc_js( $period ); ?>',
				format: 'daily' // Can be 'daily' or 'detailed'
			},
			success: function(response) {
				if (response.success && response.data) {
					// Create and download the CSV file
					const blob = new Blob([response.data.content], { type: response.data.mimeType });
					const url = window.URL.createObjectURL(blob);
					const a = document.createElement('a');
					a.href = url;
					a.download = response.data.filename;
					document.body.appendChild(a);
					a.click();
					document.body.removeChild(a);
					window.URL.revokeObjectURL(url);
					
					// Show success message
					$button.html('<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Exported!', 'layoutberg' ); ?>');
					setTimeout(function() {
						$button.prop('disabled', false).html(originalText);
					}, 2000);
				} else {
					// Show error message
					alert(response.data || '<?php esc_html_e( 'Export failed. Please try again.', 'layoutberg' ); ?>');
					$button.prop('disabled', false).html(originalText);
				}
			},
			error: function() {
				alert('<?php esc_html_e( 'Export failed. Please check your connection and try again.', 'layoutberg' ); ?>');
				$button.prop('disabled', false).html(originalText);
			}
		});
	});

	// Print Report
	$('#print-report').on('click', function() {
		window.print();
	});
});
</script>

<style>
/* Analytics specific styles */
.layoutberg-table {
	width: 100%;
	border-collapse: collapse;
}

.layoutberg-table th,
.layoutberg-table td {
	padding: 0.75rem;
	text-align: left;
	border-bottom: 1px solid var(--lberg-gray-200);
}

.layoutberg-table th {
	font-weight: 600;
	color: var(--lberg-gray-700);
	background: var(--lberg-gray-50);
}

.layoutberg-list-item:last-child {
	border-bottom: none !important;
}

.layoutberg-stat-sublabel {
	margin: 0.25rem 0 0 0;
	font-size: 0.75rem;
	color: var(--lberg-gray-500);
}

/* Style for disabled select options */
#period-selector option:disabled {
	color: #9ca3af;
	font-style: italic;
}

.layoutberg-alert-warning {
	background-color: #fef3c7;
	border-color: #f59e0b;
	color: #92400e;
}

.layoutberg-alert-warning .dashicons-warning {
	color: #f59e0b;
}

/* Spinning animation for loading states */
@keyframes spin {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}

.dashicons.spin {
	animation: spin 1s linear infinite;
}

@media print {
	.layoutberg-header-actions,
	#export-csv,
	#print-report,
	.layoutberg-alert-warning {
		display: none;
	}
	
	.layoutberg-card {
		break-inside: avoid;
		box-shadow: none;
		border: 1px solid #ddd;
	}
}
</style>