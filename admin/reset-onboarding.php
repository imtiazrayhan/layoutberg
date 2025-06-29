<?php
/**
 * Reset Onboarding Tool
 * 
 * Access this file directly in your browser at:
 * /wp-content/plugins/layoutberg/admin/reset-onboarding.php
 *
 * @package LayoutBerg
 */

// Load WordPress
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );

// Check if user is logged in and has admin capabilities
if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You must be logged in as an administrator to use this tool.' );
}

// Reset onboarding
delete_option( 'layoutberg_onboarding_completed' );
delete_option( 'layoutberg_onboarding_skipped' );
delete_option( 'layoutberg_onboarding_progress' );
delete_option( 'layoutberg_onboarding_completed_at' );
set_transient( 'layoutberg_onboarding_redirect', true, 30 );

?>
<!DOCTYPE html>
<html>
<head>
	<title>LayoutBerg - Reset Onboarding</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			background: #f0f0f1;
			display: flex;
			align-items: center;
			justify-content: center;
			height: 100vh;
			margin: 0;
		}
		.container {
			background: white;
			padding: 40px;
			border-radius: 8px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
			text-align: center;
			max-width: 400px;
		}
		h1 {
			color: #1e293b;
			margin-top: 0;
		}
		.success {
			color: #10b981;
			font-size: 48px;
			margin: 20px 0;
		}
		p {
			color: #64748b;
			margin: 20px 0;
		}
		a {
			display: inline-block;
			background: #6366f1;
			color: white;
			padding: 12px 24px;
			text-decoration: none;
			border-radius: 4px;
			margin-top: 20px;
		}
		a:hover {
			background: #4f46e5;
		}
	</style>
</head>
<body>
	<div class="container">
		<h1>LayoutBerg Onboarding Reset</h1>
		<div class="success">✓</div>
		<p>The onboarding process has been reset successfully!</p>
		<p>You will be redirected to the onboarding wizard when you visit the admin area.</p>
		<a href="<?php echo admin_url(); ?>">Go to Admin Dashboard</a>
	</div>
</body>
</html>