/**
 * Onboarding Wizard Entry Point
 */
import { render } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import OnboardingWizard from './components/OnboardingWizard';
import './style.scss';

// Wait for DOM to be ready
document.addEventListener( 'DOMContentLoaded', () => {
	const rootElement = document.getElementById( 'layoutberg-onboarding-root' );

	if ( rootElement ) {
		render( <OnboardingWizard />, rootElement );
	}
} );
