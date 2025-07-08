<?php
/**
 * Plugin Name:       LayoutBerg - AI Layout Designer
 * Plugin URI:        https://dotcamp.com/layoutberg
 * Description:       AI-powered layout designer that seamlessly integrates with the WordPress Gutenberg editor. Generate complete, responsive layouts using natural language prompts.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            DotCamp
 * Author URI:        https://dotcamp.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       layoutberg
 * Domain Path:       /languages
 *
 * @package LayoutBerg
 */

namespace DotCamp\LayoutBerg;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'LAYOUTBERG_VERSION', '1.0.0' );
define( 'LAYOUTBERG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LAYOUTBERG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LAYOUTBERG_PLUGIN_FILE', __FILE__ );
define( 'LAYOUTBERG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Minimum requirements.
define( 'LAYOUTBERG_MIN_PHP_VERSION', '7.4' );
define( 'LAYOUTBERG_MIN_WP_VERSION', '6.0' );

if ( ! function_exists( 'lay_fs' ) ) {
    // Create a helper function for easy SDK access.
    function lay_fs() {
        global $lay_fs;

        if ( ! isset( $lay_fs ) ) {
            // Activate multisite network integration.
            if ( ! defined( 'WP_FS__PRODUCT_19761_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_19761_MULTISITE', true );
            }

            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
            $lay_fs = fs_dynamic_init( array(
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

        return $lay_fs;
    }

    // Init Freemius.
    lay_fs();
    // Signal that SDK was initiated.
    do_action( 'lay_fs_loaded' );
}

/**
 * Check minimum requirements.
 *
 * @since 1.0.0
 * @return bool True if requirements are met, false otherwise.
 */
function layoutberg_check_requirements() {
	$errors = array();

	// Check PHP version.
	if ( version_compare( PHP_VERSION, LAYOUTBERG_MIN_PHP_VERSION, '<' ) ) {
		$errors[] = sprintf(
			/* translators: 1: Required PHP version, 2: Current PHP version */
			__( 'LayoutBerg requires PHP %1$s or higher. You are running PHP %2$s.', 'layoutberg' ),
			LAYOUTBERG_MIN_PHP_VERSION,
			PHP_VERSION
		);
	}

	// Check WordPress version.
	if ( version_compare( get_bloginfo( 'version' ), LAYOUTBERG_MIN_WP_VERSION, '<' ) ) {
		$errors[] = sprintf(
			/* translators: 1: Required WordPress version, 2: Current WordPress version */
			__( 'LayoutBerg requires WordPress %1$s or higher. You are running WordPress %2$s.', 'layoutberg' ),
			LAYOUTBERG_MIN_WP_VERSION,
			get_bloginfo( 'version' )
		);
	}

	// Display errors if any.
	if ( ! empty( $errors ) ) {
		add_action(
			'admin_notices',
			function() use ( $errors ) {
				?>
				<div class="notice notice-error">
					<p><strong><?php esc_html_e( 'LayoutBerg cannot be activated.', 'layoutberg' ); ?></strong></p>
					<ul>
						<?php foreach ( $errors as $error ) : ?>
							<li><?php echo esc_html( $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php
				// Deactivate the plugin.
				deactivate_plugins( LAYOUTBERG_PLUGIN_BASENAME );
			}
		);
		return false;
	}

	return true;
}

// Check requirements before loading the plugin.
if ( ! layoutberg_check_requirements() ) {
	return;
}

// Load Composer autoloader if it exists.
if ( file_exists( LAYOUTBERG_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once LAYOUTBERG_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * The code that runs during plugin activation.
 *
 * @since 1.0.0
 */
function layoutberg_activate() {
	require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-activator.php';
	Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since 1.0.0
 */
function layoutberg_deactivate() {
	require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-deactivator.php';
	Deactivator::deactivate();
}

register_activation_hook( __FILE__, __NAMESPACE__ . '\layoutberg_activate' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\layoutberg_deactivate' );

/**
 * Load the plugin text domain for translation.
 *
 * @since 1.0.0
 */
function layoutberg_load_textdomain() {
	load_plugin_textdomain(
		'layoutberg',
		false,
		dirname( LAYOUTBERG_PLUGIN_BASENAME ) . '/languages/'
	);
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\layoutberg_load_textdomain' );

/**
 * Check and run database upgrades.
 *
 * @since 1.0.0
 */
function layoutberg_check_upgrades() {
	require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-database-upgrader.php';
	
	$db_version = get_option( 'layoutberg_db_version', '1.0.0' );
	if ( version_compare( $db_version, '1.1.0', '<' ) ) {
		Database_Upgrader::upgrade();
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\layoutberg_check_upgrades', 10 );

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 */
function layoutberg_init() {
	// Load core plugin class.
	require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-layoutberg.php';

	// Initialize the plugin.
	$plugin = LayoutBerg::get_instance();
	$plugin->run();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\layoutberg_init', 20 );

/**
 * Get the main plugin instance.
 *
 * @since 1.0.0
 * @return LayoutBerg The main plugin instance.
 */
function layoutberg() {
	return LayoutBerg::get_instance();
}