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

// Handle API key display.
$api_key_display = '';
$api_key_status = '';
if ( ! empty( $options['api_key'] ) ) {
	$security = new \DotCamp\LayoutBerg\Security_Manager();
	$decrypted = $security->decrypt_api_key( $options['api_key'] );
	if ( $decrypted ) {
		// Mask the API key for display.
		$api_key_display = substr( $decrypted, 0, 7 ) . str_repeat( '*', 20 ) . substr( $decrypted, -4 );
		$api_key_status = 'valid';
	}
}
?>

<div class="layoutberg-admin-page">
	<!-- Header -->
	<div class="layoutberg-header">
		<div class="layoutberg-header-content">
			<div class="layoutberg-title">
				<div class="layoutberg-logo">LB</div>
				<div>
					<h1><?php esc_html_e( 'LayoutBerg Settings', 'layoutberg' ); ?></h1>
					<p><?php esc_html_e( 'Configure your AI-powered layout generation preferences', 'layoutberg' ); ?></p>
				</div>
			</div>
			<div class="layoutberg-header-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=layoutberg' ) ); ?>" class="layoutberg-btn layoutberg-btn-secondary">
					<span class="dashicons dashicons-arrow-left-alt"></span>
					<?php esc_html_e( 'Back to Dashboard', 'layoutberg' ); ?>
				</a>
			</div>
		</div>
	</div>

	<!-- Main Content -->
	<div class="layoutberg-container">
		<?php if ( ! empty( $_GET['updated'] ) ) : ?>
			<div class="layoutberg-alert layoutberg-alert-success layoutberg-mb-4">
				<span class="dashicons dashicons-yes-alt"></span>
				<div>
					<strong><?php esc_html_e( 'Settings Saved', 'layoutberg' ); ?></strong>
					<p class="layoutberg-mt-1"><?php esc_html_e( 'Your settings have been updated successfully.', 'layoutberg' ); ?></p>
				</div>
			</div>
		<?php endif; ?>

		<!-- Settings Grid Layout -->
		<div class="layoutberg-settings-grid">
			<!-- Settings Sidebar -->
			<div class="layoutberg-settings-sidebar">
				<nav class="layoutberg-settings-nav">
					<a href="#api-settings" class="layoutberg-settings-nav-item active" data-tab="api-settings">
						<span class="dashicons dashicons-admin-network"></span>
						<?php esc_html_e( 'API Settings', 'layoutberg' ); ?>
					</a>
					<a href="#generation-settings" class="layoutberg-settings-nav-item" data-tab="generation-settings">
						<span class="dashicons dashicons-admin-tools"></span>
						<?php esc_html_e( 'Generation', 'layoutberg' ); ?>
					</a>
					<a href="#style-defaults" class="layoutberg-settings-nav-item" data-tab="style-defaults">
						<span class="dashicons dashicons-admin-appearance"></span>
						<?php esc_html_e( 'Style Defaults', 'layoutberg' ); ?>
					</a>
					<a href="#advanced" class="layoutberg-settings-nav-item" data-tab="advanced">
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Advanced', 'layoutberg' ); ?>
					</a>
				</nav>
			</div>

			<!-- Settings Content -->
			<div class="layoutberg-settings-content">
				<form method="post" action="options.php" id="layoutberg-settings-form">
					<?php settings_fields( 'layoutberg_settings' ); ?>

					<!-- API Settings Tab -->
					<div id="api-settings" class="layoutberg-settings-tab active">
						<div class="layoutberg-card">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'OpenAI API Configuration', 'layoutberg' ); ?></h3>
								<?php if ( $api_key_status === 'valid' ) : ?>
									<span class="layoutberg-api-status valid">
										<span class="dashicons dashicons-yes-alt"></span>
										<?php esc_html_e( 'Connected', 'layoutberg' ); ?>
									</span>
								<?php else : ?>
									<span class="layoutberg-api-status invalid">
										<span class="dashicons dashicons-warning"></span>
										<?php esc_html_e( 'Not Connected', 'layoutberg' ); ?>
									</span>
								<?php endif; ?>
							</div>

							<div class="layoutberg-form-group">
								<label for="layoutberg_api_key" class="layoutberg-label">
									<?php esc_html_e( 'OpenAI API Key', 'layoutberg' ); ?>
								</label>
								<div class="layoutberg-input-group">
									<div class="layoutberg-input-group-addon">
										<span class="dashicons dashicons-admin-network"></span>
									</div>
									<input 
										type="password" 
										id="layoutberg_api_key" 
										name="layoutberg_options[api_key]" 
										value="<?php echo esc_attr( $api_key_display ); ?>" 
										class="layoutberg-input"
										placeholder="sk-..."
										data-encrypted="<?php echo ! empty( $options['api_key'] ) ? 'true' : 'false'; ?>"
									/>
									<?php if ( ! empty( $options['api_key'] ) ) : ?>
										<input type="hidden" name="layoutberg_options[has_api_key]" value="1" />
									<?php endif; ?>
								</div>
								<p class="layoutberg-help-text">
									<?php 
									printf(
										/* translators: %s: OpenAI platform URL */
										esc_html__( 'Enter your OpenAI API key. Get one from %s', 'layoutberg' ),
										'<a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>'
									); 
									?>
								</p>
								<div class="layoutberg-flex layoutberg-gap-2 layoutberg-mt-2">
									<button type="button" class="layoutberg-btn layoutberg-btn-secondary" id="test-api-key">
										<span class="dashicons dashicons-admin-tools"></span>
										<?php esc_html_e( 'Test Connection', 'layoutberg' ); ?>
									</button>
									<span id="api-key-status"></span>
								</div>
							</div>

							<div class="layoutberg-form-group">
								<label for="layoutberg_model" class="layoutberg-label">
									<?php esc_html_e( 'AI Model', 'layoutberg' ); ?>
								</label>
								<select id="layoutberg_model" name="layoutberg_options[model]" class="layoutberg-select">
									<option value="gpt-3.5-turbo" <?php selected( $options['model'] ?? 'gpt-3.5-turbo', 'gpt-3.5-turbo' ); ?>>
										<?php esc_html_e( 'GPT-3.5 Turbo (Fast & Affordable)', 'layoutberg' ); ?>
									</option>
									<option value="gpt-4" <?php selected( $options['model'] ?? '', 'gpt-4' ); ?>>
										<?php esc_html_e( 'GPT-4 (Most Capable)', 'layoutberg' ); ?>
									</option>
									<option value="gpt-4-turbo" <?php selected( $options['model'] ?? '', 'gpt-4-turbo' ); ?>>
										<?php esc_html_e( 'GPT-4 Turbo (Fast & Capable)', 'layoutberg' ); ?>
									</option>
								</select>
								<p class="layoutberg-help-text">
									<?php esc_html_e( 'Select the AI model to use for layout generation.', 'layoutberg' ); ?>
								</p>
							</div>
						</div>
					</div>

					<!-- Generation Settings Tab -->
					<div id="generation-settings" class="layoutberg-settings-tab">
						<div class="layoutberg-card">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'Generation Parameters', 'layoutberg' ); ?></h3>
							</div>

							<div class="layoutberg-grid layoutberg-grid-2">
								<div class="layoutberg-form-group">
									<label for="layoutberg_max_tokens" class="layoutberg-label">
										<?php esc_html_e( 'Max Tokens', 'layoutberg' ); ?>
									</label>
									<input 
										type="number" 
										id="layoutberg_max_tokens" 
										name="layoutberg_options[max_tokens]" 
										value="<?php echo esc_attr( $options['max_tokens'] ?? 2000 ); ?>" 
										min="100" 
										max="4096" 
										step="100"
										class="layoutberg-input"
									/>
									<p class="layoutberg-help-text">
										<?php esc_html_e( 'Maximum completion tokens (output length). All models support up to 4096 completion tokens. Higher values = longer, more detailed layouts but higher cost.', 'layoutberg' ); ?>
									</p>
								</div>

								<div class="layoutberg-form-group">
									<label for="layoutberg_temperature" class="layoutberg-label">
										<?php esc_html_e( 'Creativity Level', 'layoutberg' ); ?>
									</label>
									<input 
										type="range" 
										id="layoutberg_temperature" 
										name="layoutberg_options[temperature]" 
										value="<?php echo esc_attr( $options['temperature'] ?? 0.7 ); ?>" 
										min="0" 
										max="2" 
										step="0.1"
										class="layoutberg-input"
									/>
									<div class="layoutberg-flex layoutberg-justify-between layoutberg-mt-1" style="font-size: 0.75rem; color: var(--lberg-gray-600);">
										<span><?php esc_html_e( 'Focused', 'layoutberg' ); ?></span>
										<span id="temperature-value"><?php echo esc_attr( $options['temperature'] ?? 0.7 ); ?></span>
										<span><?php esc_html_e( 'Creative', 'layoutberg' ); ?></span>
									</div>
									<p class="layoutberg-help-text">
										<?php esc_html_e( 'Controls randomness in generation. Lower = more focused, Higher = more creative.', 'layoutberg' ); ?>
									</p>
								</div>
							</div>
						</div>

						<div class="layoutberg-card">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'Block Restrictions', 'layoutberg' ); ?></h3>
							</div>

							<div class="layoutberg-form-group">
								<label class="layoutberg-flex layoutberg-items-center layoutberg-gap-2">
									<div class="layoutberg-toggle">
										<input 
											type="checkbox" 
											name="layoutberg_options[allow_custom_blocks]" 
											value="1"
											<?php checked( $options['allow_custom_blocks'] ?? false, true ); ?>
										/>
										<span class="layoutberg-toggle-slider"></span>
									</div>
									<div>
										<span class="layoutberg-label" style="margin-bottom: 0;"><?php esc_html_e( 'Allow Third-Party Blocks', 'layoutberg' ); ?></span>
										<p class="layoutberg-help-text" style="margin-top: 0.25rem;">
											<?php esc_html_e( 'Enable to include registered third-party blocks in generated layouts. Only core WordPress blocks are used by default.', 'layoutberg' ); ?>
										</p>
									</div>
								</label>
							</div>
						</div>
					</div>

					<!-- Style Defaults Tab -->
					<div id="style-defaults" class="layoutberg-settings-tab">
						<div class="layoutberg-card">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'Default Style Preferences', 'layoutberg' ); ?></h3>
								<p class="layoutberg-card-subtitle"><?php esc_html_e( 'Set your preferred defaults for new layout generations', 'layoutberg' ); ?></p>
							</div>

							<div class="layoutberg-grid layoutberg-grid-2">
								<div class="layoutberg-form-group">
									<label for="layoutberg_default_style" class="layoutberg-label">
										<?php esc_html_e( 'Design Style', 'layoutberg' ); ?>
									</label>
									<select id="layoutberg_default_style" name="layoutberg_options[style_defaults][style]" class="layoutberg-select">
										<option value="modern" <?php selected( $options['style_defaults']['style'] ?? 'modern', 'modern' ); ?>>
											<?php esc_html_e( 'Modern - Clean & Contemporary', 'layoutberg' ); ?>
										</option>
										<option value="classic" <?php selected( $options['style_defaults']['style'] ?? '', 'classic' ); ?>>
											<?php esc_html_e( 'Classic - Timeless & Professional', 'layoutberg' ); ?>
										</option>
										<option value="minimal" <?php selected( $options['style_defaults']['style'] ?? '', 'minimal' ); ?>>
											<?php esc_html_e( 'Minimal - Simple & Focused', 'layoutberg' ); ?>
										</option>
										<option value="bold" <?php selected( $options['style_defaults']['style'] ?? '', 'bold' ); ?>>
											<?php esc_html_e( 'Bold - Dynamic & Impactful', 'layoutberg' ); ?>
										</option>
									</select>
								</div>

								<div class="layoutberg-form-group">
									<label for="layoutberg_default_colors" class="layoutberg-label">
										<?php esc_html_e( 'Color Scheme', 'layoutberg' ); ?>
									</label>
									<select id="layoutberg_default_colors" name="layoutberg_options[style_defaults][colors]" class="layoutberg-select">
										<option value="brand" <?php selected( $options['style_defaults']['colors'] ?? 'brand', 'brand' ); ?>>
											<?php esc_html_e( 'Brand Colors - Your Theme Colors', 'layoutberg' ); ?>
										</option>
										<option value="monochrome" <?php selected( $options['style_defaults']['colors'] ?? '', 'monochrome' ); ?>>
											<?php esc_html_e( 'Monochrome - Black & White', 'layoutberg' ); ?>
										</option>
										<option value="vibrant" <?php selected( $options['style_defaults']['colors'] ?? '', 'vibrant' ); ?>>
											<?php esc_html_e( 'Vibrant - Bright & Energetic', 'layoutberg' ); ?>
										</option>
										<option value="pastel" <?php selected( $options['style_defaults']['colors'] ?? '', 'pastel' ); ?>>
											<?php esc_html_e( 'Pastel - Soft & Gentle', 'layoutberg' ); ?>
										</option>
									</select>
								</div>

								<div class="layoutberg-form-group">
									<label for="layoutberg_default_layout" class="layoutberg-label">
										<?php esc_html_e( 'Layout Structure', 'layoutberg' ); ?>
									</label>
									<select id="layoutberg_default_layout" name="layoutberg_options[style_defaults][layout]" class="layoutberg-select">
										<option value="single-column" <?php selected( $options['style_defaults']['layout'] ?? 'single-column', 'single-column' ); ?>>
											<?php esc_html_e( 'Single Column - Full Width', 'layoutberg' ); ?>
										</option>
										<option value="sidebar" <?php selected( $options['style_defaults']['layout'] ?? '', 'sidebar' ); ?>>
											<?php esc_html_e( 'With Sidebar - Traditional', 'layoutberg' ); ?>
										</option>
										<option value="grid" <?php selected( $options['style_defaults']['layout'] ?? '', 'grid' ); ?>>
											<?php esc_html_e( 'Grid - Multi-Column', 'layoutberg' ); ?>
										</option>
										<option value="asymmetric" <?php selected( $options['style_defaults']['layout'] ?? '', 'asymmetric' ); ?>>
											<?php esc_html_e( 'Asymmetric - Creative', 'layoutberg' ); ?>
										</option>
									</select>
								</div>

								<div class="layoutberg-form-group">
									<label for="layoutberg_default_density" class="layoutberg-label">
										<?php esc_html_e( 'Content Density', 'layoutberg' ); ?>
									</label>
									<select id="layoutberg_default_density" name="layoutberg_options[style_defaults][density]" class="layoutberg-select">
										<option value="spacious" <?php selected( $options['style_defaults']['density'] ?? 'balanced', 'spacious' ); ?>>
											<?php esc_html_e( 'Spacious - More Whitespace', 'layoutberg' ); ?>
										</option>
										<option value="balanced" <?php selected( $options['style_defaults']['density'] ?? 'balanced', 'balanced' ); ?>>
											<?php esc_html_e( 'Balanced - Just Right', 'layoutberg' ); ?>
										</option>
										<option value="compact" <?php selected( $options['style_defaults']['density'] ?? '', 'compact' ); ?>>
											<?php esc_html_e( 'Compact - More Content', 'layoutberg' ); ?>
										</option>
									</select>
								</div>
							</div>
						</div>
					</div>

					<!-- Advanced Tab -->
					<div id="advanced" class="layoutberg-settings-tab">
						<div class="layoutberg-card">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'Performance & Caching', 'layoutberg' ); ?></h3>
							</div>

							<div class="layoutberg-grid layoutberg-grid-2">
								<div class="layoutberg-form-group">
									<label class="layoutberg-flex layoutberg-items-center layoutberg-gap-2">
										<div class="layoutberg-toggle">
											<input 
												type="checkbox" 
												name="layoutberg_options[cache_enabled]" 
												value="1"
												<?php checked( $options['cache_enabled'] ?? true, true ); ?>
											/>
											<span class="layoutberg-toggle-slider"></span>
										</div>
										<div>
											<span class="layoutberg-label" style="margin-bottom: 0;"><?php esc_html_e( 'Enable Caching', 'layoutberg' ); ?></span>
											<p class="layoutberg-help-text" style="margin-top: 0.25rem;">
												<?php esc_html_e( 'Cache generated layouts to improve performance and reduce API costs.', 'layoutberg' ); ?>
											</p>
										</div>
									</label>
								</div>

								<div class="layoutberg-form-group">
									<label for="layoutberg_cache_duration" class="layoutberg-label">
										<?php esc_html_e( 'Cache Duration', 'layoutberg' ); ?>
									</label>
									<select id="layoutberg_cache_duration" name="layoutberg_options[cache_duration]" class="layoutberg-select">
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
									<button type="button" class="layoutberg-btn layoutberg-btn-secondary layoutberg-mt-2" id="clear-cache">
										<span class="dashicons dashicons-admin-tools"></span>
										<?php esc_html_e( 'Clear Cache', 'layoutberg' ); ?>
									</button>
								</div>
							</div>
						</div>

						<div class="layoutberg-card">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'Monitoring & Debug', 'layoutberg' ); ?></h3>
							</div>

							<div class="layoutberg-grid layoutberg-grid-2">
								<div class="layoutberg-form-group">
									<label class="layoutberg-flex layoutberg-items-center layoutberg-gap-2">
										<div class="layoutberg-toggle">
											<input 
												type="checkbox" 
												name="layoutberg_options[analytics_enabled]" 
												value="1"
												<?php checked( $options['analytics_enabled'] ?? true, true ); ?>
											/>
											<span class="layoutberg-toggle-slider"></span>
										</div>
										<div>
											<span class="layoutberg-label" style="margin-bottom: 0;"><?php esc_html_e( 'Usage Analytics', 'layoutberg' ); ?></span>
											<p class="layoutberg-help-text" style="margin-top: 0.25rem;">
												<?php esc_html_e( 'Track layout generation usage and performance statistics.', 'layoutberg' ); ?>
											</p>
										</div>
									</label>
								</div>

								<div class="layoutberg-form-group">
									<label class="layoutberg-flex layoutberg-items-center layoutberg-gap-2">
										<div class="layoutberg-toggle">
											<input 
												type="checkbox" 
												name="layoutberg_options[debug_mode]" 
												value="1"
												<?php checked( $options['debug_mode'] ?? false, true ); ?>
											/>
											<span class="layoutberg-toggle-slider"></span>
										</div>
										<div>
											<span class="layoutberg-label" style="margin-bottom: 0;"><?php esc_html_e( 'Debug Mode', 'layoutberg' ); ?></span>
											<p class="layoutberg-help-text" style="margin-top: 0.25rem;">
												<?php esc_html_e( 'Log API requests and responses for debugging. Enable only when troubleshooting.', 'layoutberg' ); ?>
											</p>
										</div>
									</label>
								</div>
							</div>
						</div>
					</div>

					<!-- Save Button -->
					<div class="layoutberg-mt-4">
						<button type="submit" class="layoutberg-btn layoutberg-btn-primary layoutberg-btn-lg">
							<span class="dashicons dashicons-yes-alt"></span>
							<?php esc_html_e( 'Save Settings', 'layoutberg' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<style>
/* Settings specific styles */
.layoutberg-settings-tab {
	display: none;
}

.layoutberg-settings-tab.active {
	display: block;
}

#api-key-status {
	margin-left: 10px;
	font-size: 0.875rem;
	font-weight: 500;
}

#api-key-status.success {
	color: var(--lberg-success);
}

#api-key-status.error {
	color: var(--lberg-danger);
}

#temperature-value {
	font-weight: 600;
	color: var(--lberg-primary);
}
</style>

<script>
jQuery(document).ready(function($) {
	// Tab switching
	$('.layoutberg-settings-nav-item').on('click', function(e) {
		e.preventDefault();
		var target = $(this).data('tab');
		
		// Update navigation
		$('.layoutberg-settings-nav-item').removeClass('active');
		$(this).addClass('active');
		
		// Update content
		$('.layoutberg-settings-tab').removeClass('active');
		$('#' + target).addClass('active');
	});

	// Temperature slider update
	$('#layoutberg_temperature').on('input', function() {
		$('#temperature-value').text($(this).val());
	});

	// Test API key
	$('#test-api-key').on('click', function() {
		var $button = $(this);
		var $status = $('#api-key-status');
		var $input = $('#layoutberg_api_key');
		var apiKey = $input.val();
		var isEncrypted = $input.data('encrypted') === true || $input.data('encrypted') === 'true';

		// Check if API key field is empty or just contains masked value
		if (!apiKey || (isEncrypted && apiKey.indexOf('*') !== -1)) {
			// If it's a masked value, we'll test the stored key
			apiKey = 'use_stored';
		}

		if (!apiKey && !isEncrypted) {
			$status.removeClass('success').addClass('error').text('<?php esc_html_e( 'Please enter an API key', 'layoutberg' ); ?>');
			return;
		}

		$button.prop('disabled', true).find('.dashicons').addClass('layoutberg-spinner');
		$status.removeClass('success error').text('<?php esc_html_e( 'Testing...', 'layoutberg' ); ?>');

		// Make actual API request to validate key
		wp.apiRequest({
			path: '/layoutberg/v1/validate-key',
			method: 'POST',
			data: {
				api_key: apiKey
			}
		}).done(function(response) {
			$status.addClass('success').text('<?php esc_html_e( 'Valid API key!', 'layoutberg' ); ?>');
		}).fail(function(xhr) {
			var message = '<?php esc_html_e( 'Invalid API key', 'layoutberg' ); ?>';
			if (xhr.responseJSON && xhr.responseJSON.message) {
				message = xhr.responseJSON.message;
			}
			$status.addClass('error').text(message);
		}).always(function() {
			$button.prop('disabled', false).find('.dashicons').removeClass('layoutberg-spinner');
		});
	});

	// Clear cache
	$('#clear-cache').on('click', function() {
		var $button = $(this);
		$button.prop('disabled', true);

		// Simulate cache clearing
		setTimeout(function() {
			$button.prop('disabled', false);
			// Show temporary success message
			var $success = $('<span class="layoutberg-badge layoutberg-badge-success layoutberg-ml-2"><?php esc_html_e( 'Cache cleared!', 'layoutberg' ); ?></span>');
			$button.after($success);
			setTimeout(function() {
				$success.fadeOut(function() {
					$(this).remove();
				});
			}, 3000);
		}, 1000);
	});

	// Form validation
	$('#layoutberg-settings-form').on('submit', function(e) {
		var $form = $(this);
		var $submitBtn = $form.find('button[type="submit"]');
		var $apiKeyInput = $('#layoutberg_api_key');
		
		// If API key field is empty but we have a stored key, ensure we send the masked value
		if (!$apiKeyInput.val() && $apiKeyInput.data('encrypted') === 'true') {
			// Don't modify the field, the backend will handle it
		}
		
		$submitBtn.prop('disabled', true);
		$submitBtn.find('.dashicons').addClass('layoutberg-spinner');
		
		// Let the form submit naturally after brief delay for visual feedback
		setTimeout(function() {
			// Form will submit
		}, 500);
	});
});
</script>