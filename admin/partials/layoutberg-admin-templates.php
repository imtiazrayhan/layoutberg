<?php
/**
 * Admin templates page.
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

<div class="wrap layoutberg-templates">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<div class="notice notice-info">
		<p><?php esc_html_e( 'Templates feature coming soon!', 'layoutberg' ); ?></p>
	</div>
</div>