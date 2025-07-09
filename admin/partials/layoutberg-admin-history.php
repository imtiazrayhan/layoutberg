<?php
/**
 * Admin history page.
 *
 * @package    LayoutBerg
 * @subpackage Admin/Partials
 * @since      1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current user.
$user_id = get_current_user_id();

// Get database tables.
global $wpdb;
$table_generations = $wpdb->prefix . 'layoutberg_generations';

// Handle pagination.
$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$per_page = 20;
$offset = ( $paged - 1 ) * $per_page;

// Handle search.
$search_term = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

// Build query conditions.
$where_conditions = array( 'user_id = %d' );
$query_params = array( $user_id );

// Check history days limit for Starter plans
$history_days = \DotCamp\LayoutBerg\LayoutBerg_Licensing::get_history_days();
$is_limited = ( $history_days !== PHP_INT_MAX );

if ( $is_limited ) {
	// Add date restriction for Starter plans - show only last 30 days
	$date_limit = date( 'Y-m-d H:i:s', strtotime( '-' . $history_days . ' days' ) );
	$where_conditions[] = 'created_at >= %s';
	$query_params[] = $date_limit;
}

if ( ! empty( $search_term ) ) {
	$where_conditions[] = '(prompt LIKE %s OR status LIKE %s)';
	$search_like = '%' . $wpdb->esc_like( $search_term ) . '%';
	$query_params[] = $search_like;
	$query_params[] = $search_like;
}

$where_clause = implode( ' AND ', $where_conditions );

// Get total count for pagination.
$total_query = "SELECT COUNT(*) FROM {$table_generations} WHERE {$where_clause}";
$total_items = $wpdb->get_var( $wpdb->prepare( $total_query, $query_params ) );
$total_pages = ceil( $total_items / $per_page );

// If limited, also get total count without date restriction to show what they're missing
$total_all_time = 0;
if ( $is_limited ) {
	$total_all_query = "SELECT COUNT(*) FROM {$table_generations} WHERE user_id = %d";
	$total_all_time = $wpdb->get_var( $wpdb->prepare( $total_all_query, $user_id ) );
}

// Get generations with pagination.
$query_params[] = $per_page;
$query_params[] = $offset;

$generations_query = "
	SELECT * FROM {$table_generations} 
	WHERE {$where_clause} 
	ORDER BY created_at DESC 
	LIMIT %d OFFSET %d
";

$generations = $wpdb->get_results( $wpdb->prepare( $generations_query, $query_params ) );
?>

<div class="wrap layoutberg-history">
	<h1 class="wp-heading-inline">
		<?php echo esc_html( get_admin_page_title() ); ?>
	</h1>
	
	<hr class="wp-header-end">
	
	<?php if ( $is_limited ) : ?>
		<div class="notice notice-info inline">
			<p>
				<strong><?php esc_html_e( 'Showing last 30 days of history.', 'layoutberg' ); ?></strong>
				<?php
				$hidden_count = $total_all_time - $total_items;
				if ( $hidden_count > 0 ) {
					printf(
						/* translators: %d: number of hidden generations */
						esc_html__( 'You have %d older generations that are currently hidden.', 'layoutberg' ),
						$hidden_count
					);
					echo ' ';
				}
				printf(
					/* translators: %s: upgrade URL */
					esc_html__( 'Upgrade to Professional or Agency plan to see your complete generation history. %s', 'layoutberg' ),
					'<a href="' . esc_url( \DotCamp\LayoutBerg\LayoutBerg_Licensing::get_action_url() ) . '">' . esc_html__( 'Upgrade Now', 'layoutberg' ) . '</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>
	
	<!-- Search and Filters -->
	<div class="tablenav top">
		<div class="alignleft actions">
			<form method="get" class="search-form">
				<input type="hidden" name="page" value="layoutberg-history">
				<input type="search" 
					   name="s" 
					   value="<?php echo esc_attr( $search_term ); ?>" 
					   placeholder="<?php esc_attr_e( 'Search generations...', 'layoutberg' ); ?>"
					   class="wp-filter-search">
				<input type="submit" 
					   name="filter_action" 
					   class="button" 
					   value="<?php esc_attr_e( 'Search', 'layoutberg' ); ?>">
			</form>
		</div>
		
		<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav-pages">
			<span class="displaying-num">
				<?php
				printf(
					esc_html( _n( '%s generation', '%s generations', $total_items, 'layoutberg' ) ),
					number_format_i18n( $total_items )
				);
				?>
			</span>
			
			<span class="pagination-links">
				<?php
				$base_url = add_query_arg( array(
					'page' => 'layoutberg-history',
					's' => $search_term,
				), admin_url( 'admin.php' ) );
				
				if ( $paged > 1 ) {
					printf(
						'<a class="prev-page button" href="%s"><span aria-hidden="true">&lsaquo;</span></a>',
						esc_url( add_query_arg( 'paged', $paged - 1, $base_url ) )
					);
				} else {
					echo '<span class="prev-page button disabled"><span aria-hidden="true">&lsaquo;</span></span>';
				}
				
				printf(
					'<span class="paging-input">%s</span>',
					sprintf(
						esc_html__( '%1$s of %2$s', 'layoutberg' ),
						$paged,
						$total_pages
					)
				);
				
				if ( $paged < $total_pages ) {
					printf(
						'<a class="next-page button" href="%s"><span aria-hidden="true">&rsaquo;</span></a>',
						esc_url( add_query_arg( 'paged', $paged + 1, $base_url ) )
					);
				} else {
					echo '<span class="next-page button disabled"><span aria-hidden="true">&rsaquo;</span></span>';
				}
				?>
			</span>
		</div>
		<?php endif; ?>
		
		<br class="clear">
	</div>
	
	<!-- Generations List -->
	<?php if ( empty( $generations ) ) : ?>
		<div class="layoutberg-empty-state">
			<div class="layoutberg-empty-icon">
				<span class="dashicons dashicons-layout"></span>
			</div>
			<h3><?php esc_html_e( 'No generations yet', 'layoutberg' ); ?></h3>
			<p><?php esc_html_e( 'Start creating AI-powered layouts to see your generation history here.', 'layoutberg' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Create Your First Layout', 'layoutberg' ); ?>
			</a>
		</div>
	<?php else : ?>
		<div class="layoutberg-generations-list">
			<?php foreach ( $generations as $generation ) : ?>
				<div class="layoutberg-generation-item" data-generation-id="<?php echo esc_attr( $generation->id ); ?>">
					<div class="generation-content">
						<div class="generation-header">
							<h3 class="generation-prompt">
								<?php echo esc_html( wp_trim_words( $generation->prompt, 12, '...' ) ); ?>
							</h3>
							<div class="generation-status">
								<span class="status-badge status-<?php echo esc_attr( $generation->status ); ?>">
									<?php echo esc_html( ucfirst( $generation->status ) ); ?>
								</span>
							</div>
						</div>
						
						<div class="generation-meta">
							<span class="generation-date">
								<?php
								printf(
									esc_html__( 'Generated %s', 'layoutberg' ),
									human_time_diff( strtotime( $generation->created_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'layoutberg' )
								);
								?>
							</span>
							
							<?php if ( ! empty( $generation->model ) ) : ?>
								<span class="generation-model">
									<?php echo esc_html( $generation->model ); ?>
								</span>
							<?php endif; ?>
							
							<?php if ( ! empty( $generation->tokens_used ) ) : ?>
								<span class="generation-tokens">
									<?php
									printf(
										esc_html__( '%s tokens', 'layoutberg' ),
										number_format_i18n( $generation->tokens_used )
									);
									?>
								</span>
							<?php endif; ?>
						</div>
						
						<?php if ( ! empty( $generation->prompt ) && strlen( $generation->prompt ) > 80 ) : ?>
							<div class="generation-full-prompt">
								<strong><?php esc_html_e( 'Full Prompt:', 'layoutberg' ); ?></strong>
								<p><?php echo esc_html( $generation->prompt ); ?></p>
							</div>
						<?php endif; ?>
						
						<?php if ( ! empty( $generation->error_message ) ) : ?>
							<div class="generation-error">
								<strong><?php esc_html_e( 'Error:', 'layoutberg' ); ?></strong>
								<p><?php echo esc_html( $generation->error_message ); ?></p>
							</div>
						<?php endif; ?>
					</div>
					
					<div class="generation-actions">
						<?php if ( $generation->status === 'completed' && ! empty( $generation->result_data ) ) : ?>
							<button class="button button-secondary layoutberg-view-result" 
									data-generation-id="<?php echo esc_attr( $generation->id ); ?>">
								<?php esc_html_e( 'View Result', 'layoutberg' ); ?>
							</button>
							<button class="button button-primary layoutberg-use-result" 
									data-generation-id="<?php echo esc_attr( $generation->id ); ?>">
								<?php esc_html_e( 'Use in Editor', 'layoutberg' ); ?>
							</button>
						<?php endif; ?>
						
						<button class="button layoutberg-view-details" 
								data-generation-id="<?php echo esc_attr( $generation->id ); ?>">
							<?php esc_html_e( 'Details', 'layoutberg' ); ?>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	
	<!-- Bottom pagination -->
	<?php if ( $total_pages > 1 && ! empty( $generations ) ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<span class="pagination-links">
					<?php
					if ( $paged > 1 ) {
						printf(
							'<a class="prev-page button" href="%s"><span aria-hidden="true">&lsaquo;</span></a>',
							esc_url( add_query_arg( 'paged', $paged - 1, $base_url ) )
						);
					} else {
						echo '<span class="prev-page button disabled"><span aria-hidden="true">&lsaquo;</span></span>';
					}
					
					printf(
						'<span class="paging-input">%s</span>',
						sprintf(
							esc_html__( '%1$s of %2$s', 'layoutberg' ),
							$paged,
							$total_pages
						)
					);
					
					if ( $paged < $total_pages ) {
						printf(
							'<a class="next-page button" href="%s"><span aria-hidden="true">&rsaquo;</span></a>',
							esc_url( add_query_arg( 'paged', $paged + 1, $base_url ) )
						);
					} else {
						echo '<span class="next-page button disabled"><span aria-hidden="true">&rsaquo;</span></span>';
					}
					?>
				</span>
			</div>
		</div>
	<?php endif; ?>
</div>

<!-- Result Preview Modal -->
<div id="layoutberg-result-preview-modal" class="layoutberg-modal" style="display: none;">
	<div class="layoutberg-modal-content">
		<div class="layoutberg-modal-header">
			<h2 class="modal-title"><?php esc_html_e( 'Generation Result', 'layoutberg' ); ?></h2>
			<button type="button" class="layoutberg-modal-close">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="layoutberg-modal-body">
			<div class="result-preview-container">
				<div class="result-preview-content"></div>
			</div>
		</div>
		<div class="layoutberg-modal-footer">
			<button type="button" class="button layoutberg-modal-close"><?php esc_html_e( 'Close', 'layoutberg' ); ?></button>
			<button type="button" class="button button-primary layoutberg-use-result-modal"><?php esc_html_e( 'Use in Editor', 'layoutberg' ); ?></button>
		</div>
	</div>
</div>

<style>
/* History Page Styles */
.layoutberg-history .tablenav {
	margin-bottom: 20px;
}

.layoutberg-history .search-form {
	display: flex;
	align-items: center;
	gap: 8px;
}

.layoutberg-history .wp-filter-search {
	width: 280px;
}

.layoutberg-empty-state {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 60px 40px;
	text-align: center;
	margin-top: 20px;
	max-width: 600px;
	margin-left: auto;
	margin-right: auto;
}

.layoutberg-empty-icon {
	font-size: 64px;
	color: #ccc;
	margin-bottom: 20px;
}

.layoutberg-empty-state h3 {
	font-size: 24px;
	margin: 0 0 20px;
	color: #23282d;
}

.layoutberg-empty-state p {
	font-size: 16px;
	color: #666;
	margin-bottom: 30px;
}

.layoutberg-generations-list {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 4px;
	margin-top: 20px;
}

.layoutberg-generation-item {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	padding: 20px;
	border-bottom: 1px solid #eee;
	transition: background-color 0.2s;
}

.layoutberg-generation-item:last-child {
	border-bottom: none;
}

.layoutberg-generation-item:hover {
	background-color: #f8f9fa;
}

.generation-content {
	flex: 1;
	margin-right: 20px;
}

.generation-header {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	margin-bottom: 10px;
}

.generation-prompt {
	margin: 0;
	font-size: 16px;
	font-weight: 600;
	color: #23282d;
	flex: 1;
	margin-right: 15px;
}

.status-badge {
	padding: 4px 8px;
	border-radius: 3px;
	font-size: 12px;
	font-weight: 500;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.status-completed {
	background-color: #d4edda;
	color: #155724;
}

.status-failed {
	background-color: #f8d7da;
	color: #721c24;
}

.status-processing {
	background-color: #fff3cd;
	color: #856404;
}

.status-pending {
	background-color: #e2e3e5;
	color: #383d41;
}

.generation-meta {
	display: flex;
	flex-wrap: wrap;
	gap: 15px;
	font-size: 13px;
	color: #666;
	margin-bottom: 10px;
}

.generation-meta span {
	display: flex;
	align-items: center;
}

.generation-full-prompt,
.generation-error {
	margin-top: 15px;
	padding: 12px;
	background: #f8f9fa;
	border-left: 3px solid #007cba;
	border-radius: 0 4px 4px 0;
}

.generation-error {
	background: #fff5f5;
	border-left-color: #dc3545;
}

.generation-full-prompt p,
.generation-error p {
	margin: 5px 0 0;
	color: #555;
}

.generation-actions {
	display: flex;
	flex-direction: column;
	gap: 8px;
	min-width: 120px;
}

.generation-actions .button {
	text-align: center;
	white-space: nowrap;
}

/* Modal styles */
.layoutberg-modal {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	z-index: 999999;
	display: flex;
	align-items: center;
	justify-content: center;
}

.layoutberg-modal-content {
	background: #fff;
	border-radius: 4px;
	box-shadow: 0 2px 20px rgba(0, 0, 0, 0.2);
	max-width: 1000px;
	width: 90%;
	max-height: 90vh;
	display: flex;
	flex-direction: column;
}

.layoutberg-modal-header {
	padding: 20px;
	border-bottom: 1px solid #ddd;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.layoutberg-modal-header h2 {
	margin: 0;
	font-size: 20px;
}

.layoutberg-modal-close {
	background: none;
	border: none;
	font-size: 20px;
	cursor: pointer;
	color: #666;
	padding: 0;
	width: 30px;
	height: 30px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.layoutberg-modal-close:hover {
	color: #000;
}

.layoutberg-modal-body {
	padding: 20px;
	overflow-y: auto;
	flex: 1;
}

.layoutberg-modal-footer {
	padding: 20px;
	border-top: 1px solid #ddd;
	display: flex;
	justify-content: flex-end;
	align-items: center;
	gap: 15px;
}

.result-preview-container {
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 20px;
	background: #f9f9f9;
	min-height: 400px;
}

.result-preview-content {
	background: #fff;
	padding: 20px;
	border-radius: 4px;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Responsive design */
@media (max-width: 768px) {
	.layoutberg-generation-item {
		flex-direction: column;
		align-items: stretch;
	}
	
	.generation-content {
		margin-right: 0;
		margin-bottom: 15px;
	}
	
	.generation-actions {
		flex-direction: row;
		min-width: auto;
	}
	
	.generation-header {
		flex-direction: column;
		align-items: stretch;
	}
	
	.generation-prompt {
		margin-right: 0;
		margin-bottom: 10px;
	}
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// View result functionality
	document.addEventListener('click', function(e) {
		if (e.target.classList.contains('layoutberg-view-result')) {
			e.preventDefault();
			const generationId = e.target.getAttribute('data-generation-id');
			
			// Show modal
			const modal = document.getElementById('layoutberg-result-preview-modal');
			if (modal) {
				modal.style.display = 'flex';
				
				// Load result content
				loadGenerationResult(generationId);
			}
		}
	});
	
	// Use result functionality
	document.addEventListener('click', function(e) {
		if (e.target.classList.contains('layoutberg-use-result') || e.target.classList.contains('layoutberg-use-result-modal')) {
			e.preventDefault();
			const generationId = e.target.getAttribute('data-generation-id') || 
								 document.querySelector('.layoutberg-use-result-modal').getAttribute('data-generation-id');
			
			// Redirect to editor with generation result
			window.location.href = '<?php echo admin_url( 'post-new.php?post_type=page&layoutberg_generation=' ); ?>' + generationId;
		}
	});
	
	// View details functionality
	document.addEventListener('click', function(e) {
		if (e.target.classList.contains('layoutberg-view-details')) {
			e.preventDefault();
			const generationId = e.target.getAttribute('data-generation-id');
			
			// Redirect to generation details page
			window.location.href = '<?php echo admin_url( 'admin.php?page=layoutberg-generation-details&id=' ); ?>' + generationId;
		}
	});
	
	// Modal close functionality
	document.addEventListener('click', function(e) {
		if (e.target.classList.contains('layoutberg-modal-close') || 
			e.target.classList.contains('layoutberg-modal')) {
			const modal = e.target.closest('.layoutberg-modal') || e.target;
			if (modal && modal.classList.contains('layoutberg-modal')) {
				modal.style.display = 'none';
			}
		}
	});
	
	// Load generation result
	function loadGenerationResult(generationId) {
		const container = document.querySelector('.result-preview-content');
		if (!container) return;
		
		container.innerHTML = '<p>Loading result...</p>';
		
		// Make AJAX request to get generation result
		fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'layoutberg_get_generation_result',
				generation_id: generationId,
				_wpnonce: '<?php echo wp_create_nonce( 'layoutberg_nonce' ); ?>'
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success && data.data) {
				if (data.data.html_preview) {
					container.innerHTML = data.data.html_preview;
				} else if (data.data.content) {
					container.innerHTML = '<pre><code>' + data.data.content + '</code></pre>';
				} else {
					container.innerHTML = '<p>No preview available for this generation.</p>';
				}
				
				// Update use button
				const useButton = document.querySelector('.layoutberg-use-result-modal');
				if (useButton) {
					useButton.setAttribute('data-generation-id', generationId);
				}
			} else {
				container.innerHTML = '<p>Error loading result: ' + (data.data || 'Unknown error') + '</p>';
			}
		})
		.catch(error => {
			container.innerHTML = '<p>Error loading result: ' + error.message + '</p>';
		});
	}
});
</script>