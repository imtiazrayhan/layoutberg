<?php
/**
 * Test cover block validation issues
 */

// Sample cover blocks that might be generated
$test_blocks = [
    // Test 1: Cover with gradient (usually works)
    'gradient_cover' => '<!-- wp:cover {"gradient":"vivid-cyan-blue-to-vivid-purple","minHeight":600,"align":"full"} -->
<div class="wp-block-cover alignfull" style="min-height:600px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim has-background-gradient has-vivid-cyan-blue-to-vivid-purple-gradient-background"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","textColor":"white"} -->
<h1 class="wp-block-heading has-text-align-center has-white-color has-text-color">Hero Title</h1>
<!-- /wp:heading -->
</div></div>
<!-- /wp:cover -->',

    // Test 2: Cover with image URL (might fail)
    'image_cover' => '<!-- wp:cover {"url":"https://images.unsplash.com/photo-123456","dimRatio":50,"minHeight":600,"align":"full"} -->
<div class="wp-block-cover alignfull" style="min-height:600px"><img class="wp-block-cover__image-background" alt="" src="https://images.unsplash.com/photo-123456" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","textColor":"white"} -->
<h1 class="wp-block-heading has-text-align-center has-white-color has-text-color">Hero Title</h1>
<!-- /wp:heading -->
</div></div>
<!-- /wp:cover -->',

    // Test 3: Pattern Pal style cover with image
    'pattern_pal_style' => '<!-- wp:cover {"url":"https://images.unsplash.com/photo-1234567890","id":123,"dimRatio":50,"align":"full"} -->
<div class="wp-block-cover alignfull"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-123" alt="" src="https://images.unsplash.com/photo-1234567890" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Hero Title</h2>
<!-- /wp:heading -->
</div></div>
<!-- /wp:cover -->'
];

// Common issues with cover blocks:
echo "COMMON COVER BLOCK VALIDATION ISSUES:\n\n";

echo "1. IMAGE ID MISMATCH:\n";
echo "   - Block has 'id' attribute but no corresponding wp-image-{id} class\n";
echo "   - Or vice versa - has class but no id attribute\n\n";

echo "2. MISSING REQUIRED ATTRIBUTES:\n";
echo "   - Cover blocks with images often need both 'url' and 'id'\n";
echo "   - The id should match the class wp-image-{id}\n\n";

echo "3. INCORRECT HTML STRUCTURE:\n";
echo "   - Image element placement matters\n";
echo "   - Background dim span placement matters\n";
echo "   - Order of elements can cause validation failure\n\n";

echo "4. URL VALIDATION:\n";
echo "   - Some URLs might be blocked by WordPress\n";
echo "   - URL format might not match expected pattern\n\n";

echo "SOLUTION:\n";
echo "For cover blocks with images, Pattern Pal likely:\n";
echo "1. Doesn't include 'id' attribute (avoids id/class mismatch)\n";
echo "2. Uses simple structure without image IDs\n";
echo "3. Lets WordPress handle the validation\n";

echo "\nRECOMMENDED PROMPT INSTRUCTION:\n";
echo "When generating cover blocks with images:\n";
echo "- Use url attribute but NOT id attribute\n";
echo "- Don't add wp-image-XXX classes\n";
echo "- Keep the structure simple\n";