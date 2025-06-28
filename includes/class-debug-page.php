<?php
/**
 * Debug page for LayoutBerg
 *
 * @package    LayoutBerg
 * @subpackage Admin
 * @since      1.0.0
 */

namespace DotCamp\LayoutBerg;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Debug page class.
 *
 * @since 1.0.0
 */
class Debug_Page {

	/**
	 * Render the debug page.
	 *
	 * @since 1.0.0
	 */
	public static function render() {
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Access denied' );
		}
		?>
		<div class="wrap">
			<h1>LayoutBerg Debug Information</h1>
			
			<div class="card">
				<h2>Environment Check</h2>
				<table class="widefat">
					<tbody>
						<tr>
							<td><strong>PHP Version:</strong></td>
							<td><?php echo PHP_VERSION; ?></td>
						</tr>
						<tr>
							<td><strong>WordPress Version:</strong></td>
							<td><?php echo get_bloginfo( 'version' ); ?></td>
						</tr>
						<tr>
							<td><strong>Plugin Version:</strong></td>
							<td><?php echo LAYOUTBERG_VERSION; ?></td>
						</tr>
						<tr>
							<td><strong>Debug Mode:</strong></td>
							<td><?php echo defined( 'WP_DEBUG' ) && WP_DEBUG ? '<span style="color: green;">✓ Enabled</span>' : '<span style="color: red;">✗ Disabled</span>'; ?></td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="card">
				<h2>Class Availability</h2>
				<table class="widefat">
					<tbody>
						<?php
						$classes = array(
							'API_Client'        => 'API Client for OpenAI',
							'Block_Generator'   => 'Block Generator',
							'Block_Serializer'  => 'Block Serializer',
							'Cache_Manager'     => 'Cache Manager',
							'Security_Manager'  => 'Security Manager',
							'Prompt_Engineer'   => 'Prompt Engineer',
							'Input_Sanitizer'   => 'Input Sanitizer',
							'Template_Manager'  => 'Template Manager',
						);

						foreach ( $classes as $class => $description ) {
							$full_class = 'DotCamp\\LayoutBerg\\' . $class;
							$exists = class_exists( $full_class );
							?>
							<tr>
								<td><strong><?php echo esc_html( $description ); ?>:</strong></td>
								<td><?php echo $exists ? '<span style="color: green;">✓ Available</span>' : '<span style="color: red;">✗ Missing</span>'; ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</div>

			<div class="card">
				<h2>API Configuration</h2>
				<table class="widefat">
					<tbody>
						<?php
						$options = get_option( 'layoutberg_options', array() );
						$has_api_key = ! empty( $options['api_key'] );
						?>
						<tr>
							<td><strong>API Key Status:</strong></td>
							<td><?php echo $has_api_key ? '<span style="color: green;">✓ Configured</span>' : '<span style="color: red;">✗ Not configured</span>'; ?></td>
						</tr>
						<?php if ( $has_api_key ) : ?>
							<tr>
								<td><strong>API Key Validation:</strong></td>
								<td>
									<?php
									try {
										$security = new Security_Manager();
										$decrypted = $security->decrypt_api_key( $options['api_key'] );
										if ( $decrypted && preg_match( '/^sk-[a-zA-Z0-9]{48}$/', $decrypted ) ) {
											echo '<span style="color: green;">✓ Valid format</span>';
										} else {
											echo '<span style="color: orange;">⚠ Invalid format</span>';
										}
									} catch ( \Exception $e ) {
										echo '<span style="color: red;">✗ Decryption error: ' . esc_html( $e->getMessage() ) . '</span>';
									}
									?>
								</td>
							</tr>
						<?php endif; ?>
						<tr>
							<td><strong>Selected Model:</strong></td>
							<td><?php echo esc_html( $options['model'] ?? 'gpt-3.5-turbo' ); ?></td>
						</tr>
						<tr>
							<td><strong>Max Tokens:</strong></td>
							<td><?php echo esc_html( $options['max_tokens'] ?? 4000 ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="card">
				<h2>Component Tests</h2>
				<table class="widefat">
					<tbody>
						<tr>
							<td><strong>API Client Initialization:</strong></td>
							<td>
								<?php
								try {
									$api_client = new API_Client();
									echo '<span style="color: green;">✓ Success</span>';
								} catch ( \Exception $e ) {
									echo '<span style="color: red;">✗ Error: ' . esc_html( $e->getMessage() ) . '</span>';
								}
								?>
							</td>
						</tr>
						<tr>
							<td><strong>Block Generator Initialization:</strong></td>
							<td>
								<?php
								try {
									$generator = new Block_Generator();
									echo '<span style="color: green;">✓ Success</span>';
								} catch ( \Exception $e ) {
									echo '<span style="color: red;">✗ Error: ' . esc_html( $e->getMessage() ) . '</span>';
								}
								?>
							</td>
						</tr>
						<tr>
							<td><strong>Security Manager Initialization:</strong></td>
							<td>
								<?php
								try {
									$security = new Security_Manager();
									echo '<span style="color: green;">✓ Success</span>';
								} catch ( \Exception $e ) {
									echo '<span style="color: red;">✗ Error: ' . esc_html( $e->getMessage() ) . '</span>';
								}
								?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="card">
				<h2>REST API Endpoints</h2>
				<table class="widefat">
					<tbody>
						<?php
						$endpoints = array(
							'/layoutberg/v1/generate'      => 'Layout Generation',
							'/layoutberg/v1/validate-key'  => 'API Key Validation',
							'/layoutberg/v1/templates'     => 'Template Management',
							'/layoutberg/v1/usage'         => 'Usage Statistics',
						);

						foreach ( $endpoints as $endpoint => $description ) {
							?>
							<tr>
								<td><strong><?php echo esc_html( $description ); ?>:</strong></td>
								<td><code><?php echo esc_html( rest_url( $endpoint ) ); ?></code></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</div>

			<div class="card">
				<h2>Quick Test</h2>
				<p>Click the button below to test the generation endpoint (dry run - no API call):</p>
				<button type="button" class="button button-primary" id="test-generation">Test Generation Endpoint</button>
				<div id="test-result" style="margin-top: 10px;"></div>
			</div>

			<script>
			jQuery(document).ready(function($) {
				$('#test-generation').on('click', function() {
					var $button = $(this);
					var $result = $('#test-result');
					
					$button.prop('disabled', true).text('Testing...');
					$result.html('<p>Running test...</p>');
					
					wp.apiRequest({
						path: '/layoutberg/v1/generate',
						method: 'POST',
						data: {
							prompt: 'Test prompt',
							settings: {
								model: 'gpt-3.5-turbo',
								maxTokens: 100
							}
						}
					}).done(function(response) {
						$result.html('<p style="color: green;"><strong>Success!</strong> The endpoint is accessible.</p>');
						if (response.data && response.data.error) {
							$result.append('<p>Error: ' + response.data.error + '</p>');
						}
					}).fail(function(xhr) {
						var message = 'Unknown error';
						if (xhr.responseJSON && xhr.responseJSON.message) {
							message = xhr.responseJSON.message;
						} else if (xhr.responseText) {
							message = xhr.responseText;
						}
						$result.html('<p style="color: red;"><strong>Error:</strong> ' + message + '</p>');
						if (xhr.status) {
							$result.append('<p>HTTP Status: ' + xhr.status + '</p>');
						}
					}).always(function() {
						$button.prop('disabled', false).text('Test Generation Endpoint');
					});
				});
			});
			</script>
		</div>
		<?php
	}
}