<?php
/**
 * Template manager class.
 *
 * @package    LayoutBerg
 * @subpackage Core
 * @since      1.0.0
 */

namespace DotCamp\LayoutBerg;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template manager class.
 *
 * @since 1.0.0
 */
class Template_Manager {

	/**
	 * Table name.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string
	 */
	private $table_name;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'layoutberg_templates';
	}

	/**
	 * Get templates.
	 *
	 * @since 1.0.0
	 * @param array $args Query arguments.
	 * @return array Templates.
	 */
	public function get_templates( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'category' => '',
			'search'   => '',
			'page'     => 1,
			'per_page' => 20,
			'orderby'  => 'created_at',
			'order'    => 'DESC',
			'user_id'  => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		// Build WHERE clause.
		$where_clauses = array( '1=1' );
		$prepare_args  = array();

		if ( ! empty( $args['category'] ) ) {
			$where_clauses[] = 'category = %s';
			$prepare_args[]   = $args['category'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where_clauses[] = '(name LIKE %s OR description LIKE %s OR tags LIKE %s)';
			$search_term     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$prepare_args[]   = $search_term;
			$prepare_args[]   = $search_term;
			$prepare_args[]   = $search_term;
		}

		if ( ! empty( $args['user_id'] ) ) {
			$where_clauses[] = 'created_by = %d';
			$prepare_args[]   = $args['user_id'];
		}

		$where = implode( ' AND ', $where_clauses );

		// Calculate offset.
		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// Build query.
		$query = "SELECT * FROM {$this->table_name} WHERE {$where}";

		// Add ORDER BY.
		$allowed_orderby = array( 'name', 'created_at', 'updated_at', 'usage_count' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$query          .= " ORDER BY {$orderby} {$order}";

		// Add LIMIT.
		$query .= " LIMIT %d OFFSET %d";
		$prepare_args[] = $args['per_page'];
		$prepare_args[] = $offset;

		// Execute query.
		$results = $wpdb->get_results(
			$wpdb->prepare( $query, $prepare_args ),
			ARRAY_A
		);

		// Get total count.
		$count_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where}";
		$total_items = $wpdb->get_var(
			$wpdb->prepare( $count_query, array_slice( $prepare_args, 0, -2 ) )
		);

		// Process results.
		$templates = array();
		foreach ( $results as $row ) {
			$templates[] = $this->format_template( $row );
		}

		return array(
			'templates'    => $templates,
			'total_items'  => intval( $total_items ),
			'total_pages'  => ceil( $total_items / $args['per_page'] ),
			'current_page' => $args['page'],
		);
	}

	/**
	 * Get template by ID.
	 *
	 * @since 1.0.0
	 * @param int $template_id Template ID.
	 * @return array|WP_Error Template data or error.
	 */
	public function get_template( $template_id ) {
		global $wpdb;

		$template = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d",
				$template_id
			),
			ARRAY_A
		);

		if ( ! $template ) {
			return new \WP_Error( 'template_not_found', __( 'Template not found.', 'layoutberg' ) );
		}

		// Increment usage count.
		$this->increment_usage_count( $template_id );

		return $this->format_template( $template );
	}

	/**
	 * Save template.
	 *
	 * @since 1.0.0
	 * @param array $data Template data.
	 * @return int|WP_Error Template ID or error.
	 */
	public function save_template( $data ) {
		global $wpdb;

		// Validate required fields.
		if ( empty( $data['name'] ) || empty( $data['content'] ) ) {
			return new \WP_Error( 'missing_fields', __( 'Template name and content are required.', 'layoutberg' ) );
		}

		// Generate slug.
		$slug = $this->generate_unique_slug( $data['name'] );

		// Prepare data.
		$insert_data = array(
			'name'        => sanitize_text_field( $data['name'] ),
			'slug'        => $slug,
			'description' => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
			'content'     => wp_kses_post( $data['content'] ),
			'category'    => isset( $data['category'] ) ? sanitize_text_field( $data['category'] ) : 'general',
			'tags'        => isset( $data['tags'] ) ? wp_json_encode( array_map( 'sanitize_text_field', $data['tags'] ) ) : '',
			'prompt'      => isset( $data['prompt'] ) ? sanitize_textarea_field( $data['prompt'] ) : '',
			'is_public'   => isset( $data['is_public'] ) ? (int) $data['is_public'] : 0,
			'created_by'  => get_current_user_id(),
		);

		// Insert template.
		$result = $wpdb->insert(
			$this->table_name,
			$insert_data,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d' )
		);

		if ( false === $result ) {
			return new \WP_Error( 'db_error', __( 'Failed to save template.', 'layoutberg' ) );
		}

		$template_id = $wpdb->insert_id;

		// Fire action hook.
		do_action( 'layoutberg_template_saved', $template_id, $insert_data );

		return $template_id;
	}

	/**
	 * Update template.
	 *
	 * @since 1.0.0
	 * @param int   $template_id Template ID.
	 * @param array $data        Template data.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function update_template( $template_id, $data ) {
		global $wpdb;

		// Check if template exists.
		$existing = $this->get_template( $template_id );
		if ( is_wp_error( $existing ) ) {
			return $existing;
		}

		// Check permissions.
		if ( ! $this->can_edit_template( $template_id ) ) {
			return new \WP_Error( 'permission_denied', __( 'You do not have permission to edit this template.', 'layoutberg' ) );
		}

		// Prepare update data.
		$update_data = array();
		$format      = array();

		if ( isset( $data['name'] ) ) {
			$update_data['name'] = sanitize_text_field( $data['name'] );
			$format[]            = '%s';
		}

		if ( isset( $data['description'] ) ) {
			$update_data['description'] = sanitize_textarea_field( $data['description'] );
			$format[]                   = '%s';
		}

		if ( isset( $data['content'] ) ) {
			$update_data['content'] = wp_kses_post( $data['content'] );
			$format[]               = '%s';
		}

		if ( isset( $data['category'] ) ) {
			$update_data['category'] = sanitize_text_field( $data['category'] );
			$format[]                = '%s';
		}

		if ( isset( $data['tags'] ) ) {
			$update_data['tags'] = wp_json_encode( array_map( 'sanitize_text_field', $data['tags'] ) );
			$format[]            = '%s';
		}

		if ( isset( $data['is_public'] ) ) {
			$update_data['is_public'] = intval( $data['is_public'] );
			$format[]                 = '%d';
		}

		if ( empty( $update_data ) ) {
			return true; // Nothing to update.
		}

		// Update template.
		$result = $wpdb->update(
			$this->table_name,
			$update_data,
			array( 'id' => $template_id ),
			$format,
			array( '%d' )
		);

		if ( false === $result ) {
			return new \WP_Error( 'db_error', __( 'Failed to update template.', 'layoutberg' ) );
		}

		// Fire action hook.
		do_action( 'layoutberg_template_updated', $template_id, $update_data );

		return true;
	}

	/**
	 * Delete template.
	 *
	 * @since 1.0.0
	 * @param int $template_id Template ID.
	 * @return bool|WP_Error True on success, error on failure.
	 */
	public function delete_template( $template_id ) {
		global $wpdb;

		// Check permissions.
		if ( ! $this->can_edit_template( $template_id ) ) {
			return new \WP_Error( 'permission_denied', __( 'You do not have permission to delete this template.', 'layoutberg' ) );
		}

		// Delete template.
		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $template_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new \WP_Error( 'db_error', __( 'Failed to delete template.', 'layoutberg' ) );
		}

		// Fire action hook.
		do_action( 'layoutberg_template_deleted', $template_id );

		return true;
	}

	/**
	 * Get template categories.
	 *
	 * @since 1.0.0
	 * @return array Categories.
	 */
	public function get_categories() {
		$categories = array(
			'general'    => __( 'General', 'layoutberg' ),
			'business'   => __( 'Business', 'layoutberg' ),
			'creative'   => __( 'Creative', 'layoutberg' ),
			'ecommerce'  => __( 'E-commerce', 'layoutberg' ),
			'blog'       => __( 'Blog/Magazine', 'layoutberg' ),
			'portfolio'  => __( 'Portfolio', 'layoutberg' ),
			'landing'    => __( 'Landing Pages', 'layoutberg' ),
			'custom'     => __( 'Custom', 'layoutberg' ),
		);

		return apply_filters( 'layoutberg_template_categories', $categories );
	}

	/**
	 * Import template.
	 *
	 * @since 1.0.0
	 * @param array $template_data Template data.
	 * @return int|WP_Error Template ID or error.
	 */
	public function import_template( $template_data ) {
		// Validate template data.
		if ( ! isset( $template_data['name'] ) || ! isset( $template_data['content'] ) ) {
			return new \WP_Error( 'invalid_template', __( 'Invalid template data.', 'layoutberg' ) );
		}

		// Remove ID if present to avoid conflicts.
		unset( $template_data['id'] );

		// Save as new template.
		return $this->save_template( $template_data );
	}

	/**
	 * Export template.
	 *
	 * @since 1.0.0
	 * @param int $template_id Template ID.
	 * @return array|WP_Error Template data or error.
	 */
	public function export_template( $template_id ) {
		$template = $this->get_template( $template_id );

		if ( is_wp_error( $template ) ) {
			return $template;
		}

		// Remove sensitive data.
		unset( $template['id'] );
		unset( $template['created_by'] );
		unset( $template['usage_count'] );

		return $template;
	}

	/**
	 * Format template data.
	 *
	 * @since 1.0.0
	 * @param array $template Raw template data.
	 * @return array Formatted template data.
	 */
	private function format_template( $template ) {
		return array(
			'id'            => intval( $template['id'] ),
			'name'          => $template['name'],
			'slug'          => $template['slug'],
			'description'   => $template['description'],
			'content'       => $template['content'],
			'category'      => $template['category'],
			'tags'          => ! empty( $template['tags'] ) ? json_decode( $template['tags'], true ) : array(),
			'thumbnail_url' => $template['thumbnail_url'] ?? '',
			'is_public'     => intval( $template['is_public'] ?? 0 ),
			'usage_count'   => intval( $template['usage_count'] ),
			'created_by'    => intval( $template['created_by'] ),
			'created_at'    => $template['created_at'],
			'updated_at'    => $template['updated_at'],
			'author'        => get_userdata( $template['created_by'] )->display_name ?? __( 'Unknown', 'layoutberg' ),
		);
	}

	/**
	 * Generate unique slug.
	 *
	 * @since 1.0.0
	 * @param string $name Template name.
	 * @return string Unique slug.
	 */
	private function generate_unique_slug( $name ) {
		global $wpdb;

		$slug  = sanitize_title( $name );
		$count = 1;

		while ( true ) {
			$check_slug = $count > 1 ? $slug . '-' . $count : $slug;
			
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table_name} WHERE slug = %s",
					$check_slug
				)
			);

			if ( ! $exists ) {
				return $check_slug;
			}

			$count++;
		}
	}

	/**
	 * Check if user can edit template.
	 *
	 * @since 1.0.0
	 * @param int $template_id Template ID.
	 * @return bool True if user can edit.
	 */
	private function can_edit_template( $template_id ) {
		global $wpdb;

		// Admins can edit all templates.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Check if user owns the template.
		$created_by = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT created_by FROM {$this->table_name} WHERE id = %d",
				$template_id
			)
		);

		return intval( $created_by ) === get_current_user_id();
	}

	/**
	 * Increment usage count.
	 *
	 * @since 1.0.0
	 * @param int $template_id Template ID.
	 */
	private function increment_usage_count( $template_id ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table_name} SET usage_count = usage_count + 1 WHERE id = %d",
				$template_id
			)
		);
	}

	/**
	 * Get templates count.
	 *
	 * @since 1.0.0
	 * @param array $args Query arguments.
	 * @return int Total templates count.
	 */
	public function get_templates_count( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'category' => '',
			'search'   => '',
			'user_id'  => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		// Build WHERE clause.
		$where_clauses = array( '1=1' );
		$prepare_args  = array();

		if ( ! empty( $args['category'] ) ) {
			$where_clauses[] = 'category = %s';
			$prepare_args[]   = $args['category'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where_clauses[] = '(name LIKE %s OR description LIKE %s OR tags LIKE %s)';
			$search_term     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$prepare_args[]   = $search_term;
			$prepare_args[]   = $search_term;
			$prepare_args[]   = $search_term;
		}

		if ( ! empty( $args['user_id'] ) ) {
			$where_clauses[] = 'created_by = %d';
			$prepare_args[]   = $args['user_id'];
		}

		$where = implode( ' AND ', $where_clauses );

		// Get total count.
		$count_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where}";
		
		if ( ! empty( $prepare_args ) ) {
			$total_items = $wpdb->get_var(
				$wpdb->prepare( $count_query, $prepare_args )
			);
		} else {
			$total_items = $wpdb->get_var( $count_query );
		}

		return intval( $total_items );
	}
}