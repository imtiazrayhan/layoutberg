<?php
/**
 * Debug logs page for Agency plan users
 *
 * @package    LayoutBerg
 * @subpackage Admin/Partials
 * @since      1.3.0
 */

use DotCamp\LayoutBerg\Debug_Logger;
use DotCamp\LayoutBerg\LayoutBerg_Licensing;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only allow admins to view this page
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Access denied.' );
}

// Check if user has Agency plan
if ( ! LayoutBerg_Licensing::is_agency_plan() ) {
	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Debug Logs', 'layoutberg' ) . '</h1>';
	echo '<div class="notice notice-info"><p>';
	echo esc_html__( 'Debug logs are only available for Agency plan users.', 'layoutberg' );
	echo ' <a href="' . esc_url( admin_url( 'admin.php?page=layoutberg-pricing' ) ) . '">' . esc_html__( 'Upgrade to Agency', 'layoutberg' ) . '</a>';
	echo '</p></div>';
	echo '</div>';
	return;
}

// Handle log clearing
if ( isset( $_POST['clear_logs'] ) && wp_verify_nonce( $_POST['layoutberg_debug_nonce'], 'layoutberg_clear_logs' ) ) {
	$days = isset( $_POST['clear_days'] ) ? intval( $_POST['clear_days'] ) : 0;
	$cleared = Debug_Logger::clear_logs( $days );
	echo '<div class="notice notice-success"><p>' . sprintf( esc_html__( 'Cleared %d log entries.', 'layoutberg' ), $cleared ) . '</p></div>';
}

// Get filter parameters
$filter_type = isset( $_GET['log_type'] ) ? sanitize_text_field( $_GET['log_type'] ) : '';
$filter_level = isset( $_GET['log_level'] ) ? sanitize_text_field( $_GET['log_level'] ) : '';
$filter_provider = isset( $_GET['provider'] ) ? sanitize_text_field( $_GET['provider'] ) : '';
$current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$per_page = 50;

// Build filter args
$filter_args = array(
	'limit' => $per_page,
	'offset' => ( $current_page - 1 ) * $per_page,
);

if ( $filter_type ) {
	$filter_args['log_type'] = $filter_type;
}
if ( $filter_level ) {
	$filter_args['log_level'] = $filter_level;
}
if ( $filter_provider ) {
	$filter_args['provider'] = $filter_provider;
}

// Get logs
$logs = Debug_Logger::get_logs( $filter_args );
$total_logs = Debug_Logger::get_log_count( array_diff_key( $filter_args, array( 'limit' => '', 'offset' => '' ) ) );
$total_pages = ceil( $total_logs / $per_page );

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Debug Logs', 'layoutberg' ); ?></h1>
	
	<?php if ( ! Debug_Logger::is_enabled() ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( 'Debug logging is currently disabled.', 'layoutberg' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=layoutberg-settings#debug' ) ); ?>">
					<?php esc_html_e( 'Enable Debug Mode', 'layoutberg' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>
	
	<!-- Filters -->
	<div class="tablenav top">
		<form method="get" action="">
			<input type="hidden" name="page" value="layoutberg-debug" />
			
			<select name="log_type">
				<option value=""><?php esc_html_e( 'All Types', 'layoutberg' ); ?></option>
				<option value="api_request" <?php selected( $filter_type, 'api_request' ); ?>><?php esc_html_e( 'API Requests', 'layoutberg' ); ?></option>
				<option value="debug" <?php selected( $filter_type, 'debug' ); ?>><?php esc_html_e( 'Debug Messages', 'layoutberg' ); ?></option>
			</select>
			
			<select name="log_level">
				<option value=""><?php esc_html_e( 'All Levels', 'layoutberg' ); ?></option>
				<option value="info" <?php selected( $filter_level, 'info' ); ?>><?php esc_html_e( 'Info', 'layoutberg' ); ?></option>
				<option value="warning" <?php selected( $filter_level, 'warning' ); ?>><?php esc_html_e( 'Warning', 'layoutberg' ); ?></option>
				<option value="error" <?php selected( $filter_level, 'error' ); ?>><?php esc_html_e( 'Error', 'layoutberg' ); ?></option>
				<option value="debug" <?php selected( $filter_level, 'debug' ); ?>><?php esc_html_e( 'Debug', 'layoutberg' ); ?></option>
			</select>
			
			<select name="provider">
				<option value=""><?php esc_html_e( 'All Providers', 'layoutberg' ); ?></option>
				<option value="openai" <?php selected( $filter_provider, 'openai' ); ?>>OpenAI</option>
				<option value="claude" <?php selected( $filter_provider, 'claude' ); ?>>Claude</option>
			</select>
			
			<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'layoutberg' ); ?>" />
		</form>
		
		<form method="post" action="" style="float: right;">
			<?php wp_nonce_field( 'layoutberg_clear_logs', 'layoutberg_debug_nonce' ); ?>
			<select name="clear_days">
				<option value="0"><?php esc_html_e( 'Clear all logs', 'layoutberg' ); ?></option>
				<option value="7"><?php esc_html_e( 'Clear logs older than 7 days', 'layoutberg' ); ?></option>
				<option value="30"><?php esc_html_e( 'Clear logs older than 30 days', 'layoutberg' ); ?></option>
			</select>
			<input type="submit" name="clear_logs" class="button" value="<?php esc_attr_e( 'Clear Logs', 'layoutberg' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear logs?', 'layoutberg' ); ?>');" />
		</form>
	</div>
	
	<!-- Logs Table -->
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width: 150px;"><?php esc_html_e( 'Timestamp', 'layoutberg' ); ?></th>
				<th style="width: 80px;"><?php esc_html_e( 'Type', 'layoutberg' ); ?></th>
				<th style="width: 80px;"><?php esc_html_e( 'Level', 'layoutberg' ); ?></th>
				<th style="width: 80px;"><?php esc_html_e( 'Provider', 'layoutberg' ); ?></th>
				<th style="width: 100px;"><?php esc_html_e( 'Model', 'layoutberg' ); ?></th>
				<th><?php esc_html_e( 'Details', 'layoutberg' ); ?></th>
				<th style="width: 100px;"><?php esc_html_e( 'Tokens', 'layoutberg' ); ?></th>
				<th style="width: 100px;"><?php esc_html_e( 'Time (s)', 'layoutberg' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $logs ) ) : ?>
				<tr>
					<td colspan="8"><?php esc_html_e( 'No logs found.', 'layoutberg' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $logs as $log ) : ?>
					<tr>
						<td><?php echo esc_html( date_i18n( 'Y-m-d H:i:s', strtotime( $log['created_at'] ) ) ); ?></td>
						<td>
							<span class="log-type log-type-<?php echo esc_attr( $log['log_type'] ); ?>">
								<?php echo esc_html( $log['log_type'] ); ?>
							</span>
						</td>
						<td>
							<span class="log-level log-level-<?php echo esc_attr( $log['log_level'] ); ?>">
								<?php echo esc_html( $log['log_level'] ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $log['provider'] ?: '-' ); ?></td>
						<td><?php echo esc_html( $log['model'] ?: '-' ); ?></td>
						<td>
							<?php if ( $log['error_message'] ) : ?>
								<strong><?php esc_html_e( 'Error:', 'layoutberg' ); ?></strong> <?php echo esc_html( $log['error_message'] ); ?>
							<?php endif; ?>
							
							<?php if ( $log['request_data'] ) : ?>
								<details>
									<summary><?php esc_html_e( 'Request Data', 'layoutberg' ); ?></summary>
									<pre style="max-height: 200px; overflow: auto;"><?php echo esc_html( json_encode( $log['request_data'], JSON_PRETTY_PRINT ) ); ?></pre>
								</details>
							<?php endif; ?>
							
							<?php if ( $log['response_data'] ) : ?>
								<details>
									<summary><?php esc_html_e( 'Response Data', 'layoutberg' ); ?></summary>
									<pre style="max-height: 200px; overflow: auto;"><?php echo esc_html( json_encode( $log['response_data'], JSON_PRETTY_PRINT ) ); ?></pre>
								</details>
							<?php endif; ?>
						</td>
						<td><?php echo $log['tokens_used'] > 0 ? esc_html( number_format( $log['tokens_used'] ) ) : '-'; ?></td>
						<td><?php echo $log['processing_time'] > 0 ? esc_html( number_format( $log['processing_time'], 2 ) ) : '-'; ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	
	<!-- Pagination -->
	<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php echo sprintf( esc_html__( '%d items', 'layoutberg' ), $total_logs ); ?>
				</span>
				<span class="pagination-links">
					<?php
					$base_url = add_query_arg( array(
						'page' => 'layoutberg-debug',
						'log_type' => $filter_type,
						'log_level' => $filter_level,
						'provider' => $filter_provider,
					), admin_url( 'admin.php' ) );
					
					if ( $current_page > 1 ) {
						echo '<a class="prev-page" href="' . esc_url( add_query_arg( 'paged', $current_page - 1, $base_url ) ) . '">‹</a>';
					} else {
						echo '<span class="tablenav-pages-navspan" aria-hidden="true">‹</span>';
					}
					
					echo ' <span class="paging-input">';
					echo '<span class="current-page">' . $current_page . '</span>';
					echo '<span class="tablenav-paging-text"> of <span class="total-pages">' . $total_pages . '</span></span>';
					echo '</span> ';
					
					if ( $current_page < $total_pages ) {
						echo '<a class="next-page" href="' . esc_url( add_query_arg( 'paged', $current_page + 1, $base_url ) ) . '">›</a>';
					} else {
						echo '<span class="tablenav-pages-navspan" aria-hidden="true">›</span>';
					}
					?>
				</span>
			</div>
		</div>
	<?php endif; ?>
</div>

<style>
.log-type {
	padding: 2px 8px;
	border-radius: 3px;
	font-size: 12px;
}
.log-type-api_request {
	background: #e1f5fe;
	color: #0277bd;
}
.log-type-debug {
	background: #f3e5f5;
	color: #6a1b9a;
}
.log-level {
	padding: 2px 8px;
	border-radius: 3px;
	font-size: 12px;
	font-weight: bold;
}
.log-level-info {
	background: #e8f5e9;
	color: #2e7d32;
}
.log-level-warning {
	background: #fff3e0;
	color: #e65100;
}
.log-level-error {
	background: #ffebee;
	color: #c62828;
}
.log-level-debug {
	background: #e3f2fd;
	color: #1565c0;
}
details {
	margin-top: 5px;
}
details summary {
	cursor: pointer;
	color: #0073aa;
}
details summary:hover {
	color: #005177;
}
</style>