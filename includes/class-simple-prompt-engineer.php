<?php
/**
 * Simplified prompt engineering for reliable block generation.
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
 * Simple prompt engineer class.
 *
 * @since 1.0.0
 */
class Simple_Prompt_Engineer {

	/**
	 * Build simple, effective system prompt.
	 *
	 * @since 1.0.0
	 * @param array $options Generation options.
	 * @return string System prompt.
	 */
	public function build_system_prompt( $options = array() ) {
		$theme_name = wp_get_theme()->get( 'Name' );
		$theme_colors = $this->get_theme_colors();
		
		$color_info = '';
		if ( ! empty( $theme_colors ) ) {
			$color_info = "The theme uses these colors:\n";
			foreach ( $theme_colors as $slug => $hex ) {
				$color_info .= "- $slug: $hex\n";
			}
			$color_info .= "Use these colors when appropriate.\n";
		}
		
		// Simple, direct instructions like the working plugin
		$prompt = sprintf(
			"You are an AI that generates valid WordPress block patterns. 
			ONLY return the block pattern using proper WordPress block markup. 
			DO NOT use generic HTML elements like <div> or <section>. 
			Always wrap elements in valid Gutenberg blocks (e.g., <!-- wp:paragraph -->, <!-- wp:group -->).

			NO explanations, NO additional text, NO Markdown formatting like triple backticks.

			The block pattern should be designed for the \"%s\" theme and should include proper spacing, padding and margins. Please make sure all inner blocks use content width.

			For cover blocks with images: Use ONLY the url attribute without id attribute. Do not add wp-image-XXX classes. Use gradient backgrounds instead of images when possible.

			%s",
			esc_html( $theme_name ),
			esc_html( $color_info )
		);
		
		return $prompt;
	}
	
	/**
	 * Get theme colors from theme.json.
	 *
	 * @since 1.0.0
	 * @return array Theme colors.
	 */
	private function get_theme_colors() {
		$theme_json = wp_get_global_settings( array( 'color', 'palette' ) );
		
		if ( empty( $theme_json ) || ! isset( $theme_json['theme'] ) ) {
			return array();
		}
		
		$colors = array();
		foreach ( $theme_json['theme'] as $color ) {
			if ( isset( $color['slug'], $color['color'] ) ) {
				$colors[ $color['slug'] ] = $color['color'];
			}
		}
		
		return $colors;
	}
	
	/**
	 * Enhance user prompt minimally.
	 *
	 * @since 1.0.0
	 * @param string $prompt User prompt.
	 * @param array  $options Options.
	 * @return string Enhanced prompt.
	 */
	public function enhance_user_prompt( $prompt, $options = array() ) {
		// Just return the original prompt - no enhancement needed
		return $prompt;
	}
	
	/**
	 * Validate prompt basic requirements.
	 *
	 * @since 1.0.0
	 * @param string $prompt User prompt.
	 * @return true|WP_Error True if valid.
	 */
	public function validate_prompt( $prompt ) {
		if ( strlen( $prompt ) < 10 ) {
			return new \WP_Error( 
				'prompt_too_short', 
				__( 'Please provide more details about what you want to create.', 'layoutberg' )
			);
		}
		
		if ( strlen( $prompt ) > 1000 ) {
			return new \WP_Error( 
				'prompt_too_long', 
				__( 'Please keep your description under 1000 characters.', 'layoutberg' )
			);
		}
		
		return true;
	}
}