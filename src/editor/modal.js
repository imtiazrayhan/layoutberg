/**
 * LayoutBerg Generation Modal Component
 *
 * Provides the main interface for AI layout generation
 *
 * @package LayoutBerg
 * @since 1.0.0
 */

import { __, sprintf } from '@wordpress/i18n';
import { Fragment, useState, useEffect } from '@wordpress/element';
import {
	Modal,
	Button,
	TextareaControl,
	SelectControl,
	RangeControl,
	ToggleControl,
	Notice,
	Spinner,
	Flex,
	FlexItem,
	FlexBlock,
	Card,
	CardBody,
	CardHeader,
	CardDivider,
	__experimentalGrid as Grid,
	__experimentalVStack as VStack,
	__experimentalHStack as HStack,
	TabPanel,
} from '@wordpress/components';
import React from 'react';
import { layout, starFilled, cog } from '@wordpress/icons';

/**
 * LayoutBerg Generation Modal
 */
const LayoutBergModal = ( {
	isOpen,
	onClose,
	onGenerate,
	isGenerating,
	error,
	prompt,
	onPromptChange,
	settings,
	onSettingsChange,
	hasSelectedBlocks,
	lastResponse,
	generationState = 'idle',
	onCancel,
} ) => {
	const [ showAdvanced, setShowAdvanced ] = useState( false );
	const [ showPrompts, setShowPrompts ] = useState( false );
	const [ selectedVariation, setSelectedVariation ] = useState( 'default' );
	const [ matchedTemplate, setMatchedTemplate ] = useState( null );

	// Get max tokens limit based on selected model
	const getMaxTokensLimit = () => {
		const models = window.layoutbergEditor?.models || {};
		let maxLimit = 4096; // Default fallback

		// Search through all providers for the selected model
		Object.keys( models ).forEach( ( provider ) => {
			if (
				models[ provider ].models &&
				models[ provider ].models[ settings.model ]
			) {
				const modelConfig = models[ provider ].models[ settings.model ];
				if ( modelConfig.max_output ) {
					maxLimit = modelConfig.max_output;
				}
			}
		} );

		return maxLimit;
	};

	const updateSetting = ( key, value ) => {
		onSettingsChange( {
			...settings,
			[ key ]: value,
		} );
	};

	// Update max tokens when model changes to ensure it doesn't exceed the new limit
	useEffect( () => {
		const currentMaxLimit = getMaxTokensLimit();
		if ( settings.maxTokens > currentMaxLimit ) {
			updateSetting( 'maxTokens', currentMaxLimit );
		}
	}, [ settings.model ] );

	// Check if prompt matches a predefined template
	useEffect( () => {
		if ( ! window.layoutbergEditor?.predefinedTemplates || ! window.layoutbergEditor?.licensing?.canUseVariations ) {
			setMatchedTemplate( null );
			return;
		}

		const normalizedPrompt = prompt.toLowerCase().trim();
		const templates = window.layoutbergEditor.predefinedTemplates;
		
		// Check each template for a match
		for ( const [ key, template ] of Object.entries( templates ) ) {
			const templatePrompt = template.prompt.toLowerCase();
			const templateName = template.name.toLowerCase();
			
			if ( normalizedPrompt === templatePrompt ||
				 normalizedPrompt.includes( templatePrompt ) ||
				 normalizedPrompt.includes( key ) ||
				 normalizedPrompt.includes( templateName ) ) {
				setMatchedTemplate( { key, ...template } );
				return;
			}
		}
		
		setMatchedTemplate( null );
	}, [ prompt ] );

	const quickPrompts = [
		{
			label: __( 'Hero Section', 'layoutberg' ),
			prompt: __(
				'Create a modern hero section with headline, description, and call-to-action button',
				'layoutberg'
			),
		},
		{
			label: __( 'Feature Grid', 'layoutberg' ),
			prompt: __(
				'Create a 3-column grid showcasing product features with icons and descriptions',
				'layoutberg'
			),
		},
		{
			label: __( 'About Section', 'layoutberg' ),
			prompt: __(
				'Create an about us section with team member profiles and company description',
				'layoutberg'
			),
		},
		{
			label: __( 'Contact Form', 'layoutberg' ),
			prompt: __(
				'Create a contact section with form fields and contact information',
				'layoutberg'
			),
		},
		{
			label: __( 'Blog Layout', 'layoutberg' ),
			prompt: __(
				'Create a blog post layout with featured image, title, content, and sidebar',
				'layoutberg'
			),
		},
		{
			label: __( 'Portfolio Grid', 'layoutberg' ),
			prompt: __(
				'Create a portfolio grid showcasing projects with images and descriptions',
				'layoutberg'
			),
		},
	];

	// Progress steps configuration with enhanced details
	const progressSteps = [
		{
			id: 'preparing',
			label: __( 'Preparing your request', 'layoutberg' ),
			icon: '📝',
			description: __(
				'Analyzing prompt and optimizing parameters',
				'layoutberg'
			),
			estimatedDuration: 1,
		},
		{
			id: 'sending',
			label: __( 'Connecting to AI model', 'layoutberg' ),
			icon: '🚀',
			description: __(
				'Establishing secure connection to AI service',
				'layoutberg'
			),
			estimatedDuration: 1,
		},
		{
			id: 'generating',
			label: __( 'Generating layout blocks', 'layoutberg' ),
			icon: '✨',
			description: __(
				'AI is creating your custom layout design',
				'layoutberg'
			),
			estimatedDuration: 20,
		},
		{
			id: 'processing',
			label: __( 'Processing and validating', 'layoutberg' ),
			icon: '🔍',
			description: __(
				'Validating blocks and optimizing structure',
				'layoutberg'
			),
			estimatedDuration: 3,
		},
		{
			id: 'complete',
			label: __( 'Finalizing your layout', 'layoutberg' ),
			icon: '✅',
			description: __(
				'Inserting blocks into your editor',
				'layoutberg'
			),
			estimatedDuration: 1,
		},
	];

	// Get current step based on generation state
	const getCurrentStep = () => {
		switch ( generationState ) {
			case 'preparing':
				return 0;
			case 'sending':
				return 1;
			case 'generating':
				return 2;
			case 'processing':
				return 3;
			case 'complete':
				return 4;
			default:
				return -1;
		}
	};

	// Calculate progress percentage
	const getProgressPercentage = () => {
		const currentStep = getCurrentStep();
		if ( currentStep === -1 ) return 0;
		return Math.min(
			( ( currentStep + 1 ) / progressSteps.length ) * 100,
			100
		);
	};

	// Get estimated time remaining
	const getEstimatedTimeRemaining = () => {
		const currentStep = getCurrentStep();
		if ( currentStep === -1 ) return getEstimatedTime();

		let remainingSeconds = 0;
		for ( let i = currentStep; i < progressSteps.length; i++ ) {
			remainingSeconds += progressSteps[ i ].estimatedDuration;
		}

		if ( remainingSeconds < 60 ) {
			return sprintf(
				__( '%d seconds', 'layoutberg' ),
				remainingSeconds
			);
		} else {
			const minutes = Math.ceil( remainingSeconds / 60 );
			return sprintf( __( '%d minutes', 'layoutberg' ), minutes );
		}
	};

	// Estimated time based on model
	const getEstimatedTime = () => {
		if ( settings.model?.includes( 'gpt-4' ) ) {
			return __( '25-45 seconds', 'layoutberg' );
		} else if ( settings.model?.includes( 'claude' ) ) {
			return __( '20-35 seconds', 'layoutberg' );
		}
		return __( '15-30 seconds', 'layoutberg' );
	};

	// Get model-specific tips
	const getModelTips = () => {
		if ( settings.model?.includes( 'gpt-4' ) ) {
			return __(
				'GPT-4 provides the highest quality results but may take longer to generate.',
				'layoutberg'
			);
		} else if ( settings.model?.includes( 'claude' ) ) {
			return __(
				'Claude excels at creative layouts and detailed design descriptions.',
				'layoutberg'
			);
		} else if ( settings.model?.includes( 'gpt-3.5' ) ) {
			return __(
				'GPT-3.5 offers fast generation with good quality for most layouts.',
				'layoutberg'
			);
		}
		return __(
			'Your AI model is working to create the perfect layout for you.',
			'layoutberg'
		);
	};

	// Get real-time progress updates
	const getProgressUpdates = () => {
		const updates = {
			preparing: __(
				'Optimizing your prompt for best results...',
				'layoutberg'
			),
			sending: __( 'Connecting securely to AI service...', 'layoutberg' ),
			generating: __(
				'AI is analyzing your requirements and creating blocks...',
				'layoutberg'
			),
			processing: __(
				'Validating generated content and optimizing structure...',
				'layoutberg'
			),
			complete: __(
				'Finalizing and inserting blocks into your editor...',
				'layoutberg'
			),
		};

		return (
			updates[ generationState ] ||
			__( 'Processing your request...', 'layoutberg' )
		);
	};

	// Get step-specific tips
	const getStepTips = () => {
		const currentStep = getCurrentStep();
		const tips = {
			0: __(
				'This step analyzes your prompt to ensure optimal results.',
				'layoutberg'
			),
			1: __(
				'Establishing a secure connection to the AI service.',
				'layoutberg'
			),
			2: __(
				'This is where the AI does the heavy lifting - creating your layout based on your description. This step typically takes the longest.',
				'layoutberg'
			),
			3: __(
				'Validating the generated content for compatibility.',
				'layoutberg'
			),
			4: __(
				'Inserting the blocks into your WordPress editor.',
				'layoutberg'
			),
		};

		return (
			tips[ currentStep ] ||
			__( 'Processing your request...', 'layoutberg' )
		);
	};

	// Progress View Component - Simplified for Performance
	const ProgressView = () => {
		const currentStep = getCurrentStep();
		const progressPercentage = getProgressPercentage();

		// Scroll to top when progress view is shown
		React.useEffect( () => {
			// Try multiple selectors to find the scrollable element
			const selectors = [
				'.layoutberg-generation-modal .components-modal__content',
				'.layoutberg-generation-modal',
				'.components-modal__content',
				'.components-modal__frame',
			];

			for ( const selector of selectors ) {
				const element = document.querySelector( selector );
				if ( element ) {
					console.log(
						'Found scrollable element:',
						selector,
						element
					);
					element.scrollTop = 0;
					// Also try scrolling into view
					element.scrollIntoView( {
						behavior: 'smooth',
						block: 'start',
					} );
					break;
				}
			}
		}, [] );

		return (
			<div className="layoutberg-progress-view">
				<div className="layoutberg-progress-header">
					<h3>{ __( 'Creating your layout...', 'layoutberg' ) }</h3>
					<p className="layoutberg-prompt-preview">{ prompt }</p>
				</div>

				{ /* Simple Progress Bar */ }
				<div className="layoutberg-progress-bar-container">
					<div className="layoutberg-progress-bar">
						<div
							className="layoutberg-progress-bar-fill"
							style={ { width: `${ progressPercentage }%` } }
						/>
					</div>
					<div className="layoutberg-progress-percentage">
						{ Math.round( progressPercentage ) }%
					</div>
				</div>

				{ /* Simple Animation */ }
				<div className="layoutberg-progress-animation">
					<div className="layoutberg-progress-circle">
						<Spinner />
					</div>
					<div className="layoutberg-progress-info">
						<div className="layoutberg-progress-model">
							{ sprintf(
								__( 'Using %s', 'layoutberg' ),
								settings.model || 'AI Model'
							) }
						</div>
						<div className="layoutberg-progress-update">
							{ getProgressUpdates() }
						</div>
					</div>
				</div>

				{ /* Simple Progress Steps */ }
				<div className="layoutberg-progress-steps">
					{ progressSteps.map( ( step, index ) => (
						<div
							key={ step.id }
							className={ `layoutberg-progress-step ${
								index < currentStep
									? 'completed'
									: index === currentStep
									? 'active'
									: 'pending'
							}` }
						>
							<span className="step-icon">
								{ index < currentStep
									? '✓'
									: index === currentStep
									? '⟳'
									: '○' }
							</span>
							<div className="step-content">
								<span className="step-label">
									{ step.label }
								</span>
								<span className="step-description">
									{ step.description }
								</span>
							</div>
						</div>
					) ) }
				</div>

				<div className="layoutberg-progress-footer">
					{ onCancel && generationState !== 'complete' && (
						<Button
							variant="secondary"
							onClick={ onCancel }
							className="layoutberg-cancel-button"
						>
							{ __( 'Cancel Generation', 'layoutberg' ) }
						</Button>
					) }
				</div>
			</div>
		);
	};

	if ( ! isOpen ) return null;

	return (
		<Modal
			title={
				<HStack>
					<FlexItem>
						<img
							src={
								window.layoutbergEditor &&
								window.layoutbergEditor.pluginUrl
									? window.layoutbergEditor.pluginUrl +
									  'assets/images/layoutberg-logo.png'
									: '/wp-content/plugins/layoutberg/assets/images/layoutberg-logo.png'
							}
							alt="LayoutBerg"
							style={ {
								width: '24px',
								height: '24px',
								objectFit: 'contain',
							} }
						/>
					</FlexItem>
					<FlexBlock>
						{ __( 'Generate AI Layout', 'layoutberg' ) }
					</FlexBlock>
					{ hasSelectedBlocks && (
						<FlexItem>
							<span className="layoutberg-modal-badge">
								{ __( 'Replace Mode', 'layoutberg' ) }
							</span>
						</FlexItem>
					) }
				</HStack>
			}
			onRequestClose={ onClose }
			className="layoutberg-generation-modal"
			size="large"
		>
			{ isGenerating && generationState !== 'idle' ? (
				<ProgressView />
			) : (
				<VStack spacing={ 4 }>
					{ /* Error Notice */ }
					{ error && (
						<Notice
							status="error"
							isDismissible={ false }
							className="layoutberg-modal-error"
						>
							{ error }
						</Notice>
					) }

					{ /* Selected Blocks Info */ }
					{ hasSelectedBlocks && (
						<Notice
							status="info"
							isDismissible={ false }
							className="layoutberg-modal-info"
						>
							{ __(
								'The generated layout will replace your selected blocks.',
								'layoutberg'
							) }
						</Notice>
					) }

					{ /* Quick Prompts */ }
					<Card>
						<CardHeader>
							<strong>
								{ __( 'Quick Start Templates', 'layoutberg' ) }
							</strong>
						</CardHeader>
						<CardBody>
							<Grid
								columns={ 3 }
								gap={ 2 }
								className="layoutberg-quick-prompts"
							>
								{ quickPrompts.map( ( quickPrompt, index ) => (
									<Button
										key={ index }
										variant="secondary"
										size="small"
										onClick={ () =>
											onPromptChange( quickPrompt.prompt )
										}
										className="layoutberg-quick-prompt-button"
									>
										{ quickPrompt.label }
									</Button>
								) ) }
							</Grid>
						</CardBody>
					</Card>

					{ /* Main Prompt Input */ }
					<Card>
						<CardHeader>
							<strong>
								{ __( 'Describe Your Layout', 'layoutberg' ) }
							</strong>
						</CardHeader>
						<CardBody>
							<TextareaControl
								placeholder={ __(
									'Describe the layout you want to create in detail. Include colors, style, sections, spacing, and any specific design preferences...',
									'layoutberg'
								) }
								value={ prompt }
								onChange={ onPromptChange }
								rows={ 5 }
								className="layoutberg-prompt-input"
								help={ __(
									'The more detailed your description, the better the results. Include style preferences (modern, minimal, bold), colors (gradient backgrounds, specific hex codes), layout structure (grid, sidebar, asymmetric), spacing preferences, and any other design elements you want.',
									'layoutberg'
								) }
							/>
						</CardBody>
					</Card>

					{ /* Variations Section - Only show for Professional/Agency users when template is matched */ }
					{ matchedTemplate && window.layoutbergEditor?.licensing?.canUseVariations && (
						<Card className="layoutberg-variations-card">
							<CardHeader>
								<HStack>
									<FlexBlock>
										<strong>
											{ __( 'Layout Variations', 'layoutberg' ) }
										</strong>
										<span className="layoutberg-pro-badge">
											{ __( 'Pro Feature', 'layoutberg' ) }
										</span>
									</FlexBlock>
								</HStack>
							</CardHeader>
							<CardBody>
								<Notice
									status="info"
									isDismissible={ false }
									className="layoutberg-variations-notice"
								>
									{ sprintf(
										__( 'We detected you want to create a "%s". Select a variation style below for different layouts.', 'layoutberg' ),
										matchedTemplate.name
									) }
								</Notice>
								
								<div className="layoutberg-variations-grid">
									<label className="layoutberg-variation-option">
										<input
											type="radio"
											name="variation"
											value="default"
											checked={ selectedVariation === 'default' || ! selectedVariation }
											onChange={ () => setSelectedVariation( 'default' ) }
										/>
										<div className="layoutberg-variation-preview">
											<div className="variation-icon">🎨</div>
											<div className="variation-name">
												{ __( 'Random Style', 'layoutberg' ) }
											</div>
											<div className="variation-description">
												{ __( 'Let AI choose the best variation', 'layoutberg' ) }
											</div>
										</div>
									</label>
									
									<label className="layoutberg-variation-option">
										<input
											type="radio"
											name="variation"
											value="modern"
											checked={ selectedVariation === 'modern' }
											onChange={ () => setSelectedVariation( 'modern' ) }
										/>
										<div className="layoutberg-variation-preview">
											<div className="variation-icon">✨</div>
											<div className="variation-name">
												{ __( 'Modern', 'layoutberg' ) }
											</div>
											<div className="variation-description">
												{ __( 'Clean lines, bold typography', 'layoutberg' ) }
											</div>
										</div>
									</label>
									
									<label className="layoutberg-variation-option">
										<input
											type="radio"
											name="variation"
											value="classic"
											checked={ selectedVariation === 'classic' }
											onChange={ () => setSelectedVariation( 'classic' ) }
										/>
										<div className="layoutberg-variation-preview">
											<div className="variation-icon">📜</div>
											<div className="variation-name">
												{ __( 'Classic', 'layoutberg' ) }
											</div>
											<div className="variation-description">
												{ __( 'Traditional, timeless design', 'layoutberg' ) }
											</div>
										</div>
									</label>
									
									<label className="layoutberg-variation-option">
										<input
											type="radio"
											name="variation"
											value="minimal"
											checked={ selectedVariation === 'minimal' }
											onChange={ () => setSelectedVariation( 'minimal' ) }
										/>
										<div className="layoutberg-variation-preview">
											<div className="variation-icon">⚡</div>
											<div className="variation-name">
												{ __( 'Minimal', 'layoutberg' ) }
											</div>
											<div className="variation-description">
												{ __( 'Simple, focused on content', 'layoutberg' ) }
											</div>
										</div>
									</label>
								</div>
								
								<ToggleControl
									label={ __( 'Use variations for faster generation', 'layoutberg' ) }
									help={ __( 'Generate layouts instantly without API calls using our pre-built variations', 'layoutberg' ) }
									checked={ settings.useVariations !== false }
									onChange={ ( value ) => updateSetting( 'useVariations', value ) }
								/>
							</CardBody>
						</Card>
					) }

					{ /* Advanced Settings */ }
					<Card>
						<CardHeader>
							<HStack>
								<FlexBlock>
									<strong>
										{ __(
											'Advanced Settings',
											'layoutberg'
										) }
									</strong>
								</FlexBlock>
								<FlexItem>
									<Button
										variant="tertiary"
										size="small"
										onClick={ () =>
											setShowAdvanced( ! showAdvanced )
										}
										icon={ cog }
									>
										{ showAdvanced
											? __( 'Hide', 'layoutberg' )
											: __( 'Show', 'layoutberg' ) }
									</Button>
								</FlexItem>
							</HStack>
						</CardHeader>
						{ showAdvanced && (
							<CardBody>
								<VStack spacing={ 3 }>
									<SelectControl
										label={ __( 'AI Model', 'layoutberg' ) }
										value={ settings.model }
										options={ ( () => {
											const options = [];
											const models =
												window.layoutbergEditor
													?.models || {};

											// Debug log
											console.log(
												'window.layoutbergEditor:',
												window.layoutbergEditor
											);
											console.log(
												'LayoutBerg Models:',
												models
											);
											console.log(
												'Available model keys:',
												Object.keys( models )
											);
											console.log(
												'Has OpenAI models:',
												models.openai ? 'yes' : 'no'
											);
											console.log(
												'Has Claude models:',
												models.claude ? 'yes' : 'no'
											);

											// Build options from available models
											Object.keys( models ).forEach(
												( provider ) => {
													if (
														models[ provider ]
															.models
													) {
														// Add optgroup label
														options.push( {
															label: models[
																provider
															].label,
															value: '',
															disabled: true,
														} );

														// Add models for this provider
														Object.entries(
															models[ provider ]
																.models
														).forEach(
															( [
																value,
																modelConfig,
															] ) => {
																options.push( {
																	label: `  ${
																		modelConfig.label ||
																		modelConfig.name
																	}`,
																	value,
																} );
															}
														);
													}
												}
											);

											// Fallback if no models are available
											if ( options.length === 0 ) {
												console.log(
													'No models found, using fallback'
												);
												options.push(
													{
														label: __(
															'GPT-3.5 Turbo (Fast & Affordable)',
															'layoutberg'
														),
														value: 'gpt-3.5-turbo',
													},
													{
														label: __(
															'GPT-4 (Most Capable)',
															'layoutberg'
														),
														value: 'gpt-4',
													},
													{
														label: __(
															'GPT-4 Turbo (Fast & Capable)',
															'layoutberg'
														),
														value: 'gpt-4-turbo',
													}
												);
											}

											return options;
										} )() }
										onChange={ ( value ) =>
											updateSetting( 'model', value )
										}
										help={ __(
											'Choose the AI model for generation',
											'layoutberg'
										) }
									/>

									<div className="layoutberg-creativity-slider">
										<RangeControl
											label={ __(
												'Creativity Level',
												'layoutberg'
											) }
											value={ settings.temperature }
											onChange={ ( value ) =>
												updateSetting(
													'temperature',
													value
												)
											}
											min={ 0 }
											max={ 2 }
											step={ 0.1 }
											help={ __(
												'Lower values produce more focused results, higher values are more creative',
												'layoutberg'
											) }
										/>
									</div>

									<RangeControl
										label={ __(
											'Max Tokens',
											'layoutberg'
										) }
										value={ settings.maxTokens }
										onChange={ ( value ) =>
											updateSetting( 'maxTokens', value )
										}
										min={ 500 }
										max={ getMaxTokensLimit() }
										step={ 100 }
										help={ sprintf(
											__(
												'Higher values allow more complex layouts but cost more. This model supports up to %d completion tokens.',
												'layoutberg'
											),
											getMaxTokensLimit()
										) }
									/>
								</VStack>
							</CardBody>
						) }
					</Card>

					{ /* Show Prompts if Available */ }
					{ lastResponse && lastResponse.prompts && (
						<Card>
							<CardHeader>
								<HStack>
									<FlexBlock>
										<strong>
											{ __(
												'AI Prompts Used',
												'layoutberg'
											) }
										</strong>
									</FlexBlock>
									<FlexItem>
										<Button
											variant="tertiary"
											size="small"
											onClick={ () =>
												setShowPrompts( ! showPrompts )
											}
										>
											{ showPrompts
												? __( 'Hide', 'layoutberg' )
												: __( 'Show', 'layoutberg' ) }
										</Button>
									</FlexItem>
								</HStack>
							</CardHeader>
							{ showPrompts && (
								<CardBody>
									<TabPanel
										className="layoutberg-prompts-tabs"
										tabs={ [
											{
												name: 'system',
												title: __(
													'System Prompt',
													'layoutberg'
												),
											},
											{
												name: 'user',
												title: __(
													'Enhanced User Prompt',
													'layoutberg'
												),
											},
										] }
									>
										{ ( tab ) => (
											<div className="layoutberg-prompt-display">
												<pre>
													{ tab.name === 'system'
														? lastResponse.prompts
																.system
														: lastResponse.prompts
																.user }
												</pre>
											</div>
										) }
									</TabPanel>
								</CardBody>
							) }
						</Card>
					) }

					{ /* Action Buttons */ }
					<HStack justify="flex-end" spacing={ 3 }>
						<Button
							variant="tertiary"
							onClick={ onClose }
							disabled={ isGenerating }
						>
							{ __( 'Cancel', 'layoutberg' ) }
						</Button>

						<Button
							variant="primary"
							onClick={ () => {
								// Pass variation data if available and variations are enabled
								if ( matchedTemplate && settings.useVariations !== false && selectedVariation ) {
									onGenerate({
										useVariations: true,
										variationStyle: selectedVariation,
										templateKey: matchedTemplate.key
									});
								} else {
									onGenerate();
								}
							} }
							disabled={ isGenerating || ! prompt.trim() }
							icon={ isGenerating ? undefined : starFilled }
						>
							{ isGenerating ? (
								<Fragment>
									<Spinner />
									{ __( 'Generating...', 'layoutberg' ) }
								</Fragment>
							) : hasSelectedBlocks ? (
								__( 'Replace with AI Layout', 'layoutberg' )
							) : (
								__( 'Generate AI Layout', 'layoutberg' )
							) }
						</Button>
					</HStack>
				</VStack>
			) }
		</Modal>
	);
};

export default LayoutBergModal;
