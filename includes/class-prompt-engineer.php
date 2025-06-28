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

CRITICAL OUTPUT RULES:
1. Output ONLY valid Gutenberg block markup - no explanations, markdown backticks, or wrapper text
2. Start immediately with <!-- wp: and end with --> 
3. Do NOT include any introductory text like 'Here is the layout:' or code block markers
4. Do NOT include any concluding text or explanations after the blocks

BLOCK SYNTAX REQUIREMENTS:
1. Use ONLY core WordPress blocks unless specifically requested otherwise
2. Follow exact block comment syntax: <!-- wp:block-name {\"attribute\":\"value\"} -->
3. Ensure ALL blocks are properly nested and closed with <!-- /wp:block-name -->
4. Use double quotes for JSON attributes, not single quotes
5. Escape quotes properly in JSON: {\"text\":\"Welcome to our site\"}
6. Close all self-closing blocks properly

SEMANTIC STRUCTURE REQUIREMENTS:
1. Use proper heading hierarchy: h1 for main title, h2 for sections, h3 for subsections
2. Never skip heading levels (don't go from h1 to h3)
3. Use semantic HTML via appropriate blocks (group for sections, cover for heroes)
4. Wrap related content in group blocks with semantic class names
5. Use columns for layout, not for visual styling

RESPONSIVE & LAYOUT REQUIREMENTS:
1. Use mobile-first approach - layouts must work on small screens
2. Set explicit column widths that add up to 100%
3. Use stackable columns for mobile compatibility
4. Add appropriate spacer blocks (minimum 32px, maximum 80px)
5. Ensure content doesn't overflow on narrow screens

ACCESSIBILITY REQUIREMENTS:
1. Include descriptive alt text for ALL images
2. Use meaningful button text (never just 'Click Here')
3. Maintain proper heading hierarchy
4. Add appropriate ARIA labels where beneficial
5. Ensure color contrast considerations in text

CONTENT REQUIREMENTS:
1. Use contextually relevant placeholder text
2. Make headings descriptive of the section purpose
3. Use Lorem Ipsum for body paragraphs (2-3 sentences)
4. Add realistic company/service names in placeholders
5. Include specific, actionable button text
6. Use proper image alt text that describes the visual content

VISUAL DESIGN REQUIREMENTS:
1. For hero sections and cover blocks, use background colors instead of images:
   - Use gradient backgrounds: {\"gradient\":\"linear-gradient(135deg,#667eea 0%,#764ba2 100%)\"}
   - Or solid colors: {\"backgroundColor\":\"primary\"} or {\"customBackgroundColor\":\"#123456\"}
2. For image blocks, use colored placeholders with text overlays
3. Focus on color combinations and gradients for visual interest
4. NEVER use external image URLs - use background colors and gradients instead
5. Use these color palettes:
   - Modern: gradients with purple, blue, teal combinations
   - Corporate: solid blues, grays, whites
   - Creative: bold gradients with orange, pink, yellow
   - Minimal: grayscale with single accent color

COMMON MISTAKES TO AVOID:
1. Don't wrap everything in a single group block
2. Don't create overly nested structures
3. Don't use empty blocks or blocks without content
4. Don't mix up inner and outer HTML structures
5. Don't forget to close blocks properly
6. Don't use invalid attribute names or values
7. Don't create malformed JSON in block attributes";
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
		return "VALID BLOCK MARKUP EXAMPLES:

PROPER HERO SECTION:
<!-- wp:cover {\"url\":\"https://example.com/hero-bg.jpg\",\"dimRatio\":50,\"overlayColor\":\"primary\",\"align\":\"full\",\"className\":\"hero-section\"} -->
<div class=\"wp-block-cover alignfull hero-section\">
	<span aria-hidden=\"true\" class=\"wp-block-cover__background has-primary-background-color has-background-dim\"></span>
	<img class=\"wp-block-cover__image-background\" alt=\"\" src=\"https://example.com/hero-bg.jpg\" data-object-fit=\"cover\"/>
	<div class=\"wp-block-cover__inner-container\">
		<!-- wp:heading {\"level\":1,\"textAlign\":\"center\",\"className\":\"hero-title\"} -->
		<h1 class=\"has-text-align-center hero-title\">Transform Your Business Today</h1>
		<!-- /wp:heading -->
		
		<!-- wp:paragraph {\"align\":\"center\",\"fontSize\":\"large\"} -->
		<p class=\"has-text-align-center has-large-font-size\">Discover how our innovative solutions can help you achieve your goals faster than ever before.</p>
		<!-- /wp:paragraph -->
		
		<!-- wp:spacer {\"height\":\"20px\"} -->
		<div style=\"height:20px\" aria-hidden=\"true\" class=\"wp-block-spacer\"></div>
		<!-- /wp:spacer -->
		
		<!-- wp:buttons {\"layout\":{\"type\":\"flex\",\"justifyContent\":\"center\"}} -->
		<div class=\"wp-block-buttons\">
			<!-- wp:button {\"backgroundColor\":\"vivid-cyan-blue\",\"textColor\":\"white\"} -->
			<div class=\"wp-block-button\"><a class=\"wp-block-button__link has-vivid-cyan-blue-background-color has-white-color has-text-color has-background\">Get Started Free</a></div>
			<!-- /wp:button -->
			
			<!-- wp:button {\"style\":{\"border\":{\"width\":\"2px\",\"color\":\"#ffffff\"}},\"textColor\":\"white\",\"className\":\"is-style-outline\"} -->
			<div class=\"wp-block-button is-style-outline\"><a class=\"wp-block-button__link has-white-color has-text-color has-border-color\" style=\"border-color:#ffffff;border-width:2px\">Learn More</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
</div>
<!-- /wp:cover -->

PROPER 3-COLUMN FEATURES SECTION:
<!-- wp:group {\"align\":\"wide\",\"className\":\"features-section\"} -->
<div class=\"wp-block-group alignwide features-section\">
	<!-- wp:heading {\"level\":2,\"textAlign\":\"center\"} -->
	<h2 class=\"has-text-align-center\">Why Choose Our Platform</h2>
	<!-- /wp:heading -->
	
	<!-- wp:paragraph {\"align\":\"center\"} -->
	<p class=\"has-text-align-center\">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore.</p>
	<!-- /wp:paragraph -->
	
	<!-- wp:spacer {\"height\":\"40px\"} -->
	<div style=\"height:40px\" aria-hidden=\"true\" class=\"wp-block-spacer\"></div>
	<!-- /wp:spacer -->
	
	<!-- wp:columns {\"className\":\"features-grid\"} -->
	<div class=\"wp-block-columns features-grid\">
		<!-- wp:column {\"width\":\"33.33%\"} -->
		<div class=\"wp-block-column\" style=\"flex-basis:33.33%\">
			<!-- wp:image {\"align\":\"center\",\"width\":64,\"height\":64,\"className\":\"feature-icon\"} -->
			<figure class=\"wp-block-image aligncenter is-resized feature-icon\">
				<img src=\"https://via.placeholder.com/64x64/007cba/ffffff?text=🚀\" alt=\"Speed and performance icon\" width=\"64\" height=\"64\"/>
			</figure>
			<!-- /wp:image -->
			
			<!-- wp:heading {\"level\":3,\"textAlign\":\"center\"} -->
			<h3 class=\"has-text-align-center\">Lightning Fast</h3>
			<!-- /wp:heading -->
			
			<!-- wp:paragraph {\"align\":\"center\"} -->
			<p class=\"has-text-align-center\">Experience blazing-fast performance with our optimized infrastructure and cutting-edge technology.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
		
		<!-- wp:column {\"width\":\"33.33%\"} -->
		<div class=\"wp-block-column\" style=\"flex-basis:33.33%\">
			<!-- wp:image {\"align\":\"center\",\"width\":64,\"height\":64,\"className\":\"feature-icon\"} -->
			<figure class=\"wp-block-image aligncenter is-resized feature-icon\">
				<img src=\"https://via.placeholder.com/64x64/007cba/ffffff?text=🔒\" alt=\"Security and privacy icon\" width=\"64\" height=\"64\"/>
			</figure>
			<!-- /wp:image -->
			
			<!-- wp:heading {\"level\":3,\"textAlign\":\"center\"} -->
			<h3 class=\"has-text-align-center\">Secure & Private</h3>
			<!-- /wp:heading -->
			
			<!-- wp:paragraph {\"align\":\"center\"} -->
			<p class=\"has-text-align-center\">Your data is protected with enterprise-grade security and privacy controls you can trust.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
		
		<!-- wp:column {\"width\":\"33.33%\"} -->
		<div class=\"wp-block-column\" style=\"flex-basis:33.33%\">
			<!-- wp:image {\"align\":\"center\",\"width\":64,\"height\":64,\"className\":\"feature-icon\"} -->
			<figure class=\"wp-block-image aligncenter is-resized feature-icon\">
				<img src=\"https://via.placeholder.com/64x64/007cba/ffffff?text=⚡\" alt=\"Easy integration icon\" width=\"64\" height=\"64\"/>
			</figure>
			<!-- /wp:image -->
			
			<!-- wp:heading {\"level\":3,\"textAlign\":\"center\"} -->
			<h3 class=\"has-text-align-center\">Easy Integration</h3>
			<!-- /wp:heading -->
			
			<!-- wp:paragraph {\"align\":\"center\"} -->
			<p class=\"has-text-align-center\">Seamlessly integrate with your existing tools and workflows in just a few clicks.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->

PROPER CALL-TO-ACTION SECTION:
<!-- wp:group {\"align\":\"full\",\"backgroundColor\":\"primary\",\"textColor\":\"white\",\"className\":\"cta-section\"} -->
<div class=\"wp-block-group alignfull cta-section has-white-color has-primary-background-color has-text-color has-background\">
	<!-- wp:spacer {\"height\":\"60px\"} -->
	<div style=\"height:60px\" aria-hidden=\"true\" class=\"wp-block-spacer\"></div>
	<!-- /wp:spacer -->
	
	<!-- wp:heading {\"level\":2,\"textAlign\":\"center\",\"textColor\":\"white\"} -->
	<h2 class=\"has-text-align-center has-white-color has-text-color\">Ready to Get Started?</h2>
	<!-- /wp:heading -->
	
	<!-- wp:paragraph {\"align\":\"center\",\"fontSize\":\"medium\"} -->
	<p class=\"has-text-align-center has-medium-font-size\">Join thousands of satisfied customers who have transformed their business with our platform.</p>
	<!-- /wp:paragraph -->
	
	<!-- wp:spacer {\"height\":\"30px\"} -->
	<div style=\"height:30px\" aria-hidden=\"true\" class=\"wp-block-spacer\"></div>
	<!-- /wp:spacer -->
	
	<!-- wp:buttons {\"layout\":{\"type\":\"flex\",\"justifyContent\":\"center\"}} -->
	<div class=\"wp-block-buttons\">
		<!-- wp:button {\"backgroundColor\":\"white\",\"textColor\":\"primary\",\"fontSize\":\"medium\"} -->
		<div class=\"wp-block-button has-custom-font-size has-medium-font-size\"><a class=\"wp-block-button__link has-primary-color has-white-background-color has-text-color has-background\">Start Your Free Trial</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
	
	<!-- wp:spacer {\"height\":\"60px\"} -->
	<div style=\"height:60px\" aria-hidden=\"true\" class=\"wp-block-spacer\"></div>
	<!-- /wp:spacer -->
</div>
<!-- /wp:group -->";
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
			return new \WP_Error( 'prompt_too_short', __( 'Please provide a more detailed description of the layout you want. Try including what type of content, style, or purpose you have in mind.', 'layoutberg' ) );
		}
		
		if ( strlen( $prompt ) > 2000 ) {
			return new \WP_Error( 'prompt_too_long', __( 'Please keep your description under 2000 characters for better processing.', 'layoutberg' ) );
		}
		
		// Check for inappropriate content.
		$inappropriate_terms = apply_filters( 'layoutberg_inappropriate_terms', array() );
		foreach ( $inappropriate_terms as $term ) {
			if ( stripos( $prompt, $term ) !== false ) {
				return new \WP_Error( 'inappropriate_content', __( 'Your prompt contains content that cannot be processed. Please revise your request.', 'layoutberg' ) );
			}
		}
		
		// Check for overly technical requests that might confuse the AI.
		$technical_patterns = array(
			'/\bHTML\b/i',
			'/\bCSS\b/i',
			'/\bJavaScript\b/i',
			'/\bPHP\b/i',
			'/\bSQL\b/i',
			'/\bcode\b/i',
			'/\bdatabase\b/i',
			'/\bAPI\b/i',
		);
		
		$technical_count = 0;
		foreach ( $technical_patterns as $pattern ) {
			if ( preg_match( $pattern, $prompt ) ) {
				$technical_count++;
			}
		}
		
		if ( $technical_count >= 3 ) {
			return new \WP_Error( 
				'too_technical', 
				__( 'Your request seems very technical. Try describing the visual layout and content you want instead of implementation details.', 'layoutberg' ) 
			);
		}
		
		// Check for common problematic requests.
		$problematic_patterns = array(
			'/\bembed\s+script/i' => __( 'Cannot embed custom scripts. Try describing the visual content instead.', 'layoutberg' ),
			'/\bcustom\s+plugin/i' => __( 'Cannot create custom plugins. Focus on layout and content structure.', 'layoutberg' ),
			'/\bform\s+submission/i' => __( 'Cannot handle form submissions. Try describing the form layout instead.', 'layoutberg' ),
			'/\bdynamic\s+content/i' => __( 'Cannot create dynamic content. Focus on static layout structure.', 'layoutberg' ),
		);
		
		foreach ( $problematic_patterns as $pattern => $message ) {
			if ( preg_match( $pattern, $prompt ) ) {
				return new \WP_Error( 'problematic_request', $message );
			}
		}
		
		// Check for common misspellings that might affect AI understanding.
		$common_corrections = array(
			'hero sectin' => 'hero section',
			'testimoinals' => 'testimonials',
			'collumns' => 'columns',
			'butttons' => 'buttons',
			'priceing' => 'pricing',
		);
		
		foreach ( $common_corrections as $wrong => $right ) {
			if ( stripos( $prompt, $wrong ) !== false ) {
				// Just log this for now, don't block the request
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "LayoutBerg: Possible typo detected in prompt: '$wrong' might be '$right'" );
				}
			}
		}
		
		return true;
	}

	/**
	 * Get prompt improvement suggestions.
	 *
	 * @since 1.0.0
	 * @param string $prompt User prompt.
	 * @return array Suggestions for improvement.
	 */
	public function get_prompt_suggestions( $prompt ) {
		$suggestions = array();
		$prompt_lower = strtolower( $prompt );
		
		// Check if prompt mentions specific layout types.
		$layout_keywords = array(
			'hero' => 'hero section',
			'landing' => 'landing page',
			'about' => 'about page',
			'contact' => 'contact page',
			'features' => 'features section',
			'testimonials' => 'testimonials section',
			'pricing' => 'pricing table',
		);
		
		$mentioned_layouts = array();
		foreach ( $layout_keywords as $keyword => $full_name ) {
			if ( strpos( $prompt_lower, $keyword ) !== false ) {
				$mentioned_layouts[] = $full_name;
			}
		}
		
		// Suggest being more specific about layout type.
		if ( empty( $mentioned_layouts ) && strlen( $prompt ) < 50 ) {
			$suggestions[] = array(
				'type' => 'be_specific',
				'message' => __( 'Try being more specific about the type of layout you want (e.g., "hero section", "pricing table", "contact page").', 'layoutberg' ),
			);
		}
		
		// Check for style preferences.
		$style_keywords = array( 'modern', 'minimal', 'corporate', 'creative', 'professional', 'elegant', 'bold' );
		$has_style = false;
		foreach ( $style_keywords as $style ) {
			if ( strpos( $prompt_lower, $style ) !== false ) {
				$has_style = true;
				break;
			}
		}
		
		if ( ! $has_style && strlen( $prompt ) > 30 ) {
			$suggestions[] = array(
				'type' => 'add_style',
				'message' => __( 'Consider mentioning a design style preference like "modern", "minimal", or "professional".', 'layoutberg' ),
			);
		}
		
		// Check for color preferences.
		$color_keywords = array( 'blue', 'green', 'red', 'purple', 'orange', 'pink', 'dark', 'light', 'bright', 'muted' );
		$has_colors = false;
		foreach ( $color_keywords as $color ) {
			if ( strpos( $prompt_lower, $color ) !== false ) {
				$has_colors = true;
				break;
			}
		}
		
		if ( ! $has_colors && strlen( $prompt ) > 40 ) {
			$suggestions[] = array(
				'type' => 'add_colors',
				'message' => __( 'You could mention color preferences or themes to get more targeted results.', 'layoutberg' ),
			);
		}
		
		// Check for purpose/audience.
		$purpose_keywords = array( 'business', 'personal', 'portfolio', 'blog', 'ecommerce', 'nonprofit', 'startup' );
		$has_purpose = false;
		foreach ( $purpose_keywords as $purpose ) {
			if ( strpos( $prompt_lower, $purpose ) !== false ) {
				$has_purpose = true;
				break;
			}
		}
		
		if ( ! $has_purpose && strlen( $prompt ) > 50 ) {
			$suggestions[] = array(
				'type' => 'add_purpose',
				'message' => __( 'Mentioning the purpose or target audience can help create more relevant content.', 'layoutberg' ),
			);
		}
		
		// Suggest example prompts for very short inputs.
		if ( strlen( $prompt ) < 20 ) {
			$suggestions[] = array(
				'type' => 'example_prompts',
				'message' => __( 'Try a more detailed prompt like: "Create a modern hero section for a tech startup with a compelling headline and two call-to-action buttons"', 'layoutberg' ),
			);
		}
		
		return $suggestions;
	}

	/**
	 * Get contextual prompt templates based on user input.
	 *
	 * @since 1.0.0
	 * @param string $prompt User's partial prompt.
	 * @return array Relevant template suggestions.
	 */
	public function get_contextual_templates( $prompt ) {
		$prompt_lower = strtolower( $prompt );
		$templates = $this->get_prompt_templates();
		$suggestions = array();
		
		// Match templates based on keywords in the prompt.
		$template_keywords = array(
			'hero_section' => array( 'hero', 'banner', 'header', 'main', 'top' ),
			'features_grid' => array( 'features', 'benefits', 'services', 'advantages', 'grid' ),
			'testimonials' => array( 'testimonials', 'reviews', 'feedback', 'customers', 'quotes' ),
			'pricing_table' => array( 'pricing', 'plans', 'packages', 'cost', 'price' ),
			'team_section' => array( 'team', 'staff', 'members', 'people', 'about us' ),
			'faq_section' => array( 'faq', 'questions', 'help', 'support', 'answers' ),
			'cta_section' => array( 'call to action', 'cta', 'signup', 'register', 'contact' ),
			'stats_section' => array( 'statistics', 'stats', 'numbers', 'metrics', 'data' ),
		);
		
		foreach ( $template_keywords as $template_key => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( strpos( $prompt_lower, $keyword ) !== false ) {
					if ( isset( $templates[ $template_key ] ) ) {
						$suggestions[] = $templates[ $template_key ];
					}
					break;
				}
			}
		}
		
		// If no specific matches, suggest popular templates.
		if ( empty( $suggestions ) ) {
			$popular_templates = array( 'hero_section', 'features_grid', 'cta_section' );
			foreach ( $popular_templates as $template_key ) {
				if ( isset( $templates[ $template_key ] ) ) {
					$suggestions[] = $templates[ $template_key ];
				}
			}
		}
		
		return array_slice( $suggestions, 0, 3 ); // Return max 3 suggestions.
	}
}