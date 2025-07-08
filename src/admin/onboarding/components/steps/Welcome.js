/**
 * Welcome Step Component
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

const Welcome = ({ onNext }) => {
	return (
		<div className="layoutberg-onboarding__step layoutberg-onboarding__step--welcome">
			<div className="layoutberg-onboarding__welcome-hero">
				<div className="layoutberg-onboarding__welcome-logo">
					<img 
						src={window.layoutbergOnboarding && window.layoutbergOnboarding.pluginUrl 
							? window.layoutbergOnboarding.pluginUrl + 'assets/images/layoutberg-logo.png'
							: '/wp-content/plugins/layoutberg/assets/images/layoutberg-logo.png'}
						alt="LayoutBerg"
						className="layoutberg-logo layoutberg-logo--large"
					/>
				</div>
				<h2>{__('Welcome to LayoutBerg!', 'layoutberg')}</h2>
				<p className="layoutberg-onboarding__welcome-subtitle">
					{__('Transform your ideas into stunning layouts with AI-powered design', 'layoutberg')}
				</p>
			</div>

			<div className="layoutberg-onboarding__welcome-features">
				<div className="layoutberg-onboarding__feature">
					<span className="dashicons dashicons-admin-customizer"></span>
					<h3>{__('AI-Powered Layouts', 'layoutberg')}</h3>
					<p>{__('Generate complete, responsive layouts using natural language prompts', 'layoutberg')}</p>
				</div>
				<div className="layoutberg-onboarding__feature">
					<span className="dashicons dashicons-block-default"></span>
					<h3>{__('Native Gutenberg', 'layoutberg')}</h3>
					<p>{__('Creates native WordPress blocks without proprietary page builders', 'layoutberg')}</p>
				</div>
				<div className="layoutberg-onboarding__feature">
					<span className="dashicons dashicons-performance"></span>
					<h3>{__('Fast & Efficient', 'layoutberg')}</h3>
					<p>{__('Optimized for speed with smart caching and efficient API usage', 'layoutberg')}</p>
				</div>
				<div className="layoutberg-onboarding__feature">
					<span className="dashicons dashicons-admin-generic"></span>
					<h3>{__('Multiple AI Models', 'layoutberg')}</h3>
					<p>{__('Choose from OpenAI GPT and Claude models to match your needs', 'layoutberg')}</p>
				</div>
			</div>

			<div className="layoutberg-onboarding__welcome-cta">
				<Button
					variant="primary"
					size="large"
					onClick={() => onNext()}
				>
					{__('Get Started', 'layoutberg')}
					<span className="dashicons dashicons-arrow-right-alt"></span>
				</Button>
				<p className="layoutberg-onboarding__welcome-time">
					{__('Setup takes less than 3 minutes', 'layoutberg')}
				</p>
			</div>
		</div>
	);
};

export default Welcome;