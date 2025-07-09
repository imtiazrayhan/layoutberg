/**
 * API Setup Step Component
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

const ApiSetup = ( { data, onNext, isLoading, onboardingData } ) => {
	const [ apiKey, setApiKey ] = useState( '' );
	const [ isValidating, setIsValidating ] = useState( false );
	const [ validationError, setValidationError ] = useState( '' );
	const [ isValid, setIsValid ] = useState( false );

	// Check if API key already exists
	useEffect( () => {
		if ( onboardingData?.has_api_key || data?.apiKeySet ) {
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
					provider: 'openai',
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
		if ( isValid || onboardingData?.has_api_key ) {
			onNext( {
				openai_api_key: apiKey,
				apiKeySet: true,
			} );
		}
	};

	const handleSkip = () => {
		onNext( {
			apiKeySkipped: true,
		} );
	};

	return (
		<div className="layoutberg-onboarding__step layoutberg-onboarding__step--api-setup">
			<h2>{ __( 'Connect Your OpenAI Account', 'layoutberg' ) }</h2>
			<p className="layoutberg-onboarding__step-description">
				{ __(
					"LayoutBerg uses OpenAI to generate layouts. You'll need your own API key.",
					'layoutberg'
				) }
			</p>

			{ onboardingData?.has_api_key && (
				<Notice status="success" isDismissible={ false }>
					{ __(
						'Great! You already have an API key configured. You can update it below or continue to the next step.',
						'layoutberg'
					) }
				</Notice>
			) }

			<div className="layoutberg-onboarding__api-setup-content">
				<div className="layoutberg-onboarding__api-instructions">
					<h3>{ __( 'How to get your API key:', 'layoutberg' ) }</h3>
					<ol>
						<li>
							{ __( 'Visit', 'layoutberg' ) }{ ' ' }
							<ExternalLink href="https://platform.openai.com/signup">
								{ __( 'OpenAI Platform', 'layoutberg' ) }
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
							<ExternalLink href="https://platform.openai.com/api-keys">
								{ __( 'API Keys', 'layoutberg' ) }
							</ExternalLink>
						</li>
						<li>
							{ __(
								'Click "Create new secret key"',
								'layoutberg'
							) }
						</li>
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
						label={ __( 'OpenAI API Key', 'layoutberg' ) }
						value={ apiKey }
						onChange={ setApiKey }
						type="password"
						placeholder="sk-..."
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

					{ isValid && ! onboardingData?.has_api_key && (
						<Notice status="success" isDismissible={ false }>
							{ __(
								'API key validated successfully!',
								'layoutberg'
							) }
						</Notice>
					) }

					<div className="layoutberg-onboarding__api-actions">
						{ ! isValid && ! onboardingData?.has_api_key && (
							<>
								<Button
									variant="primary"
									onClick={ validateApiKey }
									isBusy={ isValidating }
									disabled={ isValidating || ! apiKey }
								>
									{ __( 'Validate Key', 'layoutberg' ) }
								</Button>
								<Button
									variant="link"
									onClick={ handleSkip }
									disabled={ isValidating }
								>
									{ __( 'Skip for now', 'layoutberg' ) }
								</Button>
							</>
						) }
					</div>
				</div>
			</div>

			<div className="layoutberg-onboarding__api-info">
				<h4>{ __( 'Pricing Information', 'layoutberg' ) }</h4>
				<p>
					{ __(
						'OpenAI charges based on usage. Most users spend less than $5/month. You can set spending limits in your OpenAI account.',
						'layoutberg'
					) }
				</p>
			</div>
		</div>
	);
};

export default ApiSetup;
