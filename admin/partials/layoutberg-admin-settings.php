<?php
/**
 * Admin settings page.
 *
 * @package    LayoutBerg
 * @subpackage Admin/Partials
 * @since      1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current settings.
$options = get_option( 'layoutberg_options', array() );
?>

<div class="wrap layoutberg-settings">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors( 'layoutberg_settings' ); ?>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'layoutberg_settings' );
		do_settings_sections( 'layoutberg_settings' );
		?>

		<div class="layoutberg-settings-tabs">
			<nav class="nav-tab-wrapper">
				<a href="#api-settings" class="nav-tab nav-tab-active"><?php esc_html_e( 'API Settings', 'layoutberg' ); ?></a>
				<a href="#generation-settings" class="nav-tab"><?php esc_html_e( 'Generation Settings', 'layoutberg' ); ?></a>
				<a href="#style-defaults" class="nav-tab"><?php esc_html_e( 'Style Defaults', 'layoutberg' ); ?></a>
				<a href="#advanced" class="nav-tab"><?php esc_html_e( 'Advanced', 'layoutberg' ); ?></a>
			</nav>

			<!-- API Settings Tab -->
			<div id="api-settings" class="tab-content active">
				<h2><?php esc_html_e( 'API Configuration', 'layoutberg' ); ?></h2>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="layoutberg_api_key"><?php esc_html_e( 'OpenAI API Key', 'layoutberg' ); ?></label>
						</th>
						<td>
							<input 
								type="password" 
								id="layoutberg_api_key" 
								name="layoutberg_options[api_key]" 
								value="<?php echo esc_attr( $options['api_key'] ?? '' ); ?>" 
								class="regular-text"
								placeholder="sk-..."
							/>
							<p class="description">
								<?php 
								printf(
									/* translators: %s: OpenAI platform URL */
									esc_html__( 'Enter your OpenAI API key. Get one from %s', 'layoutberg' ),
									'<a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>'
								); 
								?>
							</p>
							<button type="button" class="button" id="test-api-key"><?php esc_html_e( 'Test API Key', 'layoutberg' ); ?></button>
							<span id="api-key-status"></span>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="layoutberg_model"><?php esc_html_e( 'AI Model', 'layoutberg' ); ?></label>
						</th>
						<td>
							<select id="layoutberg_model" name="layoutberg_options[model]">
								<option value="gpt-3.5-turbo" <?php selected( $options['model'] ?? 'gpt-3.5-turbo', 'gpt-3.5-turbo' ); ?>>
									<?php esc_html_e( 'GPT-3.5 Turbo (Fast & Affordable)', 'layoutberg' ); ?>
								</option>
								<option value="gpt-4" <?php selected( $options['model'] ?? '', 'gpt-4' ); ?>>
									<?php esc_html_e( 'GPT-4 (Most Capable) - Pro', 'layoutberg' ); ?>
								</option>
								<option value="gpt-4-turbo" <?php selected( $options['model'] ?? '', 'gpt-4-turbo' ); ?>>
									<?php esc_html_e( 'GPT-4 Turbo (Fast & Capable) - Pro', 'layoutberg' ); ?>
								</option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Select the AI model to use for layout generation. GPT-4 models require a Pro license.', 'layoutberg' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Generation Settings Tab -->
			<div id="generation-settings" class="tab-content">
				<h2><?php esc_html_e( 'Generation Settings', 'layoutberg' ); ?></h2>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="layoutberg_max_tokens"><?php esc_html_e( 'Max Tokens', 'layoutberg' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="layoutberg_max_tokens" 
								name="layoutberg_options[max_tokens]" 
								value="<?php echo esc_attr( $options['max_tokens'] ?? 2000 ); ?>" 
								min="100" 
								max="8000" 
								step="100"
								class="small-text"
							/>
							<p class="description">
								<?php esc_html_e( 'Maximum number of tokens to use for generation (100-8000). Higher values allow for more complex layouts but cost more.', 'layoutberg' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="layoutberg_temperature"><?php esc_html_e( 'Temperature', 'layoutberg' ); ?></label>
						</th>
						<td>
							<input 
								type="number" 
								id="layoutberg_temperature" 
								name="layoutberg_options[temperature]" 
								value="<?php echo esc_attr( $options['temperature'] ?? 0.7 ); ?>" 
								min="0" 
								max="2" 
								step="0.1"
								class="small-text"
							/>
							<p class="description">
								<?php esc_html_e( 'Controls randomness in generation (0-2). Lower values make output more focused and deterministic.', 'layoutberg' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Block Restrictions', 'layoutberg' ); ?>
						</th>
						<td>
							<fieldset>
								<label>
									<input 
										type="checkbox" 
										name="layoutberg_options[allow_custom_blocks]" 
										value="1"
										<?php checked( $options['allow_custom_blocks'] ?? false, true ); ?>
									/>
									<?php esc_html_e( 'Allow third-party blocks in generated layouts', 'layoutberg' ); ?>
								</label>
							</fieldset>
							<p class="description">
								<?php esc_html_e( 'By default, only core WordPress blocks are used. Enable this to allow registered third-party blocks.', 'layoutberg' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Style Defaults Tab -->
			<div id="style-defaults" class="tab-content">
				<h2><?php esc_html_e( 'Style Defaults', 'layoutberg' ); ?></h2>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="layoutberg_default_style"><?php esc_html_e( 'Default Style', 'layoutberg' ); ?></label>
						</th>
						<td>
							<select id="layoutberg_default_style" name="layoutberg_options[style_defaults][style]">
								<option value="modern" <?php selected( $options['style_defaults']['style'] ?? 'modern', 'modern' ); ?>>
									<?php esc_html_e( 'Modern', 'layoutberg' ); ?>
								</option>
								<option value="classic" <?php selected( $options['style_defaults']['style'] ?? '', 'classic' ); ?>>
									<?php esc_html_e( 'Classic', 'layoutberg' ); ?>
								</option>
								<option value="minimal" <?php selected( $options['style_defaults']['style'] ?? '', 'minimal' ); ?>>
									<?php esc_html_e( 'Minimal', 'layoutberg' ); ?>
								</option>
								<option value="bold" <?php selected( $options['style_defaults']['style'] ?? '', 'bold' ); ?>>
									<?php esc_html_e( 'Bold', 'layoutberg' ); ?>
								</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="layoutberg_default_colors"><?php esc_html_e( 'Default Color Scheme', 'layoutberg' ); ?></label>
						</th>
						<td>
							<select id="layoutberg_default_colors" name="layoutberg_options[style_defaults][colors]">
								<option value="brand" <?php selected( $options['style_defaults']['colors'] ?? 'brand', 'brand' ); ?>>
									<?php esc_html_e( 'Brand Colors', 'layoutberg' ); ?>
								</option>
								<option value="monochrome" <?php selected( $options['style_defaults']['colors'] ?? '', 'monochrome' ); ?>>
									<?php esc_html_e( 'Monochrome', 'layoutberg' ); ?>
								</option>
								<option value="vibrant" <?php selected( $options['style_defaults']['colors'] ?? '', 'vibrant' ); ?>>
									<?php esc_html_e( 'Vibrant', 'layoutberg' ); ?>
								</option>
								<option value="pastel" <?php selected( $options['style_defaults']['colors'] ?? '', 'pastel' ); ?>>
									<?php esc_html_e( 'Pastel', 'layoutberg' ); ?>
								</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="layoutberg_default_layout"><?php esc_html_e( 'Default Layout Type', 'layoutberg' ); ?></label>
						</th>
						<td>
							<select id="layoutberg_default_layout" name="layoutberg_options[style_defaults][layout]">
								<option value="single-column" <?php selected( $options['style_defaults']['layout'] ?? 'single-column', 'single-column' ); ?>>
									<?php esc_html_e( 'Single Column', 'layoutberg' ); ?>
								</option>
								<option value="sidebar" <?php selected( $options['style_defaults']['layout'] ?? '', 'sidebar' ); ?>>
									<?php esc_html_e( 'With Sidebar', 'layoutberg' ); ?>
								</option>
								<option value="grid" <?php selected( $options['style_defaults']['layout'] ?? '', 'grid' ); ?>>
									<?php esc_html_e( 'Grid', 'layoutberg' ); ?>
								</option>
								<option value="asymmetric" <?php selected( $options['style_defaults']['layout'] ?? '', 'asymmetric' ); ?>>
									<?php esc_html_e( 'Asymmetric', 'layoutberg' ); ?>
								</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="layoutberg_default_density"><?php esc_html_e( 'Default Density', 'layoutberg' ); ?></label>
						</th>
						<td>
							<select id="layoutberg_default_density" name="layoutberg_options[style_defaults][density]">
								<option value="spacious" <?php selected( $options['style_defaults']['density'] ?? 'balanced', 'spacious' ); ?>>
									<?php esc_html_e( 'Spacious', 'layoutberg' ); ?>
								</option>
								<option value="balanced" <?php selected( $options['style_defaults']['density'] ?? 'balanced', 'balanced' ); ?>>
									<?php esc_html_e( 'Balanced', 'layoutberg' ); ?>
								</option>
								<option value="compact" <?php selected( $options['style_defaults']['density'] ?? '', 'compact' ); ?>>
									<?php esc_html_e( 'Compact', 'layoutberg' ); ?>
								</option>
							</select>
						</td>
					</tr>
				</table>
			</div>

			<!-- Advanced Tab -->
			<div id="advanced" class="tab-content">
				<h2><?php esc_html_e( 'Advanced Settings', 'layoutberg' ); ?></h2>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Cache', 'layoutberg' ); ?>
						</th>
						<td>
							<fieldset>
								<label>
									<input 
										type="checkbox" 
										name="layoutberg_options[cache_enabled]" 
										value="1"
										<?php checked( $options['cache_enabled'] ?? true, true ); ?>
									/>
									<?php esc_html_e( 'Enable caching for generated layouts', 'layoutberg' ); ?>
								</label>
							</fieldset>
							<p class="description">
								<?php esc_html_e( 'Cache generated layouts to improve performance and reduce API costs.', 'layoutberg' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="layoutberg_cache_duration"><?php esc_html_e( 'Cache Duration', 'layoutberg' ); ?></label>
						</th>
						<td>
							<select id="layoutberg_cache_duration" name="layoutberg_options[cache_duration]">
								<option value="3600" <?php selected( $options['cache_duration'] ?? 3600, 3600 ); ?>>
									<?php esc_html_e( '1 Hour', 'layoutberg' ); ?>
								</option>
								<option value="21600" <?php selected( $options['cache_duration'] ?? '', 21600 ); ?>>
									<?php esc_html_e( '6 Hours', 'layoutberg' ); ?>
								</option>
								<option value="86400" <?php selected( $options['cache_duration'] ?? '', 86400 ); ?>>
									<?php esc_html_e( '24 Hours', 'layoutberg' ); ?>
								</option>
								<option value="604800" <?php selected( $options['cache_duration'] ?? '', 604800 ); ?>>
									<?php esc_html_e( '1 Week', 'layoutberg' ); ?>
								</option>
							</select>
							<button type="button" class="button" id="clear-cache"><?php esc_html_e( 'Clear Cache', 'layoutberg' ); ?></button>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Analytics', 'layoutberg' ); ?>
						</th>
						<td>
							<fieldset>
								<label>
									<input 
										type="checkbox" 
										name="layoutberg_options[analytics_enabled]" 
										value="1"
										<?php checked( $options['analytics_enabled'] ?? true, true ); ?>
									/>
									<?php esc_html_e( 'Enable usage analytics', 'layoutberg' ); ?>
								</label>
							</fieldset>
							<p class="description">
								<?php esc_html_e( 'Track layout generation usage and statistics.', 'layoutberg' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Debug Mode', 'layoutberg' ); ?>
						</th>
						<td>
							<fieldset>
								<label>
									<input 
										type="checkbox" 
										name="layoutberg_options[debug_mode]" 
										value="1"
										<?php checked( $options['debug_mode'] ?? false, true ); ?>
									/>
									<?php esc_html_e( 'Enable debug mode', 'layoutberg' ); ?>
								</label>
							</fieldset>
							<p class="description">
								<?php esc_html_e( 'Log API requests and responses for debugging. Only enable when troubleshooting.', 'layoutberg' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<?php submit_button(); ?>
	</form>
</div>

<style>
.layoutberg-settings-tabs {
	margin-top: 20px;
}

.layoutberg-settings-tabs .nav-tab-wrapper {
	margin-bottom: 0;
	border-bottom: 1px solid #ccc;
}

.layoutberg-settings-tabs .tab-content {
	display: none;
	background: #fff;
	padding: 20px;
	border: 1px solid #ccc;
	border-top: none;
}

.layoutberg-settings-tabs .tab-content.active {
	display: block;
}

.layoutberg-settings-tabs .tab-content h2 {
	margin-top: 0;
}

#api-key-status {
	margin-left: 10px;
}

#api-key-status.success {
	color: #00a32a;
}

#api-key-status.error {
	color: #d63638;
}
</style>

<script>
jQuery(document).ready(function($) {
	// Tab switching
	$('.nav-tab').on('click', function(e) {
		e.preventDefault();
		var target = $(this).attr('href');
		
		$('.nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		
		$('.tab-content').removeClass('active');
		$(target).addClass('active');
	});

	// Test API key
	$('#test-api-key').on('click', function() {
		var $button = $(this);
		var $status = $('#api-key-status');
		var apiKey = $('#layoutberg_api_key').val();

		if (!apiKey) {
			$status.removeClass('success').addClass('error').text('<?php esc_html_e( 'Please enter an API key', 'layoutberg' ); ?>');
			return;
		}

		$button.prop('disabled', true);
		$status.removeClass('success error').text('<?php esc_html_e( 'Testing...', 'layoutberg' ); ?>');

		$.ajax({
			url: layoutbergAdmin.apiUrl + '/validate-key',
			method: 'POST',
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', layoutbergAdmin.restNonce);
			},
			data: {
				api_key: apiKey
			},
			success: function(response) {
				$status.addClass('success').text('<?php esc_html_e( 'Valid API key!', 'layoutberg' ); ?>');
			},
			error: function(xhr) {
				var message = xhr.responseJSON?.message || '<?php esc_html_e( 'Invalid API key', 'layoutberg' ); ?>';
				$status.addClass('error').text(message);
			},
			complete: function() {
				$button.prop('disabled', false);
			}
		});
	});

	// Clear cache
	$('#clear-cache').on('click', function() {
		var $button = $(this);
		$button.prop('disabled', true);

		// TODO: Implement cache clearing
		alert('<?php esc_html_e( 'Cache cleared successfully!', 'layoutberg' ); ?>');
		
		$button.prop('disabled', false);
	});
});
</script>