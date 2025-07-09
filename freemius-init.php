<?php
/**
 * Freemius initialization file.
 * This file is included before the main plugin file to ensure
 * layoutberg_fs() is available in the global namespace.
 *
 * @package LayoutBerg
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'layoutberg_fs' ) ) {
    // Create a helper function for easy SDK access.
    function layoutberg_fs() {
        global $layoutberg_fs;

        if ( ! isset( $layoutberg_fs ) ) {
            // Activate multisite network integration.
            if ( ! defined( 'WP_FS__PRODUCT_19761_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_19761_MULTISITE', true );
            }

            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
            $layoutberg_fs = fs_dynamic_init( array(
                'id'                  => '19761',
                'slug'                => 'layoutberg',
                'premium_slug'        => 'layoutberg',
                'type'                => 'plugin',
                'public_key'          => 'pk_6fbf13f450f12e2a396f3071c4e2c',
                'is_premium'          => true,
                'is_premium_only'     => true,
                'has_addons'          => false,
                'has_paid_plans'      => true,
                'is_org_compliant'    => false,
                'menu'                => array(
                    'slug'           => 'layoutberg',
                    'first-path'     => 'admin.php?page=layoutberg-onboarding',
                    'contact'        => false,
                    'support'        => false,
                ),
            ) );
        }

        return $layoutberg_fs;
    }

    // Init Freemius.
    layoutberg_fs();
    // Signal that SDK was initiated.
    do_action( 'layoutberg_fs_loaded' );
}