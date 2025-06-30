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

		return $response;
	}
}