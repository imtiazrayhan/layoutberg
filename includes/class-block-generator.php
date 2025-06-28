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
		$this->api_client    = new API_Client();
		$this->cache_manager = new Cache_Manager();
		$this->block_serializer = new Block_Serializer();
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
			return $cached;
		}

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
	private function validate_specific_block( $block ) {
		switch ( $block['blockName'] ) {
			case 'core/columns':
				// Validate columns have column children.
				if ( empty( $block['innerBlocks'] ) ) {
					return new \WP_Error( 'columns_missing_columns', __( 'Columns block must contain at least one column.', 'layoutberg' ) );
				}
				break;

			case 'core/column':
				// Validate column width if specified.
				if ( isset( $block['attrs']['width'] ) ) {
					$width = $block['attrs']['width'];
					if ( ! is_numeric( str_replace( '%', '', $width ) ) ) {
						return new \WP_Error( 'invalid_column_width', __( 'Column width must be a valid percentage.', 'layoutberg' ) );
					}
				}
				break;

			case 'core/heading':
				// Validate heading level.
				if ( isset( $block['attrs']['level'] ) ) {
					$level = intval( $block['attrs']['level'] );
					if ( $level < 1 || $level > 6 ) {
						return new \WP_Error( 'invalid_heading_level', __( 'Heading level must be between 1 and 6.', 'layoutberg' ) );
					}
				}
				break;

			case 'core/image':
				// Validate image has alt text for accessibility.
				if ( empty( $block['attrs']['alt'] ) && strpos( $block['innerHTML'], 'alt=' ) === false ) {
					// Add a warning but don't fail validation.
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'LayoutBerg Warning: Image block missing alt text for accessibility.' );
					}
				}
				break;

			case 'core/button':
				// Validate button has link text.
				if ( empty( $block['innerHTML'] ) || strpos( $block['innerHTML'], '</a>' ) === false ) {
					return new \WP_Error( 'button_missing_text', __( 'Button block must contain link text.', 'layoutberg' ) );
				}
				break;
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
		return 'layoutberg_' . md5( $prompt . wp_json_encode( $options ) );
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