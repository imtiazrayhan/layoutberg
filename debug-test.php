<?php
/**
 * Debug test file for LayoutBerg
 * 
 * This file helps diagnose issues with the plugin.
 * Place this in the plugin root and access it directly.
 */

// Find and load WordPress
$wp_load_paths = array(
    dirname( __FILE__ ) . '/../../../../wp-load.php',  // Standard installation
    dirname( __FILE__ ) . '/../../../wp-load.php',     // If in wp-content/plugins
    dirname( __FILE__ ) . '/../../wp-load.php',        // If in wp-content
    $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php',       // Document root
);

$wp_loaded = false;
foreach ( $wp_load_paths as $path ) {
    if ( file_exists( $path ) ) {
        require_once( $path );
        $wp_loaded = true;
        break;
    }
}

if ( ! $wp_loaded ) {
    die( 'Error: Could not find wp-load.php. Please ensure this file is in the LayoutBerg plugin directory.' );
}

// Check if user is admin
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Access denied' );
}

// Enable error reporting
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

echo "<h1>LayoutBerg Debug Test</h1>";

// Check PHP version
echo "<h2>Environment Check</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "WordPress Version: " . get_bloginfo( 'version' ) . "<br>";

// Check if classes exist
echo "<h2>Class Availability</h2>";
$classes = array(
    'DotCamp\LayoutBerg\API_Client',
    'DotCamp\LayoutBerg\Block_Generator',
    'DotCamp\LayoutBerg\Block_Serializer',
    'DotCamp\LayoutBerg\Cache_Manager',
    'DotCamp\LayoutBerg\Security_Manager',
    'DotCamp\LayoutBerg\Prompt_Engineer',
    'DotCamp\LayoutBerg\Input_Sanitizer',
);

foreach ( $classes as $class ) {
    echo $class . ": " . ( class_exists( $class ) ? '<span style="color: green;">✓ EXISTS</span>' : '<span style="color: red;">✗ MISSING</span>' ) . "<br>";
}

// Check API key
echo "<h2>API Key Status</h2>";
$options = get_option( 'layoutberg_options', array() );
if ( ! empty( $options['api_key'] ) ) {
    echo "API Key: <span style='color: green;'>✓ Configured</span><br>";
    
    // Try to decrypt
    try {
        $security = new \DotCamp\LayoutBerg\Security_Manager();
        $decrypted = $security->decrypt_api_key( $options['api_key'] );
        if ( $decrypted ) {
            echo "Decryption: <span style='color: green;'>✓ Success</span><br>";
            echo "Key Format: " . ( preg_match( '/^sk-[a-zA-Z0-9]{48}$/', $decrypted ) ? '<span style="color: green;">✓ Valid</span>' : '<span style="color: red;">✗ Invalid</span>' ) . "<br>";
        } else {
            echo "Decryption: <span style='color: red;'>✗ Failed</span><br>";
        }
    } catch ( Exception $e ) {
        echo "Decryption Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "API Key: <span style='color: red;'>✗ Not configured</span><br>";
}

// Test API client initialization
echo "<h2>API Client Test</h2>";
try {
    $api_client = new \DotCamp\LayoutBerg\API_Client();
    echo "API Client: <span style='color: green;'>✓ Initialized</span><br>";
} catch ( Exception $e ) {
    echo "API Client Error: <span style='color: red;'>" . $e->getMessage() . "</span><br>";
}

// Test Block Generator initialization
echo "<h2>Block Generator Test</h2>";
try {
    $generator = new \DotCamp\LayoutBerg\Block_Generator();
    echo "Block Generator: <span style='color: green;'>✓ Initialized</span><br>";
} catch ( Exception $e ) {
    echo "Block Generator Error: <span style='color: red;'>" . $e->getMessage() . "</span><br>";
}

// Test a simple generation (without actually calling OpenAI)
echo "<h2>Generation Test (Dry Run)</h2>";
try {
    $generator = new \DotCamp\LayoutBerg\Block_Generator();
    // This will fail due to no API key or quota, but we can see where it fails
    $result = $generator->generate( 'Create a simple hero section', array(
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => 500,
    ) );
    
    if ( is_wp_error( $result ) ) {
        echo "Error Code: " . $result->get_error_code() . "<br>";
        echo "Error Message: " . $result->get_error_message() . "<br>";
    } else {
        echo "Success! (This is unexpected in dry run)<br>";
    }
} catch ( Exception $e ) {
    echo "Exception: <span style='color: red;'>" . $e->getMessage() . "</span><br>";
    echo "Stack Trace:<pre>" . $e->getTraceAsString() . "</pre>";
}

// Check REST API registration
echo "<h2>REST API Endpoints</h2>";
$server = rest_get_server();
$routes = $server->get_routes();
$layoutberg_routes = array_filter( array_keys( $routes ), function( $route ) {
    return strpos( $route, '/layoutberg/' ) !== false;
});

foreach ( $layoutberg_routes as $route ) {
    echo $route . "<br>";
}

echo "<h2>Debug Complete</h2>";
echo "<p>If you see any red ✗ marks or errors above, those indicate the source of the problem.</p>";