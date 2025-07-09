<?php
/**
 * Debug page to check Freemius status
 *
 * @package    LayoutBerg
 * @subpackage Admin/Partials
 * @since      1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only allow admins to view this page
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Access denied.' );
}

echo '<div class="wrap">';
echo '<h1>' . esc_html__( 'LayoutBerg Debug Information', 'layoutberg' ) . '</h1>';

// Add description
echo '<p>' . esc_html__( 'This page shows technical information about your LayoutBerg installation and licensing status.', 'layoutberg' ) . '</p>';

echo '<h2>Freemius Status</h2>';
echo '<table class="widefat">';
echo '<thead><tr><th>Check</th><th>Status</th></tr></thead>';
echo '<tbody>';

// Check if function exists
echo '<tr>';
echo '<td>layoutberg_fs() function exists</td>';
echo '<td>' . ( function_exists( 'layoutberg_fs' ) ? '✅ Yes' : '❌ No' ) . '</td>';
echo '</tr>';

if ( function_exists( 'layoutberg_fs' ) ) {
	$fs = layoutberg_fs();
	
	// Basic checks
	$checks = array(
		'Is registered' => $fs->is_registered(),
		'Is paying' => $fs->is_paying(),
		'Can use premium code' => $fs->can_use_premium_code(),
		'Is trial' => $fs->is_trial(),
		'Is free plan' => $fs->is_free_plan(),
		'Is premium' => $fs->is_premium(),
	);
	
	foreach ( $checks as $label => $value ) {
		echo '<tr>';
		echo '<td>' . esc_html( $label ) . '</td>';
		echo '<td>' . ( $value ? '✅ Yes' : '❌ No' ) . '</td>';
		echo '</tr>';
	}
	
	// Plan info
	echo '<tr>';
	echo '<td>Current plan</td>';
	$plan = $fs->get_plan();
	if ( $plan ) {
		echo '<td>';
		echo 'Name: ' . esc_html( $plan->name ) . '<br>';
		echo 'ID: ' . esc_html( $plan->id ) . '<br>';
		echo 'Title: ' . ( isset( $plan->title ) ? esc_html( $plan->title ) : 'N/A' ) . '<br>';
		// Debug: show all plan properties
		echo '<details><summary>All properties (debug)</summary><pre>' . esc_html( print_r( $plan, true ) ) . '</pre></details>';
		echo '</td>';
	} else {
		echo '<td>None</td>';
	}
	echo '</tr>';
	
	// Check specific plans
	$plans = array( 'starter', 'professional', 'agency' );
	foreach ( $plans as $plan_slug ) {
		echo '<tr>';
		echo '<td>Is plan: ' . esc_html( $plan_slug ) . '</td>';
		echo '<td>' . ( $fs->is_plan( $plan_slug ) ? '✅ Yes' : '❌ No' ) . '</td>';
		echo '</tr>';
	}
	
	// Try checking by plan ID if we know the current plan ID
	if ( $plan ) {
		echo '<tr>';
		echo '<td>Is plan by ID (' . esc_html( $plan->id ) . ')</td>';
		echo '<td>' . ( $fs->is_plan( $plan->id ) ? '✅ Yes' : '❌ No' ) . '</td>';
		echo '</tr>';
	}
	
	// Check license object for more details
	echo '<tr>';
	echo '<td>License details</td>';
	echo '<td>';
	$license = $fs->_get_license();
	if ( $license ) {
		echo 'License ID: ' . esc_html( $license->id ) . '<br>';
		echo 'Plan ID: ' . esc_html( $license->plan_id ) . '<br>';
		echo 'Is expired: ' . ( $license->is_expired() ? 'Yes' : 'No' ) . '<br>';
		echo 'Is lifetime: ' . ( $license->is_lifetime() ? 'Yes' : 'No' ) . '<br>';
		if ( isset( $license->pricing_id ) ) {
			echo 'Pricing ID: ' . esc_html( $license->pricing_id ) . '<br>';
		}
	} else {
		echo 'No license found';
	}
	echo '</td>';
	echo '</tr>';
	
	// User info
	$user = $fs->get_user();
	if ( $user ) {
		echo '<tr>';
		echo '<td>User email</td>';
		echo '<td>' . esc_html( $user->email ) . '</td>';
		echo '</tr>';
	}
	
	// License info
	$license = $fs->_get_license();
	if ( $license ) {
		echo '<tr>';
		echo '<td>License status</td>';
		echo '<td>' . esc_html( $license->is_expired() ? 'Expired' : 'Active' ) . '</td>';
		echo '</tr>';
		
		echo '<tr>';
		echo '<td>License expiration</td>';
		echo '<td>' . esc_html( $license->expiration ) . '</td>';
		echo '</tr>';
	}
}

echo '</tbody>';
echo '</table>';

// Check licensing helper
echo '<h2>LayoutBerg Licensing Helper</h2>';
echo '<table class="widefat">';
echo '<thead><tr><th>Method</th><th>Result</th></tr></thead>';
echo '<tbody>';

if ( class_exists( 'DotCamp\LayoutBerg\LayoutBerg_Licensing' ) ) {
	$licensing_methods = array(
		'can_use_premium_code' => 'Can use premium code',
		'is_expired_monthly' => 'Is expired monthly',
		'is_starter_plan' => 'Is Starter plan',
		'is_professional_plan' => 'Is Professional plan',
		'is_agency_plan' => 'Is Agency plan',
		'can_use_all_models' => 'Can use all models',
		'can_export_templates' => 'Can export templates',
		'can_export_csv' => 'Can export CSV',
		'get_plan_name' => 'Plan name',
		'get_template_limit' => 'Template limit',
		'get_history_days' => 'History days',
	);
	
	foreach ( $licensing_methods as $method => $label ) {
		echo '<tr>';
		echo '<td>' . esc_html( $label ) . '</td>';
		$result = call_user_func( array( 'DotCamp\LayoutBerg\LayoutBerg_Licensing', $method ) );
		if ( is_bool( $result ) ) {
			echo '<td>' . ( $result ? '✅ Yes' : '❌ No' ) . '</td>';
		} else {
			echo '<td>' . esc_html( $result ) . '</td>';
		}
		echo '</tr>';
	}
} else {
	echo '<tr><td colspan="2">❌ LayoutBerg_Licensing class not found</td></tr>';
}

echo '</tbody>';
echo '</table>';

echo '</div>';