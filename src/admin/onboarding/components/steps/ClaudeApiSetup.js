/**
 * Claude API Setup Step Component
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	TextControl,
	Notice,
	ExternalLink,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const ClaudeApiSetup = ( { data, onNext, isLoading, onboardingData } ) => {
	const [ apiKey, setApiKey ] = useState( '' );
	const [ isValidating, setIsValidating ] = useState( false );
	const [ validationError, setValidationError ] = useState( '' );
	const [ isValid, setIsValid ] = useState( false );

	// Check if Claude API key already exists
	useEffect( () => {
		if ( onboardingData?.has_claude_key || data?.claudeKeySet ) {
			setIsValid( true );
		}
	}, [ onboardingData, data ] );

	const validateApiKey = async () => {
		if ( ! apiKey ) {
			setValidationError( __( 'Please enter an API key', 'layoutberg' ) );
			return;
		}

		setIsValidating( true );
		setValidationError( '' );

		try {
			const response = await apiFetch( {
				path: '/layoutberg/v1/validate-key',
				method: 'POST',
				data: {
					api_key: apiKey,
					provider: 'claude',
				},
			} );

			if ( response.valid ) {
				setIsValid( true );
				setValidationError( '' );
				// Automatically proceed to next step after successful validation
				setTimeout( () => {
					handleNext();
				}, 1000 );
			}
		} catch ( error ) {
			setValidationError(
				error.message ||
					__(
						'Invalid API key. Please check and try again.',
						'layoutberg'
					)
			);
			setIsValid( false );
		}

		setIsValidating( false );
	};

	const handleNext = () => {
		if ( isValid || onboardingData?.has_claude_key ) {
			onNext( {
				claude_api_key: apiKey,
				claudeKeySet: true,
			} );
		}
	};

	const handleSkip = () => {
		onNext( {
			claudeKeySkipped: true,
		} );
	};

	return (
		<div className="layoutberg-onboarding__step layoutberg-onboarding__step--api-setup">
			<h2>{ __( 'Connect Claude (Optional)', 'layoutberg' ) }</h2>
			<p className="layoutberg-onboarding__step-description">
				{ __(
					'Add Claude for even more AI model options. Claude excels at understanding complex instructions and creating sophisticated layouts.',
					'layoutberg'
				) }
			</p>

			{ onboardingData?.has_claude_key && (
				<Notice status="success" isDismissible={ false }>
					{ __(
						'Great! You already have a Claude API key configured. You can update it below or continue to the next step.',
						'layoutberg'
					) }
				</Notice>
			) }

			<div className="layoutberg-onboarding__api-setup-content">
				<div className="layoutberg-onboarding__api-instructions">
					<h3>
						{ __(
							'How to get your Claude API key:',
							'layoutberg'
						) }
					</h3>
					<ol>
						<li>
							{ __( 'Visit', 'layoutberg' ) }{ ' ' }
							<ExternalLink href="https://console.anthropic.com">
								{ __( 'Anthropic Console', 'layoutberg' ) }
							</ExternalLink>
						</li>
						<li>
							{ __(
								'Sign up or log in to your account',
								'layoutberg'
							) }
						</li>
						<li>
							{ __( 'Go to', 'layoutberg' ) }{ ' ' }
							<ExternalLink href="https://console.anthropic.com/api-keys">
								{ __( 'API Keys', 'layoutberg' ) }
							</ExternalLink>
						</li>
						<li>{ __( 'Click "Create Key"', 'layoutberg' ) }</li>
						<li>
							{ __(
								'Copy the key and paste it below',
								'layoutberg'
							) }
						</li>
					</ol>
				</div>

				<div className="layoutberg-onboarding__api-form">
					<TextControl
						label={ __( 'Claude API Key', 'layoutberg' ) }
						value={ apiKey }
						onChange={ setApiKey }
						type="password"
						placeholder="sk-ant-..."
						help={ __(
							'Your API key is encrypted and stored securely',
							'layoutberg'
						) }
						disabled={ isValidating }
					/>

					{ validationError && (
						<Notice status="error" isDismissible={ false }>
							{ validationError }
						</Notice>
					) }

					{ isValid && ! onboardingData?.has_claude_key && (
						<Notice status="success" isDismissible={ false }>
							{ __(
								'API key validated successfully!',
								'layoutberg'
							) }
						</Notice>
					) }

					<div className="layoutberg-onboarding__api-actions">
						{ ! isValid && ! onboardingData?.has_claude_key && (
							<Button
								variant="primary"
								onClick={ validateApiKey }
								isBusy={ isValidating }
								disabled={ isValidating || ! apiKey }
							>
								{ __( 'Validate Key', 'layoutberg' ) }
							</Button>
						) }
					</div>
				</div>
			</div>

			<div className="layoutberg-onboarding__api-info">
				<h4>{ __( 'Why Choose Claude?', 'layoutberg' ) }</h4>
				<ul>
					<li>
						{ __(
							'Superior understanding of complex layout requirements',
							'layoutberg'
						) }
					</li>
					<li>
						{ __(
							'Excellent at following detailed instructions',
							'layoutberg'
						) }
					</li>
					<li>
						{ __(
							'Creates more sophisticated and nuanced designs',
							'layoutberg'
						) }
					</li>
					<li>
						{ __(
							'Great for professional and enterprise projects',
							'layoutberg'
						) }
					</li>
				</ul>
			</div>
		</div>
	);
};

export default ClaudeApiSetup;
