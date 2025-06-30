<?php
/**
 * Simplified block generator with minimal validation.
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
 * Simple block generator class.
 *
 * @since 1.0.0
 */
class Simple_Block_Generator {

	/**
	 * API client instance.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    API_Client
	 */
	private $api_client;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->api_client = new API_Client();
	}

	/**
	 * Generate layout from prompt with minimal processing.
	 *
	 * @since 1.0.0
	 * @param string $prompt  User prompt.
	 * @param array  $options Generation options.
	 * @return array|WP_Error Generated layout or error.
	 */
	public function generate( $prompt, $options = array() ) {
		// Generate via API using simplified method
		$result = $this->api_client->generate_layout_simple( $prompt, $options );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Clean up response minimally (like the working plugin)
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

		// Parse blocks using WordPress function
		$blocks = parse_blocks( $content );

		if ( empty( $blocks ) ) {
			return new \WP_Error(
				'no_blocks_parsed',
				__( 'No blocks could be parsed from the generated content.', 'layoutberg' )
			);
		}

		// Filter out empty blocks
		$blocks = array_filter( $blocks, function( $block ) {
			return ! empty( $block['blockName'] );
		} );

		// Serialize blocks for editor
		$serialized = serialize_blocks( $blocks );

		// Prepare response
		$response = array(
			'blocks'     => $blocks,
			'serialized' => $serialized,
			'html'       => do_blocks( $content ),
			'raw'        => $result['content'],
			'usage'      => $result['usage'],
			'model'      => $result['model'],
			'prompts'    => isset( $result['prompts'] ) ? $result['prompts'] : null,
			'metadata'   => array(
				'prompt'     => $prompt,
				'options'    => $options,
				'generated'  => current_time( 'mysql' ),
			),
		);

		return $response;
	}
}