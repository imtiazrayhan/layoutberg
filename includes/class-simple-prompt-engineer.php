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
		
		// Get user's style defaults (only for Professional/Agency plans)
		$user_options = get_option( 'layoutberg_options', array() );
		$style_defaults = '';
		
		if ( ! empty( $user_options['use_style_defaults'] ) && 
			( LayoutBerg_Licensing::is_professional_plan() || LayoutBerg_Licensing::is_agency_plan() ) ) {
			$defaults = array();
			
			// Typography defaults
			if ( ! empty( $user_options['default_heading_size'] ) && $user_options['default_heading_size'] !== 'default' ) {
				$size_map = array(
					'small' => 'Use small headings (1.5rem-2rem)',
					'medium' => 'Use medium headings (2rem-3rem)',
					'large' => 'Use large headings (3rem-4rem)',
					'x-large' => 'Use extra large headings (4rem-5rem)'
				);
				if ( isset( $size_map[ $user_options['default_heading_size'] ] ) ) {
					$defaults[] = $size_map[ $user_options['default_heading_size'] ];
				}
			}
			
			if ( ! empty( $user_options['default_text_size'] ) && $user_options['default_text_size'] !== 'default' ) {
				$text_size_map = array(
					'small' => 'Use small body text (14px)',
					'medium' => 'Use medium body text (16px)',
					'large' => 'Use large body text (18px)'
				);
				if ( isset( $text_size_map[ $user_options['default_text_size'] ] ) ) {
					$defaults[] = $text_size_map[ $user_options['default_text_size'] ];
				}
			}
			
			if ( ! empty( $user_options['default_text_align'] ) && $user_options['default_text_align'] !== 'default' ) {
				$defaults[] = 'Default text alignment: ' . $user_options['default_text_align'];
			}
			
			// Color defaults
			if ( ! empty( $user_options['default_text_color'] ) ) {
				$defaults[] = 'Default text color: ' . $user_options['default_text_color'];
			}
			
			if ( ! empty( $user_options['default_background_color'] ) ) {
				$defaults[] = 'Default background color: ' . $user_options['default_background_color'];
			}
			
			if ( ! empty( $user_options['default_button_color'] ) ) {
				$defaults[] = 'Default button background: ' . $user_options['default_button_color'];
			}
			
			if ( ! empty( $user_options['default_button_text_color'] ) ) {
				$defaults[] = 'Default button text color: ' . $user_options['default_button_text_color'];
			}
			
			// Layout defaults
			if ( ! empty( $user_options['default_content_width'] ) && $user_options['default_content_width'] !== 'default' ) {
				$defaults[] = 'Use ' . $user_options['default_content_width'] . ' width for content blocks';
			}
			
			if ( ! empty( $user_options['default_spacing'] ) && $user_options['default_spacing'] !== 'default' ) {
				$spacing_map = array(
					'compact' => 'Use compact spacing (20-40px between blocks)',
					'comfortable' => 'Use comfortable spacing (40-60px between blocks)',
					'spacious' => 'Use spacious spacing (60-80px between blocks)'
				);
				if ( isset( $spacing_map[ $user_options['default_spacing'] ] ) ) {
					$defaults[] = $spacing_map[ $user_options['default_spacing'] ];
				}
			}
			
			if ( ! empty( $defaults ) ) {
				$style_defaults = "\n\nUser style preferences:\n" . implode( "\n", $defaults ) . "\n";
			}
		}
		
		// Apply preferred style if set
		$style_instruction = '';
		if ( ! empty( $user_options['preferred_style'] ) && $user_options['preferred_style'] !== 'auto' ) {
			$style_map = array(
				'modern' => 'Use a modern, clean design with gradient backgrounds and sans-serif fonts',
				'classic' => 'Use a traditional, professional design with light backgrounds and serif headings',
				'bold' => 'Use a high-impact design with strong gradients and large fonts',
				'minimal' => 'Use an ultra-clean design with maximum whitespace',
				'creative' => 'Use an artistic design with colorful gradients and mixed fonts',
				'playful' => 'Use a friendly, fun design with bright colors and rounded corners'
			);
			if ( isset( $style_map[ $user_options['preferred_style'] ] ) ) {
				$style_instruction = "\n" . $style_map[ $user_options['preferred_style'] ] . "\n";
			}
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

			%s%s%s",
			esc_html( $theme_name ),
			esc_html( $color_info ),
			esc_html( $style_instruction ),
			esc_html( $style_defaults )
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
		// Apply agency user prompt template if provided
		if ( ! empty( $options['prompt_template_id'] ) && LayoutBerg_Licensing::is_agency_plan() ) {
			$template_prompt = $this->apply_user_prompt_template( $prompt, $options['prompt_template_id'] );
			if ( $template_prompt ) {
				return $template_prompt;
			}
		}
		
		// Just return the original prompt - no other enhancement needed
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
	
	/**
	 * Apply user-defined prompt template.
	 *
	 * @since 1.0.0
	 * @param string $user_prompt Original user prompt.
	 * @param string $template_id Template ID.
	 * @return string|false Modified prompt or false if template not found.
	 */
	private function apply_user_prompt_template( $user_prompt, $template_id ) {
		$user_id = get_current_user_id();
		$templates = get_user_meta( $user_id, 'layoutberg_prompt_templates', true );
		
		if ( ! is_array( $templates ) ) {
			return false;
		}
		
		// Find the template
		$template = null;
		foreach ( $templates as $t ) {
			if ( $t['id'] === $template_id ) {
				$template = $t;
				break;
			}
		}
		
		if ( ! $template ) {
			return false;
		}
		
		// Apply template
		$final_prompt = $template['prompt'];
		
		// Replace variables if any
		if ( ! empty( $template['variables'] ) && is_array( $template['variables'] ) ) {
			// For now, we'll use the user prompt as the main content variable
			$final_prompt = str_replace( '{content}', $user_prompt, $final_prompt );
			$final_prompt = str_replace( '{prompt}', $user_prompt, $final_prompt );
			
			// Replace other variables with their default values
			foreach ( $template['variables'] as $var_name => $var_value ) {
				$final_prompt = str_replace( '{' . $var_name . '}', $var_value, $final_prompt );
			}
		} else {
			// If no variables, append user prompt to template
			$final_prompt = $template['prompt'] . "\n\n" . $user_prompt;
		}
		
		return $final_prompt;
	}
}