/**
 * Main Onboarding Wizard Component
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

// Import step components
import Welcome from './steps/Welcome';
import ApiSetup from './steps/ApiSetup';
import ClaudeApiSetup from './steps/ClaudeApiSetup';
import SiteContext from './steps/SiteContext';
import PluginRecommendations from './steps/PluginRecommendations';
import ModelGuide from './steps/ModelGuide';
import PromptTips from './steps/PromptTips';
import Completion from './steps/Completion';

const OnboardingWizard = () => {
	const [ currentStep, setCurrentStep ] = useState( 0 );
	const [ wizardData, setWizardData ] = useState( {} );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ onboardingData, setOnboardingData ] = useState( null );

	// Define steps
	const steps = [
		{
			id: 'welcome',
			title: __( 'Welcome', 'layoutberg' ),
			component: Welcome,
		},
		{
			id: 'api-setup',
			title: __( 'OpenAI Setup', 'layoutberg' ),
			component: ApiSetup,
		},
		{
			id: 'claude-api-setup',
			title: __( 'Claude Setup', 'layoutberg' ),
			component: ClaudeApiSetup,
		},
		{
			id: 'site-context',
			title: __( 'Site Context', 'layoutberg' ),
			component: SiteContext,
		},
		{
			id: 'plugin-recommendations',
			title: __( 'Recommended Plugins', 'layoutberg' ),
			component: PluginRecommendations,
		},
		{
			id: 'model-guide',
			title: __( 'Choose Your Model', 'layoutberg' ),
			component: ModelGuide,
		},
		{
			id: 'prompt-tips',
			title: __( 'Prompt Tips', 'layoutberg' ),
			component: PromptTips,
		},
		{
			id: 'completion',
			title: __( 'All Set!', 'layoutberg' ),
			component: Completion,
		},
	];

	// Load initial data
	useEffect( () => {
		apiFetch( {
			path: '/layoutberg/v1/onboarding/data',
		} ).then( ( data ) => {
			setOnboardingData( data );

			// Check if API keys are already set
			if ( data.has_api_key ) {
				setWizardData( ( prev ) => ( {
					...prev,
					apiKeySet: true,
				} ) );
			}
			if ( data.has_claude_key ) {
				setWizardData( ( prev ) => ( {
					...prev,
					claudeKeySet: true,
				} ) );
			}
		} );
	}, [] );

	// Handle next step
	const handleNext = async ( stepData = {} ) => {
		// Save current step data
		const updatedData = { ...wizardData, ...stepData };
		setWizardData( updatedData );

		// Save progress to server
		if ( steps[ currentStep ].id !== 'welcome' ) {
			setIsLoading( true );
			try {
				await apiFetch( {
					path: '/layoutberg/v1/onboarding/progress',
					method: 'POST',
					data: {
						step: steps[ currentStep ].id,
						data: stepData,
					},
				} );
			} catch ( error ) {
				console.error( 'Failed to save progress:', error );
			}
			setIsLoading( false );
		}

		// Move to next step
		if ( currentStep < steps.length - 1 ) {
			setCurrentStep( currentStep + 1 );
		}
	};

	// Handle previous step
	const handlePrevious = () => {
		if ( currentStep > 0 ) {
			setCurrentStep( currentStep - 1 );
		}
	};

	// Handle completion
	const handleComplete = async () => {
		setIsLoading( true );
		try {
			const response = await apiFetch( {
				path: '/layoutberg/v1/onboarding/complete',
				method: 'POST',
			} );

			// Redirect to dashboard
			window.location.href =
				response.redirect_url || layoutbergOnboarding.dashboardUrl;
		} catch ( error ) {
			console.error( 'Failed to complete onboarding:', error );
			setIsLoading( false );
		}
	};

	// Handle skip
	const handleSkip = async () => {
		if (
			window.confirm(
				__(
					'Are you sure you want to skip the onboarding? You can always configure settings later.',
					'layoutberg'
				)
			)
		) {
			setIsLoading( true );
			try {
				const response = await apiFetch( {
					path: '/layoutberg/v1/onboarding/skip',
					method: 'POST',
				} );

				// Redirect to settings
				window.location.href =
					response.redirect_url || layoutbergOnboarding.settingsUrl;
			} catch ( error ) {
				console.error( 'Failed to skip onboarding:', error );
				setIsLoading( false );
			}
		}
	};

	const CurrentStepComponent = steps[ currentStep ].component;
	const isLastStep = currentStep === steps.length - 1;

	return (
		<div className="layoutberg-onboarding">
			<div className="layoutberg-onboarding__container">
				{ /* Header */ }
				<div className="layoutberg-onboarding__header">
					<div className="layoutberg-onboarding__logo">
						<img
							src={
								window.layoutbergOnboarding &&
								window.layoutbergOnboarding.pluginUrl
									? window.layoutbergOnboarding.pluginUrl +
									  'assets/images/layoutberg-logo.png'
									: '/wp-content/plugins/layoutberg/assets/images/layoutberg-logo.png'
							}
							alt="LayoutBerg"
							className="layoutberg-logo"
						/>
						<h1>{ __( 'LayoutBerg Setup', 'layoutberg' ) }</h1>
					</div>
					{ currentStep > 0 && currentStep < steps.length - 1 && (
						<Button
							variant="tertiary"
							onClick={ handleSkip }
							disabled={ isLoading }
						>
							{ __( 'Skip Setup', 'layoutberg' ) }
						</Button>
					) }
				</div>

				{ /* Progress bar */ }
				{ currentStep > 0 && currentStep < steps.length - 1 && (
					<div className="layoutberg-onboarding__progress">
						<div
							className="layoutberg-onboarding__progress-bar"
							style={ {
								width: `${
									( currentStep / ( steps.length - 2 ) ) * 100
								}%`,
							} }
						/>
					</div>
				) }

				{ /* Step content */ }
				<div className="layoutberg-onboarding__content">
					<CurrentStepComponent
						data={ wizardData }
						onNext={ handleNext }
						onPrevious={ handlePrevious }
						onComplete={ handleComplete }
						isLoading={ isLoading }
						onboardingData={ onboardingData }
					/>
				</div>

				{ /* Footer navigation */ }
				<div className="layoutberg-onboarding__footer">
					<div className="layoutberg-onboarding__footer-left">
						{ currentStep > 0 && ! isLastStep && (
							<Button
								variant="secondary"
								onClick={ handlePrevious }
								disabled={ isLoading }
							>
								{ __( '← Back', 'layoutberg' ) }
							</Button>
						) }
					</div>
					<div className="layoutberg-onboarding__footer-right">
						{ currentStep > 0 && currentStep < steps.length - 1 && (
							<Button
								variant="link"
								onClick={ () => handleNext() }
								disabled={ isLoading }
							>
								{ __( 'Skip this step', 'layoutberg' ) }
							</Button>
						) }
						{ ! isLastStep && (
							<Button
								variant="primary"
								onClick={ () => handleNext() }
								isBusy={ isLoading }
								disabled={ isLoading }
							>
								{ __( 'Next →', 'layoutberg' ) }
							</Button>
						) }
					</div>
				</div>
			</div>
		</div>
	);
};

export default OnboardingWizard;
