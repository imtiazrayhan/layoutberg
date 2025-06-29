<?php
/**
 * Block serializer class for converting blocks to Gutenberg markup.
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
 * Block serializer class.
 *
 * @since 1.0.0
 */
class Block_Serializer {

	/**
	 * Serialize blocks array to Gutenberg markup.
	 *
	 * @since 1.0.0
	 * @param array $blocks Blocks to serialize.
	 * @return string Serialized block markup.
	 */
	public function serialize_blocks( $blocks ) {
		$output = '';
		
		foreach ( $blocks as $block ) {
			$output .= $this->serialize_block( $block );
		}
		
		return $output;
	}

	/**
	 * Serialize a single block.
	 *
	 * @since 1.0.0
	 * @param array $block Block to serialize.
	 * @return string Serialized block markup.
	 */
	public function serialize_block( $block ) {
		// Skip invalid blocks.
		if ( empty( $block['blockName'] ) ) {
			return '';
		}
		
		$block_name = $block['blockName'];
		$attrs      = isset( $block['attrs'] ) ? $block['attrs'] : array();
		$inner_html = isset( $block['innerHTML'] ) ? $block['innerHTML'] : '';
		$inner_blocks = isset( $block['innerBlocks'] ) ? $block['innerBlocks'] : array();
		
		// Build opening comment.
		$output = $this->build_block_comment( $block_name, $attrs );
		
		// Add content based on block type.
		if ( ! empty( $inner_blocks ) ) {
			// Container blocks with inner blocks.
			$output .= "\n";
			
			// Get appropriate wrapper tag.
			$wrapper = $this->get_block_wrapper( $block_name, $attrs );
			if ( $wrapper['open'] ) {
				$output .= $wrapper['open'] . "\n";
			}
			
			// Serialize inner blocks.
			foreach ( $inner_blocks as $inner_block ) {
				$output .= $this->serialize_block( $inner_block );
			}
			
			// Close wrapper.
			if ( $wrapper['close'] ) {
				$output .= $wrapper['close'] . "\n";
			}
		} else {
			// Leaf blocks with direct content.
			$output .= "\n" . $this->format_inner_content( $block ) . "\n";
		}
		
		// Add closing comment.
		$output .= "<!-- /wp:{$block_name} -->\n\n";
		
		return $output;
	}

	/**
	 * Build block comment with attributes.
	 *
	 * @since 1.0.0
	 * @param string $block_name Block name.
	 * @param array  $attrs      Block attributes.
	 * @return string Block comment.
	 */
	private function build_block_comment( $block_name, $attrs ) {
		$comment = "<!-- wp:{$block_name}";
		
		if ( ! empty( $attrs ) ) {
			// Clean and encode attributes.
			$attrs = $this->clean_attributes( $attrs );
			$json = wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			
			// Only add attributes if they're not empty after encoding.
			if ( $json !== '{}' && $json !== '[]' ) {
				$comment .= ' ' . $json;
			}
		}
		
		$comment .= ' -->';
		
		return $comment;
	}

	/**
	 * Clean block attributes for serialization.
	 *
	 * @since 1.0.0
	 * @param array $attrs Attributes to clean.
	 * @return array Cleaned attributes.
	 */
	private function clean_attributes( $attrs ) {
		$cleaned = array();
		
		foreach ( $attrs as $key => $value ) {
			// Skip null or empty values.
			if ( is_null( $value ) || ( is_string( $value ) && $value === '' ) ) {
				continue;
			}
			
			// Handle special attribute types.
			if ( $key === 'className' && empty( $value ) ) {
				continue;
			}
			
			// Recursively clean nested arrays.
			if ( is_array( $value ) ) {
				$cleaned_value = $this->clean_attributes( $value );
				if ( ! empty( $cleaned_value ) ) {
					$cleaned[ $key ] = $cleaned_value;
				}
			} else {
				$cleaned[ $key ] = $value;
			}
		}
		
		return $cleaned;
	}

	/**
	 * Get wrapper elements for block types.
	 *
	 * @since 1.0.0
	 * @param string $block_name Block name.
	 * @param array  $attrs      Block attributes.
	 * @return array Wrapper open and close tags.
	 */
	private function get_block_wrapper( $block_name, $attrs ) {
		$class_name = $this->get_block_class_name( $block_name );
		$additional_classes = isset( $attrs['className'] ) ? ' ' . esc_attr( $attrs['className'] ) : '';
		$align_class = isset( $attrs['align'] ) ? ' align' . esc_attr( $attrs['align'] ) : '';
		
		$classes = $class_name . $additional_classes . $align_class;
		
		switch ( $block_name ) {
			case 'core/columns':
				return array(
					'open'  => '<div class="' . esc_attr( $classes ) . '">',
					'close' => '</div>',
				);
				
			case 'core/column':
				$style = '';
				if ( isset( $attrs['width'] ) ) {
					$style = ' style="flex-basis:' . esc_attr( $attrs['width'] ) . '"';
				}
				return array(
					'open'  => '<div class="' . esc_attr( $classes ) . '"' . $style . '>',
					'close' => '</div>',
				);
				
			case 'core/group':
				$tag = isset( $attrs['tagName'] ) ? $attrs['tagName'] : 'div';
				return array(
					'open'  => '<' . $tag . ' class="' . esc_attr( $classes ) . '">',
					'close' => '</' . $tag . '>',
				);
				
			case 'core/cover':
				$style = '';
				if ( isset( $attrs['dimRatio'] ) ) {
					$style .= ' style="background-color:rgba(0,0,0,' . ( $attrs['dimRatio'] / 100 ) . ')"';
				}
				return array(
					'open'  => '<div class="' . esc_attr( $classes ) . '">' .
					          '<div class="wp-block-cover__inner-container">',
					'close' => '</div></div>',
				);
				
			case 'core/buttons':
				$layout_class = '';
				if ( isset( $attrs['layout']['justifyContent'] ) ) {
					$layout_class = ' is-content-justification-' . $attrs['layout']['justifyContent'];
				}
				return array(
					'open'  => '<div class="' . esc_attr( $classes . $layout_class ) . '">',
					'close' => '</div>',
				);
				
			case 'core/list':
				$tag = isset( $attrs['ordered'] ) && $attrs['ordered'] ? 'ol' : 'ul';
				return array(
					'open'  => '<' . $tag . ' class="' . esc_attr( $classes ) . '">',
					'close' => '</' . $tag . '>',
				);
				
			case 'core/social-links':
				$icon_class = isset( $attrs['iconColor'] ) ? ' has-icon-color' : '';
				return array(
					'open'  => '<ul class="' . esc_attr( $classes . $icon_class ) . '">',
					'close' => '</ul>',
				);
				
			case 'core/details':
				return array(
					'open'  => '<details class="' . esc_attr( $classes ) . '">',
					'close' => '</details>',
				);
				
			case 'core/table':
				return array(
					'open'  => '<figure class="' . esc_attr( $classes ) . '"><table>',
					'close' => '</table></figure>',
				);
				
			case 'core/gallery':
				$columns_class = isset( $attrs['columns'] ) ? ' columns-' . intval( $attrs['columns'] ) : '';
				return array(
					'open'  => '<figure class="' . esc_attr( $classes . ' has-nested-images' . $columns_class . ' is-cropped' ) . '">',
					'close' => '</figure>',
				);
				
			case 'core/quote':
				return array(
					'open'  => '<blockquote class="' . esc_attr( $classes ) . '">',
					'close' => '</blockquote>',
				);
				
			case 'core/pullquote':
				return array(
					'open'  => '<figure class="' . esc_attr( $classes ) . '"><blockquote>',
					'close' => '</blockquote></figure>',
				);
				
			case 'core/media-text':
				$media_on_right = isset( $attrs['mediaPosition'] ) && $attrs['mediaPosition'] === 'right' ? ' has-media-on-the-right' : '';
				return array(
					'open'  => '<div class="' . esc_attr( $classes . ' is-stacked-on-mobile' . $media_on_right ) . '">',
					'close' => '</div>',
				);
				
			default:
				return array(
					'open'  => '',
					'close' => '',
				);
		}
	}

	/**
	 * Format inner content for blocks.
	 *
	 * @since 1.0.0
	 * @param array $block Block data.
	 * @return string Formatted inner content.
	 */
	private function format_inner_content( $block ) {
		$block_name = $block['blockName'];
		$attrs      = isset( $block['attrs'] ) ? $block['attrs'] : array();
		$inner_html = isset( $block['innerHTML'] ) ? $block['innerHTML'] : '';
		
		// If innerHTML is already properly formatted, return it.
		if ( ! empty( $inner_html ) && $this->is_valid_html( $inner_html ) ) {
			return $inner_html;
		}
		
		// Generate appropriate content based on block type.
		switch ( $block_name ) {
			case 'core/paragraph':
				return $this->format_paragraph_block( $attrs, $inner_html );
				
			case 'core/heading':
				return $this->format_heading_block( $attrs, $inner_html );
				
			case 'core/image':
				return $this->format_image_block( $attrs );
				
			case 'core/button':
				return $this->format_button_block( $attrs, $inner_html );
				
			case 'core/list-item':
				return $this->format_list_item_block( $attrs, $inner_html );
				
			case 'core/spacer':
				return $this->format_spacer_block( $attrs );
				
			case 'core/separator':
				return $this->format_separator_block( $attrs );
				
			default:
				return $inner_html;
		}
	}

	/**
	 * Format paragraph block.
	 *
	 * @since 1.0.0
	 * @param array  $attrs      Block attributes.
	 * @param string $inner_html Inner HTML.
	 * @return string Formatted block content.
	 */
	private function format_paragraph_block( $attrs, $inner_html ) {
		$class = 'wp-block-paragraph';
		
		if ( isset( $attrs['align'] ) ) {
			$class .= ' has-text-align-' . $attrs['align'];
		}
		
		if ( isset( $attrs['fontSize'] ) ) {
			$class .= ' has-' . $attrs['fontSize'] . '-font-size';
		}
		
		$content = ! empty( $inner_html ) ? $inner_html : 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
		
		return '<p class="' . esc_attr( $class ) . '">' . $content . '</p>';
	}

	/**
	 * Format heading block.
	 *
	 * @since 1.0.0
	 * @param array  $attrs      Block attributes.
	 * @param string $inner_html Inner HTML.
	 * @return string Formatted block content.
	 */
	private function format_heading_block( $attrs, $inner_html ) {
		$level = isset( $attrs['level'] ) ? intval( $attrs['level'] ) : 2;
		$tag = 'h' . $level;
		$class = 'wp-block-heading';
		
		if ( isset( $attrs['textAlign'] ) ) {
			$class .= ' has-text-align-' . $attrs['textAlign'];
		}
		
		$content = ! empty( $inner_html ) ? $inner_html : 'Heading Text';
		
		return '<' . $tag . ' class="' . esc_attr( $class ) . '">' . $content . '</' . $tag . '>';
	}

	/**
	 * Format image block.
	 *
	 * @since 1.0.0
	 * @param array $attrs Block attributes.
	 * @return string Formatted block content.
	 */
	private function format_image_block( $attrs ) {
		$class = 'wp-block-image';
		$size_slug = isset( $attrs['sizeSlug'] ) ? $attrs['sizeSlug'] : 'large';
		$align = isset( $attrs['align'] ) ? $attrs['align'] : '';
		
		if ( $align ) {
			$class .= ' align' . $align;
		}
		
		$class .= ' size-' . $size_slug;
		
		$src = isset( $attrs['url'] ) ? $attrs['url'] : 'placeholder.jpg';
		$alt = isset( $attrs['alt'] ) ? $attrs['alt'] : 'Image description';
		
		return '<figure class="' . esc_attr( $class ) . '"><img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '"/></figure>';
	}

	/**
	 * Format button block.
	 *
	 * @since 1.0.0
	 * @param array  $attrs      Block attributes.
	 * @param string $inner_html Inner HTML.
	 * @return string Formatted block content.
	 */
	private function format_button_block( $attrs, $inner_html ) {
		$class = 'wp-block-button';
		$link_class = 'wp-block-button__link';
		
		if ( isset( $attrs['className'] ) ) {
			$class .= ' ' . $attrs['className'];
		}
		
		$url = isset( $attrs['url'] ) ? $attrs['url'] : '#';
		$text = ! empty( $inner_html ) ? strip_tags( $inner_html ) : 'Click Here';
		
		return '<div class="' . esc_attr( $class ) . '"><a class="' . esc_attr( $link_class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a></div>';
	}

	/**
	 * Format list item block.
	 *
	 * @since 1.0.0
	 * @param array  $attrs      Block attributes.
	 * @param string $inner_html Inner HTML.
	 * @return string Formatted block content.
	 */
	private function format_list_item_block( $attrs, $inner_html ) {
		$content = ! empty( $inner_html ) ? $inner_html : 'List item text';
		return '<li>' . $content . '</li>';
	}

	/**
	 * Format spacer block.
	 *
	 * @since 1.0.0
	 * @param array $attrs Block attributes.
	 * @return string Formatted block content.
	 */
	private function format_spacer_block( $attrs ) {
		$height = isset( $attrs['height'] ) ? $attrs['height'] : '100px';
		
		// Ensure height has unit.
		if ( is_numeric( $height ) ) {
			$height .= 'px';
		}
		
		return '<div style="height:' . esc_attr( $height ) . '" aria-hidden="true" class="wp-block-spacer"></div>';
	}

	/**
	 * Format separator block.
	 *
	 * @since 1.0.0
	 * @param array $attrs Block attributes.
	 * @return string Formatted block content.
	 */
	private function format_separator_block( $attrs ) {
		$class = 'wp-block-separator';
		
		if ( isset( $attrs['className'] ) ) {
			$class .= ' ' . $attrs['className'];
		}
		
		return '<hr class="' . esc_attr( $class ) . '"/>';
	}

	/**
	 * Get block class name.
	 *
	 * @since 1.0.0
	 * @param string $block_name Block name.
	 * @return string Block class name.
	 */
	private function get_block_class_name( $block_name ) {
		// Convert block name to class name.
		// e.g., core/columns -> wp-block-columns
		$class_name = str_replace( 'core/', 'wp-block-', $block_name );
		$class_name = str_replace( '/', '-', $class_name );
		
		return $class_name;
	}

	/**
	 * Check if HTML is valid.
	 *
	 * @since 1.0.0
	 * @param string $html HTML to check.
	 * @return bool True if valid.
	 */
	private function is_valid_html( $html ) {
		// Basic check for HTML structure.
		return preg_match( '/<[^>]+>/', $html ) && ! preg_match( '/^[<>]+$/', $html );
	}

	/**
	 * Serialize blocks for editor insertion.
	 *
	 * @since 1.0.0
	 * @param array $blocks Blocks to serialize.
	 * @return string Serialized blocks ready for editor.
	 */
	public function serialize_for_editor( $blocks ) {
		$serialized = $this->serialize_blocks( $blocks );
		
		// Ensure proper formatting for editor.
		$serialized = trim( $serialized );
		
		// Convert to format expected by Gutenberg.
		return $serialized;
	}
}