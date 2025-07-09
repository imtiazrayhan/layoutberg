# LayoutBerg Pricing Tiers - Feature Implementation Status

## **Starter Plan - $9/month or $89/year**

_Perfect for individual bloggers and content creators_

| Feature                                                                         | Backend | UI  | Status                |
| ------------------------------------------------------------------------------- | ------- | --- | --------------------- |
| AI-powered layout generation                                                    | ✅      | ✅  | **Fully Implemented** |
| Bring your own API key                                                          | ✅      | ✅  | **Fully Implemented** |
| Access to GPT-3.5 Turbo model                                                   | ✅      | ✅  | **Fully Implemented** |
| Basic WordPress blocks (paragraphs, headings, images, buttons, columns, groups) | ✅      | ✅  | **Fully Implemented** |
| 4 style presets (Modern, Classic, Minimal, Bold)                                | ✅      | ✅  | **Fully Implemented** |
| Save up to 10 custom templates                                                  | ✅      | ✅  | **Fully Implemented** |
| Basic template categories (General, Business, Blog)                             | ✅      | ✅  | **Fully Implemented** |
| Generation history (last 30 days)                                               | ✅      | ✅  | **Fully Implemented** |
| Basic usage analytics                                                           | ✅      | ✅  | **Fully Implemented** |
| Email support                                                                   | N/A     | N/A | **Business Process**  |

## **Professional Plan - $19/month or $149/year**

_Ideal for agencies and professional developers_

| Feature                                                                                                            | Backend | UI  | Status                      |
| ------------------------------------------------------------------------------------------------------------------ | ------- | --- | --------------------------- |
| Everything in Starter                                                                                              | ✅      | ✅  | **Fully Implemented**       |
| Access to all AI models (OpenAI: GPT-3.5, GPT-4, GPT-4 Turbo, GPT-4o \| Claude: Opus, Sonnet, Haiku)               | ✅      | ✅  | **Fully Implemented**       |
| Unlimited template saving                                                                                          | ✅      | ✅  | **Fully Implemented**       |
| All template categories (General, Business, Creative, E-commerce, Blog/Magazine, Portfolio, Landing Pages, Custom) | ✅      | ✅  | **Fully Implemented**       |
| Template import (JSON format)                                                                                      | ✅      | ✅  | **Fully Implemented**       |
| Template export (JSON format)                                                                                      | ✅      | ✅  | **Fully Implemented**       |
| Advanced generation options (temperature control, max tokens adjustment)                                           | ✅      | ✅  | **Fully Implemented**       |
| Pattern variations support                                                                                         | ✅      | ❌  | **Needs UI Implementation** |
| Block variations support                                                                                           | ✅      | ❌  | **Needs UI Implementation** |
| Full generation history (unlimited)                                                                                | ✅      | ✅  | **Fully Implemented**       |
| Detailed usage analytics with cost tracking                                                                        | ✅      | ✅  | **Fully Implemented**       |
| Cache management controls                                                                                          | ✅      | ✅  | **Fully Implemented**       |
| Priority email support                                                                                             | N/A     | N/A | **Business Process**        |

## **Agency Plan - $49/month or $389/year**

_For teams managing multiple client sites_

| Feature                                | Backend | UI  | Status                                 |
| -------------------------------------- | ------- | --- | -------------------------------------- |
| Everything in Professional             | ✅      | ✅  | **Fully Implemented**                  |
| Multisite network support              | ⚠️      | ❌  | **Partial - Basic compatibility only** |
| Advanced prompt engineering templates  | ✅      | ❌  | **Needs UI Implementation**            |
| Custom style defaults configuration    | ✅      | ❌  | **Needs UI Implementation**            |
| Advanced block customization options   | ❌      | ❌  | **Not Implemented**                    |
| Debug mode access                      | ✅      | ❌  | **Needs UI Implementation**            |
| Usage analytics export (CSV)           | ✅      | ✅  | **Fully Implemented**                  |
| Template sharing between network sites | ❌      | ❌  | **Not Implemented**                    |
| Advanced caching options               | ✅      | ✅  | **Fully Implemented**                  |
| Dedicated account support              | N/A     | N/A | **Business Process**                   |
| Early access to new features           | N/A     | N/A | **Business Process**                   |
| Quarterly feature request reviews      | N/A     | N/A | **Business Process**                   |

## Implementation Priority List

### ✅ Completed Tasks

1. ~~**Template Export UI**~~ ✅ **COMPLETED (2025-01-09)**

    - ~~Add export button in templates list~~
    - ~~Implementation: Add button next to each template in `layoutberg-admin-templates.php`~~
    - ~~Use existing `export_template()` method from `Template_Manager` class~~
    - ~~Add AJAX handler with licensing checks~~
    - ~~Fix import/export compatibility issues~~

2. ~~**License Validation**~~ ✅ **COMPLETED (2025-01-09)**

    - ~~Updated `validate_license()` method to use Freemius SDK~~
    - ~~Updated `is_pro()` method to use `can_use_premium_code()`~~
    - ~~Properly integrated with Freemius licensing system~~

3. ~~**Template Categories Gatekeeping**~~ ✅ **COMPLETED (2025-01-09)**

    - ~~Dynamic categories in admin templates page based on plan~~
    - ~~Dynamic categories in save template modal based on plan~~
    - ~~Proper filtering in all template-related UI elements~~

4. ~~**Export Functionality Gatekeeping**~~ ✅ **COMPLETED (2025-01-09)**

    - ~~Export button shows only for Professional and Agency plans~~
    - ~~Proper upgrade/renewal messages for other plans~~
    - ~~Backend validation in AJAX handler~~

5. ~~**Freemius Integration Fixes**~~ ✅ **COMPLETED (2025-01-09)**
    - ~~Fixed namespace issue with `layoutberg_fs()` function~~
    - ~~Added separate `freemius-init.php` file for global namespace~~
    - ~~Improved plan detection logic with case-insensitive checks~~
    - ~~Added debug page for troubleshooting licensing issues~~

### High Priority (Professional Plan) - TODO

1. **Pattern/Block Variations UI**

    - Add variations selector in generation modal
    - Implementation: Extend `GeneratorModal.tsx` to include variation options
    - Leverage existing `Block_Variations` and `Pattern_Variations` classes
    - **Status**: Backend classes exist but no UI components found

2. **Style Defaults Configuration UI**
    - Add settings tab for default styles
    - Implementation: Add new tab in settings page
    - Save to `layoutberg_options['style_defaults']`
    - **Status**: No UI found in settings page

### Medium Priority (Agency Plan) - TODO

1. **Prompt Engineering Templates UI**

    - Expose templates in settings/editor
    - Implementation: Create interface to view/select prompt templates
    - Use existing `Prompt_Engineer` class templates
    - **Status**: Backend class exists but no UI exposure found

2. **Debug Mode Toggle**

    - Add toggle in Advanced settings
    - Implementation: Add checkbox in settings to enable/disable debug mode
    - Control `WP_DEBUG` logging for LayoutBerg specifically
    - **Status**: No UI toggle found in settings page

3. **Multisite Features**
    - Enhance network support beyond basic compatibility
    - Implementation: Add network admin pages
    - Create network-wide settings management
    - **Status**: Basic compatibility only, no network-specific features

### Low Priority (Future Enhancement) - TODO

1. **Advanced Block Customization**

    - New feature development required
    - Would need custom block attribute controls
    - **Status**: Not implemented at all

2. **Template Sharing Between Sites**
    - Requires significant architecture changes
    - Would need central template repository
    - API for cross-site communication
    - **Status**: Not implemented at all

## Freemius Feature Gatekeeping Implementation

### Basic License Checking

```php
// Get Freemius instance (assuming it's initialized as layoutberg_fs())
global $layoutberg_fs;

** IMPORTANT: Since Freemius is configured to block features only for expired MONTHLY plans,
// use can_use_premium_code() instead of is_paying() to respect this configuration

// Check if user can access premium features (active license OR expired yearly)
if ( layoutberg_fs()->can_use_premium_code() ) {
    // User can access their plan's features
}

// Check for specific plans
if ( layoutberg_fs()->is_plan('starter') ) {
    // Starter plan features
}

if ( layoutberg_fs()->is_plan('professional') ) {
    // Professional plan features
}

if ( layoutberg_fs()->is_plan('agency') ) {
    // Agency plan features
}

// Check if license is expired (for showing renewal notices)
if ( layoutberg_fs()->is_registered() && ! layoutberg_fs()->is_paying() ) {
    // User has expired subscription - show renewal notice
    // But still check can_use_premium_code() for feature access
}
```

### Feature-Specific Implementation

#### 1. **Model Access Restrictions**

Location: `includes/class-admin.php` - `get_available_models()` method

```php
// Check if user can access premium features
if ( ! layoutberg_fs()->can_use_premium_code() ) {
    // Expired monthly or no license - show only GPT-3.5 Turbo
    $models['openai'] = array(
        'label' => __( 'OpenAI Models', 'layoutberg' ),
        'models' => array(
            'gpt-3.5-turbo' => $openai_models['gpt-3.5-turbo']
        )
    );
} else if ( layoutberg_fs()->is_plan('starter') ) {
    // Active or expired yearly Starter - show only GPT-3.5 Turbo
    $models['openai'] = array(
        'label' => __( 'OpenAI Models', 'layoutberg' ),
        'models' => array(
            'gpt-3.5-turbo' => $openai_models['gpt-3.5-turbo']
        )
    );
} else {
    // Professional and Agency: All models
    // Show all OpenAI and Claude models
}
```

#### 2. **Template Saving Limits**

Location: `includes/class-template-manager.php` - `save_template()` method

```php
public function save_template( $template_data ) {
    global $wpdb;

    // Check if user can access premium features
    if ( ! layoutberg_fs()->can_use_premium_code() ) {
        // Expired monthly - cannot save templates
        return new \WP_Error(
            'license_expired',
            __( 'Your subscription has expired. Please renew to save templates.', 'layoutberg' ),
            array( 'account_url' => layoutberg_fs()->get_account_url() )
        );
    }

    // Check template limit for Starter plan
    if ( layoutberg_fs()->is_plan('starter') ) {
        $user_templates = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE created_by = %d",
                get_current_user_id()
            )
        );

        if ( $user_templates >= 10 ) {
            return new \WP_Error(
                'template_limit_reached',
                __( 'Template limit reached. Upgrade to Professional to save unlimited templates.', 'layoutberg' ),
                array( 'upgrade_url' => layoutberg_fs()->get_upgrade_url() )
            );
        }
    }

    // Continue with save logic...
}
```

#### 3. **Template Categories Restriction**

Location: `includes/class-template-manager.php` - `get_categories()` method

```php
public function get_categories() {
    $basic_categories = array(
        'general'  => __( 'General', 'layoutberg' ),
        'business' => __( 'Business', 'layoutberg' ),
        'blog'     => __( 'Blog/Magazine', 'layoutberg' ),
    );

    // Check if user can access premium features
    if ( ! layoutberg_fs()->can_use_premium_code() || layoutberg_fs()->is_plan('starter') ) {
        // Expired monthly or Starter plan - basic categories only
        return apply_filters( 'layoutberg_template_categories', $basic_categories );
    }

    // Professional and Agency get all categories
    $all_categories = array_merge( $basic_categories, array(
        'creative'   => __( 'Creative', 'layoutberg' ),
        'ecommerce'  => __( 'E-commerce', 'layoutberg' ),
        'portfolio'  => __( 'Portfolio', 'layoutberg' ),
        'landing'    => __( 'Landing Pages', 'layoutberg' ),
        'custom'     => __( 'Custom', 'layoutberg' ),
    ));

    return apply_filters( 'layoutberg_template_categories', $all_categories );
}
```

#### 4. **Template Export (Professional+)**

Location: `admin/partials/layoutberg-admin-templates.php` - Add export button conditionally

```php
<?php if ( layoutberg_fs()->can_use_premium_code() &&
         ( layoutberg_fs()->is_plan('professional') || layoutberg_fs()->is_plan('agency') ) ) : ?>
    <button class="button export-template" data-template-id="<?php echo esc_attr( $template->id ); ?>">
        <?php esc_html_e( 'Export', 'layoutberg' ); ?>
    </button>
<?php else : ?>
    <?php
    $button_text = ! layoutberg_fs()->can_use_premium_code()
        ? __( 'Renew subscription to export', 'layoutberg' )
        : __( 'Upgrade to Professional to export templates', 'layoutberg' );
    ?>
    <button class="button disabled" title="<?php echo esc_attr( $button_text ); ?>">
        <?php esc_html_e( 'Export', 'layoutberg' ); ?>
        <span class="dashicons dashicons-lock"></span>
    </button>
<?php endif; ?>
```

#### 5. **Advanced Generation Options (Professional+)**

Location: `src/admin/components/GeneratorModal.tsx` or settings page

```php
// In PHP for localizing script
wp_localize_script( 'layoutberg-editor', 'layoutbergEditor', array(
    // ... other data ...
    'canUsePremiumCode' => layoutberg_fs()->can_use_premium_code(),
    'isProfessional' => layoutberg_fs()->can_use_premium_code() &&
                       ( layoutberg_fs()->is_plan('professional') || layoutberg_fs()->is_plan('agency') ),
    'upgradeUrl' => layoutberg_fs()->get_upgrade_url(),
    'accountUrl' => layoutberg_fs()->get_account_url(),
) );
```

#### 6. **CSV Export (Agency Only)**

Location: `admin/partials/layoutberg-admin-analytics.php`

```php
<?php if ( layoutberg_fs()->can_use_premium_code() && layoutberg_fs()->is_plan('agency') ) : ?>
    <button id="export-csv" class="button">
        <span class="dashicons dashicons-download"></span>
        <?php esc_html_e( 'Export as CSV', 'layoutberg' ); ?>
    </button>
<?php else : ?>
    <?php
    $button_url = ! layoutberg_fs()->can_use_premium_code()
        ? layoutberg_fs()->get_account_url()
        : layoutberg_fs()->get_upgrade_url();
    $button_text = ! layoutberg_fs()->can_use_premium_code()
        ? __( 'Renew subscription for CSV Export', 'layoutberg' )
        : __( 'Upgrade to Agency for CSV Export', 'layoutberg' );
    ?>
    <a href="<?php echo esc_url( $button_url ); ?>" class="button">
        <span class="dashicons dashicons-lock"></span>
        <?php echo esc_html( $button_text ); ?>
    </a>
<?php endif; ?>
```

#### 7. **Generation History Limits**

Location: Database query for history

```php
// Check if user can access premium features
if ( ! layoutberg_fs()->can_use_premium_code() || layoutberg_fs()->is_plan('starter') ) {
    // Expired monthly or Starter: Last 30 days only
    $where_date = "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} else {
    // Professional and Agency: Unlimited history
    $where_date = "";
}
```

### Helper Functions (Recommended)

Create a helper class or functions file:

```php
// includes/class-layoutberg-licensing.php
class LayoutBerg_Licensing {

    /**
     * Check if user can access premium features
     */
    public static function can_use_premium_code() {
        return layoutberg_fs()->can_use_premium_code();
    }

    /**
     * Check if user has expired monthly subscription
     */
    public static function is_expired_monthly() {
        return layoutberg_fs()->is_registered() &&
               ! layoutberg_fs()->can_use_premium_code() &&
               ! layoutberg_fs()->is_paying();
    }

    /**
     * Check if user can use all AI models
     */
    public static function can_use_all_models() {
        return self::can_use_premium_code() &&
               ( layoutberg_fs()->is_plan('professional') ||
                 layoutberg_fs()->is_plan('agency') );
    }

    /**
     * Check if user can export templates
     */
    public static function can_export_templates() {
        return self::can_use_premium_code() &&
               ( layoutberg_fs()->is_plan('professional') ||
                 layoutberg_fs()->is_plan('agency') );
    }

    /**
     * Check if user can export CSV
     */
    public static function can_export_csv() {
        return self::can_use_premium_code() &&
               layoutberg_fs()->is_plan('agency');
    }

    /**
     * Get template limit for current plan
     */
    public static function get_template_limit() {
        if ( ! self::can_use_premium_code() ) {
            return 0; // Expired monthly cannot save
        }

        if ( layoutberg_fs()->is_plan('starter') ) {
            return 10;
        }

        return PHP_INT_MAX; // Unlimited
    }

    /**
     * Get history days limit
     */
    public static function get_history_days() {
        if ( ! self::can_use_premium_code() || layoutberg_fs()->is_plan('starter') ) {
            return 30;
        }
        return PHP_INT_MAX; // Unlimited
    }

    /**
     * Get appropriate URL for user action
     */
    public static function get_action_url() {
        if ( self::is_expired_monthly() ) {
            return layoutberg_fs()->get_account_url(); // For renewal
        }
        return layoutberg_fs()->get_upgrade_url(); // For upgrade
    }
}
```

### UI Upgrade Prompts

For locked features, show upgrade prompts:

```php
// Generic upgrade prompt component
function layoutberg_upgrade_notice( $feature_name, $required_plan = 'professional' ) {
    $is_expired = LayoutBerg_Licensing::is_expired_monthly();
    $action_url = LayoutBerg_Licensing::get_action_url();
    ?>
    <div class="layoutberg-upgrade-notice">
        <p>
            <?php
            if ( $is_expired ) {
                esc_html_e( 'Your subscription has expired. Please renew to access premium features.', 'layoutberg' );
            } else {
                printf(
                    esc_html__( '%s is available in the %s plan.', 'layoutberg' ),
                    esc_html( $feature_name ),
                    esc_html( ucfirst( $required_plan ) )
                );
            }
            ?>
        </p>
        <a href="<?php echo esc_url( $action_url ); ?>" class="button button-primary">
            <?php
            echo $is_expired
                ? esc_html__( 'Renew Subscription', 'layoutberg' )
                : esc_html__( 'Upgrade Now', 'layoutberg' );
            ?>
        </a>
    </div>
    <?php
}
```

### Important Notes on Freemius Configuration

Since your Freemius is configured to:

-   Block features only for expired **monthly** subscriptions
-   Allow expired **yearly** subscriptions to keep their features

Key implementation points:

1. Always use `can_use_premium_code()` instead of `is_paying()` for feature access
2. `can_use_premium_code()` respects your Freemius blocking configuration
3. Expired monthly users will get `false` from `can_use_premium_code()`
4. Expired yearly users will get `true` from `can_use_premium_code()`
5. Use `get_account_url()` for renewal links (expired users)
6. Use `get_upgrade_url()` for upgrade links (active users wanting higher tier)

## Legend

-   ✅ = Implemented and working
-   ⚠️ = Partially implemented
-   ❌ = Not implemented
-   N/A = Not a technical feature (business/support process)

## Implementation Details

### Freemius Integration Architecture

The plugin uses a separated architecture for Freemius integration to avoid namespace conflicts:

1. **`freemius-init.php`** - Defines `layoutberg_fs()` in the global namespace
2. **`layoutberg.php`** - Main plugin file uses namespace `DotCamp\LayoutBerg`
3. **`class-layoutberg-licensing.php`** - Helper class for all licensing checks

This separation ensures that:

-   `layoutberg_fs()` is available globally as expected by Freemius SDK
-   The rest of the plugin code remains properly namespaced
-   No PHP namespace declaration errors occur

### Key Implementation Decisions

1. **Dynamic Category Loading**

    - Categories are loaded from `Template_Manager->get_categories()` in all UI elements
    - JavaScript components receive categories via `wp_localize_script()`
    - Ensures consistency across admin pages and editor

2. **Plan Detection**

    - Uses case-insensitive checks for plan names
    - Supports variations (e.g., "agency", "business", "enterprise")
    - Falls back to actual Freemius plan name if no match found

3. **Debug Tools**
    - Debug page available at `LayoutBerg > Debug` when `WP_DEBUG` is enabled
    - Shows comprehensive Freemius status and licensing information
    - Helps troubleshoot plan detection and feature access issues

## Notes

-   All features marked as "Fully Implemented" are production-ready
-   Features needing UI implementation have working backend code
-   Business process features (support, early access) are handled outside the plugin
-   Template limits (10 for Starter) are enforced in `save_template()` method
-   Always use `can_use_premium_code()` instead of `is_paying()` for feature access
-   Always provide upgrade paths for locked features with appropriate messaging
