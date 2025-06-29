/**
 * Prompt Tips Step Component
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, TabPanel } from '@wordpress/components';

const PromptTips = ({ onNext, isLoading }) => {
	const [currentExample, setCurrentExample] = useState(0);

	const examples = [
		{
			title: __('Hero Section', 'layoutberg'),
			prompt: __('Create a hero section with a large background image, centered headline, subtitle, and two call-to-action buttons', 'layoutberg'),
			tip: __('Be specific about layout elements and their arrangement', 'layoutberg'),
		},
		{
			title: __('Feature Grid', 'layoutberg'),
			prompt: __('Design a 3-column feature grid with icons, headings, and descriptions. Use a light background with subtle shadows on each feature card', 'layoutberg'),
			tip: __('Mention specific styling preferences like shadows, backgrounds, or spacing', 'layoutberg'),
		},
		{
			title: __('Testimonial Carousel', 'layoutberg'),
			prompt: __('Build a testimonial section with customer quotes, names, and companies. Include star ratings and make it responsive', 'layoutberg'),
			tip: __('Include details about the content type and responsive behavior', 'layoutberg'),
		},
	];

	const bestPractices = [
		{
			icon: 'yes',
			title: __('Be Descriptive', 'layoutberg'),
			description: __('Include details about colors, spacing, alignment, and overall style', 'layoutberg'),
		},
		{
			icon: 'yes',
			title: __('Specify Structure', 'layoutberg'),
			description: __('Mention columns, rows, sections, and how elements should be arranged', 'layoutberg'),
		},
		{
			icon: 'yes',
			title: __('Include Content Types', 'layoutberg'),
			description: __('Specify if you want headings, paragraphs, images, buttons, etc.', 'layoutberg'),
		},
		{
			icon: 'no',
			title: __('Avoid Technical Jargon', 'layoutberg'),
			description: __('Use natural language instead of CSS properties or technical terms', 'layoutberg'),
		},
		{
			icon: 'no',
			title: __('Don\'t Be Too Vague', 'layoutberg'),
			description: __('Avoid prompts like "make it nice" or "create something cool"', 'layoutberg'),
		},
	];

	const tabs = [
		{
			name: 'examples',
			title: __('Examples', 'layoutberg'),
			content: (
				<div className="layoutberg-onboarding__examples">
					<div className="layoutberg-onboarding__example-card">
						<h4>{examples[currentExample].title}</h4>
						<div className="layoutberg-onboarding__example-prompt">
							<span className="dashicons dashicons-format-quote"></span>
							<p>{examples[currentExample].prompt}</p>
						</div>
						<div className="layoutberg-onboarding__example-tip">
							<span className="dashicons dashicons-lightbulb"></span>
							<p>{examples[currentExample].tip}</p>
						</div>
					</div>
					<div className="layoutberg-onboarding__example-nav">
						{examples.map((_, index) => (
							<button
								key={index}
								className={`layoutberg-onboarding__example-dot ${
									currentExample === index ? 'is-active' : ''
								}`}
								onClick={() => setCurrentExample(index)}
								aria-label={`Example ${index + 1}`}
							/>
						))}
					</div>
				</div>
			),
		},
		{
			name: 'best-practices',
			title: __('Best Practices', 'layoutberg'),
			content: (
				<div className="layoutberg-onboarding__best-practices">
					{bestPractices.map((practice, index) => (
						<div
							key={index}
							className={`layoutberg-onboarding__practice ${
								practice.icon === 'yes' ? 'is-do' : 'is-dont'
							}`}
						>
							<span className={`dashicons dashicons-${practice.icon}`}></span>
							<div>
								<h4>{practice.title}</h4>
								<p>{practice.description}</p>
							</div>
						</div>
					))}
				</div>
			),
		},
		{
			name: 'quick-start',
			title: __('Quick Start', 'layoutberg'),
			content: (
				<div className="layoutberg-onboarding__quick-start">
					<h4>{__('Prompt Formula', 'layoutberg')}</h4>
					<div className="layoutberg-onboarding__formula">
						<div className="layoutberg-onboarding__formula-part">
							<span>1</span>
							<p>{__('What to create', 'layoutberg')}</p>
							<small>{__('"Create a pricing table"', 'layoutberg')}</small>
						</div>
						<span className="layoutberg-onboarding__formula-plus">+</span>
						<div className="layoutberg-onboarding__formula-part">
							<span>2</span>
							<p>{__('Layout details', 'layoutberg')}</p>
							<small>{__('"with 3 columns"', 'layoutberg')}</small>
						</div>
						<span className="layoutberg-onboarding__formula-plus">+</span>
						<div className="layoutberg-onboarding__formula-part">
							<span>3</span>
							<p>{__('Style preferences', 'layoutberg')}</p>
							<small>{__('"modern design with shadows"', 'layoutberg')}</small>
						</div>
					</div>
					<div className="layoutberg-onboarding__formula-result">
						<p>
							<strong>{__('Result:', 'layoutberg')}</strong>{' '}
							{__('"Create a pricing table with 3 columns in a modern design with shadows"', 'layoutberg')}
						</p>
					</div>
				</div>
			),
		},
	];

	return (
		<div className="layoutberg-onboarding__step layoutberg-onboarding__step--prompt-tips">
			<h2>{__('Master the Art of Prompting', 'layoutberg')}</h2>
			<p className="layoutberg-onboarding__step-description">
				{__('Great prompts lead to amazing layouts. Here\'s how to get the best results.', 'layoutberg')}
			</p>

			<TabPanel
				className="layoutberg-onboarding__tabs"
				tabs={tabs}
			>
				{(tab) => tab.content}
			</TabPanel>

		</div>
	);
};

export default PromptTips;