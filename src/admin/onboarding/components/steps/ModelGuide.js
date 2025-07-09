/**
 * Model Guide Step Component
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody } from '@wordpress/components';

const ModelGuide = ( { data, onNext, isLoading } ) => {
	const [ selectedModel, setSelectedModel ] = useState(
		data.default_model || 'gpt-3.5-turbo'
	);

	const models = [
		// OpenAI Models
		{
			id: 'gpt-3.5-turbo',
			name: 'GPT-3.5 Turbo',
			provider: 'OpenAI',
			badge: __( 'Recommended', 'layoutberg' ),
			description: __(
				'Fast, affordable, and great for most layouts',
				'layoutberg'
			),
			pros: [
				__( 'Very fast generation', 'layoutberg' ),
				__( 'Most cost-effective', 'layoutberg' ),
				__( 'Great for simple to medium layouts', 'layoutberg' ),
			],
			cons: [ __( 'Less creative for complex designs', 'layoutberg' ) ],
			pricing: __( '~$0.002 per layout', 'layoutberg' ),
			speed: '⚡⚡⚡⚡⚡',
		},
		{
			id: 'gpt-4',
			name: 'GPT-4',
			provider: 'OpenAI',
			badge: __( 'Most Capable', 'layoutberg' ),
			description: __(
				'Superior quality for complex, creative layouts',
				'layoutberg'
			),
			pros: [
				__( 'Best understanding of complex prompts', 'layoutberg' ),
				__( 'Most creative and detailed layouts', 'layoutberg' ),
				__( 'Handles intricate designs well', 'layoutberg' ),
			],
			cons: [
				__( 'Slower generation', 'layoutberg' ),
				__( 'Higher cost', 'layoutberg' ),
			],
			pricing: __( '~$0.06 per layout', 'layoutberg' ),
			speed: '⚡⚡',
		},
		{
			id: 'gpt-4-turbo',
			name: 'GPT-4 Turbo',
			provider: 'OpenAI',
			badge: __( 'Best Balance', 'layoutberg' ),
			description: __(
				'GPT-4 quality with improved speed',
				'layoutberg'
			),
			pros: [
				__( 'GPT-4 level quality', 'layoutberg' ),
				__( 'Faster than standard GPT-4', 'layoutberg' ),
				__( 'Good for professional use', 'layoutberg' ),
			],
			cons: [ __( 'More expensive than GPT-3.5', 'layoutberg' ) ],
			pricing: __( '~$0.03 per layout', 'layoutberg' ),
			speed: '⚡⚡⚡',
		},
		// Claude Models
		{
			id: 'claude-3-5-sonnet-20241022',
			name: 'Claude 3.5 Sonnet',
			provider: 'Claude',
			badge: __( 'Latest & Fastest', 'layoutberg' ),
			description: __(
				'Most advanced Claude model with excellent performance',
				'layoutberg'
			),
			pros: [
				__( 'Superior instruction following', 'layoutberg' ),
				__( 'Very fast generation', 'layoutberg' ),
				__( 'Excellent for complex layouts', 'layoutberg' ),
				__( 'Great context understanding', 'layoutberg' ),
			],
			cons: [ __( 'Requires Claude API key', 'layoutberg' ) ],
			pricing: __( '~$0.003 per layout', 'layoutberg' ),
			speed: '⚡⚡⚡⚡',
		},
		{
			id: 'claude-3-opus-20240229',
			name: 'Claude 3 Opus',
			provider: 'Claude',
			badge: __( 'Most Powerful', 'layoutberg' ),
			description: __(
				'Top-tier model for the most demanding tasks',
				'layoutberg'
			),
			pros: [
				__( 'Exceptional creativity', 'layoutberg' ),
				__( 'Best for complex requirements', 'layoutberg' ),
				__( 'Superior reasoning capabilities', 'layoutberg' ),
			],
			cons: [
				__( 'Highest cost', 'layoutberg' ),
				__( 'Slower than Sonnet', 'layoutberg' ),
			],
			pricing: __( '~$0.015 per layout', 'layoutberg' ),
			speed: '⚡⚡',
		},
		{
			id: 'claude-3-haiku-20240307',
			name: 'Claude 3 Haiku',
			provider: 'Claude',
			badge: __( 'Ultra Fast', 'layoutberg' ),
			description: __(
				'Lightning-fast model for simple layouts',
				'layoutberg'
			),
			pros: [
				__( 'Extremely fast', 'layoutberg' ),
				__( 'Very affordable', 'layoutberg' ),
				__( 'Great for simple layouts', 'layoutberg' ),
			],
			cons: [ __( 'Less capable for complex designs', 'layoutberg' ) ],
			pricing: __( '~$0.0003 per layout', 'layoutberg' ),
			speed: '⚡⚡⚡⚡⚡',
		},
	];

	const handleNext = () => {
		onNext( {
			default_model: selectedModel,
		} );
	};

	return (
		<div className="layoutberg-onboarding__step layoutberg-onboarding__step--model-guide">
			<h2>{ __( 'Choose Your AI Model', 'layoutberg' ) }</h2>
			<p className="layoutberg-onboarding__step-description">
				{ __(
					'Different models offer different capabilities. You can always change this later.',
					'layoutberg'
				) }
			</p>

			<div className="layoutberg-onboarding__models-grid">
				{ models.map( ( model ) => (
					<Card
						key={ model.id }
						className={ `layoutberg-onboarding__model-card ${
							selectedModel === model.id ? 'is-selected' : ''
						}` }
						onClick={ () => setSelectedModel( model.id ) }
					>
						<CardBody>
							<div className="layoutberg-onboarding__model-header">
								<div>
									<h3>{ model.name }</h3>
									<span className="layoutberg-onboarding__model-provider">
										{ model.provider }
									</span>
								</div>
								{ model.badge && (
									<span className="layoutberg-onboarding__model-badge">
										{ model.badge }
									</span>
								) }
							</div>

							<p className="layoutberg-onboarding__model-description">
								{ model.description }
							</p>

							<div className="layoutberg-onboarding__model-details">
								<div className="layoutberg-onboarding__model-pros">
									<h4>{ __( 'Pros:', 'layoutberg' ) }</h4>
									<ul>
										{ model.pros.map( ( pro, index ) => (
											<li key={ index }>
												<span className="dashicons dashicons-yes"></span>
												{ pro }
											</li>
										) ) }
									</ul>
								</div>

								{ model.cons.length > 0 && (
									<div className="layoutberg-onboarding__model-cons">
										<h4>{ __( 'Cons:', 'layoutberg' ) }</h4>
										<ul>
											{ model.cons.map(
												( con, index ) => (
													<li key={ index }>
														<span className="dashicons dashicons-minus"></span>
														{ con }
													</li>
												)
											) }
										</ul>
									</div>
								) }
							</div>

							<div className="layoutberg-onboarding__model-meta">
								<div className="layoutberg-onboarding__model-pricing">
									<strong>
										{ __( 'Cost:', 'layoutberg' ) }
									</strong>{ ' ' }
									{ model.pricing }
								</div>
								<div className="layoutberg-onboarding__model-speed">
									<strong>
										{ __( 'Speed:', 'layoutberg' ) }
									</strong>{ ' ' }
									{ model.speed }
								</div>
							</div>

							<div className="layoutberg-onboarding__model-select">
								<input
									type="radio"
									name="model"
									value={ model.id }
									checked={ selectedModel === model.id }
									onChange={ ( e ) =>
										setSelectedModel( e.target.value )
									}
								/>
								<span>
									{ __( 'Select this model', 'layoutberg' ) }
								</span>
							</div>
						</CardBody>
					</Card>
				) ) }
			</div>

			<div className="layoutberg-onboarding__model-tip">
				<span className="dashicons dashicons-lightbulb"></span>
				<p>
					{ __(
						'Tip: Start with GPT-3.5 Turbo for quick results, or try Claude 3.5 Sonnet for more sophisticated layouts. You can change models anytime in settings.',
						'layoutberg'
					) }
				</p>
			</div>
		</div>
	);
};

export default ModelGuide;
