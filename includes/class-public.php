<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    LayoutBerg
 * @subpackage Public
 * @since      1.0.0
 */

namespace DotCamp\LayoutBerg;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The public-facing functionality of the plugin.
 *
 * @since 1.0.0
 */
class PublicFacing {

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $version The version of this plugin.
	 */
	public function __construct( $version ) {
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		// Only enqueue if we have LayoutBerg blocks on the page.
		if ( ! $this->has_layoutberg_blocks() ) {
			return;
		}

		wp_enqueue_style(
			'layoutberg-public',
			LAYOUTBERG_PLUGIN_URL . 'public/css/layoutberg-public.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		// Only enqueue if we have LayoutBerg blocks on the page.
		if ( ! $this->has_layoutberg_blocks() ) {
			return;
		}

		wp_enqueue_script(
			'layoutberg-public',
			LAYOUTBERG_PLUGIN_URL . 'public/js/layoutberg-public.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Localize script if needed.
		wp_localize_script(
			'layoutberg-public',
			'layoutbergPublic',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'layoutberg_public_nonce' ),
			)
		);
	}

	/**
	 * Check if current page has LayoutBerg blocks.
	 *
	 * @since 1.0.0
	 * @return bool True if page has LayoutBerg blocks.
	 */
	private function has_layoutberg_blocks() {
		// Check if we're on a singular page.
		if ( ! is_singular() ) {
			return false;
		}

		// Get post content.
		$post = get_post();
		if ( ! $post || empty( $post->post_content ) ) {
			return false;
		}

		// Check for LayoutBerg blocks.
		return has_block( 'layoutberg/ai-layout', $post );
	}
}