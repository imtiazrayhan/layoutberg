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
		
		// Check if we should use compact mode based on user prompt length
		$use_compact = false;
		if ( isset( $options['user_prompt_length'] ) && $options['user_prompt_length'] > 1000 ) {
			$use_compact = true;
		}
		
		// Get model limits
		$model = isset( $options['model'] ) ? $options['model'] : 'gpt-3.5-turbo';
		$model_limits = $this->get_model_limits( $model );
		
		// Start with base prompt (compact or full)
		$prompt = $use_compact ? $this->get_base_system_prompt_compact() : $this->get_base_system_prompt();
		
		// Add specific layout type instructions.
		if ( isset( $options['layout_type'] ) && isset( $this->layout_templates[ $options['layout_type'] ] ) ) {
			$prompt .= "\n\n" . $this->layout_templates[ $options['layout_type'] ]['instructions'];
		}
		
		// Add style-specific instructions.
		if ( isset( $options['style'] ) && $options['style'] !== 'default' ) {
			$prompt .= "\n\nDEFAULT STYLE GUIDELINES (use only if user doesn't specify):\n";
			$prompt .= $this->get_style_instructions( $options['style'] );
		}
		
		// Add layout-specific instructions.
		if ( isset( $options['layout'] ) && $options['layout'] !== 'default' ) {
			$prompt .= "\n\n" . $this->get_layout_instructions( $options['layout'] );
		}
		
		// Add color scheme instructions.
		if ( isset( $options['color_scheme'] ) && $options['color_scheme'] !== 'default' && $options['color_scheme'] !== null ) {
			$prompt .= "\n\nDEFAULT COLOR SCHEME (use only if user doesn't specify colors):\n";
			$prompt .= $this->get_color_scheme_instructions( $options['color_scheme'] );
		}
		
		// Add layout density instructions.
		if ( isset( $options['density'] ) && $options['density'] !== 'normal' && $options['density'] !== null ) {
			$prompt .= "\n\nDEFAULT SPACING (use only if user doesn't specify spacing):\n";
			$prompt .= $this->get_density_instructions( $options['density'] );
		}
		
		// Add audience targeting instructions.
		if ( isset( $options['audience'] ) && $options['audience'] !== 'general' && $options['audience'] !== null ) {
			$prompt .= "\n\n" . $this->get_audience_instructions( $options['audience'] );
		}
		
		// Add industry-specific instructions.
		if ( isset( $options['industry'] ) && $options['industry'] !== 'general' && $options['industry'] !== null ) {
			$prompt .= "\n\n" . $this->get_industry_instructions( $options['industry'] );
		}
		
		// Add language-specific instructions.
		if ( isset( $options['language'] ) && $options['language'] !== 'en' ) {
			$prompt .= "\n\n" . $this->get_language_instructions( $options['language'] );
		}
		
		// Add responsive design instructions.
		$prompt .= "\n\n" . $this->get_responsive_instructions();
		
		// Add accessibility instructions.
		$prompt .= "\n\n" . $this->get_accessibility_instructions();
		
		// Add example blocks.
		$prompt .= "\n\n" . $this->get_example_blocks();
		
		// Add final reminder
		$prompt .= "\n\nIMPORTANT REMINDER:\n";
		$prompt .= "1. User instructions take priority over all defaults.\n";
		$prompt .= "2. If the user specifies colors, gradients, spacing, or styles, use exactly what they request.\n";
		$prompt .= "3. The style guidelines above are only defaults to use when the user hasn't specified preferences.\n";
		if ( isset( $options['style'] ) && $options['style'] !== 'default' ) {
			$prompt .= "4. Default style: " . $options['style'] . " (only use if user doesn't specify)\n";
		}
		
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

IMPORTANT: The user's description takes priority. Follow their specific requests for colors, styles, layouts, and design elements exactly as they describe them.

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

VISUAL DESIGN GUIDELINES:
1. For placeholder images, use placehold.co URLs:
   - Example: {\"url\":\"https://placehold.co/600x400/007cba/ffffff?text=Hero+Image\"}
   - Format: https://placehold.co/WIDTHxHEIGHT/BGCOLOR/TEXTCOLOR?text=DESCRIPTION
2. For backgrounds, you can use:
   - Gradients: {\"gradient\":\"linear-gradient(135deg,#667eea 0%,#764ba2 100%)\"}
   - Solid colors: {\"backgroundColor\":\"primary\"} or {\"customBackgroundColor\":\"#123456\"}
3. Only use placehold.co for placeholder images, no other external URLs

IMPORTANT: If the user specifies any colors, gradients, or visual preferences in their description, use exactly what they request instead of any defaults.

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
	 * Get style-specific instructions.
	 *
	 * @since 1.0.0
	 * @param string $style Style preference.
	 * @return string Style instructions.
	 */
	private function get_style_instructions( $style ) {
		$style_guides = array(
			'modern' => "MODERN STYLE (use only if user hasn't specified):
• Spacers: 48px+ between sections
• Gradients: linear-gradient(135deg,#667eea 0%,#764ba2 100%)
• Typography: Large/bold headings (fontSize:\"x-large\")
• Layout: Asymmetric columns, card-based sections
• Colors: Vibrant gradients, bold contrasts",
			
			'classic' => "CLASSIC STYLE (defaults if not specified):
• Layout: Symmetrical grids, equal columns
• Colors: Navy #001F3F, gray #666666, white
• Typography: Traditional serif headings
• Spacing: Consistent 32px
• Design: Subtle shadows, 1px borders",
			
			'minimal' => "MINIMAL STYLE (defaults if not specified):
• Spacers: 80px+ (maximum whitespace)
• Colors: Black/white/grays (no gradients)
• Typography: Simple, clean fonts
• Design: No decorative elements
• Layout: Focus on content, negative space",
			
			'bold' => "BOLD STYLE (defaults if not specified):
• Layout: Asymmetric, varied columns
• Colors: Bright #FF006E, #FB5607, vibrant gradients
• Typography: Extra-large (fontSize:\"xx-large\")
• Spacing: Dynamic (mix tight/loose)
• Design: High contrast, strong patterns",
			
			'elegant' => "Apply elegant/sophisticated design principles:
- Use refined typography with serif fonts for headings
- Implement muted, sophisticated color palettes
- Add subtle decorative elements
- Include generous but balanced spacing
- Use elegant transitions and animations
- Apply golden ratio proportions
- Implement sophisticated gradient overlays
- Focus on luxurious feel",
			
			'playful' => "Apply playful/fun design principles:
- Use bright, cheerful color combinations
- Implement rounded corners and organic shapes
- Add playful typography with varied sizes
- Include fun icons and illustrations
- Use casual, friendly placeholder text
- Apply bouncy animations and transitions
- Implement colorful gradient backgrounds
- Focus on approachable, friendly feel",
			
			'corporate' => "Apply corporate/professional design principles:
- Use conservative color schemes (blues, grays, whites)
- Implement structured, predictable layouts
- Add professional imagery placeholders
- Include formal typography choices
- Use business-oriented placeholder text
- Apply consistent spacing and alignment
- Implement trust-building elements
- Focus on credibility and professionalism",
			
			'tech' => "Apply tech/startup design principles:
- Use modern, futuristic color schemes
- Implement cutting-edge layout patterns
- Add tech-oriented visual elements
- Include modern sans-serif typography
- Use innovation-focused placeholder text
- Apply subtle animations and interactions
- Implement data visualization elements
- Focus on innovation and progress",
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
			'single-column' => "Apply single column layout structure:
- Use full-width sections that stack vertically
- Center all content with consistent max-width
- Add generous spacing between sections
- Use group blocks with wide or full alignment
- Ensure clear visual separation between sections
- Focus on vertical rhythm and flow",
			
			'sidebar' => "Apply sidebar layout structure:
- Create a two-column layout with main content and sidebar
- Use columns block with 2/3 + 1/3 or 3/4 + 1/4 ratio
- Place primary content in the larger column
- Add complementary content in sidebar (navigation, related info, CTAs)
- Ensure sidebar remains visible on desktop but stacks on mobile
- Maintain visual hierarchy between main and sidebar content",
			
			'grid' => "Apply grid-based layout structure:
- Use columns blocks with equal-width columns
- Create card-based layouts with consistent spacing
- Implement 2, 3, or 4 column grids based on content
- Ensure uniform height and styling for grid items
- Add proper gaps between grid elements
- Make grid responsive (stack on smaller screens)",
			
			'asymmetric' => "Apply asymmetric layout structure:
- Use varied column widths and arrangements
- Create visual interest with offset sections
- Mix full-width and constrained-width sections
- Implement overlapping or staggered elements
- Use negative space creatively
- Break traditional alignment patterns",
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
			'monochrome' => "MONOCHROME DEFAULTS:
• Colors: #000000, #FFFFFF, grays
• Gradients: linear-gradient(135deg,#e0e0e0 0%,#666666 100%)
• Images: https://placehold.co/600x400/cccccc/333333",
			
			'blue' => "BLUE SCHEME DEFAULTS:
• Colors: #001F3F, #0074D9, #7FDBFF
• Gradients: linear-gradient(135deg,#1e3c72 0%,#2a5298 100%)
• Images: https://placehold.co/600x400/0074D9/FFFFFF",
			
			'green' => "Apply green-dominated color scheme:
- Use natural, calming green tones
- Implement eco-friendly gradients: linear-gradient(135deg,#134e5e 0%,#71b280 100%)
- Add earth tones as complementary colors
- Create organic, natural feeling
- Example palette: #0E4429, #006400, #2ECC40, #01FF70, #ffffff",
			
			'warm' => "Apply warm color scheme:
- Use reds, oranges, and yellows as primary colors
- Implement sunset gradients: linear-gradient(135deg,#ff6b6b 0%,#feca57 100%)
- Add brown and beige for balance
- Create energetic, inviting atmosphere
- Example palette: #FF4136, #FF851B, #FFDC00, #FF6347, #8B4513",
			
			'cool' => "Apply cool color scheme:
- Use blues, greens, and purples as primary colors
- Implement ocean gradients: linear-gradient(135deg,#667eea 0%,#764ba2 100%)
- Add silver and light grays for balance
- Create calm, professional atmosphere
- Example palette: #001f3f, #0074D9, #B10DC9, #85144b, #F8F8FF",
			
			'pastel' => "Apply pastel color scheme:
- Use soft, muted versions of colors
- Implement gentle gradients: linear-gradient(135deg,#ffeaa7 0%,#fab1a0 100%)
- Create dreamy, soft atmosphere
- Focus on light, airy feeling
- Example palette: #FFE5E5, #E5F3FF, #E5FFE5, #FFF5E5, #F5E5FF",
			
			'vibrant' => "Apply vibrant color scheme:
- Use bold, saturated colors
- Implement electric gradients: linear-gradient(135deg,#f093fb 0%,#f5576c 100%)
- Create high energy and excitement
- Use strong contrasts
- Example palette: #FF006E, #FB5607, #FFBE0B, #8338EC, #3A86FF",
			
			'dark' => "Apply dark color scheme:
- Use dark backgrounds with light text
- Implement subtle dark gradients: linear-gradient(135deg,#232526 0%,#414345 100%)
- Add bright accent colors for contrast
- Create modern, sophisticated atmosphere
- Example palette: #000000, #1a1a1a, #2d2d2d, #404040, #00ff00",
		);
		
		return isset( $color_guides[ $color_scheme ] ) ? $color_guides[ $color_scheme ] : '';
	}

	/**
	 * Get layout density instructions.
	 *
	 * @since 1.0.0
	 * @param string $density Density preference.
	 * @return string Density instructions.
	 */
	private function get_density_instructions( $density ) {
		$density_guides = array(
			'compact' => "COMPACT DEFAULTS: {\"height\":\"20px\"} spacers, small fonts",
			
			'normal' => "NORMAL DEFAULTS: {\"height\":\"40px\"} spacers, medium fonts",
			
			'spacious' => "SPACIOUS DEFAULTS: {\"height\":\"80px\"} spacers, large fonts",
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
			'professional' => "Target professional audience:
- Use formal, business-appropriate language
- Implement conservative design choices
- Include data-driven content placeholders
- Focus on credibility and expertise
- Use professional stock imagery descriptions
- Apply structured, logical layout flow",
			
			'casual' => "Target casual audience:
- Use friendly, conversational language
- Implement relaxed, approachable design
- Include lifestyle-oriented content
- Focus on relatability and warmth
- Use everyday imagery descriptions
- Apply organic, natural layout flow",
			
			'young' => "Target young audience (18-30):
- Use modern, trendy language
- Implement bold, dynamic design choices
- Include social media integration hints
- Focus on innovation and trends
- Use contemporary imagery descriptions
- Apply unconventional, creative layouts",
			
			'mature' => "Target mature audience (50+):
- Use clear, straightforward language
- Implement larger, readable typography
- Include traditional design elements
- Focus on clarity and ease of use
- Use classic imagery descriptions
- Apply simple, intuitive navigation",
			
			'tech-savvy' => "Target tech-savvy audience:
- Use technical terminology where appropriate
- Implement cutting-edge design patterns
- Include interactive element suggestions
- Focus on functionality and efficiency
- Use futuristic imagery descriptions
- Apply innovative layout structures",
			
			'creative' => "Target creative audience:
- Use artistic, expressive language
- Implement experimental design choices
- Include portfolio-style elements
- Focus on visual storytelling
- Use artistic imagery descriptions
- Apply asymmetric, artistic layouts",
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
			'healthcare' => "Apply healthcare industry standards:
- Use calming blues and greens
- Implement trust-building elements
- Include medical credibility indicators
- Focus on cleanliness and professionalism
- Use healthcare-related placeholder text
- Apply HIPAA-conscious design patterns
- Include appointment/contact CTAs",
			
			'finance' => "Apply finance industry standards:
- Use conservative colors (blues, grays)
- Implement security-focused design
- Include financial data visualizations
- Focus on trust and stability
- Use finance-related placeholder text
- Apply data-driven layout patterns
- Include calculator/tool CTAs",
			
			'education' => "Apply education industry standards:
- Use inspiring, vibrant colors
- Implement learning-focused design
- Include course/program showcases
- Focus on growth and achievement
- Use education-related placeholder text
- Apply structured, organized layouts
- Include enrollment/inquiry CTAs",
			
			'retail' => "Apply retail/e-commerce standards:
- Use product-focused layouts
- Implement shopping-friendly design
- Include product grid patterns
- Focus on visual merchandising
- Use retail-related placeholder text
- Apply conversion-optimized layouts
- Include shop/buy now CTAs",
			
			'technology' => "Apply technology industry standards:
- Use modern, futuristic design
- Implement innovation-focused layouts
- Include feature comparison tables
- Focus on cutting-edge solutions
- Use tech-related placeholder text
- Apply data visualization patterns
- Include demo/trial CTAs",
			
			'hospitality' => "Apply hospitality industry standards:
- Use warm, inviting colors
- Implement experience-focused design
- Include gallery/showcase sections
- Focus on comfort and luxury
- Use hospitality-related placeholder text
- Apply visual storytelling layouts
- Include booking/reservation CTAs",
			
			'nonprofit' => "Apply nonprofit standards:
- Use mission-driven design
- Implement impact-focused layouts
- Include story/testimonial sections
- Focus on community and cause
- Use nonprofit-related placeholder text
- Apply emotional connection patterns
- Include donate/volunteer CTAs",
			
			'legal' => "Apply legal industry standards:
- Use traditional, conservative design
- Implement authority-focused layouts
- Include practice area sections
- Focus on expertise and trust
- Use legal-related placeholder text
- Apply structured, formal patterns
- Include consultation CTAs",
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
			'es' => "Apply Spanish language considerations:
- Use Spanish placeholder text and headings
- Apply cultural design preferences (warmer colors, family-oriented imagery)
- Use formal/informal tone as appropriate
- Consider longer text lengths (Spanish text is ~20% longer than English)
- Example headings: 'Bienvenidos', 'Nuestros Servicios', 'Contáctenos'",
			
			'fr' => "Apply French language considerations:
- Use French placeholder text and headings
- Apply elegant, sophisticated design aesthetics
- Use formal tone and proper French typography
- Consider text expansion (French text is ~15% longer than English)
- Example headings: 'Bienvenue', 'Nos Services', 'Contactez-nous'",
			
			'de' => "Apply German language considerations:
- Use German placeholder text and headings
- Apply precise, structured design layouts
- Use formal business tone
- Consider compound words and longer text strings
- Example headings: 'Willkommen', 'Unsere Leistungen', 'Kontakt'",
			
			'it' => "Apply Italian language considerations:
- Use Italian placeholder text and headings
- Apply stylish, artistic design elements
- Use warm, expressive tone
- Consider text expansion similar to Spanish
- Example headings: 'Benvenuti', 'I Nostri Servizi', 'Contattaci'",
			
			'pt' => "Apply Portuguese language considerations:
- Use Portuguese placeholder text and headings
- Apply vibrant, colorful design elements
- Use friendly, approachable tone
- Consider Brazilian vs European Portuguese differences
- Example headings: 'Bem-vindo', 'Nossos Serviços', 'Contato'",
			
			'ja' => "Apply Japanese language considerations:
- Use Japanese placeholder text (mix of Kanji/Hiragana/Katakana)
- Apply minimalist, clean design principles
- Use respectful, formal tone
- Consider vertical text layout options
- Example headings: 'ようこそ', 'サービス', 'お問い合わせ'",
			
			'zh' => "Apply Chinese language considerations:
- Use Simplified Chinese placeholder text
- Apply balanced, harmonious design principles
- Use formal, respectful tone
- Consider character density and spacing
- Example headings: '欢迎', '我们的服务', '联系我们'",
			
			'ar' => "Apply Arabic language considerations:
- Use Arabic placeholder text
- Apply RIGHT-TO-LEFT layout direction
- Use traditional Middle Eastern design patterns
- Consider Arabic typography requirements
- Example headings: 'مرحبا', 'خدماتنا', 'اتصل بنا'
- IMPORTANT: Ensure all text alignment is reversed",
			
			'ru' => "Apply Russian language considerations:
- Use Cyrillic placeholder text
- Apply bold, strong design elements
- Use formal or informal tone as appropriate
- Consider Cyrillic character widths
- Example headings: 'Добро пожаловать', 'Наши услуги', 'Контакты'",
			
			'hi' => "Apply Hindi language considerations:
- Use Devanagari script placeholder text
- Apply colorful, vibrant design elements
- Use respectful, formal tone
- Consider complex character rendering
- Example headings: 'स्वागत है', 'हमारी सेवाएं', 'संपर्क करें'",
		);
		
		$general_language_instruction = "MULTILINGUAL CONTENT REQUIREMENTS:
- Generate all text content in {$language} language
- Maintain proper grammar and syntax for the target language
- Use culturally appropriate imagery descriptions
- Apply language-specific typography best practices
- Consider text direction and alignment requirements
- Ensure proper character encoding for special characters";
		
		if ( isset( $language_guides[ $language ] ) ) {
			return $language_guides[ $language ] . "\n\n" . $general_language_instruction;
		}
		
		return $general_language_instruction;
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
			'healthcare_landing' => array(
				'name' => __( 'Healthcare Landing Page', 'layoutberg' ),
				'instructions' => 'Create a healthcare landing page with: calming hero section with appointment CTA, services grid with medical icons, doctor profiles section, patient testimonials, insurance information, and contact form section. Use trust-building elements and professional medical imagery.',
			),
			'saas_landing' => array(
				'name' => __( 'SaaS Landing Page', 'layoutberg' ),
				'instructions' => 'Create a SaaS landing page with: feature-focused hero with demo CTA, key features grid with icons, pricing comparison table, integration partners logos, customer success stories, and free trial signup section. Emphasize ease of use and ROI.',
			),
			'restaurant_page' => array(
				'name' => __( 'Restaurant Page', 'layoutberg' ),
				'instructions' => 'Create a restaurant page with: appetizing hero image with reservation CTA, menu highlights section, chef introduction, customer reviews, location and hours information, and online ordering section. Focus on ambiance and cuisine quality.',
			),
			'real_estate_listing' => array(
				'name' => __( 'Real Estate Listing', 'layoutberg' ),
				'instructions' => 'Create a real estate listing page with: property hero gallery, key features and amenities grid, detailed property description, neighborhood information, virtual tour CTA, agent contact section, and mortgage calculator placeholder.',
			),
			'event_landing' => array(
				'name' => __( 'Event Landing Page', 'layoutberg' ),
				'instructions' => 'Create an event landing page with: exciting hero with countdown timer placeholder, event schedule/agenda, speaker profiles grid, venue information with map, ticket pricing options, and registration form section. Build anticipation and urgency.',
			),
			'nonprofit_campaign' => array(
				'name' => __( 'Nonprofit Campaign', 'layoutberg' ),
				'instructions' => 'Create a nonprofit campaign page with: emotional hero with donation CTA, mission statement, impact statistics, success stories/testimonials, volunteer opportunities section, and multiple donation options. Focus on emotional connection and trust.',
			),
			'fitness_landing' => array(
				'name' => __( 'Fitness Landing Page', 'layoutberg' ),
				'instructions' => 'Create a fitness landing page with: motivational hero with class signup CTA, trainer profiles, class schedule grid, transformation testimonials, membership pricing table, and facility features showcase. Emphasize energy and results.',
			),
			'education_course' => array(
				'name' => __( 'Education Course Page', 'layoutberg' ),
				'instructions' => 'Create an education course page with: engaging hero with enrollment CTA, course curriculum outline, instructor profile, student testimonials, learning outcomes section, pricing and enrollment options, and FAQ section. Focus on value and credibility.',
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
		
		if ( isset( $options['color_scheme'] ) && $options['color_scheme'] !== 'default' ) {
			$requirements[] = "Use " . $options['color_scheme'] . " color scheme as default (unless user specifies otherwise)";
		}
		
		if ( isset( $options['density'] ) && $options['density'] !== 'normal' ) {
			$requirements[] = "Apply " . $options['density'] . " layout density (unless user specifies otherwise)";
		}
		
		if ( isset( $options['audience'] ) && $options['audience'] !== 'general' ) {
			$requirements[] = "Optimize content for a " . $options['audience'] . " audience";
		}
		
		if ( isset( $options['industry'] ) && $options['industry'] !== 'general' ) {
			$requirements[] = "Consider " . $options['industry'] . " industry conventions";
		}
		
		if ( isset( $options['include_cta'] ) && $options['include_cta'] ) {
			$requirements[] = "Include prominent call-to-action sections";
		}
		
		if ( isset( $options['include_testimonials'] ) && $options['include_testimonials'] ) {
			$requirements[] = "Add a testimonials section";
		}
		
		if ( isset( $options['include_features'] ) && $options['include_features'] ) {
			$requirements[] = "Include a features or benefits section";
		}
		
		if ( isset( $options['include_pricing'] ) && $options['include_pricing'] ) {
			$requirements[] = "Add a pricing table or pricing information section";
		}
		
		if ( isset( $options['include_team'] ) && $options['include_team'] ) {
			$requirements[] = "Include a team members section";
		}
		
		if ( isset( $options['include_faq'] ) && $options['include_faq'] ) {
			$requirements[] = "Add a frequently asked questions section";
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
			'timeline_section' => array(
				'name' => __( 'Timeline', 'layoutberg' ),
				'prompt' => 'Create a timeline section showing company history or process steps with dates, titles, and descriptions in a vertical layout.',
			),
			'comparison_table' => array(
				'name' => __( 'Comparison Table', 'layoutberg' ),
				'prompt' => 'Create a feature comparison table with 3 columns comparing different plans or products with checkmarks and features list.',
			),
			'gallery_section' => array(
				'name' => __( 'Gallery', 'layoutberg' ),
				'prompt' => 'Create an image gallery section with 6-8 placeholder images in a grid layout with captions.',
			),
			'logo_grid' => array(
				'name' => __( 'Logo Grid', 'layoutberg' ),
				'prompt' => 'Create a client/partner logo grid section with 6-8 company logo placeholders and a heading like "Trusted by Industry Leaders".',
			),
			'process_steps' => array(
				'name' => __( 'Process Steps', 'layoutberg' ),
				'prompt' => 'Create a process/workflow section with 3-4 numbered steps, each with an icon, title, and description.',
			),
			'contact_info' => array(
				'name' => __( 'Contact Information', 'layoutberg' ),
				'prompt' => 'Create a contact information section with address, phone, email, and business hours in an organized layout.',
			),
			'newsletter_signup' => array(
				'name' => __( 'Newsletter Signup', 'layoutberg' ),
				'prompt' => 'Create a newsletter signup section with heading, benefit points, email input placeholder, and subscribe button.',
			),
			'social_proof' => array(
				'name' => __( 'Social Proof', 'layoutberg' ),
				'prompt' => 'Create a social proof section with customer logos, testimonial quotes, and trust badges or certifications.',
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