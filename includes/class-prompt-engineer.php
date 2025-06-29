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
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init_variations();
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
	 * @since 1.0.0
	 * @param array $options Generation options.
	 * @return string Optimized prompt.
	 */
	private function build_minimal_prompt( $options = array() ) {
		// Analyze the user's prompt to determine what's needed
		$analysis = $this->analyze_user_prompt( $options['prompt'] ?? '' );
		
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
	 * @since 1.0.0
	 * @param string $prompt User prompt.
	 * @return array Analysis results.
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
		
		// Determine complexity
		if ( count( $blocks ) > 5 || stripos( $prompt_lower, 'complex' ) !== false || stripos( $prompt_lower, 'full' ) !== false ) {
			$complexity = 'complex';
		} elseif ( count( $blocks ) > 2 || stripos( $prompt_lower, 'with' ) !== false ) {
			$complexity = 'moderate';
		}
		
		return array(
			'blocks' => array_unique( $blocks ),
			'complexity' => $complexity,
		);
	}

	/**
	 * Get core instructions for block generation.
	 *
	 * @since 1.0.0
	 * @return string Core instructions.
	 */
	private function get_core_instructions() {
		return 'Generate valid Gutenberg blocks. Rules:

OUTPUT FORMAT:
- Only block markup, no explanations
- Format: <!-- wp:namespace/block {"attr":"value"} -->content<!-- /wp:namespace/block -->
- Use double quotes in JSON
- Match opening/closing comments

CRITICAL VISIBILITY RULES:
- ALWAYS ensure text is visible against backgrounds
- Hero sections MUST use cover blocks with gradient or image backgrounds
- Use contrasting colors: light text on dark backgrounds, dark text on light backgrounds
- Never use white text without a background color/gradient

VALIDATION:
- Images: Use absolute URLs (https://images.unsplash.com/photo-[id] or https://placehold.co/)
- Classes: wp-block-[blockname], has-[color]-color has-text-color
- Alignment: alignfull, alignwide, has-text-align-[left|center|right]

COMMON ATTRIBUTES:
- Colors: {"textColor":"white","backgroundColor":"primary"}
- Spacing: {"style":{"spacing":{"padding":{"top":"60px"}}}}
- Font: {"fontSize":"large"} or {"fontSize":"1.5rem"}';
	}

	/**
	 * Get relevant block specifications.
	 *
	 * @since 1.0.0
	 * @param array $blocks Needed blocks.
	 * @return string Block specifications.
	 */
	private function get_relevant_blocks( $blocks ) {
		$specs = "BLOCK SPECS:\n";
		
		$block_specs = array(
			'heading' => '- Heading: {"level":1-6,"textAlign":"center","fontSize":"huge","textColor":"white"} (use white for dark backgrounds)',
			'cover' => '- Cover: ALWAYS use gradient {"gradient":"vivid-cyan-blue-to-vivid-purple"} or image {"url":"https://...","dimRatio":50}. Inner content should have white text.',
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
	 * @since 1.0.0
	 * @param array $blocks Needed blocks.
	 * @return string Example blocks.
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
	 * @since 1.0.0
	 * @param string $prompt Original user prompt.
	 * @param array  $options Enhancement options.
	 * @return string Enhanced prompt.
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
	 * Initialize style variations.
	 *
	 * @since 1.0.0
	 * @deprecated Kept for backward compatibility
	 */
	private function init_style_variations() {
		// This method is kept for backward compatibility
		// Style variations are now initialized in init_variations()
	}

	/**
	 * Initialize layout variations.
	 *
	 * @since 1.0.0
	 * @deprecated Kept for backward compatibility
	 */
	private function init_layout_variations() {
		// This method is kept for backward compatibility
		// Layout variations are now initialized in init_variations()
	}

	/**
	 * Initialize color schemes.
	 *
	 * @since 1.0.0
	 * @deprecated Kept for backward compatibility
	 */
	private function init_color_schemes() {
		// This method is kept for backward compatibility
		// No longer used in optimized version
	}

	/**
	 * Initialize typography variations.
	 *
	 * @since 1.0.0
	 * @deprecated Kept for backward compatibility
	 */
	private function init_typography_variations() {
		// This method is kept for backward compatibility
		// No longer used in optimized version
	}

	/**
	 * Get layout-specific instructions.
	 *
	 * @since 1.0.0
	 * @param string $layout Layout type.
	 * @return string Layout instructions.
	 */
	private function get_layout_instructions( $layout ) {
		// Simplified for optimization
		return '';
	}
}