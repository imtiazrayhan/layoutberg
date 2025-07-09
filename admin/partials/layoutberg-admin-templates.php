<?php
/**
 * Admin templates page.
 *
 * @package    LayoutBerg
 * @subpackage Admin/Partials
 * @since      1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get template manager instance
global $wpdb;
require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-template-manager.php';
$template_manager = new \DotCamp\LayoutBerg\Template_Manager();

// Handle actions
$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
$template_id = isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : 0;

// Handle delete action
if ( 'delete' === $action && $template_id && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'delete_template_' . $template_id ) ) {
	if ( $template_manager->delete_template( $template_id ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Template deleted successfully.', 'layoutberg' ) . '</p></div>';
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Failed to delete template.', 'layoutberg' ) . '</p></div>';
	}
}

// Get current filters
$current_category = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : '';
$search_term = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$paged = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'created_at';

// Get templates
$args = array(
	'category' => $current_category,
	'search'   => $search_term,
	'page'     => $paged,
	'per_page' => 20,
	'orderby'  => $orderby,
	'order'    => 'DESC',
);

// Add user filter for non-admins
if ( ! current_user_can( 'manage_options' ) ) {
	$args['user_id'] = get_current_user_id();
}

$result = $template_manager->get_templates( $args );
$templates = $result['templates'];
$total_templates = $result['total_items'];
$total_pages = $result['total_pages'];

// Convert arrays to objects for easier access in the template
$templates = array_map( function( $template ) {
	return (object) $template;
}, $templates );

// Get categories based on user's plan
$available_categories = $template_manager->get_categories();
// Add 'All Categories' option for filter
$categories = array_merge(
	array( 'all' => __( 'All Categories', 'layoutberg' ) ),
	$available_categories
);
?>

<div class="wrap layoutberg-templates">
	<div class="layoutberg-header-bar" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px;">
		<h1 class="wp-heading-inline" style="margin-bottom: 0;">
			<?php echo esc_html( get_admin_page_title() ); ?>
		</h1>
		<div class="layoutberg-header-actions" style="display: flex; gap: 12px;">
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=post&layoutberg_create_template=1' ) ); ?>" class="page-title-action layoutberg-new-template" title="<?php esc_attr_e( 'Create a new layout in the editor and save it as a template', 'layoutberg' ); ?>">
				<?php esc_html_e( 'Add New Template', 'layoutberg' ); ?>
			</a>
			<?php 
			// Check if user can import templates (Professional or Agency plan)
			// Using the same method as export since import/export are related features
			$can_import = \DotCamp\LayoutBerg\LayoutBerg_Licensing::can_export_templates();
			
			if ( $can_import ) : 
			?>
				<a href="#" class="page-title-action layoutberg-import-template">
					<?php esc_html_e( 'Import', 'layoutberg' ); ?>
				</a>
			<?php else : ?>
				<?php 
				// Show locked import option
				$is_expired = \DotCamp\LayoutBerg\LayoutBerg_Licensing::is_expired_monthly();
				$button_text = $is_expired 
					? __( 'Renew to import', 'layoutberg' )
					: __( 'Upgrade to import', 'layoutberg' );
				$button_url = \DotCamp\LayoutBerg\LayoutBerg_Licensing::get_action_url();
				?>
					<a href="<?php echo esc_url( $button_url ); ?>" class="page-title-action disabled" title="<?php echo esc_attr( $button_text ); ?>" style="opacity: 0.6; cursor: pointer;">
						<?php esc_html_e( 'Import', 'layoutberg' ); ?> <span class="dashicons dashicons-lock" style="font-size: 14px; vertical-align: middle; margin-left: 2px;"></span>
					</a>
			<?php endif; ?>
		</div>
	</div>

	<!-- Modern Filter Bar -->
	<div class="layoutberg-filter-bar" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); padding: 20px 24px; margin-bottom: 32px; display: flex; align-items: center; gap: 20px; flex-wrap: wrap; justify-content: space-between;">
		<div class="layoutberg-filter-group" style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
			<div class="layoutberg-search-container">
				<input type="text" id="template-search" name="s" value="<?php echo esc_attr( $search_term ); ?>" placeholder="<?php esc_attr_e( 'Search templates...', 'layoutberg' ); ?>" class="layoutberg-search">
				<button type="button" class="button layoutberg-search-btn">
					<span class="dashicons dashicons-search"></span>
				</button>
			</div>
			<select name="category" id="filter-by-category">
				<option value=""><?php esc_html_e( 'All Categories', 'layoutberg' ); ?></option>
				<?php foreach ( $categories as $key => $label ) : ?>
					<?php if ( 'all' === $key ) continue; ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_category, $key ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<select name="orderby" id="filter-by-order">
				<option value="created_at" <?php selected( isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'created_at', 'created_at' ); ?>>
					<?php esc_html_e( 'Date Created', 'layoutberg' ); ?>
				</option>
				<option value="name" <?php selected( isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'created_at', 'name' ); ?>>
					<?php esc_html_e( 'Name', 'layoutberg' ); ?>
				</option>
				<option value="usage_count" <?php selected( isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'created_at', 'usage_count' ); ?>>
					<?php esc_html_e( 'Most Used', 'layoutberg' ); ?>
				</option>
				<option value="updated_at" <?php selected( isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'created_at', 'updated_at' ); ?>>
					<?php esc_html_e( 'Last Modified', 'layoutberg' ); ?>
				</option>
			</select>
		</div>
		<div class="layoutberg-template-stats" style="background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb; padding: 10px 24px; display: flex; align-items: center; gap: 8px; min-width: 120px; justify-content: center;">
			<span class="stat-value" style="font-size: 22px; font-weight: 700; color: #111827; line-height: 1;"> <?php echo number_format_i18n( $total_templates ); ?> </span>
			<span class="stat-label" style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;"> <?php esc_html_e( 'Total Templates', 'layoutberg' ); ?> </span>
		</div>
	</div>

	<div style="height: 8px;"></div>

	<!-- Templates Grid -->
	<?php if ( empty( $templates ) ) : ?>
		<div class="layoutberg-templates-empty">
			<div class="empty-illustration">
				<span class="dashicons dashicons-layout" style="font-size: 64px; color: #94a3b8; margin-bottom: 16px; display: block;"></span>
			</div>
			<h3><?php esc_html_e( 'No templates yet', 'layoutberg' ); ?></h3>
			<p><?php esc_html_e( 'Templates let you save and reuse your AI-generated layouts.', 'layoutberg' ); ?></p>
			<p><?php esc_html_e( 'To create your first template:', 'layoutberg' ); ?></p>
			<ol>
				<li><?php esc_html_e( 'Click "Add New Template" to open the editor', 'layoutberg' ); ?></li>
				<li><?php esc_html_e( 'Add a LayoutBerg AI Layout block', 'layoutberg' ); ?></li>
				<li><?php esc_html_e( 'Generate a layout with your prompt', 'layoutberg' ); ?></li>
				<li><?php esc_html_e( 'Save it as a template for future use', 'layoutberg' ); ?></li>
			</ol>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=post&layoutberg_create_template=1' ) ); ?>" class="button button-primary button-hero">
				<?php esc_html_e( 'Create Your First Template', 'layoutberg' ); ?>
			</a>
		</div>
	<?php else : ?>
		<div class="layoutberg-templates-grid">
			<?php foreach ( $templates as $template ) : ?>
				<div class="layoutberg-template-card" data-template-id="<?php echo esc_attr( $template->id ); ?>">
					<div class="template-preview">
						<?php if ( ! empty( $template->thumbnail_url ) ) : ?>
							<img src="<?php echo esc_url( $template->thumbnail_url ); ?>" alt="<?php echo esc_attr( $template->name ); ?>">
						<?php else : ?>
							<div class="template-placeholder">
								<span class="dashicons dashicons-layout"></span>
							</div>
						<?php endif; ?>
						
						<div class="template-actions">
							<button class="button button-primary layoutberg-use-template" data-template-id="<?php echo esc_attr( $template->id ); ?>">
								<?php esc_html_e( 'Use Template', 'layoutberg' ); ?>
							</button>
							<button class="button layoutberg-preview-template" data-template-id="<?php echo esc_attr( $template->id ); ?>">
								<?php esc_html_e( 'Preview', 'layoutberg' ); ?>
							</button>
						</div>
					</div>
					
					<div class="template-details">
						<h3 class="template-name"><?php echo esc_html( $template->name ); ?></h3>
						
						<?php if ( ! empty( $template->description ) ) : ?>
							<p class="template-description"><?php echo esc_html( $template->description ); ?></p>
						<?php endif; ?>
						
						<div class="template-meta">
							<span class="template-category <?php echo esc_attr( $template->category ); ?>">
								<?php echo esc_html( $categories[ $template->category ] ?? $template->category ); ?>
							</span>
							
							<?php if ( $template->usage_count > 0 ) : ?>
								<span class="template-usage">
									<span class="usage-icon">📊</span>
									<?php
									printf(
										esc_html( _n( '%s use', '%s uses', $template->usage_count, 'layoutberg' ) ),
										number_format_i18n( $template->usage_count )
									);
									?>
								</span>
							<?php endif; ?>
							
							<?php if ( ! empty( $template->tags ) ) : ?>
								<span class="template-tags">
									<span class="tags-icon">🏷️</span>
									<?php 
									$tags = is_array( $template->tags ) ? $template->tags : explode( ',', $template->tags );
									$tags = array_slice( $tags, 0, 2 ); // Show only first 2 tags
									echo esc_html( implode( ', ', $tags ) );
									if ( count( is_array( $template->tags ) ? $template->tags : explode( ',', $template->tags ) ) > 2 ) {
										echo ' +' . ( count( is_array( $template->tags ) ? $template->tags : explode( ',', $template->tags ) ) - 2 );
									}
									?>
								</span>
							<?php endif; ?>
						</div>
						
						<div class="template-footer">
							<span class="template-date">
								<?php
								printf(
									esc_html__( 'Created %s', 'layoutberg' ),
									human_time_diff( strtotime( $template->created_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'layoutberg' )
								);
								?>
							</span>
							
							<?php if ( current_user_can( 'layoutberg_manage_templates' ) && ( current_user_can( 'manage_options' ) || $template->created_by == get_current_user_id() ) ) : ?>
								<span class="template-actions-footer">
									<a href="#" class="edit-template" data-template-id="<?php echo esc_attr( $template->id ); ?>">
										<?php esc_html_e( 'Edit', 'layoutberg' ); ?>
									</a>
									|
									<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'template_id' => $template->id ), admin_url( 'admin.php?page=layoutberg-templates' ) ), 'delete_template_' . $template->id ) ); ?>" class="delete-template" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this template?', 'layoutberg' ); ?>');">
										<?php esc_html_e( 'Delete', 'layoutberg' ); ?>
									</a>
									|
									<?php 
									// Check if user can export templates (Professional or Agency plan)
									$can_export = false;
									if ( function_exists( 'layoutberg_fs' ) ) {
										$can_export = \layoutberg_fs()->can_use_premium_code() && 
													 ( \layoutberg_fs()->is_plan('professional') || \layoutberg_fs()->is_plan('agency') );
									}
									
									if ( $can_export ) : 
									?>
										<a href="#" class="export-template" data-template-id="<?php echo esc_attr( $template->id ); ?>">
											<?php esc_html_e( 'Export', 'layoutberg' ); ?>
										</a>
									<?php else : ?>
										<?php 
										// Show locked export option if Freemius is available
										if ( function_exists( 'layoutberg_fs' ) ) :
											$button_text = ! \layoutberg_fs()->can_use_premium_code() 
												? __( 'Renew to export', 'layoutberg' )
												: __( 'Upgrade to export', 'layoutberg' );
											$button_url = ! \layoutberg_fs()->can_use_premium_code() 
												? \layoutberg_fs()->get_account_url() 
												: \layoutberg_fs()->get_upgrade_url();
										?>
											<a href="<?php echo esc_url( $button_url ); ?>" class="export-template-locked" title="<?php echo esc_attr( $button_text ); ?>">
												<?php esc_html_e( 'Export', 'layoutberg' ); ?> <span class="dashicons dashicons-lock" style="font-size: 12px; vertical-align: middle;"></span>
											</a>
										<?php endif; ?>
									<?php endif; ?>
								</span>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<!-- Template Preview Modal -->
<div id="layoutberg-template-preview-modal" class="layoutberg-modal" style="display: none;">
	<div class="layoutberg-modal-content">
		<div class="layoutberg-modal-header">
			<h2 class="modal-title"><?php esc_html_e( 'Template Preview', 'layoutberg' ); ?></h2>
			<button type="button" class="layoutberg-modal-close">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="layoutberg-modal-body">
			<div class="template-preview-container">
				<div id="layoutberg-gutenberg-preview-container"></div>
			</div>
		</div>
		<div class="layoutberg-modal-footer">
			<button type="button" class="button layoutberg-modal-close"><?php esc_html_e( 'Close', 'layoutberg' ); ?></button>
			<button type="button" class="button button-primary layoutberg-use-template-modal"><?php esc_html_e( 'Use This Template', 'layoutberg' ); ?></button>
		</div>
	</div>
</div>

<!-- Edit Template Modal -->
<div id="layoutberg-template-edit-modal" class="layoutberg-modal" style="display: none;">
	<div class="layoutberg-modal-content">
		<div class="layoutberg-modal-header">
			<h2 class="modal-title"><?php esc_html_e( 'Edit Template', 'layoutberg' ); ?></h2>
			<button type="button" class="layoutberg-modal-close">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="layoutberg-modal-body">
			<form id="layoutberg-edit-template-form">
				<div class="form-field">
					<label for="template-name"><?php esc_html_e( 'Template Name', 'layoutberg' ); ?></label>
					<input type="text" id="template-name" name="name" required>
				</div>
				
				<div class="form-field">
					<label for="template-description"><?php esc_html_e( 'Description', 'layoutberg' ); ?></label>
					<textarea id="template-description" name="description" rows="3"></textarea>
				</div>
				
				<div class="form-field">
					<label for="template-category"><?php esc_html_e( 'Category', 'layoutberg' ); ?></label>
					<select id="template-category" name="category">
						<?php foreach ( $available_categories as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				
				<div class="form-field">
					<label for="template-tags"><?php esc_html_e( 'Tags', 'layoutberg' ); ?></label>
					<input type="text" id="template-tags" name="tags" placeholder="<?php esc_attr_e( 'Separate tags with commas', 'layoutberg' ); ?>">
				</div>
				
				<div class="form-field">
					<label>
						<input type="checkbox" id="template-public" name="is_public" value="1">
						<?php esc_html_e( 'Make this template public', 'layoutberg' ); ?>
					</label>
				</div>
				
				<input type="hidden" id="template-id" name="template_id">
			</form>
		</div>
		<div class="layoutberg-modal-footer">
			<button type="button" class="button layoutberg-modal-close"><?php esc_html_e( 'Cancel', 'layoutberg' ); ?></button>
			<button type="button" class="button button-primary" id="save-template-changes"><?php esc_html_e( 'Save Changes', 'layoutberg' ); ?></button>
		</div>
	</div>
</div>

<!-- Import Template Modal -->
<div id="layoutberg-template-import-modal" class="layoutberg-modal" style="display: none;">
	<div class="layoutberg-modal-content">
		<div class="layoutberg-modal-header">
			<h2 class="modal-title"><?php esc_html_e( 'Import Template', 'layoutberg' ); ?></h2>
			<button type="button" class="layoutberg-modal-close">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="layoutberg-modal-body">
			<form id="layoutberg-import-template-form">
				<div class="form-field">
					<label for="import-file"><?php esc_html_e( 'Select Template File', 'layoutberg' ); ?></label>
					<input type="file" id="import-file" name="import_file" accept=".json" required>
					<p class="description"><?php esc_html_e( 'Upload a JSON file exported from LayoutBerg.', 'layoutberg' ); ?></p>
				</div>
			</form>
		</div>
		<div class="layoutberg-modal-footer">
			<button type="button" class="button layoutberg-modal-close"><?php esc_html_e( 'Cancel', 'layoutberg' ); ?></button>
			<button type="button" class="button button-primary" id="import-template"><?php esc_html_e( 'Import Template', 'layoutberg' ); ?></button>
		</div>
	</div>
</div>

<style>
/* Templates Grid - Enhanced Phase 1 Design */
.layoutberg-templates-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
	gap: 24px;
	margin-top: 24px;
	padding: 0;
}

.layoutberg-template-card {
	background: white;
	border: 1px solid #f3f4f6;
	border-radius: 12px;
	overflow: hidden;
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
	position: relative;
}

.layoutberg-template-card:hover {
	transform: translateY(-4px);
	box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
	border-color: #e5e7eb;
}

.template-preview {
	position: relative;
	padding-top: 60%;
	background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
	overflow: hidden;
}

.template-preview img {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	object-fit: cover;
}

.template-placeholder {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.template-placeholder .dashicons {
	font-size: 48px;
	color: #94a3b8;
	opacity: 0.7;
}

.template-actions {
	position: absolute;
	top: 12px;
	right: 12px;
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(8px);
	display: flex;
	flex-direction: column;
	align-items: stretch;
	gap: 6px;
	padding: 8px;
	border-radius: 8px;
	box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
	opacity: 0;
	transition: all 0.2s ease;
	z-index: 10;
}

.layoutberg-template-card:hover .template-actions,
.template-actions:hover {
	opacity: 1;
}

.template-actions .button {
	font-size: 12px;
	padding: 6px 10px;
	height: auto;
	line-height: 1.2;
	white-space: nowrap;
	cursor: pointer;
	border-radius: 6px;
	font-weight: 500;
	transition: all 0.2s ease;
	border: 1px solid transparent;
}

.template-actions .button-primary {
	background: #6366f1;
	border-color: #6366f1;
	color: white;
}

.template-actions .button-primary:hover {
	background: #4f46e5;
	border-color: #4f46e5;
	transform: translateY(-1px);
}

.template-actions .button:not(.button-primary) {
	background: white;
	border-color: #d1d5db;
	color: #374151;
}

.template-actions .button:not(.button-primary):hover {
	background: #f9fafb;
	border-color: #9ca3af;
	transform: translateY(-1px);
}

/* Show actions on touch devices */
@media (hover: none) and (pointer: coarse) {
	.template-actions {
		opacity: 0.9;
	}
}

.template-details {
	padding: 20px;
}

.template-name {
	margin: 0 0 12px;
	font-size: 16px;
	font-weight: 600;
	color: #111827;
	line-height: 1.4;
}

.template-description {
	margin: 0 0 12px;
	color: #6b7280;
	font-size: 14px;
	line-height: 1.5;
}

.template-meta {
	display: flex;
	gap: 12px;
	margin-bottom: 12px;
	font-size: 12px;
	color: #9ca3af;
	align-items: center;
}

.template-category {
	background: #f3f4f6;
	color: #374151;
	padding: 4px 8px;
	border-radius: 12px;
	font-size: 11px;
	font-weight: 500;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	display: inline-flex;
	align-items: center;
	gap: 4px;
}

.template-category.business { 
	background: #dbeafe; 
	color: #1e40af; 
}

.template-category.creative { 
	background: #fef3c7; 
	color: #92400e; 
}

.template-category.ecommerce { 
	background: #dcfce7; 
	color: #166534; 
}

.template-category.portfolio { 
	background: #f3e8ff; 
	color: #7c3aed; 
}

.template-category.landing { 
	background: #fef2f2; 
	color: #dc2626; 
}

.template-category.blog { 
	background: #ecfdf5; 
	color: #059669; 
}

.template-category.general { 
	background: #f1f5f9; 
	color: #475569; 
}

.template-category.custom { 
	background: #fef7cd; 
	color: #a16207; 
}

.template-usage {
	display: flex;
	align-items: center;
	gap: 4px;
	color: #6b7280;
	font-size: 11px;
}

.template-usage .usage-icon {
	font-size: 10px;
}

.template-tags {
	display: flex;
	align-items: center;
	gap: 4px;
	color: #6b7280;
	font-size: 11px;
}

.template-tags .tags-icon {
	font-size: 10px;
}

.template-footer {
	display: flex;
	justify-content: space-between;
	align-items: center;
	font-size: 12px;
	color: #9ca3af;
	padding-top: 12px;
	border-top: 1px solid #f3f4f6;
}

.template-date {
	display: flex;
	align-items: center;
	gap: 4px;
}

.template-date::before {
	content: '🕒';
	font-size: 10px;
}

.template-actions-footer {
	display: flex;
	align-items: center;
	gap: 8px;
}

.template-actions-footer a {
	color: #6366f1;
	text-decoration: none;
	font-weight: 500;
	transition: color 0.2s ease;
}

.template-actions-footer a:hover {
	color: #4f46e5;
	text-decoration: underline;
}

.template-actions-footer .export-template-locked {
	opacity: 0.7;
	color: #9ca3af;
	cursor: pointer;
}

.template-actions-footer .export-template-locked:hover {
	opacity: 1;
	color: #6366f1;
	cursor: pointer;
}

/* Enhanced Empty State */
.layoutberg-templates-empty {
	background: white;
	border: 2px dashed #e5e7eb;
	border-radius: 16px;
	padding: 80px 40px;
	text-align: center;
	margin-top: 24px;
	max-width: 600px;
	margin-left: auto;
	margin-right: auto;
	transition: all 0.3s ease;
}

.layoutberg-templates-empty:hover {
	border-color: #d1d5db;
	background: #fafafa;
}

.layoutberg-templates-empty h3 {
	font-size: 28px;
	margin: 0 0 16px;
	color: #111827;
	font-weight: 700;
}

.layoutberg-templates-empty p {
	font-size: 16px;
	color: #6b7280;
	margin-bottom: 16px;
	line-height: 1.6;
}

.layoutberg-templates-empty ol {
	text-align: left;
	display: inline-block;
	margin: 24px 0 32px;
	font-size: 14px;
	color: #4b5563;
	line-height: 1.8;
}

.layoutberg-templates-empty ol li {
	margin-bottom: 8px;
}

.layoutberg-templates-empty .button-hero {
	font-size: 16px;
	padding: 14px 32px;
	height: auto;
	border-radius: 8px;
	font-weight: 600;
	background: #6366f1;
	border-color: #6366f1;
	transition: all 0.2s ease;
}

.layoutberg-templates-empty .button-hero:hover {
	background: #4f46e5;
	border-color: #4f46e5;
	transform: translateY(-2px);
	box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
}

/* Enhanced Filter Bar - Phase 2 */
.tablenav.top {
	background: white;
	border: 1px solid #e5e7eb;
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 24px;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	display: flex;
	justify-content: space-between;
	align-items: center;
	flex-wrap: wrap;
	gap: 16px;
}

.tablenav.top .alignleft {
	display: flex;
	align-items: center;
	gap: 12px;
	flex-wrap: wrap;
}

/* Enhanced Search Container */
.layoutberg-search-container {
	position: relative;
	display: flex;
	align-items: center;
}

/* Searching indicator */
.layoutberg-search-container.searching::after {
	content: '';
	position: absolute;
	right: 40px;
	top: 50%;
	transform: translateY(-50%);
	width: 16px;
	height: 16px;
	border: 2px solid #e5e7eb;
	border-top-color: #6366f1;
	border-radius: 50%;
	animation: spin 0.8s linear infinite;
}

.layoutberg-search {
	border: 1px solid #d1d5db;
	border-radius: 6px;
	padding: 8px 40px 8px 12px;
	font-size: 14px;
	background: white;
	transition: all 0.2s ease;
	min-width: 200px;
}

.layoutberg-search:focus {
	border-color: #6366f1;
	outline: none;
	box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.layoutberg-search-btn {
	position: absolute;
	right: 4px;
	top: 50%;
	transform: translateY(-50%);
	background: none;
	border: none;
	color: #6b7280;
	padding: 4px;
	border-radius: 4px;
	cursor: pointer;
	transition: all 0.2s ease;
}

.layoutberg-search-btn:hover {
	color: #6366f1;
	background: #f3f4f6;
}

/* Template Statistics */
.layoutberg-template-stats {
	display: flex;
	align-items: center;
	gap: 20px;
	padding: 12px 16px;
	background: #f8fafc;
	border-radius: 6px;
	border: 1px solid #e5e7eb;
}

.layoutberg-template-stats .stat-item {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 4px;
}

.layoutberg-template-stats .stat-value {
	font-size: 18px;
	font-weight: 700;
	color: #111827;
	line-height: 1;
}

.layoutberg-template-stats .stat-label {
	font-size: 11px;
	color: #6b7280;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	font-weight: 500;
}

.tablenav.top select {
	border: 1px solid #d1d5db;
	border-radius: 6px;
	padding: 8px 12px;
	font-size: 14px;
	background: white;
	transition: border-color 0.2s ease;
}

.tablenav.top select:focus {
	border-color: #6366f1;
	outline: none;
	box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.tablenav.top .button {
	border-radius: 6px;
	padding: 8px 16px;
	font-weight: 500;
	transition: all 0.2s ease;
}

.tablenav.top .button:hover {
	transform: translateY(-1px);
}

.tablenav-pages {
	display: flex;
	align-items: center;
	gap: 16px;
}

.displaying-num {
	font-weight: 500;
	color: #374151;
}

.pagination-links {
	display: flex;
	align-items: center;
	gap: 8px;
}

.pagination-links .button {
	border-radius: 6px;
	padding: 6px 12px;
	min-width: 32px;
	height: 32px;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.2s ease;
}

.pagination-links .button:hover:not(.disabled) {
	transform: translateY(-1px);
}

.paging-input {
	font-weight: 500;
	color: #374151;
}

/* Responsive Design */
@media (max-width: 768px) {
	.layoutberg-templates-grid {
		grid-template-columns: 1fr;
		gap: 16px;
		margin-top: 16px;
	}
	
	.template-details {
		padding: 16px;
	}
	
	.template-name {
		font-size: 15px;
	}
	
	.template-description {
		font-size: 13px;
	}
	
	.template-meta {
		flex-direction: column;
		align-items: flex-start;
		gap: 8px;
	}
	
	.template-footer {
		flex-direction: column;
		align-items: flex-start;
		gap: 8px;
	}
	
	.template-actions-footer {
		width: 100%;
		justify-content: flex-start;
	}
	
	.layoutberg-templates-empty {
		padding: 60px 24px;
		margin-top: 16px;
	}
	
	.layoutberg-templates-empty h3 {
		font-size: 24px;
	}
	
	.layoutberg-templates-empty p {
		font-size: 14px;
	}
	
	/* Phase 2 Mobile Responsive */
	.tablenav.top {
		padding: 16px;
		margin-bottom: 16px;
		flex-direction: column;
		align-items: stretch;
		gap: 12px;
	}
	
	.tablenav.top .alignleft {
		flex-direction: column;
		align-items: stretch;
		gap: 8px;
	}
	
	.layoutberg-search-container {
		width: 100%;
	}
	
	.layoutberg-search {
		width: 100%;
		min-width: auto;
	}
	
	.tablenav.top select,
	.tablenav.top .button {
		width: 100%;
	}
	
	.layoutberg-template-stats {
		flex-direction: column;
		gap: 12px;
		padding: 16px;
	}
	
	.layoutberg-template-stats .stat-item {
		flex-direction: row;
		gap: 8px;
	}
	
	.layoutberg-template-stats .stat-value {
		font-size: 16px;
	}
	
	.layoutberg-template-stats .stat-label {
		font-size: 12px;
	}
}

@media (max-width: 480px) {
	.layoutberg-templates-grid {
		gap: 12px;
	}
	
	.template-details {
		padding: 12px;
	}
	
	.template-name {
		font-size: 14px;
	}
	
	.template-description {
		font-size: 12px;
	}
	
	.layoutberg-templates-empty {
		padding: 40px 16px;
	}
	
	.layoutberg-templates-empty h3 {
		font-size: 20px;
	}
}

/* Modals - Enhanced Design */
.layoutberg-modal {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.6);
	backdrop-filter: blur(4px);
	z-index: 999999;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 20px;
}

.layoutberg-modal[style*="display: none"] {
	display: none !important;
}

.layoutberg-modal[style*="display: block"],
.layoutberg-modal.show {
	display: flex !important;
	visibility: visible !important;
	opacity: 1 !important;
}

.layoutberg-modal-content {
	background: white;
	border-radius: 12px;
	box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
	max-width: 1200px;
	width: 95%;
	max-height: 95vh;
	display: flex;
	flex-direction: column;
	overflow: hidden;
}

.layoutberg-modal-header {
	padding: 24px;
	border-bottom: 1px solid #e5e7eb;
	display: flex;
	justify-content: space-between;
	align-items: center;
	background: #fafafa;
}

.layoutberg-modal-header h2 {
	margin: 0;
	font-size: 20px;
	font-weight: 600;
	color: #111827;
}

.layoutberg-modal-close {
	background: none;
	border: none;
	font-size: 20px;
	cursor: pointer;
	color: #6b7280;
	padding: 8px;
	width: 36px;
	height: 36px;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 6px;
	transition: all 0.2s ease;
}

.layoutberg-modal-close:hover {
	color: #111827;
	background: #f3f4f6;
}

.layoutberg-modal-body {
	padding: 24px;
	overflow-y: auto;
	flex: 1;
}

.layoutberg-modal-footer {
	padding: 24px;
	border-top: 1px solid #e5e7eb;
	display: flex;
	justify-content: flex-end;
	align-items: center;
	gap: 12px;
	background: #fafafa;
}

.layoutberg-modal-footer .button {
	margin: 0;
	min-width: 100px;
	border-radius: 6px;
	font-weight: 500;
	transition: all 0.2s ease;
}

.layoutberg-modal-footer .button:hover {
	transform: translateY(-1px);
}

/* Form Fields - Enhanced */
.form-field {
	margin-bottom: 20px;
}

.form-field label {
	display: block;
	margin-bottom: 6px;
	font-weight: 600;
	color: #374151;
	font-size: 14px;
}

.form-field input[type="text"],
.form-field input[type="file"],
.form-field textarea,
.form-field select {
	width: 100%;
	padding: 10px 12px;
	border: 1px solid #d1d5db;
	border-radius: 6px;
	font-size: 14px;
	transition: all 0.2s ease;
	background: white;
}

.form-field input[type="text"]:focus,
.form-field input[type="file"]:focus,
.form-field textarea:focus,
.form-field select:focus {
	border-color: #6366f1;
	outline: none;
	box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-field .description {
	margin-top: 6px;
	color: #6b7280;
	font-size: 13px;
	line-height: 1.4;
}

/* Template Preview - Enhanced */
.template-preview-container {
	height: 100%;
	display: flex;
	flex-direction: column;
}

#layoutberg-gutenberg-preview-container {
	flex: 1;
	min-height: 500px;
	border: 1px solid #e5e7eb;
	border-radius: 8px;
	overflow: hidden;
}

.template-visual-preview {
	padding: 24px;
	background: white;
	min-height: 400px;
	border-radius: 8px;
}

.template-code-preview {
	background: #f8fafc;
	padding: 20px;
	border-radius: 8px;
	margin-top: 16px;
}

.template-code-preview pre {
	background: #1f2937;
	color: #f9fafb;
	padding: 16px;
	border-radius: 6px;
	overflow-x: auto;
	max-height: 400px;
	overflow-y: auto;
	margin: 0;
	font-size: 13px;
	line-height: 1.5;
}

.template-loading {
	display: flex;
	align-items: center;
	justify-content: center;
	min-height: 200px;
	color: #6b7280;
	font-style: italic;
}

.template-loading::before {
	content: '';
	display: inline-block;
	width: 20px;
	height: 20px;
	border: 2px solid #e5e7eb;
	border-top-color: #6366f1;
	border-radius: 50%;
	animation: spin 1s linear infinite;
	margin-right: 12px;
}

@keyframes spin {
	to {
		transform: rotate(360deg);
	}
}

/* Loading Overlay */
.layoutberg-loading-overlay {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(255, 255, 255, 0.9);
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	z-index: 100;
	border-radius: 12px;
}

.layoutberg-loading-overlay .layoutberg-spinner {
	width: 32px;
	height: 32px;
	border: 3px solid #e5e7eb;
	border-top-color: #6366f1;
	border-radius: 50%;
	animation: spin 1s linear infinite;
	margin-bottom: 12px;
}

.layoutberg-loading-overlay p {
	margin: 0;
	color: #6b7280;
	font-size: 14px;
	font-weight: 500;
}

.template-visual-preview iframe {
	width: 100%;
	border: none;
	min-height: 400px;
	border-radius: 8px;
	background: white;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.template-preview-info {
	margin-bottom: 24px;
	padding-bottom: 20px;
	border-bottom: 1px solid #e5e7eb;
}

.template-preview-info h3 {
	margin: 0 0 12px;
	font-size: 20px;
	font-weight: 600;
	color: #111827;
}

.template-preview-info .template-meta {
	display: flex;
	gap: 20px;
	font-size: 14px;
	color: #6b7280;
}

.template-preview-info .template-meta span {
	display: block;
}

.template-code-preview {
	margin-top: 20px;
}

.template-code-preview h4 {
	margin: 0 0 12px;
	font-size: 16px;
	font-weight: 600;
	color: #111827;
}

.template-code-preview pre {
	background: #1f2937;
	color: #f9fafb;
	padding: 16px;
	border-radius: 6px;
	overflow-x: auto;
	max-height: 300px;
	overflow-y: auto;
	font-size: 13px;
	line-height: 1.5;
}

.template-code-preview code {
	font-size: 12px;
	line-height: 1.5;
}
</style>

<script>
// Ensure ajaxurl is defined for WordPress admin
if (typeof ajaxurl === 'undefined') {
	window.ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
}

document.addEventListener('DOMContentLoaded', function() {
	// Helper function to make AJAX requests
	function makeAjaxRequest(url, options) {
		return new Promise((resolve, reject) => {
			const xhr = new XMLHttpRequest();
			const method = options.method || 'GET';
			let requestUrl = url;
			let requestData = null;
			
			if (method === 'GET' && options.data && typeof options.data === 'object') {
				// For GET requests, append data to URL
				const params = new URLSearchParams();
				for (const key in options.data) {
					params.append(key, options.data[key]);
				}
				requestUrl += (url.includes('?') ? '&' : '?') + params.toString();
			} else if (method === 'POST' && options.data) {
				if (options.data instanceof FormData) {
					requestData = options.data;
				} else if (typeof options.data === 'object') {
					const formData = new URLSearchParams();
					for (const key in options.data) {
						formData.append(key, options.data[key]);
					}
					requestData = formData.toString();
				}
			}
			
			// Open the connection FIRST
			xhr.open(method, requestUrl);
			
			// Set headers AFTER opening the connection
			if (method === 'POST' && !(options.data instanceof FormData)) {
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			}
			
			xhr.onload = function() {
				if (xhr.status >= 200 && xhr.status < 300) {
					try {
						const response = JSON.parse(xhr.responseText);
						resolve(response);
					} catch (e) {
						resolve(xhr.responseText);
					}
				} else {
					reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
				}
			};
			
			xhr.onerror = function() {
				reject(new Error('Network error'));
			};
			
			if (options.beforeSend) {
				options.beforeSend();
			}
			
			console.log('Making request to:', requestUrl);
			console.log('Request method:', method);
			console.log('Request data:', requestData);
			
			xhr.send(requestData);
		});
	}
	
	// Helper function to show modal
	function showModal(modalId) {
		console.log('Attempting to show modal:', modalId);
		const modal = document.getElementById(modalId);
		console.log('Modal element found:', modal);
		
		if (modal) {
			console.log('Modal initial display:', getComputedStyle(modal).display);
			console.log('Modal computed styles:', {
				position: getComputedStyle(modal).position,
				zIndex: getComputedStyle(modal).zIndex,
				visibility: getComputedStyle(modal).visibility,
				opacity: getComputedStyle(modal).opacity
			});
			
			modal.style.display = 'flex';
			modal.style.zIndex = '999999999';
			modal.style.visibility = 'visible';
			modal.style.opacity = '1';
			modal.classList.add('show');
			
			console.log('Modal display after show:', getComputedStyle(modal).display);
			console.log('Modal is visible:', modal.offsetWidth > 0 && modal.offsetHeight > 0);
			
			return modal;
		} else {
			console.error('Modal not found:', modalId);
			return null;
		}
	}
	
	// Helper function to hide modal
	function hideModal(modal) {
		if (modal) {
			modal.style.display = 'none';
			modal.style.visibility = 'hidden';
			modal.style.opacity = '0';
			modal.classList.remove('show');
		}
	}
	
	// Enhanced Filter handling
	const filterSelect = document.getElementById('filter-by-category');
	const orderSelect = document.getElementById('filter-by-order');
	const searchInput = document.getElementById('template-search');
	const searchBtn = document.querySelector('.layoutberg-search-btn');
	
	function getFilterParams() {
		return {
			category: filterSelect ? filterSelect.value : '',
			s: searchInput ? searchInput.value.trim() : '',
			orderby: orderSelect ? orderSelect.value : 'created_at',
			paged: 1
		};
	}
	
	function updateTemplatesGridAJAX() {
		const params = getFilterParams();
		const url = new URL(typeof ajaxurl !== 'undefined' ? ajaxurl : '<?php echo admin_url( 'admin-ajax.php' ); ?>', window.location.origin);
		url.searchParams.set('action', 'layoutberg_render_templates_grid');
		Object.entries(params).forEach(([key, value]) => {
			if (value) url.searchParams.set(key, value);
			else url.searchParams.delete(key);
		});

		const gridContainer = document.querySelector('.layoutberg-templates-grid') || document.querySelector('.layoutberg-templates-empty');
		const searchContainer = document.querySelector('.layoutberg-search-container');
		
		// Add searching indicator to search input
		if (searchContainer) {
			searchContainer.classList.add('searching');
		}
		
		if (gridContainer) {
			const loadingOverlay = document.createElement('div');
			loadingOverlay.className = 'layoutberg-loading-overlay';
			loadingOverlay.innerHTML = '<div class="layoutberg-spinner"></div><p>Searching...</p>';
			gridContainer.parentNode.insertBefore(loadingOverlay, gridContainer);
			gridContainer.style.opacity = '0.5';
		}

		fetch(url.toString(), { credentials: 'same-origin' })
			.then(res => res.json())
			.then(data => {
				if (data.success && data.data && data.data.html) {
					const grid = document.querySelector('.layoutberg-templates-grid') || document.querySelector('.layoutberg-templates-empty');
					if (grid) {
						const wrapper = document.createElement('div');
						wrapper.innerHTML = data.data.html;
						grid.replaceWith(wrapper.firstElementChild);
					}
				}
			})
			.catch(() => {
				window.location.reload(); // fallback
			})
			.finally(() => {
				const loading = document.querySelector('.layoutberg-loading-overlay');
				if (loading) loading.remove();
				
				// Remove searching indicator
				const searchContainer = document.querySelector('.layoutberg-search-container');
				if (searchContainer) {
					searchContainer.classList.remove('searching');
				}
				
				// Keep focus on search input for better UX
				if (searchInput && document.activeElement === searchInput) {
					const cursorPosition = searchInput.selectionStart;
					searchInput.focus();
					searchInput.setSelectionRange(cursorPosition, cursorPosition);
				}
			});
	}
	
	if (filterSelect) {
		filterSelect.addEventListener('change', function() {
			updateTemplatesGridAJAX();
		});
	}
	if (orderSelect) {
		orderSelect.addEventListener('change', function() {
			updateTemplatesGridAJAX();
		});
	}
	// Debounce function for live search
	function debounce(func, wait) {
		let timeout;
		return function executedFunction(...args) {
			const later = () => {
				clearTimeout(timeout);
				func(...args);
			};
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
		};
	}
	
	// Create debounced version of updateTemplatesGridAJAX
	const debouncedUpdate = debounce(updateTemplatesGridAJAX, 300);
	
	if (searchInput) {
		// Live search as you type
		searchInput.addEventListener('input', function(e) {
			// Show instant feedback
			if (e.target.value.trim()) {
				searchInput.setAttribute('data-searching', 'true');
			} else {
				searchInput.removeAttribute('data-searching');
			}
			debouncedUpdate();
		});
		
		// Also handle Enter key for immediate search
		searchInput.addEventListener('keypress', function(e) {
			if (e.key === 'Enter') {
				e.preventDefault();
				// Cancel any pending debounced calls
				clearTimeout(debouncedUpdate.timeout);
				// Execute search immediately
				updateTemplatesGridAJAX();
			}
		});
	}
	if (searchBtn) {
		searchBtn.addEventListener('click', function(e) {
			e.preventDefault();
			updateTemplatesGridAJAX();
		});
	}
	
	// Preview template
	document.addEventListener('click', function(e) {
		const previewButton = e.target.closest('.layoutberg-preview-template');
		if (previewButton) {
			e.preventDefault();
			e.stopPropagation();
			const templateId = previewButton.getAttribute('data-template-id');
			console.log('Preview button clicked for template ID:', templateId);
			
			if (!templateId) {
				console.error('No template ID found');
				return;
			}
			
			const modal = showModal('layoutberg-template-preview-modal');
			if (!modal) return;
			
			// Load template content with visual preview
			makeAjaxRequest('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'GET',
				data: {
					action: 'layoutberg_get_template',
					template_id: templateId,
					include_preview: '1',
					_wpnonce: '<?php echo wp_create_nonce( 'layoutberg_nonce' ); ?>'
				},
				beforeSend: function() {
					console.log('Sending AJAX request for preview template ID:', templateId);
					// Show loading state using the preview component
					if (window.layoutbergTemplatePreview && window.layoutbergTemplatePreview.init) {
						window.layoutbergTemplatePreview.init(
							'layoutberg-gutenberg-preview-container',
							'',
							{ isLoading: true }
						);
					}
				}
			}).then(response => {
				console.log('Preview AJAX response:', response);
				if (response.success && response.data) {
					console.log('Template content:', response.data.content);
					console.log('Content type:', typeof response.data.content);
					console.log('Content length:', response.data.content ? response.data.content.length : 0);
					
					// Test with hardcoded content first
					const testContent = `<!-- wp:paragraph -->
<p>This is a test paragraph block.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>Test Heading</h2>
<!-- /wp:heading -->`;
					
					console.log('Using test content for debugging');
					
					// Use the new Gutenberg preview component
					if (window.layoutbergTemplatePreview && window.layoutbergTemplatePreview.init) {
						// For testing, use hardcoded content
						// window.layoutbergTemplatePreview.init(
						// 	'layoutberg-gutenberg-preview-container',
						// 	testContent,
						// 	{ showCode: true }
						// );
						
						// Use actual template content
						window.layoutbergTemplatePreview.init(
							'layoutberg-gutenberg-preview-container',
							response.data.content,
							{ showCode: true }
						);
					} else {
						// Fallback if the preview component isn't loaded
						const container = document.getElementById('layoutberg-gutenberg-preview-container');
						if (container) {
							container.innerHTML = '<div class="notice notice-error"><p>Preview component not loaded. Please refresh the page.</p></div>';
						}
					}
					
					// Update use template button
					const useTemplateBtn = document.querySelector('.layoutberg-use-template-modal');
					if (useTemplateBtn) {
						useTemplateBtn.setAttribute('data-template-id', templateId);
					}
				}
			}).catch(error => {
				console.log('Preview AJAX error:', error);
				const container = document.getElementById('layoutberg-gutenberg-preview-container');
				if (container) {
					container.innerHTML = '<div class="notice notice-error"><p>Error loading template: ' + error.message + '</p></div>';
				}
			});
		}
	});
	
	// Edit template
	document.addEventListener('click', function(e) {
		const editLink = e.target.closest('.edit-template');
		if (editLink) {
			e.preventDefault();
			const templateId = editLink.getAttribute('data-template-id');
			console.log('Edit button clicked for template ID:', templateId);
			
			const modal = showModal('layoutberg-template-edit-modal');
			if (!modal) return;
			
			// Load template data
			makeAjaxRequest('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'GET',
				data: {
					action: 'layoutberg_get_template',
					template_id: templateId,
					_wpnonce: '<?php echo wp_create_nonce( 'layoutberg_nonce' ); ?>'
				},
				beforeSend: function() {
					console.log('Sending AJAX request for edit template ID:', templateId);
				}
			}).then(response => {
				console.log('Edit AJAX response:', response);
				if (response.success && response.data) {
					const fields = {
						'template-id': response.data.id,
						'template-name': response.data.name,
						'template-description': response.data.description || '',
						'template-category': response.data.category
					};
					
					for (const [fieldId, value] of Object.entries(fields)) {
						const field = document.getElementById(fieldId);
						if (field) {
							field.value = value;
						}
					}
					
					// Handle tags - convert array to comma-separated string
					let tags = '';
					if (response.data.tags) {
						if (Array.isArray(response.data.tags)) {
							tags = response.data.tags.join(', ');
						} else {
							tags = response.data.tags;
						}
					}
					
					const tagsField = document.getElementById('template-tags');
					if (tagsField) {
						tagsField.value = tags;
					}
					
					const publicField = document.getElementById('template-public');
					if (publicField) {
						publicField.checked = response.data.is_public == 1;
					}
				}
			}).catch(error => {
				console.log('Edit AJAX error:', error);
				alert('Error loading template data: ' + error.message);
				hideModal(modal);
			});
		}
	});
	
	// Save template changes
	const saveBtn = document.getElementById('save-template-changes');
	if (saveBtn) {
		saveBtn.addEventListener('click', function() {
			const form = document.getElementById('layoutberg-edit-template-form');
			const formData = new FormData(form);
			
			const data = {
				action: 'layoutberg_update_template',
				_wpnonce: '<?php echo wp_create_nonce( 'layoutberg_nonce' ); ?>'
			};
			
			// Convert FormData to regular object
			for (const [key, value] of formData.entries()) {
				data[key] = value;
			}
			
			// Handle checkbox separately
			const publicField = document.getElementById('template-public');
			data.is_public = publicField && publicField.checked ? '1' : '0';
			
			makeAjaxRequest('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'POST',
				data: data
			}).then(response => {
				if (response.success) {
					location.reload();
				} else {
					alert(response.data || 'Failed to update template');
				}
			}).catch(error => {
				alert('Error updating template: ' + error.message);
			});
		});
	}
	
	// Import template
	const importBtn = document.querySelector('.layoutberg-import-template');
	if (importBtn) {
		importBtn.addEventListener('click', function(e) {
			e.preventDefault();
			
			// Check if import is locked
			if (this.classList.contains('disabled')) {
				// Open pricing modal instead
				if (window.layoutbergAdmin && window.layoutbergAdmin.openPricingModal) {
					window.layoutbergAdmin.openPricingModal({
						currentTarget: this,
						preventDefault: function() {}
					});
				}
			} else {
				showModal('layoutberg-template-import-modal');
			}
		});
	}
	
	const importSubmitBtn = document.getElementById('import-template');
	if (importSubmitBtn) {
		importSubmitBtn.addEventListener('click', function() {
			const fileInput = document.getElementById('import-file');
			if (!fileInput.files.length) {
				alert('Please select a file to import');
				return;
			}
			
			const formData = new FormData();
			formData.append('action', 'layoutberg_import_template');
			formData.append('_wpnonce', '<?php echo wp_create_nonce( 'layoutberg_nonce' ); ?>');
			formData.append('import_file', fileInput.files[0]);
			
			makeAjaxRequest('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'POST',
				data: formData
			}).then(response => {
				if (response.success) {
					location.reload();
				} else {
					alert(response.data || 'Failed to import template');
				}
			});
		});
	}
	
	// Use template
	document.addEventListener('click', function(e) {
		const useButton = e.target.closest('.layoutberg-use-template, .layoutberg-use-template-modal');
		if (useButton) {
			e.preventDefault();
			e.stopPropagation();
			console.log('Use template clicked');
			const templateId = useButton.getAttribute('data-template-id');
			console.log('Template ID:', templateId);
			
			// Close any open modals
			const modals = document.querySelectorAll('.layoutberg-modal');
			modals.forEach(modal => hideModal(modal));
			
			// Increment usage count before redirecting to editor
			makeAjaxRequest('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'GET',
				data: {
					action: 'layoutberg_get_template',
					template_id: templateId,
					increment_usage: 1,
					_wpnonce: '<?php echo wp_create_nonce( 'layoutberg_nonce' ); ?>'
				}
			}).then((response) => {
				console.log('Template get response:', response);
				// Redirect to post editor with template (usage already incremented)
				window.location.href = '<?php echo admin_url( 'post-new.php?post_type=post&layoutberg_template=' ); ?>' + templateId;
			}).catch((error) => {
				console.error('Error getting template:', error);
				// Redirect anyway even if usage increment fails
				window.location.href = '<?php echo admin_url( 'post-new.php?post_type=post&layoutberg_template=' ); ?>' + templateId;
			});
		}
	});
	
	// Export template
	document.addEventListener('click', function(e) {
		const exportLink = e.target.closest('.export-template');
		if (exportLink) {
			e.preventDefault();
			const templateId = exportLink.getAttribute('data-template-id');
			
			// Make AJAX request to export template
			makeAjaxRequest('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'POST',
				data: {
					action: 'layoutberg_export_template',
					template_id: templateId,
					_wpnonce: '<?php echo wp_create_nonce( 'layoutberg_nonce' ); ?>'
				}
			}).then(response => {
				if (response.success && response.data) {
					// Create a blob from the JSON data
					const blob = new Blob([JSON.stringify(response.data, null, 2)], { type: 'application/json' });
					const url = window.URL.createObjectURL(blob);
					
					// Create a temporary link and trigger download
					const a = document.createElement('a');
					a.href = url;
					a.download = response.data.name ? `layoutberg-template-${response.data.name.toLowerCase().replace(/\s+/g, '-')}.json` : 'layoutberg-template.json';
					document.body.appendChild(a);
					a.click();
					
					// Cleanup
					window.URL.revokeObjectURL(url);
					document.body.removeChild(a);
				} else {
					alert(response.data || 'Failed to export template');
				}
			}).catch(error => {
				alert('Error exporting template: ' + error.message);
			});
		}
		
		// Handle locked export links
		if (e.target.classList.contains('export-template-locked')) {
			// Let the default action happen (navigate to upgrade/account URL)
			return true;
		}
	});
	
	// Modal close handlers
	document.addEventListener('click', function(e) {
		// Check if clicked element or its parent has the close class
		const closeButton = e.target.closest('.layoutberg-modal-close');
		if (closeButton) {
			console.log('Closing modal via close button');
			const modal = closeButton.closest('.layoutberg-modal');
			hideModal(modal);
			return;
		}
		
		// Close modal on background click
		if (e.target.classList.contains('layoutberg-modal')) {
			console.log('Closing modal via background click');
			hideModal(e.target);
		}
	});
});
</script>