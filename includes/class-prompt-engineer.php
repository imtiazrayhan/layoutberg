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
	 * Pattern library.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $pattern_library = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init_layout_templates();
		$this->init_block_examples();
		$this->init_pattern_library();
	}

	/**
	 * Estimate token count for a string.
	 *
	 * @since 1.0.0
	 * @param string $text Text to count tokens for.
	 * @return int Estimated token count.
	 */
	public function estimate_token_count( $text ) {
		// Improved estimation: ~3.5 characters = 1 token for English text
		// Account for JSON formatting and special characters
		$json_overhead = substr_count( $text, '{' ) + substr_count( $text, '}' ) + substr_count( $text, '"' );
		$base_tokens = intval( strlen( $text ) / 3.5 );
		$overhead_tokens = intval( $json_overhead / 10 );
		
		return $base_tokens + $overhead_tokens;
	}

	/**
	 * Get model token limits.
	 *
	 * @since 1.0.0
	 * @param string $model Model name.
	 * @return array Token limits.
	 */
	public function get_model_limits( $model ) {
		$limits = array(
			'gpt-3.5-turbo' => array(
				'total' => 4096,
				'max_completion' => 4096,
				'optimal_prompt' => 2000, // Leave room for completion
			),
			'gpt-4' => array(
				'total' => 8192,
				'max_completion' => 4096,
				'optimal_prompt' => 4000,
			),
			'gpt-4-turbo' => array(
				'total' => 128000,
				'max_completion' => 4096,
				'optimal_prompt' => 8000,
			),
			'claude-3-opus' => array(
				'total' => 200000,
				'max_completion' => 4096,
				'optimal_prompt' => 10000,
			),
			'claude-3-sonnet' => array(
				'total' => 200000,
				'max_completion' => 4096,
				'optimal_prompt' => 10000,
			),
		);
		
		return isset( $limits[ $model ] ) ? $limits[ $model ] : $limits['gpt-3.5-turbo'];
	}

	/**
	 * Build enhanced system prompt for layout generation.
	 *
	 * @since 1.0.0
	 * @param array $options Generation options.
	 * @return string System prompt.
	 */
	public function build_system_prompt( $options = array() ) {
		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'LayoutBerg Prompt Engineer - Options received: ' . print_r( $options, true ) );
		}
		
		// Get model and optimize prompt
		$model = isset( $options['model'] ) ? $options['model'] : 'gpt-3.5-turbo';
		
		// Start with base prompt
		$prompt = $this->get_enhanced_system_prompt();
		
		// Add style-specific instructions if provided
		if ( isset( $options['style'] ) && $options['style'] ) {
			$prompt .= "\n\n" . $this->get_style_instructions( $options['style'] );
		}
		
		// Add layout-specific instructions if provided
		if ( isset( $options['layout'] ) && $options['layout'] ) {
			$prompt .= "\n\n" . $this->get_layout_instructions( $options['layout'] );
		}
		
		// Add color scheme instructions if provided
		if ( isset( $options['color_scheme'] ) && $options['color_scheme'] ) {
			$prompt .= "\n\n" . $this->get_color_scheme_instructions( $options['color_scheme'] );
		}
		
		// Add density instructions if provided
		if ( isset( $options['density'] ) && $options['density'] ) {
			$prompt .= "\n\n" . $this->get_density_instructions( $options['density'] );
		}
		
		// Add audience instructions if provided
		if ( isset( $options['audience'] ) && $options['audience'] ) {
			$prompt .= "\n\n" . $this->get_audience_instructions( $options['audience'] );
		}
		
		// Add industry instructions if provided
		if ( isset( $options['industry'] ) && $options['industry'] ) {
			$prompt .= "\n\n" . $this->get_industry_instructions( $options['industry'] );
		}
		
		// Add language instructions if provided
		if ( isset( $options['language'] ) && $options['language'] && $options['language'] !== 'en' ) {
			$prompt .= "\n\n" . $this->get_language_instructions( $options['language'] );
		}
		
		// Add responsive and accessibility instructions
		$prompt .= "\n\n" . $this->get_responsive_instructions();
		$prompt .= "\n\n" . $this->get_accessibility_instructions();
		
		// Add learning instructions
		$prompt .= "\n\n" . $this->add_learning_instructions();
		
		// Add enhanced block examples
		$prompt .= "\n\n" . $this->get_enhanced_block_examples();
		
		// Optimize prompt for model
		$prompt = $this->optimize_prompt_for_model( $prompt, $model );
		
		return $prompt;
	}

	/**
	 * Get enhanced system prompt with better structure.
	 *
	 * @since 1.0.0
	 * @return string Enhanced system prompt.
	 */
	private function get_enhanced_system_prompt() {
		return "You are an expert WordPress Gutenberg layout designer. Generate ONLY valid Gutenberg block markup.

CRITICAL REQUIREMENTS:
1. Output must start with <!-- wp: and end with -->
2. NO explanatory text, comments, or markdown formatting
3. Use proper JSON in block attributes: {\"attribute\":\"value\"}
4. Close all blocks: <!-- /wp:block-name -->
5. Escape quotes in content: use &quot; for quotes within HTML attributes

BLOCK STRUCTURE RULES:
- Always wrap major sections in group blocks with appropriate spacing
- Use columns block for multi-column layouts with responsive settings
- Apply consistent spacing using spacer blocks or padding/margin styles
- Maintain semantic HTML hierarchy (h1→h2→h3)
- Nest blocks logically: group > columns > column > content

STYLING BEST PRACTICES:
- Use style object for custom styling: {\"style\":{\"color\":{},\"spacing\":{},\"border\":{},\"typography\":{}}}
- Apply responsive widths: percentages for columns, max-widths for containers
- Use consistent spacing units (8px grid: 8px, 16px, 24px, 32px, 48px, 64px, 80px)
- Include hover states for interactive elements
- Maintain visual rhythm with consistent spacing

COLOR APPLICATION:
- Named colors: {\"backgroundColor\":\"primary\"} or {\"textColor\":\"primary\"}
- Custom colors: {\"style\":{\"color\":{\"background\":\"#hex\",\"text\":\"#hex\"}}}
- Gradients: {\"gradient\":\"vivid-cyan-blue-to-vivid-purple\"} or custom in style
- Overlays: Use background dim for cover blocks

TYPOGRAPHY RULES:
- Font sizes: {\"fontSize\":\"small|medium|large|x-large|xx-large\"} or custom in style
- Line height: {\"style\":{\"typography\":{\"lineHeight\":\"1.6\"}}}
- Font weight: {\"style\":{\"typography\":{\"fontWeight\":\"300|400|500|600|700\"}}}
- Text transform: {\"style\":{\"typography\":{\"textTransform\":\"uppercase\"}}}

RESPONSIVE DESIGN:
- Container alignments: \"align\":\"wide\" or \"align\":\"full\"
- Column stacking: Columns automatically stack on mobile
- Media queries: Use percentage widths and max-widths
- Hide on mobile: {\"className\":\"hide-on-mobile\"} (requires theme support)

IMAGES:
- Placeholder format: {\"url\":\"https://placehold.co/[width]x[height]/[bg-hex]/[text-hex]?text=[URL-encoded-text]\"}
- Always include: {\"alt\":\"Descriptive alternative text\"}
- Size options: {\"sizeSlug\":\"thumbnail|medium|large|full\"}
- Alignment: {\"align\":\"left|center|right|wide|full\"}

USER INSTRUCTIONS PRIORITY:
The user's description overrides all defaults. Follow exactly what they specify for:
- Colors (hex codes, gradients, named colors)
- Layout structure (columns, grids, sidebars)
- Content density and spacing
- Typography preferences
- Design style
- Any specific visual requirements";
	}

	/**
	 * Get enhanced block examples with complex patterns.
	 *
	 * @since 1.0.0
	 * @return string Enhanced block examples.
	 */
	private function get_enhanced_block_examples() {
		return "ENHANCED BLOCK EXAMPLES:

1. ADVANCED HEADING WITH STYLE:
<!-- wp:heading {\"level\":2,\"style\":{\"color\":{\"text\":\"#1e40af\"},\"typography\":{\"fontSize\":\"48px\",\"fontWeight\":\"700\",\"lineHeight\":\"1.2\"},\"spacing\":{\"margin\":{\"bottom\":\"24px\"}}},\"className\":\"animated-heading\"} -->
<h2 class=\"animated-heading has-text-color\" style=\"color:#1e40af;font-size:48px;font-weight:700;line-height:1.2;margin-bottom:24px\">Your Heading</h2>
<!-- /wp:heading -->

2. RESPONSIVE COLUMNS WITH STYLING:
<!-- wp:columns {\"verticalAlignment\":\"center\",\"align\":\"wide\",\"style\":{\"spacing\":{\"blockGap\":{\"top\":\"48px\",\"left\":\"48px\"},\"padding\":{\"top\":\"80px\",\"bottom\":\"80px\"}}}} -->
<div class=\"wp-block-columns alignwide are-vertically-aligned-center\" style=\"padding-top:80px;padding-bottom:80px\">
    <!-- wp:column {\"verticalAlignment\":\"center\",\"width\":\"60%\"} -->
    <div class=\"wp-block-column is-vertically-aligned-center\" style=\"flex-basis:60%\">
        <!-- wp:heading {\"level\":3} -->
        <h3>Feature Title</h3>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>Description text here.</p>
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {\"verticalAlignment\":\"center\",\"width\":\"40%\"} -->
    <div class=\"wp-block-column is-vertically-aligned-center\" style=\"flex-basis:40%\">
        <!-- wp:image {\"sizeSlug\":\"large\",\"linkDestination\":\"none\"} -->
        <figure class=\"wp-block-image size-large\"><img src=\"https://placehold.co/600x400/667eea/ffffff?text=Feature+Image\" alt=\"Feature visualization\"/></figure>
        <!-- /wp:image -->
    </div>
    <!-- /wp:column -->
</div>
<!-- /wp:columns -->

3. HERO SECTION WITH GRADIENT OVERLAY:
<!-- wp:cover {\"url\":\"https://placehold.co/1920x800/1e293b/ffffff?text=Hero+Background\",\"dimRatio\":70,\"overlayColor\":\"primary\",\"minHeight\":600,\"minHeightUnit\":\"px\",\"contentPosition\":\"center center\",\"align\":\"full\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"100px\",\"bottom\":\"100px\"}}}} -->
<div class=\"wp-block-cover alignfull\" style=\"padding-top:100px;padding-bottom:100px;min-height:600px\">
    <span aria-hidden=\"true\" class=\"wp-block-cover__background has-primary-background-color has-background-dim-70 has-background-dim\"></span>
    <img class=\"wp-block-cover__image-background\" alt=\"Hero Background\" src=\"https://placehold.co/1920x800/1e293b/ffffff?text=Hero+Background\" data-object-fit=\"cover\"/>
    <div class=\"wp-block-cover__inner-container\">
        <!-- wp:heading {\"textAlign\":\"center\",\"level\":1,\"style\":{\"typography\":{\"fontSize\":\"64px\"},\"color\":{\"text\":\"#ffffff\"}}} -->
        <h1 class=\"has-text-align-center has-text-color\" style=\"color:#ffffff;font-size:64px\">Welcome to Excellence</h1>
        <!-- /wp:heading -->
    </div>
</div>
<!-- /wp:cover -->

4. CARD WITH SHADOW AND HOVER:
<!-- wp:group {\"style\":{\"spacing\":{\"padding\":{\"top\":\"32px\",\"bottom\":\"32px\",\"left\":\"32px\",\"right\":\"32px\"}},\"border\":{\"radius\":\"12px\"},\"color\":{\"background\":\"#ffffff\"},\"elements\":{\"link\":{\"color\":{\"text\":\"#0891b2\"}}},\"shadow\":\"0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)\"}} -->
<div class=\"wp-block-group has-background has-link-color\" style=\"background-color:#ffffff;border-radius:12px;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px;box-shadow:0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)\">
    <!-- wp:heading {\"level\":3,\"style\":{\"spacing\":{\"margin\":{\"bottom\":\"16px\"}}}} -->
    <h3 style=\"margin-bottom:16px\">Card Title</h3>
    <!-- /wp:heading -->
    <!-- wp:paragraph -->
    <p>Card content with proper spacing and styling.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

5. ADVANCED BUTTON GROUP:
<!-- wp:buttons {\"layout\":{\"type\":\"flex\",\"justifyContent\":\"center\"},\"style\":{\"spacing\":{\"blockGap\":\"16px\"}}} -->
<div class=\"wp-block-buttons\">
    <!-- wp:button {\"gradient\":\"vivid-cyan-blue-to-vivid-purple\",\"style\":{\"border\":{\"radius\":\"50px\"},\"spacing\":{\"padding\":{\"left\":\"32px\",\"right\":\"32px\",\"top\":\"16px\",\"bottom\":\"16px\"}}}} -->
    <div class=\"wp-block-button\"><a class=\"wp-block-button__link has-vivid-cyan-blue-to-vivid-purple-gradient-background has-background wp-element-button\" style=\"border-radius:50px;padding-top:16px;padding-right:32px;padding-bottom:16px;padding-left:32px\">Primary Action</a></div>
    <!-- /wp:button -->
    <!-- wp:button {\"style\":{\"border\":{\"radius\":\"50px\",\"width\":\"2px\"},\"spacing\":{\"padding\":{\"left\":\"32px\",\"right\":\"32px\",\"top\":\"14px\",\"bottom\":\"14px\"}}},\"borderColor\":\"primary\",\"className\":\"is-style-outline\"} -->
    <div class=\"wp-block-button is-style-outline\"><a class=\"wp-block-button__link has-border-color has-primary-border-color wp-element-button\" style=\"border-width:2px;border-radius:50px;padding-top:14px;padding-right:32px;padding-bottom:14px;padding-left:32px\">Secondary Action</a></div>
    <!-- /wp:button -->
</div>
<!-- /wp:buttons -->

ADVANCED PATTERNS:
- Gradient overlays: Use dimRatio with overlayColor or custom gradients
- Box shadows: Apply via style object or custom CSS classes
- Hover states: Use className and theme CSS or inline :hover styles
- Animations: Add className for CSS animations (theme-dependent)
- Custom spacing: Use 8px grid system for consistency
- Nested groups: Wrap related content for better organization";
	}

	/**
	 * Add learning instructions to avoid common mistakes.
	 *
	 * @since 1.0.0
	 * @return string Learning instructions.
	 */
	private function add_learning_instructions() {
		return "COMMON MISTAKES TO AVOID:
- Don't nest columns inside columns (creates mobile layout issues)
- Don't use inline CSS when block attributes exist
- Don't skip heading levels (h1→h3 breaks accessibility)
- Don't use only px units (mix with rem/% for responsiveness)
- Don't forget to close all opened blocks
- Don't use deprecated block formats or attributes
- Don't add empty paragraphs for spacing (use spacer blocks)
- Don't hardcode colors if theme colors are appropriate
- Don't create overly deep nesting (max 4-5 levels)
- Don't forget alt text for images

PREFERRED PATTERNS:
- Wrap related content in group blocks with consistent padding
- Use 8px grid system for spacing (8, 16, 24, 32, 48, 64, 80px)
- Apply theme colors when available, custom colors when specific
- Choose semantic blocks (quote vs paragraph with quotes)
- Include micro-interactions (hover states, transitions)
- Use proper heading hierarchy for document outline
- Implement consistent border radius across similar elements
- Apply shadows subtly for depth without overwhelming
- Use spacer blocks between major sections
- Maintain consistent column gaps and padding

PERFORMANCE BEST PRACTICES:
- Optimize image sizes (don't use 4000x3000 for a 400x300 display)
- Limit the number of cover blocks with large images
- Use CSS classes over inline styles when patterns repeat
- Avoid excessive nesting that creates complex DOM
- Use native blocks over custom HTML when possible";
	}

	/**
	 * Initialize pattern library with complete section patterns.
	 *
	 * @since 1.0.0
	 */
	private function init_pattern_library() {
		$this->pattern_library = array(
			'hero_gradient_cta' => '<!-- wp:cover {"gradient":"vivid-cyan-blue-to-vivid-purple","minHeight":600,"minHeightUnit":"px","contentPosition":"center center","align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px"}}}} -->
<div class="wp-block-cover alignfull" style="padding-top:80px;padding-bottom:80px;min-height:600px">
    <span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim has-background-gradient has-vivid-cyan-blue-to-vivid-purple-gradient-background"></span>
    <div class="wp-block-cover__inner-container">
        <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"56px","fontWeight":"700","lineHeight":"1.1"},"color":{"text":"#ffffff"},"spacing":{"margin":{"bottom":"24px"}}}} -->
        <h1 class="has-text-align-center has-text-color" style="color:#ffffff;font-size:56px;font-weight:700;line-height:1.1;margin-bottom:24px">Transform Your Business Today</h1>
        <!-- /wp:heading -->
        
        <!-- wp:paragraph {"align":"center","style":{"color":{"text":"#ffffff"},"typography":{"fontSize":"20px","lineHeight":"1.6"},"spacing":{"margin":{"bottom":"40px"}}}} -->
        <p class="has-text-align-center has-text-color" style="color:#ffffff;font-size:20px;line-height:1.6;margin-bottom:40px">Unlock the power of innovation with our cutting-edge solutions designed to elevate your success</p>
        <!-- /wp:paragraph -->
        
        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"16px"}}} -->
        <div class="wp-block-buttons">
            <!-- wp:button {"backgroundColor":"white","textColor":"vivid-cyan-blue","style":{"border":{"radius":"50px"},"spacing":{"padding":{"left":"40px","right":"40px","top":"16px","bottom":"16px"}},"typography":{"fontSize":"18px","fontWeight":"600"}}} -->
            <div class="wp-block-button"><a class="wp-block-button__link has-vivid-cyan-blue-color has-white-background-color has-text-color has-background wp-element-button" style="border-radius:50px;padding-top:16px;padding-right:40px;padding-bottom:16px;padding-left:40px;font-size:18px;font-weight:600">Get Started Free</a></div>
            <!-- /wp:button -->
            
            <!-- wp:button {"textColor":"white","style":{"border":{"radius":"50px","width":"2px"},"spacing":{"padding":{"left":"40px","right":"40px","top":"14px","bottom":"14px"}},"typography":{"fontSize":"18px","fontWeight":"600"}},"borderColor":"white","className":"is-style-outline"} -->
            <div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-text-color has-border-color has-white-border-color wp-element-button" style="border-width:2px;border-radius:50px;padding-top:14px;padding-right:40px;padding-bottom:14px;padding-left:40px;font-size:18px;font-weight:600">Watch Demo</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    </div>
</div>
<!-- /wp:cover -->',
			
			'feature_cards_grid' => '<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"80px","bottom":"80px"}}}} -->
<div class="wp-block-group alignwide" style="padding-top:80px;padding-bottom:80px">
    <!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"16px"}}}} -->
    <h2 class="has-text-align-center" style="margin-bottom:16px">Our Features</h2>
    <!-- /wp:heading -->
    
    <!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"48px"}}}} -->
    <p class="has-text-align-center" style="margin-bottom:48px">Discover what makes us different</p>
    <!-- /wp:paragraph -->
    
    <!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"32px","left":"32px"}}}} -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","right":"32px","bottom":"40px","left":"32px"}},"border":{"radius":"8px"},"color":{"background":"#f8fafc"}}} -->
            <div class="wp-block-group has-background" style="background-color:#f8fafc;border-radius:8px;padding-top:40px;padding-right:32px;padding-bottom:40px;padding-left:32px">
                <!-- wp:image {"width":64,"height":64,"sizeSlug":"full","linkDestination":"none","align":"center"} -->
                <figure class="wp-block-image aligncenter size-full is-resized"><img src="https://placehold.co/128x128/3b82f6/ffffff?text=Icon" alt="Feature icon" width="64" height="64"/></figure>
                <!-- /wp:image -->
                
                <!-- wp:heading {"textAlign":"center","level":3,"style":{"spacing":{"margin":{"top":"24px","bottom":"16px"}}}} -->
                <h3 class="has-text-align-center" style="margin-top:24px;margin-bottom:16px">Fast Performance</h3>
                <!-- /wp:heading -->
                
                <!-- wp:paragraph {"align":"center"} -->
                <p class="has-text-align-center">Lightning-fast load times and optimized performance for the best user experience.</p>
                <!-- /wp:paragraph -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->
        
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","right":"32px","bottom":"40px","left":"32px"}},"border":{"radius":"8px"},"color":{"background":"#f8fafc"}}} -->
            <div class="wp-block-group has-background" style="background-color:#f8fafc;border-radius:8px;padding-top:40px;padding-right:32px;padding-bottom:40px;padding-left:32px">
                <!-- wp:image {"width":64,"height":64,"sizeSlug":"full","linkDestination":"none","align":"center"} -->
                <figure class="wp-block-image aligncenter size-full is-resized"><img src="https://placehold.co/128x128/10b981/ffffff?text=Icon" alt="Feature icon" width="64" height="64"/></figure>
                <!-- /wp:image -->
                
                <!-- wp:heading {"textAlign":"center","level":3,"style":{"spacing":{"margin":{"top":"24px","bottom":"16px"}}}} -->
                <h3 class="has-text-align-center" style="margin-top:24px;margin-bottom:16px">Secure & Reliable</h3>
                <!-- /wp:heading -->
                
                <!-- wp:paragraph {"align":"center"} -->
                <p class="has-text-align-center">Enterprise-grade security with 99.9% uptime guarantee and automated backups.</p>
                <!-- /wp:paragraph -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->
        
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","right":"32px","bottom":"40px","left":"32px"}},"border":{"radius":"8px"},"color":{"background":"#f8fafc"}}} -->
            <div class="wp-block-group has-background" style="background-color:#f8fafc;border-radius:8px;padding-top:40px;padding-right:32px;padding-bottom:40px;padding-left:32px">
                <!-- wp:image {"width":64,"height":64,"sizeSlug":"full","linkDestination":"none","align":"center"} -->
                <figure class="wp-block-image aligncenter size-full is-resized"><img src="https://placehold.co/128x128/8b5cf6/ffffff?text=Icon" alt="Feature icon" width="64" height="64"/></figure>
                <!-- /wp:image -->
                
                <!-- wp:heading {"textAlign":"center","level":3,"style":{"spacing":{"margin":{"top":"24px","bottom":"16px"}}}} -->
                <h3 class="has-text-align-center" style="margin-top:24px;margin-bottom:16px">24/7 Support</h3>
                <!-- /wp:heading -->
                
                <!-- wp:paragraph {"align":"center"} -->
                <p class="has-text-align-center">Round-the-clock customer support to help you succeed every step of the way.</p>
                <!-- /wp:paragraph -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->',
			
			'testimonial_section' => '<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px"}},"color":{"background":"#f8fafc"}}} -->
<div class="wp-block-group alignfull has-background" style="background-color:#f8fafc;padding-top:80px;padding-bottom:80px">
    <!-- wp:group {"align":"wide"} -->
    <div class="wp-block-group alignwide">
        <!-- wp:heading {"textAlign":"center","style":{"spacing":{"margin":{"bottom":"48px"}}}} -->
        <h2 class="has-text-align-center" style="margin-bottom:48px">What Our Customers Say</h2>
        <!-- /wp:heading -->
        
        <!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"32px","left":"32px"}}}} -->
        <div class="wp-block-columns">
            <!-- wp:column -->
            <div class="wp-block-column">
                <!-- wp:group {"style":{"spacing":{"padding":{"top":"32px","right":"32px","bottom":"32px","left":"32px"}},"border":{"radius":"8px"},"color":{"background":"#ffffff"}}} -->
                <div class="wp-block-group has-background" style="background-color:#ffffff;border-radius:8px;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px">
                    <!-- wp:paragraph {"style":{"typography":{"fontSize":"18px","lineHeight":"1.6"},"spacing":{"margin":{"bottom":"24px"}}}} -->
                    <p style="font-size:18px;line-height:1.6;margin-bottom:24px">"This product has completely transformed how we do business. The results have been incredible and the support team is fantastic."</p>
                    <!-- /wp:paragraph -->
                    
                    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
                    <div class="wp-block-group">
                        <!-- wp:image {"width":48,"height":48,"sizeSlug":"full","linkDestination":"none","style":{"border":{"radius":"50%"}}} -->
                        <figure class="wp-block-image size-full is-resized" style="border-radius:50%"><img src="https://placehold.co/96x96/94a3b8/ffffff?text=JD" alt="John Doe" width="48" height="48" style="border-radius:50%"/></figure>
                        <!-- /wp:image -->
                        
                        <!-- wp:group {"style":{"spacing":{"blockGap":"4px"}}} -->
                        <div class="wp-block-group">
                            <!-- wp:paragraph {"style":{"typography":{"fontWeight":"600"}}} -->
                            <p style="font-weight:600">John Doe</p>
                            <!-- /wp:paragraph -->
                            
                            <!-- wp:paragraph {"style":{"color":{"text":"#64748b"},"typography":{"fontSize":"14px"}}} -->
                            <p class="has-text-color" style="color:#64748b;font-size:14px">CEO, Tech Company</p>
                            <!-- /wp:paragraph -->
                        </div>
                        <!-- /wp:group -->
                    </div>
                    <!-- /wp:group -->
                </div>
                <!-- /wp:group -->
            </div>
            <!-- /wp:column -->
            
            <!-- wp:column -->
            <div class="wp-block-column">
                <!-- wp:group {"style":{"spacing":{"padding":{"top":"32px","right":"32px","bottom":"32px","left":"32px"}},"border":{"radius":"8px"},"color":{"background":"#ffffff"}}} -->
                <div class="wp-block-group has-background" style="background-color:#ffffff;border-radius:8px;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px">
                    <!-- wp:paragraph {"style":{"typography":{"fontSize":"18px","lineHeight":"1.6"},"spacing":{"margin":{"bottom":"24px"}}}} -->
                    <p style="font-size:18px;line-height:1.6;margin-bottom:24px">"Outstanding service and amazing results. We\'ve seen a 300% increase in efficiency since implementing this solution."</p>
                    <!-- /wp:paragraph -->
                    
                    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
                    <div class="wp-block-group">
                        <!-- wp:image {"width":48,"height":48,"sizeSlug":"full","linkDestination":"none","style":{"border":{"radius":"50%"}}} -->
                        <figure class="wp-block-image size-full is-resized" style="border-radius:50%"><img src="https://placehold.co/96x96/94a3b8/ffffff?text=JS" alt="Jane Smith" width="48" height="48" style="border-radius:50%"/></figure>
                        <!-- /wp:image -->
                        
                        <!-- wp:group {"style":{"spacing":{"blockGap":"4px"}}} -->
                        <div class="wp-block-group">
                            <!-- wp:paragraph {"style":{"typography":{"fontWeight":"600"}}} -->
                            <p style="font-weight:600">Jane Smith</p>
                            <!-- /wp:paragraph -->
                            
                            <!-- wp:paragraph {"style":{"color":{"text":"#64748b"},"typography":{"fontSize":"14px"}}} -->
                            <p class="has-text-color" style="color:#64748b;font-size:14px">Marketing Director</p>
                            <!-- /wp:paragraph -->
                        </div>
                        <!-- /wp:group -->
                    </div>
                    <!-- /wp:group -->
                </div>
                <!-- /wp:group -->
            </div>
            <!-- /wp:column -->
            
            <!-- wp:column -->
            <div class="wp-block-column">
                <!-- wp:group {"style":{"spacing":{"padding":{"top":"32px","right":"32px","bottom":"32px","left":"32px"}},"border":{"radius":"8px"},"color":{"background":"#ffffff"}}} -->
                <div class="wp-block-group has-background" style="background-color:#ffffff;border-radius:8px;padding-top:32px;padding-right:32px;padding-bottom:32px;padding-left:32px">
                    <!-- wp:paragraph {"style":{"typography":{"fontSize":"18px","lineHeight":"1.6"},"spacing":{"margin":{"bottom":"24px"}}}} -->
                    <p style="font-size:18px;line-height:1.6;margin-bottom:24px">"The best investment we\'ve made this year. Simple to use, powerful features, and exceptional customer support."</p>
                    <!-- /wp:paragraph -->
                    
                    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
                    <div class="wp-block-group">
                        <!-- wp:image {"width":48,"height":48,"sizeSlug":"full","linkDestination":"none","style":{"border":{"radius":"50%"}}} -->
                        <figure class="wp-block-image size-full is-resized" style="border-radius:50%"><img src="https://placehold.co/96x96/94a3b8/ffffff?text=MJ" alt="Mike Johnson" width="48" height="48" style="border-radius:50%"/></figure>
                        <!-- /wp:image -->
                        
                        <!-- wp:group {"style":{"spacing":{"blockGap":"4px"}}} -->
                        <div class="wp-block-group">
                            <!-- wp:paragraph {"style":{"typography":{"fontWeight":"600"}}} -->
                            <p style="font-weight:600">Mike Johnson</p>
                            <!-- /wp:paragraph -->
                            
                            <!-- wp:paragraph {"style":{"color":{"text":"#64748b"},"typography":{"fontSize":"14px"}}} -->
                            <p class="has-text-color" style="color:#64748b;font-size:14px">Product Manager</p>
                            <!-- /wp:paragraph -->
                        </div>
                        <!-- /wp:group -->
                    </div>
                    <!-- /wp:group -->
                </div>
                <!-- /wp:group -->
            </div>
            <!-- /wp:column -->
        </div>
        <!-- /wp:columns -->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->',
		);
	}

	/**
	 * Get cached pattern.
	 *
	 * @since 1.0.0
	 * @param string $pattern_key Pattern identifier.
	 * @return string|false Pattern content or false if not cached.
	 */
	private function get_cached_pattern( $pattern_key ) {
		$cache_key = 'layoutberg_pattern_' . md5( $pattern_key );
		$cached = get_transient( $cache_key );
		
		if ( $cached !== false ) {
			return $cached;
		}
		
		// Check if pattern exists in library
		if ( isset( $this->pattern_library[ $pattern_key ] ) ) {
			$pattern = $this->pattern_library[ $pattern_key ];
			
			// Cache for 1 week
			set_transient( $cache_key, $pattern, WEEK_IN_SECONDS );
			
			return $pattern;
		}
		
		return false;
	}

	/**
	 * Optimize prompt for model token limits.
	 *
	 * @since 1.0.0
	 * @param string $prompt Original prompt.
	 * @param string $model Model identifier.
	 * @return string Optimized prompt.
	 */
	public function optimize_prompt_for_model( $prompt, $model ) {
		$limits = $this->get_model_limits( $model );
		$estimated_tokens = $this->estimate_token_count( $prompt );
		
		// If prompt is within optimal range, return as-is
		if ( $estimated_tokens <= $limits['optimal_prompt'] ) {
			return $prompt;
		}
		
		// Compress prompt if too long
		return $this->compress_prompt( $prompt, $limits['optimal_prompt'] );
	}

	/**
	 * Compress prompt to fit token limits.
	 *
	 * @since 1.0.0
	 * @param string $prompt Original prompt.
	 * @param int    $target_tokens Target token count.
	 * @return string Compressed prompt.
	 */
	private function compress_prompt( $prompt, $target_tokens ) {
		// Priority order for sections to keep
		$sections = array(
			'CRITICAL REQUIREMENTS' => 1,
			'USER INSTRUCTIONS PRIORITY' => 1,
			'BLOCK STRUCTURE RULES' => 2,
			'STYLING BEST PRACTICES' => 3,
			'ENHANCED BLOCK EXAMPLES' => 3,
			'COMMON MISTAKES TO AVOID' => 4,
			'PREFERRED PATTERNS' => 4,
			'PERFORMANCE BEST PRACTICES' => 5,
		);
		
		// Extract sections
		$extracted_sections = array();
		foreach ( $sections as $section => $priority ) {
			if ( preg_match( '/' . preg_quote( $section, '/' ) . ':(.+?)(?=\n[A-Z\s]+:|$)/s', $prompt, $matches ) ) {
				$extracted_sections[ $priority ][] = $section . ':' . $matches[1];
			}
		}
		
		// Rebuild prompt with priority sections
		$compressed = '';
		$current_tokens = 0;
		
		ksort( $extracted_sections );
		foreach ( $extracted_sections as $priority => $priority_sections ) {
			foreach ( $priority_sections as $section ) {
				$section_tokens = $this->estimate_token_count( $section );
				if ( $current_tokens + $section_tokens <= $target_tokens ) {
					$compressed .= "\n\n" . $section;
					$current_tokens += $section_tokens;
				}
			}
		}
		
		return trim( $compressed );
	}

	/**
	 * Preprocess prompt for better understanding.
	 *
	 * @since 1.0.0
	 * @param string $prompt User prompt.
	 * @return string Preprocessed prompt.
	 */
	public function preprocess_prompt( $prompt ) {
		// Normalize common variations
		$replacements = array(
			'/\bcall[\s-]?to[\s-]?action\b/i' => 'CTA',
			'/\bhero\s+section\b/i' => 'hero section',
			'/\b(\d+)\s+col(umn)?s?\b/i' => '$1 columns',
			'/\bbg\b/i' => 'background',
			'/\bpx\b/i' => 'pixels',
			'/\bfull[\s-]?width\b/i' => 'full width',
			'/\b2[\s-]?col(umn)?\b/i' => 'two columns',
			'/\b3[\s-]?col(umn)?\b/i' => 'three columns',
			'/\b4[\s-]?col(umn)?\b/i' => 'four columns',
		);
		
		foreach ( $replacements as $pattern => $replacement ) {
			$prompt = preg_replace( $pattern, $replacement, $prompt );
		}
		
		// Fix common typos
		$typo_fixes = array(
			'collumn' => 'column',
			'collumns' => 'columns',
			'backgroud' => 'background',
			'botton' => 'button',
			'bottons' => 'buttons',
			'gradiant' => 'gradient',
			'gradiants' => 'gradients',
			'testimonal' => 'testimonial',
			'testimoinals' => 'testimonials',
			'priceing' => 'pricing',
			'responsiv' => 'responsive',
		);
		
		foreach ( $typo_fixes as $wrong => $correct ) {
			$prompt = str_ireplace( $wrong, $correct, $prompt );
		}
		
		return $prompt;
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
		// Preprocess the prompt
		$enhanced = $this->preprocess_prompt( $prompt );
		
		// Add implicit requirements based on content
		$content_enhancements = array(
			'/\b(shop|store|product|ecommerce|woocommerce)\b/i' => ' Include product grid layout with price displays, product images, and add to cart buttons. Use card-based design for products.',
			'/\b(blog|article|post|news)\b/i' => ' Include proper article structure with featured image, post meta (date, author, category), content sections with proper headings, and author bio section.',
			'/\b(portfolio|gallery|showcase)\b/i' => ' Create a visual grid layout with image previews, hover effects, and project titles. Include filtering options placeholder.',
			'/\b(restaurant|menu|food|dining)\b/i' => ' Include appetizing imagery, menu sections with prices, reservation call-to-action, and hours of operation.',
			'/\b(medical|healthcare|doctor|clinic)\b/i' => ' Use calming colors, include trust signals, appointment booking CTA, and professional imagery.',
			'/\b(fitness|gym|workout|training)\b/i' => ' Use energetic design, include class schedules, trainer profiles, and membership CTAs.',
			'/\b(real estate|property|housing)\b/i' => ' Include property features grid, image galleries, location information, and contact agent CTAs.',
		);
		
		foreach ( $content_enhancements as $pattern => $enhancement ) {
			if ( preg_match( $pattern, $enhanced ) ) {
				$enhanced .= $enhancement;
			}
		}
		
		// Add responsive considerations if not mentioned
		if ( ! preg_match( '/\b(mobile|responsive|stack|tablet)\b/i', $enhanced ) ) {
			$enhanced .= ' Ensure all columns stack properly on mobile devices and maintain readability across all screen sizes.';
		}
		
		// Add accessibility if not mentioned
		if ( ! preg_match( '/\b(accessible|accessibility|aria|alt|screen reader)\b/i', $enhanced ) ) {
			$enhanced .= ' Include proper accessibility features with semantic HTML, alt text for images, and ARIA labels where appropriate.';
		}
		
		// Add color guidance if colors mentioned but not specific
		if ( preg_match( '/\b(color|colour|colorful|vibrant|bright)\b/i', $enhanced ) && 
		    ! preg_match( '/#[a-fA-F0-9]{3,6}\b/', $enhanced ) ) {
			$enhanced .= ' Use modern, cohesive color scheme with proper contrast for readability.';
		}
		
		// Add spacing guidance if not mentioned
		if ( ! preg_match( '/\b(spacing|space|padding|margin|gap)\b/i', $enhanced ) ) {
			$enhanced .= ' Apply consistent, generous spacing between sections for visual breathing room.';
		}
		
		return $enhanced;
	}

	/**
	 * Validate AI output for correct Gutenberg format.
	 *
	 * @since 1.0.0
	 * @param string $output AI-generated output.
	 * @return bool|array True if valid, array of errors if invalid.
	 */
	public function validate_ai_output( $output ) {
		$errors = array();
		
		// Check if output starts with Gutenberg block comment
		if ( ! preg_match( '/^\s*<!--\s*wp:/', trim( $output ) ) ) {
			$errors[] = 'Output does not start with valid Gutenberg block markup';
		}
		
		// Check for balanced block tags
		preg_match_all( '/<!--\s*wp:([a-z\-\/]+)(?:\s|{)/', $output, $opens );
		preg_match_all( '/<!--\s*\/wp:([a-z\-]+)\s*-->/', $output, $closes );
		
		$open_blocks = array();
		foreach ( $opens[1] as $block ) {
			if ( strpos( $block, '/' ) !== 0 ) {
				$open_blocks[] = $block;
			}
		}
		
		$close_blocks = $closes[1];
		
		if ( count( $open_blocks ) !== count( $close_blocks ) ) {
			$errors[] = sprintf( 
				'Unbalanced block tags: %d opening, %d closing', 
				count( $open_blocks ), 
				count( $close_blocks ) 
			);
		}
		
		// Validate JSON in block attributes
		preg_match_all( '/<!--\s*wp:[a-z\-]+\s*(\{[^}]+\})/', $output, $json_matches );
		foreach ( $json_matches[1] as $index => $json ) {
			$decoded = json_decode( $json );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$errors[] = sprintf( 
					'Invalid JSON in block %d: %s', 
					$index + 1, 
					json_last_error_msg() 
				);
			}
		}
		
		// Check for common formatting issues
		if ( strpos( $output, '```' ) !== false ) {
			$errors[] = 'Output contains markdown code blocks';
		}
		
		if ( preg_match( '/^(Here\'s|This is|The following)/i', trim( $output ) ) ) {
			$errors[] = 'Output contains explanatory text instead of pure markup';
		}
		
		// Check for deprecated block formats
		$deprecated_patterns = array(
			'/<!--\s*wp:core\//' => 'Remove "core/" prefix from block names',
			'/style="[^"]*font-family:[^;"]+/i' => 'Avoid hardcoded font-family in inline styles',
			'/<!--\s*wp:html/' => 'Use native blocks instead of custom HTML block',
		);
		
		foreach ( $deprecated_patterns as $pattern => $message ) {
			if ( preg_match( $pattern, $output ) ) {
				$errors[] = $message;
			}
		}
		
		// Return validation result
		return empty( $errors ) ? true : $errors;
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
			'modern' => "MODERN STYLE REQUIREMENTS:
• Spacing: Use generous spacing (48px-80px between major sections)
• Colors: Vibrant gradients (e.g., linear-gradient(135deg,#667eea 0%,#764ba2 100%))
• Typography: Large, bold headings (fontSize:\"x-large\" or custom 48px+)
• Layout: Asymmetric columns, overlapping elements, creative arrangements
• Elements: Rounded corners (8px-16px), subtle shadows, glass-morphism effects
• Images: Full-width hero images, masked shapes, gradient overlays
• Buttons: Pill-shaped (border-radius: 50px), gradient backgrounds, hover effects
• Cards: Elevated with shadows, hover animations, gradient accents",
			
			'classic' => "CLASSIC STYLE REQUIREMENTS:
• Spacing: Consistent, moderate spacing (32px between sections)
• Colors: Traditional palette (navy #001F3F, grays #666666, white #FFFFFF)
• Typography: Serif fonts for headings, moderate sizes
• Layout: Symmetrical grids, centered content, traditional hierarchy
• Elements: Subtle 1px borders, minimal shadows, square corners
• Images: Framed with borders, traditional aspect ratios
• Buttons: Rectangular with subtle hover states
• Design: Timeless, professional, understated elegance",
			
			'minimal' => "MINIMAL STYLE REQUIREMENTS:
• Spacing: Maximum whitespace (80px+ between sections)
• Colors: Monochrome (black #000000, white #FFFFFF, grays)
• Typography: Simple, clean sans-serif, consistent sizes
• Layout: Single column or simple grids, centered content
• Elements: No decorative elements, focus on content
• Images: Full-bleed or with ample padding
• Buttons: Text-only or simple borders, no backgrounds
• Design: Extreme simplicity, zen-like calm",
			
			'bold' => "BOLD STYLE REQUIREMENTS:
• Spacing: Dynamic contrast (mix tight and loose spacing)
• Colors: Bright, high-contrast (#FF006E, #FB5607, electric gradients)
• Typography: Extra-large headings (fontSize:\"xx-large\" or 64px+)
• Layout: Asymmetric, breaking grid conventions
• Elements: Strong geometric shapes, thick borders (3px+)
• Images: Duotone effects, color overlays, dramatic crops
• Buttons: Large, impossible to miss, strong CTAs
• Design: High energy, attention-grabbing, memorable",
			
			'elegant' => "ELEGANT STYLE REQUIREMENTS:
• Spacing: Golden ratio proportions, balanced negative space
• Colors: Muted, sophisticated palette (deep purples, golds, creams)
• Typography: Refined serif fonts, elegant script accents
• Layout: Graceful asymmetry, flowing sections
• Elements: Delicate borders, subtle gradients, refined details
• Images: High-quality, artistic composition
• Buttons: Understated with elegant hover transitions
• Design: Luxurious, sophisticated, timeless beauty",
			
			'playful' => "PLAYFUL STYLE REQUIREMENTS:
• Spacing: Varied and dynamic, creating rhythm
• Colors: Bright, cheerful combinations (rainbow gradients, pastels)
• Typography: Mixed sizes, playful fonts, rotated text
• Layout: Unexpected arrangements, tilted elements
• Elements: Rounded corners (16px+), bouncy animations
• Images: Illustrations, mascots, fun overlays
• Buttons: Colorful, animated, personality-driven
• Design: Fun, approachable, smile-inducing",
			
			'corporate' => "CORPORATE STYLE REQUIREMENTS:
• Spacing: Professional consistency (40px standard)
• Colors: Conservative (blues #0066CC, grays #4A4A4A, white)
• Typography: Clean sans-serif, clear hierarchy
• Layout: Grid-based, predictable, organized
• Elements: Subtle shadows, professional icons
• Images: Business photography, team photos, office settings
• Buttons: Professional CTAs, clear actions
• Design: Trustworthy, stable, professional",
			
			'tech' => "TECH STYLE REQUIREMENTS:
• Spacing: Precise grid system (8px base unit)
• Colors: Dark mode friendly (#1a1a1a, neon accents #00ff00)
• Typography: Monospace accents, modern sans-serif
• Layout: Modular components, dashboard-like
• Elements: Glassmorphism, neon glows, code-like elements
• Images: Abstract tech patterns, circuit designs
• Buttons: Cyber-style with hover glows
• Design: Futuristic, innovative, cutting-edge",
		);
		
		return isset( $style_guides[ $style ] ) ? $style_guides[ $style ] : $style_guides['modern'];
	}

	/**
	 * Get layout-specific instructions.
	 *
	 * @since 1.0.0
	 * @param string $layout Layout preference.
	 * @return string Layout instructions.
	 */
	private function get_layout_instructions( $layout ) {
		$layout_guides = array(
			'single-column' => "SINGLE COLUMN LAYOUT:
• Structure: Full-width sections stacking vertically
• Container: Max-width 800px-1200px, centered with auto margins
• Sections: Group blocks with consistent padding (60px-80px vertical)
• Content: Center-aligned or left-aligned with generous line-height
• Images: Full-width or contained within column width
• Spacing: Clear visual separation between sections
• Mobile: Already optimized, may need minor padding adjustments",
			
			'sidebar' => "SIDEBAR LAYOUT:
• Structure: Two-column layout (main content + sidebar)
• Proportions: 2/3 + 1/3 or 3/4 + 1/4 ratio
• Main content: Primary information, articles, main features
• Sidebar: Navigation, related links, ads, secondary info
• Sticky sidebar: Optional for desktop viewing
• Mobile: Sidebar stacks below main content
• Spacing: Clear gutter between columns (32px+)",
			
			'grid' => "GRID LAYOUT:
• Structure: Equal-width columns in rows
• Columns: 2, 3, or 4 columns based on content
• Cards: Consistent height and styling
• Gaps: Uniform spacing (24px-32px)
• Alignment: Perfect grid alignment
• Mobile: Responsive stacking (4→2→1, 3→1, 2→1)
• Use cases: Product listings, team members, features",
			
			'asymmetric' => "ASYMMETRIC LAYOUT:
• Structure: Varied column widths and arrangements
• Patterns: 1/3 + 2/3, 1/4 + 1/2 + 1/4, offset sections
• Visual interest: Breaking traditional alignment
• Overlap: Elements can overlap or stagger
• Negative space: Used creatively for impact
• Mobile: Thoughtful reflow maintaining visual interest
• Balance: Asymmetric but visually balanced",
			
			'magazine' => "MAGAZINE LAYOUT:
• Structure: Mixed column widths, feature articles
• Hero article: Large featured section at top
• Grid mixing: Combine 2, 3, and 4 column sections
• Typography: Varied sizes for visual hierarchy
• Images: Mixed sizes, pull quotes, captions
• Sidebar elements: Scattered throughout
• Mobile: Simplified stacking with maintained hierarchy",
			
			'masonry' => "MASONRY LAYOUT:
• Structure: Pinterest-style vertical flow
• Columns: Fixed width, variable height
• Cards: Different heights creating organic flow
• Gaps: Consistent horizontal and vertical spacing
• Loading: Progressive, maintaining layout
• Mobile: Reduces to fewer columns (usually 1-2)
• Use cases: Portfolios, galleries, blogs",
		);
		
		return isset( $layout_guides[ $layout ] ) ? $layout_guides[ $layout ] : $layout_guides['single-column'];
	}

	/**
	 * Get color scheme-specific instructions.
	 *
	 * @since 1.0.0
	 * @param string $color_scheme Color scheme preference.
	 * @return string Color scheme instructions.
	 */
	private function get_color_scheme_instructions( $color_scheme ) {
		$color_guides = array(
			'monochrome' => "MONOCHROME COLOR SCHEME:
• Primary colors: #000000 (black), #FFFFFF (white)
• Grays: #F5F5F5, #E0E0E0, #BDBDBD, #757575, #424242, #212121
• Gradients: linear-gradient(135deg,#e0e0e0 0%,#666666 100%)
• Accents: Use only shades of gray for emphasis
• Images: https://placehold.co/600x400/cccccc/333333
• Contrast: Maximum contrast for text readability
• Usage: Background variations, subtle borders
• Effect: Timeless, sophisticated, focused on content",
			
			'blue' => "BLUE COLOR SCHEME:
• Primary: #0066CC (corporate blue), #001F3F (navy)
• Secondary: #0074D9 (bright blue), #7FDBFF (light blue)
• Accents: #39CCCC (teal), #001f3f (deep navy)
• Gradients: linear-gradient(135deg,#1e3c72 0%,#2a5298 100%)
• Images: https://placehold.co/600x400/0074D9/FFFFFF
• Complementary: Grays and whites for balance
• Usage: Headers, CTAs, link colors, backgrounds
• Effect: Professional, trustworthy, calming",
			
			'green' => "GREEN COLOR SCHEME:
• Primary: #0E4429 (forest), #006400 (dark green)
• Secondary: #2ECC40 (grass), #01FF70 (lime)
• Accents: #3D9970 (olive), #71b280 (sage)
• Gradients: linear-gradient(135deg,#134e5e 0%,#71b280 100%)
• Images: https://placehold.co/600x400/2ECC40/FFFFFF
• Complementary: Earth tones, browns, beiges
• Usage: Eco-friendly, natural, organic themes
• Effect: Natural, calming, growth-oriented",
			
			'warm' => "WARM COLOR SCHEME:
• Primary: #FF4136 (red), #FF851B (orange)
• Secondary: #FFDC00 (yellow), #FF6347 (tomato)
• Accents: #8B4513 (saddle brown), #D2691E (chocolate)
• Gradients: linear-gradient(135deg,#ff6b6b 0%,#feca57 100%)
• Images: https://placehold.co/600x400/FF6347/FFFFFF
• Complementary: Warm grays, creams
• Usage: Energy, passion, autumn themes
• Effect: Energetic, inviting, cozy",
			
			'cool' => "COOL COLOR SCHEME:
• Primary: #001f3f (navy), #0074D9 (blue)
• Secondary: #B10DC9 (purple), #85144b (maroon)
• Accents: #F8F8FF (ghost white), #E6E6FA (lavender)
• Gradients: linear-gradient(135deg,#667eea 0%,#764ba2 100%)
• Images: https://placehold.co/600x400/667eea/FFFFFF
• Complementary: Silver, light grays
• Usage: Technology, innovation, winter themes
• Effect: Calm, professional, sophisticated",
			
			'pastel' => "PASTEL COLOR SCHEME:
• Primary: #FFE5E5 (pink), #E5F3FF (blue)
• Secondary: #E5FFE5 (green), #FFF5E5 (peach)
• Accents: #F5E5FF (lavender), #FFEAA7 (yellow)
• Gradients: linear-gradient(135deg,#ffeaa7 0%,#fab1a0 100%)
• Images: https://placehold.co/600x400/FFE5E5/666666
• Complementary: White, light gray
• Usage: Soft, dreamy, gentle themes
• Effect: Soft, calming, approachable",
			
			'vibrant' => "VIBRANT COLOR SCHEME:
• Primary: #FF006E (pink), #FB5607 (orange)
• Secondary: #FFBE0B (yellow), #8338EC (purple)
• Accents: #3A86FF (blue), #06FFA5 (mint)
• Gradients: linear-gradient(135deg,#f093fb 0%,#f5576c 100%)
• Images: https://placehold.co/600x400/FF006E/FFFFFF
• Complementary: Black for contrast
• Usage: Bold statements, youth-oriented
• Effect: Energetic, exciting, attention-grabbing",
			
			'dark' => "DARK COLOR SCHEME:
• Background: #000000, #0a0a0a, #1a1a1a
• Surface: #2d2d2d, #404040, #525252
• Text: #FFFFFF, #F5F5F5, #E0E0E0
• Accents: #00FF00 (neon green), #00FFFF (cyan)
• Gradients: linear-gradient(135deg,#232526 0%,#414345 100%)
• Images: https://placehold.co/600x400/1a1a1a/00FF00
• Usage: Dark mode, tech themes, luxury
• Effect: Modern, sophisticated, easy on eyes",
		);
		
		return isset( $color_guides[ $color_scheme ] ) ? $color_guides[ $color_scheme ] : '';
	}

	/**
	 * Get density instructions.
	 *
	 * @since 1.0.0
	 * @param string $density Density preference.
	 * @return string Density instructions.
	 */
	private function get_density_instructions( $density ) {
		$density_guides = array(
			'compact' => "COMPACT DENSITY:
• Section spacing: {\"height\":\"20px\"} spacers
• Padding: 16px-24px for containers
• Typography: Smaller sizes (14px-16px body)
• Line height: 1.4-1.5
• Margins: Minimal (8px-16px)
• Images: Smaller, thumbnail sizes
• Effect: Information-dense, efficient",
			
			'normal' => "NORMAL DENSITY:
• Section spacing: {\"height\":\"40px\"} spacers
• Padding: 32px-48px for containers
• Typography: Standard sizes (16px-18px body)
• Line height: 1.6-1.8
• Margins: Comfortable (24px-32px)
• Images: Medium sizes, balanced
• Effect: Readable, balanced, standard",
			
			'spacious' => "SPACIOUS DENSITY:
• Section spacing: {\"height\":\"80px\"} spacers
• Padding: 64px-100px for containers
• Typography: Larger sizes (18px-20px body)
• Line height: 1.8-2.0
• Margins: Generous (48px-64px)
• Images: Large, full-width options
• Effect: Luxurious, easy to scan, modern",
		);
		
		return isset( $density_guides[ $density ] ) ? $density_guides[ $density ] : $density_guides['normal'];
	}

	/**
	 * Get audience-specific instructions.
	 *
	 * @since 1.0.0
	 * @param string $audience Target audience.
	 * @return string Audience instructions.
	 */
	private function get_audience_instructions( $audience ) {
		$audience_guides = array(
			'professional' => "PROFESSIONAL AUDIENCE:
• Language: Formal, industry-specific terminology
• Design: Conservative, credibility-focused
• Content: Data-driven, case studies, credentials
• Images: Professional photography, charts, graphs
• CTAs: 'Schedule Consultation', 'Download Whitepaper'
• Trust signals: Certifications, awards, testimonials
• Layout: Organized, logical flow, clear hierarchy",
			
			'casual' => "CASUAL AUDIENCE:
• Language: Friendly, conversational, relatable
• Design: Relaxed, approachable, warm
• Content: Stories, lifestyle focus, benefits
• Images: Lifestyle photography, relatable scenarios
• CTAs: 'Join Us', 'Get Started', 'Learn More'
• Social proof: User reviews, social media
• Layout: Organic, flowing, inviting",
			
			'young' => "YOUNG AUDIENCE (18-30):
• Language: Modern slang, trending references
• Design: Bold, dynamic, Instagram-worthy
• Content: Quick, scannable, video-friendly
• Images: Vibrant, diverse, authentic
• CTAs: 'Get It Now', 'Join the Movement'
• Social integration: Share buttons, hashtags
• Layout: Mobile-first, thumb-friendly",
			
			'mature' => "MATURE AUDIENCE (50+):
• Language: Clear, respectful, no jargon
• Design: Larger text, high contrast
• Content: Detailed information, clear benefits
• Images: Relatable age representation
• CTAs: 'Learn More', 'Contact Us'
• Trust: Security, privacy, guarantees
• Layout: Simple navigation, larger buttons",
			
			'tech-savvy' => "TECH-SAVVY AUDIENCE:
• Language: Technical accuracy, specifications
• Design: Modern, feature-rich, interactive
• Content: Technical details, comparisons
• Images: UI screenshots, technical diagrams
• CTAs: 'Try Demo', 'View Documentation'
• Features: API info, integrations, specs
• Layout: Information-dense, efficient",
			
			'creative' => "CREATIVE AUDIENCE:
• Language: Expressive, inspirational
• Design: Artistic, experimental, unique
• Content: Portfolio pieces, process insights
• Images: High-quality visuals, artistic
• CTAs: 'Explore', 'Create', 'Collaborate'
• Showcase: Work samples, creative process
• Layout: Gallery-style, visual-first",
		);
		
		return isset( $audience_guides[ $audience ] ) ? $audience_guides[ $audience ] : '';
	}

	/**
	 * Get industry-specific instructions.
	 *
	 * @since 1.0.0
	 * @param string $industry Target industry.
	 * @return string Industry instructions.
	 */
	private function get_industry_instructions( $industry ) {
		$industry_guides = array(
			'healthcare' => "HEALTHCARE INDUSTRY:
• Colors: Calming blues (#4A90E2), greens (#5CB85C), white
• Imagery: Medical professionals, care settings, healing
• Content: Services, specialties, patient resources
• Trust elements: Certifications, HIPAA compliance
• CTAs: 'Book Appointment', 'Find a Doctor'
• Sections: Services, providers, patient portal
• Compliance: Privacy notices, accessibility
• Tone: Compassionate, professional, reassuring",
			
			'finance' => "FINANCE INDUSTRY:
• Colors: Trust blues (#003366), grays (#4A4A4A)
• Imagery: Charts, growth, security symbols
• Content: Services, rates, calculators, education
• Trust elements: FDIC, security badges, testimonials
• CTAs: 'Open Account', 'Calculate Savings'
• Sections: Products, tools, resources, security
• Compliance: Disclaimers, legal notices
• Tone: Authoritative, stable, helpful",
			
			'education' => "EDUCATION INDUSTRY:
• Colors: Inspiring colors, school branding
• Imagery: Students, campus, learning activities
• Content: Programs, admissions, student life
• Trust elements: Accreditation, rankings
• CTAs: 'Apply Now', 'Schedule Tour'
• Sections: Academics, admissions, campus life
• Features: Course catalogs, calendars
• Tone: Inspiring, informative, welcoming",
			
			'retail' => "RETAIL/E-COMMERCE:
• Colors: Brand colors, sale highlights
• Imagery: Product photos, lifestyle shots
• Content: Products, categories, deals
• Trust elements: Reviews, secure checkout
• CTAs: 'Shop Now', 'Add to Cart'
• Sections: Featured products, categories, sales
• Features: Search, filters, recommendations
• Tone: Engaging, urgent (sales), helpful",
			
			'technology' => "TECHNOLOGY INDUSTRY:
• Colors: Modern, often dark themes
• Imagery: Abstract tech, product screenshots
• Content: Features, specs, integrations
• Trust elements: Client logos, case studies
• CTAs: 'Start Free Trial', 'See Demo'
• Sections: Features, pricing, docs, support
• Features: Interactive demos, comparisons
• Tone: Innovative, technical, solution-focused",
			
			'hospitality' => "HOSPITALITY INDUSTRY:
• Colors: Warm, inviting, luxurious
• Imagery: Venues, rooms, experiences, food
• Content: Amenities, packages, local attractions
• Trust elements: Reviews, awards, ratings
• CTAs: 'Book Now', 'Check Availability'
• Sections: Accommodations, dining, amenities
• Features: Booking engine, gallery, virtual tours
• Tone: Welcoming, luxurious, experiential",
			
			'nonprofit' => "NONPROFIT INDUSTRY:
• Colors: Mission-aligned, hopeful
• Imagery: Impact photos, beneficiaries
• Content: Mission, programs, impact stories
• Trust elements: Transparency reports, ratings
• CTAs: 'Donate Now', 'Volunteer'
• Sections: About, programs, impact, get involved
• Features: Donation forms, event calendar
• Tone: Inspiring, urgent, grateful",
			
			'legal' => "LEGAL INDUSTRY:
• Colors: Traditional (navy, burgundy, gold)
• Imagery: Courthouses, professional headshots
• Content: Practice areas, attorney profiles
• Trust elements: Bar admissions, case results
• CTAs: 'Free Consultation', 'Contact Us'
• Sections: Practice areas, attorneys, resources
• Features: Case evaluation forms, FAQs
• Tone: Professional, authoritative, accessible",
		);
		
		return isset( $industry_guides[ $industry ] ) ? $industry_guides[ $industry ] : '';
	}

	/**
	 * Get language-specific instructions.
	 *
	 * @since 1.0.0
	 * @param string $language Language code.
	 * @return string Language instructions.
	 */
	private function get_language_instructions( $language ) {
		$language_guides = array(
			'es' => "SPANISH LANGUAGE:
• Headings: 'Bienvenidos', 'Nuestros Servicios', 'Contáctenos'
• CTAs: 'Más Información', 'Comenzar Ahora', 'Contactar'
• Content: Warm, family-oriented messaging
• Text expansion: Allow 20% more space than English
• Cultural: Warmer colors, personal approach
• Formality: Usted (formal) vs tú (informal)
• Common sections: 'Quiénes Somos', 'Testimonios'",
			
			'fr' => "FRENCH LANGUAGE:
• Headings: 'Bienvenue', 'Nos Services', 'Contactez-nous'
• CTAs: 'En Savoir Plus', 'Commencer', 'Nous Contacter'
• Content: Elegant, sophisticated tone
• Text expansion: Allow 15% more space than English
• Cultural: Refined aesthetics, attention to detail
• Formality: Vous (formal) by default
• Common sections: 'À Propos', 'Témoignages'",
			
			'de' => "GERMAN LANGUAGE:
• Headings: 'Willkommen', 'Unsere Leistungen', 'Kontakt'
• CTAs: 'Mehr Erfahren', 'Jetzt Starten', 'Kontaktieren'
• Content: Precise, detailed, structured
• Text expansion: Allow 30% more space (compound words)
• Cultural: Clean, organized, efficient design
• Formality: Sie (formal) in business context
• Common sections: 'Über Uns', 'Referenzen'",
			
			'it' => "ITALIAN LANGUAGE:
• Headings: 'Benvenuti', 'I Nostri Servizi', 'Contattaci'
• CTAs: 'Scopri di Più', 'Inizia Ora', 'Contatta'
• Content: Expressive, stylish, personal
• Text expansion: Allow 15% more space than English
• Cultural: Aesthetic focus, bella figura
• Formality: Lei (formal) vs tu (informal)
• Common sections: 'Chi Siamo', 'Testimonianze'",
			
			'pt' => "PORTUGUESE LANGUAGE:
• Headings: 'Bem-vindo', 'Nossos Serviços', 'Contato'
• CTAs: 'Saiba Mais', 'Começar Agora', 'Fale Conosco'
• Content: Friendly, warm, approachable
• Text expansion: Allow 15-20% more space
• Cultural: Vibrant colors, personal touch
• Formality: Você (Brazilian) vs O Senhor (formal)
• Common sections: 'Sobre Nós', 'Depoimentos'",
			
			'ja' => "JAPANESE LANGUAGE:
• Headings: 'ようこそ', 'サービス', 'お問い合わせ'
• CTAs: '詳細を見る', '今すぐ始める', 'お問い合わせ'
• Content: Respectful, indirect, detailed
• Text direction: Consider vertical options
• Cultural: Minimalist, balanced, harmonious
• Formality: Keigo (honorific language)
• Common sections: '会社概要', 'お客様の声'",
			
			'zh' => "CHINESE LANGUAGE:
• Headings: '欢迎', '我们的服务', '联系我们'
• CTAs: '了解更多', '立即开始', '联系我们'
• Content: Concise, respectful, auspicious
• Characters: Dense, requires careful spacing
• Cultural: Red for luck, gold for prosperity
• Formality: Respectful, hierarchical
• Common sections: '关于我们', '客户评价'",
			
			'ar' => "ARABIC LANGUAGE:
• Headings: 'مرحبا', 'خدماتنا', 'اتصل بنا'
• CTAs: 'اعرف أكثر', 'ابدأ الآن', 'تواصل معنا'
• Content: RIGHT-TO-LEFT layout required
• Text direction: RTL for all elements
• Cultural: Geometric patterns, rich colors
• Formality: Formal address preferred
• Common sections: 'من نحن', 'آراء العملاء'
• CRITICAL: Mirror entire layout for RTL",
			
			'ru' => "RUSSIAN LANGUAGE:
• Headings: 'Добро пожаловать', 'Наши услуги', 'Контакты'
• CTAs: 'Узнать больше', 'Начать', 'Связаться'
• Content: Direct, informative, professional
• Cyrillic: Wider characters, adjust spacing
• Cultural: Strong colors, clear hierarchy
• Formality: Вы (formal) in business
• Common sections: 'О нас', 'Отзывы'",
			
			'hi' => "HINDI LANGUAGE:
• Headings: 'स्वागत है', 'हमारी सेवाएं', 'संपर्क करें'
• CTAs: 'और जानें', 'शुरू करें', 'संपर्क करें'
• Content: Respectful, family-oriented
• Script: Devanagari requires special handling
• Cultural: Vibrant colors, traditional motifs
• Formality: आप (respectful) by default
• Common sections: 'हमारे बारे में', 'प्रशंसापत्र'",
		);
		
		$general_language_instruction = "MULTILINGUAL REQUIREMENTS:
- Generate ALL text content in {$language} language
- Maintain proper grammar and cultural context
- Use appropriate date/time/currency formats
- Consider text expansion/contraction
- Apply culturally appropriate imagery
- Respect reading direction (LTR/RTL)
- Use native language for ALL elements:
  - Headings and subheadings
  - Button text and CTAs
  - Placeholder text
  - Alt text for images
  - Navigation items
  - Form labels";
		
		if ( isset( $language_guides[ $language ] ) ) {
			return $language_guides[ $language ] . "\n\n" . $general_language_instruction;
		}
		
		return str_replace( '{$language}', strtoupper( $language ), $general_language_instruction );
	}

	/**
	 * Get responsive design instructions.
	 *
	 * @since 1.0.0
	 * @return string Responsive instructions.
	 */
	private function get_responsive_instructions() {
		return "RESPONSIVE DESIGN REQUIREMENTS:
Mobile First Approach:
- Design for mobile, enhance for desktop
- Touch-friendly tap targets (minimum 44px)
- Readable font sizes (minimum 16px on mobile)

Breakpoints:
- Mobile: < 600px (single column)
- Tablet: 600px - 960px (2 columns max)
- Desktop: > 960px (full layout)

Column Behavior:
- Columns auto-stack on mobile by default
- Use percentage widths for flexibility
- Equal columns: 50%, 33.33%, 25%
- Asymmetric: 66.66% + 33.33%, etc.

Spacing Adjustments:
- Reduce padding on mobile (50% of desktop)
- Adjust font sizes responsively
- Scale images appropriately

Hidden Elements:
- Use utility classes for responsive hiding
- Consider mobile-specific content

Testing Considerations:
- Ensure readability at all sizes
- Check touch target sizes
- Verify image loading performance
- Test text wrapping and overflow";
	}

	/**
	 * Get accessibility instructions.
	 *
	 * @since 1.0.0
	 * @return string Accessibility instructions.
	 */
	private function get_accessibility_instructions() {
		return "ACCESSIBILITY REQUIREMENTS:
Semantic Structure:
- Use proper heading hierarchy (never skip levels)
- Choose semantic blocks (quote vs paragraph)
- Maintain logical reading order
- Use lists for grouped items

Color & Contrast:
- WCAG AA compliance (4.5:1 for normal text)
- Don't rely on color alone for meaning
- Test with color blindness simulators
- Ensure focus indicators are visible

Images & Media:
- Descriptive alt text for all images
- Avoid 'image of' or 'picture of' in alt text
- Empty alt=\"\" for decorative images
- Captions for complex images

Interactive Elements:
- Descriptive link text (not 'click here')
- Keyboard accessible (tab order)
- Focus indicators for all interactive elements
- Sufficient touch target size (44x44px)

Screen Reader Support:
- ARIA labels where helpful
- Landmark roles for major sections
- Skip links for navigation
- Announce dynamic content changes

Forms:
- Associate labels with inputs
- Group related fields with fieldsets
- Provide clear error messages
- Include helpful placeholder text";
	}

	/**
	 * Get simplified system prompt.
	 *
	 * @since 1.0.0
	 * @return string Simplified system prompt.
	 */
	private function get_simplified_system_prompt() {
		return "You are an expert WordPress Gutenberg layout designer. Generate valid Gutenberg block markup based entirely on the user's description.

CRITICAL RULES:
1. Output ONLY valid Gutenberg block markup - no explanations or wrapper text
2. Start immediately with <!-- wp: and end with -->
3. Follow the user's description exactly for all design choices
4. Use proper block syntax: <!-- wp:block-name {\"attribute\":\"value\"} -->
5. Close all blocks properly: <!-- /wp:block-name -->

IMPORTANT: The user's description is your guide. They will specify:
- Colors (hex codes, gradients, named colors)
- Layout structure (columns, grids, sidebars)
- Spacing and density
- Typography and font sizes
- Design style (modern, minimal, bold, etc.)
- Any other visual preferences

BASIC STRUCTURE:
1. Use semantic HTML through appropriate blocks
2. Maintain proper heading hierarchy (h1 → h2 → h3)
3. Ensure responsive design with stackable columns
4. Include accessibility features (alt text, ARIA labels)

IMAGES:
Use placehold.co for placeholder images:
{\"url\":\"https://placehold.co/600x400/007cba/ffffff?text=Description\"}

Trust the user's description completely. They know what they want.";
	}

	/**
	 * Get base system prompt - COMPACT VERSION.
	 *
	 * @since 1.0.0
	 * @return string Base system prompt.
	 */
	private function get_base_system_prompt_compact() {
		return "Generate valid Gutenberg block markup based on the user's description.

ESSENTIAL RULES:
1. Output ONLY block markup starting with <!-- wp: and ending with -->
2. Use proper JSON syntax: {\"attribute\":\"value\"}
3. Close all blocks: <!-- /wp:block-name -->
4. For placeholder images: https://placehold.co/600x400/007cba/ffffff?text=Description

IMPORTANT: Follow the user's instructions precisely. If they specify colors, gradients, layouts, or styles, use exactly what they request.";
	}

	/**
	 * Get example blocks.
	 *
	 * @since 1.0.0
	 * @return string Example blocks.
	 */
	private function get_example_blocks() {
		return "BLOCK SYNTAX EXAMPLES:

1. Cover block with gradient: {\"gradient\":\"linear-gradient(135deg,#667eea 0%,#764ba2 100%)\"}
2. Custom background color: {\"customBackgroundColor\":\"#001F3F\"}
3. Spacer heights: {\"height\":\"40px\"}
4. Column widths: {\"width\":\"33.33%\"}
5. Font sizes: {\"fontSize\":\"large\"} or {\"fontSize\":\"x-large\"}
6. Text alignment: {\"textAlign\":\"center\"}
7. Button styles: {\"backgroundColor\":\"primary\",\"textColor\":\"white\"}
8. Image URLs: https://placehold.co/600x400/007cba/ffffff?text=Hero+Image";
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
				'instructions' => 'Create a complete landing page with: hero section with gradient background and CTAs, features grid (3 columns) with icons, about section with image and text, testimonials carousel placeholder, statistics/numbers section, and final call-to-action. Structure the page to guide visitors toward conversion with proper visual hierarchy.',
			),
			'blog_post' => array(
				'name' => __( 'Blog Post', 'layoutberg' ),
				'instructions' => 'Create a blog post layout with: large featured image with overlay title, post meta (date, author, category), content sections with proper h2/h3 headings, blockquotes for important points, image galleries where appropriate, pull quotes for emphasis, related posts section, and author bio box with avatar.',
			),
			'portfolio' => array(
				'name' => __( 'Portfolio', 'layoutberg' ),
				'instructions' => 'Create a portfolio showcase with: hero introduction with your name/brand, filter buttons placeholder, project grid (3 columns) with hover effects, each project showing image/title/category, modal or lightbox placeholder for project details, client testimonials section, skills or services overview, and contact call-to-action.',
			),
			'about_page' => array(
				'name' => __( 'About Page', 'layoutberg' ),
				'instructions' => 'Create an about page with: hero section with company/personal introduction, story timeline with milestones, mission/vision/values in columns, team members grid with photos and bios, achievements counter section, company culture image gallery, awards and certifications, and career opportunities CTA.',
			),
			'contact_page' => array(
				'name' => __( 'Contact Page', 'layoutberg' ),
				'instructions' => 'Create a contact page with: welcoming hero message, two-column layout (form + info), contact form placeholder with fields, contact methods (phone/email/address), business hours table, embedded map placeholder, FAQ accordion section, social media links, and emergency contact info if applicable.',
			),
			'services' => array(
				'name' => __( 'Services Page', 'layoutberg' ),
				'instructions' => 'Create a services page with: hero section explaining value proposition, services overview grid (3-4 items), detailed service sections with icons, process/workflow visualization, pricing packages comparison table, client success stories, trust badges and certifications, FAQ section, and consultation booking CTA.',
			),
			'product_showcase' => array(
				'name' => __( 'Product Showcase', 'layoutberg' ),
				'instructions' => 'Create a product showcase with: hero product image with key benefit, features list with icons (2 columns), benefits vs features comparison, technical specifications table, image gallery with zoom placeholder, customer reviews section, pricing options, shipping information, and add to cart/purchase CTA.',
			),
			'healthcare_landing' => array(
				'name' => __( 'Healthcare Landing Page', 'layoutberg' ),
				'instructions' => 'Create a healthcare landing page with: calming hero (blue/green gradient) with appointment CTA, services grid with medical icons, why choose us section with credentials, doctor/staff profiles with qualifications, patient testimonials with privacy consideration, insurance accepted logos, health resources section, contact forms for appointments, emergency contact prominently displayed.',
			),
			'saas_landing' => array(
				'name' => __( 'SaaS Landing Page', 'layoutberg' ),
				'instructions' => 'Create a SaaS landing page with: tech-inspired hero with product mockup, key features grid with animations placeholder, how it works (3-step process), integration partners logo cloud, pricing table with recommended plan, ROI calculator placeholder, customer success metrics, security compliance badges, API documentation link, and free trial signup.',
			),
			'restaurant_page' => array(
				'name' => __( 'Restaurant Page', 'layoutberg' ),
				'instructions' => 'Create a restaurant page with: appetizing hero with reservation CTA, featured dishes gallery, menu categories with prices, chef introduction with signature dish, ambiance photo gallery, customer reviews, location map with parking info, hours and special events, online ordering/delivery section, and newsletter signup for specials.',
			),
			'real_estate_listing' => array(
				'name' => __( 'Real Estate Listing', 'layoutberg' ),
				'instructions' => 'Create a real estate listing page with: hero image gallery with virtual tour CTA, property highlights (beds/baths/sqft), detailed description with features, room-by-room breakdown, neighborhood amenities map, nearby schools and transport, mortgage calculator placeholder, similar properties section, agent contact card, and schedule viewing form.',
			),
			'event_landing' => array(
				'name' => __( 'Event Landing Page', 'layoutberg' ),
				'instructions' => 'Create an event landing page with: exciting hero with countdown timer placeholder, event highlights/benefits, detailed schedule/agenda with times, speaker profiles grid with bios, venue information with directions, ticket tiers with early bird pricing, sponsor logos section, FAQ about the event, past event testimonials, and registration form.',
			),
			'nonprofit_campaign' => array(
				'name' => __( 'Nonprofit Campaign', 'layoutberg' ),
				'instructions' => 'Create a nonprofit campaign page with: emotional hero with immediate donation CTA, mission statement with impact video placeholder, impact statistics counters, success stories with beneficiary photos, how donations help breakdown, volunteer opportunities section, upcoming events calendar, transparency report link, partner organizations, and multiple donation amount options.',
			),
			'fitness_landing' => array(
				'name' => __( 'Fitness Landing Page', 'layoutberg' ),
				'instructions' => 'Create a fitness landing page with: high-energy hero with class signup CTA, class types grid with intensity levels, trainer profiles with specialties, transformation testimonials with before/after, class schedule table, membership tiers comparison, facility features gallery, nutrition tips section, mobile app download links, and free trial offer.',
			),
			'education_course' => array(
				'name' => __( 'Education Course Page', 'layoutberg' ),
				'instructions' => 'Create an education course page with: engaging hero with enrollment urgency, course overview with key outcomes, detailed curriculum modules, instructor bio with credentials, student success testimonials, learning format explanation, technical requirements, pricing with payment plans, money-back guarantee badge, and enrollment form with start dates.',
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
	 * Get prompt templates for common layouts.
	 *
	 * @since 1.0.0
	 * @return array Prompt templates.
	 */
	public function get_prompt_templates() {
		return array(
			'hero_section' => array(
				'name' => __( 'Hero Section', 'layoutberg' ),
				'prompt' => 'Create a hero section with gradient background, compelling headline, descriptive subheadline, and two call-to-action buttons (primary and secondary). Use cover block with centered content and 600px minimum height.',
			),
			'features_grid' => array(
				'name' => __( 'Features Grid', 'layoutberg' ),
				'prompt' => 'Create a 3-column features section with icon placeholders, feature titles, and descriptions. Add background color to cards and proper spacing between them. Include hover effect placeholders.',
			),
			'testimonials' => array(
				'name' => __( 'Testimonials', 'layoutberg' ),
				'prompt' => 'Create testimonials section with 3 customer reviews in columns. Include quote text, 5-star rating placeholder, customer photo, name, and company/role. Add quotation marks styling.',
			),
			'pricing_table' => array(
				'name' => __( 'Pricing Table', 'layoutberg' ),
				'prompt' => 'Create 3-column pricing table with plan names, prices (monthly/yearly toggle placeholder), feature lists with checkmarks, and CTA buttons. Highlight the recommended plan with different background.',
			),
			'team_section' => array(
				'name' => __( 'Team Section', 'layoutberg' ),
				'prompt' => 'Create team members section with 4 people in a grid. Include circular profile images, names, job titles, brief bios, and social media links placeholders. Add hover effects on images.',
			),
			'faq_section' => array(
				'name' => __( 'FAQ Section', 'layoutberg' ),
				'prompt' => 'Create FAQ section with 6 frequently asked questions. Use alternating background colors, clear question/answer separation, and expandable sections placeholder. Include contact CTA at bottom.',
			),
			'cta_section' => array(
				'name' => __( 'Call to Action', 'layoutberg' ),
				'prompt' => 'Create compelling CTA section with gradient background, powerful headline, supporting paragraph, and prominent button. Add urgency element like limited time offer. Center all content.',
			),
			'stats_section' => array(
				'name' => __( 'Statistics', 'layoutberg' ),
				'prompt' => 'Create statistics section with 4 key metrics in columns. Use large numbers with animation placeholder, descriptive labels, and icons. Add subtle background and proper spacing.',
			),
			'timeline_section' => array(
				'name' => __( 'Timeline', 'layoutberg' ),
				'prompt' => 'Create vertical timeline showing 5 milestones. Include years/dates, event titles, descriptions, and connecting line visual. Alternate left/right alignment for visual interest.',
			),
			'comparison_table' => array(
				'name' => __( 'Comparison Table', 'layoutberg' ),
				'prompt' => 'Create feature comparison table with 3 columns (products/plans). Include feature rows with checkmarks, X marks, and values. Highlight differences and add CTA buttons at bottom.',
			),
			'gallery_section' => array(
				'name' => __( 'Gallery', 'layoutberg' ),
				'prompt' => 'Create image gallery with 8 placeholder images in a 4-column grid. Include lightbox placeholder, image captions, and filter buttons by category. Add hover zoom effect.',
			),
			'logo_grid' => array(
				'name' => __( 'Logo Grid', 'layoutberg' ),
				'prompt' => 'Create client/partner logos section with "Trusted by Industry Leaders" heading. Display 8 company logos in grayscale with color on hover. Use 4-column grid with proper spacing.',
			),
			'process_steps' => array(
				'name' => __( 'Process Steps', 'layoutberg' ),
				'prompt' => 'Create process section with 4 numbered steps. Include step numbers in circles, titles, descriptions, and connecting arrows or lines. Use gradient background for active step.',
			),
			'contact_info' => array(
				'name' => __( 'Contact Information', 'layoutberg' ),
				'prompt' => 'Create contact section with 3 columns: address with map link, phone/email with icons, and business hours table. Add emergency contact if applicable. Use cards with shadows.',
			),
			'newsletter_signup' => array(
				'name' => __( 'Newsletter Signup', 'layoutberg' ),
				'prompt' => 'Create newsletter section with compelling heading, 3 benefit points with checkmarks, email input field placeholder, subscribe button, and privacy policy link. Use contrasting background.',
			),
			'social_proof' => array(
				'name' => __( 'Social Proof', 'layoutberg' ),
				'prompt' => 'Create social proof section combining customer count, rating stars, testimonial quotes, media mentions logos, and awards/certifications badges. Use multiple columns for impact.',
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
		// Check prompt length
		if ( strlen( $prompt ) < 10 ) {
			return new \WP_Error( 
				'prompt_too_short', 
				__( 'Please provide a more detailed description of the layout you want. Try including what type of content, style, or purpose you have in mind.', 'layoutberg' ) 
			);
		}
		
		if ( strlen( $prompt ) > 2000 ) {
			return new \WP_Error( 
				'prompt_too_long', 
				__( 'Please keep your description under 2000 characters for optimal processing.', 'layoutberg' ) 
			);
		}
		
		// Check for conflicting instructions
		$conflicts = array(
			array( 'modern', 'classic' ),
			array( 'minimal', 'complex' ),
			array( 'colorful', 'monochrome' ),
			array( 'spacious', 'compact' ),
		);
		
		$prompt_lower = strtolower( $prompt );
		foreach ( $conflicts as $conflict_pair ) {
			$found = array();
			foreach ( $conflict_pair as $term ) {
				if ( strpos( $prompt_lower, $term ) !== false ) {
					$found[] = $term;
				}
			}
			
			if ( count( $found ) > 1 ) {
				return new \WP_Error( 
					'conflicting_instructions', 
					sprintf( 
						__( 'Your description contains conflicting terms: %s. Please choose one style direction.', 'layoutberg' ), 
						implode( ' and ', $found ) 
					) 
				);
			}
		}
		
		// Check for inappropriate content
		$inappropriate_terms = apply_filters( 'layoutberg_inappropriate_terms', array() );
		foreach ( $inappropriate_terms as $term ) {
			if ( stripos( $prompt, $term ) !== false ) {
				return new \WP_Error( 
					'inappropriate_content', 
					__( 'Your prompt contains content that cannot be processed. Please revise your request.', 'layoutberg' ) 
				);
			}
		}
		
		// Check for overly technical requests
		$technical_patterns = array(
			'/\b(custom\s+)?PHP\b/i',
			'/\b(custom\s+)?JavaScript\b/i',
			'/\bSQL\s+query\b/i',
			'/\bAPI\s+endpoint\b/i',
			'/\bwebhook/i',
			'/\bdatabase\s+connection\b/i',
			'/\bserver[\s-]side\b/i',
		);
		
		foreach ( $technical_patterns as $pattern ) {
			if ( preg_match( $pattern, $prompt ) ) {
				return new \WP_Error( 
					'too_technical', 
					__( 'Your request includes technical features beyond layout generation. Please focus on visual design and content structure.', 'layoutberg' ) 
				);
			}
		}
		
		// Check for common problematic requests
		$problematic_patterns = array(
			'/\bembed\s+(custom\s+)?script/i' => __( 'Cannot embed custom scripts. Try describing the visual content you want instead.', 'layoutberg' ),
			'/\bcustom\s+plugin/i' => __( 'Cannot create custom plugins. Focus on layout and content structure.', 'layoutberg' ),
			'/\bform\s+submission\s+handling/i' => __( 'Cannot handle form submissions. Try describing the form layout instead.', 'layoutberg' ),
			'/\bdynamic\s+content\s+from\s+database/i' => __( 'Cannot pull dynamic content. Focus on static layout structure.', 'layoutberg' ),
			'/\buser\s+authentication/i' => __( 'Cannot implement authentication. Describe the visual layout you need.', 'layoutberg' ),
			'/\bpayment\s+processing/i' => __( 'Cannot process payments. Describe the checkout page layout instead.', 'layoutberg' ),
		);
		
		foreach ( $problematic_patterns as $pattern => $message ) {
			if ( preg_match( $pattern, $prompt ) ) {
				return new \WP_Error( 'problematic_request', $message );
			}
		}
		
		// Check for too many columns
		if ( preg_match( '/\b(\d+)\s*columns?\b/i', $prompt, $matches ) ) {
			$columns = intval( $matches[1] );
			if ( $columns > 6 ) {
				return new \WP_Error( 
					'too_many_columns', 
					__( 'Too many columns requested. For better mobile experience, please use 6 or fewer columns.', 'layoutberg' ) 
				);
			}
		}
		
		// Validate color formats if specified
		if ( preg_match_all( '/#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})\b/', $prompt, $matches ) ) {
			foreach ( $matches[0] as $color ) {
				if ( ! $this->is_valid_hex_color( $color ) ) {
					return new \WP_Error( 
						'invalid_color', 
						sprintf( __( 'Invalid color format: %s. Use format like #FF0000 or #F00.', 'layoutberg' ), $color ) 
					);
				}
			}
		}
		
		return true;
	}

	/**
	 * Check if a string is a valid hex color.
	 *
	 * @since 1.0.0
	 * @param string $color Color string to validate.
	 * @return bool True if valid hex color.
	 */
	private function is_valid_hex_color( $color ) {
		return preg_match( '/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $color );
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
		
		// Check if prompt mentions specific layout types
		$layout_keywords = array(
			'hero' => 'hero section',
			'landing' => 'landing page',
			'about' => 'about page',
			'contact' => 'contact page',
			'features' => 'features section',
			'testimonials' => 'testimonials section',
			'pricing' => 'pricing table',
			'portfolio' => 'portfolio gallery',
			'blog' => 'blog layout',
			'team' => 'team section',
		);
		
		$mentioned_layouts = array();
		foreach ( $layout_keywords as $keyword => $full_name ) {
			if ( strpos( $prompt_lower, $keyword ) !== false ) {
				$mentioned_layouts[] = $full_name;
			}
		}
		
		// Suggest being more specific about layout type
		if ( empty( $mentioned_layouts ) && strlen( $prompt ) < 50 ) {
			$suggestions[] = array(
				'type' => 'be_specific',
				'message' => __( 'Try being more specific about the type of layout you want (e.g., "hero section", "pricing table", "contact page").', 'layoutberg' ),
				'examples' => array(
					'Create a modern hero section with gradient background',
					'Build a 3-column pricing table with features',
					'Design a contact page with form and map',
				),
			);
		}
		
		// Check for style preferences
		$style_keywords = array( 'modern', 'minimal', 'corporate', 'creative', 'professional', 'elegant', 'bold', 'playful' );
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
				'message' => __( 'Consider mentioning a design style preference to get more targeted results.', 'layoutberg' ),
				'options' => array( 'modern', 'minimal', 'corporate', 'creative', 'professional', 'elegant' ),
			);
		}
		
		// Check for color preferences
		$has_colors = preg_match( '/(color|colour|blue|green|red|purple|orange|pink|dark|light|bright|muted|gradient|#[a-fA-F0-9]{3,6})/i', $prompt );
		
		if ( ! $has_colors && strlen( $prompt ) > 40 ) {
			$suggestions[] = array(
				'type' => 'add_colors',
				'message' => __( 'You could mention color preferences or themes to get more personalized results.', 'layoutberg' ),
				'examples' => array(
					'Use blue and white colors',
					'Create with vibrant gradient background',
					'Apply dark theme with neon accents',
				),
			);
		}
		
		// Check for purpose/audience
		$purpose_keywords = array( 'business', 'personal', 'portfolio', 'blog', 'shop', 'nonprofit', 'startup', 'agency' );
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
				'examples' => array(
					'for a tech startup',
					'for a personal portfolio',
					'for a small business',
				),
			);
		}
		
		// Check for responsive/mobile mentions
		if ( ! preg_match( '/\b(mobile|responsive|tablet|desktop)\b/i', $prompt ) && strlen( $prompt ) > 60 ) {
			$suggestions[] = array(
				'type' => 'responsive_reminder',
				'message' => __( 'All layouts are mobile-responsive by default, but you can specify special mobile considerations if needed.', 'layoutberg' ),
			);
		}
		
		// Suggest using our templates for very short inputs
		if ( strlen( $prompt ) < 20 ) {
			$suggestions[] = array(
				'type' => 'use_templates',
				'message' => __( 'For quick starts, try one of our pre-designed templates or be more descriptive.', 'layoutberg' ),
				'templates' => array_slice( $this->get_prompt_templates(), 0, 3 ),
			);
		}
		
		// Advanced suggestions for longer prompts
		if ( strlen( $prompt ) > 100 ) {
			// Check for structure organization
			if ( ! preg_match( '/\b(section|first|then|next|finally|top|middle|bottom)\b/i', $prompt ) ) {
				$suggestions[] = array(
					'type' => 'add_structure',
					'message' => __( 'Consider organizing your description by sections (e.g., "First, a hero section... Then, features grid...").', 'layoutberg' ),
				);
			}
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
		$scores = array();
		
		// Keywords for each template with weights
		$template_keywords = array(
			'hero_section' => array( 
				'keywords' => array( 'hero' => 3, 'banner' => 2, 'header' => 2, 'main' => 1, 'top' => 1, 'welcome' => 2 ),
				'weight' => 1.2,
			),
			'features_grid' => array( 
				'keywords' => array( 'features' => 3, 'benefits' => 2, 'services' => 2, 'advantages' => 2, 'grid' => 1, 'cards' => 1 ),
				'weight' => 1.1,
			),
			'testimonials' => array( 
				'keywords' => array( 'testimonials' => 3, 'reviews' => 3, 'feedback' => 2, 'customers' => 2, 'quotes' => 1, 'clients' => 2 ),
				'weight' => 1.0,
			),
			'pricing_table' => array( 
				'keywords' => array( 'pricing' => 3, 'plans' => 3, 'packages' => 2, 'cost' => 2, 'price' => 2, 'tiers' => 2 ),
				'weight' => 1.1,
			),
			'team_section' => array( 
				'keywords' => array( 'team' => 3, 'staff' => 3, 'members' => 2, 'people' => 1, 'about us' => 2, 'who' => 1 ),
				'weight' => 1.0,
			),
			'faq_section' => array( 
				'keywords' => array( 'faq' => 3, 'questions' => 3, 'help' => 2, 'support' => 2, 'answers' => 2, 'how' => 1 ),
				'weight' => 1.0,
			),
			'cta_section' => array( 
				'keywords' => array( 'call to action' => 3, 'cta' => 3, 'signup' => 2, 'register' => 2, 'contact' => 2, 'get started' => 2 ),
				'weight' => 1.1,
			),
			'stats_section' => array( 
				'keywords' => array( 'statistics' => 3, 'stats' => 3, 'numbers' => 2, 'metrics' => 2, 'data' => 1, 'results' => 2 ),
				'weight' => 1.0,
			),
			'gallery_section' => array( 
				'keywords' => array( 'gallery' => 3, 'images' => 2, 'photos' => 2, 'portfolio' => 2, 'showcase' => 2, 'work' => 1 ),
				'weight' => 1.0,
			),
			'newsletter_signup' => array( 
				'keywords' => array( 'newsletter' => 3, 'subscribe' => 3, 'email' => 2, 'updates' => 2, 'signup' => 2, 'list' => 1 ),
				'weight' => 1.0,
			),
		);
		
		// Calculate relevance scores
		foreach ( $template_keywords as $template_key => $config ) {
			$score = 0;
			foreach ( $config['keywords'] as $keyword => $weight ) {
				if ( strpos( $prompt_lower, $keyword ) !== false ) {
					$score += $weight;
				}
			}
			
			if ( $score > 0 ) {
				$scores[ $template_key ] = $score * $config['weight'];
			}
		}
		
		// Sort by score and get top matches
		arsort( $scores );
		
		foreach ( array_keys( $scores ) as $template_key ) {
			if ( isset( $templates[ $template_key ] ) ) {
				$suggestions[] = $templates[ $template_key ];
			}
		}
		
		// If no specific matches, suggest popular templates based on prompt length
		if ( empty( $suggestions ) ) {
			if ( strlen( $prompt ) < 30 ) {
				// Short prompt - suggest most common
				$popular_templates = array( 'hero_section', 'features_grid', 'cta_section' );
			} elseif ( strpos( $prompt_lower, 'page' ) !== false ) {
				// Mentions "page" - suggest full page layouts
				$popular_templates = array( 'landing_page', 'about_page', 'contact_page' );
			} else {
				// General suggestions
				$popular_templates = array( 'hero_section', 'features_grid', 'testimonials' );
			}
			
			foreach ( $popular_templates as $template_key ) {
				if ( isset( $this->layout_templates[ $template_key ] ) ) {
					$suggestions[] = $this->layout_templates[ $template_key ];
				} elseif ( isset( $templates[ $template_key ] ) ) {
					$suggestions[] = $templates[ $template_key ];
				}
			}
		}
		
		return array_slice( $suggestions, 0, 3 ); // Return max 3 suggestions
	}

	/**
	 * Get friendly error message for validation errors.
	 *
	 * @since 1.0.0
	 * @param array $errors Array of error codes.
	 * @return string Friendly error message.
	 */
	private function get_friendly_error_message( $errors ) {
		$messages = array();
		
		$error_messages = array(
			'conflicting_styles' => __( 'Your description contains conflicting style preferences. Please choose either modern or classic, not both.', 'layoutberg' ),
			'too_many_columns' => __( 'Too many columns requested. For optimal mobile experience, use 6 or fewer columns.', 'layoutberg' ),
			'invalid_color' => __( 'One or more color codes are invalid. Use format like #FF0000 or #F00.', 'layoutberg' ),
		);
		
		foreach ( $errors as $error ) {
			if ( isset( $error_messages[ $error ] ) ) {
				$messages[] = $error_messages[ $error ];
			}
		}
		
		return implode( ' ', $messages );
	}
}