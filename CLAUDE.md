# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

# LayoutBerg Development Guidelines

This document contains specific instructions and guidelines for Claude (AI assistant) when working on the LayoutBerg WordPress plugin. Always refer to this document before making any code changes.

## Table of Contents

1. [Project Overview](#project-overview)
2. [Development Environment](#development-environment)
3. [Task Management](#task-management)
4. [Coding Standards](#coding-standards)
5. [Security Guidelines](#security-guidelines)
6. [Testing Requirements](#testing-requirements)
7. [Git Workflow](#git-workflow)
8. [Performance Guidelines](#performance-guidelines)
9. [WordPress Best Practices](#wordpress-best-practices)
10. [Gutenberg Development](#gutenberg-development)
11. [API Integration Guidelines](#api-integration-guidelines)

---

## Project Overview

LayoutBerg is an AI-powered layout designer plugin for WordPress that seamlessly integrates with the Gutenberg editor.

It lets users generate complete, responsive layouts using natural language prompts, powered by OpenAI's GPT models, creating native Gutenberg blocks without proprietary page builders.

It's built with PHP 8.1+, React, TypeScript, WordPress REST API, and uses MySQL for data storage with optional Redis caching.

Key features:

-   Native Gutenberg block generation
-   Multi-model AI support (GPT-3.5, GPT-4)
-   Template management system
-   Real-time preview
-   Bring your own API keys or use our proxy

Most of the functionality lives in this WordPress plugin repo. There's also a licensing backend that handles accounts, billing, and API key proxying; that lives at api.dotcamp.com and is written in PHP/Laravel.

Always prioritize:

1. Native Gutenberg compatibility
2. Performance and caching
3. Security (especially API key handling)
4. User experience in the editor

---

## Task Management

### Working with GitHub Issues

We use GitHub issues to track work and tasks. When working on any features or fixes:

1. **Before starting work:**
    - Read the relevant GitHub issue (or create one if needed)
    - Comment on the issue to indicate you're starting work
    - Tag issues with "by-claude" when creating them

2. **During development:**
    - Reference the issue number in your commits (e.g., "Fix #123: Add feature")
    - Update the issue with progress comments if working on complex features
    - Ask for clarification in the issue if requirements are unclear

3. **After completing work:**
    - Ensure your PR description includes "Fixes #[issue-number]"
    - The issue will be automatically closed when the PR is merged

### Task Tracking Best Practices

-   Break down complex features into smaller, manageable tasks
-   Use GitHub issue labels to categorize work (bug, feature, enhancement)
-   Link related issues and PRs for better tracking
-   Document any blockers or dependencies in issue comments
-   Use milestones for grouping related issues

---

## Development Environment

### Local Setup Requirements

-   WordPress 6.0+ (latest recommended)
-   PHP 8.1+ (8.2+ preferred)
-   MySQL 5.7+ (8.0+ preferred)
-   Node.js 18+ and npm 8+
-   Composer 2.0+
-   WP-CLI (recommended)

### Essential WordPress Plugins for Testing

-   Query Monitor (performance debugging)
-   Debug Bar (general debugging)
-   User Switching (testing different roles)

### Commands to Run Before Starting

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Build assets
npm run build

# For development with watch
npm run dev

# Run linting
npm run lint         # Runs both lint:js and lint:css
npm run lint:js      # Lint JavaScript only
npm run lint:css     # Lint CSS only
npm run format       # Auto-format code
composer phpcs       # Check PHP coding standards
composer phpcs-fix   # Auto-fix PHP coding standards

# Run tests
npm test             # Run Jest tests
npm run test:watch   # Run tests in watch mode
composer test        # Run PHPUnit tests
composer test:coverage # Generate coverage report
```

---

## Git Workflow

We use GitHub issues to track work we need to do, and PRs to review code. Whenever you create an issue or a PR, tag it with "by-claude". Use the `gh` bash command to interact with GitHub.

To start working on a feature, you should:

1. **Setup**

    - Read the relevant GitHub issue (or create one if needed)
    - Checkout master and pull the latest changes.
    - No branching required. Always push to master

2. **Development**

    - Commit often as you write code, so that we can revert if needed
    - Follow WordPress coding standards and use proper sanitization/escaping
    - When you have a draft of what you're working on, ask me to test it in my local WordPress environment to confirm that it works as you expect. Do this early and often.
    - Remember to update both PHP and JavaScript/React code as needed

3. **Review**

    - When the work is done, verify that the diff looks good with `git diff main`
    - Run `npm run build` to ensure the JavaScript builds correctly
    - Check that you've followed WordPress coding standards
    - Push the branch to GitHub
    - Open a PR:
        - The PR title should describe the feature clearly
        - The PR description should start with "Fixes #[issue-number]" and a detailed description of the changes
        - Include a test plan that covers:
            - Admin interface testing steps
            - Gutenberg editor testing steps
            - Different user role scenarios
            - Edge cases and error handling

4. **Fixing issues**
    - To reconcile different branches, always rebase or cherry-pick. Do not merge.
    - Test database migrations carefully if schema changes are involved

Sometimes, after you've been working on one feature, I will ask you to start work on an unrelated feature. If I do, you should probably repeat this process from the beginning (checkout main, pull changes, create a new branch). When in doubt, just ask.

### Project Structure

-   **Plugin Core:** Main PHP files in root and `includes/`
-   **Admin UI:** PHP templates in `admin/` and React components in `src/admin/`
-   **Gutenberg Blocks:** Block definitions in `src/blocks/`
-   **Public Assets:** Frontend files in `public/`
-   **Build Output:** Compiled files in `build/`

Important files to be aware of:

-   `layoutberg.php` - Main plugin file with header and initialization
-   `includes/class-layoutberg.php` - Core plugin class
-   `includes/class-api-client.php` - OpenAI API integration
-   `includes/class-block-generator.php` - Gutenberg block generation logic
-   `includes/class-admin.php` - Admin functionality and menus
-   `src/blocks/ai-layout/index.js` - Main Gutenberg block registration
-   `src/admin/components/GeneratorModal.tsx` - AI generation interface
-   `admin/partials/layoutberg-admin-display.php` - Settings page template
-   `webpack.config.js` - Custom webpack configuration extending @wordpress/scripts
-   `tsconfig.json` - TypeScript configuration with path aliases

---

## WordPress Best Practices

### Database Operations

Changes to the database schema require:

-   Creating activation/upgrade functions in `includes/class-activator.php`
-   Using `$wpdb` for all database operations
-   Proper table prefixing with `$wpdb->prefix`
-   Schema versioning in options table

Example:

```php
global $wpdb;
$table_name = $wpdb->prefix . 'layoutberg_generations';
$wpdb->insert($table_name, [
    'user_id' => get_current_user_id(),
    'prompt' => $sanitized_prompt,
    'status' => 'pending'
]);
```

### API Integration

When working with the OpenAI API:

-   Always use `wp_remote_post()` for HTTP requests
-   Store API keys encrypted in options table
-   Implement proper error handling
-   Add timeout handling (30-60 seconds)
-   Use WordPress transients for caching

### Gutenberg Block Development

For block-related code:

-   Register blocks in both PHP and JavaScript
-   Use `@wordpress/blocks` and `@wordpress/block-editor` packages
-   Follow block.json schema for attributes
-   Ensure valid block markup generation
-   Test in both editor and frontend

## Debugging Provider Calls

When debugging OpenAI API calls:

-   Add logging in `includes/class-api-client.php`:
    ```php
    error_log('OpenAI Request: ' . json_encode($request_body));
    error_log('OpenAI Response: ' . json_encode($response));
    ```
-   Check WordPress debug.log for output
-   Use browser console for JavaScript debugging

---

## Coding Standards

### PHP Standards

-   **WordPress Coding Standards:** Follow strictly (use PHPCS)
-   **PHP Version:** Target PHP 8.1+ features
-   **Namespacing:** Use `DotCamp\LayoutBerg\` namespace
-   **Prefixing:** Use `layoutberg_` for functions, `lberg_` for short prefixes
-   **Security:** Always escape output, sanitize input, verify nonces
-   **Hooks:** Document with proper PHPDoc blocks

Example:

```php
/**
 * Generate layout from AI prompt
 *
 * @param string $prompt User input prompt
 * @param array  $options Generation options
 * @return array|WP_Error Generated blocks or error
 */
public function generate_layout( string $prompt, array $options = [] ) {
    $prompt = sanitize_textarea_field( $prompt );

    if ( ! current_user_can( 'edit_posts' ) ) {
        return new WP_Error( 'forbidden', __( 'Insufficient permissions', 'layoutberg' ) );
    }

    // Implementation...
}
```

### JavaScript/React Standards

-   **TypeScript:** Use for all new React components (strict mode enabled)
-   **ES Modules:** Use modern imports
-   **WordPress Packages:** Prefer `@wordpress/*` packages
-   **State Management:** Use WordPress Data stores where applicable
-   **Formatting:** Follow @wordpress/scripts ESLint config
-   **Path Aliases:** Use configured aliases (@components, @utils, @hooks, @admin, @blocks, @editor)

Example:

```typescript
import { useState } from "@wordpress/element";
import { Button } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

interface IGeneratorProps {
	onGenerate: (prompt: string) => void;
	isLoading: boolean;
}

export const Generator: React.FC<IGeneratorProps> = ({
	onGenerate,
	isLoading,
}) => {
	const [prompt, setPrompt] = useState("");

	// Implementation...
};
```

---

## Security Guidelines

### CRITICAL Security Rules

1. **API Key Handling**:
    - NEVER hardcode API keys
    - ALWAYS encrypt API keys before storage
    - Use WordPress options API with encryption
    - Validate API keys before use

```php
// Storing API key
$encrypted_key = $this->encrypt_api_key( $api_key );
update_option( 'layoutberg_api_key', $encrypted_key );

// Retrieving API key
$encrypted_key = get_option( 'layoutberg_api_key' );
$api_key = $this->decrypt_api_key( $encrypted_key );
```

2. **Nonce Verification**: ALWAYS verify nonces

```php
if ( ! wp_verify_nonce( $_POST['layoutberg_nonce'], 'layoutberg_action' ) ) {
    wp_die( 'Security check failed' );
}
```

3. **Capability Checks**: ALWAYS check user capabilities

```php
if ( ! current_user_can( 'edit_posts' ) ) {
    wp_die( 'Insufficient permissions' );
}
```

4. **SQL Queries**: Use prepared statements

```php
global $wpdb;
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}layoutberg_templates WHERE user_id = %d",
        $user_id
    )
);
```

5. **File Operations**: Validate file paths and types

```php
$allowed_types = array( 'jpg', 'jpeg', 'png', 'gif' );
$file_type = wp_check_filetype( $filename );
if ( ! in_array( $file_type['ext'], $allowed_types, true ) ) {
    wp_die( 'Invalid file type' );
}
```

### Security Checklist

Before committing any code, ensure:

-   [ ] No hardcoded credentials
-   [ ] All inputs are sanitized
-   [ ] All outputs are escaped
-   [ ] Nonces are used for forms
-   [ ] Capabilities are checked
-   [ ] SQL queries use prepared statements
-   [ ] File uploads are validated
-   [ ] No direct file access allowed

---

## Testing Requirements

### Unit Testing (PHPUnit)

1. Test file naming: `test-class-name.php`
2. Test class naming: `Test_Class_Name`
3. Minimum coverage: 80%
4. Test critical paths first

```php
class Test_Block_Generator extends WP_UnitTestCase {
    public function test_generate_creates_valid_blocks() {
        $generator = new Block_Generator();
        $result = $generator->generate( 'Create a hero section' );

        $this->assertStringContainsString( '<!-- wp:', $result );
        $this->assertNotEmpty( $result );
    }
}
```

### Integration Testing

1. Test with different WordPress versions
2. Test with popular plugins (Yoast, WooCommerce)
3. Test with different themes
4. Test multisite compatibility

### JavaScript Testing (Jest)

```javascript
describe("LayoutBergButton", () => {
	it("renders without crashing", () => {
		const wrapper = shallow(<LayoutBergButton />);
		expect(wrapper.exists()).toBe(true);
	});
});
```

### Manual Testing Checklist

Before asking me to test, ensure:

1. **PHP Syntax:** No parse errors
2. **JavaScript Build:** `npm run build` succeeds
3. **WordPress Hooks:** Properly added/removed
4. **Capability Checks:** User permissions verified
5. **Nonce Verification:** For all forms/AJAX
6. **Escaping:** All output properly escaped
7. **Sanitization:** All input sanitized
8. **Error Handling:** Try/catch blocks where needed
9. **Translations:** Strings wrapped in `__()`
10. **Block Validation:** Generated blocks validate in editor

---

## Performance Guidelines

### Caching Strategy

1. **Use transients** for API responses

```php
$cache_key = 'layoutberg_' . md5( $prompt );
$cached = get_transient( $cache_key );

if ( false === $cached ) {
    $result = $this->api->generate( $prompt );
    set_transient( $cache_key, $result, HOUR_IN_SECONDS );
    return $result;
}

return $cached;
```

2. **Object caching** when available

```php
if ( wp_using_ext_object_cache() ) {
    wp_cache_set( $key, $value, 'layoutberg', 3600 );
}
```

### Database Optimization

1. Add indexes for frequently queried columns
2. Use batch operations when possible
3. Implement pagination for large datasets
4. Clean up old data periodically

### Asset Optimization

1. Minify CSS and JavaScript
2. Use `wp_enqueue_script` with dependencies
3. Load assets only where needed
4. Use async/defer for non-critical scripts

### API Optimization

1. Implement request queuing
2. Use background processing for large operations
3. Cache API responses appropriately
4. Implement retry logic with exponential backoff

---

## Gutenberg Development

### Block Registration

```javascript
import { registerBlockType } from "@wordpress/blocks";

registerBlockType("layoutberg/ai-layout", {
	title: __("AI Layout", "layoutberg"),
	category: "layoutberg",
	supports: {
		align: ["wide", "full"],
		html: false,
	},
	attributes: {
		prompt: {
			type: "string",
			default: "",
		},
	},
	edit: Edit,
	save: Save,
});
```

### Editor Integration

1. Use SlotFill for toolbar integration
2. Follow Gutenberg data flow patterns
3. Use WordPress components
4. Implement proper error boundaries

### Block Patterns

1. Register reusable patterns
2. Categorize patterns logically
3. Provide pattern variations
4. Include preview images

---

## API Integration Guidelines

### OpenAI API

1. **Rate Limiting**: Implement token bucket algorithm
2. **Error Handling**: Graceful degradation
3. **Timeout Handling**: 30-second timeout
4. **Response Validation**: Verify block structure

```php
try {
    $response = $this->openai->complete( [
        'model'      => $model,
        'messages'   => $messages,
        'max_tokens' => $max_tokens,
    ] );
} catch ( Exception $e ) {
    // Log error
    error_log( 'LayoutBerg API Error: ' . $e->getMessage() );

    // Return user-friendly error
    return new WP_Error(
        'api_error',
        __( 'Unable to generate layout. Please try again.', 'layoutberg' )
    );
}
```

### WordPress REST API

1. Use proper authentication
2. Validate and sanitize all inputs
3. Return consistent response formats
4. Implement proper HTTP status codes

```php
register_rest_route( 'layoutberg/v1', '/generate', array(
    'methods'             => 'POST',
    'callback'            => array( $this, 'handle_generation' ),
    'permission_callback' => array( $this, 'check_permissions' ),
    'args'                => array(
        'prompt' => array(
            'required'          => true,
            'sanitize_callback' => 'sanitize_textarea_field',
            'validate_callback' => array( $this, 'validate_prompt' ),
        ),
    ),
) );
```

---

## Build System Details

### Webpack Configuration
- Extends @wordpress/scripts default config
- Custom entry points for modular loading
- TypeScript support via ts-loader
- Code splitting for WordPress vendor packages
- Path aliases configured for cleaner imports

### TypeScript Configuration
- Target: ES2018 for broad browser support
- Strict mode enabled for better type safety
- Path aliases matching webpack config
- Declaration files generated for type checking

## Common WordPress Gotchas

-   Post meta keys starting with `_` are hidden
-   Transients can be cleared by caching plugins
-   Admin notices need to be hooked properly
-   AJAX actions need `wp_` or `wp_ajax_nopriv_` prefixes
-   Block attributes must match block.json schema
-   REST API routes need permission callbacks
-   Enqueued scripts need proper dependencies

---

## Important Reminders

1. **Always check** existing code patterns before implementing new features
2. **Never commit** sensitive data or API keys
3. **Test thoroughly** before marking tasks complete
4. **Document** any deviations from these guidelines
5. **Ask for clarification** when requirements are unclear
6. **Track progress** in GitHub issues with clear status updates
7. **Reference issue numbers** in commits and PRs for better traceability

## Quick Reference Commands

```bash
# Before starting work
git pull origin master
composer install
npm install

# During development
npm run dev
composer phpcs-fix

# Before committing
npm run lint
composer test
git diff --cached

# Building for production
npm run build
composer install --no-dev

# Webpack Entry Points (configured in webpack.config.js)
# - admin/index: Admin interface
# - admin/template-preview: Template preview
# - admin/onboarding: Onboarding wizard
# - editor: Main Gutenberg editor integration
# - blocks/ai-layout/index: AI Layout block
# - public/index: Public-facing scripts
```

Remember: Quality over speed. A well-tested, secure feature is better than a quickly implemented one.

Last Updated: 2025-01-08
