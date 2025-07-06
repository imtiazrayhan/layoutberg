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
																	<?php 
								// Ensure Model Config class is loaded
								if ( ! class_exists( '\DotCamp\LayoutBerg\Model_Config' ) ) {
									require_once LAYOUTBERG_PLUGIN_DIR . 'includes/class-model-config.php';
								}
								
								// Use Model Config for consistent model information
								try {
									$models = \DotCamp\LayoutBerg\Model_Config::get_all_models();
									$current_model = $options['model'] ?? 'gpt-3.5-turbo';
									
									// Group models by provider
									$openai_models = array();
									$claude_models = array();
									
									foreach ( $models as $model_id => $config ) {
										if ( $config['provider'] === 'openai' ) {
											$openai_models[ $model_id ] = $config;
										} elseif ( $config['provider'] === 'claude' ) {
											$claude_models[ $model_id ] = $config;
										}
									}
								} catch ( Exception $e ) {
									// Fallback to hardcoded models if Model Config fails
									$models = array();
									$current_model = $options['model'] ?? 'gpt-3.5-turbo';
									$openai_models = array();
									$claude_models = array();
									
									// Fallback models with full configuration
									if ( $openai_key_status === 'valid' || ! empty( $options['api_key'] ) ) {
										$openai_models = array(
											'gpt-3.5-turbo' => array( 
												'name' => 'GPT-3.5 Turbo', 
												'description' => 'Fast and affordable',
												'context_window' => 16385,
												'max_output' => 4096,
												'cost_per_1k_input' => 0.0005,
												'cost_per_1k_output' => 0.0015
											),
											'gpt-4' => array( 
												'name' => 'GPT-4', 
												'description' => 'Most capable model',
												'context_window' => 8192,
												'max_output' => 4096,
												'cost_per_1k_input' => 0.03,
												'cost_per_1k_output' => 0.06
											),
											'gpt-4-turbo' => array( 
												'name' => 'GPT-4 Turbo', 
												'description' => 'Fast and capable',
												'context_window' => 128000,
												'max_output' => 4096,
												'cost_per_1k_input' => 0.01,
												'cost_per_1k_output' => 0.03
											),
										);
									}
									
									if ( $claude_key_status === 'valid' ) {
										$claude_models = array(
											'claude-3-opus-20240229' => array( 
												'name' => 'Claude 3 Opus', 
												'description' => 'Most powerful Claude model',
												'context_window' => 200000,
												'max_output' => 4096,
												'cost_per_1k_input' => 0.015,
												'cost_per_1k_output' => 0.075
											),
											'claude-3-5-sonnet-20241022' => array( 
												'name' => 'Claude 3.5 Sonnet', 
												'description' => 'Latest balanced Claude model',
												'context_window' => 200000,
												'max_output' => 8192,
												'cost_per_1k_input' => 0.003,
												'cost_per_1k_output' => 0.015
											),
											'claude-3-haiku-20240307' => array( 
												'name' => 'Claude 3 Haiku', 
												'description' => 'Fast and affordable Claude model',
												'context_window' => 200000,
												'max_output' => 4096,
												'cost_per_1k_input' => 0.00025,
												'cost_per_1k_output' => 0.00125
											),
										);
									}
								}
									
									// Show OpenAI models if API key is configured
									if ( $openai_key_status === 'valid' || ! empty( $options['api_key'] ) ) : ?>
										<optgroup label="<?php esc_attr_e( 'OpenAI Models', 'layoutberg' ); ?>">
											<?php foreach ( $openai_models as $model_id => $config ) : ?>
												<option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( $current_model, $model_id ); ?>>
													<?php echo esc_html( $config['name'] . ' - ' . $config['description'] ); ?>
													<?php if ( isset( $config['context_window'] ) && $config['context_window'] > 100000 ) : ?>
														<?php esc_html_e( ' (Long context)', 'layoutberg' ); ?>
													<?php endif; ?>
												</option>
											<?php endforeach; ?>
										</optgroup>
									<?php endif; ?>
									
									<?php // Show Claude models if API key is configured
									if ( $claude_key_status === 'valid' ) : ?>
										<optgroup label="<?php esc_attr_e( 'Claude Models', 'layoutberg' ); ?>">
											<?php foreach ( $claude_models as $model_id => $config ) : ?>
												<option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( $current_model, $model_id ); ?>>
													<?php echo esc_html( $config['name'] . ' - ' . $config['description'] ); ?>
													<?php if ( isset( $config['context_window'] ) && $config['context_window'] > 100000 ) : ?>
														<?php esc_html_e( ' (Long context)', 'layoutberg' ); ?>
													<?php endif; ?>
												</option>
											<?php endforeach; ?>
										</optgroup>
									<?php endif; ?>
								</select>
								<p class="layoutberg-help-text">
									<?php esc_html_e( 'Select the default AI model to use for layout generation. Only models with configured API keys are shown.', 'layoutberg' ); ?>
								</p>
								
								<script>
window.layoutbergModels = <?php echo json_encode($models); ?>;
</script>

<?php 
// Debug: Check what models are available
error_log( 'LayoutBerg Debug: Models array keys: ' . print_r( array_keys( $models ), true ) );
error_log( 'LayoutBerg Debug: Current model: ' . $current_model );
error_log( 'LayoutBerg Debug: Models array: ' . print_r( $models, true ) );
								
								// Show model information if a model is selected
								$model_config = null;
								if ( ! empty( $current_model ) ) {
									// Check Model Config first
									if ( isset( $models[ $current_model ] ) && isset( $models[ $current_model ]['context_window'] ) ) {
										$model_config = $models[ $current_model ];
										error_log( 'LayoutBerg Debug: Using Model Config for ' . $current_model );
									}
									// Check fallback models if not found in Model Config
									elseif ( isset( $openai_models[ $current_model ] ) && isset( $openai_models[ $current_model ]['context_window'] ) ) {
										$model_config = $openai_models[ $current_model ];
										error_log( 'LayoutBerg Debug: Using fallback OpenAI model for ' . $current_model );
									}
									elseif ( isset( $claude_models[ $current_model ] ) && isset( $claude_models[ $current_model ]['context_window'] ) ) {
										$model_config = $claude_models[ $current_model ];
										error_log( 'LayoutBerg Debug: Using fallback Claude model for ' . $current_model );
									}
									else {
										error_log( 'LayoutBerg Debug: No model config found for ' . $current_model );
									}
								}
								
								if ( $model_config ) :
								?>
								<div class="layoutberg-model-info layoutberg-mt-3">
									<div class="layoutberg-grid layoutberg-grid-3">
										<div>
											<strong><?php esc_html_e( 'Context Window:', 'layoutberg' ); ?></strong>
											<br>
											<span id="layoutberg-context-window"><?php echo esc_html( number_format( $model_config['context_window'] ) ); ?></span> tokens
										</div>
										<div>
											<strong><?php esc_html_e( 'Max Output:', 'layoutberg' ); ?></strong>
											<br>
											<span id="layoutberg-max-output"><?php echo esc_html( number_format( $model_config['max_output'] ) ); ?></span> tokens
										</div>
										<div>
											<strong><?php esc_html_e( 'Cost:', 'layoutberg' ); ?></strong>
											<br>
											$<span id="layoutberg-cost-input"><?php echo esc_html( $model_config['cost_per_1k_input'] ); ?></span>/1k input
											<br>
											$<span id="layoutberg-cost-output"><?php echo esc_html( $model_config['cost_per_1k_output'] ); ?></span>/1k output
										</div>
									</div>
								</div>
								<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modelSelect = document.getElementById('layoutberg_model');
    if (!modelSelect || !window.layoutbergModels) return;
    
    modelSelect.addEventListener('change', function() {
        var modelId = this.value;
        var info = window.layoutbergModels[modelId];
        if (!info) return;
        
        var context = document.getElementById('layoutberg-context-window');
        var maxOut = document.getElementById('layoutberg-max-output');
        var costIn = document.getElementById('layoutberg-cost-input');
        var costOut = document.getElementById('layoutberg-cost-output');
        var maxTokensInput = document.getElementById('layoutberg_max_tokens');
        var helpText = maxTokensInput ? maxTokensInput.parentNode.querySelector('.layoutberg-help-text') : null;
        
        if (context) context.textContent = info.context_window.toLocaleString();
        if (maxOut) maxOut.textContent = info.max_output.toLocaleString();
        if (costIn) costIn.textContent = info.cost_per_1k_input;
        if (costOut) costOut.textContent = info.cost_per_1k_output;
        
        // Update max tokens input
        if (maxTokensInput) {
            maxTokensInput.max = info.max_output;
            // If current value exceeds new limit, adjust it
            if (parseInt(maxTokensInput.value) > info.max_output) {
                maxTokensInput.value = info.max_output;
            }
        }
        
        // Update help text
        if (helpText) {
            helpText.textContent = 'Maximum completion tokens (output length). This model supports up to ' + info.max_output.toLocaleString() + ' completion tokens. Higher values = longer, more detailed layouts but higher cost.';
        }
    });
});
</script>
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
										max="<?php echo esc_attr( $model_config ? $model_config['max_output'] : 4096 ); ?>" 
										step="100"
										class="layoutberg-input"
									/>
									<p class="layoutberg-help-text">
										<?php 
										if ( $model_config ) {
											printf( esc_html__( 'Maximum completion tokens (output length). This model supports up to %d completion tokens. Higher values = longer, more detailed layouts but higher cost.', 'layoutberg' ), $model_config['max_output'] );
										} else {
											esc_html_e( 'Maximum completion tokens (output length). All models support up to 4096 completion tokens. Higher values = longer, more detailed layouts but higher cost.', 'layoutberg' );
										}
										?>
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
						
						<!-- Experimental Features -->
						<div class="layoutberg-card layoutberg-mt-4">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'Experimental Features', 'layoutberg' ); ?></h3>
							</div>
							
							<div class="layoutberg-form-group">
								<label class="layoutberg-flex layoutberg-items-center layoutberg-gap-2">
									<div class="layoutberg-toggle">
										<input 
											type="checkbox" 
											name="layoutberg_options[use_simplified_generation]" 
											value="1"
											<?php checked( $options['use_simplified_generation'] ?? false, true ); ?>
										/>
										<span class="layoutberg-toggle-slider"></span>
									</div>
									<div>
										<span class="layoutberg-label" style="margin-bottom: 0;"><?php esc_html_e( 'Use Simplified Generation', 'layoutberg' ); ?></span>
										<p class="layoutberg-help-text" style="margin-top: 0.25rem;">
											<?php esc_html_e( 'Enable simplified block generation with minimal validation for better compatibility. This uses a simpler prompt system and lower temperature setting similar to other successful block generation plugins.', 'layoutberg' ); ?>
										</p>
									</div>
								</label>
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
	console.log('LayoutBerg settings page loaded');
	
	// Tab switching
	$('.layoutberg-settings-nav-item').on('click', function(e) {
		e.preventDefault();
		var target = $(this).data('tab');
		console.log('Tab clicked:', target);
		
		// Update navigation
		$('.layoutberg-settings-nav-item').removeClass('active');
		$(this).addClass('active');
		
		// Update content
		$('.layoutberg-settings-tab').removeClass('active');
		$('#' + target).addClass('active');
		console.log('Tab switched to:', target);
	});

	// Temperature slider update
	$('#layoutberg_temperature').on('input', function() {
		$('#temperature-value').text($(this).val());
	});
	
	// Model selection debugging
	$('#layoutberg_model').on('change', function() {
		console.log('Model changed to:', $(this).val());
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
		var originalText = $button.html();
		
		$button.prop('disabled', true);
		$button.html('<span class="dashicons dashicons-update layoutberg-spinner"></span> <?php esc_html_e( 'Clearing...', 'layoutberg' ); ?>');

		// Make AJAX request to clear cache
		jQuery.post(ajaxurl, {
			action: 'layoutberg_clear_cache',
			nonce: '<?php echo wp_create_nonce( 'layoutberg_admin_nonce' ); ?>'
		})
		.done(function(response) {
			if (response.success) {
				// Show success message
				var $success = $('<span class="layoutberg-badge layoutberg-badge-success layoutberg-ml-2">' + response.data.message + '</span>');
				$button.after($success);
				
				// Log cache stats if available
				if (response.data.stats) {
					console.log('Cache stats after clearing:', response.data.stats);
				}
				
				setTimeout(function() {
					$success.fadeOut(function() {
						$(this).remove();
					});
				}, 3000);
			} else {
				// Show error message
				var $error = $('<span class="layoutberg-badge layoutberg-badge-danger layoutberg-ml-2">' + response.data + '</span>');
				$button.after($error);
				setTimeout(function() {
					$error.fadeOut(function() {
						$(this).remove();
					});
				}, 3000);
			}
		})
		.fail(function() {
			// Show generic error message
			var $error = $('<span class="layoutberg-badge layoutberg-badge-danger layoutberg-ml-2"><?php esc_html_e( 'Failed to clear cache', 'layoutberg' ); ?></span>');
			$button.after($error);
			setTimeout(function() {
				$error.fadeOut(function() {
					$(this).remove();
				});
			}, 3000);
		})
		.always(function() {
			$button.prop('disabled', false);
			$button.html(originalText);
		});
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