/**
 * Site Context Step Component
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

// Custom Radio Group component for two-column layout
const RadioGroup = ( { label, options, selected, onChange } ) => {
	return (
		<div className="layoutberg-onboarding__radio-group">
			<label className="layoutberg-onboarding__radio-label">
				{ label }
			</label>
			<div className="layoutberg-onboarding__radio-options">
				{ options.map( ( option ) => (
					<label
						key={ option.value }
						className="layoutberg-onboarding__radio-option"
					>
						<input
							type="radio"
							name={ label }
							value={ option.value }
							checked={ selected === option.value }
							onChange={ ( e ) => onChange( e.target.value ) }
						/>
						<span>{ option.label }</span>
					</label>
				) ) }
			</div>
		</div>
	);
};

const SiteContext = ( { data, onNext, isLoading } ) => {
	const [ siteType, setSiteType ] = useState( data.site_type || 'business' );
	const [ stylePreference, setStylePreference ] = useState(
		data.style_preference || 'modern'
	);
	const [ colorPreference, setColorPreference ] = useState(
		data.color_preference || 'vibrant'
	);
	const [ layoutDensity, setLayoutDensity ] = useState(
		data.layout_density || 'balanced'
	);

	const handleNext = () => {
		onNext( {
			site_type: siteType,
			style_preference: stylePreference,
			color_preference: colorPreference,
			layout_density: layoutDensity,
		} );
	};

	return (
		<div className="layoutberg-onboarding__step layoutberg-onboarding__step--site-context">
			<h2>{ __( 'Tell Us About Your Site', 'layoutberg' ) }</h2>
			<p className="layoutberg-onboarding__step-description">
				{ __(
					'Help us understand your needs so we can generate better layouts for you.',
					'layoutberg'
				) }
			</p>

			<div className="layoutberg-onboarding__form-section">
				<RadioGroup
					label={ __(
						'What type of website are you building?',
						'layoutberg'
					) }
					selected={ siteType }
					options={ [
						{
							label: __( 'Business/Corporate', 'layoutberg' ),
							value: 'business',
						},
						{
							label: __( 'Blog/Magazine', 'layoutberg' ),
							value: 'blog',
						},
						{
							label: __( 'Portfolio/Creative', 'layoutberg' ),
							value: 'portfolio',
						},
						{
							label: __( 'E-commerce/Shop', 'layoutberg' ),
							value: 'ecommerce',
						},
						{
							label: __( 'Non-profit/Community', 'layoutberg' ),
							value: 'nonprofit',
						},
						{ label: __( 'Other', 'layoutberg' ), value: 'other' },
					] }
					onChange={ setSiteType }
				/>
			</div>

			<div className="layoutberg-onboarding__form-section">
				<RadioGroup
					label={ __( 'What style do you prefer?', 'layoutberg' ) }
					selected={ stylePreference }
					options={ [
						{
							label: __( 'Modern & Clean', 'layoutberg' ),
							value: 'modern',
						},
						{
							label: __( 'Classic & Traditional', 'layoutberg' ),
							value: 'classic',
						},
						{
							label: __( 'Bold & Creative', 'layoutberg' ),
							value: 'bold',
						},
						{
							label: __( 'Minimal & Simple', 'layoutberg' ),
							value: 'minimal',
						},
						{
							label: __( 'Playful & Fun', 'layoutberg' ),
							value: 'playful',
						},
					] }
					onChange={ setStylePreference }
				/>
			</div>

			<div className="layoutberg-onboarding__form-section">
				<RadioGroup
					label={ __( 'Color preference?', 'layoutberg' ) }
					selected={ colorPreference }
					options={ [
						{
							label: __( 'Vibrant & Colorful', 'layoutberg' ),
							value: 'vibrant',
						},
						{
							label: __( 'Neutral & Muted', 'layoutberg' ),
							value: 'neutral',
						},
						{
							label: __( 'Dark & Moody', 'layoutberg' ),
							value: 'dark',
						},
						{
							label: __( 'Light & Airy', 'layoutberg' ),
							value: 'light',
						},
						{
							label: __( 'Brand Colors Only', 'layoutberg' ),
							value: 'brand',
						},
					] }
					onChange={ setColorPreference }
				/>
			</div>

			<div className="layoutberg-onboarding__form-section">
				<RadioGroup
					label={ __( 'Layout density?', 'layoutberg' ) }
					selected={ layoutDensity }
					options={ [
						{
							label: __(
								'Spacious - Lots of white space',
								'layoutberg'
							),
							value: 'spacious',
						},
						{
							label: __(
								'Balanced - Standard spacing',
								'layoutberg'
							),
							value: 'balanced',
						},
						{
							label: __(
								'Compact - More content visible',
								'layoutberg'
							),
							value: 'compact',
						},
					] }
					onChange={ setLayoutDensity }
				/>
			</div>
		</div>
	);
};

export default SiteContext;
