# LayoutBerg Prompt Engineering Usage Guide

## Overview

This guide explains how to use the optimized LayoutBerg prompt engineering system effectively. The system has been optimized to achieve 86-90% token reduction while maintaining high-quality Gutenberg block generation.

## Key Features

### 🎯 Smart Prompt Analysis
- **Dynamic Block Detection**: Automatically detects which Gutenberg blocks are needed based on your request
- **Complexity Analysis**: Determines if your request is simple, moderate, or complex
- **Template Matching**: Identifies when pre-built templates can be used for faster generation

### 🚀 Token Optimization
- **86-90% Reduction**: Dramatically reduced token usage compared to previous version
- **Context-Aware**: Only includes relevant information for your specific request
- **Minimal Examples**: Shows only the most important examples for your use case

### 🛡️ Error Recovery
- **Validation-First**: Checks prompts before generation to prevent common issues
- **Intelligent Fallbacks**: Uses templates when generation fails
- **Helpful Suggestions**: Provides specific guidance when requests need improvement

## How to Use

### Basic Usage

```php
use DotCamp\LayoutBerg\Prompt_Engineer;

$prompt_engineer = new Prompt_Engineer();

// Simple generation
$result = $prompt_engineer->build_system_prompt([
    'prompt' => 'Create a hero section with title and button'
]);

// Enhanced generation with validation
$result = $prompt_engineer->generate_with_validation(
    'Create a hero section with title and button',
    ['style' => 'modern']
);
```

### Prompt Types & Examples

#### Simple Prompts (Best Performance)
These generate quickly with minimal token usage:

```
✅ "Create a hero section"
✅ "Add a simple button"
✅ "Create a heading with text"
✅ "Make a contact form"
```

Expected tokens: ~250-400

#### Moderate Prompts (Good Performance)
These include multiple elements but stay focused:

```
✅ "Create features section with 3 columns and images"
✅ "Make a pricing table with 3 plans"
✅ "Create testimonials with customer quotes"
✅ "Build hero section with background image and CTA"
```

Expected tokens: ~300-500

#### Complex Prompts (Higher Token Usage)
These generate comprehensive layouts:

```
✅ "Create complete landing page with hero, features, and pricing"
✅ "Build full homepage with all sections"
✅ "Make comprehensive contact page with form and info"
```

Expected tokens: ~400-600

### Optimization Tips

#### 🎯 Be Specific but Concise
```
❌ "Create something for my website"
✅ "Create a hero section with title and button"

❌ "Make a page with everything I need for my business"
✅ "Create landing page with hero, features, and contact form"
```

#### 🎯 Use Keywords the System Recognizes
The system is optimized to detect these keywords:

**Sections**: hero, features, testimonials, pricing, FAQ, contact, gallery
**Elements**: title, button, image, form, list, columns, quote
**Layouts**: grid, columns, side by side, centered

#### 🎯 Leverage Templates
For faster generation, use prompts that match built-in templates:

```
✅ "Create a hero section" → Uses hero template
✅ "Create features grid" → Uses features template  
✅ "Create pricing table" → Uses pricing template
✅ "Create testimonials section" → Uses testimonials template
✅ "Create call to action" → Uses CTA template
```

## Available Options

### Style Options
```php
$options = [
    'style' => 'modern'  // modern, classic, bold
];
```

- **modern**: Clean, minimal with gradients and generous spacing
- **classic**: Traditional, professional with neutral colors  
- **bold**: High impact with strong colors and dramatic spacing

### Context Options
```php
$options = [
    'site_type' => 'business',  // business, blog, portfolio, ecommerce, nonprofit
    'context' => 'Professional consulting firm'
];
```

## Error Handling & Recovery

### Common Validation Errors

#### Prompt Too Short
```
Error: "Please provide more details about what you want to create."
Solution: Add more specifics like "Create a hero section with title and button"
```

#### Prompt Too Complex
```
Error: "This request is very complex. Consider breaking it into smaller sections."
Solution: Generate one section at a time, then combine manually
```

#### Vague Request
```
Error: "Too vague - be more specific about what you want"
Solution: Use specific terms like "hero section", "features grid", "pricing table"
```

### Using Validation-First Generation

```php
$result = $prompt_engineer->generate_with_validation($prompt, $options);

if (is_wp_error($result)) {
    $error_data = $result->get_error_data();
    echo "Error: " . $result->get_error_message();
    
    if (isset($error_data['suggestion'])) {
        echo "Suggestion: " . $error_data['suggestion'];
    }
} else {
    $system_prompt = $result['system_prompt'];
    $token_count = $result['token_count'];
    $status = $result['status']; // 'success' or 'fallback_template_used'
}
```

## Monitoring & Analytics

### Token Usage Statistics
```php
// Get usage statistics
$stats = $prompt_engineer->get_token_usage_stats();
echo "Average tokens: " . $stats['average_tokens'];
echo "Total requests: " . $stats['total_requests'];

// Clear logs (for maintenance)
$prompt_engineer->clear_token_usage_logs();
```

### Performance Monitoring
The system automatically logs token usage when `WP_DEBUG` is enabled:

```php
// In wp-config.php
define('WP_DEBUG', true);
```

Logs are stored in WordPress options table and include:
- Timestamp
- Token count
- Text length
- User ID
- Text preview

## Best Practices

### ✅ Do's

1. **Start Simple**: Begin with basic sections, then combine them
2. **Use Templates**: Leverage built-in templates for common patterns
3. **Be Specific**: Use clear, descriptive language
4. **Test Variations**: Try different phrasings to find what works best
5. **Monitor Usage**: Keep track of token consumption for cost optimization

### ❌ Don'ts

1. **Avoid Vague Terms**: Don't use "something", "anything", "stuff"
2. **Don't Overload**: Avoid requesting too many elements at once
3. **Skip Conflicting Terms**: Don't mix "simple" and "complex" in same request
4. **Avoid Code Injection**: Don't include HTML/JavaScript in prompts
5. **Don't Ignore Errors**: Pay attention to validation messages

## Integration Examples

### WordPress Plugin Integration
```php
class My_Layout_Generator {
    private $prompt_engineer;
    
    public function __construct() {
        $this->prompt_engineer = new Prompt_Engineer();
    }
    
    public function generate_layout($user_input, $options = []) {
        $result = $this->prompt_engineer->generate_with_validation($user_input, $options);
        
        if (is_wp_error($result)) {
            return [
                'error' => $result->get_error_message(),
                'suggestion' => $result->get_error_data()['suggestion'] ?? ''
            ];
        }
        
        return [
            'prompt' => $result['system_prompt'],
            'tokens' => $result['token_count'],
            'status' => $result['status']
        ];
    }
}
```

### REST API Endpoint
```php
add_action('rest_api_init', function() {
    register_rest_route('layoutberg/v1', '/generate', [
        'methods' => 'POST',
        'callback' => function($request) {
            $prompt_engineer = new Prompt_Engineer();
            $user_prompt = $request->get_param('prompt');
            $options = $request->get_param('options') ?: [];
            
            $result = $prompt_engineer->generate_with_validation($user_prompt, $options);
            
            if (is_wp_error($result)) {
                return new WP_Error('generation_failed', $result->get_error_message(), [
                    'status' => 400,
                    'suggestion' => $result->get_error_data()['suggestion'] ?? ''
                ]);
            }
            
            return rest_ensure_response($result);
        },
        'permission_callback' => function() {
            return current_user_can('edit_posts');
        }
    ]);
});
```

## Testing Your Integration

### Basic Test Cases
```php
// Test basic functionality
$test_prompts = [
    'Create a hero section',
    'Create features grid with 3 columns',
    'Create pricing table',
    'Create complete landing page'
];

foreach ($test_prompts as $prompt) {
    $result = $prompt_engineer->build_system_prompt(['prompt' => $prompt]);
    $tokens = $prompt_engineer->estimate_token_count($result);
    echo "Prompt: $prompt | Tokens: $tokens\n";
}
```

### Run Comprehensive Tests
```php
// Use the built-in test suite
require_once 'tests/test-prompt-optimization-comprehensive.php';
$tester = new Test_Prompt_Optimization_Comprehensive();
$results = $tester->run_all_tests();
```

## Troubleshooting

### High Token Usage
If you're seeing higher than expected token usage:

1. Check if prompts are triggering 'complex' complexity detection
2. Verify you're using template-matched keywords
3. Ensure prompts are specific rather than broad
4. Consider breaking complex requests into smaller parts

### Poor Generation Quality
If generated blocks aren't working well:

1. Use the validation-first approach: `generate_with_validation()`
2. Check error messages for specific guidance
3. Try template-matched prompts for known patterns
4. Verify block specifications match your Gutenberg version

### Performance Issues
If generation is slow:

1. Use simpler prompts when possible
2. Leverage template matching for faster generation
3. Cache results for repeated patterns
4. Monitor token usage statistics

## Support & Further Reading

- Check the comprehensive test suite for examples
- Review the comparison tool for performance metrics
- Examine the block templates for pattern ideas
- Use the token monitoring features for optimization

For technical support, check the error logs and validation messages for specific guidance on improving your prompts.