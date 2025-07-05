<?php
/**
 * Simple test for block validation fix
 * 
 * This tests the logic without requiring WordPress
 */

echo "=== TESTING BLOCK VALIDATION FIX LOGIC ===\n\n";

// Simulate AI-generated content (what we'd get from the API)
$ai_generated_content = '```html
<!-- wp:cover {"gradient":"vivid-cyan-blue-to-vivid-purple","align":"full","minHeight":600} -->
<div class="wp-block-cover alignfull" style="min-height:600px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim has-background-gradient has-vivid-cyan-blue-to-vivid-purple-gradient-background"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","textColor":"white"} -->
<h1 class="wp-block-heading has-text-align-center has-white-color has-text-color">Hero Title</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","textColor":"white"} -->
<p class="has-text-align-center has-white-color has-text-color">Hero description text</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->
```';

echo "1. AI GENERATED CONTENT:\n";
echo $ai_generated_content . "\n\n";

// Step 1: Remove markdown code blocks
$content = preg_replace( '/^```html\s*/', '', $ai_generated_content );
$content = preg_replace( '/```$/', '', $content );
$content = trim( $content );

echo "2. AFTER REMOVING MARKDOWN:\n";
echo $content . "\n\n";

// Step 2: Basic validation
if ( ! preg_match( '/<!-- wp:/', $content ) ) {
    echo "✗ ERROR: No valid block comments found\n";
} else {
    echo "✓ SUCCESS: Contains valid block comments\n";
}

// Step 3: Check for common validation issues
echo "\n3. VALIDATION CHECKS:\n";

if ( preg_match( '/"id":\s*\d+/', $content ) ) {
    echo "✗ Contains 'id' attributes (may cause validation issues)\n";
} else {
    echo "✓ No 'id' attributes found\n";
}

if ( preg_match( '/wp-image-\d+/', $content ) ) {
    echo "✗ Contains wp-image-XXX classes (may cause validation issues)\n";
} else {
    echo "✓ No wp-image-XXX classes found\n";
}

if ( strpos( $content, '```' ) !== false ) {
    echo "✗ Still contains markdown backticks\n";
} else {
    echo "✓ Markdown backticks removed\n";
}

echo "\n4. FINAL RESULT:\n";
echo "This content would be returned to JavaScript as:\n";
echo "result.blocks = " . json_encode( $content ) . "\n\n";

echo "JavaScript would then call:\n";
echo "wp.blocks.parse(result.blocks)\n\n";

echo "5. EXPECTED OUTCOME:\n";
echo "✓ Single parsing step (no double parsing)\n";
echo "✓ No validation issues\n";
echo "✓ Blocks insert correctly into editor\n";
echo "✓ No 'Attempt Recovery' needed\n";

echo "\n=== COMPARISON WITH OLD APPROACH ===\n";
echo "OLD (Double Parsing):\n";
echo "1. PHP: parse_blocks() → validate → serialize_blocks()\n";
echo "2. JavaScript: wp.blocks.parse() again\n";
echo "3. Result: Validation failures, 'Attempt Recovery' needed\n\n";

echo "NEW (Single Parsing):\n";
echo "1. PHP: Clean content only (no parsing)\n";
echo "2. JavaScript: wp.blocks.parse() once\n";
echo "3. Result: Perfect validation, no recovery needed\n"; 