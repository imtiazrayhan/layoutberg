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
	 * Build enhanced system prompt for layout generation.
	 *
	 * @since 1.0.0
	 * @param array $options Generation options.
	 * @return string System prompt.
	 */
	public function build_system_prompt( $options = array() ) {
		// Start with critical instructions
		$prompt = $this->get_critical_wordpress_instructions();
		
		// Add validation rules
		$prompt .= "\n\n" . $this->get_validation_rules();
		
		// Add simple structure examples
		$prompt .= "\n\n" . $this->get_simple_structure_examples();
		
		// Add common pitfalls to avoid
		$prompt .= "\n\n" . $this->get_common_pitfalls();
		
		// Add comprehensive block examples
		$prompt .= "\n\n" . $this->get_example_blocks();
		
		// Add style-specific instructions if provided
		if ( isset( $options['style'] ) ) {
			$prompt .= "\n\nSTYLE: Create a " . $options['style'] . " design style.";
			
			switch ( $options['style'] ) {
				case 'modern':
					$prompt .= " Use clean lines, plenty of whitespace, sans-serif fonts, and minimalist design.";
					break;
				case 'classic':
					$prompt .= " Use traditional layouts, serif fonts, formal structure, and conservative design.";
					break;
				case 'creative':
					$prompt .= " Use bold colors, unique layouts, creative typography, and artistic elements.";
					break;
			}
		}
		
		// Add layout-specific instructions if provided
		if ( isset( $options['layout'] ) ) {
			$prompt .= "\n\nLAYOUT: Use " . $options['layout'] . " layout.";
			
			switch ( $options['layout'] ) {
				case 'single-column':
					$prompt .= " Center all content in a single column with maximum width.";
					break;
				case 'two-column':
					$prompt .= " Use two-column layouts for content sections where appropriate.";
					break;
				case 'grid':
					$prompt .= " Use grid layouts with columns for features, services, or gallery items.";
					break;
				case 'asymmetric':
					$prompt .= " Use asymmetric layouts with varied column widths and creative arrangements.";
					break;
			}
		}
		
		// Add pattern library reference
		$prompt .= "\n\nPATTERN LIBRARY: You have access to these pre-built patterns: " . implode( ', ', array_keys( $this->pattern_library ) );
		$prompt .= "\nUse these patterns as templates when appropriate, adapting content to match user requirements.";
		
		// Add user instruction priority
		$prompt .= "\n\nUSER INSTRUCTIONS: Follow the user's description exactly. They specify what sections to include and in what order.";
		$prompt .= "\nGenerate ONLY the requested sections - no more, no less.";
		
		return $prompt;
	}

	/**
	 * Get critical WordPress instructions.
	 *
	 * @since 1.0.0
	 * @return string Critical instructions.
	 */
	private function get_critical_wordpress_instructions() {
		return "You are a WordPress Gutenberg block expert. Generate ONLY valid Gutenberg blocks.

CRITICAL RULES TO PREVENT ERRORS:
1. Output ONLY block comments and content - no explanations
2. Start with <!-- wp: and end with -->
3. ALWAYS close blocks: <!-- /wp:block-name -->
4. Use SIMPLE JSON - avoid complex nested attributes
5. NO excessive nesting - maximum 3 levels deep

VALID BLOCK STRUCTURE:
<!-- wp:blockname {\"attribute\":\"value\"} -->
content here
<!-- /wp:blockname -->

ATTRIBUTE RULES:
- Use double quotes for JSON: {\"align\":\"wide\"}
- No single quotes or backticks
- No line breaks inside JSON
- Keep attributes simple and flat

SPACING WITH CSS VARIABLES:
- Use: \"var:preset|spacing|40\" (NOT var(--wp--preset--spacing--40))
- Common values: 20, 30, 40, 50, 60, 80
- Apply in style object: {\"style\":{\"spacing\":{\"padding\":{\"top\":\"var:preset|spacing|40\"}}}}";
	}

	/**
	 * Get validation rules to prevent errors.
	 *
	 * @since 1.0.0
	 * @return string Validation rules.
	 */
	private function get_validation_rules() {
		return "RULES TO PREVENT INVALID CONTENT:

1. BLOCK NAMES - Use exact core block names:
   - heading (not headers or title)
   - paragraph (not text or para)
   - columns and column (not cols)
   - buttons and button (not btn)
   - image (not img or picture)
   - group (not div or section)
   - cover (not hero or banner)
   - separator (not divider)
   - spacer (not space or gap)

2. ATTRIBUTE FORMAT:
   CORRECT: {\"align\":\"center\",\"fontSize\":\"large\"}
   WRONG: {'align': 'center'} or {align: \"center\"}

3. STYLE OBJECT FORMAT:
   CORRECT: {\"style\":{\"spacing\":{\"padding\":{\"top\":\"20px\"}}}}
   WRONG: {\"style\":\"padding-top:20px\"}

4. CLASS NAMES:
   - Always include base class: class=\"wp-block-blockname\"
   - Add modifier classes: has-text-align-center
   - Color classes: has-white-color has-text-color

5. COMMON ATTRIBUTES:
   - align: \"left\", \"center\", \"right\", \"wide\", \"full\"
   - textAlign: \"left\", \"center\", \"right\"
   - fontSize: \"small\", \"medium\", \"large\", \"x-large\"
   - backgroundColor/textColor: color slug only";
	}

	/**
	 * Get simple structure examples.
	 *
	 * @since 1.0.0
	 * @return string Simple examples.
	 */
	private function get_simple_structure_examples() {
		return "SIMPLE STRUCTURE EXAMPLES (AVOID OVER-NESTING):

1. BASIC SECTION - Just one group wrapper:
<!-- wp:group {\"align\":\"wide\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"var:preset|spacing|80\",\"bottom\":\"var:preset|spacing|80\"}}}} -->
<div class=\"wp-block-group alignwide\" style=\"padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)\">
    <!-- wp:heading {\"textAlign\":\"center\"} -->
    <h2 class=\"wp-block-heading has-text-align-center\">Title</h2>
    <!-- /wp:heading -->
    
    <!-- wp:paragraph {\"align\":\"center\"} -->
    <p class=\"has-text-align-center\">Description</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

2. COLUMNS WITHOUT EXTRA GROUPS:
<!-- wp:columns -->
<div class=\"wp-block-columns\">
    <!-- wp:column -->
    <div class=\"wp-block-column\">
        <!-- wp:heading -->
        <h3 class=\"wp-block-heading\">Feature</h3>
        <!-- /wp:heading -->
        
        <!-- wp:paragraph -->
        <p>Description</p>
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
</div>
<!-- /wp:columns -->

3. HERO WITH COVER (no nested groups):
<!-- wp:cover {\"url\":\"image.jpg\",\"dimRatio\":50,\"align\":\"full\"} -->
<div class=\"wp-block-cover alignfull\">
    <span aria-hidden=\"true\" class=\"wp-block-cover__background has-background-dim\"></span>
    <img class=\"wp-block-cover__image-background\" alt=\"\" src=\"image.jpg\" data-object-fit=\"cover\"/>
    <div class=\"wp-block-cover__inner-container\">
        <!-- wp:heading {\"textAlign\":\"center\",\"level\":1} -->
        <h1 class=\"wp-block-heading has-text-align-center\">Welcome</h1>
        <!-- /wp:heading -->
    </div>
</div>
<!-- /wp:cover -->

4. SIMPLE BUTTON:
<!-- wp:buttons {\"layout\":{\"type\":\"flex\",\"justifyContent\":\"center\"}} -->
<div class=\"wp-block-buttons\">
    <!-- wp:button -->
    <div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\">Click Here</a></div>
    <!-- /wp:button -->
</div>
<!-- /wp:buttons -->";
	}

	/**
	 * Get common pitfalls to avoid.
	 *
	 * @since 1.0.0
	 * @return string Common pitfalls.
	 */
	private function get_common_pitfalls() {
		return "COMMON PITFALLS THAT CAUSE INVALID CONTENT:

1. DON'T OVER-NEST GROUPS:
   BAD: group > group > group > content
   GOOD: group > content or group > columns > column > content

2. DON'T USE INLINE STYLES INCORRECTLY:
   BAD: style=\"padding:40px;margin:20px;\"
   GOOD: style=\"padding:40px;margin:20px\"

3. DON'T MIX VAR FORMATS:
   BAD: var(--wp--preset--spacing--40)
   GOOD: var:preset|spacing|40 (in JSON)
   GOOD: var(--wp--preset--spacing--40) (in style attribute)

4. DON'T FORGET REQUIRED CLASSES:
   - Every block needs wp-block-[blockname]
   - Alignment needs both attribute AND class
   - Color needs both attribute AND class

5. DON'T USE COMPLEX NESTED JSON:
   Keep attributes flat and simple

6. DON'T FORGET THE INNER CONTAINER:
   - Cover blocks need wp-block-cover__inner-container
   - Media & Text blocks have specific structure

7. IMAGE URLS:
   - Use full URLs: https://example.com/image.jpg
   - Not relative paths: /images/photo.jpg

8. PROPER CLOSING:
   - Every <!-- wp:block --> needs <!-- /wp:block -->
   - Check the block name matches exactly";
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
		
		// Add structural guidance
		$enhanced .= "\n\nIMPORTANT: Keep structure SIMPLE. Use only ONE group wrapper per section. Avoid nesting groups inside groups unless absolutely necessary.";
		
		// Add specific instructions for common sections
		if ( preg_match( '/hero/i', $prompt ) ) {
			$enhanced .= "\n\nHero Section: Use a single cover block with background image, no extra group wrappers inside.";
		}
		
		if ( preg_match( '/features?/i', $prompt ) ) {
			$enhanced .= "\n\nFeatures: Use columns block directly, no group wrapper needed for each column.";
		}
		
		if ( preg_match( '/pricing/i', $prompt ) ) {
			$enhanced .= "\n\nPricing: Use columns with simple group blocks for cards, avoid deep nesting.";
		}
		
		// Add validation reminder
		$enhanced .= "\n\nREMEMBER: Every block must have proper closing tags and valid JSON attributes.";
		
		return $enhanced;
	}

	/**
	 * Get simplified system prompt.
	 *
	 * @since 1.0.0
	 * @return string Simplified prompt.
	 */
	private function get_simplified_system_prompt() {
		return "Generate WordPress Gutenberg blocks. Output ONLY blocks, no explanations.

CRITICAL RULES:
1. Start with <!-- wp: and end with -->
2. Close ALL blocks: <!-- /wp:blockname -->
3. Use simple JSON: {\"attribute\":\"value\"}
4. Maximum 3 levels of nesting
5. Include required classes

STRUCTURE:
<!-- wp:blockname {\"attribute\":\"value\"} -->
<div class=\"wp-block-blockname\">
    content
</div>
<!-- /wp:blockname -->

Follow user instructions for sections and layout.";
	}

	/**
	 * Initialize pattern library with simpler patterns.
	 *
	 * @since 1.0.0
	 */
	private function init_pattern_library() {
		$this->pattern_library = array(
			'hero_cover' => '<!-- wp:cover {\"url\":\"https://images.unsplash.com/photo-1517180102446-f3ece451e9d8\",\"dimRatio\":50,\"minHeight\":600,\"align\":\"full\"} -->
<div class="wp-block-cover alignfull" style="min-height:600px"><img class="wp-block-cover__image-background" alt="" src="https://images.unsplash.com/photo-1517180102446-f3ece451e9d8" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {\"textAlign\":\"center\",\"level\":1,\"textColor\":\"white\",\"fontSize\":\"huge\"} -->
<h1 class="wp-block-heading has-text-align-center has-white-color has-text-color has-huge-font-size">Welcome to Our Service</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {\"align\":\"center\",\"textColor\":\"white\",\"fontSize\":\"large\"} -->
<p class="has-text-align-center has-white-color has-text-color has-large-font-size">Transform your business with our solutions</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {\"layout\":{\"type\":\"flex\",\"justifyContent\":\"center\"}} -->
<div class="wp-block-buttons"><!-- wp:button {\"backgroundColor\":\"white\",\"textColor\":\"black\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"15px\",\"bottom\":\"15px\",\"left\":\"40px\",\"right\":\"40px\"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-black-color has-white-background-color has-text-color has-background wp-element-button" style="padding-top:15px;padding-right:40px;padding-bottom:15px;padding-left:40px">Get Started</a></div>
<!-- /wp:button -->

<!-- wp:button {\"textColor\":\"white\",\"className\":\"is-style-outline\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"15px\",\"bottom\":\"15px\",\"left\":\"40px\",\"right\":\"40px\"}}}} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-text-color wp-element-button" style="padding-top:15px;padding-right:40px;padding-bottom:15px;padding-left:40px">Learn More</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover -->',

			'features_columns' => '<!-- wp:group {\"align\":\"wide\"} -->
<div class="wp-block-group alignwide"><!-- wp:heading {\"textAlign\":\"center\"} -->
<h2 class="wp-block-heading has-text-align-center">Our Features</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {\"align\":\"center\"} -->
<p class="has-text-align-center">Discover what makes us different</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {\"height\":\"40px\"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {\"align\":\"wide\"} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {\"width\":\"64px\",\"height\":\"64px\",\"sizeSlug\":\"large\",\"align\":\"center\"} -->
<figure class="wp-block-image aligncenter size-large is-resized"><img src="https://via.placeholder.com/64x64/0073aa/ffffff?text=1" alt="" style="width:64px;height:64px"/></figure>
<!-- /wp:image -->

<!-- wp:heading {\"textAlign\":\"center\",\"level\":3} -->
<h3 class="wp-block-heading has-text-align-center">Fast Performance</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {\"align\":\"center\"} -->
<p class="has-text-align-center">Lightning-fast load times across all devices.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {\"width\":\"64px\",\"height\":\"64px\",\"sizeSlug\":\"large\",\"align\":\"center\"} -->
<figure class="wp-block-image aligncenter size-large is-resized"><img src="https://via.placeholder.com/64x64/0073aa/ffffff?text=2" alt="" style="width:64px;height:64px"/></figure>
<!-- /wp:image -->

<!-- wp:heading {\"textAlign\":\"center\",\"level\":3} -->
<h3 class="wp-block-heading has-text-align-center">Secure & Reliable</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {\"align\":\"center\"} -->
<p class="has-text-align-center">Enterprise-grade security with 99.9% uptime.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {\"width\":\"64px\",\"height\":\"64px\",\"sizeSlug\":\"large\",\"align\":\"center\"} -->
<figure class="wp-block-image aligncenter size-large is-resized"><img src="https://via.placeholder.com/64x64/0073aa/ffffff?text=3" alt="" style="width:64px;height:64px"/></figure>
<!-- /wp:image -->

<!-- wp:heading {\"textAlign\":\"center\",\"level\":3} -->
<h3 class="wp-block-heading has-text-align-center">24/7 Support</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {\"align\":\"center\"} -->
<p class="has-text-align-center">Round-the-clock customer support.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->',

			'testimonial_quote' => '<!-- wp:columns {\"align\":\"wide\"} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p>"This service completely transformed our business. The results speak for themselves!"</p>
<!-- /wp:paragraph --><cite>Sarah Johnson, CEO TechCorp</cite></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p>"Outstanding support and amazing features. Couldn\'t be happier."</p>
<!-- /wp:paragraph --><cite>Mike Chen, Founder StartupXYZ</cite></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->',

			'pricing_table' => '<!-- wp:table {\"hasFixedLayout\":false,\"className\":\"is-style-stripes\"} -->
<figure class="wp-block-table is-style-stripes"><table><thead><tr><th>Feature</th><th>Basic</th><th>Professional</th><th>Enterprise</th></tr></thead><tbody><tr><td>Users</td><td>Up to 5</td><td>Up to 25</td><td>Unlimited</td></tr><tr><td>Storage</td><td>10 GB</td><td>100 GB</td><td>Unlimited</td></tr><tr><td>Support</td><td>Email</td><td>Email & Chat</td><td>24/7 Phone</td></tr><tr><td>Price</td><td><strong>$19/mo</strong></td><td><strong>$49/mo</strong></td><td><strong>Custom</strong></td></tr></tbody></table></figure>
<!-- /wp:table -->',

			'faq_details' => '<!-- wp:group {\"layout\":{\"type\":\"constrained\",\"contentSize\":\"800px\"}} -->
<div class="wp-block-group"><!-- wp:details -->
<details class="wp-block-details"><summary>What makes your service different from competitors?</summary><!-- wp:paragraph -->
<p>Our service stands out through our combination of cutting-edge technology, exceptional customer support, and proven results.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>How long does it take to get started?</summary><!-- wp:paragraph -->
<p>Getting started is quick and easy! Most clients are up and running within 24 hours of signing up.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Can I upgrade or downgrade my plan?</summary><!-- wp:paragraph -->
<p>Absolutely! You can upgrade or downgrade your plan at any moment through your account dashboard.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details --></div>
<!-- /wp:group -->',

			'cta_section' => '<!-- wp:group {\"align\":\"full\",\"backgroundColor\":\"black\",\"textColor\":\"white\"} -->
<div class="wp-block-group alignfull has-white-color has-black-background-color has-text-color has-background"><!-- wp:spacer {\"height\":\"60px\"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {\"textAlign\":\"center\",\"textColor\":\"white\"} -->
<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color">Ready to Get Started?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {\"align\":\"center\",\"textColor\":\"white\"} -->
<p class="has-text-align-center has-white-color has-text-color">Join thousands of satisfied customers today</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {\"height\":\"20px\"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:buttons {\"layout\":{\"type\":\"flex\",\"justifyContent\":\"center\"}} -->
<div class="wp-block-buttons"><!-- wp:button {\"backgroundColor\":\"white\",\"textColor\":\"black\"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-black-color has-white-background-color has-text-color has-background wp-element-button">Start Free Trial</a></div>
<!-- /wp:button -->

<!-- wp:button {\"textColor\":\"white\",\"className\":\"is-style-outline\"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-text-color wp-element-button">Contact Sales</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:spacer {\"height\":\"40px\"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->',
		);
	}

	/**
	 * Validate AI output for WordPress compatibility.
	 *
	 * @since 1.0.0
	 * @param string $output AI output to validate.
	 * @return array Validation results with fixed output if possible.
	 */
	public function validate_and_fix_output( $output ) {
		$issues = array();
		$fixed_output = $output;
		
		// Fix common JSON issues
		$fixed_output = $this->fix_json_attributes( $fixed_output );
		
		// Fix missing closing tags
		$fixed_output = $this->fix_missing_closing_tags( $fixed_output );
		
		// Fix class name issues
		$fixed_output = $this->fix_class_names( $fixed_output );
		
		// Check nesting depth
		$nesting_depth = $this->check_nesting_depth( $fixed_output );
		if ( $nesting_depth > 4 ) {
			$issues[] = 'Excessive nesting detected (depth: ' . $nesting_depth . ')';
		}
		
		// Validate block structure
		$structure_valid = $this->validate_block_structure( $fixed_output );
		if ( ! $structure_valid ) {
			$issues[] = 'Invalid block structure detected';
		}
		
		return array(
			'valid' => empty( $issues ),
			'issues' => $issues,
			'fixed_output' => $fixed_output,
		);
	}

	/**
	 * Fix common JSON attribute issues.
	 *
	 * @since 1.0.0
	 * @param string $output Block output.
	 * @return string Fixed output.
	 */
	private function fix_json_attributes( $output ) {
		// Fix single quotes in JSON
		$output = preg_replace_callback(
			'/<!--\s*wp:[a-z\/\-]+\s*({[^}]+})/i',
			function( $matches ) {
				$json = $matches[1];
				// Replace single quotes with double quotes
				$json = str_replace( "'", '"', $json );
				// Fix unquoted keys
				$json = preg_replace( '/([{,]\s*)([a-zA-Z_]+)(\s*:)/', '$1"$2"$3', $json );
				return str_replace( $matches[1], $json, $matches[0] );
			},
			$output
		);
		
		return $output;
	}

	/**
	 * Fix missing closing tags.
	 *
	 * @since 1.0.0
	 * @param string $output Block output.
	 * @return string Fixed output.
	 */
	private function fix_missing_closing_tags( $output ) {
		// Track opened blocks
		$open_blocks = array();
		
		// Find all opening blocks
		preg_match_all( '/<!--\s*wp:([a-z\-]+)(?:\s|{)/i', $output, $opens, PREG_OFFSET_CAPTURE );
		
		// Find all closing blocks
		preg_match_all( '/<!--\s*\/wp:([a-z\-]+)\s*-->/i', $output, $closes, PREG_OFFSET_CAPTURE );
		
		// Build stack of open blocks
		foreach ( $opens[1] as $open ) {
			$open_blocks[] = $open[0];
		}
		
		// Remove closed blocks from stack
		foreach ( $closes[1] as $close ) {
			$key = array_search( $close[0], $open_blocks );
			if ( $key !== false ) {
				unset( $open_blocks[ $key ] );
			}
		}
		
		// Add missing closing tags
		foreach ( array_reverse( $open_blocks ) as $block ) {
			$output .= "\n<!-- /wp:{$block} -->";
		}
		
		return $output;
	}

	/**
	 * Fix class name issues.
	 *
	 * @since 1.0.0
	 * @param string $output Block output.
	 * @return string Fixed output.
	 */
	private function fix_class_names( $output ) {
		// Ensure wp-block- prefix
		$output = preg_replace(
			'/<div class="([^"]*)"/',
			function( $matches ) {
				$classes = $matches[1];
				if ( strpos( $classes, 'wp-block-' ) === false ) {
					// Try to determine block type from context
					$classes = 'wp-block-group ' . $classes;
				}
				return '<div class="' . $classes . '"';
			},
			$output
		);
		
		return $output;
	}

	/**
	 * Check nesting depth.
	 *
	 * @since 1.0.0
	 * @param string $output Block output.
	 * @return int Maximum nesting depth.
	 */
	private function check_nesting_depth( $output ) {
		$max_depth = 0;
		$current_depth = 0;
		
		$lines = explode( "\n", $output );
		foreach ( $lines as $line ) {
			if ( preg_match( '/<!--\s*wp:([a-z\-]+)(?:\s|{)/i', $line ) ) {
				$current_depth++;
				$max_depth = max( $max_depth, $current_depth );
			} elseif ( preg_match( '/<!--\s*\/wp:([a-z\-]+)/i', $line ) ) {
				$current_depth--;
			}
		}
		
		return $max_depth;
	}

	/**
	 * Validate block structure.
	 *
	 * @since 1.0.0
	 * @param string $output Block output.
	 * @return bool True if valid.
	 */
	private function validate_block_structure( $output ) {
		// Check if starts with block comment
		if ( ! preg_match( '/^\s*<!--\s*wp:/', $output ) ) {
			return false;
		}
		
		// Check balanced tags
		preg_match_all( '/<!--\s*wp:([a-z\-]+)/i', $output, $opens );
		preg_match_all( '/<!--\s*\/wp:([a-z\-]+)/i', $output, $closes );
		
		return count( $opens[0] ) === count( $closes[0] );
	}

	/**
	 * Get example blocks.
	 *
	 * @since 1.0.0
	 * @return string Example blocks.
	 */
	private function get_example_blocks() {
		return "COMPREHENSIVE BLOCK EXAMPLES:

1. HEADING WITH COLORS:
<!-- wp:heading {\"textAlign\":\"center\",\"level\":2,\"textColor\":\"primary\",\"fontSize\":\"x-large\"} -->
<h2 class=\"wp-block-heading has-text-align-center has-primary-color has-text-color has-x-large-font-size\">Title</h2>
<!-- /wp:heading -->

2. PARAGRAPH WITH STYLES:
<!-- wp:paragraph {\"align\":\"center\",\"backgroundColor\":\"light-gray\",\"textColor\":\"black\",\"fontSize\":\"medium\"} -->
<p class=\"has-text-align-center has-black-color has-light-gray-background-color has-text-color has-background has-medium-font-size\">Text content here</p>
<!-- /wp:paragraph -->

3. BUTTONS WITH STYLES:
<!-- wp:buttons {\"layout\":{\"type\":\"flex\",\"justifyContent\":\"center\"}} -->
<div class=\"wp-block-buttons\"><!-- wp:button {\"backgroundColor\":\"primary\",\"textColor\":\"white\",\"style\":{\"spacing\":{\"padding\":{\"top\":\"12px\",\"bottom\":\"12px\",\"left\":\"24px\",\"right\":\"24px\"}}}} -->
<div class=\"wp-block-button\"><a class=\"wp-block-button__link has-white-color has-primary-background-color has-text-color has-background wp-element-button\" style=\"padding-top:12px;padding-right:24px;padding-bottom:12px;padding-left:24px\">Primary Button</a></div>
<!-- /wp:button -->

<!-- wp:button {\"textColor\":\"primary\",\"className\":\"is-style-outline\"} -->
<div class=\"wp-block-button is-style-outline\"><a class=\"wp-block-button__link has-primary-color has-text-color wp-element-button\">Outline Button</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

4. IMAGE WITH CAPTION:
<!-- wp:image {\"sizeSlug\":\"large\",\"linkDestination\":\"none\"} -->
<figure class=\"wp-block-image size-large\"><img src=\"https://images.unsplash.com/photo-1497366216548-37526070297c\" alt=\"Office workspace\"/><figcaption class=\"wp-element-caption\">Modern workspace environment</figcaption></figure>
<!-- /wp:image -->

5. COLUMNS WITH SPACING:
<!-- wp:columns {\"style\":{\"spacing\":{\"blockGap\":{\"top\":\"var:preset|spacing|40\",\"left\":\"var:preset|spacing|40\"}}}} -->
<div class=\"wp-block-columns\"><!-- wp:column {\"style\":{\"spacing\":{\"padding\":{\"top\":\"var:preset|spacing|30\",\"right\":\"var:preset|spacing|30\",\"bottom\":\"var:preset|spacing|30\",\"left\":\"var:preset|spacing|30\"}}}} -->
<div class=\"wp-block-column\" style=\"padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)\">
<!-- content -->
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

6. LIST WITH LIST ITEMS:
<!-- wp:list -->
<ul class=\"wp-block-list\"><!-- wp:list-item -->
<li>First item</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Second item</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Third item</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

7. PULLQUOTE:
<!-- wp:pullquote -->
<figure class=\"wp-block-pullquote\"><blockquote><p>Innovation distinguishes between a leader and a follower.</p><cite>Steve Jobs</cite></blockquote></figure>
<!-- /wp:pullquote -->

8. SEPARATOR:
<!-- wp:separator {\"className\":\"is-style-wide\"} -->
<hr class=\"wp-block-separator has-alpha-channel-opacity is-style-wide\"/>
<!-- /wp:separator -->

9. MEDIA & TEXT:
<!-- wp:media-text {\"mediaId\":1,\"mediaType\":\"image\"} -->
<div class=\"wp-block-media-text is-stacked-on-mobile\"><figure class=\"wp-block-media-text__media\"><img src=\"https://images.unsplash.com/photo-1551434678-e076c223a692\" alt=\"\"/></figure><div class=\"wp-block-media-text__content\"><!-- wp:heading -->
<h2 class=\"wp-block-heading\">Media Title</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Description text goes here.</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:media-text -->

10. DETAILS/ACCORDION:
<!-- wp:details -->
<details class=\"wp-block-details\"><summary>Click to expand</summary><!-- wp:paragraph -->
<p>Hidden content that appears when expanded.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->";
	}

	/**
	 * Initialize block examples.
	 *
	 * @since 1.0.0
	 */
	private function init_block_examples() {
		$this->block_examples = array(
			'heading' => '<!-- wp:heading {\"textAlign\":\"center\"} -->
<h2 class="wp-block-heading has-text-align-center">Title</h2>
<!-- /wp:heading -->',
			
			'paragraph' => '<!-- wp:paragraph {\"align\":\"center\"} -->
<p class="has-text-align-center">Content here</p>
<!-- /wp:paragraph -->',
			
			'button' => '<!-- wp:buttons {\"layout\":{\"type\":\"flex\",\"justifyContent\":\"center\"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Click Here</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->',
			
			'image' => '<!-- wp:image {\"sizeSlug\":\"large\"} -->
<figure class="wp-block-image size-large"><img src="https://images.unsplash.com/photo-1497366216548-37526070297c" alt="Description"/></figure>
<!-- /wp:image -->',
			
			'spacer' => '<!-- wp:spacer {\"height\":\"50px\"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->',
			
			'gallery' => '<!-- wp:gallery {\"columns\":3,\"linkTo\":\"none\"} -->
<figure class="wp-block-gallery has-nested-images columns-3 is-cropped"><!-- wp:image -->
<figure class="wp-block-image"><img src="https://images.unsplash.com/photo-1497366216548-37526070297c" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:image -->
<figure class="wp-block-image"><img src="https://images.unsplash.com/photo-1497366811353-6870744d04b2" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:image -->
<figure class="wp-block-image"><img src="https://images.unsplash.com/photo-1497366754035-f200968a6e72" alt=""/></figure>
<!-- /wp:image --></figure>
<!-- /wp:gallery -->',
			
			'social_links' => '<!-- wp:social-links {\"iconColor\":\"primary\",\"className\":\"is-style-logos-only\"} -->
<ul class="wp-block-social-links has-icon-color is-style-logos-only"><!-- wp:social-link {\"url\":\"https://facebook.com\",\"service\":\"facebook\"} /-->

<!-- wp:social-link {\"url\":\"https://twitter.com\",\"service\":\"twitter\"} /-->

<!-- wp:social-link {\"url\":\"https://instagram.com\",\"service\":\"instagram\"} /--></ul>
<!-- /wp:social-links -->',
			
			'details' => '<!-- wp:details -->
<details class="wp-block-details"><summary>Question or title</summary><!-- wp:paragraph -->
<p>Answer or content that appears when expanded.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->',
			
			'media_text' => '<!-- wp:media-text {\"mediaType\":\"image\"} -->
<div class="wp-block-media-text is-stacked-on-mobile"><figure class="wp-block-media-text__media"><img src="https://images.unsplash.com/photo-1551434678-e076c223a692" alt=""/></figure><div class="wp-block-media-text__content"><!-- wp:heading -->
<h2 class="wp-block-heading">Title</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Description text.</p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:media-text -->',
			
			'table' => '<!-- wp:table {\"className\":\"is-style-stripes\"} -->
<figure class="wp-block-table is-style-stripes"><table><thead><tr><th>Header 1</th><th>Header 2</th></tr></thead><tbody><tr><td>Cell 1</td><td>Cell 2</td></tr></tbody></table></figure>
<!-- /wp:table -->',
		);
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
				'instructions' => 'Create a landing page with these sections IN ORDER: 1) Hero with cover block, 2) Features in 3 columns, 3) Gallery with 6 images, 4) Pricing in 3 columns, 5) FAQ with details blocks, 6) Team in 3 columns, 7) Contact info, 8) Blog posts. Keep structure SIMPLE - one group wrapper per section maximum.',
			),
		);
	}

	// Remaining methods can stay the same...
	
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
				'prompt' => 'Create a hero using a single cover block with background image, centered text, and button. No extra group wrappers.',
			),
			'features_grid' => array(
				'name' => __( 'Features Grid', 'layoutberg' ),
				'prompt' => 'Create a features section with heading, then columns block with 3 columns. Each column has heading and paragraph only.',
			),
			'simple_section' => array(
				'name' => __( 'Simple Section', 'layoutberg' ),
				'prompt' => 'Create a section with one group wrapper containing heading and paragraph. Keep it simple.',
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
		if ( strlen( $prompt ) < 10 ) {
			return new \WP_Error( 
				'prompt_too_short', 
				__( 'Please provide more details about what you want to create.', 'layoutberg' ) 
			);
		}
		
		return true;
	}

	/**
	 * Estimate token count for a string.
	 *
	 * @since 1.0.0
	 * @param string $text Text to count tokens for.
	 * @return int Estimated token count.
	 */
	public function estimate_token_count( $text ) {
		// Rough estimate: ~4 characters = 1 token
		// This is a simplified estimation. Real tokenization is more complex.
		return intval( strlen( $text ) / 4 );
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
			),
			'gpt-4' => array(
				'total' => 8192,
				'max_completion' => 4096,
			),
			'gpt-4-turbo' => array(
				'total' => 128000,
				'max_completion' => 4096,
			),
		);
		
		return isset( $limits[ $model ] ) ? $limits[ $model ] : $limits['gpt-3.5-turbo'];
	}
}