<?php
/**
 * Block generator class.
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
 * Block generator class.
 *
 * @since 1.0.0
 */
class Block_Generator {

	/**
	 * API client instance.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    API_Client
	 */
	private $api_client;

	/**
	 * Cache manager instance.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    Cache_Manager
	 */
	private $cache_manager;

	/**
	 * Block serializer instance.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    Block_Serializer
	 */
	private $block_serializer;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		try {
			$this->api_client    = new API_Client();
			$this->cache_manager = new Cache_Manager();
			$this->block_serializer = new Block_Serializer();
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'LayoutBerg Block_Generator initialization error: ' . $e->getMessage() );
			}
			throw $e;
		}
	}

	/**
	 * Generate layout from prompt.
	 *
	 * @since 1.0.0
	 * @param string $prompt  User prompt.
	 * @param array  $options Generation options.
	 * @return array|WP_Error Generated layout or error.
	 */
	public function generate( $prompt, $options = array() ) {
		// Check cache first.
		$cache_key = $this->get_cache_key( $prompt, $options );
		$cached    = $this->cache_manager->get( $cache_key );

		if ( false !== $cached ) {
			$this->cache_manager->track_hit();
			return $cached;
		}

		$this->cache_manager->track_miss();

		// Generate via API.
		$result = $this->api_client->generate_layout( $prompt, $options );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Parse and validate the generated content.
		$parsed = $this->parse_generated_content( $result['content'] );

		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		// Validate block structure.
		$validated = $this->validate_blocks( $parsed );

		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		// Serialize blocks for editor.
		$serialized = $this->block_serializer->serialize_for_editor( $validated );

		// Prepare response.
		$response = array(
			'blocks'     => $validated,
			'serialized' => $serialized,
			'html'       => $this->blocks_to_html( $validated ),
			'raw'        => $result['content'],
			'usage'      => $result['usage'],
			'model'      => $result['model'],
			'metadata'   => array(
				'prompt'     => $prompt,
				'options'    => $options,
				'generated'  => current_time( 'mysql' ),
			),
		);

		// Cache the result.
		$this->cache_manager->set( $cache_key, $response );

		return $response;
	}

	/**
	 * Parse generated content into blocks.
	 *
	 * @since 1.0.0
	 * @param string $content Generated content.
	 * @return array|WP_Error Parsed blocks or error.
	 */
	private function parse_generated_content( $content ) {
		// Log raw response for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'LayoutBerg Raw AI Response: ' . $content );
		}

		// Extract block content from various response formats.
		$content = $this->extract_block_content( $content );
		
		// Clean and normalize the content.
		$content = $this->clean_generated_content( $content );

		// Check if content looks like valid block markup.
		if ( ! preg_match( '/<!-- wp:/', $content ) ) {
			return new \WP_Error(
				'invalid_block_markup',
				__( 'Generated content does not contain valid block markup.', 'layoutberg' )
			);
		}

		// Parse blocks using WordPress function.
		$blocks = parse_blocks( $content );

		if ( empty( $blocks ) ) {
			return new \WP_Error(
				'no_blocks_parsed',
				__( 'No blocks could be parsed from the generated content.', 'layoutberg' )
			);
		}

		// Filter out empty blocks and clean up.
		$blocks = $this->clean_parsed_blocks( $blocks );

		if ( empty( $blocks ) ) {
			return new \WP_Error(
				'no_valid_blocks_after_cleanup',
				__( 'No valid blocks remaining after cleanup.', 'layoutberg' )
			);
		}

		return $blocks;
	}

	/**
	 * Extract block content from AI response.
	 *
	 * @since 1.0.0
	 * @param string $content Raw AI response.
	 * @return string Extracted block content.
	 */
	private function extract_block_content( $content ) {
		// Remove any markdown code blocks.
		if ( preg_match( '/```(?:html|wp|wordpress)?\s*([\s\S]*?)```/i', $content, $matches ) ) {
			$content = $matches[1];
		}
		
		// Remove any JSON wrapper if present.
		if ( preg_match( '/^\s*\{[\s\S]*"content"\s*:\s*"([\s\S]+)"\s*\}\s*$/i', $content, $matches ) ) {
			$content = stripslashes( $matches[1] );
		}
		
		// Remove any explanatory text before the first block comment.
		if ( preg_match( '/(<!-- wp:[\s\S]+)$/i', $content, $matches ) ) {
			$content = $matches[1];
		}
		
		// Remove any trailing explanatory text after the last block comment.
		if ( preg_match( '/^([\s\S]+<!-- \/wp:[^>]+-->)/i', $content, $matches ) ) {
			$content = $matches[1];
		}
		
		return trim( $content );
	}

	/**
	 * Clean generated content.
	 *
	 * @since 1.0.0
	 * @param string $content Content to clean.
	 * @return string Cleaned content.
	 */
	private function clean_generated_content( $content ) {
		// Fix common AI formatting issues.
		
		// Fix escaped quotes in JSON attributes.
		$content = preg_replace_callback(
			'/<!-- wp:([^{]+)(\{[^}]+\})/',
			function( $matches ) {
				$block_name = $matches[1];
				$attrs = $matches[2];
				// Unescape quotes within the JSON.
				$attrs = str_replace( '\\"', '"', $attrs );
				// Ensure proper JSON formatting.
				$attrs = preg_replace( '/([{,])\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1"$2":', $attrs );
				return '<!-- wp:' . $block_name . $attrs;
			},
			$content
		);
		
		// Fix line breaks within block comments.
		$content = preg_replace( '/<!-- wp:([^>]+)\n([^>]+)-->/', '<!-- wp:$1 $2-->', $content );
		
		// Normalize whitespace.
		$content = preg_replace( '/\r\n|\r/', "\n", $content );
		
		// Remove extra whitespace between blocks.
		$content = preg_replace( '/-->\s+<!-- wp:/', "-->\n\n<!-- wp:", $content );
		
		// Fix common class name issues
		// Ensure wp-block-heading class is present
		$content = preg_replace(
			'/<h([1-6])(\s+class="[^"]*")?>/i',
			function( $matches ) {
				$level = $matches[1];
				$class_attr = isset( $matches[2] ) ? $matches[2] : '';
				
				if ( empty( $class_attr ) ) {
					return '<h' . $level . ' class="wp-block-heading">';
				} elseif ( strpos( $class_attr, 'wp-block-heading' ) === false ) {
					// Add wp-block-heading to existing classes
					$class_attr = str_replace( 'class="', 'class="wp-block-heading ', $class_attr );
					return '<h' . $level . $class_attr . '>';
				}
				
				return $matches[0];
			},
			$content
		);
		
		// Ensure button links have wp-element-button class
		$content = preg_replace(
			'/<a\s+class="wp-block-button__link([^"]*)"/',
			function( $matches ) {
				$extra_classes = $matches[1];
				if ( strpos( $extra_classes, 'wp-element-button' ) === false ) {
					return '<a class="wp-block-button__link' . $extra_classes . ' wp-element-button"';
				}
				return $matches[0];
			},
			$content
		);
		
		// Fix self-closing social links
		$content = preg_replace( 
			'/<!-- wp:social-link ([^>]+) \/-->/', 
			'<!-- wp:social-link $1 /-->', 
			$content 
		);
		
		return trim( $content );
	}

	/**
	 * Clean parsed blocks.
	 *
	 * @since 1.0.0
	 * @param array $blocks Parsed blocks.
	 * @return array Cleaned blocks.
	 */
	private function clean_parsed_blocks( $blocks ) {
		$cleaned = array();
		
		foreach ( $blocks as $block ) {
			// Skip empty blocks.
			if ( empty( $block['blockName'] ) ) {
				continue;
			}
			
			// Fix block attributes.
			if ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) {
				$block['attrs'] = $this->fix_block_attributes( $block['attrs'] );
			}
			
			// Clean innerHTML.
			if ( isset( $block['innerHTML'] ) ) {
				$block['innerHTML'] = $this->clean_inner_html( $block['innerHTML'] );
			}
			
			// Recursively clean inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->clean_parsed_blocks( $block['innerBlocks'] );
			}
			
			$cleaned[] = $block;
		}
		
		return $cleaned;
	}

	/**
	 * Fix block attributes.
	 *
	 * @since 1.0.0
	 * @param array $attrs Block attributes.
	 * @return array Fixed attributes.
	 */
	private function fix_block_attributes( $attrs ) {
		// Convert string boolean values to actual booleans.
		foreach ( $attrs as $key => $value ) {
			if ( $value === 'true' ) {
				$attrs[ $key ] = true;
			} elseif ( $value === 'false' ) {
				$attrs[ $key ] = false;
			} elseif ( is_string( $value ) && is_numeric( $value ) ) {
				// Convert numeric strings to numbers where appropriate.
				if ( strpos( $value, '.' ) !== false ) {
					$attrs[ $key ] = floatval( $value );
				} else {
					$attrs[ $key ] = intval( $value );
				}
			}
		}
		
		return $attrs;
	}

	/**
	 * Clean inner HTML content.
	 *
	 * @since 1.0.0
	 * @param string $html HTML content.
	 * @return string Cleaned HTML.
	 */
	private function clean_inner_html( $html ) {
		// Remove excessive whitespace while preserving structure.
		$html = preg_replace( '/>\s+</', '><', $html );
		$html = preg_replace( '/\s+/', ' ', $html );
		
		// Ensure proper encoding.
		$html = wp_kses_post( $html );
		
		return trim( $html );
	}

	/**
	 * Validate block structure.
	 *
	 * @since 1.0.0
	 * @param array $blocks Parsed blocks.
	 * @return array|WP_Error Validated blocks or error.
	 */
	private function validate_blocks( $blocks ) {
		$allowed_blocks = $this->get_allowed_blocks();
		$validated      = array();
		$validation_errors = array();

		foreach ( $blocks as $index => $block ) {
			// Check if block is allowed.
			if ( ! in_array( $block['blockName'], $allowed_blocks, true ) ) {
				$validation_errors[] = sprintf(
					__( 'Block "%s" at position %d is not allowed.', 'layoutberg' ),
					$block['blockName'],
					$index + 1
				);
				continue; // Skip disallowed blocks.
			}

			// Validate block structure.
			$structure_validation = $this->validate_block_structure( $block );
			if ( is_wp_error( $structure_validation ) ) {
				$validation_errors[] = $structure_validation->get_error_message();
				continue;
			}

			// Validate block attributes.
			$block = $this->validate_block_attributes( $block );

			// Validate nesting rules.
			$nesting_validation = $this->validate_block_nesting( $block );
			if ( is_wp_error( $nesting_validation ) ) {
				$validation_errors[] = $nesting_validation->get_error_message();
				continue;
			}

			// Recursively validate inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$inner_validated = $this->validate_blocks( $block['innerBlocks'] );
				if ( is_wp_error( $inner_validated ) ) {
					return $inner_validated;
				}
				$block['innerBlocks'] = $inner_validated;
			}

			// Additional block-specific validation.
			$specific_validation = $this->validate_specific_block( $block );
			if ( is_wp_error( $specific_validation ) ) {
				$validation_errors[] = $specific_validation->get_error_message();
				continue;
			}

			$validated[] = $block;
		}

		// Log validation errors in debug mode.
		if ( ! empty( $validation_errors ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'LayoutBerg Validation Errors: ' . implode( '; ', $validation_errors ) );
		}

		if ( empty( $validated ) ) {
			return new \WP_Error(
				'no_valid_blocks',
				__( 'No valid blocks found in the generated content.', 'layoutberg' ) . 
				( ! empty( $validation_errors ) ? ' ' . implode( ' ', $validation_errors ) : '' )
			);
		}

		return $validated;
	}

	/**
	 * Validate block structure.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_block_structure( $block ) {
		// Check required block properties.
		if ( ! isset( $block['blockName'] ) || empty( $block['blockName'] ) ) {
			return new \WP_Error( 'missing_block_name', __( 'Block is missing required blockName property.', 'layoutberg' ) );
		}

		// Validate block name format.
		if ( ! preg_match( '/^[a-z0-9-]+\/[a-z0-9-]+$/', $block['blockName'] ) ) {
			return new \WP_Error( 'invalid_block_name', sprintf( __( 'Invalid block name format: %s', 'layoutberg' ), $block['blockName'] ) );
		}

		return true;
	}

	/**
	 * Validate block nesting rules.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_block_nesting( $block ) {
		$nesting_rules = array(
			'core/buttons' => array( 'core/button' ),
			'core/columns' => array( 'core/column' ),
			'core/list' => array( 'core/list-item' ),
			'core/social-links' => array( 'core/social-link' ),
			'core/navigation' => array( 'core/navigation-link', 'core/navigation-submenu' ),
		);

		// Check if block has nesting rules.
		if ( isset( $nesting_rules[ $block['blockName'] ] ) && ! empty( $block['innerBlocks'] ) ) {
			$allowed_children = $nesting_rules[ $block['blockName'] ];
			
			foreach ( $block['innerBlocks'] as $inner_block ) {
				if ( ! empty( $inner_block['blockName'] ) && ! in_array( $inner_block['blockName'], $allowed_children, true ) ) {
					return new \WP_Error(
						'invalid_nesting',
						sprintf(
							__( 'Block "%s" cannot contain "%s". Allowed children: %s', 'layoutberg' ),
							$block['blockName'],
							$inner_block['blockName'],
							implode( ', ', $allowed_children )
						)
					);
				}
			}
		}

		// Check for blocks that shouldn't have inner blocks.
		$no_inner_blocks = array( 'core/image', 'core/spacer', 'core/separator', 'core/html' );
		if ( in_array( $block['blockName'], $no_inner_blocks, true ) && ! empty( $block['innerBlocks'] ) ) {
			return new \WP_Error(
				'unexpected_inner_blocks',
				sprintf( __( 'Block "%s" should not contain inner blocks.', 'layoutberg' ), $block['blockName'] )
			);
		}

		return true;
	}

	/**
	 * Validate specific block types.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_specific_block( &$block ) {
		switch ( $block['blockName'] ) {
			case 'core/columns':
				return $this->validate_columns_block( $block );

			case 'core/column':
				return $this->validate_column_block( $block );

			case 'core/heading':
				return $this->validate_heading_block( $block );

			case 'core/paragraph':
				return $this->validate_paragraph_block( $block );

			case 'core/image':
				return $this->validate_image_block( $block );

			case 'core/button':
			case 'core/buttons':
				return $this->validate_button_block( $block );

			case 'core/list':
				return $this->validate_list_block( $block );

			case 'core/group':
				return $this->validate_group_block( $block );

			case 'core/cover':
				return $this->validate_cover_block( $block );

			case 'core/media-text':
				return $this->validate_media_text_block( $block );

			case 'core/spacer':
				return $this->validate_spacer_block( $block );

			case 'core/separator':
				return $this->validate_separator_block( $block );
				
			case 'core/details':
				return $this->validate_details_block( $block );
				
			case 'core/table':
				return $this->validate_table_block( $block );
				
			case 'core/gallery':
				return $this->validate_gallery_block( $block );
				
			case 'core/social-links':
				return $this->validate_social_links_block( $block );
		}

		return true;
	}

	/**
	 * Validate columns block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_columns_block( $block ) {
		// Validate columns have column children.
		if ( empty( $block['innerBlocks'] ) ) {
			return new \WP_Error( 'columns_missing_columns', __( 'Columns block must contain at least one column.', 'layoutberg' ) );
		}

		// Validate all children are columns.
		foreach ( $block['innerBlocks'] as $inner_block ) {
			if ( $inner_block['blockName'] !== 'core/column' ) {
				return new \WP_Error( 'columns_invalid_child', __( 'Columns block can only contain column blocks.', 'layoutberg' ) );
			}
		}

		// Validate column count is reasonable.
		if ( count( $block['innerBlocks'] ) > 6 ) {
			return new \WP_Error( 'columns_too_many', __( 'Columns block should not contain more than 6 columns for responsive design.', 'layoutberg' ) );
		}

		return true;
	}

	/**
	 * Validate column block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_column_block( $block ) {
		// Validate column width if specified.
		if ( isset( $block['attrs']['width'] ) ) {
			$width = $block['attrs']['width'];
			$numeric_width = str_replace( '%', '', $width );
			
			if ( ! is_numeric( $numeric_width ) ) {
				return new \WP_Error( 'invalid_column_width', __( 'Column width must be a valid percentage.', 'layoutberg' ) );
			}

			$width_value = floatval( $numeric_width );
			if ( $width_value <= 0 || $width_value > 100 ) {
				return new \WP_Error( 'invalid_column_width_range', __( 'Column width must be between 1% and 100%.', 'layoutberg' ) );
			}
		}

		return true;
	}

	/**
	 * Validate heading block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_heading_block( $block ) {
		// Validate heading level.
		if ( isset( $block['attrs']['level'] ) ) {
			$level = intval( $block['attrs']['level'] );
			if ( $level < 1 || $level > 6 ) {
				return new \WP_Error( 'invalid_heading_level', __( 'Heading level must be between 1 and 6.', 'layoutberg' ) );
			}
		}

		// Validate heading has content.
		if ( empty( $block['innerHTML'] ) || strip_tags( $block['innerHTML'] ) === '' ) {
			return new \WP_Error( 'heading_empty', __( 'Heading block must contain text content.', 'layoutberg' ) );
		}

		// Check for overly long headings.
		$heading_text = strip_tags( $block['innerHTML'] );
		if ( strlen( $heading_text ) > 200 ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'LayoutBerg Warning: Heading text is very long, consider shortening for better readability.' );
			}
		}

		return true;
	}

	/**
	 * Validate paragraph block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_paragraph_block( $block ) {
		// Validate paragraph has content.
		if ( empty( $block['innerHTML'] ) || strip_tags( $block['innerHTML'] ) === '' ) {
			return new \WP_Error( 'paragraph_empty', __( 'Paragraph block must contain text content.', 'layoutberg' ) );
		}

		// Check for proper text content (not just whitespace).
		$text_content = trim( strip_tags( $block['innerHTML'] ) );
		if ( empty( $text_content ) ) {
			return new \WP_Error( 'paragraph_whitespace_only', __( 'Paragraph block must contain meaningful text content.', 'layoutberg' ) );
		}

		return true;
	}

	/**
	 * Validate image block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_image_block( &$block ) {
		// Check for alt text for accessibility.
		$has_alt = false;
		
		if ( isset( $block['attrs']['alt'] ) && ! empty( $block['attrs']['alt'] ) ) {
			$has_alt = true;
		} elseif ( strpos( $block['innerHTML'], 'alt=' ) !== false ) {
			$has_alt = true;
		}

		if ( ! $has_alt ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'LayoutBerg Warning: Image block missing alt text for accessibility.' );
			}
		}

		// For image blocks in AI generation, allow placehold.co URLs
		if ( isset( $block['attrs']['url'] ) ) {
			$url = $block['attrs']['url'];
			// Only allow placehold.co URLs
			if ( strpos( $url, 'https://placehold.co/' ) !== 0 ) {
				// Replace with a placehold.co URL
				$block['attrs']['url'] = 'https://placehold.co/600x400/007cba/ffffff?text=Placeholder+Image';
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'LayoutBerg: Replaced non-placehold.co URL with placeholder' );
				}
			}
			// Ensure alt text exists
			if ( empty( $block['attrs']['alt'] ) ) {
				$block['attrs']['alt'] = __( 'Placeholder image', 'layoutberg' );
			}
		}

		// Check for reasonable image dimensions.
		if ( isset( $block['attrs']['width'] ) && isset( $block['attrs']['height'] ) ) {
			$width = intval( $block['attrs']['width'] );
			$height = intval( $block['attrs']['height'] );
			
			if ( $width > 5000 || $height > 5000 ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'LayoutBerg Warning: Image dimensions are very large, consider optimizing for web.' );
				}
			}
		}

		return true;
	}

	/**
	 * Validate button block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_button_block( $block ) {
		if ( $block['blockName'] === 'core/buttons' ) {
			// Validate buttons container has button children.
			if ( empty( $block['innerBlocks'] ) ) {
				return new \WP_Error( 'buttons_empty', __( 'Buttons block must contain at least one button.', 'layoutberg' ) );
			}

			// Check each button.
			foreach ( $block['innerBlocks'] as $button ) {
				$button_validation = $this->validate_button_block( $button );
				if ( is_wp_error( $button_validation ) ) {
					return $button_validation;
				}
			}

			return true;
		}

		// Validate individual button.
		if ( empty( $block['innerHTML'] ) || strpos( $block['innerHTML'], '</a>' ) === false ) {
			return new \WP_Error( 'button_missing_text', __( 'Button block must contain link text.', 'layoutberg' ) );
		}

		// Check for meaningful button text.
		if ( preg_match( '/<a[^>]*>(.*?)<\/a>/i', $block['innerHTML'], $matches ) ) {
			$button_text = trim( strip_tags( $matches[1] ) );
			$generic_texts = array( 'click here', 'read more', 'link', 'button' );
			
			if ( in_array( strtolower( $button_text ), $generic_texts, true ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'LayoutBerg Warning: Button has generic text, consider more descriptive text for better UX.' );
				}
			}
		}

		return true;
	}

	/**
	 * Validate list block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_list_block( $block ) {
		// Validate list has items.
		if ( empty( $block['innerBlocks'] ) ) {
			return new \WP_Error( 'list_empty', __( 'List block must contain at least one list item.', 'layoutberg' ) );
		}

		// Validate all children are list items.
		foreach ( $block['innerBlocks'] as $item ) {
			if ( $item['blockName'] !== 'core/list-item' ) {
				return new \WP_Error( 'list_invalid_child', __( 'List block can only contain list-item blocks.', 'layoutberg' ) );
			}
		}

		return true;
	}

	/**
	 * Validate group block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_group_block( $block ) {
		// Group blocks should have content.
		if ( empty( $block['innerBlocks'] ) ) {
			return new \WP_Error( 'group_empty', __( 'Group block should contain other blocks.', 'layoutberg' ) );
		}

		return true;
	}

	/**
	 * Validate cover block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_cover_block( &$block ) {
		// Check overlay settings.
		if ( isset( $block['attrs']['dimRatio'] ) ) {
			$dim_ratio = intval( $block['attrs']['dimRatio'] );
			if ( $dim_ratio < 0 || $dim_ratio > 100 ) {
				return new \WP_Error( 'invalid_dim_ratio', __( 'Cover block dim ratio must be between 0 and 100.', 'layoutberg' ) );
			}
		}

		// For cover blocks, we prefer background colors/gradients over images
		// If URL is present but invalid, remove it and suggest using colors instead
		if ( isset( $block['attrs']['url'] ) ) {
			unset( $block['attrs']['url'] );
			// Ensure the block has a background color or gradient
			if ( ! isset( $block['attrs']['gradient'] ) && ! isset( $block['attrs']['backgroundColor'] ) && ! isset( $block['attrs']['customBackgroundColor'] ) ) {
				// Add a default gradient
				$block['attrs']['gradient'] = 'cool-to-warm-spectrum';
			}
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'LayoutBerg: Removed image URL from cover block, using background color/gradient instead' );
			}
		}

		return true;
	}

	/**
	 * Validate media-text block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_media_text_block( $block ) {
		// Should have inner blocks for content.
		if ( empty( $block['innerBlocks'] ) ) {
			return new \WP_Error( 'media_text_empty', __( 'Media & Text block should contain content blocks.', 'layoutberg' ) );
		}

		return true;
	}

	/**
	 * Validate spacer block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_spacer_block( $block ) {
		// Check for reasonable height.
		if ( isset( $block['attrs']['height'] ) ) {
			$height = $block['attrs']['height'];
			$numeric_height = intval( str_replace( array( 'px', 'rem', 'em' ), '', $height ) );
			
			if ( $numeric_height > 500 ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'LayoutBerg Warning: Spacer height is very large, consider using smaller values.' );
				}
			}
		}

		return true;
	}

	/**
	 * Validate separator block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_separator_block( $block ) {
		// Separator blocks are generally self-contained and valid.
		return true;
	}

	/**
	 * Validate details block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_details_block( $block ) {
		// Details blocks should have content
		if ( empty( $block['innerBlocks'] ) && empty( $block['innerHTML'] ) ) {
			return new \WP_Error( 'details_empty', __( 'Details block should contain summary and content.', 'layoutberg' ) );
		}
		
		// Check for summary in innerHTML
		if ( strpos( $block['innerHTML'], '<summary>' ) === false ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'LayoutBerg Warning: Details block missing summary element.' );
			}
		}
		
		return true;
	}

	/**
	 * Validate table block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_table_block( $block ) {
		// Table should have content
		if ( empty( $block['innerHTML'] ) || strpos( $block['innerHTML'], '<table' ) === false ) {
			return new \WP_Error( 'table_empty', __( 'Table block must contain a table element.', 'layoutberg' ) );
		}
		
		// Check for proper table structure
		if ( strpos( $block['innerHTML'], '<thead' ) === false && strpos( $block['innerHTML'], '<tbody' ) === false ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'LayoutBerg Warning: Table missing thead or tbody elements.' );
			}
		}
		
		return true;
	}

	/**
	 * Validate gallery block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_gallery_block( $block ) {
		// Gallery should have image blocks
		if ( empty( $block['innerBlocks'] ) ) {
			return new \WP_Error( 'gallery_empty', __( 'Gallery block must contain at least one image.', 'layoutberg' ) );
		}
		
		// Validate all children are images
		foreach ( $block['innerBlocks'] as $image ) {
			if ( $image['blockName'] !== 'core/image' ) {
				return new \WP_Error( 'gallery_invalid_child', __( 'Gallery block can only contain image blocks.', 'layoutberg' ) );
			}
		}
		
		// Check for reasonable gallery size
		if ( count( $block['innerBlocks'] ) > 20 ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'LayoutBerg Warning: Gallery contains many images, consider pagination.' );
			}
		}
		
		return true;
	}

	/**
	 * Validate social links block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return true|WP_Error True if valid, error otherwise.
	 */
	private function validate_social_links_block( $block ) {
		// Social links should have social link children
		if ( empty( $block['innerBlocks'] ) ) {
			return new \WP_Error( 'social_links_empty', __( 'Social links block must contain at least one social link.', 'layoutberg' ) );
		}
		
		// Validate all children are social links
		foreach ( $block['innerBlocks'] as $link ) {
			if ( $link['blockName'] !== 'core/social-link' ) {
				return new \WP_Error( 'social_links_invalid_child', __( 'Social links block can only contain social link blocks.', 'layoutberg' ) );
			}
			
			// Validate service attribute
			if ( ! isset( $link['attrs']['service'] ) ) {
				return new \WP_Error( 'social_link_missing_service', __( 'Social link must specify a service.', 'layoutberg' ) );
			}
		}
		
		return true;
	}

	/**
	 * Validate block attributes.
	 *
	 * @since 1.0.0
	 * @param array $block Block to validate.
	 * @return array Validated block.
	 */
	private function validate_block_attributes( $block ) {
		// Get block type.
		$block_type = \WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );

		if ( ! $block_type ) {
			return $block;
		}

		// Validate attributes against schema.
		if ( ! empty( $block_type->attributes ) && ! empty( $block['attrs'] ) ) {
			foreach ( $block['attrs'] as $attr_name => $attr_value ) {
				if ( ! isset( $block_type->attributes[ $attr_name ] ) ) {
					// Remove unknown attributes.
					unset( $block['attrs'][ $attr_name ] );
				}
			}
		}

		return $block;
	}

	/**
	 * Get allowed blocks.
	 *
	 * @since 1.0.0
	 * @return array Allowed block names.
	 */
	private function get_allowed_blocks() {
		$core_blocks = array(
			// Layout blocks.
			'core/columns',
			'core/column',
			'core/group',
			'core/cover',
			'core/media-text',
			'core/separator',
			'core/spacer',

			// Text blocks.
			'core/paragraph',
			'core/heading',
			'core/list',
			'core/list-item',
			'core/quote',
			'core/pullquote',
			'core/verse',
			'core/preformatted',
			'core/code',
			'core/details',  // Added for FAQ sections

			// Media blocks.
			'core/image',
			'core/gallery',
			'core/audio',
			'core/video',
			'core/file',

			// Design blocks.
			'core/button',
			'core/buttons',
			'core/text-columns',
			'core/more',
			'core/nextpage',
			'core/page-break',
			'core/table',

			// Widget blocks.
			'core/shortcode',
			'core/html',
			'core/latest-posts',
			'core/latest-comments',
			'core/archives',
			'core/categories',
			'core/search',
			'core/calendar',
			'core/rss',
			'core/social-links',
			'core/social-link',
			'core/tag-cloud',

			// Theme blocks.
			'core/site-logo',
			'core/site-title',
			'core/site-tagline',
			'core/query',
			'core/posts-list',
			'core/avatar',
			'core/post-title',
			'core/post-excerpt',
			'core/post-featured-image',
			'core/post-content',
			'core/post-author',
			'core/post-date',
			'core/post-terms',
			'core/post-navigation-link',
			'core/read-more',
			'core/comments',
			'core/comment-template',
			'core/comment-author-name',
			'core/comment-content',
			'core/comment-date',
			'core/comment-edit-link',
			'core/comment-reply-link',
			'core/navigation',
			'core/navigation-link',
			'core/navigation-submenu',

			// Embed blocks.
			'core/embed',
		);

		// Allow filtering of allowed blocks.
		return apply_filters( 'layoutberg_allowed_blocks', $core_blocks );
	}

	/**
	 * Convert blocks to HTML.
	 *
	 * @since 1.0.0
	 * @param array $blocks Blocks to convert.
	 * @return string HTML output.
	 */
	private function blocks_to_html( $blocks ) {
		$html = '';

		foreach ( $blocks as $block ) {
			$html .= render_block( $block );
		}

		return $html;
	}

	/**
	 * Get cache key for prompt.
	 *
	 * @since 1.0.0
	 * @param string $prompt  Prompt.
	 * @param array  $options Options.
	 * @return string Cache key.
	 */
	private function get_cache_key( $prompt, $options ) {
		// Normalize options for consistent caching.
		$normalized_options = $this->normalize_cache_options( $options );
		
		// Include user context for user-specific caching.
		$user_id = get_current_user_id();
		$user_role = $this->get_user_primary_role( $user_id );
		
		// Include site-specific context.
		$site_context = array(
			'theme' => get_option( 'stylesheet' ),
			'locale' => get_locale(),
			'version' => LAYOUTBERG_VERSION,
		);
		
		// Build cache key components.
		$key_data = array(
			'prompt' => trim( strtolower( $prompt ) ),
			'options' => $normalized_options,
			'user_role' => $user_role,
			'context' => $site_context,
		);
		
		// Generate a more robust cache key.
		$cache_key = 'layoutberg_generation_' . md5( wp_json_encode( $key_data ) );
		
		// Apply filters for custom cache key modification.
		return apply_filters( 'layoutberg_cache_key', $cache_key, $prompt, $options, $user_id );
	}

	/**
	 * Normalize cache options for consistent caching.
	 *
	 * @since 1.0.0
	 * @param array $options Generation options.
	 * @return array Normalized options.
	 */
	private function normalize_cache_options( $options ) {
		$defaults = array(
			'style' => 'modern',
			'layout' => 'single-column',
			'model' => 'gpt-3.5-turbo',
			'temperature' => 0.7,
			'max_tokens' => 4000,
		);
		
		$options = wp_parse_args( $options, $defaults );
		
		// Normalize temperature to 1 decimal place.
		$options['temperature'] = round( floatval( $options['temperature'] ), 1 );
		
		// Normalize max_tokens to nearest 100.
		$options['max_tokens'] = round( intval( $options['max_tokens'] ) / 100 ) * 100;
		
		// Sort options for consistency.
		ksort( $options );
		
		return $options;
	}

	/**
	 * Get user's primary role.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return string Primary role.
	 */
	private function get_user_primary_role( $user_id ) {
		if ( ! $user_id ) {
			return 'guest';
		}
		
		$user = get_userdata( $user_id );
		if ( ! $user || empty( $user->roles ) ) {
			return 'subscriber';
		}
		
		return $user->roles[0];
	}

	/**
	 * Invalidate related cache entries.
	 *
	 * @since 1.0.0
	 * @param array $tags Cache tags to invalidate.
	 */
	public function invalidate_cache( $tags = array() ) {
		if ( empty( $tags ) ) {
			// Default invalidation: clear all generation cache.
			$this->cache_manager->flush();
			return;
		}
		
		foreach ( $tags as $tag ) {
			switch ( $tag ) {
				case 'user_generations':
					$this->invalidate_user_cache();
					break;
				case 'template_changes':
					$this->invalidate_template_cache();
					break;
				case 'settings_update':
					$this->invalidate_settings_cache();
					break;
				case 'plugin_update':
					$this->cache_manager->flush();
					break;
			}
		}
		
		// Fire action for custom cache invalidation.
		do_action( 'layoutberg_cache_invalidated', $tags );
	}

	/**
	 * Invalidate user-specific cache.
	 *
	 * @since 1.0.0
	 */
	private function invalidate_user_cache() {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}
		
		// Get all cache keys for this user and delete them.
		// This is a simplified approach - in production, you might want to use cache tags.
		$cache_pattern = 'layoutberg_generation_*';
		
		// For now, we'll use a hook that cache plugins can implement.
		do_action( 'layoutberg_invalidate_user_cache', $user_id, $cache_pattern );
	}

	/**
	 * Invalidate template-related cache.
	 *
	 * @since 1.0.0
	 */
	private function invalidate_template_cache() {
		// Clear predefined templates cache.
		$this->cache_manager->delete( 'layoutberg_predefined_templates' );
		$this->cache_manager->delete( 'layoutberg_popular_templates' );
		
		// Clear any template-specific generation cache.
		do_action( 'layoutberg_invalidate_template_cache' );
	}

	/**
	 * Invalidate settings-related cache.
	 *
	 * @since 1.0.0
	 */
	private function invalidate_settings_cache() {
		// When settings change, cached generations might be affected.
		// Clear all cache to be safe.
		$this->cache_manager->flush();
		
		do_action( 'layoutberg_invalidate_settings_cache' );
	}

	/**
	 * Get predefined templates.
	 *
	 * @since 1.0.0
	 * @return array Templates.
	 */
	public function get_predefined_templates() {
		return array(
			'hero'      => array(
				'name'        => __( 'Hero Section', 'layoutberg' ),
				'description' => __( 'Eye-catching hero section with headline and call-to-action', 'layoutberg' ),
				'prompt'      => 'Create a hero section with a compelling headline, subheadline, and call-to-action button',
			),
			'features'  => array(
				'name'        => __( 'Features Grid', 'layoutberg' ),
				'description' => __( 'Grid layout showcasing product or service features', 'layoutberg' ),
				'prompt'      => 'Create a 3-column features section with icons, headings, and descriptions',
			),
			'about'     => array(
				'name'        => __( 'About Section', 'layoutberg' ),
				'description' => __( 'Company or personal introduction with image', 'layoutberg' ),
				'prompt'      => 'Create an about section with text on one side and an image on the other',
			),
			'testimonials' => array(
				'name'        => __( 'Testimonials', 'layoutberg' ),
				'description' => __( 'Customer testimonials in a grid or carousel', 'layoutberg' ),
				'prompt'      => 'Create a testimonials section with 3 customer reviews including names and ratings',
			),
			'cta'       => array(
				'name'        => __( 'Call to Action', 'layoutberg' ),
				'description' => __( 'Compelling call-to-action section', 'layoutberg' ),
				'prompt'      => 'Create a call-to-action section with a strong headline and button',
			),
			'pricing'   => array(
				'name'        => __( 'Pricing Table', 'layoutberg' ),
				'description' => __( 'Pricing plans comparison table', 'layoutberg' ),
				'prompt'      => 'Create a 3-column pricing table with features and call-to-action buttons',
			),
			'contact'   => array(
				'name'        => __( 'Contact Section', 'layoutberg' ),
				'description' => __( 'Contact information and form', 'layoutberg' ),
				'prompt'      => 'Create a contact section with contact details and a contact form placeholder',
			),
			'portfolio' => array(
				'name'        => __( 'Portfolio Grid', 'layoutberg' ),
				'description' => __( 'Project showcase in grid layout', 'layoutberg' ),
				'prompt'      => 'Create a portfolio grid with 6 project items including images and titles',
			),
			'team'      => array(
				'name'        => __( 'Team Members', 'layoutberg' ),
				'description' => __( 'Team member profiles with photos', 'layoutberg' ),
				'prompt'      => 'Create a team section with 4 team member cards including photos, names, and roles',
			),
			'faq'       => array(
				'name'        => __( 'FAQ Section', 'layoutberg' ),
				'description' => __( 'Frequently asked questions', 'layoutberg' ),
				'prompt'      => 'Create an FAQ section with 5 common questions and answers',
			),
		);
	}
}