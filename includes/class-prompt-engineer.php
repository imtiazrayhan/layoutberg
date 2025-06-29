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
	 * Style variations for prompts.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $style_variations = array();

	/**
	 * Layout variations for prompts.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $layout_variations = array();

	/**
	 * Color scheme variations.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $color_schemes = array();

	/**
	 * Typography variations.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $typography_variations = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init_style_variations();
		$this->init_layout_variations();
		$this->init_color_schemes();
		$this->init_typography_variations();
	}

	/**
	 * Build enhanced system prompt for layout generation.
	 *
	 * @since 1.0.0
	 * @param array $options Generation options.
	 * @return string System prompt.
	 */
	public function build_system_prompt( $options = array() ) {
		// Start with core instructions
		$prompt = $this->get_core_instructions();
		
		// Add dynamic style instructions based on options
		if ( isset( $options['style'] ) ) {
			$prompt .= "\n\n" . $this->get_style_instructions( $options['style'] );
		}
		
		// Add dynamic layout instructions based on options
		if ( isset( $options['layout'] ) ) {
			$prompt .= "\n\n" . $this->get_layout_instructions( $options['layout'] );
		}
		
		// Add variation instructions to prevent repetition
		$prompt .= "\n\n" . $this->get_variation_instructions();
		
		// Add user-specific context
		$prompt .= "\n\n" . $this->get_context_instructions( $options );
		
		// Add block examples for reference
		$prompt .= "\n\n## BLOCK REFERENCE EXAMPLES\n\n" . $this->get_example_blocks();
		
		return $prompt;
	}

	/**
	 * Get core instructions for block generation.
	 *
	 * @since 1.0.0
	 * @return string Core instructions.
	 */
	private function get_core_instructions() {
		return 'You are a WordPress Gutenberg block expert. Generate ONLY valid Gutenberg blocks that pass WordPress validation.

## OUTPUT RULES
1. Output ONLY block markup - no explanations, comments, or surrounding text
2. Every block MUST have matching opening/closing HTML comments
3. Use ONLY double quotes in JSON: {"attribute":"value"}
4. NEVER use single quotes or backticks in attributes
5. Escape special characters in content: &amp; &lt; &gt; &quot;

## BLOCK ANATOMY
<!-- wp:namespace/blockname {"attribute":"value","nested":{"key":"value"}} -->
<tag class="wp-block-namespace-blockname">content</tag>
<!-- /wp:namespace/blockname -->

## CRITICAL VALIDATION REQUIREMENTS

### IMAGES & MEDIA
- ALWAYS use absolute URLs:
  - Photos: https://images.unsplash.com/photo-[id]?w=[width]&h=[height]&fit=crop
  - Placeholders: https://placehold.co/[width]x[height]/[bg-color]/[text-color]?text=[text]
  - Icons: https://placehold.co/64x64/[color]/white?text=[symbol]
- NEVER use: image.jpg, ./images/, ../assets/, or any relative paths
- Required attributes: {"url":"https://...","alt":"descriptive text","id":1}

### COVER BLOCKS - COMPREHENSIVE RULES

Cover blocks support TWO methods:

#### Method 1: With Background Image
<!-- wp:cover {"url":"https://images.unsplash.com/photo-[id]","dimRatio":50,"minHeight":600,"align":"full"} -->
<div class="wp-block-cover alignfull" style="min-height:600px"><img class="wp-block-cover__image-background" alt="" src="https://images.unsplash.com/photo-[id]" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- inner blocks here -->
</div></div>
<!-- /wp:cover -->

Key image attributes:
- dimRatio: 0-100 (controls overlay darkness)
- When dimRatio is 50+: add has-background-dim-[number] class
- Always include: <img class="wp-block-cover__image-background" data-object-fit="cover"/>

#### Method 2: With Gradient
<!-- wp:cover {"gradient":"gradient-name","align":"full"} -->
<div class="wp-block-cover alignfull"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim has-background-gradient has-[gradient-name]-gradient-background"></span><div class="wp-block-cover__inner-container">
<!-- inner blocks here -->
</div></div>
<!-- /wp:cover -->

OR with custom gradient:
<!-- wp:cover {"customGradient":"linear-gradient(135deg,#667eea 0%,#764ba2 100%)","align":"full"} -->
<div class="wp-block-cover alignfull"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim has-background-gradient" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)"></span><div class="wp-block-cover__inner-container">
<!-- inner blocks here -->
</div></div>
<!-- /wp:cover -->

### REQUIRED CLASS PATTERNS
- Core blocks: class="wp-block-[blockname]"
- Variations: class="wp-block-[blockname] is-style-[style]"
- Alignment: class="wp-block-[blockname] has-text-align-[left|center|right]"
- Colors: class="has-[color]-color has-text-color" OR "has-[color]-background-color has-background"
- Custom classes: Can add multiple custom classes like "hero-section custom-style"

## WORDPRESS PRESETS

### GRADIENTS
Named gradients:
- vivid-cyan-blue-to-vivid-purple
- vivid-green-cyan-to-vivid-cyan-blue  
- light-green-cyan-to-vivid-green-cyan
- luminous-vivid-amber-to-luminous-vivid-orange
- luminous-vivid-orange-to-vivid-red
- very-light-gray-to-cyan-bluish-gray
- cool-to-warm-spectrum
- blush-light-purple
- blush-bordeaux
- luminous-dusk
- pale-ocean
- electric-grass
- midnight

Custom gradients:
- linear-gradient(135deg,#color1 0%,#color2 100%)
- radial-gradient(circle,#color1 0%,#color2 100%)

### COLORS
Named colors:
- white, black
- primary, secondary, tertiary, quaternary
- base, contrast, accent, base-2, contrast-2, contrast-3, accent-2, accent-3
- vivid-cyan-blue, vivid-green-cyan, vivid-purple
- pale-cyan-blue, pale-pink
- luminous-vivid-orange, luminous-vivid-amber
- light-green-cyan

### SPACING
Two methods supported:

1. CSS Variables:
   - JSON: {"padding":{"top":"var:preset|spacing|40"}}
   - Style: style="padding-top:var(--wp--preset--spacing--40)"
   - Scale: 20, 30, 40, 50, 60, 70, 80, 100

2. Direct Values:
   - JSON: {"padding":{"top":"15px","bottom":"15px","left":"40px","right":"40px"}}
   - Style: style="padding:15px 40px"

### TYPOGRAPHY
Font Sizes:
- Named: small, medium, large, larger, huge, gigantic
- Example: {"fontSize":"large"} or {"fontSize":"huge"}
- Custom: {"fontSize":"1.25rem"} or {"fontSize":"clamp(2rem, 4vw, 3rem)"}

Font Families:
- system-font, source-serif-pro, system-sans-serif

## COMMON BLOCK PATTERNS

### BUTTONS
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"primary","textColor":"base"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-base-color has-primary-background-color has-text-color has-background wp-element-button">Button Text</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

#### Button Style Variations:
- Default (filled): No additional class needed
- Outline: {"className":"is-style-outline"}
- Rounded: {"className":"is-style-rounded"}
- Combined: {"className":"is-style-outline is-style-rounded"}

### COLUMNS (Responsive)
<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns">
<!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%">
<!-- content -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

### GROUP WITH PADDING
<!-- wp:group {"style":{"spacing":{"padding":{"top":"60px","bottom":"60px","left":"40px","right":"40px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:60px;padding-right:40px;padding-bottom:60px;padding-left:40px">
<!-- content -->
</div>
<!-- /wp:group -->

### SPACER
<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

## BLOCK-SPECIFIC VALIDATION

### HEADING
- Must include level: {"level":1} through {"level":6}
- Alignment: {"textAlign":"center"}
- Font size: {"fontSize":"huge"} or custom
- Can add custom classes: {"className":"hero-title"}

### IMAGE
Required: {"url":"https://...","alt":"text"}
Optional: {"sizeSlug":"large","linkDestination":"none","width":800,"height":600}

### PARAGRAPH
- Alignment: {"align":"center"} (note: "align" not "textAlign" for paragraphs)
- Font size: {"fontSize":"large"}
- Text color: {"textColor":"white"}

### SEPARATOR
Style variations: {"className":"is-style-wide"} or {"className":"is-style-dots"}

### LIST
CRITICAL: Lists must contain list-item blocks:
<!-- wp:list -->
<ul class="wp-block-list">
<!-- wp:list-item -->
<li>First item</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li>Second item</li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->

For ordered lists: {"ordered":true} and use <ol> instead of <ul>

## COLOR CLASS CONVENTIONS
When using named colors:
- Text color only: has-[color]-color has-text-color
- Background only: has-[color]-background-color has-background
- Both: has-[text-color]-color has-[bg-color]-background-color has-text-color has-background

Examples:
- class="has-white-color has-text-color"
- class="has-primary-background-color has-background"
- class="has-black-color has-white-background-color has-text-color has-background"

## COMMON MISTAKES TO AVOID
1. Missing or mismatched block closing comments
2. Using single quotes in JSON attributes
3. Forgetting required classes on HTML elements
4. Using relative image paths
5. Missing wp-block- prefix in class names
6. Incorrect nesting of inner blocks
7. Using url attribute in gradient-based cover blocks
8. Missing required attributes like alt for images
9. Malformed JSON in block attributes
10. Forgetting wrapper divs for certain blocks
11. Using textAlign instead of align for paragraphs
12. Not including data-object-fit="cover" for cover block images

## CUSTOM CLASSES & STYLING
- Multiple custom classes allowed: {"className":"hero-section is-style-rounded custom-class"}
- Custom inline styles supported: style="min-height:600px"
- Combine preset classes with custom classes
- Maintain WordPress naming conventions alongside custom classes

## CONTENT GENERATION RULES
- Professional tone: innovative, cutting-edge, forward-thinking
- Vary vocabulary - avoid repetitive phrases
- Use specific benefits, not generic claims
- Include numbers and data when relevant
- Mix short punchy headlines with descriptive subheadings
- Vary button CTAs: "Get Started", "Learn More", "Book a Demo", "Explore Features", "Discover Our Services"
- Use power words: Transform, Accelerate, Optimize, Revolutionize, Streamline, Innovative

## LAYOUT VARIATION STRATEGIES
1. Alternate between full-width and constrained sections
2. Mix 2, 3, and 4 column layouts
3. Vary alignment patterns (not everything centered)
4. Use different spacer heights: 20px, 30px, 50px, 80px, 100px
5. Combine different block patterns for visual interest
6. Use asymmetrical column widths: 40/60, 25/75, 30/40/30
7. Mix gradient and image-based cover blocks
8. Vary button styles within the same section
9. Use both CSS variables and direct values for spacing variety';
	}

	/**
	 * Get style-specific instructions.
	 *
	 * @since 1.0.0
	 * @param string $style Style type.
	 * @return string Style instructions.
	 */
	private function get_style_instructions( $style ) {
		if ( ! isset( $this->style_variations[ $style ] ) ) {
			$style = 'modern'; // Default fallback
		}
		
		$variation = $this->style_variations[ $style ];
		$random_index = array_rand( $variation['approaches'] );
		$approach = $variation['approaches'][ $random_index ];
		
		return "STYLE: {$variation['name']} Design
{$variation['description']}

Design Approach: {$approach}

Visual Elements:
- Colors: {$variation['colors']}
- Typography: {$variation['typography']}
- Spacing: {$variation['spacing']}
- Imagery: {$variation['imagery']}

Block Preferences:
{$variation['block_preferences']}";
	}

	/**
	 * Get layout-specific instructions.
	 *
	 * @since 1.0.0
	 * @param string $layout Layout type.
	 * @return string Layout instructions.
	 */
	private function get_layout_instructions( $layout ) {
		if ( ! isset( $this->layout_variations[ $layout ] ) ) {
			$layout = 'single-column'; // Default fallback
		}
		
		$variation = $this->layout_variations[ $layout ];
		$random_structure = $variation['structures'][ array_rand( $variation['structures'] ) ];
		
		return "LAYOUT: {$variation['name']}
{$variation['description']}

Structure: {$random_structure}

Layout Rules:
{$variation['rules']}

Section Organization:
{$variation['organization']}";
	}

	/**
	 * Get variation instructions to prevent repetition.
	 *
	 * @since 1.0.0
	 * @return string Variation instructions.
	 */
	private function get_variation_instructions() {
		$content_types = array(
			'professional services',
			'technology startup',
			'creative agency',
			'e-commerce business',
			'educational platform',
			'healthcare provider',
			'nonprofit organization',
			'consulting firm'
		);
		
		$random_content = $content_types[ array_rand( $content_types ) ];
		
		$tone_variations = array(
			'professional and authoritative',
			'friendly and approachable',
			'innovative and cutting-edge',
			'trustworthy and reliable',
			'creative and inspiring',
			'modern and sophisticated'
		);
		
		$random_tone = $tone_variations[ array_rand( $tone_variations ) ];
		
		return "CONTENT VARIATION:
Generate unique content as if for a {$random_content} with a {$random_tone} tone.

IMPORTANT VARIATION RULES:
1. Vary heading text - don't use generic titles
2. Mix block order - don't always follow the same pattern
3. Vary column counts - use 2, 3, or 4 columns as appropriate
4. Use different gradient combinations for cover blocks
5. Vary button text and styles
6. Mix alignment - not everything needs to be centered
7. Use different spacer heights for visual rhythm
8. Vary color combinations while maintaining cohesion";
	}

	/**
	 * Get context-specific instructions.
	 *
	 * @since 1.0.0
	 * @param array $options Context options.
	 * @return string Context instructions.
	 */
	private function get_context_instructions( $options ) {
		$instructions = "CONTEXT-SPECIFIC RULES:";
		
		// Add randomized content suggestions
		$hero_variations = array(
			'Use a compelling headline that grabs attention',
			'Create an inspiring tagline with a clear value proposition',
			'Craft a bold statement that defines the brand',
			'Write a customer-focused headline addressing their needs',
			'Develop a unique selling proposition as the main headline'
		);
		
		$instructions .= "\n- Hero Section: " . $hero_variations[ array_rand( $hero_variations ) ];
		
		// Add feature variations
		$feature_counts = array( 3, 4, 6 );
		$feature_layouts = array( 'icons with text', 'numbers with descriptions', 'bold headings with details' );
		
		$instructions .= "\n- Features: Display " . $feature_counts[ array_rand( $feature_counts ) ] . 
		                 " features using " . $feature_layouts[ array_rand( $feature_layouts ) ];
		
		// Add color scheme if not specified
		if ( ! isset( $options['colors'] ) ) {
			$color_scheme = $this->color_schemes[ array_rand( $this->color_schemes ) ];
			$instructions .= "\n- Color Scheme: " . $color_scheme;
		}
		
		return $instructions;
	}

	/**
	 * Initialize style variations.
	 *
	 * @since 1.0.0
	 */
	private function init_style_variations() {
		$this->style_variations = array(
			'modern' => array(
				'name' => 'Modern',
				'description' => 'Clean, minimalist design with plenty of whitespace',
				'approaches' => array(
					'Use bold typography with sans-serif fonts and high contrast',
					'Implement card-based layouts with subtle shadows',
					'Focus on geometric shapes and clean lines',
					'Emphasize negative space and minimal decoration'
				),
				'colors' => 'Monochromatic with accent colors, high contrast between elements',
				'typography' => 'Sans-serif fonts, large headings, readable body text',
				'spacing' => 'Generous padding (60-80px sections), consistent margins',
				'imagery' => 'High-quality photos with subtle overlays, geometric patterns',
				'block_preferences' => '- Use cover blocks with gradients for heroes
- Group blocks with background colors for sections
- Media & text blocks for feature highlights
- Simple button styles with hover effects'
			),
			'classic' => array(
				'name' => 'Classic',
				'description' => 'Traditional, professional design with structured layouts',
				'approaches' => array(
					'Implement formal grid structures with clear hierarchy',
					'Use traditional typography with serif headings',
					'Create balanced, symmetrical layouts',
					'Focus on readability and clear information architecture'
				),
				'colors' => 'Neutral palette with deep blues, grays, and subtle accents',
				'typography' => 'Serif fonts for headings, clean sans-serif for body',
				'spacing' => 'Moderate padding (40-60px), consistent throughout',
				'imagery' => 'Professional photography, traditional layouts',
				'block_preferences' => '- Traditional header with centered text
- Columns for features and services
- Testimonials in quote blocks
- Structured lists for information'
			),
			'creative' => array(
				'name' => 'Creative',
				'description' => 'Bold, artistic design with unique layouts',
				'approaches' => array(
					'Mix asymmetric layouts with dynamic angles',
					'Use vibrant color combinations and gradients',
					'Implement playful typography and custom styles',
					'Create unexpected layout combinations'
				),
				'colors' => 'Vibrant gradients, bold color combinations, high energy',
				'typography' => 'Mix of font styles, decorative headings, expressive text',
				'spacing' => 'Variable spacing for dynamic rhythm',
				'imagery' => 'Artistic images, overlapping elements, creative masks',
				'block_preferences' => '- Cover blocks with bold gradients
- Asymmetric column layouts
- Gallery blocks with creative arrangements
- Pullquotes for emphasis'
			),
			'minimal' => array(
				'name' => 'Minimal',
				'description' => 'Ultra-clean design with maximum simplicity',
				'approaches' => array(
					'Remove all unnecessary elements',
					'Focus on typography and content',
					'Use monochromatic color schemes',
					'Emphasize functionality over decoration'
				),
				'colors' => 'Black, white, and one accent color maximum',
				'typography' => 'Single font family, consistent weights',
				'spacing' => 'Maximum whitespace, sparse layouts',
				'imagery' => 'Minimal use of images, when used they are impactful',
				'block_preferences' => '- Simple text blocks
- Minimal buttons
- Clean separators
- Focus on content hierarchy'
			)
		);
	}

	/**
	 * Initialize layout variations.
	 *
	 * @since 1.0.0
	 */
	private function init_layout_variations() {
		$this->layout_variations = array(
			'single-column' => array(
				'name' => 'Single Column',
				'description' => 'Centered content in a single column',
				'structures' => array(
					'Full-width hero → Narrow content sections → Full-width CTA',
					'Alternating full and constrained width sections',
					'Consistent narrow column throughout',
					'Wide intro → Narrow body → Wide conclusion'
				),
				'rules' => '- Maximum content width: 800-1000px
- Center all content blocks
- Use full-width backgrounds with constrained content
- Maintain consistent alignment',
				'organization' => '- Stack sections vertically
- Use spacers for rhythm (40-80px)
- Alternate background colors for sections
- Keep consistent internal spacing'
			),
			'two-column' => array(
				'name' => 'Two Column',
				'description' => 'Content arranged in two columns',
				'structures' => array(
					'50/50 split for all sections',
					'60/40 asymmetric layout',
					'Alternating column dominance',
					'Mixed single and two-column sections'
				),
				'rules' => '- Use columns block with 2 columns
- Alternate text/image placement
- Maintain column spacing (40px gap)
- Responsive stacking on mobile',
				'organization' => '- Hero: single column
- Features: 2 columns
- About: media-text block
- Mix layouts for variety'
			),
			'grid' => array(
				'name' => 'Grid Layout',
				'description' => 'Content organized in grid patterns',
				'structures' => array(
					'3-column grid for features and services',
					'4-column grid for compact items',
					'Mixed 2-3-4 column grids',
					'Masonry-style varied heights'
				),
				'rules' => '- Use columns blocks for grids
- Consistent gap between items (30-40px)
- Equal height rows where possible
- Responsive column reduction',
				'organization' => '- Hero: full-width
- Features: 3-4 column grid
- Team/Portfolio: grid layout
- Footer: multi-column'
			),
			'asymmetric' => array(
				'name' => 'Asymmetric Layout',
				'description' => 'Dynamic, unbalanced layouts',
				'structures' => array(
					'70/30 split with varying sides',
					'Offset content blocks',
					'Diagonal or angled sections',
					'Overlapping elements'
				),
				'rules' => '- Vary column widths dramatically
- Use offset alignment
- Mix full, wide, and normal widths
- Create visual tension',
				'organization' => '- Break traditional patterns
- Use unexpected alignments
- Vary section widths
- Create dynamic flow'
			)
		);
	}

	/**
	 * Initialize color schemes.
	 *
	 * @since 1.0.0
	 */
	private function init_color_schemes() {
		$this->color_schemes = array(
			'Use a monochromatic blue scheme with white and gray accents',
			'Implement a warm palette with oranges, yellows, and browns',
			'Create a cool palette with teals, purples, and blues',
			'Use a high-contrast black and white with one bold accent color',
			'Apply an earthy palette with greens, browns, and natural tones',
			'Design with a gradient-heavy approach using vivid color transitions',
			'Implement a pastel palette with soft, muted colors',
			'Use a corporate palette with navy, gray, and subtle blue accents',
			'Create a vibrant palette with complementary colors',
			'Apply a dark theme with bright accent colors'
		);
	}

	/**
	 * Initialize typography variations.
	 *
	 * @since 1.0.0
	 */
	private function init_typography_variations() {
		$this->typography_variations = array(
			'modern_sans' => array(
				'headings' => 'Bold sans-serif, sizes: h1-60px, h2-48px, h3-36px',
				'body' => 'Clean sans-serif, 16-18px, 1.6 line-height',
				'special' => 'All caps for small labels, letter-spacing for emphasis'
			),
			'classic_serif' => array(
				'headings' => 'Elegant serif, sizes: h1-48px, h2-36px, h3-28px',
				'body' => 'Readable sans-serif, 16px, 1.7 line-height',
				'special' => 'Italic for quotes, small-caps for distinctions'
			),
			'mixed_modern' => array(
				'headings' => 'Display font for h1, sans-serif for h2-h6',
				'body' => 'System font stack for optimal reading',
				'special' => 'Variable font weights for hierarchy'
			)
		);
	}

	/**
	 * Enhance user prompt with dynamic context.
	 *
	 * @since 1.0.0
	 * @param string $prompt Original user prompt.
	 * @param array  $options Enhancement options.
	 * @return string Enhanced prompt.
	 */
	public function enhance_user_prompt( $prompt, $options = array() ) {
		$enhanced = $prompt;
		
		// Add specific variation instructions
		$variations = $this->get_prompt_variations( $prompt );
		$enhanced .= "\n\n" . $variations;
		
		// Add structure guidance based on detected sections
		$structure = $this->analyze_prompt_structure( $prompt );
		if ( ! empty( $structure ) ) {
			$enhanced .= "\n\nCreate these sections in order: " . implode( ', ', $structure );
		}
		
		// Add randomization hints
		$enhanced .= "\n\nIMPORTANT: Create unique content. Vary the structure, avoid generic text, and make each section distinctive.";
		
		return $enhanced;
	}

	/**
	 * Get prompt variations based on content.
	 *
	 * @since 1.0.0
	 * @param string $prompt User prompt.
	 * @return string Variation instructions.
	 */
	private function get_prompt_variations( $prompt ) {
		$variations = "SPECIFIC VARIATIONS:\n";
		
		// Hero variations
		if ( stripos( $prompt, 'hero' ) !== false ) {
			$hero_types = array(
				'Create a hero with gradient cover block, large headline, subheadline, and 2 buttons',
				'Design a hero with gradient background, centered text, and single CTA',
				'Build a hero with bold statement and supporting paragraph',
				'Make a hero with question headline and answer subtext'
			);
			$variations .= "- Hero: " . $hero_types[ array_rand( $hero_types ) ] . "\n";
		}
		
		// Features variations
		if ( stripos( $prompt, 'feature' ) !== false ) {
			$feature_layouts = array(
				'Create ' . rand( 3, 6 ) . ' features in columns with icons',
				'Design features in a ' . rand( 2, 3 ) . ' column grid with numbers',
				'Build alternating left/right features with media-text blocks',
				'Make feature cards with colored backgrounds'
			);
			$variations .= "- Features: " . $feature_layouts[ array_rand( $feature_layouts ) ] . "\n";
		}
		
		// CTA variations
		if ( stripos( $prompt, 'cta' ) !== false || stripos( $prompt, 'call to action' ) !== false ) {
			$cta_styles = array(
				'Create a full-width colored section with centered CTA',
				'Design a gradient background CTA with dual buttons',
				'Build a simple centered CTA with spacious padding',
				'Make a CTA with background pattern and bold text'
			);
			$variations .= "- CTA: " . $cta_styles[ array_rand( $cta_styles ) ] . "\n";
		}
		
		// Pricing table variations
		if ( stripos( $prompt, 'pricing' ) !== false || stripos( $prompt, 'price' ) !== false ) {
			$pricing_styles = array(
				'Create pricing columns with heading for tier name, list for features, and button for action',
				'Design pricing cards with background colors, price in heading, features in list',
				'Build pricing table with featured/recommended tier having different styling',
				'Make pricing comparison with consistent structure across all tiers'
			);
			$variations .= "- Pricing: " . $pricing_styles[ array_rand( $pricing_styles ) ] . "\n";
			$variations .= "- Use lists with list-items for features (not empty lists)\n";
			$variations .= "- Include price, features list, and CTA button in each column\n";
		}
		
		// Table variations
		if ( stripos( $prompt, 'table' ) !== false ) {
			$variations .= "- For pricing tables, use columns with lists inside (not core/table block)\n";
			$variations .= "- Each column should have: heading (tier name), paragraph (price), list (features), button (CTA)\n";
		}
		
		// FAQ variations
		if ( stripos( $prompt, 'faq' ) !== false || stripos( $prompt, 'question' ) !== false ) {
			$faq_styles = array(
				'Use multiple details blocks, each containing a question as summary and answer as content',
				'Create heading for each question followed by paragraph for answer',
				'Build accordion-style FAQs using details/summary blocks',
				'Design Q&A section with alternating background colors'
			);
			$variations .= "- FAQ: " . $faq_styles[ array_rand( $faq_styles ) ] . "\n";
			$variations .= "- Prefer details blocks for collapsible FAQ items\n";
			$variations .= "- Include at least 3-5 FAQ items with real questions and answers\n";
		}
		
		return $variations;
	}

	/**
	 * Analyze prompt structure to identify sections.
	 *
	 * @since 1.0.0
	 * @param string $prompt User prompt.
	 * @return array Detected sections.
	 */
	private function analyze_prompt_structure( $prompt ) {
		$sections = array();
		$prompt_lower = strtolower( $prompt );
		
		// Common section patterns
		$section_patterns = array(
			'hero' => array( 'hero', 'banner', 'header section', 'main header' ),
			'features' => array( 'features', 'services', 'benefits', 'offerings' ),
			'about' => array( 'about', 'story', 'mission', 'who we are' ),
			'testimonials' => array( 'testimonial', 'reviews', 'feedback', 'quotes' ),
			'pricing' => array( 'pricing', 'plans', 'packages', 'cost' ),
			'team' => array( 'team', 'staff', 'people', 'members' ),
			'portfolio' => array( 'portfolio', 'work', 'projects', 'gallery' ),
			'contact' => array( 'contact', 'get in touch', 'reach us', 'connect' ),
			'faq' => array( 'faq', 'questions', 'q&a', 'help' ),
			'cta' => array( 'cta', 'call to action', 'get started', 'sign up' )
		);
		
		foreach ( $section_patterns as $section => $patterns ) {
			foreach ( $patterns as $pattern ) {
				if ( stripos( $prompt_lower, $pattern ) !== false ) {
					$sections[] = $section;
					break;
				}
			}
		}
		
		// If no specific sections detected, suggest a default structure
		if ( empty( $sections ) ) {
			$default_structures = array(
				array( 'hero', 'features', 'about', 'cta' ),
				array( 'hero', 'services', 'testimonials', 'contact' ),
				array( 'hero', 'benefits', 'pricing', 'faq', 'cta' ),
				array( 'hero', 'portfolio', 'about', 'contact' )
			);
			$sections = $default_structures[ array_rand( $default_structures ) ];
		}
		
		return $sections;
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
		
		if ( strlen( $prompt ) > 1000 ) {
			return new \WP_Error( 
				'prompt_too_long', 
				__( 'Please keep your description under 1000 characters.', 'layoutberg' ) 
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
		// More accurate estimation based on common patterns
		// Average English word is ~4-5 characters, ~1.3 tokens per word
		$word_count = str_word_count( $text );
		$char_count = strlen( $text );
		
		// Use combination of word and character count for better estimation
		$token_estimate = ( $word_count * 1.3 ) + ( $char_count * 0.04 );
		
		return intval( $token_estimate );
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
			// OpenAI models
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
			// Claude models
			'claude-3-opus-20240229' => array(
				'total' => 200000,
				'max_completion' => 4096,
			),
			'claude-3-5-sonnet-20241022' => array(
				'total' => 200000,
				'max_completion' => 8192,
			),
			'claude-3-sonnet-20240229' => array(
				'total' => 200000,
				'max_completion' => 4096,
			),
			'claude-3-haiku-20240307' => array(
				'total' => 200000,
				'max_completion' => 4096,
			),
		);
		
		return isset( $limits[ $model ] ) ? $limits[ $model ] : $limits['gpt-3.5-turbo'];
	}

	/**
	 * Get simple example blocks for reference.
	 *
	 * @since 1.0.0
	 * @return string Example blocks.
	 */
	public function get_example_blocks() {
		// Return minimal examples to save tokens
		return '## MINIMAL EXAMPLES FOR REFERENCE:

### Heading with Alignment and Font Size
<!-- wp:heading {"textAlign":"center","level":2,"fontSize":"huge","className":"hero-title"} -->
<h2 class="wp-block-heading has-text-align-center hero-title has-huge-font-size">Your Title Here</h2>
<!-- /wp:heading -->

### Cover Block Method 1: With Background Image
<!-- wp:cover {"url":"https://images.unsplash.com/photo-1555041469-a586c61ea9bc","dimRatio":50,"minHeight":600,"align":"full"} -->
<div class="wp-block-cover alignfull" style="min-height:600px"><img class="wp-block-cover__image-background" alt="" src="https://images.unsplash.com/photo-1555041469-a586c61ea9bc" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"textColor":"white"} -->
<h1 class="wp-block-heading has-text-align-center has-white-color has-text-color">Hero Title</h1>
<!-- /wp:heading -->
</div></div>
<!-- /wp:cover -->

### Cover Block Method 2: With Gradient
<!-- wp:cover {"gradient":"cool-to-warm-spectrum","align":"full"} -->
<div class="wp-block-cover alignfull"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim has-background-gradient has-cool-to-warm-spectrum-gradient-background"></span><div class="wp-block-cover__inner-container">
<!-- content -->
</div></div>
<!-- /wp:cover -->

### Cover Block Method 3: With Custom Gradient
<!-- wp:cover {"customGradient":"linear-gradient(135deg,#667eea 0%,#764ba2 100%)","align":"full"} -->
<div class="wp-block-cover alignfull"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim has-background-gradient" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)"></span><div class="wp-block-cover__inner-container">
<!-- content -->
</div></div>
<!-- /wp:cover -->

### Buttons with Style Variations
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"primary","textColor":"base"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-base-color has-primary-background-color has-text-color has-background wp-element-button">Default Button</a></div>
<!-- /wp:button -->
<!-- wp:button {"backgroundColor":"secondary","textColor":"white","className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-secondary-background-color has-text-color has-background wp-element-button">Outline Button</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

### Paragraph with Proper Alignment
<!-- wp:paragraph {"align":"center","fontSize":"large","textColor":"contrast"} -->
<p class="has-text-align-center has-contrast-color has-text-color has-large-font-size">Note: paragraphs use "align" not "textAlign"</p>
<!-- /wp:paragraph -->

### Group with Direct Padding Values
<!-- wp:group {"style":{"spacing":{"padding":{"top":"60px","bottom":"60px","left":"40px","right":"40px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:60px;padding-right:40px;padding-bottom:60px;padding-left:40px">
<!-- content -->
</div>
<!-- /wp:group -->

### Spacer
<!-- wp:spacer {"height":"50px"} -->
<div style="height:50px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

### List with Items
<!-- wp:list -->
<ul class="wp-block-list">
<!-- wp:list-item -->
<li>First feature or benefit</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li>Second feature or benefit</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li>Third feature or benefit</li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->

### Details Block (FAQ/Accordion)
<!-- wp:details {"summary":"Question goes here?"} -->
<details class="wp-block-details"><summary>Question goes here?</summary>
<!-- wp:paragraph -->
<p>Answer goes here with detailed explanation.</p>
<!-- /wp:paragraph -->
</details>
<!-- /wp:details -->

CRITICAL: The summary text is an attribute {"summary":"text"}, NOT a separate block!';
	}
}