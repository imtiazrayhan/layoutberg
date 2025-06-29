<?php
/**
 * Content randomizer class for generating varied content.
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
 * Content randomizer class.
 *
 * @since 1.0.0
 */
class Content_Randomizer {

	/**
	 * Industry contexts for content generation.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $industries = array(
		'technology' => array(
			'keywords' => array( 'innovation', 'digital', 'technology', 'software', 'platform', 'solution', 'data', 'cloud', 'AI', 'automation' ),
			'tone' => 'innovative and forward-thinking',
			'focus' => 'cutting-edge solutions and digital transformation'
		),
		'healthcare' => array(
			'keywords' => array( 'care', 'health', 'wellness', 'patients', 'medical', 'treatment', 'healing', 'clinic', 'healthcare', 'medicine' ),
			'tone' => 'caring and professional',
			'focus' => 'patient care and medical excellence'
		),
		'finance' => array(
			'keywords' => array( 'financial', 'investment', 'growth', 'portfolio', 'returns', 'wealth', 'banking', 'capital', 'assets', 'markets' ),
			'tone' => 'trustworthy and authoritative',
			'focus' => 'financial growth and security'
		),
		'education' => array(
			'keywords' => array( 'learning', 'education', 'students', 'knowledge', 'courses', 'teaching', 'academic', 'skills', 'training', 'development' ),
			'tone' => 'inspiring and supportive',
			'focus' => 'learning and personal growth'
		),
		'retail' => array(
			'keywords' => array( 'products', 'shopping', 'customers', 'quality', 'selection', 'value', 'store', 'brand', 'merchandise', 'retail' ),
			'tone' => 'friendly and customer-focused',
			'focus' => 'product quality and customer satisfaction'
		),
		'creative' => array(
			'keywords' => array( 'creative', 'design', 'artistic', 'visual', 'brand', 'aesthetic', 'style', 'imagination', 'concept', 'vision' ),
			'tone' => 'creative and inspiring',
			'focus' => 'creative excellence and unique design'
		),
		'consulting' => array(
			'keywords' => array( 'strategy', 'consulting', 'solutions', 'expertise', 'advisory', 'insights', 'analysis', 'guidance', 'performance', 'results' ),
			'tone' => 'professional and knowledgeable',
			'focus' => 'strategic solutions and expert guidance'
		),
		'nonprofit' => array(
			'keywords' => array( 'mission', 'impact', 'community', 'cause', 'support', 'difference', 'volunteer', 'charity', 'help', 'change' ),
			'tone' => 'compassionate and purpose-driven',
			'focus' => 'making a positive impact'
		)
	);

	/**
	 * Content templates for different sections.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $content_templates = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init_content_templates();
	}

	/**
	 * Get randomized content for a specific context.
	 *
	 * @since 1.0.0
	 * @param string $type    Content type.
	 * @param array  $options Content options.
	 * @return string Randomized content.
	 */
	public function get_content( $type, $options = array() ) {
		// Determine industry context
		$industry = $this->get_random_industry( $options );
		
		// Get base content
		$content = $this->get_base_content( $type, $industry );
		
		// Apply variations
		$content = $this->apply_variations( $content, $industry, $options );
		
		// Personalize content
		$content = $this->personalize_content( $content, $options );
		
		return $content;
	}

	/**
	 * Get random industry context.
	 *
	 * @since 1.0.0
	 * @param array $options Options.
	 * @return string Industry key.
	 */
	private function get_random_industry( $options ) {
		if ( isset( $options['industry'] ) && isset( $this->industries[ $options['industry'] ] ) ) {
			return $options['industry'];
		}
		
		$keys = array_keys( $this->industries );
		return $keys[ array_rand( $keys ) ];
	}

	/**
	 * Get base content for type.
	 *
	 * @since 1.0.0
	 * @param string $type     Content type.
	 * @param string $industry Industry context.
	 * @return string Base content.
	 */
	private function get_base_content( $type, $industry ) {
		if ( ! isset( $this->content_templates[ $type ] ) ) {
			return $this->get_generic_content( $type );
		}
		
		$templates = $this->content_templates[ $type ];
		$template = $templates[ array_rand( $templates ) ];
		
		// Replace industry placeholders
		$industry_data = $this->industries[ $industry ];
		$content = str_replace( '{focus}', $industry_data['focus'], $template );
		$content = str_replace( '{tone}', $industry_data['tone'], $template );
		
		// Replace keyword placeholders
		$keyword = $industry_data['keywords'][ array_rand( $industry_data['keywords'] ) ];
		$content = str_replace( '{keyword}', $keyword, $content );
		
		return $content;
	}

	/**
	 * Apply variations to content.
	 *
	 * @since 1.0.0
	 * @param string $content  Base content.
	 * @param string $industry Industry context.
	 * @param array  $options  Options.
	 * @return string Varied content.
	 */
	private function apply_variations( $content, $industry, $options ) {
		// Apply synonym variations
		$content = $this->apply_synonyms( $content );
		
		// Apply structure variations
		$content = $this->vary_sentence_structure( $content );
		
		// Apply industry-specific terms
		$content = $this->apply_industry_terms( $content, $industry );
		
		// Apply style variations
		if ( isset( $options['style'] ) ) {
			$content = $this->apply_style_variations( $content, $options['style'] );
		}
		
		return $content;
	}

	/**
	 * Apply synonyms to vary content.
	 *
	 * @since 1.0.0
	 * @param string $content Content.
	 * @return string Varied content.
	 */
	private function apply_synonyms( $content ) {
		$synonyms = array(
			'amazing' => array( 'incredible', 'remarkable', 'exceptional', 'outstanding', 'extraordinary' ),
			'innovative' => array( 'cutting-edge', 'pioneering', 'revolutionary', 'groundbreaking', 'advanced' ),
			'transform' => array( 'revolutionize', 'change', 'evolve', 'reshape', 'redefine' ),
			'powerful' => array( 'robust', 'strong', 'effective', 'potent', 'dynamic' ),
			'solution' => array( 'answer', 'approach', 'system', 'platform', 'tool' ),
			'success' => array( 'achievement', 'growth', 'results', 'excellence', 'prosperity' ),
			'experience' => array( 'journey', 'adventure', 'encounter', 'interaction', 'engagement' ),
			'quality' => array( 'excellence', 'standard', 'caliber', 'grade', 'superiority' ),
			'professional' => array( 'expert', 'skilled', 'accomplished', 'experienced', 'qualified' ),
			'dedicated' => array( 'committed', 'devoted', 'focused', 'passionate', 'determined' )
		);
		
		foreach ( $synonyms as $word => $alternatives ) {
			if ( stripos( $content, $word ) !== false ) {
				$replacement = $alternatives[ array_rand( $alternatives ) ];
				$content = str_ireplace( $word, $replacement, $content );
			}
		}
		
		return $content;
	}

	/**
	 * Vary sentence structure.
	 *
	 * @since 1.0.0
	 * @param string $content Content.
	 * @return string Varied content.
	 */
	private function vary_sentence_structure( $content ) {
		// Add sentence starters occasionally
		$starters = array(
			'Moreover, ',
			'Furthermore, ',
			'Additionally, ',
			'In fact, ',
			'Indeed, ',
			'Notably, ',
			'Importantly, '
		);
		
		// Add a starter to 20% of sentences
		if ( rand( 1, 5 ) === 1 ) {
			$sentences = explode( '. ', $content );
			if ( count( $sentences ) > 1 ) {
				$index = rand( 1, count( $sentences ) - 1 );
				$sentences[ $index ] = $starters[ array_rand( $starters ) ] . lcfirst( $sentences[ $index ] );
				$content = implode( '. ', $sentences );
			}
		}
		
		return $content;
	}

	/**
	 * Apply industry-specific terms.
	 *
	 * @since 1.0.0
	 * @param string $content  Content.
	 * @param string $industry Industry.
	 * @return string Content with industry terms.
	 */
	private function apply_industry_terms( $content, $industry ) {
		$industry_data = $this->industries[ $industry ];
		
		// Add an industry keyword if not already present
		$has_keyword = false;
		foreach ( $industry_data['keywords'] as $keyword ) {
			if ( stripos( $content, $keyword ) !== false ) {
				$has_keyword = true;
				break;
			}
		}
		
		if ( ! $has_keyword && rand( 1, 3 ) === 1 ) {
			$keyword = $industry_data['keywords'][ array_rand( $industry_data['keywords'] ) ];
			$content = $this->insert_keyword( $content, $keyword );
		}
		
		return $content;
	}

	/**
	 * Insert keyword naturally into content.
	 *
	 * @since 1.0.0
	 * @param string $content Content.
	 * @param string $keyword Keyword to insert.
	 * @return string Content with keyword.
	 */
	private function insert_keyword( $content, $keyword ) {
		$insertions = array(
			' ' . $keyword . ' ',
			' ' . $keyword . '-focused ',
			' ' . $keyword . '-driven ',
			' leading ' . $keyword . ' ',
			' advanced ' . $keyword . ' '
		);
		
		$insertion = $insertions[ array_rand( $insertions ) ];
		
		// Find a good place to insert (after "our", "the", "with", etc.)
		$markers = array( ' our ', ' the ', ' with ', ' through ', ' using ' );
		foreach ( $markers as $marker ) {
			if ( stripos( $content, $marker ) !== false ) {
				$content = preg_replace( '/' . preg_quote( $marker, '/' ) . '/i', $marker . $insertion, $content, 1 );
				break;
			}
		}
		
		return $content;
	}

	/**
	 * Apply style variations.
	 *
	 * @since 1.0.0
	 * @param string $content Content.
	 * @param string $style   Style type.
	 * @return string Styled content.
	 */
	private function apply_style_variations( $content, $style ) {
		switch ( $style ) {
			case 'modern':
				// Short, punchy sentences
				$content = $this->shorten_sentences( $content );
				break;
				
			case 'classic':
				// More formal language
				$content = $this->formalize_language( $content );
				break;
				
			case 'creative':
				// Add creative flair
				$content = $this->add_creative_elements( $content );
				break;
				
			case 'minimal':
				// Remove unnecessary words
				$content = $this->minimize_content( $content );
				break;
		}
		
		return $content;
	}

	/**
	 * Shorten sentences for modern style.
	 *
	 * @since 1.0.0
	 * @param string $content Content.
	 * @return string Shortened content.
	 */
	private function shorten_sentences( $content ) {
		// Remove some conjunctions
		$content = str_replace( ' and also', '.', $content );
		$content = str_replace( ' as well as', '.', $content );
		$content = str_replace( ', which means', '.', $content );
		
		return $content;
	}

	/**
	 * Formalize language for classic style.
	 *
	 * @since 1.0.0
	 * @param string $content Content.
	 * @return string Formalized content.
	 */
	private function formalize_language( $content ) {
		$informal_to_formal = array(
			'get' => 'obtain',
			'got' => 'received',
			'big' => 'substantial',
			'lots of' => 'numerous',
			'a lot' => 'significantly'
		);
		
		foreach ( $informal_to_formal as $informal => $formal ) {
			$content = str_ireplace( $informal, $formal, $content );
		}
		
		return $content;
	}

	/**
	 * Add creative elements.
	 *
	 * @since 1.0.0
	 * @param string $content Content.
	 * @return string Creative content.
	 */
	private function add_creative_elements( $content ) {
		$creative_phrases = array(
			'Imagine ',
			'Picture this: ',
			'Think about ',
			'Consider ',
			'Envision '
		);
		
		// Add creative starter occasionally
		if ( rand( 1, 3 ) === 1 ) {
			$content = $creative_phrases[ array_rand( $creative_phrases ) ] . lcfirst( $content );
		}
		
		return $content;
	}

	/**
	 * Minimize content for minimal style.
	 *
	 * @since 1.0.0
	 * @param string $content Content.
	 * @return string Minimized content.
	 */
	private function minimize_content( $content ) {
		// Remove filler words
		$fillers = array( ' very ', ' really ', ' actually ', ' basically ', ' simply ' );
		foreach ( $fillers as $filler ) {
			$content = str_replace( $filler, ' ', $content );
		}
		
		// Clean up extra spaces
		$content = preg_replace( '/\s+/', ' ', $content );
		
		return trim( $content );
	}

	/**
	 * Personalize content based on options.
	 *
	 * @since 1.0.0
	 * @param string $content Content.
	 * @param array  $options Options.
	 * @return string Personalized content.
	 */
	private function personalize_content( $content, $options ) {
		// Add location-based personalization
		if ( isset( $options['location'] ) ) {
			$locations = array(
				'local' => 'in your area',
				'national' => 'across the country',
				'global' => 'worldwide'
			);
			
			if ( isset( $locations[ $options['location'] ] ) ) {
				$content .= ' ' . $locations[ $options['location'] ];
			}
		}
		
		// Add urgency if specified
		if ( isset( $options['urgency'] ) && $options['urgency'] ) {
			$urgency_phrases = array(
				' - Limited time offer!',
				' - Act now!',
				' - Don\'t miss out!',
				' - Available today!'
			);
			
			$content .= $urgency_phrases[ array_rand( $urgency_phrases ) ];
		}
		
		return $content;
	}

	/**
	 * Get generic content fallback.
	 *
	 * @since 1.0.0
	 * @param string $type Content type.
	 * @return string Generic content.
	 */
	private function get_generic_content( $type ) {
		$generic = array(
			'heading' => 'Welcome to Excellence',
			'paragraph' => 'Discover innovative solutions designed to help you achieve your goals and transform your business.',
			'feature' => 'Advanced capabilities that deliver real results and drive meaningful growth.',
			'benefit' => 'Experience the advantages of working with industry leaders committed to your success.',
			'cta' => 'Take the next step towards achieving your goals with our comprehensive solutions.'
		);
		
		return isset( $generic[ $type ] ) ? $generic[ $type ] : 'Quality content that makes a difference.';
	}

	/**
	 * Initialize content templates.
	 *
	 * @since 1.0.0
	 */
	private function init_content_templates() {
		$this->content_templates = array(
			'hero_heading' => array(
				'Transform Your {keyword} with {tone} Solutions',
				'Welcome to the Future of {keyword}',
				'Elevate Your {focus} to New Heights',
				'Discover {tone} {keyword} Excellence',
				'Your Partner in {focus}',
				'Redefining {keyword} for Tomorrow',
				'Experience {tone} Innovation',
				'Leading the Way in {focus}'
			),
			'hero_description' => array(
				'Join thousands who have transformed their approach to {focus} with our {tone} platform.',
				'Experience the power of {keyword} solutions designed with {focus} in mind.',
				'Unlock new possibilities with our {tone} approach to {keyword}.',
				'Where {focus} meets innovation for unprecedented results.',
				'Empowering businesses with {tone} {keyword} solutions since day one.',
				'Your journey to {focus} excellence starts here.',
				'Discover how our {tone} platform revolutionizes {keyword}.',
				'Built for those who demand excellence in {focus}.'
			),
			'feature_heading' => array(
				'{tone} {keyword} Solutions',
				'Advanced {keyword} Features',
				'{focus} at Its Best',
				'Powerful {keyword} Tools',
				'{tone} Performance',
				'Next-Gen {keyword}',
				'Superior {focus}',
				'Innovative {keyword} Technology'
			),
			'feature_description' => array(
				'Our {tone} approach to {keyword} delivers exceptional results for {focus}.',
				'Experience {keyword} solutions that prioritize {focus} above all else.',
				'Built with {tone} precision to maximize your {keyword} potential.',
				'Designed for professionals who demand excellence in {focus}.',
				'Leverage our {tone} {keyword} capabilities for superior outcomes.',
				'Where {focus} meets cutting-edge {keyword} innovation.',
				'Achieve more with our {tone} approach to {keyword}.',
				'Setting new standards in {focus} with advanced {keyword} features.'
			),
			'about_content' => array(
				'We are leaders in {focus}, bringing {tone} solutions to the {keyword} industry.',
				'Our mission is simple: deliver {tone} {keyword} solutions that drive {focus}.',
				'With years of experience in {keyword}, we understand what it takes to excel in {focus}.',
				'Founded on principles of innovation and excellence, we specialize in {tone} {keyword} solutions.',
				'Our {tone} team is dedicated to revolutionizing {focus} through advanced {keyword} technology.',
				'We believe in the power of {keyword} to transform {focus} for businesses everywhere.',
				'Committed to {tone} service and exceptional {keyword} solutions since our founding.',
				'Your trusted partner for {focus}, delivering {tone} {keyword} excellence every day.'
			),
			'cta_heading' => array(
				'Ready to Transform Your {keyword}?',
				'Start Your {focus} Journey Today',
				'Experience {tone} Excellence Now',
				'Join the {keyword} Revolution',
				'Unlock Your {focus} Potential',
				'Begin Your {tone} Transformation',
				'Discover the Power of {keyword}',
				'Take Your {focus} to the Next Level'
			),
			'cta_description' => array(
				'Join thousands already experiencing the benefits of our {tone} {keyword} platform.',
				'Start your free trial and see how {focus} can transform your business.',
				'No credit card required. Experience {tone} {keyword} solutions today.',
				'Get instant access to our {focus} platform and start seeing results.',
				'Transform your approach to {keyword} with our {tone} solutions.',
				'Don\'t wait - your {focus} success story starts now.',
				'See why leaders choose our {tone} approach to {keyword}.',
				'Ready to revolutionize your {focus}? We\'re here to help.'
			)
		);
	}

	/**
	 * Generate random company name.
	 *
	 * @since 1.0.0
	 * @return string Company name.
	 */
	public function generate_company_name() {
		$prefixes = array( 'Tech', 'Digital', 'Global', 'Prime', 'Next', 'Smart', 'Elite', 'Pro' );
		$suffixes = array( 'Solutions', 'Systems', 'Group', 'Labs', 'Works', 'Hub', 'Co', 'Partners' );
		
		return $prefixes[ array_rand( $prefixes ) ] . $suffixes[ array_rand( $suffixes ) ];
	}

	/**
	 * Generate random testimonial.
	 *
	 * @since 1.0.0
	 * @param array $options Options.
	 * @return array Testimonial data.
	 */
	public function generate_testimonial( $options = array() ) {
		$testimonials = array(
			'This platform has completely transformed how we approach {focus}. The results speak for themselves!',
			'The {tone} support and powerful features make this the best {keyword} solution we\'ve ever used.',
			'We\'ve seen incredible growth since implementing this {keyword} platform. Highly recommended!',
			'Outstanding {focus} capabilities combined with exceptional customer service. A game-changer!',
			'The intuitive interface and {tone} features have revolutionized our {keyword} processes.',
			'Finally, a {keyword} solution that delivers on its promises. Our {focus} has never been better.',
			'Impressed by the {tone} approach and tangible results. This is what {keyword} should be.',
			'From day one, this platform has exceeded our expectations for {focus}. Simply amazing!'
		);
		
		$names = array(
			array( 'first' => 'Sarah', 'last' => 'Johnson' ),
			array( 'first' => 'Michael', 'last' => 'Chen' ),
			array( 'first' => 'Emily', 'last' => 'Williams' ),
			array( 'first' => 'David', 'last' => 'Brown' ),
			array( 'first' => 'Lisa', 'last' => 'Davis' ),
			array( 'first' => 'James', 'last' => 'Wilson' ),
			array( 'first' => 'Maria', 'last' => 'Garcia' ),
			array( 'first' => 'Robert', 'last' => 'Taylor' )
		);
		
		$titles = array( 'CEO', 'CTO', 'Director', 'Manager', 'Founder', 'VP', 'Head of Operations', 'President' );
		
		$industry = $this->get_random_industry( $options );
		$industry_data = $this->industries[ $industry ];
		
		$testimonial = $testimonials[ array_rand( $testimonials ) ];
		$testimonial = str_replace( '{focus}', $industry_data['focus'], $testimonial );
		$testimonial = str_replace( '{tone}', $industry_data['tone'], $testimonial );
		$testimonial = str_replace( '{keyword}', $industry_data['keywords'][ array_rand( $industry_data['keywords'] ) ], $testimonial );
		
		$name = $names[ array_rand( $names ) ];
		
		return array(
			'text' => $testimonial,
			'name' => $name['first'] . ' ' . $name['last'],
			'title' => $titles[ array_rand( $titles ) ],
			'company' => $this->generate_company_name()
		);
	}
}