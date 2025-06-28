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
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->api_client    = new API_Client();
		$this->cache_manager = new Cache_Manager();
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

		// Prepare response.
		$response = array(
			'blocks'   => $validated,
			'html'     => $this->blocks_to_html( $validated ),
			'raw'      => $result['content'],
			'usage'    => $result['usage'],
			'model'    => $result['model'],
			'metadata' => array(
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
		// Remove any markdown code blocks if present.
		$content = preg_replace( '/```[\s\S]*?```/', '', $content );
		$content = trim( $content );

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

		// Filter out empty blocks.
		$blocks = array_filter( $blocks, function( $block ) {
			return ! empty( $block['blockName'] );
		});

		return $blocks;
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

		foreach ( $blocks as $block ) {
			// Check if block is allowed.
			if ( ! in_array( $block['blockName'], $allowed_blocks, true ) ) {
				continue; // Skip disallowed blocks.
			}

			// Validate block attributes.
			$block = $this->validate_block_attributes( $block );

			// Recursively validate inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$inner_validated = $this->validate_blocks( $block['innerBlocks'] );
				if ( is_wp_error( $inner_validated ) ) {
					return $inner_validated;
				}
				$block['innerBlocks'] = $inner_validated;
			}

			$validated[] = $block;
		}

		if ( empty( $validated ) ) {
			return new \WP_Error(
				'no_valid_blocks',
				__( 'No valid blocks found in the generated content.', 'layoutberg' )
			);
		}

		return $validated;
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