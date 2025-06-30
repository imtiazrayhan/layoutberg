<?php
/**
 * Minimal test to replicate Pattern Pal's exact approach
 * 
 * Run this in browser: /wp-content/plugins/layoutberg/test-minimal-generation.php
 */

// Test without loading WordPress to see raw output
header('Content-Type: text/plain');

// Simulate Pattern Pal's exact flow
$prompt = "Create a hero section with title and button";

// 1. Build request exactly like Pattern Pal
$api_key = 'YOUR_API_KEY'; // You'll need to set this
$api_url = 'https://api.openai.com/v1/chat/completions';

$request_body = json_encode([
    'model'    => 'gpt-4o',
    'messages' => [
        [
            'role'    => 'system',
            'content' => 'You are an AI that generates valid WordPress block patterns. 
                ONLY return the block pattern using proper WordPress block markup. 
                DO NOT use generic HTML elements like <div> or <section>. 
                Always wrap elements in valid Gutenberg blocks (e.g., <!-- wp:paragraph -->, <!-- wp:group -->).

                NO explanations, NO additional text, NO Markdown formatting like triple backticks.

                The block pattern should be designed for the "Twenty Twenty-Four" theme and should include proper spacing, padding and margins. Please make sure all inner blocks use content width.'
        ],
        [
            'role'    => 'user',
            'content' => $prompt
        ]
    ],
    'max_tokens'  => 2000,
    'temperature' => 0.2,
]);

echo "=== TESTING PATTERN PAL's EXACT APPROACH ===\n\n";
echo "REQUEST BODY:\n";
echo $request_body . "\n\n";

// This is what Pattern Pal does:
// 1. Send request to OpenAI
// 2. Get response
// 3. Clean: trim, remove ```html and ```
// 4. Run through wp_kses_post()
// 5. Return raw string
// 6. JavaScript: wp.blocks.parse(pattern)

echo "PATTERN PAL's PROCESSING:\n";
echo "1. Send to OpenAI with temperature 0.2\n";
echo "2. Clean response (remove markdown)\n";
echo "3. wp_kses_post()\n";
echo "4. Return raw string to JavaScript\n";
echo "5. JavaScript: wp.blocks.parse()\n\n";

echo "OUR CURRENT PROCESSING:\n";
echo "1. Send to OpenAI (temp varies)\n";
echo "2. Parse in PHP\n";
echo "3. Validate extensively\n";
echo "4. Re-serialize\n";
echo "5. Return to JavaScript\n";
echo "6. JavaScript: wp.blocks.parse() again\n\n";

echo "KEY DIFFERENCES:\n";
echo "- Pattern Pal: Single parse in JavaScript\n";
echo "- Our plugin: Double parsing (PHP + JS)\n";
echo "- Pattern Pal: Minimal validation\n";
echo "- Our plugin: Extensive validation\n";