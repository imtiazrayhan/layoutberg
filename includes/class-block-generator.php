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

		// Use single parsing approach to avoid validation issues
		$content = $result['content'];
		
		// Remove markdown code blocks if present
		$content = preg_replace( '/^```html\s*/', '', $content );
		$content = preg_replace( '/```$/', '', $content );
		$content = trim( $content );

		// Basic validation - just check if it looks like blocks
		if ( ! preg_match( '/<!-- wp:/', $content ) ) {
			return new \WP_Error(
				'invalid_block_markup',
				__( 'Generated content does not contain valid block markup.', 'layoutberg' )
			);
		}

		// Use wp_kses_post for basic validation (like the working plugin)
		$content = wp_kses_post( $content );

		// DO NOT parse blocks in PHP - let JavaScript handle it
		// This matches Pattern Pal's approach and avoids double parsing

		// Prepare response with raw content for JavaScript to parse
		$response = array(
			'blocks'     => $content, // Return raw content, not parsed blocks
			'serialized' => $content, // Same raw content for compatibility
			'html'       => do_blocks( $content ), // Generate preview HTML
			'raw'        => $result['content'], // Original unprocessed content
			'usage'      => $result['usage'],
			'model'      => $result['model'],
			'prompts'    => isset( $result['prompts'] ) ? $result['prompts'] : null,
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
		$content = preg_replace_callback(
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
		$content = preg_replace_callback(
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
		
		// Fix image src attributes - ensure absolute URLs
		$content = preg_replace_callback(
			'/<img\s+([^>]*src=")([^"]+)("[^>]*>)/i',
			function( $matches ) {
				$before_src = $matches[1];
				$url = $matches[2];
				$after_src = $matches[3];
				
				// Check if URL is already absolute and from allowed source
				if ( preg_match( '/^https?:\/\//i', $url ) && $this->is_allowed_image_url( $url ) ) {
					return $matches[0];
				}
				
				// Generate appropriate placeholder
				$placeholder_url = $this->generate_placeholder_url();
				
				return $before_src . $placeholder_url . $after_src;
			},
			$content
		);
		
		// Fix image URLs in block attributes
		$content = preg_replace_callback(
			'/("url"\s*:\s*")([^"]+)(")/i',
			function( $matches ) {
				$before_url = $matches[1];
				$url = $matches[2];
				$after_url = $matches[3];
				
				// Check if URL is already absolute and from allowed source
				if ( preg_match( '/^https?:\/\//i', $url ) && $this->is_allowed_image_url( $url ) ) {
					return $matches[0];
				}
				
				// Generate appropriate placeholder
				$placeholder_url = $this->generate_placeholder_url();
				
				return $before_url . $placeholder_url . $after_url;
			},
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

			// Additional block-specific validation (passed by reference to allow fixes).
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
			// Add default alt text
			$block['attrs']['alt'] = __( 'Decorative image', 'layoutberg' );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'LayoutBerg: Added default alt text to image block for accessibility.' );
			}
		}

		// CRITICAL: Fix image URLs to ensure absolute paths
		if ( isset( $block['attrs']['url'] ) ) {
			$url = $block['attrs']['url'];
			
			// Check if URL is relative or invalid
			if ( ! preg_match( '/^https?:\/\//i', $url ) ) {
				// Replace with appropriate placeholder based on context
				$dimensions = $this->determine_image_dimensions( $block );
				$block['attrs']['url'] = $this->generate_placeholder_url( $dimensions );
				
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'LayoutBerg: Replaced relative/invalid image URL "' . $url . '" with placeholder' );
				}
			}
			// Validate that URL is from allowed sources
			elseif ( ! $this->is_allowed_image_url( $url ) ) {
				// Replace with placeholder
				$dimensions = $this->determine_image_dimensions( $block );
				$block['attrs']['url'] = $this->generate_placeholder_url( $dimensions );
				
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'LayoutBerg: Replaced non-allowed image URL with placeholder' );
				}
			}
		}
		
		// Fix image URLs in innerHTML
		if ( isset( $block['innerHTML'] ) && ! empty( $block['innerHTML'] ) ) {
			$block['innerHTML'] = preg_replace_callback(
				'/src="([^"]+)"/i',
				function( $matches ) {
					$url = $matches[1];
					if ( ! preg_match( '/^https?:\/\//i', $url ) || ! $this->is_allowed_image_url( $url ) ) {
						return 'src="' . $this->generate_placeholder_url() . '"';
					}
					return $matches[0];
				},
				$block['innerHTML']
			);
		}

		// Check for reasonable image dimensions.
		if ( isset( $block['attrs']['width'] ) && isset( $block['attrs']['height'] ) ) {
			$width = intval( $block['attrs']['width'] );
			$height = intval( $block['attrs']['height'] );
			
			if ( $width > 2000 || $height > 2000 ) {
				// Cap dimensions
				if ( $width > $height ) {
					$ratio = $height / $width;
					$block['attrs']['width'] = 1600;
					$block['attrs']['height'] = intval( 1600 * $ratio );
				} else {
					$ratio = $width / $height;
					$block['attrs']['height'] = 1600;
					$block['attrs']['width'] = intval( 1600 * $ratio );
				}
				
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'LayoutBerg: Capped large image dimensions' );
				}
			}
		}

		return true;
	}

	/**
	 * Check if image URL is valid.
	 *
	 * @since 1.0.0
	 * @param string $url Image URL.
	 * @return bool True if valid.
	 */
	private function is_valid_image_url( $url ) {
		// Check if it's an absolute URL
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}
		
		// Check if it starts with https://
		if ( strpos( $url, 'https://' ) !== 0 ) {
			return false;
		}
		
		// Check if it's from allowed sources
		return $this->is_allowed_image_url( $url );
	}
	
	/**
	 * Fix invalid image URL.
	 *
	 * @since 1.0.0
	 * @param string $url Invalid image URL.
	 * @return string|false Fixed URL or false if cannot fix.
	 */
	private function fix_image_url( $url ) {
		// If it's a relative path or placeholder text, replace with a real image
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) || strpos( $url, 'http' ) !== 0 ) {
			// Generate a placeholder URL
			return $this->generate_placeholder_url();
		}
		
		// If it's http, convert to https
		if ( strpos( $url, 'http://' ) === 0 ) {
			$url = str_replace( 'http://', 'https://', $url );
		}
		
		// If it's still not from allowed sources, replace with placeholder
		if ( ! $this->is_allowed_image_url( $url ) ) {
			return $this->generate_placeholder_url();
		}
		
		return $url;
	}

	/**
	 * Check if image URL is from allowed sources.
	 *
	 * @since 1.0.0
	 * @param string $url Image URL.
	 * @return bool True if allowed.
	 */
	private function is_allowed_image_url( $url ) {
		$allowed_domains = array(
			'images.unsplash.com',
			'placehold.co',
			'via.placeholder.com',
			'picsum.photos',
			'source.unsplash.com'
		);
		
		foreach ( $allowed_domains as $domain ) {
			if ( strpos( $url, 'https://' . $domain . '/' ) === 0 ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Determine appropriate image dimensions.
	 *
	 * @since 1.0.0
	 * @param array $block Image block.
	 * @return array Width and height.
	 */
	private function determine_image_dimensions( $block ) {
		$width = 800;
		$height = 600;
		
		if ( isset( $block['attrs']['width'] ) && isset( $block['attrs']['height'] ) ) {
			$width = intval( $block['attrs']['width'] );
			$height = intval( $block['attrs']['height'] );
		} elseif ( isset( $block['attrs']['sizeSlug'] ) ) {
			switch ( $block['attrs']['sizeSlug'] ) {
				case 'thumbnail':
					$width = 150;
					$height = 150;
					break;
				case 'medium':
					$width = 300;
					$height = 300;
					break;
				case 'large':
					$width = 1024;
					$height = 768;
					break;
				case 'full':
					$width = 1600;
					$height = 1200;
					break;
			}
		}
		
		// Check for icon-like dimensions
		if ( $width <= 100 && $height <= 100 ) {
			$width = 64;
			$height = 64;
		}
		
		return array( 'width' => $width, 'height' => $height );
	}

	/**
	 * Generate placeholder URL.
	 *
	 * @since 1.0.0
	 * @param array $dimensions Optional dimensions.
	 * @return string Placeholder URL.
	 */
	private function generate_placeholder_url( $dimensions = array() ) {
		$width = isset( $dimensions['width'] ) ? $dimensions['width'] : 800;
		$height = isset( $dimensions['height'] ) ? $dimensions['height'] : 600;
		
		// For small images (icons), use placehold.co
		if ( $width <= 100 && $height <= 100 ) {
			$colors = array( '007cba', '0073aa', '005177', '00669b' );
			$color = $colors[ array_rand( $colors ) ];
			return 'https://placehold.co/' . $width . 'x' . $height . '/' . $color . '/ffffff?text=Icon';
		}
		
		// For larger images, use Unsplash
		$unsplash_ids = array(
			'photo-1497366216548-37526070297c', // Office
			'photo-1497366811353-6870744d04b2', // Office 2
			'photo-1497366754035-f200968a6e72', // Office 3
			'photo-1497366412874-3415097a27e7', // Abstract
			'photo-1517180102446-f3ece451e9d8', // Gradient
			'photo-1551434678-e076c223a692', // Team
			'photo-1522202176988-66273c2fd55f', // People
			'photo-1556761175-4b46a572b786'  // Meeting
		);
		
		return 'https://images.unsplash.com/' . $unsplash_ids[ array_rand( $unsplash_ids ) ];
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
	private function validate_list_block( &$block ) {
		// Fix empty lists by adding default items
		if ( empty( $block['innerBlocks'] ) ) {
			// Check if there's content in innerHTML that we can parse
			if ( ! empty( $block['innerHTML'] ) && preg_match_all( '/<li[^>]*>(.*?)<\/li>/s', $block['innerHTML'], $matches ) ) {
				// Create list items from innerHTML content
				$block['innerBlocks'] = array();
				foreach ( $matches[1] as $item_content ) {
					$block['innerBlocks'][] = array(
						'blockName' => 'core/list-item',
						'attrs' => array(),
						'innerBlocks' => array(),
						'innerHTML' => '<li>' . trim( $item_content ) . '</li>',
						'innerContent' => array( '<li>', trim( $item_content ), '</li>' ),
					);
				}
			} else {
				// Add default list items if completely empty
				$default_items = array(
					__( 'Feature or benefit one', 'layoutberg' ),
					__( 'Feature or benefit two', 'layoutberg' ),
					__( 'Feature or benefit three', 'layoutberg' ),
				);
				
				$block['innerBlocks'] = array();
				foreach ( $default_items as $item_text ) {
					$block['innerBlocks'][] = array(
						'blockName' => 'core/list-item',
						'attrs' => array(),
						'innerBlocks' => array(),
						'innerHTML' => '<li>' . esc_html( $item_text ) . '</li>',
						'innerContent' => array( '<li>', esc_html( $item_text ), '</li>' ),
					);
				}
				
				// Update innerHTML to match
				$list_tag = isset( $block['attrs']['ordered'] ) && $block['attrs']['ordered'] ? 'ol' : 'ul';
				$items_html = '';
				foreach ( $block['innerBlocks'] as $item ) {
					$items_html .= $item['innerHTML'];
				}
				$block['innerHTML'] = '<' . $list_tag . ' class="wp-block-list">' . $items_html . '</' . $list_tag . '>';
				
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'LayoutBerg: Fixed empty list block by adding default items' );
				}
			}
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

		// Determine if this is an image-based or gradient-based cover
		$has_image = isset( $block['attrs']['url'] ) && ! empty( $block['attrs']['url'] );
		$has_gradient = isset( $block['attrs']['gradient'] ) || isset( $block['attrs']['customGradient'] );
		$has_background_color = isset( $block['attrs']['backgroundColor'] ) || isset( $block['attrs']['customBackgroundColor'] );
		
		// Method 1: Image-based cover blocks
		if ( $has_image ) {
			// Validate the image URL
			$image_url = $block['attrs']['url'];
			if ( ! $this->is_valid_image_url( $image_url ) ) {
				// Fix the URL if possible
				$fixed_url = $this->fix_image_url( $image_url );
				if ( $fixed_url ) {
					$block['attrs']['url'] = $fixed_url;
					
					// Update innerHTML if needed
					if ( isset( $block['innerHTML'] ) && ! empty( $block['innerHTML'] ) ) {
						$block['innerHTML'] = str_replace( $image_url, $fixed_url, $block['innerHTML'] );
					}
				} else {
					// If we can't fix it, convert to gradient
					unset( $block['attrs']['url'] );
					$has_image = false;
				}
			}
			
			// Ensure proper image structure in innerHTML for image-based covers
			if ( $has_image && isset( $block['innerHTML'] ) ) {
				// Check if img tag exists with proper class
				if ( strpos( $block['innerHTML'], 'wp-block-cover__image-background' ) === false ) {
					// Fix innerHTML structure for image covers
					$img_tag = '<img class="wp-block-cover__image-background" alt="" src="' . esc_url( $block['attrs']['url'] ) . '" data-object-fit="cover"/>';
					$dim_class = isset( $block['attrs']['dimRatio'] ) && $block['attrs']['dimRatio'] >= 50 ? ' has-background-dim-' . $block['attrs']['dimRatio'] : '';
					$span_tag = '<span aria-hidden="true" class="wp-block-cover__background has-background-dim' . $dim_class . '"></span>';
					
					// Rebuild innerHTML with proper structure
					if ( preg_match( '/<div[^>]*class="[^"]*wp-block-cover[^"]*"[^>]*>(.*)(<div[^>]*class="[^"]*wp-block-cover__inner-container[^"]*"[^>]*>.*<\/div>)<\/div>/s', $block['innerHTML'], $matches ) ) {
						$block['innerHTML'] = str_replace( $matches[1], $img_tag . $span_tag, $block['innerHTML'] );
					}
				}
			}
		}
		
		// Method 2: Gradient or color-based cover blocks
		if ( ! $has_image && ! $has_gradient && ! $has_background_color ) {
			// List of available gradients
			$gradients = array(
				'cool-to-warm-spectrum',
				'vivid-cyan-blue-to-vivid-purple',
				'light-green-cyan-to-vivid-green-cyan',
				'luminous-vivid-amber-to-luminous-vivid-orange',
				'luminous-vivid-orange-to-vivid-red',
				'very-light-gray-to-cyan-bluish-gray',
				'blush-light-purple',
				'blush-bordeaux',
				'luminous-dusk',
				'pale-ocean',
				'electric-grass',
				'midnight'
			);
			
			// Pick a random gradient
			$block['attrs']['gradient'] = $gradients[ array_rand( $gradients ) ];
			
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'LayoutBerg: Added gradient ' . $block['attrs']['gradient'] . ' to cover block' );
			}
		}

		// Rebuild innerHTML to ensure proper structure
		if ( isset( $block['innerHTML'] ) ) {
			// Extract inner content
			$inner_content = '';
			if ( preg_match( '/<div[^>]*wp-block-cover__inner-container[^>]*>(.*?)<\/div>/is', $block['innerHTML'], $matches ) ) {
				$inner_content = $matches[1];
			}
			
			// Build proper classes
			$classes = 'wp-block-cover';
			if ( isset( $block['attrs']['align'] ) ) {
				$classes .= ' align' . $block['attrs']['align'];
			}
			if ( isset( $block['attrs']['isDark'] ) && $block['attrs']['isDark'] ) {
				$classes .= ' is-dark';
			}
			
			// Build style attribute
			$styles = array();
			if ( isset( $block['attrs']['minHeight'] ) ) {
				$unit = isset( $block['attrs']['minHeightUnit'] ) ? $block['attrs']['minHeightUnit'] : 'px';
				$styles[] = 'min-height:' . esc_attr( $block['attrs']['minHeight'] ) . $unit;
			}
			$style_attr = ! empty( $styles ) ? ' style="' . implode( ';', $styles ) . '"' : '';
			
			// Method 1: Image-based cover - rebuild with proper structure
			if ( $has_image ) {
				$img_tag = '<img class="wp-block-cover__image-background" alt="" src="' . esc_url( $block['attrs']['url'] ) . '" data-object-fit="cover"/>';
				$dim_class = '';
				if ( isset( $block['attrs']['dimRatio'] ) && $block['attrs']['dimRatio'] > 0 ) {
					$dim_class = ' has-background-dim';
					if ( $block['attrs']['dimRatio'] >= 50 ) {
						$dim_class .= ' has-background-dim-' . $block['attrs']['dimRatio'];
					}
				}
				$span_tag = '<span aria-hidden="true" class="wp-block-cover__background' . $dim_class . '"></span>';
				
				$block['innerHTML'] = '<div class="' . $classes . '"' . $style_attr . '>' . $img_tag . $span_tag . '<div class="wp-block-cover__inner-container">' . $inner_content . '</div></div>';
			}
			// Method 2: Gradient-based cover
			elseif ( $has_gradient ) {
				$gradient_class = '';
				$gradient_style = '';
				
				if ( isset( $block['attrs']['gradient'] ) ) {
					$gradient_class = ' has-background-gradient has-' . $block['attrs']['gradient'] . '-gradient-background';
				} elseif ( isset( $block['attrs']['customGradient'] ) ) {
					$gradient_class = ' has-background-gradient';
					$gradient_style = ' style="background:' . esc_attr( $block['attrs']['customGradient'] ) . '"';
				}
				
				$span_tag = '<span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim' . $gradient_class . '"' . $gradient_style . '></span>';
				
				$block['innerHTML'] = '<div class="' . $classes . '"' . $style_attr . '>' . $span_tag . '<div class="wp-block-cover__inner-container">' . $inner_content . '</div></div>';
			}
			// Method 3: Color-based cover
			elseif ( $has_background_color ) {
				$color_class = '';
				$color_style = '';
				
				if ( isset( $block['attrs']['backgroundColor'] ) ) {
					$color_class = ' has-' . $block['attrs']['backgroundColor'] . '-background-color has-background';
				} elseif ( isset( $block['attrs']['customBackgroundColor'] ) ) {
					$color_style = ' style="background-color:' . esc_attr( $block['attrs']['customBackgroundColor'] ) . '"';
				}
				
				$span_tag = '<span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim' . $color_class . '"' . $color_style . '></span>';
				
				$block['innerHTML'] = '<div class="' . $classes . '"' . $style_attr . '>' . $span_tag . '<div class="wp-block-cover__inner-container">' . $inner_content . '</div></div>';
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
	private function validate_details_block( &$block ) {
		// Details blocks should have content
		if ( empty( $block['innerBlocks'] ) && empty( $block['innerHTML'] ) ) {
			return new \WP_Error( 'details_empty', __( 'Details block should contain summary and content.', 'layoutberg' ) );
		}
		
		// Check for invalid core/summary blocks in innerBlocks
		if ( ! empty( $block['innerBlocks'] ) ) {
			$filtered_inner_blocks = array();
			foreach ( $block['innerBlocks'] as $inner_block ) {
				// Skip any invalid core/summary blocks
				if ( $inner_block['blockName'] === 'core/summary' ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'LayoutBerg: Removing invalid core/summary block from details block. Summary should be an attribute, not a separate block.' );
					}
					continue;
				}
				$filtered_inner_blocks[] = $inner_block;
			}
			$block['innerBlocks'] = $filtered_inner_blocks;
		}
		
		// Ensure summary attribute exists
		if ( ! isset( $block['attrs']['summary'] ) || empty( $block['attrs']['summary'] ) ) {
			// Try to extract summary from innerHTML
			if ( preg_match( '/<summary[^>]*>(.*?)<\/summary>/is', $block['innerHTML'], $matches ) ) {
				$block['attrs']['summary'] = wp_strip_all_tags( $matches[1] );
			} else {
				// Add default summary
				$block['attrs']['summary'] = 'Click to expand';
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'LayoutBerg: Added default summary to details block.' );
				}
			}
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

		// Wrap in try-catch to prevent fatal errors from invalid blocks
		try {
			foreach ( $blocks as $block ) {
				// Skip null or invalid blocks
				if ( empty( $block ) || ! is_array( $block ) ) {
					continue;
				}
				
				// Ensure block has required structure
				if ( ! isset( $block['blockName'] ) ) {
					continue;
				}
				
				// Try to render the block
				$rendered = @render_block( $block );
				if ( $rendered !== false ) {
					$html .= $rendered;
				}
			}
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'LayoutBerg: Error rendering blocks - ' . $e->getMessage() );
			}
			// Return what we have so far
			return $html;
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
		// Return dynamic templates that will use our variation system
		return array(
			'hero'      => array(
				'name'        => __( 'Hero Section', 'layoutberg' ),
				'description' => __( 'Dynamic hero section with varied layouts', 'layoutberg' ),
				'prompt'      => 'Create a hero section',
				'variations'  => true,
			),
			'features'  => array(
				'name'        => __( 'Features Grid', 'layoutberg' ),
				'description' => __( 'Feature showcase with dynamic column layouts', 'layoutberg' ),
				'prompt'      => 'Create a features section',
				'variations'  => true,
			),
			'about'     => array(
				'name'        => __( 'About Section', 'layoutberg' ),
				'description' => __( 'Company introduction with varied layouts', 'layoutberg' ),
				'prompt'      => 'Create an about section',
				'variations'  => true,
			),
			'testimonials' => array(
				'name'        => __( 'Testimonials', 'layoutberg' ),
				'description' => __( 'Customer testimonials with dynamic arrangements', 'layoutberg' ),
				'prompt'      => 'Create a testimonials section',
				'variations'  => true,
			),
			'cta'       => array(
				'name'        => __( 'Call to Action', 'layoutberg' ),
				'description' => __( 'Dynamic call-to-action sections', 'layoutberg' ),
				'prompt'      => 'Create a call-to-action section',
				'variations'  => true,
			),
			'pricing'   => array(
				'name'        => __( 'Pricing Table', 'layoutberg' ),
				'description' => __( 'Pricing plans with varied layouts', 'layoutberg' ),
				'prompt'      => 'Create a pricing section',
				'variations'  => true,
			),
			'contact'   => array(
				'name'        => __( 'Contact Section', 'layoutberg' ),
				'description' => __( 'Contact information with dynamic layouts', 'layoutberg' ),
				'prompt'      => 'Create a contact section',
				'variations'  => true,
			),
			'portfolio' => array(
				'name'        => __( 'Portfolio Grid', 'layoutberg' ),
				'description' => __( 'Project showcase with varied grid layouts', 'layoutberg' ),
				'prompt'      => 'Create a portfolio section',
				'variations'  => true,
			),
			'team'      => array(
				'name'        => __( 'Team Members', 'layoutberg' ),
				'description' => __( 'Team profiles with dynamic arrangements', 'layoutberg' ),
				'prompt'      => 'Create a team section',
				'variations'  => true,
			),
			'faq'       => array(
				'name'        => __( 'FAQ Section', 'layoutberg' ),
				'description' => __( 'FAQ with varied layouts', 'layoutberg' ),
				'prompt'      => 'Create an FAQ section',
				'variations'  => true,
			),
		);
	}

	/**
	 * Check if pattern variations are enabled.
	 *
	 * @since 1.0.0
	 * @return bool True if enabled.
	 */
	private function use_pattern_variations() {
		// Always use variations for better results
		return true;
	}
}