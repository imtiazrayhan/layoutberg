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

// Get templates
$args = array(
	'category' => $current_category,
	'search'   => $search_term,
	'page'     => $paged,
	'per_page' => 20,
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

// Get categories
$categories = array(
	'all'      => __( 'All Categories', 'layoutberg' ),
	'general'  => __( 'General', 'layoutberg' ),
	'business' => __( 'Business', 'layoutberg' ),
	'creative' => __( 'Creative', 'layoutberg' ),
	'ecommerce' => __( 'E-commerce', 'layoutberg' ),
	'blog'     => __( 'Blog', 'layoutberg' ),
	'portfolio' => __( 'Portfolio', 'layoutberg' ),
	'landing'  => __( 'Landing Pages', 'layoutberg' ),
	'custom'   => __( 'Custom', 'layoutberg' ),
);
?>

<div class="wrap layoutberg-templates">
	<h1 class="wp-heading-inline">
		<?php echo esc_html( get_admin_page_title() ); ?>
	</h1>
	
	<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=post&layoutberg_create_template=1' ) ); ?>" class="page-title-action layoutberg-new-template" title="<?php esc_attr_e( 'Create a new layout in the editor and save it as a template', 'layoutberg' ); ?>">
		<?php esc_html_e( 'Add New Template', 'layoutberg' ); ?>
	</a>
	
	<a href="#" class="page-title-action layoutberg-import-template">
		<?php esc_html_e( 'Import', 'layoutberg' ); ?>
	</a>
	
	<hr class="wp-header-end">
	
	<?php if ( isset( $_GET['new'] ) && $_GET['new'] === '1' ) : ?>
		<div class="notice notice-info is-dismissible">
			<p><?php esc_html_e( 'To create a new template: Use the LayoutBerg AI Layout block in the editor to generate a layout, then save it as a template using the "Save as Template" button.', 'layoutberg' ); ?></p>
		</div>
	<?php endif; ?>
	
	<!-- Filters -->
	<div class="tablenav top">
		<div class="alignleft actions">
			<select name="category" id="filter-by-category">
				<option value=""><?php esc_html_e( 'All Categories', 'layoutberg' ); ?></option>
				<?php foreach ( $categories as $key => $label ) : ?>
					<?php if ( 'all' === $key ) continue; ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_category, $key ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<input type="submit" name="filter_action" id="post-query-submit" class="button" value="<?php esc_attr_e( 'Filter', 'layoutberg' ); ?>">
		</div>
		
		<div class="tablenav-pages">
			<span class="displaying-num">
				<?php
				printf(
					esc_html( _n( '%s template', '%s templates', $total_templates, 'layoutberg' ) ),
					number_format_i18n( $total_templates )
				);
				?>
			</span>
			
			<?php if ( $total_pages > 1 ) : ?>
				<span class="pagination-links">
					<?php
					$base_url = add_query_arg( array(
						'page' => 'layoutberg-templates',
						'category' => $current_category,
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
			<?php endif; ?>
		</div>
		
		<br class="clear">
	</div>
	
	<!-- Templates Grid -->
	<?php if ( empty( $templates ) ) : ?>
		<div class="layoutberg-templates-empty">
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
							<span class="template-category">
								<?php echo esc_html( $categories[ $template->category ] ?? $template->category ); ?>
							</span>
							
							<?php if ( $template->usage_count > 0 ) : ?>
								<span class="template-usage">
									<?php
									printf(
										esc_html( _n( 'Used %s time', 'Used %s times', $template->usage_count, 'layoutberg' ) ),
										number_format_i18n( $template->usage_count )
									);
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
									<?php 
									// Check if user can export templates (Professional or Agency plan)
									if ( function_exists( 'layoutberg_fs' ) && \layoutberg_fs()->can_use_premium_code() && 
										 ( \layoutberg_fs()->is_plan('professional') || \layoutberg_fs()->is_plan('agency') ) ) : 
									?>
										<a href="#" class="export-template" data-template-id="<?php echo esc_attr( $template->id ); ?>">
											<?php esc_html_e( 'Export', 'layoutberg' ); ?>
										</a>
										|
									<?php elseif ( function_exists( 'layoutberg_fs' ) ) : ?>
										<?php 
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
										|
									<?php else : ?>
										<!-- Freemius not loaded, hide export option -->
									<?php endif; ?>
									<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'template_id' => $template->id ), admin_url( 'admin.php?page=layoutberg-templates' ) ), 'delete_template_' . $template->id ) ); ?>" class="delete-template" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this template?', 'layoutberg' ); ?>');">
										<?php esc_html_e( 'Delete', 'layoutberg' ); ?>
									</a>
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
						<?php foreach ( $categories as $key => $label ) : ?>
							<?php if ( 'all' === $key ) continue; ?>
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
/* Templates Grid */
.layoutberg-templates-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.layoutberg-template-card {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 4px;
	overflow: hidden;
	transition: all 0.2s;
}

.layoutberg-template-card:hover {
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
	transform: translateY(-2px);
}

.template-preview {
	position: relative;
	padding-top: 60%;
	background: #f5f5f5;
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
}

.template-placeholder .dashicons {
	font-size: 48px;
	color: #ccc;
}

.template-actions {
	position: absolute;
	top: 10px;
	right: 10px;
	background: rgba(255, 255, 255, 0.95);
	display: flex;
	flex-direction: column;
	align-items: stretch;
	gap: 5px;
	padding: 8px;
	border-radius: 4px;
	box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
	opacity: 1;
	transition: opacity 0.2s;
	z-index: 10;
}

.layoutberg-template-card:hover .template-actions,
.template-actions:hover {
	opacity: 1;
}

.template-actions .button {
	font-size: 12px;
	padding: 4px 8px;
	height: auto;
	line-height: 1.2;
	white-space: nowrap;
	cursor: pointer;
}

/* Show actions on touch devices */
@media (hover: none) and (pointer: coarse) {
	.template-actions {
		opacity: 0.8;
	}
}

.template-details {
	padding: 15px;
}

.template-name {
	margin: 0 0 10px;
	font-size: 16px;
	font-weight: 600;
}

.template-description {
	margin: 0 0 10px;
	color: #666;
	font-size: 13px;
}

.template-meta {
	display: flex;
	gap: 15px;
	margin-bottom: 10px;
	font-size: 12px;
	color: #999;
}

.template-category {
	background: #f0f0f0;
	padding: 2px 8px;
	border-radius: 3px;
}

.template-footer {
	display: flex;
	justify-content: space-between;
	align-items: center;
	font-size: 12px;
	color: #999;
	padding-top: 10px;
	border-top: 1px solid #eee;
}

.template-actions-footer a {
	color: #2271b1;
	text-decoration: none;
}

.template-actions-footer a:hover {
	color: #135e96;
	text-decoration: underline;
}

.template-actions-footer .export-template-locked {
	opacity: 0.7;
}

.template-actions-footer .export-template-locked:hover {
	opacity: 1;
}

.layoutberg-templates-empty {
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

.layoutberg-templates-empty h3 {
	font-size: 24px;
	margin: 0 0 20px;
	color: #23282d;
}

.layoutberg-templates-empty p {
	font-size: 16px;
	color: #666;
	margin-bottom: 15px;
}

.layoutberg-templates-empty ol {
	text-align: left;
	display: inline-block;
	margin: 20px 0 30px;
	font-size: 14px;
}

.layoutberg-templates-empty .button-hero {
	font-size: 16px;
	padding: 12px 30px;
	height: auto;
}

/* Modals */
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
	background: #fff;
	border-radius: 4px;
	box-shadow: 0 2px 20px rgba(0, 0, 0, 0.2);
	max-width: 1200px;
	width: 95%;
	max-height: 95vh;
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

.layoutberg-modal-footer .button {
	margin: 0;
	min-width: 100px;
}

/* Form Fields */
.form-field {
	margin-bottom: 20px;
}

.form-field label {
	display: block;
	margin-bottom: 5px;
	font-weight: 600;
}

.form-field input[type="text"],
.form-field input[type="file"],
.form-field textarea,
.form-field select {
	width: 100%;
	padding: 8px 12px;
	border: 1px solid #ddd;
	border-radius: 4px;
}

.form-field .description {
	margin-top: 5px;
	color: #666;
	font-size: 13px;
}

/* Template Preview */
.template-preview-container {
	height: 100%;
	display: flex;
	flex-direction: column;
}

#layoutberg-gutenberg-preview-container {
	flex: 1;
	min-height: 500px;
}


.template-visual-preview {
	padding: 20px;
	background: #fff;
	min-height: 400px;
}

.template-code-preview {
	background: #fff;
	padding: 20px;
}

.template-code-preview pre {
	background: #f5f5f5;
	padding: 15px;
	border-radius: 4px;
	overflow-x: auto;
	max-height: 400px;
	overflow-y: auto;
	margin: 0;
}

.template-loading {
	display: flex;
	align-items: center;
	justify-content: center;
	min-height: 200px;
	color: #666;
	font-style: italic;
}

.template-loading::before {
	content: '';
	display: inline-block;
	width: 16px;
	height: 16px;
	border: 2px solid #ddd;
	border-top-color: #007cba;
	border-radius: 50%;
	animation: spin 1s linear infinite;
	margin-right: 8px;
}

@keyframes spin {
	to {
		transform: rotate(360deg);
	}
}

.template-visual-preview iframe {
	width: 100%;
	border: none;
	min-height: 400px;
	border-radius: 4px;
	background: #fff;
}

.template-preview-info {
	margin-bottom: 20px;
	padding-bottom: 20px;
	border-bottom: 1px solid #eee;
}

.template-preview-info h3 {
	margin: 0 0 10px;
	font-size: 20px;
}

.template-preview-info .template-meta {
	display: flex;
	gap: 20px;
	font-size: 14px;
	color: #666;
}

.template-preview-info .template-meta span {
	display: block;
}

.template-code-preview {
	margin-top: 20px;
}

.template-code-preview h4 {
	margin: 0 0 10px;
	font-size: 16px;
}

.template-code-preview pre {
	background: #f5f5f5;
	padding: 15px;
	border-radius: 4px;
	overflow-x: auto;
	max-height: 300px;
	overflow-y: auto;
}

.template-code-preview code {
	font-size: 12px;
	line-height: 1.5;
}
</style>

<script>
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
	
	// Filter handling
	const filterSelect = document.getElementById('filter-by-category');
	if (filterSelect) {
		filterSelect.addEventListener('change', function() {
			const category = this.value;
			const url = new URL(window.location.href);
			if (category) {
				url.searchParams.set('category', category);
			} else {
				url.searchParams.delete('category');
			}
			url.searchParams.delete('paged');
			window.location.href = url.toString();
		});
	}
	
	// Preview template
	document.addEventListener('click', function(e) {
		if (e.target.classList.contains('layoutberg-preview-template')) {
			e.preventDefault();
			const templateId = e.target.getAttribute('data-template-id');
			console.log('Preview button clicked for template ID:', templateId);
			
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
		if (e.target.classList.contains('edit-template')) {
			e.preventDefault();
			const templateId = e.target.getAttribute('data-template-id');
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
			showModal('layoutberg-template-import-modal');
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
		if (e.target.classList.contains('layoutberg-use-template') || e.target.classList.contains('layoutberg-use-template-modal')) {
			const templateId = e.target.getAttribute('data-template-id');
			
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
			}).then(() => {
				// Redirect to post editor with template (usage already incremented)
				window.location.href = '<?php echo admin_url( 'post-new.php?post_type=post&layoutberg_template=' ); ?>' + templateId;
			}).catch(() => {
				// Redirect anyway even if usage increment fails
				window.location.href = '<?php echo admin_url( 'post-new.php?post_type=post&layoutberg_template=' ); ?>' + templateId;
			});
		}
	});
	
	// Export template
	document.addEventListener('click', function(e) {
		if (e.target.classList.contains('export-template')) {
			e.preventDefault();
			const templateId = e.target.getAttribute('data-template-id');
			
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