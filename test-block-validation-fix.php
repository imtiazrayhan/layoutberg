<?php
/**
 * Test block validation fix
 * 
 * This file tests the new single parsing approach to ensure blocks validate correctly
 */

// Load WordPress
require_once( dirname( __FILE__, 4 ) . '/wp-load.php' );

// Load our plugin classes
require_once( dirname( __FILE__ ) . '/includes/class-api-client.php' );
require_once( dirname( __FILE__ ) . '/includes/class-block-generator.php' );

echo "=== TESTING BLOCK VALIDATION FIX ===\n\n";

// Test prompt
$test_prompt = "Create a hero section with title and button";

// Initialize the block generator (now uses single parsing)
$generator = new \DotCamp\LayoutBerg\Block_Generator();

// Generate
$result = $generator->generate( $test_prompt, array() );

// Output results
if ( is_wp_error( $result ) ) {
    echo "ERROR: " . $result->get_error_message() . "\n";
} else {
    echo "SUCCESS! Generated content:\n\n";
    
    echo "BLOCKS field (raw content for JavaScript):\n";
    echo "Type: " . gettype( $result['blocks'] ) . "\n";
    echo "Content: " . substr( $result['blocks'], 0, 300 ) . "...\n\n";
    
    echo "SERIALIZED field (same as blocks):\n";
    echo "Type: " . gettype( $result['serialized'] ) . "\n";
    echo "Content: " . substr( $result['serialized'], 0, 300 ) . "...\n\n";
    
    echo "HTML field (preview):\n";
    echo "Type: " . gettype( $result['html'] ) . "\n";
    echo "Content: " . substr( $result['html'], 0, 300 ) . "...\n\n";
    
    // Test what JavaScript would do
    echo "=== JAVASCRIPT SIMULATION ===\n";
    echo "JavaScript would receive the 'blocks' field and call:\n";
    echo "wp.blocks.parse(result.blocks)\n\n";
    
    // Simulate JavaScript parsing
    $parsed = parse_blocks( $result['blocks'] );
    echo "PHP parse_blocks() found " . count( $parsed ) . " blocks\n";
    
    // Check for validation issues
    echo "\n=== VALIDATION CHECK ===\n";
    if ( strpos( $result['blocks'], '<!-- wp:' ) !== false ) {
        echo "✓ Contains valid block comments\n";
    } else {
        echo "✗ No valid block comments found\n";
    }
    
    if ( strpos( $result['blocks'], '```' ) !== false ) {
        echo "✗ Still contains markdown backticks\n";
    } else {
        echo "✓ Markdown backticks removed\n";
    }
    
    // Check for common validation issues
    if ( preg_match( '/"id":\s*\d+/', $result['blocks'] ) ) {
        echo "✗ Contains 'id' attributes (may cause validation issues)\n";
    } else {
        echo "✓ No 'id' attributes found\n";
    }
    
    if ( preg_match( '/wp-image-\d+/', $result['blocks'] ) ) {
        echo "✗ Contains wp-image-XXX classes (may cause validation issues)\n";
    } else {
        echo "✓ No wp-image-XXX classes found\n";
    }
    
    echo "\n=== EXPECTED RESULT ===\n";
    echo "With single parsing approach:\n";
    echo "1. PHP: Generate → Clean → wp_kses_post() → Return raw string\n";
    echo "2. JavaScript: Receive raw string → wp.blocks.parse() → Insert blocks\n";
    echo "3. Result: Single parsing step, blocks should validate perfectly\n";
} 