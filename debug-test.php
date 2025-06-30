<?php
/**
 * Debug test for block generation
 * 
 * This file helps debug what's actually being generated and returned
 */

// Load WordPress
require_once( dirname( __FILE__, 5 ) . '/wp-load.php' );

// Load our plugin classes
require_once( dirname( __FILE__ ) . '/includes/class-api-client.php' );
require_once( dirname( __FILE__ ) . '/includes/class-simple-block-generator.php' );
require_once( dirname( __FILE__ ) . '/includes/class-simple-prompt-engineer.php' );

// Test prompt
$test_prompt = "Create a simple hero section with a title and button";

// Initialize the simple generator
$generator = new \DotCamp\LayoutBerg\Simple_Block_Generator();

// Generate
$result = $generator->generate( $test_prompt, array() );

// Output results
echo "=== SIMPLE BLOCK GENERATOR TEST ===\n\n";

if ( is_wp_error( $result ) ) {
    echo "ERROR: " . $result->get_error_message() . "\n";
} else {
    echo "SUCCESS! Generated content:\n\n";
    
    echo "BLOCKS field:\n";
    echo "Type: " . gettype( $result['blocks'] ) . "\n";
    echo "Content: " . substr( $result['blocks'], 0, 200 ) . "...\n\n";
    
    echo "SERIALIZED field:\n";
    echo "Type: " . gettype( $result['serialized'] ) . "\n";
    echo "Content: " . substr( $result['serialized'], 0, 200 ) . "...\n\n";
    
    echo "RAW field:\n";
    echo "Type: " . gettype( $result['raw'] ) . "\n";
    echo "Content: " . substr( $result['raw'], 0, 200 ) . "...\n\n";
    
    // Check what the API handler would return
    echo "=== API HANDLER WOULD RETURN ===\n";
    echo "'blocks' field would contain: " . ( isset( $result['serialized'] ) ? "serialized" : "blocks" ) . "\n";
    
    // Test parsing
    echo "\n=== PARSE TEST ===\n";
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
}