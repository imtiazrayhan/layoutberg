/**
 * Completion Step Component
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

const Completion = ( { data, onComplete, isLoading } ) => {
	const quickActions = [
		{
			icon: 'edit',
			title: __( 'Create Your First Layout', 'layoutberg' ),
			description: __(
				'Jump into the editor and start creating',
				'layoutberg'
			),
			action: 'editor',
			primary: true,
		},
		{
			icon: 'layout',
			title: __( 'Browse Templates', 'layoutberg' ),
			description: __(
				'Explore pre-made layouts to get inspired',
				'layoutberg'
			),
			action: 'templates',
		},
		{
			icon: 'admin-generic',
			title: __( 'Adjust Settings', 'layoutberg' ),
			description: __( 'Fine-tune your preferences', 'layoutberg' ),
			action: 'settings',
		},
		{
			icon: 'book',
			title: __( 'Read Documentation', 'layoutberg' ),
			description: __(
				'Learn more about LayoutBerg features',
				'layoutberg'
			),
			action: 'docs',
			external: true,
		},
	];

	const handleAction = ( action ) => {
		switch ( action ) {
			case 'editor':
				window.location.href = layoutbergOnboarding.editorUrl;
				break;
			case 'templates':
				window.location.href =
					layoutbergOnboarding.adminUrl +
					'admin.php?page=layoutberg-templates';
				break;
			case 'settings':
				window.location.href = layoutbergOnboarding.settingsUrl;
				break;
			case 'docs':
				window.open( 'https://docs.layoutberg.com', '_blank' );
				break;
			default:
				onComplete();
		}
	};

	return (
		<div className="layoutberg-onboarding__step layoutberg-onboarding__step--completion">
			<div className="layoutberg-onboarding__completion-hero">
				<div className="layoutberg-onboarding__completion-icon">
					<span className="dashicons dashicons-yes-alt"></span>
				</div>
				<h2>{ __( "You're All Set! 🎉", 'layoutberg' ) }</h2>
				<p className="layoutberg-onboarding__completion-subtitle">
					{ __(
						'LayoutBerg is ready to transform your ideas into beautiful layouts.',
						'layoutberg'
					) }
				</p>
			</div>

			<div className="layoutberg-onboarding__completion-summary">
				<h3>{ __( 'Setup Summary', 'layoutberg' ) }</h3>
				<div className="layoutberg-onboarding__completion-items">
					{ data.apiKeySet && (
						<div className="layoutberg-onboarding__completion-item">
							<span className="dashicons dashicons-yes"></span>
							{ __( 'API key configured', 'layoutberg' ) }
						</div>
					) }
					{ data.site_type && (
						<div className="layoutberg-onboarding__completion-item">
							<span className="dashicons dashicons-yes"></span>
							{ __( 'Site preferences saved', 'layoutberg' ) }
						</div>
					) }
					{ data.default_model && (
						<div className="layoutberg-onboarding__completion-item">
							<span className="dashicons dashicons-yes"></span>
							{ __( 'AI model selected', 'layoutberg' ) }
						</div>
					) }
					{ data.plugins_installed &&
						data.plugins_installed.length > 0 && (
							<div className="layoutberg-onboarding__completion-item">
								<span className="dashicons dashicons-yes"></span>
								{ __(
									'Recommended plugins installed',
									'layoutberg'
								) }
							</div>
						) }
				</div>
			</div>

			<div className="layoutberg-onboarding__quick-actions">
				<h3>
					{ __( 'What would you like to do next?', 'layoutberg' ) }
				</h3>
				<div className="layoutberg-onboarding__actions-grid">
					{ quickActions.map( ( action ) => (
						<button
							key={ action.action }
							className={ `layoutberg-onboarding__action-card ${
								action.primary ? 'is-primary' : ''
							}` }
							onClick={ () => handleAction( action.action ) }
						>
							<span
								className={ `dashicons dashicons-${ action.icon }` }
							></span>
							<h4>{ action.title }</h4>
							<p>{ action.description }</p>
							{ action.external && (
								<span className="dashicons dashicons-external"></span>
							) }
						</button>
					) ) }
				</div>
			</div>

			<div className="layoutberg-onboarding__completion-footer">
				<Button
					variant="primary"
					size="large"
					onClick={ onComplete }
					isBusy={ isLoading }
					disabled={ isLoading }
				>
					{ __( 'Go to Dashboard', 'layoutberg' ) }
				</Button>
				<p className="layoutberg-onboarding__completion-note">
					{ __(
						'You can always access these options from the LayoutBerg menu.',
						'layoutberg'
					) }
				</p>
			</div>
		</div>
	);
};

export default Completion;
