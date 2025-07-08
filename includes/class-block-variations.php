<?php
/**
 * Block variations class for creating dynamic block-level variations.
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
 * Block variations class.
 *
 * @since 1.0.0
 */
class Block_Variations {

	/**
	 * Get heading block variation.
	 *
	 * @since 1.0.0
	 * @param array $options Block options.
	 * @return array Block data.
	 */
	public function get_heading_variation( $options = array() ) {
		$level = isset( $options['level'] ) ? intval( $options['level'] ) : rand( 1, 3 );
		$alignment = isset( $options['alignment'] ) ? $options['alignment'] : $this->get_random_alignment();
		$text = isset( $options['text'] ) ? $options['text'] : $this->generate_heading_text( $options );
		
		$attrs = array(
			'level' => $level,
			'textAlign' => $alignment
		);
		
		// Add color variations
		if ( isset( $options['color'] ) ) {
			$attrs['textColor'] = $options['color'];
		} elseif ( rand( 0, 2 ) === 0 ) {
			$colors = array( 'primary', 'secondary', 'tertiary', 'black' );
			$attrs['textColor'] = $colors[ array_rand( $colors ) ];
		}
		
		// Add font size variations
		if ( $level === 1 ) {
			$sizes = array( 'x-large', 'xx-large', 'huge' );
		} elseif ( $level === 2 ) {
			$sizes = array( 'large', 'x-large' );
		} else {
			$sizes = array( 'medium', 'large' );
		}
		$attrs['fontSize'] = $sizes[ array_rand( $sizes ) ];
		
		return array(
			'blockName' => 'core/heading',
			'attrs' => $attrs,
			'innerBlocks' => array(),
			'innerHTML' => '<h' . $level . ' class="wp-block-heading' . $this->build_heading_classes( $attrs ) . '">' . esc_html( $text ) . '</h' . $level . '>',
			'innerContent' => array( '<h' . $level . ' class="wp-block-heading' . $this->build_heading_classes( $attrs ) . '">' . esc_html( $text ) . '</h' . $level . '>' )
		);
	}

	/**
	 * Get paragraph block variation.
	 *
	 * @since 1.0.0
	 * @param array $options Block options.
	 * @return array Block data.
	 */
	public function get_paragraph_variation( $options = array() ) {
		$alignment = isset( $options['alignment'] ) ? $options['alignment'] : $this->get_random_alignment( 'paragraph' );
		$text = isset( $options['text'] ) ? $options['text'] : $this->generate_paragraph_text( $options );
		
		$attrs = array();
		
		if ( $alignment !== 'none' ) {
			$attrs['align'] = $alignment;
		}
		
		// Add color variations
		if ( rand( 0, 3 ) === 0 ) {
			$bg_colors = array( 'pale-cyan-blue', 'pale-pink', 'luminous-vivid-amber', 'light-green-cyan' );
			$attrs['backgroundColor'] = $bg_colors[ array_rand( $bg_colors ) ];
			$attrs['textColor'] = 'black';
		}
		
		// Add font size variations
		if ( isset( $options['fontSize'] ) ) {
			$attrs['fontSize'] = $options['fontSize'];
		} elseif ( rand( 0, 2 ) === 0 ) {
			$sizes = array( 'small', 'medium', 'large' );
			$attrs['fontSize'] = $sizes[ array_rand( $sizes ) ];
		}
		
		// Add drop cap occasionally
		if ( isset( $options['dropCap'] ) ) {
			$attrs['dropCap'] = $options['dropCap'];
		} elseif ( rand( 0, 10 ) === 0 ) {
			$attrs['dropCap'] = true;
		}
		
		return array(
			'blockName' => 'core/paragraph',
			'attrs' => $attrs,
			'innerBlocks' => array(),
			'innerHTML' => '<p class="' . $this->build_paragraph_classes( $attrs ) . '">' . $text . '</p>',
			'innerContent' => array( '<p class="' . $this->build_paragraph_classes( $attrs ) . '">' . $text . '</p>' )
		);
	}

	/**
	 * Get image block variation.
	 *
	 * @since 1.0.0
	 * @param array $options Block options.
	 * @return array Block data.
	 */
	public function get_image_variation( $options = array() ) {
		$alignment = isset( $options['alignment'] ) ? $options['alignment'] : $this->get_random_alignment( 'image' );
		$size = isset( $options['size'] ) ? $options['size'] : $this->get_random_image_size();
		
		$attrs = array(
			'sizeSlug' => $size['slug']
		);
		
		if ( $alignment !== 'none' ) {
			$attrs['align'] = $alignment;
		}
		
		// Get appropriate image URL
		$attrs['url'] = $this->get_image_url( $size, $options );
		$attrs['alt'] = isset( $options['alt'] ) ? $options['alt'] : $this->generate_image_alt( $options );
		
		// Add caption occasionally
		$caption = '';
		if ( isset( $options['caption'] ) ) {
			$caption = $options['caption'];
		} elseif ( rand( 0, 3 ) === 0 ) {
			$caption = $this->generate_image_caption();
		}
		
		// Add link occasionally
		if ( rand( 0, 4 ) === 0 ) {
			$attrs['linkDestination'] = 'custom';
			$attrs['href'] = '#';
		}
		
		// Build HTML
		$html = '<figure class="wp-block-image' . ( $alignment !== 'none' ? ' align' . $alignment : '' ) . ' size-' . $size['slug'] . '">';
		$html .= '<img src="' . esc_url( $attrs['url'] ) . '" alt="' . esc_attr( $attrs['alt'] ) . '"';
		
		if ( isset( $size['width'] ) && isset( $size['height'] ) ) {
			$html .= ' width="' . $size['width'] . '" height="' . $size['height'] . '"';
		}
		
		$html .= '/>';
		
		if ( ! empty( $caption ) ) {
			$html .= '<figcaption class="wp-element-caption">' . esc_html( $caption ) . '</figcaption>';
		}
		
		$html .= '</figure>';
		
		return array(
			'blockName' => 'core/image',
			'attrs' => $attrs,
			'innerBlocks' => array(),
			'innerHTML' => $html,
			'innerContent' => array( $html )
		);
	}

	/**
	 * Get button block variation.
	 *
	 * @since 1.0.0
	 * @param array $options Block options.
	 * @return array Block data.
	 */
	public function get_button_variation( $options = array() ) {
		$style = isset( $options['style'] ) ? $options['style'] : ( rand( 0, 2 ) === 0 ? 'outline' : 'fill' );
		$text = isset( $options['text'] ) ? $options['text'] : $this->generate_button_text( $options );
		
		$attrs = array();
		
		if ( $style === 'outline' ) {
			$attrs['className'] = 'is-style-outline';
			$colors = array( 'primary', 'secondary', 'tertiary' );
			$attrs['textColor'] = $colors[ array_rand( $colors ) ];
		} else {
			$colors = array( 'primary', 'vivid-cyan-blue', 'vivid-purple', 'luminous-vivid-orange' );
			$attrs['backgroundColor'] = $colors[ array_rand( $colors ) ];
			$attrs['textColor'] = 'white';
		}
		
		// Add padding variations
		$padding_variations = array(
			array( 'top' => '12px', 'bottom' => '12px', 'left' => '24px', 'right' => '24px' ),
			array( 'top' => '15px', 'bottom' => '15px', 'left' => '30px', 'right' => '30px' ),
			array( 'top' => '18px', 'bottom' => '18px', 'left' => '36px', 'right' => '36px' ),
			array( 'top' => '20px', 'bottom' => '20px', 'left' => '40px', 'right' => '40px' )
		);
		
		$attrs['style'] = array(
			'spacing' => array(
				'padding' => $padding_variations[ array_rand( $padding_variations ) ]
			)
		);
		
		// Add border radius occasionally
		if ( rand( 0, 3 ) === 0 ) {
			$attrs['style']['border'] = array(
				'radius' => rand( 4, 12 ) . 'px'
			);
		}
		
		return array(
			'blockName' => 'core/button',
			'attrs' => $attrs,
			'innerBlocks' => array(),
			'innerHTML' => $this->build_button_html( $attrs, $text ),
			'innerContent' => array( $this->build_button_html( $attrs, $text ) )
		);
	}

	/**
	 * Get spacer block variation.
	 *
	 * @since 1.0.0
	 * @param array $options Block options.
	 * @return array Block data.
	 */
	public function get_spacer_variation( $options = array() ) {
		$heights = array( 20, 30, 40, 50, 60, 80, 100 );
		$height = isset( $options['height'] ) ? intval( $options['height'] ) : $heights[ array_rand( $heights ) ];
		
		$attrs = array(
			'height' => $height . 'px'
		);
		
		return array(
			'blockName' => 'core/spacer',
			'attrs' => $attrs,
			'innerBlocks' => array(),
			'innerHTML' => '<div style="height:' . $height . 'px" aria-hidden="true" class="wp-block-spacer"></div>',
			'innerContent' => array( '<div style="height:' . $height . 'px" aria-hidden="true" class="wp-block-spacer"></div>' )
		);
	}

	/**
	 * Get separator block variation.
	 *
	 * @since 1.0.0
	 * @param array $options Block options.
	 * @return array Block data.
	 */
	public function get_separator_variation( $options = array() ) {
		$styles = array( 'default', 'wide', 'dots' );
		$style = isset( $options['style'] ) ? $options['style'] : $styles[ array_rand( $styles ) ];
		
		$attrs = array();
		
		if ( $style !== 'default' ) {
			$attrs['className'] = 'is-style-' . $style;
		}
		
		// Add color occasionally
		if ( rand( 0, 3 ) === 0 ) {
			$colors = array( 'cyan-bluish-gray', 'light-gray', 'primary' );
			$attrs['color'] = array(
				'background' => $colors[ array_rand( $colors ) ]
			);
		}
		
		$classes = 'wp-block-separator has-alpha-channel-opacity';
		if ( isset( $attrs['className'] ) ) {
			$classes .= ' ' . $attrs['className'];
		}
		
		return array(
			'blockName' => 'core/separator',
			'attrs' => $attrs,
			'innerBlocks' => array(),
			'innerHTML' => '<hr class="' . $classes . '"/>',
			'innerContent' => array( '<hr class="' . $classes . '"/>' )
		);
	}

	/**
	 * Get random alignment.
	 *
	 * @since 1.0.0
	 * @param string $context Block context.
	 * @return string Alignment.
	 */
	private function get_random_alignment( $context = 'general' ) {
		if ( $context === 'paragraph' ) {
			// Paragraphs are often left-aligned or centered
			$weights = array( 'none' => 4, 'left' => 3, 'center' => 2, 'right' => 1 );
		} elseif ( $context === 'image' ) {
			// Images should favor wide/full alignments for modern layouts
			$weights = array( 'none' => 1, 'left' => 1, 'center' => 2, 'right' => 1, 'wide' => 4, 'full' => 3 );
		} elseif ( $context === 'section' || $context === 'container' ) {
			// Section containers should almost always be full width
			$weights = array( 'full' => 8, 'wide' => 2 );
		} else {
			// General alignment
			$weights = array( 'left' => 2, 'center' => 5, 'right' => 2 );
		}
		
		$total = array_sum( $weights );
		$rand = rand( 1, $total );
		$current = 0;
		
		foreach ( $weights as $alignment => $weight ) {
			$current += $weight;
			if ( $rand <= $current ) {
				return $alignment;
			}
		}
		
		return 'center';
	}

	/**
	 * Get random image size.
	 *
	 * @since 1.0.0
	 * @return array Size data.
	 */
	private function get_random_image_size() {
		$sizes = array(
			array( 'slug' => 'thumbnail', 'width' => 150, 'height' => 150 ),
			array( 'slug' => 'medium', 'width' => 300, 'height' => 300 ),
			array( 'slug' => 'large', 'width' => 1024, 'height' => 768 ),
			array( 'slug' => 'full', 'width' => 1600, 'height' => 1200 )
		);
		
		return $sizes[ array_rand( $sizes ) ];
	}

	/**
	 * Generate heading text.
	 *
	 * @since 1.0.0
	 * @param array $options Generation options.
	 * @return string Heading text.
	 */
	private function generate_heading_text( $options ) {
		if ( isset( $options['context'] ) ) {
			$context = $options['context'];
			
			$headings = array(
				'hero' => array(
					'Transform Your Digital Presence',
					'Welcome to Innovation',
					'Build Something Amazing',
					'Your Success Starts Here',
					'Empowering Your Vision'
				),
				'features' => array(
					'Powerful Features',
					'Why Choose Us',
					'What We Offer',
					'Our Capabilities',
					'Key Benefits'
				),
				'about' => array(
					'Our Story',
					'About Our Mission',
					'Who We Are',
					'Our Journey',
					'Company Overview'
				),
				'services' => array(
					'Our Services',
					'What We Do',
					'Service Excellence',
					'Professional Solutions',
					'How We Help'
				),
				'cta' => array(
					'Ready to Get Started?',
					'Take the Next Step',
					'Join Us Today',
					'Start Your Journey',
					'Get Started Now'
				)
			);
			
			if ( isset( $headings[ $context ] ) ) {
				return $headings[ $context ][ array_rand( $headings[ $context ] ) ];
			}
		}
		
		// Default headings
		$defaults = array(
			'Innovative Solutions',
			'Excellence in Every Detail',
			'Your Partner in Success',
			'Leading the Way Forward',
			'Trusted by Thousands'
		);
		
		return $defaults[ array_rand( $defaults ) ];
	}

	/**
	 * Generate paragraph text.
	 *
	 * @since 1.0.0
	 * @param array $options Generation options.
	 * @return string Paragraph text.
	 */
	private function generate_paragraph_text( $options ) {
		$paragraphs = array(
			'We deliver innovative solutions that transform businesses and drive growth. Our commitment to excellence ensures that every project exceeds expectations.',
			'Experience the difference with our cutting-edge technology and dedicated support team. We\'re here to help you achieve your goals.',
			'Our platform combines powerful features with intuitive design, making it easy for teams of all sizes to collaborate and succeed.',
			'Join thousands of satisfied customers who have transformed their workflow with our comprehensive solution. Start your journey today.',
			'We believe in the power of innovation to solve complex challenges. Our team works tirelessly to deliver solutions that make a real difference.',
			'Discover a new way of working that puts efficiency and results first. Our tools are designed to help you achieve more with less effort.',
			'From startups to enterprises, we provide scalable solutions that grow with your business. Experience reliability you can count on.',
			'Our mission is simple: to empower businesses with the tools they need to succeed in today\'s competitive landscape.'
		);
		
		return $paragraphs[ array_rand( $paragraphs ) ];
	}

	/**
	 * Generate button text.
	 *
	 * @since 1.0.0
	 * @param array $options Generation options.
	 * @return string Button text.
	 */
	private function generate_button_text( $options ) {
		if ( isset( $options['type'] ) ) {
			if ( $options['type'] === 'primary' ) {
				$texts = array( 'Get Started', 'Start Now', 'Sign Up Free', 'Try It Now', 'Begin Today' );
			} elseif ( $options['type'] === 'secondary' ) {
				$texts = array( 'Learn More', 'Explore Features', 'View Details', 'Discover More', 'See How' );
			} else {
				$texts = array( 'Contact Us', 'Get in Touch', 'Schedule Demo', 'Request Info', 'Talk to Sales' );
			}
		} else {
			$texts = array( 'Click Here', 'Learn More', 'Get Started', 'Explore Now', 'Discover More' );
		}
		
		return $texts[ array_rand( $texts ) ];
	}

	/**
	 * Generate image alt text.
	 *
	 * @since 1.0.0
	 * @param array $options Generation options.
	 * @return string Alt text.
	 */
	private function generate_image_alt( $options ) {
		$alts = array(
			'Professional workspace',
			'Team collaboration',
			'Modern office environment',
			'Business meeting',
			'Technology solution',
			'Creative design',
			'Innovation in action',
			'Digital transformation'
		);
		
		return $alts[ array_rand( $alts ) ];
	}

	/**
	 * Generate image caption.
	 *
	 * @since 1.0.0
	 * @return string Caption text.
	 */
	private function generate_image_caption() {
		$captions = array(
			'Innovation at its finest',
			'Where ideas come to life',
			'Excellence in every detail',
			'Transforming the future',
			'Built for success'
		);
		
		return $captions[ array_rand( $captions ) ];
	}

	/**
	 * Get image URL.
	 *
	 * @since 1.0.0
	 * @param array $size Size data.
	 * @param array $options Image options.
	 * @return string Image URL.
	 */
	private function get_image_url( $size, $options = array() ) {
		// For small images (icons)
		if ( $size['width'] <= 150 ) {
			$colors = array( '007cba', '0073aa', '005177', '00669b' );
			$color = $colors[ array_rand( $colors ) ];
			return 'https://placehold.co/' . $size['width'] . 'x' . $size['height'] . '/' . $color . '/ffffff?text=Icon';
		}
		
		// For larger images, use Unsplash
		$categories = array(
			'office' => array(
				'photo-1497366216548-37526070297c',
				'photo-1497366811353-6870744d04b2',
				'photo-1497366754035-f200968a6e72',
				'photo-1497366412874-3415097a27e7'
			),
			'team' => array(
				'photo-1522202176988-66273c2fd55f',
				'photo-1551434678-e076c223a692',
				'photo-1556761175-4b46a572b786',
				'photo-1522071820081-009f0129c71c'
			),
			'abstract' => array(
				'photo-1557683316-973673baf926',
				'photo-1557682250-33bd709cbe85',
				'photo-1557682224-5b8590cd9ec5',
				'photo-1557682260-96773eb01377'
			),
			'technology' => array(
				'photo-1517180102446-f3ece451e9d8',
				'photo-1518770660439-4636190af475',
				'photo-1461749280684-dccba630e2f6',
				'photo-1504639725590-34d0984388bd'
			)
		);
		
		$category = isset( $options['category'] ) ? $options['category'] : array_rand( $categories );
		$images = isset( $categories[ $category ] ) ? $categories[ $category ] : $categories['office'];
		
		return 'https://images.unsplash.com/' . $images[ array_rand( $images ) ];
	}

	/**
	 * Build heading classes.
	 *
	 * @since 1.0.0
	 * @param array $attrs Block attributes.
	 * @return string Class string.
	 */
	private function build_heading_classes( $attrs ) {
		$classes = '';
		
		if ( isset( $attrs['textAlign'] ) ) {
			$classes .= ' has-text-align-' . $attrs['textAlign'];
		}
		
		if ( isset( $attrs['textColor'] ) ) {
			$classes .= ' has-' . $attrs['textColor'] . '-color has-text-color';
		}
		
		if ( isset( $attrs['fontSize'] ) ) {
			$classes .= ' has-' . $attrs['fontSize'] . '-font-size';
		}
		
		return $classes;
	}

	/**
	 * Build paragraph classes.
	 *
	 * @since 1.0.0
	 * @param array $attrs Block attributes.
	 * @return string Class string.
	 */
	private function build_paragraph_classes( $attrs ) {
		$classes = '';
		
		if ( isset( $attrs['align'] ) ) {
			$classes .= 'has-text-align-' . $attrs['align'];
		}
		
		if ( isset( $attrs['backgroundColor'] ) ) {
			$classes .= ' has-' . $attrs['backgroundColor'] . '-background-color has-background';
		}
		
		if ( isset( $attrs['textColor'] ) ) {
			$classes .= ' has-' . $attrs['textColor'] . '-color has-text-color';
		}
		
		if ( isset( $attrs['fontSize'] ) ) {
			$classes .= ' has-' . $attrs['fontSize'] . '-font-size';
		}
		
		if ( isset( $attrs['dropCap'] ) && $attrs['dropCap'] ) {
			$classes .= ' has-drop-cap';
		}
		
		return trim( $classes );
	}

	/**
	 * Build button HTML.
	 *
	 * @since 1.0.0
	 * @param array  $attrs Button attributes.
	 * @param string $text  Button text.
	 * @return string Button HTML.
	 */
	private function build_button_html( $attrs, $text ) {
		$class = 'wp-block-button';
		$link_class = 'wp-block-button__link wp-element-button';
		
		if ( isset( $attrs['className'] ) ) {
			$class .= ' ' . $attrs['className'];
		}
		
		if ( isset( $attrs['backgroundColor'] ) ) {
			$link_class .= ' has-' . $attrs['backgroundColor'] . '-background-color has-background';
		}
		
		if ( isset( $attrs['textColor'] ) ) {
			$link_class .= ' has-' . $attrs['textColor'] . '-color has-text-color';
		}
		
		$style = '';
		if ( isset( $attrs['style'] ) ) {
			$styles = array();
			
			if ( isset( $attrs['style']['spacing']['padding'] ) ) {
				$padding = $attrs['style']['spacing']['padding'];
				$styles[] = 'padding-top:' . $padding['top'];
				$styles[] = 'padding-right:' . $padding['right'];
				$styles[] = 'padding-bottom:' . $padding['bottom'];
				$styles[] = 'padding-left:' . $padding['left'];
			}
			
			if ( isset( $attrs['style']['border']['radius'] ) ) {
				$styles[] = 'border-radius:' . $attrs['style']['border']['radius'];
			}
			
			if ( ! empty( $styles ) ) {
				$style = ' style="' . implode( ';', $styles ) . '"';
			}
		}
		
		return '<div class="' . $class . '"><a class="' . $link_class . '"' . $style . '>' . esc_html( $text ) . '</a></div>';
	}
}