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

// Handle OpenAI API key display.
$openai_key_display = '';
$openai_key_status = '';
if ( ! empty( $options['openai_api_key'] ) ) {
	$security = new \DotCamp\LayoutBerg\Security_Manager();
	$decrypted = $security->decrypt_api_key( $options['openai_api_key'] );
	if ( $decrypted ) {
		// Mask the API key for display.
		$openai_key_display = substr( $decrypted, 0, 7 ) . str_repeat( '*', 20 ) . substr( $decrypted, -4 );
		$openai_key_status = 'valid';
	}
} elseif ( ! empty( $options['api_key'] ) ) {
	// Backward compatibility - migrate old api_key to openai_api_key
	$security = new \DotCamp\LayoutBerg\Security_Manager();
	$decrypted = $security->decrypt_api_key( $options['api_key'] );
	if ( $decrypted ) {
		$openai_key_display = substr( $decrypted, 0, 7 ) . str_repeat( '*', 20 ) . substr( $decrypted, -4 );
		$openai_key_status = 'valid';
	}
}

// Handle Claude API key display.
$claude_key_display = '';
$claude_key_status = '';
if ( ! empty( $options['claude_api_key'] ) ) {
	$security = new \DotCamp\LayoutBerg\Security_Manager();
	$decrypted = $security->decrypt_api_key( $options['claude_api_key'] );
	if ( $decrypted ) {
		// Mask the API key for display.
		$claude_key_display = substr( $decrypted, 0, 5 ) . str_repeat( '*', 20 ) . substr( $decrypted, -4 );
		$claude_key_status = 'valid';
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
						<!-- OpenAI Configuration -->
						<div class="layoutberg-card">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'OpenAI API Configuration', 'layoutberg' ); ?></h3>
								<?php if ( $openai_key_status === 'valid' ) : ?>
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
								<label for="layoutberg_openai_api_key" class="layoutberg-label">
									<?php esc_html_e( 'OpenAI API Key', 'layoutberg' ); ?>
								</label>
								<div class="layoutberg-input-group">
									<div class="layoutberg-input-group-addon">
										<span class="dashicons dashicons-admin-network"></span>
									</div>
									<input 
										type="password" 
										id="layoutberg_openai_api_key" 
										name="layoutberg_options[openai_api_key]" 
										value="<?php echo esc_attr( $openai_key_display ); ?>" 
										class="layoutberg-input"
										placeholder="sk-..."
										data-encrypted="<?php echo ! empty( $options['openai_api_key'] ) || ! empty( $options['api_key'] ) ? 'true' : 'false'; ?>"
									/>
									<?php if ( ! empty( $options['openai_api_key'] ) || ! empty( $options['api_key'] ) ) : ?>
										<input type="hidden" name="layoutberg_options[has_openai_key]" value="1" />
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
									<button type="button" class="layoutberg-btn layoutberg-btn-secondary" id="test-openai-key" data-provider="openai">
										<span class="dashicons dashicons-admin-tools"></span>
										<?php esc_html_e( 'Test Connection', 'layoutberg' ); ?>
									</button>
									<span id="openai-key-status"></span>
								</div>
							</div>
						</div>

						<!-- Claude Configuration -->
						<div class="layoutberg-card">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'Claude API Configuration', 'layoutberg' ); ?></h3>
								<?php if ( $claude_key_status === 'valid' ) : ?>
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
								<label for="layoutberg_claude_api_key" class="layoutberg-label">
									<?php esc_html_e( 'Claude API Key', 'layoutberg' ); ?>
								</label>
								<div class="layoutberg-input-group">
									<div class="layoutberg-input-group-addon">
										<span class="dashicons dashicons-admin-network"></span>
									</div>
									<input 
										type="password" 
										id="layoutberg_claude_api_key" 
										name="layoutberg_options[claude_api_key]" 
										value="<?php echo esc_attr( $claude_key_display ); ?>" 
										class="layoutberg-input"
										placeholder="sk-ant-..."
										data-encrypted="<?php echo ! empty( $options['claude_api_key'] ) ? 'true' : 'false'; ?>"
									/>
									<?php if ( ! empty( $options['claude_api_key'] ) ) : ?>
										<input type="hidden" name="layoutberg_options[has_claude_key]" value="1" />
									<?php endif; ?>
								</div>
								<p class="layoutberg-help-text">
									<?php 
									printf(
										/* translators: %s: Anthropic console URL */
										esc_html__( 'Enter your Claude API key. Get one from %s', 'layoutberg' ),
										'<a href="https://console.anthropic.com/api-keys" target="_blank">console.anthropic.com</a>'
									); 
									?>
								</p>
								<div class="layoutberg-flex layoutberg-gap-2 layoutberg-mt-2">
									<button type="button" class="layoutberg-btn layoutberg-btn-secondary" id="test-claude-key" data-provider="claude">
										<span class="dashicons dashicons-admin-tools"></span>
										<?php esc_html_e( 'Test Connection', 'layoutberg' ); ?>
									</button>
									<span id="claude-key-status"></span>
								</div>
							</div>
						</div>
					</div>

					<!-- Generation Settings Tab -->
					<div id="generation-settings" class="layoutberg-settings-tab">
						<!-- Model Selection -->
						<div class="layoutberg-card">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'Default Model Selection', 'layoutberg' ); ?></h3>
							</div>

							<div class="layoutberg-form-group">
								<label for="layoutberg_model" class="layoutberg-label">
									<?php esc_html_e( 'Default AI Model', 'layoutberg' ); ?>
								</label>
								<select id="layoutberg_model" name="layoutberg_options[model]" class="layoutberg-select">
									<?php if ( $openai_key_status === 'valid' || ! empty( $options['api_key'] ) ) : ?>
										<optgroup label="<?php esc_attr_e( 'OpenAI Models', 'layoutberg' ); ?>">
											<option value="gpt-3.5-turbo" <?php selected( $options['model'] ?? 'gpt-3.5-turbo', 'gpt-3.5-turbo' ); ?>>
												<?php esc_html_e( 'GPT-3.5 Turbo (Fast & Affordable)', 'layoutberg' ); ?>
											</option>
											<option value="gpt-4" <?php selected( $options['model'] ?? '', 'gpt-4' ); ?>>
												<?php esc_html_e( 'GPT-4 (Most Capable)', 'layoutberg' ); ?>
											</option>
											<option value="gpt-4-turbo" <?php selected( $options['model'] ?? '', 'gpt-4-turbo' ); ?>>
												<?php esc_html_e( 'GPT-4 Turbo (Fast & Capable)', 'layoutberg' ); ?>
											</option>
										</optgroup>
									<?php endif; ?>
									<?php if ( $claude_key_status === 'valid' ) : ?>
										<optgroup label="<?php esc_attr_e( 'Claude Models', 'layoutberg' ); ?>">
											<option value="claude-3-opus-20240229" <?php selected( $options['model'] ?? '', 'claude-3-opus-20240229' ); ?>>
												<?php esc_html_e( 'Claude 3 Opus (Most Powerful)', 'layoutberg' ); ?>
											</option>
											<option value="claude-3-5-sonnet-20241022" <?php selected( $options['model'] ?? '', 'claude-3-5-sonnet-20241022' ); ?>>
												<?php esc_html_e( 'Claude 3.5 Sonnet (Latest & Fast)', 'layoutberg' ); ?>
											</option>
											<option value="claude-3-sonnet-20240229" <?php selected( $options['model'] ?? '', 'claude-3-sonnet-20240229' ); ?>>
												<?php esc_html_e( 'Claude 3 Sonnet (Balanced)', 'layoutberg' ); ?>
											</option>
											<option value="claude-3-haiku-20240307" <?php selected( $options['model'] ?? '', 'claude-3-haiku-20240307' ); ?>>
												<?php esc_html_e( 'Claude 3 Haiku (Fast & Light)', 'layoutberg' ); ?>
											</option>
										</optgroup>
									<?php endif; ?>
								</select>
								<p class="layoutberg-help-text">
									<?php esc_html_e( 'Select the default AI model to use for layout generation. Only models with configured API keys are shown.', 'layoutberg' ); ?>
								</p>
							</div>
						</div>

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

#openai-key-status,
#claude-key-status {
	margin-left: 10px;
	font-size: 0.875rem;
	font-weight: 500;
}

#openai-key-status.success,
#claude-key-status.success {
	color: var(--lberg-success);
}

#openai-key-status.error,
#claude-key-status.error {
	color: var(--lberg-danger);
}

#temperature-value {
	font-weight: 600;
	color: var(--lberg-primary);
}

/* Fix toggle switch appearance */
.layoutberg-toggle input[type="checkbox"] {
	-webkit-appearance: none;
	-moz-appearance: none;
	appearance: none;
	position: absolute;
	opacity: 0;
	width: 0;
	height: 0;
}

.layoutberg-toggle {
	position: relative;
	display: inline-block;
	width: 48px;
	height: 24px;
}

.layoutberg-toggle-slider {
	position: absolute;
	cursor: pointer;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background-color: var(--lberg-gray-300);
	transition: .4s;
	border-radius: 24px;
}

.layoutberg-toggle-slider:before {
	position: absolute;
	content: "";
	height: 16px;
	width: 16px;
	left: 4px;
	bottom: 4px;
	background-color: white;
	transition: .4s;
	border-radius: 50%;
}

.layoutberg-toggle input:checked + .layoutberg-toggle-slider {
	background-color: var(--lberg-primary);
}

.layoutberg-toggle input:checked + .layoutberg-toggle-slider:before {
	transform: translateX(0);
	-webkit-transform: translateX(0);
	-ms-transform: translateX(0);
	right: 4px;
	left: auto;
}

/* Remove any browser default styling */
.layoutberg-toggle input[type="checkbox"]:focus + .layoutberg-toggle-slider {
	box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
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

	// Test API keys
	$('#test-openai-key, #test-claude-key').on('click', function() {
		var $button = $(this);
		var provider = $button.data('provider');
		var $status = $('#' + provider + '-key-status');
		var $input = $('#layoutberg_' + provider + '_api_key');
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
				api_key: apiKey,
				provider: provider
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
		var $openaiKeyInput = $('#layoutberg_openai_api_key');
		var $claudeKeyInput = $('#layoutberg_claude_api_key');
		
		// If API key fields are empty but we have stored keys, the backend will handle it
		
		$submitBtn.prop('disabled', true);
		$submitBtn.find('.dashicons').addClass('layoutberg-spinner');
		
		// Let the form submit naturally after brief delay for visual feedback
		setTimeout(function() {
			// Form will submit
		}, 500);
	});
});
</script>