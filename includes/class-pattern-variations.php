<?php
/**
 * Pattern variations class for dynamic pattern generation.
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
 * Pattern variations class.
 *
 * @since 1.0.0
 */
class Pattern_Variations {

	/**
	 * Available gradients for cover blocks.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $gradients = array(
		'vivid-cyan-blue-to-vivid-purple',
		'light-green-cyan-to-vivid-green-cyan',
		'luminous-vivid-amber-to-luminous-vivid-orange',
		'luminous-vivid-orange-to-vivid-red',
		'very-light-gray-to-cyan-bluish-gray',
		'cool-to-warm-spectrum',
		'blush-light-purple',
		'blush-bordeaux',
		'luminous-dusk',
		'pale-ocean',
		'electric-grass',
		'midnight',
		'purple-crush',
		'blue-and-orange',
		'red-to-transparent'
	);

	/**
	 * Available button styles.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $button_styles = array(
		'fill' => array(
			'primary' => 'backgroundColor',
			'styles' => array( 'default', 'rounded' )
		),
		'outline' => array(
			'primary' => 'textColor',
			'styles' => array( 'is-style-outline' )
		)
	);

	/**
	 * Available alignments.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $alignments = array( 'left', 'center', 'right' );

	/**
	 * Get hero pattern variation.
	 *
	 * @since 1.0.0
	 * @param array $options Pattern options.
	 * @return string Pattern markup.
	 */
	public function get_hero_variation( $options = array() ) {
		$gradient = $this->get_random_gradient();
		$alignment = $this->get_random_alignment();
		$min_height = $this->get_random_height( 500, 700 );
		$buttons = $this->get_random_button_count();
		
		$pattern = '<!-- wp:cover {"gradient":"' . $gradient . '","dimRatio":' . rand( 30, 70 ) . ',"minHeight":' . $min_height . ',"align":"full"} -->
<div class="wp-block-cover alignfull" style="min-height:' . $min_height . 'px"><div class="wp-block-cover__inner-container">';
		
		// Add heading
		$heading_level = rand( 1, 2 );
		$pattern .= '<!-- wp:heading {"textAlign":"' . $alignment . '","level":' . $heading_level . ',"textColor":"white","fontSize":"' . $this->get_random_font_size( 'heading' ) . '"} -->
<h' . $heading_level . ' class="wp-block-heading has-text-align-' . $alignment . ' has-white-color has-text-color has-' . $this->get_random_font_size( 'heading' ) . '-font-size">' . $this->get_hero_heading() . '</h' . $heading_level . '>
<!-- /wp:heading -->';

		// Add description
		$pattern .= '

<!-- wp:paragraph {"align":"' . $alignment . '","textColor":"white","fontSize":"' . $this->get_random_font_size( 'paragraph' ) . '"} -->
<p class="has-text-align-' . $alignment . ' has-white-color has-text-color has-' . $this->get_random_font_size( 'paragraph' ) . '-font-size">' . $this->get_hero_description() . '</p>
<!-- /wp:paragraph -->';

		// Add spacer
		$pattern .= '

<!-- wp:spacer {"height":"' . rand( 20, 40 ) . 'px"} -->
<div style="height:' . rand( 20, 40 ) . 'px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->';

		// Add buttons
		$pattern .= '

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"' . $alignment . '"}} -->
<div class="wp-block-buttons">';

		for ( $i = 0; $i < $buttons; $i++ ) {
			$pattern .= $this->get_button_variation( $i === 0 ? 'primary' : 'secondary' );
		}

		$pattern .= '</div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover -->';

		return $pattern;
	}

	/**
	 * Get features pattern variation.
	 *
	 * @since 1.0.0
	 * @param array $options Pattern options.
	 * @return string Pattern markup.
	 */
	public function get_features_variation( $options = array() ) {
		$columns = isset( $options['columns'] ) ? intval( $options['columns'] ) : rand( 3, 4 );
		$with_icons = isset( $options['with_icons'] ) ? $options['with_icons'] : ( rand( 0, 1 ) === 1 );
		$alignment = $this->get_random_alignment();
		
		$pattern = '<!-- wp:group {"align":"wide"} -->
<div class="wp-block-group alignwide">';
		
		// Add section heading
		$pattern .= '<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">' . $this->get_section_heading( 'features' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">' . $this->get_section_description( 'features' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"40px"} -->
<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->';

		// Add columns
		$pattern .= '

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide">';

		for ( $i = 0; $i < $columns; $i++ ) {
			$pattern .= '<!-- wp:column -->
<div class="wp-block-column">';
			
			if ( $with_icons ) {
				$pattern .= '<!-- wp:image {"width":"64px","height":"64px","sizeSlug":"large","align":"center"} -->
<figure class="wp-block-image aligncenter size-large is-resized"><img src="' . $this->get_icon_url( $i + 1 ) . '" alt="Feature ' . ( $i + 1 ) . ' icon" style="width:64px;height:64px"/></figure>
<!-- /wp:image -->

';
			}

			$pattern .= '<!-- wp:heading {"textAlign":"' . $alignment . '","level":3} -->
<h3 class="wp-block-heading has-text-align-' . $alignment . '">' . $this->get_feature_heading( $i ) . '</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"' . $alignment . '"} -->
<p class="has-text-align-' . $alignment . '">' . $this->get_feature_description( $i ) . '</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

';
		}

		$pattern = rtrim( $pattern ) . '</div>
<!-- /wp:columns --></div>
<!-- /wp:group -->';

		return $pattern;
	}

	/**
	 * Get CTA pattern variation.
	 *
	 * @since 1.0.0
	 * @param array $options Pattern options.
	 * @return string Pattern markup.
	 */
	public function get_cta_variation( $options = array() ) {
		$use_gradient = rand( 0, 1 ) === 1;
		$alignment = 'center'; // CTAs are usually centered
		
		if ( $use_gradient ) {
			$gradient = $this->get_random_gradient();
			$pattern = '<!-- wp:group {"align":"full","gradient":"' . $gradient . '"} -->
<div class="wp-block-group alignfull has-background has-' . $gradient . '-gradient-background">';
		} else {
			$colors = array( 'black', 'primary', 'secondary', 'tertiary', 'vivid-cyan-blue' );
			$bg_color = $colors[ array_rand( $colors ) ];
			$pattern = '<!-- wp:group {"align":"full","backgroundColor":"' . $bg_color . '","textColor":"white"} -->
<div class="wp-block-group alignfull has-white-color has-' . $bg_color . '-background-color has-text-color has-background">';
		}

		$pattern .= '<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"textAlign":"center","textColor":"white"} -->
<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color">' . $this->get_cta_heading() . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"white"} -->
<p class="has-text-align-center has-white-color has-text-color">' . $this->get_cta_description() . '</p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"20px"} -->
<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">';

		// Add 1-2 buttons
		$button_count = rand( 1, 2 );
		for ( $i = 0; $i < $button_count; $i++ ) {
			$pattern .= $this->get_button_variation( $i === 0 ? 'cta-primary' : 'cta-secondary' );
		}

		$pattern .= '</div>
<!-- /wp:buttons -->

<!-- wp:spacer {"height":"60px"} -->
<div style="height:60px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->';

		return $pattern;
	}

	/**
	 * Get random gradient.
	 *
	 * @since 1.0.0
	 * @return string Gradient name.
	 */
	private function get_random_gradient() {
		return $this->gradients[ array_rand( $this->gradients ) ];
	}

	/**
	 * Get random alignment.
	 *
	 * @since 1.0.0
	 * @return string Alignment.
	 */
	private function get_random_alignment() {
		// Weighted towards center
		$weights = array( 'left' => 2, 'center' => 5, 'right' => 2 );
		$rand = rand( 1, 9 );
		
		if ( $rand <= 2 ) {
			return 'left';
		} elseif ( $rand <= 7 ) {
			return 'center';
		} else {
			return 'right';
		}
	}

	/**
	 * Get random height.
	 *
	 * @since 1.0.0
	 * @param int $min Minimum height.
	 * @param int $max Maximum height.
	 * @return int Height value.
	 */
	private function get_random_height( $min = 400, $max = 700 ) {
		return rand( $min / 50, $max / 50 ) * 50; // Round to nearest 50
	}

	/**
	 * Get random button count.
	 *
	 * @since 1.0.0
	 * @return int Button count.
	 */
	private function get_random_button_count() {
		// Weighted towards 1-2 buttons
		$rand = rand( 1, 10 );
		if ( $rand <= 4 ) {
			return 1;
		} elseif ( $rand <= 9 ) {
			return 2;
		} else {
			return 3;
		}
	}

	/**
	 * Get random font size.
	 *
	 * @since 1.0.0
	 * @param string $type Element type.
	 * @return string Font size slug.
	 */
	private function get_random_font_size( $type = 'paragraph' ) {
		if ( $type === 'heading' ) {
			$sizes = array( 'large', 'x-large', 'xx-large', 'huge' );
		} else {
			$sizes = array( 'small', 'medium', 'large' );
		}
		
		return $sizes[ array_rand( $sizes ) ];
	}

	/**
	 * Get button variation.
	 *
	 * @since 1.0.0
	 * @param string $type Button type.
	 * @return string Button markup.
	 */
	private function get_button_variation( $type = 'primary' ) {
		$button_texts = $this->get_button_texts( $type );
		$text = $button_texts[ array_rand( $button_texts ) ];
		
		$padding = $this->get_button_padding();
		
		if ( $type === 'primary' || $type === 'cta-primary' ) {
			$colors = array( 'primary', 'vivid-cyan-blue', 'vivid-purple', 'luminous-vivid-orange' );
			if ( $type === 'cta-primary' ) {
				$colors[] = 'white';
			}
			$color = $colors[ array_rand( $colors ) ];
			
			$button = '<!-- wp:button {"backgroundColor":"' . $color . '","textColor":"' . ( $color === 'white' ? 'black' : 'white' ) . '","style":{"spacing":{"padding":' . $padding . '}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-' . ( $color === 'white' ? 'black' : 'white' ) . '-color has-' . $color . '-background-color has-text-color has-background wp-element-button" style="' . $this->format_padding_style( $padding ) . '">' . $text . '</a></div>
<!-- /wp:button -->';
		} else {
			$colors = array( 'primary', 'white', 'secondary' );
			$color = $colors[ array_rand( $colors ) ];
			
			$button = '<!-- wp:button {"textColor":"' . $color . '","className":"is-style-outline","style":{"spacing":{"padding":' . $padding . '}}} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-' . $color . '-color has-text-color wp-element-button" style="' . $this->format_padding_style( $padding ) . '">' . $text . '</a></div>
<!-- /wp:button -->';
		}
		
		return $button;
	}

	/**
	 * Get button padding.
	 *
	 * @since 1.0.0
	 * @return string Padding JSON.
	 */
	private function get_button_padding() {
		$vertical = rand( 12, 20 );
		$horizontal = rand( 24, 48 );
		
		return '{"top":"' . $vertical . 'px","bottom":"' . $vertical . 'px","left":"' . $horizontal . 'px","right":"' . $horizontal . 'px"}';
	}

	/**
	 * Format padding style.
	 *
	 * @since 1.0.0
	 * @param string $padding Padding JSON.
	 * @return string CSS style.
	 */
	private function format_padding_style( $padding ) {
		$decoded = json_decode( $padding, true );
		if ( ! is_array( $decoded ) ) {
			return '';
		}
		
		return 'padding-top:' . $decoded['top'] . ';padding-right:' . $decoded['right'] . ';padding-bottom:' . $decoded['bottom'] . ';padding-left:' . $decoded['left'];
	}

	/**
	 * Get button texts.
	 *
	 * @since 1.0.0
	 * @param string $type Button type.
	 * @return array Button texts.
	 */
	private function get_button_texts( $type ) {
		$texts = array(
			'primary' => array(
				'Get Started',
				'Start Now',
				'Begin Today',
				'Get Started Free',
				'Try It Now',
				'Start Your Journey',
				'Begin Here'
			),
			'secondary' => array(
				'Learn More',
				'Discover More',
				'Read More',
				'Explore Features',
				'See How It Works',
				'View Details',
				'Find Out More'
			),
			'cta-primary' => array(
				'Start Free Trial',
				'Get Started Today',
				'Sign Up Now',
				'Join Now',
				'Create Account',
				'Get Access',
				'Start Now'
			),
			'cta-secondary' => array(
				'Contact Sales',
				'Schedule Demo',
				'Talk to Us',
				'Get in Touch',
				'Request Info',
				'Learn More',
				'See Pricing'
			)
		);
		
		return isset( $texts[ $type ] ) ? $texts[ $type ] : $texts['primary'];
	}

	/**
	 * Get hero heading.
	 *
	 * @since 1.0.0
	 * @return string Heading text.
	 */
	private function get_hero_heading() {
		$headings = array(
			'Transform Your Business Today',
			'Welcome to the Future',
			'Empowering Your Success',
			'Innovation Starts Here',
			'Your Journey Begins Now',
			'Unlock Your Potential',
			'Experience Excellence',
			'Redefine What\'s Possible',
			'Build Something Amazing',
			'Create Without Limits'
		);
		
		return $headings[ array_rand( $headings ) ];
	}

	/**
	 * Get hero description.
	 *
	 * @since 1.0.0
	 * @return string Description text.
	 */
	private function get_hero_description() {
		$descriptions = array(
			'Discover powerful solutions designed to accelerate your growth and achieve remarkable results.',
			'Join thousands of satisfied customers who have revolutionized their workflow with our platform.',
			'Experience the perfect blend of innovation, reliability, and exceptional performance.',
			'Take your business to new heights with our cutting-edge technology and expert support.',
			'Streamline your processes and unlock new opportunities for growth and success.',
			'Empower your team with tools that inspire creativity and drive meaningful results.',
			'Transform the way you work with intelligent solutions built for modern businesses.',
			'Achieve more with less effort using our intuitive and powerful platform.',
			'Connect, collaborate, and create with confidence using our comprehensive suite of tools.',
			'Step into a world of endless possibilities and unprecedented growth.'
		);
		
		return $descriptions[ array_rand( $descriptions ) ];
	}

	/**
	 * Get section heading.
	 *
	 * @since 1.0.0
	 * @param string $section Section type.
	 * @return string Heading text.
	 */
	private function get_section_heading( $section ) {
		$headings = array(
			'features' => array(
				'Our Features',
				'What We Offer',
				'Key Features',
				'Why Choose Us',
				'What Makes Us Different',
				'Our Advantages',
				'Core Features',
				'Platform Features'
			),
			'services' => array(
				'Our Services',
				'What We Do',
				'Service Offerings',
				'How We Help',
				'Our Solutions',
				'Professional Services',
				'Our Expertise',
				'Service Portfolio'
			),
			'about' => array(
				'About Us',
				'Our Story',
				'Who We Are',
				'Our Mission',
				'Get to Know Us',
				'Our Journey',
				'Company Overview',
				'Our Vision'
			)
		);
		
		$section_headings = isset( $headings[ $section ] ) ? $headings[ $section ] : $headings['features'];
		return $section_headings[ array_rand( $section_headings ) ];
	}

	/**
	 * Get section description.
	 *
	 * @since 1.0.0
	 * @param string $section Section type.
	 * @return string Description text.
	 */
	private function get_section_description( $section ) {
		$descriptions = array(
			'features' => array(
				'Discover the powerful features that set us apart',
				'Everything you need to succeed in one place',
				'Built with your success in mind',
				'Designed to help you achieve more',
				'Features that make a difference',
				'Tools to power your growth',
				'Capabilities that drive results',
				'Innovation at every level'
			)
		);
		
		$section_descriptions = isset( $descriptions[ $section ] ) ? $descriptions[ $section ] : $descriptions['features'];
		return $section_descriptions[ array_rand( $section_descriptions ) ];
	}

	/**
	 * Get feature heading.
	 *
	 * @since 1.0.0
	 * @param int $index Feature index.
	 * @return string Heading text.
	 */
	private function get_feature_heading( $index ) {
		$features = array(
			array( 'Lightning Fast', 'Blazing Speed', 'High Performance', 'Optimized Speed' ),
			array( 'Secure & Reliable', 'Enterprise Security', 'Always Secure', 'Protected Data' ),
			array( '24/7 Support', 'Expert Support', 'Always Available', 'Dedicated Help' ),
			array( 'Easy Integration', 'Seamless Setup', 'Quick Start', 'Simple Integration' ),
			array( 'Scalable Solution', 'Grows With You', 'Unlimited Scale', 'Flexible Growth' ),
			array( 'Advanced Analytics', 'Deep Insights', 'Smart Analytics', 'Data Intelligence' )
		);
		
		$feature_set = $features[ $index % count( $features ) ];
		return $feature_set[ array_rand( $feature_set ) ];
	}

	/**
	 * Get feature description.
	 *
	 * @since 1.0.0
	 * @param int $index Feature index.
	 * @return string Description text.
	 */
	private function get_feature_description( $index ) {
		$descriptions = array(
			'Experience unmatched performance with our optimized infrastructure designed for speed and reliability.',
			'Rest easy knowing your data is protected by industry-leading security measures and encryption.',
			'Get help whenever you need it with our dedicated support team available around the clock.',
			'Get up and running in minutes with our intuitive setup process and comprehensive documentation.',
			'Scale your operations effortlessly as your business grows without worrying about limitations.',
			'Make informed decisions with powerful analytics and insights at your fingertips.'
		);
		
		return $descriptions[ $index % count( $descriptions ) ];
	}

	/**
	 * Get CTA heading.
	 *
	 * @since 1.0.0
	 * @return string Heading text.
	 */
	private function get_cta_heading() {
		$headings = array(
			'Ready to Get Started?',
			'Start Your Free Trial Today',
			'Join Thousands of Happy Customers',
			'Take the Next Step',
			'Transform Your Business Today',
			'Get Started in Minutes',
			'Experience the Difference',
			'Ready to Transform Your Workflow?',
			'Join Our Growing Community',
			'Start Building Today'
		);
		
		return $headings[ array_rand( $headings ) ];
	}

	/**
	 * Get CTA description.
	 *
	 * @since 1.0.0
	 * @return string Description text.
	 */
	private function get_cta_description() {
		$descriptions = array(
			'No credit card required. Start your free trial today.',
			'Join thousands of satisfied customers and transform your business.',
			'Get instant access to all features with our free trial.',
			'See why thousands choose us for their business needs.',
			'Start now and see results in minutes, not months.',
			'Experience the power of our platform with no commitment.',
			'Your success story starts here. Join us today.',
			'Take advantage of our limited-time offer and get started now.',
			'Unlock your full potential with our comprehensive solution.',
			'Begin your journey to success with just one click.'
		);
		
		return $descriptions[ array_rand( $descriptions ) ];
	}

	/**
	 * Get icon URL.
	 *
	 * @since 1.0.0
	 * @param int $number Icon number.
	 * @return string Icon URL.
	 */
	private function get_icon_url( $number ) {
		$colors = array( '007cba', '0073aa', '005177', '00669b', '0085ba' );
		$color = $colors[ array_rand( $colors ) ];
		
		return 'https://placehold.co/64x64/' . $color . '/ffffff?text=' . $number;
	}
}