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
$openai_key_status  = '';
if ( ! empty( $options['openai_api_key'] ) ) {
	$security  = new \DotCamp\LayoutBerg\Security_Manager();
	$decrypted = $security->decrypt_api_key( $options['openai_api_key'] );
	if ( $decrypted ) {
		// Mask the API key for display.
		$openai_key_display = substr( $decrypted, 0, 7 ) . str_repeat( '*', 20 ) . substr( $decrypted, -4 );
		$openai_key_status  = 'valid';
	}
} elseif ( ! empty( $options['api_key'] ) ) {
	// Backward compatibility - migrate old api_key to openai_api_key
	$security  = new \DotCamp\LayoutBerg\Security_Manager();
	$decrypted = $security->decrypt_api_key( $options['api_key'] );
	if ( $decrypted ) {
		$openai_key_display = substr( $decrypted, 0, 7 ) . str_repeat( '*', 20 ) . substr( $decrypted, -4 );
		$openai_key_status  = 'valid';
	}
}

// Handle Claude API key display.
$claude_key_display = '';
$claude_key_status  = '';
if ( ! empty( $options['claude_api_key'] ) ) {
	$security  = new \DotCamp\LayoutBerg\Security_Manager();
	$decrypted = $security->decrypt_api_key( $options['claude_api_key'] );
	if ( $decrypted ) {
		// Mask the API key for display.
		$claude_key_display = substr( $decrypted, 0, 5 ) . str_repeat( '*', 20 ) . substr( $decrypted, -4 );
		$claude_key_status  = 'valid';
	}
}
?>

<div class="layoutberg-admin-page">
	<!-- Header -->
	<div class="layoutberg-header">
		<div class="layoutberg-header-content">
			<div class="layoutberg-title">
				<div class="layoutberg-logo">
					<img src="<?php echo esc_url( LAYOUTBERG_PLUGIN_URL . 'assets/images/layoutberg-logo.png' ); ?>" alt="<?php esc_attr_e( 'LayoutBerg Logo', 'layoutberg' ); ?>" />
				</div>
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
					<?php if ( \DotCamp\LayoutBerg\LayoutBerg_Licensing::is_professional_plan() || \DotCamp\LayoutBerg\LayoutBerg_Licensing::is_agency_plan() ) : ?>
						<a href="#style-defaults" class="layoutberg-settings-nav-item" data-tab="style-defaults">
							<span class="dashicons dashicons-art"></span>
							<?php esc_html_e( 'Style Defaults', 'layoutberg' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( \DotCamp\LayoutBerg\LayoutBerg_Licensing::is_agency_plan() ) : ?>
						<a href="#agency-features" class="layoutberg-settings-nav-item" data-tab="agency-features">
							<span class="dashicons dashicons-building"></span>
							<?php esc_html_e( 'Agency Features', 'layoutberg' ); ?>
						</a>
					<?php endif; ?>
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
																		$models        = \DotCamp\LayoutBerg\Model_Config::get_all_models();
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
																		$models        = array();
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
																					'cost_per_1k_output' => 0.0015,
																				),
																				'gpt-4'       => array(
																					'name' => 'GPT-4',
																					'description' => 'Most capable model',
																					'context_window' => 8192,
																					'max_output' => 4096,
																					'cost_per_1k_input' => 0.03,
																					'cost_per_1k_output' => 0.06,
																				),
																				'gpt-4-turbo' => array(
																					'name' => 'GPT-4 Turbo',
																					'description' => 'Fast and capable',
																					'context_window' => 128000,
																					'max_output' => 4096,
																					'cost_per_1k_input' => 0.01,
																					'cost_per_1k_output' => 0.03,
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
																					'cost_per_1k_output' => 0.075,
																				),
																				'claude-3-5-sonnet-20241022' => array(
																					'name' => 'Claude 3.5 Sonnet',
																					'description' => 'Latest balanced Claude model',
																					'context_window' => 200000,
																					'max_output' => 8192,
																					'cost_per_1k_input' => 0.003,
																					'cost_per_1k_output' => 0.015,
																				),
																				'claude-3-haiku-20240307' => array(
																					'name' => 'Claude 3 Haiku',
																					'description' => 'Fast and affordable Claude model',
																					'context_window' => 200000,
																					'max_output' => 4096,
																					'cost_per_1k_input' => 0.00025,
																					'cost_per_1k_output' => 0.00125,
																				),
																			);
																		}
																	}

																	// Check if user can use all models
																	$can_use_all_models = \DotCamp\LayoutBerg\LayoutBerg_Licensing::can_use_all_models();

																	// Show OpenAI models if API key is configured
																	if ( $openai_key_status === 'valid' || ! empty( $options['api_key'] ) ) :
																		?>
										<optgroup label="<?php esc_attr_e( 'OpenAI Models', 'layoutberg' ); ?>">
																			<?php
																			foreach ( $openai_models as $model_id => $config ) :
																				// GPT-3.5 Turbo is available to all plans
																				$is_restricted = ( $model_id !== 'gpt-3.5-turbo' && ! $can_use_all_models );
																				?>
																				<?php if ( ! $is_restricted ) : ?>
													<option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( $current_model, $model_id ); ?>>
																					<?php echo esc_html( $config['name'] . ' - ' . $config['description'] ); ?>
																					<?php if ( isset( $config['context_window'] ) && $config['context_window'] > 100000 ) : ?>
																						<?php esc_html_e( ' (Long context)', 'layoutberg' ); ?>
														<?php endif; ?>
													</option>
												<?php else : ?>
													<option value="<?php echo esc_attr( $model_id ); ?>" disabled>
														<?php echo esc_html( $config['name'] . ' - ' . $config['description'] ); ?> 
														<?php esc_html_e( ' (Professional plan required)', 'layoutberg' ); ?>
													</option>
												<?php endif; ?>
																			<?php endforeach; ?>
										</optgroup>
																	<?php endif; ?>
									
									<?php
									// Show Claude models if API key is configured
									if ( $claude_key_status === 'valid' ) :
										?>
										<optgroup label="<?php esc_attr_e( 'Claude Models', 'layoutberg' ); ?>">
											<?php foreach ( $claude_models as $model_id => $config ) : ?>
												<?php if ( $can_use_all_models ) : ?>
													<option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( $current_model, $model_id ); ?>>
														<?php echo esc_html( $config['name'] . ' - ' . $config['description'] ); ?>
														<?php if ( isset( $config['context_window'] ) && $config['context_window'] > 100000 ) : ?>
															<?php esc_html_e( ' (Long context)', 'layoutberg' ); ?>
														<?php endif; ?>
													</option>
												<?php else : ?>
													<option value="<?php echo esc_attr( $model_id ); ?>" disabled>
														<?php echo esc_html( $config['name'] . ' - ' . $config['description'] ); ?> 
														<?php esc_html_e( ' (Professional plan required)', 'layoutberg' ); ?>
													</option>
												<?php endif; ?>
											<?php endforeach; ?>
										</optgroup>
									<?php endif; ?>
								</select>
								<p class="layoutberg-help-text">
									<?php esc_html_e( 'Select the default AI model to use for layout generation. Only models with configured API keys are shown.', 'layoutberg' ); ?>
								</p>
								
								<?php if ( ! $can_use_all_models ) : ?>
									<div class="layoutberg-upgrade-notice layoutberg-mt-3">
										<p><?php esc_html_e( 'Upgrade to Professional plan to unlock all AI models including GPT-4 and Claude.', 'layoutberg' ); ?></p>
										<?php
										echo \DotCamp\LayoutBerg\LayoutBerg_Licensing::get_locked_button(
											__( 'Unlock All Models', 'layoutberg' ),
											__( 'All AI Models', 'layoutberg' ),
											'professional'
										);
										?>
									</div>
								<?php endif; ?>
								
								<script>
window.layoutbergModels = <?php echo json_encode( $models ); ?>;
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
	} elseif ( isset( $claude_models[ $current_model ] ) && isset( $claude_models[ $current_model ]['context_window'] ) ) {
		$model_config = $claude_models[ $current_model ];
		error_log( 'LayoutBerg Debug: Using fallback Claude model for ' . $current_model );
	} else {
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

					<?php if ( \DotCamp\LayoutBerg\LayoutBerg_Licensing::is_professional_plan() || \DotCamp\LayoutBerg\LayoutBerg_Licensing::is_agency_plan() ) : ?>
					<!-- Style Defaults Tab -->
					<div id="style-defaults" class="layoutberg-settings-tab">
						<div class="layoutberg-card">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'Typography Defaults', 'layoutberg' ); ?></h3>
							</div>

							<div class="layoutberg-grid layoutberg-grid-2">
								<div class="layoutberg-form-group">
									<label for="layoutberg_default_heading_size" class="layoutberg-label">
										<?php esc_html_e( 'Default Heading Size', 'layoutberg' ); ?>
									</label>
									<select id="layoutberg_default_heading_size" name="layoutberg_options[default_heading_size]" class="layoutberg-select">
										<option value="default" <?php selected( $options['default_heading_size'] ?? 'default', 'default' ); ?>>
											<?php esc_html_e( 'Theme Default', 'layoutberg' ); ?>
										</option>
										<option value="small" <?php selected( $options['default_heading_size'] ?? '', 'small' ); ?>>
											<?php esc_html_e( 'Small', 'layoutberg' ); ?>
										</option>
										<option value="medium" <?php selected( $options['default_heading_size'] ?? '', 'medium' ); ?>>
											<?php esc_html_e( 'Medium', 'layoutberg' ); ?>
										</option>
										<option value="large" <?php selected( $options['default_heading_size'] ?? '', 'large' ); ?>>
											<?php esc_html_e( 'Large', 'layoutberg' ); ?>
										</option>
										<option value="x-large" <?php selected( $options['default_heading_size'] ?? '', 'x-large' ); ?>>
											<?php esc_html_e( 'Extra Large', 'layoutberg' ); ?>
										</option>
									</select>
									<p class="layoutberg-help-text">
										<?php esc_html_e( 'Set the default size for headings in generated layouts.', 'layoutberg' ); ?>
									</p>
								</div>

								<div class="layoutberg-form-group">
									<label for="layoutberg_default_text_size" class="layoutberg-label">
										<?php esc_html_e( 'Default Text Size', 'layoutberg' ); ?>
									</label>
									<select id="layoutberg_default_text_size" name="layoutberg_options[default_text_size]" class="layoutberg-select">
										<option value="default" <?php selected( $options['default_text_size'] ?? 'default', 'default' ); ?>>
											<?php esc_html_e( 'Theme Default', 'layoutberg' ); ?>
										</option>
										<option value="small" <?php selected( $options['default_text_size'] ?? '', 'small' ); ?>>
											<?php esc_html_e( 'Small', 'layoutberg' ); ?>
										</option>
										<option value="medium" <?php selected( $options['default_text_size'] ?? '', 'medium' ); ?>>
											<?php esc_html_e( 'Medium', 'layoutberg' ); ?>
										</option>
										<option value="large" <?php selected( $options['default_text_size'] ?? '', 'large' ); ?>>
											<?php esc_html_e( 'Large', 'layoutberg' ); ?>
										</option>
									</select>
									<p class="layoutberg-help-text">
										<?php esc_html_e( 'Set the default size for body text in generated layouts.', 'layoutberg' ); ?>
									</p>
								</div>

								<div class="layoutberg-form-group">
									<label for="layoutberg_default_font_weight" class="layoutberg-label">
										<?php esc_html_e( 'Default Font Weight', 'layoutberg' ); ?>
									</label>
									<select id="layoutberg_default_font_weight" name="layoutberg_options[default_font_weight]" class="layoutberg-select">
										<option value="default" <?php selected( $options['default_font_weight'] ?? 'default', 'default' ); ?>>
											<?php esc_html_e( 'Theme Default', 'layoutberg' ); ?>
										</option>
										<option value="300" <?php selected( $options['default_font_weight'] ?? '', '300' ); ?>>
											<?php esc_html_e( 'Light (300)', 'layoutberg' ); ?>
										</option>
										<option value="400" <?php selected( $options['default_font_weight'] ?? '', '400' ); ?>>
											<?php esc_html_e( 'Normal (400)', 'layoutberg' ); ?>
										</option>
										<option value="500" <?php selected( $options['default_font_weight'] ?? '', '500' ); ?>>
											<?php esc_html_e( 'Medium (500)', 'layoutberg' ); ?>
										</option>
										<option value="600" <?php selected( $options['default_font_weight'] ?? '', '600' ); ?>>
											<?php esc_html_e( 'Semi Bold (600)', 'layoutberg' ); ?>
										</option>
										<option value="700" <?php selected( $options['default_font_weight'] ?? '', '700' ); ?>>
											<?php esc_html_e( 'Bold (700)', 'layoutberg' ); ?>
										</option>
									</select>
									<p class="layoutberg-help-text">
										<?php esc_html_e( 'Set the default font weight for text in generated layouts.', 'layoutberg' ); ?>
									</p>
								</div>

								<div class="layoutberg-form-group">
									<label for="layoutberg_default_text_align" class="layoutberg-label">
										<?php esc_html_e( 'Default Text Alignment', 'layoutberg' ); ?>
									</label>
									<select id="layoutberg_default_text_align" name="layoutberg_options[default_text_align]" class="layoutberg-select">
										<option value="default" <?php selected( $options['default_text_align'] ?? 'default', 'default' ); ?>>
											<?php esc_html_e( 'Theme Default', 'layoutberg' ); ?>
										</option>
										<option value="left" <?php selected( $options['default_text_align'] ?? '', 'left' ); ?>>
											<?php esc_html_e( 'Left', 'layoutberg' ); ?>
										</option>
										<option value="center" <?php selected( $options['default_text_align'] ?? '', 'center' ); ?>>
											<?php esc_html_e( 'Center', 'layoutberg' ); ?>
										</option>
										<option value="right" <?php selected( $options['default_text_align'] ?? '', 'right' ); ?>>
											<?php esc_html_e( 'Right', 'layoutberg' ); ?>
										</option>
									</select>
									<p class="layoutberg-help-text">
										<?php esc_html_e( 'Set the default text alignment for content in generated layouts.', 'layoutberg' ); ?>
									</p>
								</div>
							</div>
						</div>

						<div class="layoutberg-card layoutberg-mt-4">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'Color Defaults', 'layoutberg' ); ?></h3>
							</div>

							<div class="layoutberg-grid layoutberg-grid-2">
								<div class="layoutberg-form-group">
									<label for="layoutberg_default_text_color" class="layoutberg-label">
										<?php esc_html_e( 'Default Text Color', 'layoutberg' ); ?>
									</label>
									<input 
										type="text" 
										id="layoutberg_default_text_color" 
										name="layoutberg_options[default_text_color]" 
										value="<?php echo esc_attr( $options['default_text_color'] ?? '' ); ?>" 
										class="layoutberg-input layoutberg-color-input"
										placeholder="<?php esc_attr_e( 'e.g., #000000 or contrast', 'layoutberg' ); ?>"
									/>
									<p class="layoutberg-help-text">
										<?php esc_html_e( 'Set the default text color. Use hex values (#000000) or theme color slugs (contrast, primary, etc.).', 'layoutberg' ); ?>
									</p>
								</div>

								<div class="layoutberg-form-group">
									<label for="layoutberg_default_background_color" class="layoutberg-label">
										<?php esc_html_e( 'Default Background Color', 'layoutberg' ); ?>
									</label>
									<input 
										type="text" 
										id="layoutberg_default_background_color" 
										name="layoutberg_options[default_background_color]" 
										value="<?php echo esc_attr( $options['default_background_color'] ?? '' ); ?>" 
										class="layoutberg-input layoutberg-color-input"
										placeholder="<?php esc_attr_e( 'e.g., #FFFFFF or base', 'layoutberg' ); ?>"
									/>
									<p class="layoutberg-help-text">
										<?php esc_html_e( 'Set the default background color. Use hex values (#FFFFFF) or theme color slugs (base, secondary, etc.).', 'layoutberg' ); ?>
									</p>
								</div>

								<div class="layoutberg-form-group">
									<label for="layoutberg_default_button_color" class="layoutberg-label">
										<?php esc_html_e( 'Default Button Color', 'layoutberg' ); ?>
									</label>
									<input 
										type="text" 
										id="layoutberg_default_button_color" 
										name="layoutberg_options[default_button_color]" 
										value="<?php echo esc_attr( $options['default_button_color'] ?? '' ); ?>" 
										class="layoutberg-input layoutberg-color-input"
										placeholder="<?php esc_attr_e( 'e.g., #6366f1 or primary', 'layoutberg' ); ?>"
									/>
									<p class="layoutberg-help-text">
										<?php esc_html_e( 'Set the default button background color. Use hex values or theme color slugs.', 'layoutberg' ); ?>
									</p>
								</div>

								<div class="layoutberg-form-group">
									<label for="layoutberg_default_button_text_color" class="layoutberg-label">
										<?php esc_html_e( 'Default Button Text Color', 'layoutberg' ); ?>
									</label>
									<input 
										type="text" 
										id="layoutberg_default_button_text_color" 
										name="layoutberg_options[default_button_text_color]" 
										value="<?php echo esc_attr( $options['default_button_text_color'] ?? '' ); ?>" 
										class="layoutberg-input layoutberg-color-input"
										placeholder="<?php esc_attr_e( 'e.g., #FFFFFF or base', 'layoutberg' ); ?>"
									/>
									<p class="layoutberg-help-text">
										<?php esc_html_e( 'Set the default button text color. Use hex values or theme color slugs.', 'layoutberg' ); ?>
									</p>
								</div>
							</div>
						</div>

						<div class="layoutberg-card layoutberg-mt-4">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'Layout Defaults', 'layoutberg' ); ?></h3>
							</div>

							<div class="layoutberg-grid layoutberg-grid-2">
								<div class="layoutberg-form-group">
									<label for="layoutberg_default_content_width" class="layoutberg-label">
										<?php esc_html_e( 'Default Content Width', 'layoutberg' ); ?>
									</label>
									<select id="layoutberg_default_content_width" name="layoutberg_options[default_content_width]" class="layoutberg-select">
										<option value="default" <?php selected( $options['default_content_width'] ?? 'default', 'default' ); ?>>
											<?php esc_html_e( 'Theme Default', 'layoutberg' ); ?>
										</option>
										<option value="wide" <?php selected( $options['default_content_width'] ?? '', 'wide' ); ?>>
											<?php esc_html_e( 'Wide Width', 'layoutberg' ); ?>
										</option>
										<option value="full" <?php selected( $options['default_content_width'] ?? '', 'full' ); ?>>
											<?php esc_html_e( 'Full Width', 'layoutberg' ); ?>
										</option>
									</select>
									<p class="layoutberg-help-text">
										<?php esc_html_e( 'Set the default width for content blocks in generated layouts.', 'layoutberg' ); ?>
									</p>
								</div>

								<div class="layoutberg-form-group">
									<label for="layoutberg_default_spacing" class="layoutberg-label">
										<?php esc_html_e( 'Default Spacing', 'layoutberg' ); ?>
									</label>
									<select id="layoutberg_default_spacing" name="layoutberg_options[default_spacing]" class="layoutberg-select">
										<option value="default" <?php selected( $options['default_spacing'] ?? 'default', 'default' ); ?>>
											<?php esc_html_e( 'Theme Default', 'layoutberg' ); ?>
										</option>
										<option value="compact" <?php selected( $options['default_spacing'] ?? '', 'compact' ); ?>>
											<?php esc_html_e( 'Compact (20-40px)', 'layoutberg' ); ?>
										</option>
										<option value="comfortable" <?php selected( $options['default_spacing'] ?? '', 'comfortable' ); ?>>
											<?php esc_html_e( 'Comfortable (40-60px)', 'layoutberg' ); ?>
										</option>
										<option value="spacious" <?php selected( $options['default_spacing'] ?? '', 'spacious' ); ?>>
											<?php esc_html_e( 'Spacious (60-80px)', 'layoutberg' ); ?>
										</option>
									</select>
									<p class="layoutberg-help-text">
										<?php esc_html_e( 'Set the default spacing between blocks and sections.', 'layoutberg' ); ?>
									</p>
								</div>
							</div>
						</div>

						<div class="layoutberg-card layoutberg-mt-4">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'Style Presets', 'layoutberg' ); ?></h3>
							</div>

							<div class="layoutberg-form-group">
								<label class="layoutberg-flex layoutberg-items-center layoutberg-gap-2">
									<div class="layoutberg-toggle">
										<input 
											type="checkbox" 
											name="layoutberg_options[use_style_defaults]" 
											value="1"
											<?php checked( $options['use_style_defaults'] ?? false, true ); ?>
										/>
										<span class="layoutberg-toggle-slider"></span>
									</div>
									<div>
										<span class="layoutberg-label" style="margin-bottom: 0;"><?php esc_html_e( 'Apply Style Defaults', 'layoutberg' ); ?></span>
										<p class="layoutberg-help-text" style="margin-top: 0.25rem;">
											<?php esc_html_e( 'When enabled, the AI will use these style defaults when generating layouts. When disabled, it will use theme defaults or AI-determined styles.', 'layoutberg' ); ?>
										</p>
									</div>
								</label>
							</div>

							<div class="layoutberg-form-group layoutberg-mt-4">
								<label for="layoutberg_preferred_style" class="layoutberg-label">
									<?php esc_html_e( 'Preferred Design Style', 'layoutberg' ); ?>
								</label>
								<select id="layoutberg_preferred_style" name="layoutberg_options[preferred_style]" class="layoutberg-select">
									<option value="auto" <?php selected( $options['preferred_style'] ?? 'auto', 'auto' ); ?>>
										<?php esc_html_e( 'Auto (AI decides)', 'layoutberg' ); ?>
									</option>
									<option value="modern" <?php selected( $options['preferred_style'] ?? '', 'modern' ); ?>>
										<?php esc_html_e( 'Modern - Clean, gradient backgrounds', 'layoutberg' ); ?>
									</option>
									<option value="classic" <?php selected( $options['preferred_style'] ?? '', 'classic' ); ?>>
										<?php esc_html_e( 'Classic - Traditional, professional', 'layoutberg' ); ?>
									</option>
									<option value="bold" <?php selected( $options['preferred_style'] ?? '', 'bold' ); ?>>
										<?php esc_html_e( 'Bold - High impact, dramatic', 'layoutberg' ); ?>
									</option>
									<option value="minimal" <?php selected( $options['preferred_style'] ?? '', 'minimal' ); ?>>
										<?php esc_html_e( 'Minimal - Ultra-clean, lots of whitespace', 'layoutberg' ); ?>
									</option>
									<option value="creative" <?php selected( $options['preferred_style'] ?? '', 'creative' ); ?>>
										<?php esc_html_e( 'Creative - Artistic, colorful', 'layoutberg' ); ?>
									</option>
									<option value="playful" <?php selected( $options['preferred_style'] ?? '', 'playful' ); ?>>
										<?php esc_html_e( 'Playful - Friendly, fun', 'layoutberg' ); ?>
									</option>
								</select>
								<p class="layoutberg-help-text">
									<?php esc_html_e( 'Set a preferred design style that the AI will use as a baseline for generated layouts.', 'layoutberg' ); ?>
								</p>
							</div>
						</div>
					</div>
					<?php endif; ?>

					<?php if ( \DotCamp\LayoutBerg\LayoutBerg_Licensing::is_agency_plan() ) : ?>
					<!-- Agency Features Tab -->
					<div id="agency-features" class="layoutberg-settings-tab">
						<!-- Prompt Templates -->
						<div class="layoutberg-card">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'Prompt Engineering Templates', 'layoutberg' ); ?></h3>
							</div>
							
							<div class="layoutberg-card-body">
								<p class="layoutberg-text-muted layoutberg-mb-4">
									<?php esc_html_e( 'Create and manage reusable prompt templates for consistent layout generation across your team.', 'layoutberg' ); ?>
								</p>
								
								<div class="layoutberg-prompt-templates">
									<div class="layoutberg-flex layoutberg-justify-between layoutberg-items-center layoutberg-mb-3">
										<h4 class="layoutberg-font-semibold"><?php esc_html_e( 'Saved Templates', 'layoutberg' ); ?></h4>
										<button type="button" class="layoutberg-btn layoutberg-btn-secondary" id="add-prompt-template">
											<span class="dashicons dashicons-plus-alt2"></span>
											<?php esc_html_e( 'Add Template', 'layoutberg' ); ?>
										</button>
									</div>
									
									<div id="prompt-templates-list" class="layoutberg-space-y-2">
										<!-- Templates will be loaded here dynamically -->
										<p class="layoutberg-text-muted layoutberg-text-sm">
											<?php esc_html_e( 'No prompt templates saved yet. Click "Add Template" to create your first template.', 'layoutberg' ); ?>
										</p>
									</div>
								</div>
								
								<!-- Prompt Template Modal -->
								<div id="prompt-template-modal" class="layoutberg-modal">
									<div class="layoutberg-modal-content">
										<div class="layoutberg-modal-header">
											<h3 class="layoutberg-modal-title"><?php esc_html_e( 'Add Prompt Template', 'layoutberg' ); ?></h3>
											<button type="button" class="layoutberg-modal-close" data-close-modal>&times;</button>
										</div>
										<div class="layoutberg-modal-body">
											<div class="layoutberg-form-group">
												<label for="template-name" class="layoutberg-label"><?php esc_html_e( 'Template Name', 'layoutberg' ); ?></label>
												<input type="text" id="template-name" class="layoutberg-input" placeholder="<?php esc_attr_e( 'e.g., Hero Section with CTA', 'layoutberg' ); ?>" />
											</div>
											<div class="layoutberg-form-group">
												<label for="template-category" class="layoutberg-label"><?php esc_html_e( 'Category', 'layoutberg' ); ?></label>
												<select id="template-category" class="layoutberg-select">
													<option value="hero"><?php esc_html_e( 'Hero Sections', 'layoutberg' ); ?></option>
													<option value="features"><?php esc_html_e( 'Features', 'layoutberg' ); ?></option>
													<option value="testimonials"><?php esc_html_e( 'Testimonials', 'layoutberg' ); ?></option>
													<option value="cta"><?php esc_html_e( 'Call to Action', 'layoutberg' ); ?></option>
													<option value="pricing"><?php esc_html_e( 'Pricing', 'layoutberg' ); ?></option>
													<option value="about"><?php esc_html_e( 'About', 'layoutberg' ); ?></option>
													<option value="contact"><?php esc_html_e( 'Contact', 'layoutberg' ); ?></option>
													<option value="other"><?php esc_html_e( 'Other', 'layoutberg' ); ?></option>
												</select>
											</div>
											<div class="layoutberg-form-group">
												<label for="template-prompt" class="layoutberg-label"><?php esc_html_e( 'Prompt Template', 'layoutberg' ); ?></label>
												<textarea id="template-prompt" class="layoutberg-textarea" rows="6" placeholder="<?php esc_attr_e( 'Create a hero section with {heading}, {subheading}, and a {cta_text} button...', 'layoutberg' ); ?>"></textarea>
												<p class="layoutberg-help-text">
													<?php esc_html_e( 'Use {variable_name} for dynamic parts that can be filled in when using the template.', 'layoutberg' ); ?>
												</p>
											</div>
											<div class="layoutberg-form-group">
												<label for="template-variables" class="layoutberg-label"><?php esc_html_e( 'Variables (optional)', 'layoutberg' ); ?></label>
												<textarea id="template-variables" class="layoutberg-textarea" rows="3" placeholder="<?php esc_attr_e( 'heading: Main headline text&#10;subheading: Supporting text&#10;cta_text: Button text', 'layoutberg' ); ?>"></textarea>
												<p class="layoutberg-help-text">
													<?php esc_html_e( 'Define default values for variables. Format: variable_name: description or default value', 'layoutberg' ); ?>
												</p>
											</div>
										</div>
										<div class="layoutberg-modal-footer">
											<button type="button" class="layoutberg-btn layoutberg-btn-secondary" data-close-modal><?php esc_html_e( 'Cancel', 'layoutberg' ); ?></button>
											<button type="button" class="layoutberg-btn layoutberg-btn-primary" id="save-prompt-template">
												<span class="dashicons dashicons-saved"></span>
												<?php esc_html_e( 'Save Template', 'layoutberg' ); ?>
											</button>
										</div>
									</div>
								</div>
							</div>
						</div>
						
						<!-- Debug Mode -->
						<div class="layoutberg-card">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'Debug Mode', 'layoutberg' ); ?></h3>
							</div>
							
							<div class="layoutberg-card-body">
								<div class="layoutberg-form-group">
									<label class="layoutberg-flex layoutberg-items-start layoutberg-gap-3">
										<div class="layoutberg-toggle">
											<input 
												type="checkbox" 
												name="layoutberg_options[debug_mode]" 
												value="1" 
												<?php checked( ! empty( $options['debug_mode'] ) ); ?>
											/>
											<span class="layoutberg-toggle-slider"></span>
										</div>
										<div>
											<span class="layoutberg-label" style="margin-bottom: 0;"><?php esc_html_e( 'Enable Debug Mode', 'layoutberg' ); ?></span>
											<p class="layoutberg-help-text" style="margin-top: 0.25rem;">
												<?php esc_html_e( 'When enabled, API requests and responses will be logged for debugging purposes. Logs can be viewed in the Debug page.', 'layoutberg' ); ?>
											</p>
										</div>
									</label>
								</div>
								
								<div class="layoutberg-form-group">
									<label class="layoutberg-flex layoutberg-items-start layoutberg-gap-3">
										<div class="layoutberg-toggle">
											<input 
												type="checkbox" 
												name="layoutberg_options[verbose_logging]" 
												value="1" 
												<?php checked( ! empty( $options['verbose_logging'] ) ); ?>
											/>
											<span class="layoutberg-toggle-slider"></span>
										</div>
										<div>
											<span class="layoutberg-label" style="margin-bottom: 0;"><?php esc_html_e( 'Verbose Logging', 'layoutberg' ); ?></span>
											<p class="layoutberg-help-text" style="margin-top: 0.25rem;">
												<?php esc_html_e( 'Include additional details in debug logs such as token usage, processing time, and intermediate steps.', 'layoutberg' ); ?>
											</p>
										</div>
									</label>
								</div>
								
								<?php if ( ! empty( $options['debug_mode'] ) ) : ?>
									<div class="layoutberg-mt-3">
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=layoutberg-debug' ) ); ?>" class="layoutberg-btn layoutberg-btn-secondary">
											<span class="dashicons dashicons-visibility"></span>
											<?php esc_html_e( 'View Debug Logs', 'layoutberg' ); ?>
										</a>
									</div>
								<?php endif; ?>
							</div>
						</div>
						
						<!-- Advanced Multisite Settings -->
						<?php if ( is_multisite() ) : ?>
						<div class="layoutberg-card">
							<div class="layoutberg-card-header">
								<h3 class="layoutberg-card-title"><?php esc_html_e( 'Multisite Network Settings', 'layoutberg' ); ?></h3>
							</div>
							
							<div class="layoutberg-card-body">
								<div class="layoutberg-form-group">
									<label class="layoutberg-flex layoutberg-items-start layoutberg-gap-3">
										<div class="layoutberg-toggle">
											<input 
												type="checkbox" 
												name="layoutberg_options[network_template_sharing]" 
												value="1" 
												<?php checked( ! empty( $options['network_template_sharing'] ) ); ?>
											/>
											<span class="layoutberg-toggle-slider"></span>
										</div>
										<div>
											<span class="layoutberg-label" style="margin-bottom: 0;"><?php esc_html_e( 'Enable Network-wide Template Sharing', 'layoutberg' ); ?></span>
											<p class="layoutberg-help-text" style="margin-top: 0.25rem;">
												<?php esc_html_e( 'Allow templates to be shared across all sites in the network. Network admins can manage shared templates.', 'layoutberg' ); ?>
											</p>
										</div>
									</label>
								</div>
								
								<div class="layoutberg-form-group">
									<label class="layoutberg-flex layoutberg-items-start layoutberg-gap-3">
										<div class="layoutberg-toggle">
											<input 
												type="checkbox" 
												name="layoutberg_options[network_settings_sync]" 
												value="1" 
												<?php checked( ! empty( $options['network_settings_sync'] ) ); ?>
											/>
											<span class="layoutberg-toggle-slider"></span>
										</div>
										<div>
											<span class="layoutberg-label" style="margin-bottom: 0;"><?php esc_html_e( 'Sync Settings Across Network', 'layoutberg' ); ?></span>
											<p class="layoutberg-help-text" style="margin-top: 0.25rem;">
												<?php esc_html_e( 'Synchronize plugin settings across all sites in the network. Individual sites can still override specific settings.', 'layoutberg' ); ?>
											</p>
										</div>
									</label>
								</div>
								
								<div class="layoutberg-form-group">
									<label for="network_api_limit" class="layoutberg-label">
										<?php esc_html_e( 'Network API Request Limit', 'layoutberg' ); ?>
									</label>
									<input 
										type="number" 
										id="network_api_limit" 
										name="layoutberg_options[network_api_limit]" 
										value="<?php echo esc_attr( $options['network_api_limit'] ?? '1000' ); ?>" 
										class="layoutberg-input layoutberg-w-32"
										min="100"
										step="100"
									/>
									<p class="layoutberg-help-text">
										<?php esc_html_e( 'Maximum API requests per month across all network sites. Set to 0 for unlimited.', 'layoutberg' ); ?>
									</p>
								</div>
							</div>
						</div>
						<?php endif; ?>
					</div>
					<?php endif; ?>

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

/* Remove any browser default styling */
.layoutberg-toggle input[type="checkbox"]:focus + .layoutberg-toggle-slider {
	box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Color input styling */
.layoutberg-color-input {
	font-family: monospace;
}

.layoutberg-color-input:placeholder-shown {
	font-family: inherit;
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
	
	// Prompt Templates functionality
	var promptTemplates = [];
	var editingTemplateId = null;
	
	// Load saved prompt templates
	function loadPromptTemplates() {
		wp.apiRequest({
			path: '/layoutberg/v1/prompt-templates',
			method: 'GET'
		}).done(function(response) {
			promptTemplates = response.templates || [];
			renderPromptTemplates();
		}).fail(function() {
			console.error('Failed to load prompt templates');
		});
	}
	
	// Render prompt templates list
	function renderPromptTemplates() {
		var $list = $('#prompt-templates-list');
		$list.empty();
		
		if (promptTemplates.length === 0) {
			$list.html('<p class="layoutberg-text-muted layoutberg-text-sm"><?php esc_html_e( 'No prompt templates saved yet. Click "Add Template" to create your first template.', 'layoutberg' ); ?></p>');
			return;
		}
		
		promptTemplates.forEach(function(template) {
			var $item = $('<div class="layoutberg-prompt-template-item layoutberg-card layoutberg-mb-2" />');
			$item.html(`
				<div class="layoutberg-flex layoutberg-justify-between layoutberg-items-start">
					<div class="layoutberg-flex-1">
						<h5 class="layoutberg-font-semibold layoutberg-mb-1">${template.name}</h5>
						<p class="layoutberg-text-sm layoutberg-text-muted layoutberg-mb-1">
							<span class="layoutberg-badge layoutberg-badge-secondary">${template.category}</span>
							${template.variables ? '<span class="layoutberg-ml-2">' + Object.keys(template.variables).length + ' variables</span>' : ''}
						</p>
						<p class="layoutberg-text-sm layoutberg-text-muted">${template.prompt.substring(0, 100)}${template.prompt.length > 100 ? '...' : ''}</p>
					</div>
					<div class="layoutberg-flex layoutberg-gap-2">
						<button type="button" class="layoutberg-btn layoutberg-btn-sm layoutberg-btn-secondary edit-template" data-id="${template.id}">
							<span class="dashicons dashicons-edit"></span>
						</button>
						<button type="button" class="layoutberg-btn layoutberg-btn-sm layoutberg-btn-danger delete-template" data-id="${template.id}">
							<span class="dashicons dashicons-trash"></span>
						</button>
					</div>
				</div>
			`);
			$list.append($item);
		});
	}
	
	// Show prompt template modal
	$('#add-prompt-template').on('click', function() {
		console.log('Add Template button clicked');
		editingTemplateId = null;
		$('#prompt-template-modal').find('.layoutberg-modal-title').text('<?php esc_html_e( 'Add Prompt Template', 'layoutberg' ); ?>');
		$('#template-name').val('');
		$('#template-category').val('hero');
		$('#template-prompt').val('');
		$('#template-variables').val('');
		console.log('Modal element:', $('#prompt-template-modal'));
		// Use addClass('active') instead of show() for the layoutberg modal
		$('#prompt-template-modal').addClass('active');
		console.log('Modal active class added');
	});
	
	// Edit template
	$(document).on('click', '.edit-template', function() {
		var templateId = $(this).data('id');
		var template = promptTemplates.find(t => t.id === templateId);
		if (!template) return;
		
		editingTemplateId = templateId;
		$('#prompt-template-modal').find('.layoutberg-modal-title').text('<?php esc_html_e( 'Edit Prompt Template', 'layoutberg' ); ?>');
		$('#template-name').val(template.name);
		$('#template-category').val(template.category);
		$('#template-prompt').val(template.prompt);
		
		// Format variables for display
		if (template.variables) {
			var variablesText = Object.entries(template.variables)
				.map(([key, value]) => `${key}: ${value}`)
				.join('\n');
			$('#template-variables').val(variablesText);
		} else {
			$('#template-variables').val('');
		}
		
		$('#prompt-template-modal').addClass('active');
	});
	
	// Delete template
	$(document).on('click', '.delete-template', function() {
		if (!confirm('<?php esc_html_e( 'Are you sure you want to delete this template?', 'layoutberg' ); ?>')) {
			return;
		}
		
		var templateId = $(this).data('id');
		
		wp.apiRequest({
			path: '/layoutberg/v1/prompt-templates/' + templateId,
			method: 'DELETE'
		}).done(function() {
			loadPromptTemplates();
		}).fail(function() {
			alert('<?php esc_html_e( 'Failed to delete template', 'layoutberg' ); ?>');
		});
	});
	
	// Save prompt template
	$('#save-prompt-template').on('click', function() {
		var name = $('#template-name').val().trim();
		var category = $('#template-category').val();
		var prompt = $('#template-prompt').val().trim();
		var variablesText = $('#template-variables').val().trim();
		
		if (!name || !prompt) {
			alert('<?php esc_html_e( 'Please fill in all required fields', 'layoutberg' ); ?>');
			return;
		}
		
		// Parse variables
		var variables = {};
		if (variablesText) {
			variablesText.split('\n').forEach(function(line) {
				var parts = line.split(':');
				if (parts.length >= 2) {
					var key = parts[0].trim();
					var value = parts.slice(1).join(':').trim();
					if (key) {
						variables[key] = value;
					}
				}
			});
		}
		
		var data = {
			name: name,
			category: category,
			prompt: prompt,
			variables: Object.keys(variables).length > 0 ? variables : null
		};
		
		var method = editingTemplateId ? 'PUT' : 'POST';
		var path = editingTemplateId 
			? '/layoutberg/v1/prompt-templates/' + editingTemplateId 
			: '/layoutberg/v1/prompt-templates';
		
		wp.apiRequest({
			path: path,
			method: method,
			data: data
		}).done(function() {
			$('#prompt-template-modal').removeClass('active');
			loadPromptTemplates();
		}).fail(function() {
			alert('<?php esc_html_e( 'Failed to save template', 'layoutberg' ); ?>');
		});
	});
	
	// Close modal
	$('[data-close-modal]').on('click', function() {
		$(this).closest('.layoutberg-modal').hide();
	});
	
	// Close modal on background click
	$('.layoutberg-modal').on('click', function(e) {
		if ($(e.target).hasClass('layoutberg-modal')) {
			$(this).removeClass('active');
		}
	});
	
	// Load templates if on agency features tab
	if (window.location.hash === '#agency-features') {
		loadPromptTemplates();
	}
	
	// Load templates when switching to agency features tab
	$('.layoutberg-settings-nav-item[data-tab="agency-features"]').on('click', function() {
		loadPromptTemplates();
	});
});
</script>