<?php
/**
 * Test file for prompt engineering improvements
 *
 * @package LayoutBerg
 */

// Load WordPress
require_once dirname( __FILE__, 5 ) . '/wp-load.php';

// Load plugin files
require_once dirname( __FILE__ ) . '/../includes/class-prompt-engineer.php';
require_once dirname( __FILE__ ) . '/../includes/class-block-generator.php';
require_once dirname( __FILE__ ) . '/../includes/class-block-serializer.php';

use DotCamp\LayoutBerg\Prompt_Engineer;
use DotCamp\LayoutBerg\Block_Generator;
use DotCamp\LayoutBerg\Block_Serializer;

// Initialize prompt engineer
$prompt_engineer = new Prompt_Engineer();

// Test system prompt generation
echo "=== TESTING SYSTEM PROMPT ===\n\n";

$options = array(
	'style' => 'modern',
	'layout' => 'grid',
);

$system_prompt = $prompt_engineer->build_system_prompt( $options );
echo "System Prompt Length: " . strlen( $system_prompt ) . " characters\n";
echo "First 500 chars:\n" . substr( $system_prompt, 0, 500 ) . "...\n\n";

// Test user prompt enhancement
echo "=== TESTING USER PROMPT ENHANCEMENT ===\n\n";

$user_prompt = "Create a landing page with hero section, features grid, testimonials, and CTA";
$enhanced_prompt = $prompt_engineer->enhance_user_prompt( $user_prompt, $options );
echo "Enhanced Prompt:\n" . $enhanced_prompt . "\n\n";

// Test validation of sample output
echo "=== TESTING OUTPUT VALIDATION ===\n\n";

$sample_output = '<!-- wp:cover {"url":"https://images.unsplash.com/photo-1517180102446-f3ece451e9d8","dimRatio":50,"minHeight":600,"align":"full"} -->
<div class="wp-block-cover alignfull" style="min-height:600px"><img class="wp-block-cover__image-background" alt="" src="https://images.unsplash.com/photo-1517180102446-f3ece451e9d8" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"textAlign":"center","level":1,"textColor":"white","fontSize":"huge"} -->
<h1 class="wp-block-heading has-text-align-center has-white-color has-text-color has-huge-font-size">Welcome</h1>
<!-- /wp:heading --></div></div>
<!-- /wp:cover -->';

$validation_result = $prompt_engineer->validate_and_fix_output( $sample_output );
echo "Validation Result:\n";
echo "Valid: " . ( $validation_result['valid'] ? 'YES' : 'NO' ) . "\n";
if ( ! empty( $validation_result['issues'] ) ) {
	echo "Issues: " . implode( ', ', $validation_result['issues'] ) . "\n";
}

// Test pattern library
echo "\n=== TESTING PATTERN LIBRARY ===\n\n";

$reflection = new ReflectionClass( $prompt_engineer );
$pattern_property = $reflection->getProperty( 'pattern_library' );
$pattern_property->setAccessible( true );
$patterns = $pattern_property->getValue( $prompt_engineer );

echo "Available Patterns:\n";
foreach ( array_keys( $patterns ) as $pattern_name ) {
	echo "- " . $pattern_name . "\n";
}

// Test block examples
echo "\n=== TESTING BLOCK EXAMPLES ===\n\n";

$block_property = $reflection->getProperty( 'block_examples' );
$block_property->setAccessible( true );
$blocks = $block_property->getValue( $prompt_engineer );

echo "Available Block Examples:\n";
foreach ( array_keys( $blocks ) as $block_name ) {
	echo "- " . $block_name . "\n";
}

echo "\n=== TEST COMPLETE ===\n";