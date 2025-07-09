/**
 * Plugin Recommendations Step Component
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	Card,
	CardBody,
	Notice,
	ExternalLink,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const PluginRecommendations = ( { onNext, isLoading } ) => {
	const [ installingPlugins, setInstallingPlugins ] = useState( {} );
	const [ installErrors, setInstallErrors ] = useState( {} );
	const [ plugins, setPlugins ] = useState(
		layoutbergOnboarding.plugins || {}
	);

	const installPlugin = async ( slug ) => {
		setInstallingPlugins( { ...installingPlugins, [ slug ]: true } );
		setInstallErrors( { ...installErrors, [ slug ]: '' } );

		try {
			await apiFetch( {
				path: '/layoutberg/v1/onboarding/install-plugin',
				method: 'POST',
				data: { slug },
			} );

			// Update plugin status
			setPlugins( {
				...plugins,
				[ slug ]: {
					...plugins[ slug ],
					installed: true,
					active: true,
				},
			} );
		} catch ( error ) {
			setInstallErrors( {
				...installErrors,
				[ slug ]:
					error.message || __( 'Installation failed', 'layoutberg' ),
			} );
		}

		setInstallingPlugins( { ...installingPlugins, [ slug ]: false } );
	};

	const handleNext = () => {
		onNext( {
			plugins_installed: Object.keys( plugins ).filter(
				( slug ) => plugins[ slug ].active
			),
		} );
	};

	const pluginCards = [
		{
			slug: 'ultimate-blocks',
			name: __( 'Ultimate Blocks', 'layoutberg' ),
			description: __(
				'A collection of essential blocks to supercharge your content creation. Includes advanced blocks like content toggle, testimonials, countdown, and more.',
				'layoutberg'
			),
			features: [
				__( '40+ Custom Blocks', 'layoutberg' ),
				__( 'No Coding Required', 'layoutberg' ),
				__( 'Lightweight & Fast', 'layoutberg' ),
			],
			icon: '🎨',
		},
		{
			slug: 'tableberg',
			name: __( 'TableBerg', 'layoutberg' ),
			description: __(
				'Create beautiful, responsive tables with advanced features like sorting, filtering, and custom styling - all within Gutenberg.',
				'layoutberg'
			),
			features: [
				__( 'Responsive Tables', 'layoutberg' ),
				__( 'Advanced Styling', 'layoutberg' ),
				__( 'Import from CSV', 'layoutberg' ),
			],
			icon: '📊',
		},
	];

	return (
		<div className="layoutberg-onboarding__step layoutberg-onboarding__step--plugins">
			<h2>{ __( 'Enhance Your Experience', 'layoutberg' ) }</h2>
			<p className="layoutberg-onboarding__step-description">
				{ __(
					'These plugins work perfectly with LayoutBerg to give you even more design possibilities.',
					'layoutberg'
				) }
			</p>

			<div className="layoutberg-onboarding__plugins-grid">
				{ pluginCards.map( ( pluginInfo ) => {
					const plugin = plugins[ pluginInfo.slug ];
					const isInstalled = plugin?.installed;
					const isActive = plugin?.active;
					const isInstalling = installingPlugins[ pluginInfo.slug ];
					const error = installErrors[ pluginInfo.slug ];

					return (
						<Card
							key={ pluginInfo.slug }
							className="layoutberg-onboarding__plugin-card"
						>
							<CardBody>
								<div className="layoutberg-onboarding__plugin-header">
									<span className="layoutberg-onboarding__plugin-icon">
										{ pluginInfo.icon }
									</span>
									<h3>{ pluginInfo.name }</h3>
									{ isActive && (
										<span className="layoutberg-onboarding__plugin-badge">
											{ __( 'Active', 'layoutberg' ) }
										</span>
									) }
								</div>

								<p className="layoutberg-onboarding__plugin-description">
									{ pluginInfo.description }
								</p>

								<ul className="layoutberg-onboarding__plugin-features">
									{ pluginInfo.features.map(
										( feature, index ) => (
											<li key={ index }>
												<span className="dashicons dashicons-yes"></span>
												{ feature }
											</li>
										)
									) }
								</ul>

								{ error && (
									<Notice
										status="error"
										isDismissible={ false }
									>
										{ error }
									</Notice>
								) }

								<div className="layoutberg-onboarding__plugin-actions">
									{ ! isActive && (
										<Button
											variant="secondary"
											onClick={ () =>
												installPlugin( pluginInfo.slug )
											}
											isBusy={ isInstalling }
											disabled={ isInstalling }
										>
											{ isInstalled
												? __( 'Activate', 'layoutberg' )
												: __(
														'Install & Activate',
														'layoutberg'
												  ) }
										</Button>
									) }
									{ isActive && (
										<Button variant="secondary" disabled>
											<span className="dashicons dashicons-yes"></span>
											{ __( 'Activated', 'layoutberg' ) }
										</Button>
									) }
									<ExternalLink
										href={ `https://wordpress.org/plugins/${ pluginInfo.slug }/` }
									>
										{ __( 'Learn More', 'layoutberg' ) }
									</ExternalLink>
								</div>
							</CardBody>
						</Card>
					);
				} ) }
			</div>
		</div>
	);
};

export default PluginRecommendations;
