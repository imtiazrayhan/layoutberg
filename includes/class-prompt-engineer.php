<?php
/**
 * Prompt engineering class for advanced layout generation.
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
 * Prompt engineer class.
 *
 * @since 1.0.0
 */
class Prompt_Engineer {

	/**
	 * Layout type templates.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $layout_templates = array();

	/**
	 * Block examples.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $block_examples = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init_layout_templates();
		$this->init_block_examples();
	}

	/**
	 * Build enhanced system prompt for layout generation.
	 *
	 * @since 1.0.0
	 * @param array $options Generation options.
	 * @return string System prompt.
	 */
	public function build_system_prompt( $options = array() ) {
		$prompt = $this->get_base_system_prompt();
		
		// Add specific layout type instructions.
		if ( isset( $options['layout_type'] ) && isset( $this->layout_templates[ $options['layout_type'] ] ) ) {
			$prompt .= "\n\n" . $this->layout_templates[ $options['layout_type'] ]['instructions'];
		}
		
		// Add style-specific instructions.
		if ( isset( $options['style'] ) ) {
			$prompt .= "\n\n" . $this->get_style_instructions( $options['style'] );
		}
		
		// Add responsive design instructions.
		$prompt .= "\n\n" . $this->get_responsive_instructions();
		
		// Add accessibility instructions.
		$prompt .= "\n\n" . $this->get_accessibility_instructions();
		
		// Add example blocks.
		$prompt .= "\n\n" . $this->get_example_blocks();
		
		return $prompt;
	}

	/**
	 * Get base system prompt.
	 *
	 * @since 1.0.0
	 * @return string Base system prompt.
	 */
	private function get_base_system_prompt() {
		return "You are an expert WordPress Gutenberg layout designer. Your task is to generate valid, semantic, and accessible Gutenberg block markup based on user requirements.

CRITICAL RULES:
1. Output ONLY valid Gutenberg block markup - no explanations, markdown, or wrapper text
2. Use ONLY core WordPress blocks unless specifically requested otherwise
3. Follow exact block comment syntax: <!-- wp:block-name {\"attribute\":\"value\"} -->
4. Ensure ALL blocks are properly nested and closed with <!-- /wp:block-name -->
5. Use semantic HTML structure with proper heading hierarchy (h1 > h2 > h3)
6. Include placeholder content that matches the context
7. Add appropriate CSS classes for styling hooks
8. Ensure mobile-first responsive design
9. Follow WCAG 2.1 AA accessibility standards

BLOCK STRUCTURE REQUIREMENTS:
- Wrap all content in appropriate container blocks (group, columns, cover)
- Use columns block for multi-column layouts with proper width distributions
- Nest blocks logically (buttons inside buttons block, list-items inside list)
- Add spacer blocks for vertical spacing control
- Use separator blocks for visual content separation

CONTENT GUIDELINES:
- Use relevant placeholder text that matches the layout purpose
- Include Lorem Ipsum for body text paragraphs
- Add descriptive image placeholders with alt text
- Use realistic button text (e.g., 'Get Started', 'Learn More', 'Contact Us')
- Include proper heading text that describes section purpose";
	}

	/**
	 * Get style-specific instructions.
	 *
	 * @since 1.0.0
	 * @param string $style Style preference.
	 * @return string Style instructions.
	 */
	private function get_style_instructions( $style ) {
		$style_guides = array(
			'modern' => "Apply modern design principles:
- Use generous whitespace with spacer blocks
- Implement card-based layouts with group blocks and shadows
- Add gradient backgrounds to cover blocks
- Use large, bold typography for headings
- Include rounded corners via additional CSS classes
- Implement asymmetric column layouts",
			
			'minimal' => "Apply minimalist design principles:
- Use maximum whitespace between sections
- Stick to monochromatic color schemes
- Implement simple, clean typography
- Avoid decorative elements
- Use thin separators or none at all
- Focus on content hierarchy",
			
			'corporate' => "Apply corporate design principles:
- Use structured, grid-based layouts
- Implement professional color schemes (blues, grays)
- Add testimonial and team sections
- Include call-to-action blocks prominently
- Use formal, professional placeholder text
- Implement clear navigation structure",
			
			'creative' => "Apply creative design principles:
- Use asymmetric and overlapping layouts
- Implement bold color combinations
- Add creative use of media blocks
- Include unique spacing patterns
- Use expressive typography combinations
- Implement non-traditional column arrangements",
		);
		
		return isset( $style_guides[ $style ] ) ? $style_guides[ $style ] : $style_guides['modern'];
	}

	/**
	 * Get responsive design instructions.
	 *
	 * @since 1.0.0
	 * @return string Responsive instructions.
	 */
	private function get_responsive_instructions() {
		return "RESPONSIVE DESIGN REQUIREMENTS:
- Use stackable columns that work on mobile (set appropriate column blocks)
- Ensure text remains readable on all devices
- Use relative units for spacing where possible
- Consider mobile-first approach in layout structure
- Add appropriate classes for responsive behavior
- Test visual hierarchy on smaller screens";
	}

	/**
	 * Get accessibility instructions.
	 *
	 * @since 1.0.0
	 * @return string Accessibility instructions.
	 */
	private function get_accessibility_instructions() {
		return "ACCESSIBILITY REQUIREMENTS:
- Use semantic HTML elements via appropriate blocks
- Maintain proper heading hierarchy (never skip levels)
- Include descriptive alt text for all images
- Ensure sufficient color contrast in design choices
- Add ARIA labels where beneficial
- Make interactive elements keyboard accessible
- Use clear, descriptive link text
- Avoid using color alone to convey information";
	}

	/**
	 * Get example blocks.
	 *
	 * @since 1.0.0
	 * @return string Example blocks.
	 */
	private function get_example_blocks() {
		return "BLOCK SYNTAX EXAMPLES:

Hero Section:
<!-- wp:cover {\"dimRatio\":50,\"overlayColor\":\"primary\",\"align\":\"full\"} -->
<div class=\"wp-block-cover alignfull\">
	<div class=\"wp-block-cover__inner-container\">
		<!-- wp:heading {\"level\":1,\"textAlign\":\"center\"} -->
		<h1 class=\"has-text-align-center\">Welcome to Our Site</h1>
		<!-- /wp:heading -->
		
		<!-- wp:paragraph {\"align\":\"center\"} -->
		<p class=\"has-text-align-center\">Your compelling subheadline here</p>
		<!-- /wp:paragraph -->
		
		<!-- wp:buttons {\"layout\":{\"type\":\"flex\",\"justifyContent\":\"center\"}} -->
		<div class=\"wp-block-buttons\">
			<!-- wp:button -->
			<div class=\"wp-block-button\"><a class=\"wp-block-button__link\">Get Started</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
</div>
<!-- /wp:cover -->

Columns Layout:
<!-- wp:columns {\"align\":\"wide\"} -->
<div class=\"wp-block-columns alignwide\">
	<!-- wp:column {\"width\":\"33.33%\"} -->
	<div class=\"wp-block-column\" style=\"flex-basis:33.33%\">
		<!-- wp:heading {\"level\":3} -->
		<h3>Feature One</h3>
		<!-- /wp:heading -->
		
		<!-- wp:paragraph -->
		<p>Description of the feature goes here.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->";
	}

	/**
	 * Initialize layout templates.
	 *
	 * @since 1.0.0
	 */
	private function init_layout_templates() {
		$this->layout_templates = array(
			'landing_page' => array(
				'name' => __( 'Landing Page', 'layoutberg' ),
				'instructions' => 'Create a complete landing page with: hero section, features grid, about section, testimonials, and call-to-action. Structure the page to guide visitors toward conversion.',
			),
			'blog_post' => array(
				'name' => __( 'Blog Post', 'layoutberg' ),
				'instructions' => 'Create a blog post layout with: featured image, post title, meta information, content sections with proper headings, pull quotes, and author bio section.',
			),
			'portfolio' => array(
				'name' => __( 'Portfolio', 'layoutberg' ),
				'instructions' => 'Create a portfolio showcase with: introduction section, project grid with images and descriptions, filtering options placeholder, and contact call-to-action.',
			),
			'about_page' => array(
				'name' => __( 'About Page', 'layoutberg' ),
				'instructions' => 'Create an about page with: company/personal story section, mission and values, team members grid, achievements/statistics, and company culture showcase.',
			),
			'contact_page' => array(
				'name' => __( 'Contact Page', 'layoutberg' ),
				'instructions' => 'Create a contact page with: contact form placeholder, contact information blocks, map placeholder, office hours, and FAQ section.',
			),
			'services' => array(
				'name' => __( 'Services Page', 'layoutberg' ),
				'instructions' => 'Create a services page with: services overview, detailed service cards with icons, pricing table, process explanation, and consultation call-to-action.',
			),
			'product_showcase' => array(
				'name' => __( 'Product Showcase', 'layoutberg' ),
				'instructions' => 'Create a product showcase with: product hero image, features list, benefits section, technical specifications, customer reviews, and purchase call-to-action.',
			),
		);
	}

	/**
	 * Initialize block examples.
	 *
	 * @since 1.0.0
	 */
	private function init_block_examples() {
		$this->block_examples = array(
			'heading' => '<!-- wp:heading {\"level\":2,\"textAlign\":\"center\"} -->
<h2 class="has-text-align-center">Section Title</h2>
<!-- /wp:heading -->',
			
			'paragraph' => '<!-- wp:paragraph -->
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
<!-- /wp:paragraph -->',
			
			'button' => '<!-- wp:buttons {\"layout\":{\"type\":\"flex\",\"justifyContent\":\"center\"}} -->
<div class="wp-block-buttons">
	<!-- wp:button -->
	<div class="wp-block-button"><a class="wp-block-button__link">Click Here</a></div>
	<!-- /wp:button -->
</div>
<!-- /wp:buttons -->',
			
			'image' => '<!-- wp:image {\"align\":\"center\",\"sizeSlug\":\"large\"} -->
<figure class="wp-block-image aligncenter size-large">
	<img src="placeholder.jpg" alt="Descriptive alt text"/>
</figure>
<!-- /wp:image -->',
			
			'spacer' => '<!-- wp:spacer {\"height\":\"50px\"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->',
		);
	}

	/**
	 * Enhance user prompt with context.
	 *
	 * @since 1.0.0
	 * @param string $prompt Original user prompt.
	 * @param array  $options Enhancement options.
	 * @return string Enhanced prompt.
	 */
	public function enhance_user_prompt( $prompt, $options = array() ) {
		$enhanced = $prompt;
		
		// Add layout type context.
		if ( isset( $options['layout_type'] ) && isset( $this->layout_templates[ $options['layout_type'] ] ) ) {
			$enhanced = $this->layout_templates[ $options['layout_type'] ]['instructions'] . "\n\nAdditional requirements: " . $prompt;
		}
		
		// Add specific requirements.
		$requirements = array();
		
		if ( isset( $options['columns'] ) ) {
			$requirements[] = "Use a " . $options['columns'] . "-column layout where appropriate";
		}
		
		if ( isset( $options['color_scheme'] ) ) {
			$requirements[] = "Apply a " . $options['color_scheme'] . " color scheme";
		}
		
		if ( isset( $options['include_cta'] ) && $options['include_cta'] ) {
			$requirements[] = "Include prominent call-to-action sections";
		}
		
		if ( isset( $options['include_testimonials'] ) && $options['include_testimonials'] ) {
			$requirements[] = "Add a testimonials section";
		}
		
		if ( ! empty( $requirements ) ) {
			$enhanced .= "\n\nSpecific requirements:\n" . implode( "\n", array_map( function( $req ) {
				return "- " . $req;
			}, $requirements ) );
		}
		
		return $enhanced;
	}

	/**
	 * Get prompt templates for common layouts.
	 *
	 * @since 1.0.0
	 * @return array Prompt templates.
	 */
	public function get_prompt_templates() {
		return array(
			'hero_section' => array(
				'name' => __( 'Hero Section', 'layoutberg' ),
				'prompt' => 'Create a hero section with a compelling headline, subheadline, and two call-to-action buttons. Use a cover block with background image placeholder.',
			),
			'features_grid' => array(
				'name' => __( 'Features Grid', 'layoutberg' ),
				'prompt' => 'Create a 3-column features section with icon placeholders, feature titles, and descriptions. Include proper spacing and alignment.',
			),
			'testimonials' => array(
				'name' => __( 'Testimonials', 'layoutberg' ),
				'prompt' => 'Create a testimonials section with 3 customer reviews including quote text, customer names, and company/role information.',
			),
			'pricing_table' => array(
				'name' => __( 'Pricing Table', 'layoutberg' ),
				'prompt' => 'Create a 3-column pricing table with plan names, prices, features lists, and call-to-action buttons. Highlight the recommended plan.',
			),
			'team_section' => array(
				'name' => __( 'Team Section', 'layoutberg' ),
				'prompt' => 'Create a team members section with 4 people including placeholder images, names, roles, and brief bios in a grid layout.',
			),
			'faq_section' => array(
				'name' => __( 'FAQ Section', 'layoutberg' ),
				'prompt' => 'Create an FAQ section with 5-6 frequently asked questions and detailed answers. Use proper heading hierarchy.',
			),
			'cta_section' => array(
				'name' => __( 'Call to Action', 'layoutberg' ),
				'prompt' => 'Create a compelling call-to-action section with headline, supporting text, and prominent button. Use contrasting background.',
			),
			'stats_section' => array(
				'name' => __( 'Statistics', 'layoutberg' ),
				'prompt' => 'Create a statistics section showing 4 key metrics with large numbers, labels, and descriptions in a column layout.',
			),
		);
	}

	/**
	 * Validate prompt for potential issues.
	 *
	 * @since 1.0.0
	 * @param string $prompt User prompt.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_prompt( $prompt ) {
		// Check prompt length.
		if ( strlen( $prompt ) < 10 ) {
			return new \WP_Error( 'prompt_too_short', __( 'Please provide a more detailed description of the layout you want.', 'layoutberg' ) );
		}
		
		if ( strlen( $prompt ) > 1000 ) {
			return new \WP_Error( 'prompt_too_long', __( 'Please keep your description under 1000 characters.', 'layoutberg' ) );
		}
		
		// Check for inappropriate content.
		$inappropriate_terms = apply_filters( 'layoutberg_inappropriate_terms', array() );
		foreach ( $inappropriate_terms as $term ) {
			if ( stripos( $prompt, $term ) !== false ) {
				return new \WP_Error( 'inappropriate_content', __( 'Your prompt contains inappropriate content.', 'layoutberg' ) );
			}
		}
		
		return true;
	}
}