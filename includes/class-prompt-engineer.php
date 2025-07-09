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
	 * Block templates for common patterns.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $block_templates = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init_variations();
		$this->init_block_templates();
	}

	/**
	 * Build enhanced system prompt for layout generation.
	 *
	 * @since 1.0.0
	 * @param array $options Generation options.
	 * @return string System prompt.
	 */
	public function build_system_prompt( $options = array() ) {
		// Use the new smart prompt builder
		return $this->build_minimal_prompt( $options );
	}
	
	/**
	 * Build minimal, optimized prompt based on complexity.
	 *
	 * This method analyzes the user's prompt to determine what blocks are needed,
	 * checks for template matches, and builds a minimal prompt containing only
	 * the necessary components for generation.
	 *
	 * @since 1.0.0
	 * @param array $options Generation options including:
	 *                      - prompt: User's original prompt text
	 *                      - style: Optional style preference
	 *                      - site_type: Optional site type context
	 * @return string Optimized prompt with minimal token usage.
	 */
	private function build_minimal_prompt( $options = array() ) {
		// Analyze the user's prompt to determine what's needed
		$analysis = $this->analyze_user_prompt( $options['prompt'] ?? '' );
		
		// Check if we can use a template (only for simple/moderate complexity)
		if ( ! empty( $analysis['template'] ) && 
			isset( $this->block_templates[ $analysis['template'] ] ) &&
			$analysis['complexity'] !== 'complex' ) {
			return $this->build_template_prompt( $analysis['template'], $options );
		}
		
		// Otherwise, use dynamic generation
		// Choose template based on complexity
		$template = $this->get_prompt_template( $analysis['complexity'] );
		
		// Build prompt in optimal order
		$components = array();
		
		// Always include core rules first
		$components[] = $this->get_core_instructions();
		
		// Add block specs only for what's needed
		$components[] = $this->get_relevant_blocks( $analysis['blocks'] );
		
		// Add style if specified and complexity allows
		if ( isset( $options['style'] ) && $analysis['complexity'] !== 'simple' ) {
			$components[] = $this->get_style_instructions( $options['style'] );
		}
		
		// Include examples based on template
		if ( $template['include_examples'] ) {
			$max_examples = $template['max_examples'];
			$components[] = $this->get_example_blocks( array_slice( $analysis['blocks'], 0, $max_examples ) );
		}
		
		// Add minimal context for complex prompts
		if ( $analysis['complexity'] === 'complex' && ! empty( $options ) ) {
			$components[] = $this->get_context_instructions( $options );
		}
		
		// Join components with appropriate spacing
		return implode( "\n\n", array_filter( $components ) );
	}
	
	/**
	 * Build prompt using template.
	 *
	 * @since 1.0.0
	 * @param string $template_name Template name.
	 * @param array  $options Options.
	 * @return string Template-based prompt.
	 */
	private function build_template_prompt( $template_name, $options ) {
		$template = $this->block_templates[ $template_name ];
		
		// Minimal instructions for templates
		$prompt = "Use this exact template and customize the content:\n\n";
		$prompt .= "TEMPLATE: " . $template['name'] . "\n";
		$prompt .= "CUSTOMIZATION: Replace placeholder text with relevant content for: " . $options['prompt'] . "\n";
		
		// Add style hint if provided
		if ( isset( $options['style'] ) ) {
			$style_hints = array(
				'modern' => 'Use gradient backgrounds, white text, contemporary language',
				'classic' => 'Use neutral colors, professional tone, traditional layout',
				'bold' => 'Use strong colors, impactful headlines, dramatic spacing',
			);
			if ( isset( $style_hints[ $options['style'] ] ) ) {
				$prompt .= "STYLE: " . $style_hints[ $options['style'] ] . "\n";
			}
		}
		
		$prompt .= "\nTEMPLATE BLOCKS:\n" . $template['blocks'];
		
		return $prompt;
	}
	
	/**
	 * Get prompt template based on complexity.
	 *
	 * @since 1.0.0
	 * @param string $complexity Complexity level.
	 * @return array Template configuration.
	 */
	private function get_prompt_template( $complexity ) {
		$templates = array(
			'simple' => array(
				'include_examples' => true,
				'max_examples' => 2,
				'include_style' => false,
				'include_context' => false,
			),
			'moderate' => array(
				'include_examples' => true,
				'max_examples' => 3,
				'include_style' => true,
				'include_context' => false,
			),
			'complex' => array(
				'include_examples' => true,
				'max_examples' => 3,
				'include_style' => true,
				'include_context' => true,
			),
		);
		
		return $templates[ $complexity ] ?? $templates['moderate'];
	}

	/**
	 * Analyze user prompt to determine needed components.
	 *
	 * This method uses keyword pattern matching to detect which Gutenberg blocks
	 * are needed, determines the complexity level, handles smart dependencies,
	 * and checks for template matches.
	 *
	 * @since 1.0.0
	 * @param string $prompt User prompt text to analyze.
	 * @return array {
	 *     Analysis results containing:
	 *     @type array  $blocks     List of detected block types needed
	 *     @type string $complexity Complexity level: 'simple', 'moderate', or 'complex'
	 *     @type string $template   Template name if detected, null otherwise
	 * }
	 */
	private function analyze_user_prompt( $prompt ) {
		$prompt_lower = strtolower( $prompt );
		$blocks = array();
		$complexity = 'simple';
		
		// Block detection patterns
		$block_patterns = array(
			'heading' => ['hero', 'title', 'headline', 'header', 'heading'],
			'cover' => ['hero', 'banner', 'cover', 'background', 'jumbotron'],
			'buttons' => ['button', 'cta', 'call to action', 'link', 'action'],
			'columns' => ['columns', 'grid', 'features', 'services', 'benefits', 'cards'],
			'image' => ['image', 'photo', 'picture', 'visual', 'graphic'],
			'paragraph' => ['text', 'description', 'content', 'about', 'intro'],
			'list' => ['list', 'features', 'benefits', 'bullet', 'items'],
			'group' => ['section', 'container', 'wrapper', 'block'],
			'spacer' => ['spacing', 'gap', 'separator', 'divider'],
			'gallery' => ['gallery', 'portfolio', 'showcase', 'images'],
			'quote' => ['testimonial', 'quote', 'review', 'feedback'],
			'separator' => ['separator', 'divider', 'line', 'break'],
			'media-text' => ['media', 'side by side', 'image text', 'text image'],
			'pricing' => ['pricing', 'price', 'plans', 'tiers', 'packages'],
			'faq' => ['faq', 'questions', 'q&a', 'accordion'],
			'details' => ['details', 'expandable', 'collapsible', 'toggle'],
			'video' => ['video', 'youtube', 'vimeo', 'embed'],
		);
		
		// Detect needed blocks
		foreach ( $block_patterns as $block => $patterns ) {
			foreach ( $patterns as $pattern ) {
				if ( stripos( $prompt_lower, $pattern ) !== false ) {
					$blocks[] = $block;
					break;
				}
			}
		}
		
		// For hero sections, limit blocks to essentials
		if ( stripos( $prompt_lower, 'hero' ) !== false ) {
			// Hero sections typically only need: cover, heading, paragraph, buttons
			$hero_essentials = array( 'heading', 'cover', 'buttons', 'paragraph' );
			$blocks = array_intersect( $blocks, $hero_essentials );
		}
		
		// Add dependencies based on detected blocks
		if ( in_array( 'pricing', $blocks ) ) {
			$blocks[] = 'columns';
			$blocks[] = 'list';
			$blocks[] = 'buttons';
		}
		
		if ( in_array( 'faq', $blocks ) ) {
			$blocks[] = 'heading';
			$blocks[] = 'details';
		}
		
		if ( in_array( 'quote', $blocks ) && ( stripos( $prompt_lower, 'section' ) !== false || stripos( $prompt_lower, 'multiple' ) !== false ) ) {
			$blocks[] = 'columns';
		}
		
		if ( in_array( 'gallery', $blocks ) ) {
			$blocks[] = 'image';
		}
		
		if ( in_array( 'video', $blocks ) && stripos( $prompt_lower, 'section' ) !== false ) {
			$blocks[] = 'heading';
		}
		
		// Remove duplicates
		$blocks = array_unique( $blocks );
		
		// If no specific blocks detected, assume basic layout
		if ( empty( $blocks ) ) {
			$blocks = ['heading', 'paragraph', 'buttons'];
		}
		
		// Determine complexity using improved logic
		$complexity = $this->determine_complexity( $prompt_lower, $blocks );
		
		// Remove duplicates before checking templates
		$blocks = array_unique( $blocks );
		
		// Check for template matches
		$template_match = $this->detect_template_match( $prompt_lower, $blocks );
		
		return array(
			'blocks' => $blocks,
			'complexity' => $complexity,
			'template' => $template_match,
		);
	}
	
	/**
	 * Determine complexity based on sections and intent.
	 *
	 * @since 1.0.0
	 * @param string $prompt_lower Lowercase prompt.
	 * @param array  $blocks Detected blocks.
	 * @return string Complexity level.
	 */
	private function determine_complexity( $prompt_lower, $blocks ) {
		// Count distinct sections
		$section_count = $this->count_sections( $prompt_lower );
		
		// Check for explicit complexity indicators
		$simple_keywords = array( 'just', 'only', 'simple', 'basic', 'single' );
		$complex_keywords = array( 'full', 'complete', 'entire', 'comprehensive', 'landing page', 'all' );
		
		$has_simple_keyword = false;
		$has_complex_keyword = false;
		
		foreach ( $simple_keywords as $keyword ) {
			if ( stripos( $prompt_lower, $keyword ) !== false ) {
				$has_simple_keyword = true;
				break;
			}
		}
		
		foreach ( $complex_keywords as $keyword ) {
			if ( stripos( $prompt_lower, $keyword ) !== false ) {
				$has_complex_keyword = true;
				break;
			}
		}
		
		// Decision logic
		if ( $has_complex_keyword || $section_count > 3 ) {
			return 'complex';
		}
		
		if ( $has_simple_keyword || $section_count === 1 ) {
			// Check if it's a simple request with minimal additions
			$addition_words = substr_count( $prompt_lower, 'and' ) + substr_count( $prompt_lower, 'with' );
			$complex_additions = stripos( $prompt_lower, 'multiple' ) !== false;
			
			if ( $addition_words <= 2 && ! $complex_additions ) {
				return 'simple';
			}
		}
		
		// Default to moderate for 2-3 sections or enhanced single sections
		if ( $section_count >= 2 ) {
			return 'moderate';
		}
		
		// For single sections, check if it has too many blocks
		if ( count( $blocks ) > 5 ) {
			return 'moderate';
		}
		
		return 'simple';
	}
	
	/**
	 * Count distinct sections in prompt.
	 *
	 * @since 1.0.0
	 * @param string $prompt_lower Lowercase prompt.
	 * @return int Number of sections.
	 */
	private function count_sections( $prompt_lower ) {
		$sections = 0;
		$section_patterns = array(
			'hero' => array( 'hero', 'banner', 'header section', 'main header' ),
			'features' => array( 'feature', 'service', 'benefit', 'capability' ),
			'testimonials' => array( 'testimonial', 'review', 'feedback', 'quote' ),
			'pricing' => array( 'pricing', 'price', 'plan', 'package' ),
			'cta' => array( 'cta', 'call to action', 'contact', 'get started' ),
			'about' => array( 'about', 'story', 'mission', 'who we are' ),
			'faq' => array( 'faq', 'question', 'q&a', 'help' ),
			'gallery' => array( 'gallery', 'portfolio', 'showcase', 'work' ),
			'team' => array( 'team', 'staff', 'people', 'member' ),
		);
		
		foreach ( $section_patterns as $section => $patterns ) {
			foreach ( $patterns as $pattern ) {
				if ( stripos( $prompt_lower, $pattern ) !== false ) {
					$sections++;
					break; // Count each section type only once
				}
			}
		}
		
		// If no specific sections detected, count as 1
		return $sections > 0 ? $sections : 1;
	}
	
	/**
	 * Detect if prompt matches a template pattern.
	 *
	 * Enhanced pattern recognition that checks for template matches based on
	 * keywords, block combinations, and structural indicators. Supports both
	 * individual templates and combined template patterns.
	 *
	 * @since 1.0.0
	 * @param string $prompt_lower Lowercase prompt text for pattern matching.
	 * @param array  $blocks Array of detected block types.
	 * @return string|null Template name if match found, null otherwise.
	 */
	private function detect_template_match( $prompt_lower, $blocks ) {
		// Hero section detection - enhanced patterns
		$hero_patterns = array( 'hero', 'banner', 'header section', 'main banner', 'top section' );
		$hero_indicators = array( 'headline', 'title', 'main heading' );
		$has_hero_keyword = false;
		$has_hero_indicator = false;
		
		foreach ( $hero_patterns as $pattern ) {
			if ( stripos( $prompt_lower, $pattern ) !== false ) {
				$has_hero_keyword = true;
				break;
			}
		}
		
		foreach ( $hero_indicators as $indicator ) {
			if ( stripos( $prompt_lower, $indicator ) !== false && stripos( $prompt_lower, 'button' ) !== false ) {
				$has_hero_indicator = true;
				break;
			}
		}
		
		if ( ( $has_hero_keyword || $has_hero_indicator ) && count( $blocks ) <= 5 ) {
			return 'hero';
		}
		
		// Features grid detection - enhanced with service patterns
		$feature_patterns = array( 'features', 'services', 'benefits', 'capabilities', 'what we do', 'our services' );
		foreach ( $feature_patterns as $pattern ) {
			if ( stripos( $prompt_lower, $pattern ) !== false && 
				in_array( 'columns', $blocks ) && count( $blocks ) <= 5 ) {
				return 'features';
			}
		}
		
		// Testimonials detection - enhanced patterns
		$testimonial_patterns = array( 'testimonial', 'review', 'feedback', 'what clients say', 'customer stories' );
		foreach ( $testimonial_patterns as $pattern ) {
			if ( stripos( $prompt_lower, $pattern ) !== false && in_array( 'quote', $blocks ) ) {
				return 'testimonials';
			}
		}
		
		// CTA section detection - enhanced patterns
		$cta_patterns = array( 'cta', 'call to action', 'get started', 'contact us', 'sign up', 'join now' );
		foreach ( $cta_patterns as $pattern ) {
			if ( stripos( $prompt_lower, $pattern ) !== false && count( $blocks ) <= 4 ) {
				return 'cta';
			}
		}
		
		// Pricing table detection - enhanced with plan patterns
		$pricing_patterns = array( 'pricing', 'price', 'plans', 'packages', 'subscription', 'tiers' );
		foreach ( $pricing_patterns as $pattern ) {
			if ( stripos( $prompt_lower, $pattern ) !== false && 
				( in_array( 'pricing', $blocks ) || in_array( 'columns', $blocks ) ) ) {
				return 'pricing';
			}
		}
		
		// Combined template detection for complex requests
		if ( $this->detect_combined_templates( $prompt_lower, $blocks ) ) {
			return 'combined';
		}
		
		return null;
	}
	
	/**
	 * Detect combined template patterns.
	 *
	 * Identifies when a prompt requests multiple template sections that should
	 * be combined (e.g., "hero + features" or "full landing page").
	 *
	 * @since 1.0.0
	 * @param string $prompt_lower Lowercase prompt text.
	 * @param array  $blocks Detected block types.
	 * @return bool True if combined template pattern detected.
	 */
	private function detect_combined_templates( $prompt_lower, $blocks ) {
		// Landing page indicators
		$landing_page_patterns = array( 'landing page', 'full page', 'complete page', 'entire page' );
		foreach ( $landing_page_patterns as $pattern ) {
			if ( stripos( $prompt_lower, $pattern ) !== false ) {
				return true;
			}
		}
		
		// Multiple section indicators
		$multi_section_patterns = array( 'hero and features', 'header and pricing', 'testimonials and cta' );
		foreach ( $multi_section_patterns as $pattern ) {
			if ( stripos( $prompt_lower, $pattern ) !== false ) {
				return true;
			}
		}
		
		// High block count suggests combined sections
		if ( count( $blocks ) > 6 ) {
			return true;
		}
		
		return false;
	}

	/**
	 * Get core instructions for block generation.
	 *
	 * Returns the essential validation rules and formatting requirements
	 * for generating valid Gutenberg blocks. Optimized to be as compact
	 * as possible while covering critical requirements.
	 *
	 * @since 1.0.0
	 * @return string Compact core instructions for block generation.
	 */
	private function get_core_instructions() {
		return 'Generate valid Gutenberg blocks. Rules:

OUTPUT FORMAT:
- Only block markup, no explanations
- Format: <!-- wp:namespace/block {"attr":"value"} -->content<!-- /wp:namespace/block -->
- Use double quotes in JSON
- Match opening/closing comments

CRITICAL VALIDATION RULES:
- Cover blocks: Use ONLY "url" attribute, NEVER "id" attribute. Do not add wp-image-XXX classes.
- Images: Use absolute URLs (https://images.unsplash.com/photo-[id] or https://placehold.co/)
- Classes: wp-block-[blockname], has-[color]-color has-text-color
- Alignment: alignfull, alignwide, has-text-align-[left|center|right]
- Gradient backgrounds: Use predefined gradients like "vivid-cyan-blue-to-vivid-purple"

CRITICAL VISIBILITY RULES:
- ALWAYS ensure text is visible against backgrounds
- Hero sections MUST use cover blocks with gradient or image backgrounds
- Use contrasting colors: light text on dark backgrounds, dark text on light backgrounds
- Never use white text without a background color/gradient

COMMON ATTRIBUTES:
- Colors: {"textColor":"white","backgroundColor":"primary"}
- Spacing: {"style":{"spacing":{"padding":{"top":"60px"}}}}
- Font: {"fontSize":"large"} or {"fontSize":"1.5rem"}';
	}

	/**
	 * Get relevant block specifications.
	 *
	 * Returns only the block specifications needed for the detected blocks,
	 * rather than all possible blocks. This significantly reduces token usage
	 * by including only relevant information.
	 *
	 * @since 1.0.0
	 * @param array $blocks Array of block types that were detected as needed.
	 * @return string Concatenated block specifications for the needed blocks.
	 */
	private function get_relevant_blocks( $blocks ) {
		$specs = "BLOCK SPECS:\n";
		
		$block_specs = array(
			'heading' => '- Heading: {"level":1-6,"textAlign":"center","fontSize":"huge","textColor":"white"} (use white for dark backgrounds)',
			'cover' => '- Cover: Use gradient {"gradient":"vivid-cyan-blue-to-vivid-purple"} or image {"url":"https://...","dimRatio":50}. NEVER use "id" attribute. Inner content should have white text.',
			'buttons' => '- Button: {"backgroundColor":"white","textColor":"black"} or {"backgroundColor":"primary","textColor":"white"}',
			'columns' => '- Columns: {"style":{"spacing":{"blockGap":{"left":"40px"}}}} with column blocks inside',
			'image' => '- Image: {"url":"https://...","alt":"description","sizeSlug":"large"}',
			'paragraph' => '- Paragraph: {"align":"center","textColor":"white"} for dark backgrounds',
			'list' => '- List: Must contain list-item blocks inside',
			'group' => '- Group: {"backgroundColor":"base-2"} or {"gradient":"pale-ocean"} for sections needing backgrounds',
			'spacer' => '- Spacer: {"height":"50px"}',
			'separator' => '- Separator: {"className":"is-style-wide"} or is-style-dots',
			'quote' => '- Quote/Pullquote: {"citation":"Author Name"}',
			'media-text' => '- Media & Text: {"mediaPosition":"left","mediaType":"image"}',
			'gallery' => '- Gallery: {"columns":3,"imageCrop":true}',
			'video' => '- Video: {"url":"https://...","controls":true}',
			'details' => '- Details (FAQ): {"summary":"Question text"} with content inside',
		);
		
		foreach ( $blocks as $block ) {
			if ( isset( $block_specs[ $block ] ) ) {
				$specs .= $block_specs[ $block ] . "\n";
			}
		}
		
		return rtrim( $specs );
	}

	/**
	 * Get style-specific instructions.
	 *
	 * @since 1.0.0
	 * @param string $style Style type.
	 * @return string Style instructions.
	 */
	private function get_style_instructions( $style ) {
		$styles = array(
			'modern' => 'STYLE: Clean, minimal. Use: gradient backgrounds (vivid-cyan-blue-to-vivid-purple), white text on gradients, sans-serif, generous spacing (60-80px).',
			'classic' => 'STYLE: Traditional, professional. Use: light backgrounds (base-2), dark text (contrast), serif headings, moderate spacing (40-60px).',
			'bold' => 'STYLE: High impact. Use: strong gradient backgrounds, white text, large fonts, dramatic spacing, high-contrast buttons.',
			'minimal' => 'STYLE: Ultra-clean. Use: white background with black text OR black background with white text, maximum whitespace.',
			'creative' => 'STYLE: Artistic, unique. Use: colorful gradients (cool-to-warm-spectrum), white text overlays, mixed fonts.',
			'playful' => 'STYLE: Friendly, fun. Use: bright gradient backgrounds (blush-light-purple), white text, rounded corners.',
		);
		
		return $styles[ $style ] ?? $styles['modern'];
	}

	/**
	 * Get example blocks for reference.
	 *
	 * Returns a minimal set of examples (2-3 maximum) prioritized by relevance
	 * to the detected blocks. Uses a priority system to show the most important
	 * examples first.
	 *
	 * @since 1.0.0
	 * @param array $blocks Array of needed block types from analysis.
	 * @return string Minimal set of example blocks with proper Gutenberg markup.
	 */
	public function get_example_blocks( $blocks = array() ) {
		$examples = "EXAMPLES:\n";
		
		$block_examples = array(
			'heading' => '<!-- wp:heading {"textAlign":"center","level":1,"fontSize":"huge","textColor":"white"} -->
<h1 class="wp-block-heading has-text-align-center has-huge-font-size has-white-color has-text-color">Title</h1>
<!-- /wp:heading -->',
			
			'cover' => '<!-- wp:cover {"gradient":"vivid-cyan-blue-to-vivid-purple","align":"full","minHeight":600} -->
<div class="wp-block-cover alignfull" style="min-height:600px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim has-background-gradient has-vivid-cyan-blue-to-vivid-purple-gradient-background"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","textColor":"white"} -->
<h1 class="wp-block-heading has-text-align-center has-white-color has-text-color">Hero Title</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","textColor":"white"} -->
<p class="has-text-align-center has-white-color has-text-color">Hero description text</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->',
			
			'buttons' => '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"white","textColor":"black"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-black-color has-white-background-color has-text-color has-background wp-element-button">Get Started</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->',
			
			'group' => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}}} -->
<div class="wp-block-group" style="padding-top:60px;padding-bottom:60px">
<!-- content -->
</div>
<!-- /wp:group -->',
			
			'list' => '<!-- wp:list -->
<ul class="wp-block-list">
<!-- wp:list-item -->
<li>Item</li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->',
			
			'columns' => '<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- content -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->',
			
			'details' => '<!-- wp:details {"summary":"Question?"} -->
<details class="wp-block-details"><summary>Question?</summary>
<!-- wp:paragraph -->
<p>Answer</p>
<!-- /wp:paragraph -->
</details>
<!-- /wp:details -->',
		);
		
		// Priority order for examples
		$priority = array( 'cover', 'heading', 'buttons', 'columns', 'list', 'group', 'details' );
		
		// Only include examples for the first 2-3 most important blocks
		$included = 0;
		$blocks_to_show = empty( $blocks ) ? $priority : $blocks;
		
		foreach ( $priority as $block ) {
			if ( in_array( $block, $blocks_to_show ) && isset( $block_examples[ $block ] ) && $included < 3 ) {
				$examples .= "\n" . $block_examples[ $block ] . "\n";
				$included++;
			}
		}
		
		// If no priority blocks found, just take first 3
		if ( $included === 0 && ! empty( $blocks ) ) {
			foreach ( $blocks as $block ) {
				if ( isset( $block_examples[ $block ] ) && $included < 3 ) {
					$examples .= "\n" . $block_examples[ $block ] . "\n";
					$included++;
				}
			}
		}
		
		return rtrim( $examples );
	}

	/**
	 * Get context-specific instructions.
	 *
	 * @since 1.0.0
	 * @param array $options Context options.
	 * @return string Context instructions.
	 */
	private function get_context_instructions( $options ) {
		$context_parts = array();
		
		// Only add essential context
		if ( ! empty( $options['site_type'] ) ) {
			$types = array(
				'business' => 'professional/services',
				'blog' => 'content/readability',
				'portfolio' => 'visual showcase',
				'ecommerce' => 'products/shopping',
				'nonprofit' => 'mission/impact',
			);
			
			if ( isset( $types[ $options['site_type'] ] ) ) {
				$context_parts[] = 'TYPE: ' . $types[ $options['site_type'] ];
			}
		}
		
		// Add any critical custom context
		if ( ! empty( $options['context'] ) ) {
			$context_parts[] = 'FOCUS: ' . substr( $options['context'], 0, 50 );
		}
		
		return ! empty( $context_parts ) ? implode( ' | ', $context_parts ) : '';
	}

	/**
	 * Get variation instructions to prevent repetition.
	 *
	 * @since 1.0.0
	 * @return string Variation instructions.
	 */
	private function get_variation_instructions() {
		// This method is kept for backward compatibility but returns minimal content
		return '';
	}

	/**
	 * Initialize simplified variations.
	 *
	 * @since 1.0.0
	 */
	private function init_variations() {
		// Simplified style variations
		$this->style_variations = array(
			'modern' => array(
				'colors' => 'monochromatic, high contrast',
				'spacing' => 'generous (60-80px)',
				'typography' => 'sans-serif, large headings',
			),
			'classic' => array(
				'colors' => 'neutral, professional',
				'spacing' => 'moderate (40-60px)',
				'typography' => 'serif headings, clean body',
			),
			'bold' => array(
				'colors' => 'vibrant, high contrast',
				'spacing' => 'variable for emphasis',
				'typography' => 'bold, impactful',
			),
		);
		
		// Simplified layout variations
		$this->layout_variations = array(
			'single' => 'centered content, max-width 1000px',
			'multi' => 'columns for features, mixed widths',
			'grid' => '3-4 column grids, responsive',
		);
	}

	/**
	 * Enhance user prompt with minimal additions.
	 *
	 * Adds smart variations and structure hints to the user's original prompt
	 * without significantly increasing token usage. Only adds enhancements
	 * when they provide value.
	 *
	 * @since 1.0.0
	 * @param string $prompt Original user prompt text.
	 * @param array  $options Enhancement options (currently unused for optimization).
	 * @return string Enhanced prompt with minimal additions.
	 */
	public function enhance_user_prompt( $prompt, $options = array() ) {
		// Analyze for smarter enhancements
		$analysis = $this->analyze_user_prompt( $prompt );
		
		// Start with original prompt
		$enhanced = $prompt;
		
		// Add variation hint only for non-simple prompts
		if ( $analysis['complexity'] !== 'simple' ) {
			$seed = substr( md5( $prompt ), 0, 4 );
			$enhanced .= " [v{$seed}]";
		}
		
		// Add structure hint for complex prompts
		if ( $analysis['complexity'] === 'complex' ) {
			$structure = $this->analyze_prompt_structure( $prompt );
			if ( count( $structure ) > 2 ) {
				$enhanced .= " Structure: " . implode( '→', array_slice( $structure, 0, 3 ) );
			}
		}
		
		return $enhanced;
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
			'hero' => array( 'hero', 'banner', 'header section' ),
			'features' => array( 'features', 'services', 'benefits' ),
			'about' => array( 'about', 'story', 'mission' ),
			'testimonials' => array( 'testimonial', 'reviews', 'feedback' ),
			'pricing' => array( 'pricing', 'plans', 'packages' ),
			'contact' => array( 'contact', 'get in touch' ),
			'faq' => array( 'faq', 'questions' ),
			'cta' => array( 'cta', 'call to action' ),
		);
		
		foreach ( $section_patterns as $section => $patterns ) {
			foreach ( $patterns as $pattern ) {
				if ( stripos( $prompt_lower, $pattern ) !== false ) {
					$sections[] = $section;
					break;
				}
			}
		}
		
		return array_unique( $sections );
	}

	/**
	 * Get prompt variations based on content.
	 *
	 * @since 1.0.0
	 * @param string $prompt User prompt.
	 * @return string Variation instructions.
	 */
	private function get_prompt_variations( $prompt ) {
		// Kept for backward compatibility but returns minimal content
		return '';
	}

	/**
	 * Validate prompt for potential issues.
	 *
	 * Enhanced validation that checks for common issues and provides
	 * intelligent error recovery suggestions.
	 *
	 * @since 1.0.0
	 * @param string $prompt User prompt.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_prompt( $prompt ) {
		// Basic length validation
		if ( strlen( $prompt ) < 10 ) {
			return new \WP_Error( 
				'prompt_too_short', 
				__( 'Please provide more details about what you want to create.', 'layoutberg' ),
				array( 'suggestion' => 'Try: "Create a hero section with title and button"' )
			);
		}
		
		if ( strlen( $prompt ) > 1000 ) {
			return new \WP_Error( 
				'prompt_too_long', 
				__( 'Please keep your description under 1000 characters.', 'layoutberg' ),
				array( 'suggestion' => 'Focus on the main sections you need, like "hero, features, pricing"' )
			);
		}
		
		// Content validation
		$validation_issues = $this->detect_validation_issues( $prompt );
		if ( ! empty( $validation_issues ) ) {
			return new \WP_Error(
				'prompt_validation_failed',
				__( 'Prompt has potential issues: ', 'layoutberg' ) . implode( ', ', $validation_issues ),
				array( 'issues' => $validation_issues )
			);
		}
		
		return true;
	}
	
	/**
	 * Detect common validation issues in prompts.
	 *
	 * Identifies patterns that might lead to poor generation results
	 * and provides specific feedback for improvement.
	 *
	 * @since 1.0.0
	 * @param string $prompt User prompt.
	 * @return array List of detected issues.
	 */
	private function detect_validation_issues( $prompt ) {
		$issues = array();
		$prompt_lower = strtolower( $prompt );
		
		// Check for potentially harmful content
		$harmful_patterns = array( '<script', 'javascript:', 'onclick=', 'onerror=' );
		foreach ( $harmful_patterns as $pattern ) {
			if ( stripos( $prompt_lower, $pattern ) !== false ) {
				$issues[] = 'Contains potentially harmful code';
				break;
			}
		}
		
		// Check for overly vague requests
		$vague_patterns = array( 'something', 'anything', 'stuff', 'things' );
		$vague_count = 0;
		foreach ( $vague_patterns as $pattern ) {
			if ( stripos( $prompt_lower, $pattern ) !== false ) {
				$vague_count++;
			}
		}
		if ( $vague_count >= 2 ) {
			$issues[] = 'Too vague - be more specific about what you want';
		}
		
		// Check for unrealistic requests
		$unrealistic_patterns = array( 
			'everything', 'all possible', 'every feature', 'complete website',
			'full site', 'entire application'
		);
		foreach ( $unrealistic_patterns as $pattern ) {
			if ( stripos( $prompt_lower, $pattern ) !== false ) {
				$issues[] = 'Request too broad - focus on specific sections';
				break;
			}
		}
		
		// Check for conflicting requirements
		if ( stripos( $prompt_lower, 'simple' ) !== false && 
			 stripos( $prompt_lower, 'complex' ) !== false ) {
			$issues[] = 'Conflicting complexity requirements';
		}
		
		return $issues;
	}
	
	/**
	 * Generate layout with validation-first approach.
	 *
	 * Attempts to generate a layout with built-in validation and error recovery.
	 * If generation fails, provides fallback templates or suggestions.
	 *
	 * @since 1.0.0
	 * @param string $prompt User prompt.
	 * @param array  $options Generation options.
	 * @return array|WP_Error Generated prompt or error with recovery suggestions.
	 */
	public function generate_with_validation( $prompt, $options = array() ) {
		// First, validate the prompt
		$validation = $this->validate_prompt( $prompt );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		
		// Analyze prompt for potential issues
		$analysis = $this->analyze_user_prompt( $prompt );
		
		// Check if complexity is manageable
		if ( $analysis['complexity'] === 'complex' && count( $analysis['blocks'] ) > 8 ) {
			return new \WP_Error(
				'prompt_too_complex',
				__( 'This request is very complex. Consider breaking it into smaller sections.', 'layoutberg' ),
				array( 
					'suggestion' => 'Try generating one section at a time, like "Create a hero section" first',
					'detected_blocks' => $analysis['blocks'],
					'complexity' => $analysis['complexity']
				)
			);
		}
		
		// Attempt generation
		try {
			$system_prompt = $this->build_system_prompt( array_merge( $options, array( 'prompt' => $prompt ) ) );
			
			// Basic sanity check on generated prompt
			if ( strlen( $system_prompt ) < 100 ) {
				throw new Exception( 'Generated prompt too short' );
			}
			
			return array(
				'system_prompt' => $system_prompt,
				'analysis' => $analysis,
				'token_count' => $this->estimate_token_count( $system_prompt ),
				'status' => 'success'
			);
			
		} catch ( Exception $e ) {
			// Error recovery - try with template if available
			if ( ! empty( $analysis['template'] ) ) {
				$fallback_prompt = $this->build_template_prompt( $analysis['template'], $options );
				
				return array(
					'system_prompt' => $fallback_prompt,
					'analysis' => $analysis,
					'token_count' => $this->estimate_token_count( $fallback_prompt ),
					'status' => 'fallback_template_used',
					'original_error' => $e->getMessage()
				);
			}
			
			// Final fallback - return error with suggestions
			return new \WP_Error(
				'generation_failed',
				__( 'Unable to generate layout. Please try a simpler request.', 'layoutberg' ),
				array(
					'error' => $e->getMessage(),
					'suggestion' => $this->get_recovery_suggestion( $analysis ),
					'analysis' => $analysis
				)
			);
		}
	}
	
	/**
	 * Get recovery suggestion based on analysis.
	 *
	 * Provides specific suggestions to help users create better prompts
	 * when generation fails.
	 *
	 * @since 1.0.0
	 * @param array $analysis Prompt analysis results.
	 * @return string Recovery suggestion.
	 */
	private function get_recovery_suggestion( $analysis ) {
		$suggestions = array();
		
		// Suggest based on complexity
		switch ( $analysis['complexity'] ) {
			case 'complex':
				$suggestions[] = 'Try breaking your request into smaller sections';
				$suggestions[] = 'Start with just a hero section or features grid';
				break;
			case 'simple':
				$suggestions[] = 'Add more details about the layout you want';
				$suggestions[] = 'Specify colors, text, or structure preferences';
				break;
		}
		
		// Suggest based on detected blocks
		if ( empty( $analysis['blocks'] ) ) {
			$suggestions[] = 'Be more specific about what elements you need (headings, buttons, images, etc.)';
		} elseif ( count( $analysis['blocks'] ) > 6 ) {
			$suggestions[] = 'Focus on 2-3 main elements for better results';
		}
		
		// Default suggestions
		if ( empty( $suggestions ) ) {
			$suggestions[] = 'Try using simpler language to describe what you want';
			$suggestions[] = 'Example: "Create a hero section with title, description, and button"';
		}
		
		return implode( ' ', $suggestions );
	}

	/**
	 * Estimate token count for a string.
	 *
	 * Provides a more accurate estimation than simple word count by considering
	 * both word count and character count, which better approximates how
	 * language models tokenize text.
	 *
	 * @since 1.0.0
	 * @param string $text Text to count tokens for.
	 * @return int Estimated token count.
	 */
	public function estimate_token_count( $text ) {
		// More accurate estimation based on common patterns
		$word_count = str_word_count( $text );
		$char_count = strlen( $text );
		
		// Use combination of word and character count for better estimation
		$token_estimate = ( $word_count * 1.3 ) + ( $char_count * 0.04 );
		
		// Log token usage for monitoring if debug mode is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$this->log_token_usage( $text, intval( $token_estimate ) );
		}
		
		return intval( $token_estimate );
	}
	
	/**
	 * Log token usage for monitoring and optimization.
	 *
	 * Tracks token usage patterns to help identify optimization opportunities
	 * and monitor the system's efficiency over time.
	 *
	 * @since 1.0.0
	 * @param string $text The text that was analyzed.
	 * @param int    $token_count Estimated token count.
	 */
	private function log_token_usage( $text, $token_count ) {
		$log_data = array(
			'timestamp' => current_time( 'mysql' ),
			'token_count' => $token_count,
			'text_length' => strlen( $text ),
			'text_preview' => substr( $text, 0, 100 ) . '...',
			'user_id' => get_current_user_id(),
		);
		
		// Store in WordPress options for simple monitoring
		$usage_log = get_option( 'layoutberg_token_usage_log', array() );
		$usage_log[] = $log_data;
		
		// Keep only last 100 entries to prevent database bloat
		if ( count( $usage_log ) > 100 ) {
			$usage_log = array_slice( $usage_log, -100 );
		}
		
		update_option( 'layoutberg_token_usage_log', $usage_log );
		
		// Also log to error log if available
		if ( function_exists( 'error_log' ) ) {
			error_log( "LayoutBerg Token Usage: {$token_count} tokens for " . strlen( $text ) . " characters" );
		}
	}
	
	/**
	 * Get token usage statistics.
	 *
	 * Returns aggregated statistics about token usage patterns for monitoring
	 * and optimization purposes.
	 *
	 * @since 1.0.0
	 * @return array Token usage statistics.
	 */
	public function get_token_usage_stats() {
		$usage_log = get_option( 'layoutberg_token_usage_log', array() );
		
		if ( empty( $usage_log ) ) {
			return array(
				'total_requests' => 0,
				'average_tokens' => 0,
				'total_tokens' => 0,
				'max_tokens' => 0,
				'min_tokens' => 0,
			);
		}
		
		$total_tokens = 0;
		$token_counts = array();
		
		foreach ( $usage_log as $entry ) {
			$tokens = $entry['token_count'];
			$total_tokens += $tokens;
			$token_counts[] = $tokens;
		}
		
		return array(
			'total_requests' => count( $usage_log ),
			'average_tokens' => round( $total_tokens / count( $usage_log ), 1 ),
			'total_tokens' => $total_tokens,
			'max_tokens' => max( $token_counts ),
			'min_tokens' => min( $token_counts ),
			'recent_entries' => array_slice( $usage_log, -10 ), // Last 10 entries
		);
	}
	
	/**
	 * Clear token usage logs.
	 *
	 * Clears the stored token usage logs. Useful for maintenance or
	 * when starting fresh monitoring periods.
	 *
	 * @since 1.0.0
	 */
	public function clear_token_usage_logs() {
		delete_option( 'layoutberg_token_usage_log' );
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
	 * Initialize block templates.
	 *
	 * @since 1.0.0
	 */
	private function init_block_templates() {
		$this->block_templates = array(
			'hero' => array(
				'name' => 'Hero Section',
				'blocks' => '<!-- wp:cover {"gradient":"vivid-cyan-blue-to-vivid-purple","minHeight":600,"align":"full"} -->
<div class="wp-block-cover alignfull" style="min-height:600px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim has-background-gradient has-vivid-cyan-blue-to-vivid-purple-gradient-background"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"fontSize":"huge","textColor":"white"} -->
<h1 class="wp-block-heading has-text-align-center has-huge-font-size has-white-color has-text-color">[Main Headline]</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","fontSize":"large","textColor":"white"} -->
<p class="has-text-align-center has-large-font-size has-white-color has-text-color">[Supporting description that explains the value proposition]</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"white","textColor":"black","fontSize":"medium"} -->
<div class="wp-block-button has-custom-font-size has-medium-font-size"><a class="wp-block-button__link has-black-color has-white-background-color has-text-color has-background wp-element-button">[Primary CTA]</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div></div>
<!-- /wp:cover -->',
			),
			
			'features' => array(
				'name' => 'Features Grid',
				'blocks' => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px"}}},"backgroundColor":"base-2"} -->
<div class="wp-block-group has-base-2-background-color has-background" style="padding-top:80px;padding-bottom:80px">
<!-- wp:heading {"textAlign":"center","level":2,"fontSize":"x-large"} -->
<h2 class="wp-block-heading has-text-align-center has-x-large-font-size">[Features Headline]</h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"40px"}}}} -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"textAlign":"center","level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-text-align-center has-large-font-size">[Feature 1]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[Feature 1 description]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"textAlign":"center","level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-text-align-center has-large-font-size">[Feature 2]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[Feature 2 description]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"textAlign":"center","level":3,"fontSize":"large"} -->
<h3 class="wp-block-heading has-text-align-center has-large-font-size">[Feature 3]</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">[Feature 3 description]</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->',
			),
			
			'testimonials' => array(
				'name' => 'Testimonials Section',
				'blocks' => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:60px;padding-bottom:60px">
<!-- wp:heading {"textAlign":"center","level":2,"fontSize":"x-large"} -->
<h2 class="wp-block-heading has-text-align-center has-x-large-font-size">[Testimonials Headline]</h2>
<!-- /wp:heading -->

<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:quote -->
<blockquote class="wp-block-quote">
<p>"[Customer testimonial text]"</p>
<cite>[Customer Name, Title]</cite>
</blockquote>
<!-- /wp:quote -->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:quote -->
<blockquote class="wp-block-quote">
<p>"[Customer testimonial text]"</p>
<cite>[Customer Name, Title]</cite>
</blockquote>
<!-- /wp:quote -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->',
			),
			
			'cta' => array(
				'name' => 'Call to Action',
				'blocks' => '<!-- wp:cover {"gradient":"cool-to-warm-spectrum","minHeight":400,"align":"full"} -->
<div class="wp-block-cover alignfull" style="min-height:400px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim has-background-gradient has-cool-to-warm-spectrum-gradient-background"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":2,"fontSize":"x-large","textColor":"white"} -->
<h2 class="wp-block-heading has-text-align-center has-x-large-font-size has-white-color has-text-color">[CTA Headline]</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","fontSize":"medium","textColor":"white"} -->
<p class="has-text-align-center has-medium-font-size has-white-color has-text-color">[Supporting CTA text]</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"white","textColor":"black","className":"is-style-rounded"} -->
<div class="wp-block-button is-style-rounded"><a class="wp-block-button__link has-black-color has-white-background-color has-text-color has-background wp-element-button">[CTA Button Text]</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div></div>
<!-- /wp:cover -->',
			),
			
			'pricing' => array(
				'name' => 'Pricing Table',
				'blocks' => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"80px","bottom":"80px"}}}} -->
<div class="wp-block-group" style="padding-top:80px;padding-bottom:80px">
<!-- wp:heading {"textAlign":"center","level":2,"fontSize":"x-large"} -->
<h2 class="wp-block-heading has-text-align-center has-x-large-font-size">[Pricing Headline]</h2>
<!-- /wp:heading -->

<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"30px","right":"30px"}}},"backgroundColor":"base-2"} -->
<div class="wp-block-column has-base-2-background-color has-background" style="padding-top:40px;padding-right:30px;padding-bottom:40px;padding-left:30px">
<!-- wp:heading {"textAlign":"center","level":3} -->
<h3 class="wp-block-heading has-text-align-center">[Plan Name]</h3>
<!-- /wp:heading -->

<!-- wp:heading {"textAlign":"center","level":4,"fontSize":"x-large"} -->
<h4 class="wp-block-heading has-text-align-center has-x-large-font-size">$[Price]/mo</h4>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list">
<!-- wp:list-item -->
<li>[Feature 1]</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li>[Feature 2]</li>
<!-- /wp:list-item -->
<!-- wp:list-item -->
<li>[Feature 3]</li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"width":100} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link wp-element-button">Choose Plan</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->',
			),
		);
	}
}