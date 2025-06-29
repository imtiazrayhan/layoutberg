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
		// Debug logging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'LayoutBerg Prompt Engineer - Options received: ' . print_r( $options, true ) );
		}
		
		$prompt = $this->get_base_system_prompt();
		
		// Add specific layout type instructions.
		if ( isset( $options['layout_type'] ) && isset( $this->layout_templates[ $options['layout_type'] ] ) ) {
			$prompt .= "\n\n" . $this->layout_templates[ $options['layout_type'] ]['instructions'];
		}
		
		// Add style-specific instructions.
		if ( isset( $options['style'] ) && $options['style'] !== 'default' ) {
			$prompt .= "\n\n" . $this->get_style_instructions( $options['style'] );
		}
		
		// Add layout-specific instructions.
		if ( isset( $options['layout'] ) && $options['layout'] !== 'default' ) {
			$prompt .= "\n\n" . $this->get_layout_instructions( $options['layout'] );
		}
		
		// Add color scheme instructions.
		if ( isset( $options['color_scheme'] ) && $options['color_scheme'] !== 'default' && $options['color_scheme'] !== null ) {
			$prompt .= "\n\n" . $this->get_color_scheme_instructions( $options['color_scheme'] );
		}
		
		// Add layout density instructions.
		if ( isset( $options['density'] ) && $options['density'] !== 'normal' && $options['density'] !== null ) {
			$prompt .= "\n\n" . $this->get_density_instructions( $options['density'] );
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
		
		// Add final reinforcement
		$prompt .= "\n\nFINAL CRITICAL REMINDER:\n";
		$prompt .= "You MUST follow ALL the specific instructions provided above.\n";
		$prompt .= "Pay special attention to:\n";
		if ( isset( $options['style'] ) && $options['style'] !== 'default' ) {
			$prompt .= "- " . strtoupper( $options['style'] ) . " style requirements\n";
		}
		if ( isset( $options['color_scheme'] ) && $options['color_scheme'] !== 'default' ) {
			$prompt .= "- " . strtoupper( $options['color_scheme'] ) . " color scheme (USE ONLY THESE COLORS)\n";
		}
		if ( isset( $options['density'] ) && $options['density'] !== 'normal' ) {
			$prompt .= "- " . strtoupper( $options['density'] ) . " density (EXACT spacer sizes)\n";
		}
		$prompt .= "These are NOT optional - they are MANDATORY requirements.";
		
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
5. YOU MUST STRICTLY FOLLOW ALL STYLE, COLOR, DENSITY, AND OTHER SPECIFIC INSTRUCTIONS PROVIDED BELOW
6. Any instructions marked as MANDATORY or MUST are ABSOLUTE REQUIREMENTS - not suggestions

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
2. For image blocks, use placehold.co URLs for placeholder images:
   - Example: {\"url\":\"https://placehold.co/600x400/007cba/ffffff?text=Hero+Image\"}
   - Format: https://placehold.co/WIDTHxHEIGHT/BGCOLOR/TEXTCOLOR?text=DESCRIPTION
3. Focus on color combinations and gradients for visual interest
4. Only use placehold.co for placeholder images, no other external URLs
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
			'modern' => "MANDATORY MODERN DESIGN REQUIREMENTS - YOU MUST APPLY ALL OF THESE:
- MUST use generous whitespace with spacer blocks (minimum 48px between sections)
- MUST implement card-based layouts with group blocks and box shadows
- MUST add gradient backgrounds to cover blocks (use gradients like linear-gradient(135deg,#667eea 0%,#764ba2 100%))
- MUST use large, bold typography for headings (fontSize: x-large or larger)
- MUST include rounded corners via additional CSS classes (is-style-rounded)
- MUST implement asymmetric column layouts (avoid equal-width columns)
- MUST apply contemporary vibrant gradient color palettes
- MUST use bold color contrasts between sections",
			
			'classic' => "MANDATORY CLASSIC DESIGN REQUIREMENTS - YOU MUST APPLY ALL OF THESE:
- MUST use structured, symmetrical grid-based layouts
- MUST implement professional color schemes (navy blue #001F3F, gray #666666, white)
- MUST create balanced, symmetrical layouts (equal column widths)
- MUST use traditional typography (serif fonts for headings via className)
- MUST use formal, professional placeholder text
- MUST implement clear visual hierarchy with consistent spacing
- MUST apply subtle shadows and 1px borders
- MUST use traditional spacing (32px between elements)",
			
			'minimal' => "MANDATORY MINIMALIST DESIGN REQUIREMENTS - YOU MUST APPLY ALL OF THESE:
- MUST use maximum whitespace between sections (80px+ spacers)
- MUST stick to monochromatic color schemes (only black, white, and grays)
- MUST implement simple, clean typography (no decorative fonts)
- MUST avoid ALL decorative elements
- MUST use NO separators or only thin 1px lines
- MUST focus on content with minimal visual elements
- MUST use solid colors only (NO gradients)
- MUST use plenty of negative space (double normal spacing)",
			
			'bold' => "MANDATORY BOLD DESIGN REQUIREMENTS - YOU MUST APPLY ALL OF THESE:
- MUST use asymmetric and dynamic layouts (varied column widths)
- MUST implement bold, vibrant color combinations (bright reds #FF006E, oranges #FB5607)
- MUST add dramatic contrast between elements
- MUST include extra-large, impactful typography (xxl font sizes)
- MUST use strong visual elements and patterns
- MUST implement eye-catching gradient backgrounds (vibrant multi-color gradients)
- MUST apply dynamic spacing (mix of tight and loose spacing)
- MUST use high-contrast color combinations",
			
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
			'monochrome' => "CRITICAL COLOR REQUIREMENT - MONOCHROME SCHEME:
YOU MUST USE ONLY BLACK, WHITE, AND GRAY COLORS:
- Background colors: ONLY use #000000, #FFFFFF, or gray shades
- Text colors: ONLY use black (#000000) on white or white (#FFFFFF) on black
- For cover blocks: {\"customBackgroundColor\":\"#000000\"} or {\"customBackgroundColor\":\"#333333\"}
- For gradients: {\"gradient\":\"linear-gradient(135deg,#e0e0e0 0%,#666666 100%)\"}
- NO OTHER COLORS ALLOWED - ONLY GRAYSCALE
- Image placeholders: https://placehold.co/600x400/cccccc/333333",
			
			'blue' => "CRITICAL COLOR REQUIREMENT - BLUE SCHEME:
YOU MUST USE BLUE AS THE DOMINANT COLOR:
- Background colors: {\"customBackgroundColor\":\"#001F3F\"} or {\"customBackgroundColor\":\"#0074D9\"}
- Gradients: {\"gradient\":\"linear-gradient(135deg,#1e3c72 0%,#2a5298 100%)\"}
- Text on blue backgrounds MUST be white
- Accent colors: ONLY light blue #7FDBFF or white
- Image placeholders: https://placehold.co/600x400/0074D9/FFFFFF
- ALL sections must incorporate blue - no neutral sections",
			
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
			'compact' => "MANDATORY COMPACT DENSITY - YOU MUST APPLY:
- MUST use ONLY 20px spacer blocks between ALL elements
- {\"height\":\"20px\"} for ALL spacer blocks - NO EXCEPTIONS
- MUST use small font sizes (fontSize: small or normal)
- MUST pack content tightly together
- MUST minimize all margins and padding
- NO spacers larger than 20px allowed",
			
			'normal' => "MANDATORY NORMAL DENSITY - YOU MUST APPLY:
- MUST use EXACTLY 40px spacer blocks between sections
- {\"height\":\"40px\"} for ALL spacer blocks
- MUST use standard font sizes (fontSize: medium)
- MUST maintain consistent spacing throughout
- MUST balance content with breathing room
- ALL spacers must be exactly 40px",
			
			'spacious' => "MANDATORY SPACIOUS DENSITY - YOU MUST APPLY:
- MUST use MINIMUM 80px spacer blocks between ALL sections
- {\"height\":\"80px\"} or {\"height\":\"100px\"} for spacer blocks
- MUST use large font sizes (fontSize: large or x-large)
- MUST create airy, luxury feeling with excessive whitespace
- MUST add extra spacing around all elements
- NO spacers smaller than 80px allowed",
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
				<img src=\"https://placehold.co/64x64/007cba/ffffff?text=🚀\" alt=\"Speed and performance icon\" width=\"64\" height=\"64\"/>
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
				<img src=\"https://placehold.co/64x64/007cba/ffffff?text=🔒\" alt=\"Security and privacy icon\" width=\"64\" height=\"64\"/>
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
				<img src=\"https://placehold.co/64x64/007cba/ffffff?text=⚡\" alt=\"Easy integration icon\" width=\"64\" height=\"64\"/>
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
			$requirements[] = "YOU MUST use ONLY the " . $options['color_scheme'] . " color scheme - this is MANDATORY";
		}
		
		if ( isset( $options['density'] ) && $options['density'] !== 'normal' ) {
			$requirements[] = "YOU MUST apply " . $options['density'] . " layout density with EXACT spacer sizes specified";
		}
		
		if ( isset( $options['audience'] ) && $options['audience'] !== 'general' ) {
			$requirements[] = "YOU MUST optimize ALL content specifically for a " . $options['audience'] . " audience";
		}
		
		if ( isset( $options['industry'] ) && $options['industry'] !== 'general' ) {
			$requirements[] = "YOU MUST follow " . $options['industry'] . " industry requirements throughout";
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