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
				<div class="template-preview-content"></div>
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
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.7);
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 10px;
	opacity: 0;
	transition: opacity 0.2s;
}

.layoutberg-template-card:hover .template-actions {
	opacity: 1;
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

.layoutberg-modal-content {
	background: #fff;
	border-radius: 4px;
	box-shadow: 0 2px 20px rgba(0, 0, 0, 0.2);
	max-width: 800px;
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
	gap: 10px;
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
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 20px;
	background: #f9f9f9;
	min-height: 400px;
}

.template-preview-content {
	background: #fff;
	padding: 20px;
	border-radius: 4px;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
</style>

<script>
jQuery(document).ready(function($) {
	// Filter handling
	$('#filter-by-category').on('change', function() {
		var category = $(this).val();
		var url = new URL(window.location.href);
		if (category) {
			url.searchParams.set('category', category);
		} else {
			url.searchParams.delete('category');
		}
		url.searchParams.delete('paged');
		window.location.href = url.toString();
	});
	
	// Preview template
	$('.layoutberg-preview-template').on('click', function(e) {
		e.preventDefault();
		var templateId = $(this).data('template-id');
		
		// Show modal
		$('#layoutberg-template-preview-modal').show();
		
		// Load template content
		$.ajax({
			url: ajaxurl,
			type: 'GET',
			data: {
				action: 'layoutberg_get_template',
				template_id: templateId,
				_wpnonce: '<?php echo wp_create_nonce( 'layoutberg_nonce' ); ?>'
			},
			success: function(response) {
				if (response.success && response.data) {
					$('.template-preview-content').html(response.data.content);
					$('.layoutberg-use-template-modal').data('template-id', templateId);
				}
			}
		});
	});
	
	// Edit template
	$('.edit-template').on('click', function(e) {
		e.preventDefault();
		var templateId = $(this).data('template-id');
		
		// Show modal
		$('#layoutberg-template-edit-modal').show();
		
		// Load template data
		$.ajax({
			url: ajaxurl,
			type: 'GET',
			data: {
				action: 'layoutberg_get_template',
				template_id: templateId,
				_wpnonce: '<?php echo wp_create_nonce( 'layoutberg_nonce' ); ?>'
			},
			success: function(response) {
				if (response.success && response.data) {
					$('#template-id').val(response.data.id);
					$('#template-name').val(response.data.name);
					$('#template-description').val(response.data.description || '');
					$('#template-category').val(response.data.category);
					$('#template-tags').val(response.data.tags || '');
					$('#template-public').prop('checked', response.data.is_public == 1);
				}
			}
		});
	});
	
	// Save template changes
	$('#save-template-changes').on('click', function() {
		var formData = $('#layoutberg-edit-template-form').serializeArray();
		var data = {
			action: 'layoutberg_update_template',
			_wpnonce: '<?php echo wp_create_nonce( 'layoutberg_nonce' ); ?>'
		};
		
		formData.forEach(function(item) {
			data[item.name] = item.value;
		});
		
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: data,
			success: function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert(response.data || 'Failed to update template');
				}
			}
		});
	});
	
	// Import template
	$('.layoutberg-import-template').on('click', function(e) {
		e.preventDefault();
		$('#layoutberg-template-import-modal').show();
	});
	
	$('#import-template').on('click', function() {
		var fileInput = $('#import-file')[0];
		if (!fileInput.files.length) {
			alert('Please select a file to import');
			return;
		}
		
		var formData = new FormData();
		formData.append('action', 'layoutberg_import_template');
		formData.append('_wpnonce', '<?php echo wp_create_nonce( 'layoutberg_nonce' ); ?>');
		formData.append('import_file', fileInput.files[0]);
		
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if (response.success) {
					location.reload();
				} else {
					alert(response.data || 'Failed to import template');
				}
			}
		});
	});
	
	// Use template
	$('.layoutberg-use-template, .layoutberg-use-template-modal').on('click', function() {
		var templateId = $(this).data('template-id');
		
		// Redirect to post editor with template
		window.location.href = '<?php echo admin_url( 'post-new.php?post_type=post&layoutberg_template=' ); ?>' + templateId;
	});
	
	// Modal close
	$('.layoutberg-modal-close').on('click', function() {
		$(this).closest('.layoutberg-modal').hide();
	});
	
	// Close modal on background click
	$('.layoutberg-modal').on('click', function(e) {
		if (e.target === this) {
			$(this).hide();
		}
	});
});
</script>