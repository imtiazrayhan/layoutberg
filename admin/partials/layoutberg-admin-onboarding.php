<?php
/**
 * Admin onboarding page.
 *
 * @package    LayoutBerg
 * @subpackage Admin/Partials
 * @since      1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<div class="layoutberg-onboarding-wrapper">
		<div id="layoutberg-onboarding-root"></div>
	</div>
</div>

<style>
/* Onboarding wrapper */
.layoutberg-onboarding-wrapper {
	margin-top: 20px;
	max-width: 100%;
}

/* Loading state */
.layoutberg-onboarding-loading {
	display: flex;
	align-items: center;
	justify-content: center;
	min-height: 400px;
	font-size: 1.25rem;
	color: #666;
}

.layoutberg-onboarding-loading .spinner {
	float: none;
	margin: 0 10px 0 0;
}
</style>

<script>
// Show loading state until React app loads
document.getElementById('layoutberg-onboarding-root').innerHTML = '<div class="layoutberg-onboarding-loading"><span class="spinner is-active"></span>Loading onboarding...</div>';
</script>