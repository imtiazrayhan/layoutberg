/**
 * Save as Template Modal Component
 *
 * Allows users to save generated layouts as reusable templates
 *
 * @package LayoutBerg
 * @since 1.0.0
 */

import { __, sprintf } from '@wordpress/i18n';
import { Fragment, useState } from '@wordpress/element';
import {
	Modal,
	Button,
	TextControl,
	TextareaControl,
	SelectControl,
	ToggleControl,
	Notice,
	Spinner,
	__experimentalVStack as VStack,
	__experimentalHStack as HStack,
} from '@wordpress/components';
import { download } from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';

/**
 * Save Template Modal
 */
const SaveTemplateModal = ( {
	isOpen,
	onClose,
	onSave,
	blocks,
	prompt = '',
} ) => {
	const [ isSaving, setIsSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	// Get categories from localized data first
	const availableCategories = window.layoutbergEditor?.categories || [
		{ label: __( 'General', 'layoutberg' ), value: 'general' },
		{ label: __( 'Business', 'layoutberg' ), value: 'business' },
		{ label: __( 'Blog', 'layoutberg' ), value: 'blog' },
	];

	const [ templateData, setTemplateData ] = useState( {
		name: '',
		description: '',
		category: availableCategories[ 0 ]?.value || 'general',
		tags: '',
		is_public: false,
	} );

	const updateField = ( field, value ) => {
		setTemplateData( {
			...templateData,
			[ field ]: value,
		} );
	};

	const handleSave = async () => {
		if ( ! templateData.name.trim() ) {
			setError( __( 'Please enter a template name.', 'layoutberg' ) );
			return;
		}

		setIsSaving( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: '/layoutberg/v1/templates',
				method: 'POST',
				data: {
					...templateData,
					content: blocks,
					prompt: prompt,
					tags: templateData.tags
						.split( ',' )
						.map( ( tag ) => tag.trim() )
						.filter( ( tag ) => tag ),
				},
			} );

			if ( response.id ) {
				onSave( response );
				onClose();
			} else {
				setError(
					__(
						'Failed to save template. Please try again.',
						'layoutberg'
					)
				);
			}
		} catch ( err ) {
			// Handle error
		} finally {
			setIsSaving( false );
		}
	};

	if ( ! isOpen ) return null;

	return (
		<Modal
			title={ __( 'Save as Template', 'layoutberg' ) }
			onRequestClose={ onClose }
			className="layoutberg-save-template-modal"
			icon={ download }
		>
			<VStack spacing={ 4 }>
				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }

				<TextControl
					label={ __( 'Template Name', 'layoutberg' ) }
					value={ templateData.name }
					onChange={ ( value ) => updateField( 'name', value ) }
					placeholder={ __(
						'e.g., Modern Hero Section',
						'layoutberg'
					) }
					required
				/>

				<TextareaControl
					label={ __( 'Description', 'layoutberg' ) }
					value={ templateData.description }
					onChange={ ( value ) =>
						updateField( 'description', value )
					}
					placeholder={ __(
						'Briefly describe what this template contains...',
						'layoutberg'
					) }
					rows={ 3 }
				/>

				<SelectControl
					label={ __( 'Category', 'layoutberg' ) }
					value={ templateData.category }
					options={ availableCategories }
					onChange={ ( value ) => updateField( 'category', value ) }
				/>

				<TextControl
					label={ __( 'Tags', 'layoutberg' ) }
					value={ templateData.tags }
					onChange={ ( value ) => updateField( 'tags', value ) }
					placeholder={ __(
						'hero, cta, modern (comma separated)',
						'layoutberg'
					) }
					help={ __(
						'Add tags to help find this template later',
						'layoutberg'
					) }
				/>

				<ToggleControl
					label={ __( 'Make this template public', 'layoutberg' ) }
					checked={ templateData.is_public }
					onChange={ ( value ) => updateField( 'is_public', value ) }
					help={ __(
						'Public templates can be used by other users',
						'layoutberg'
					) }
				/>

				<HStack justify="flex-end" spacing={ 3 }>
					<Button
						variant="tertiary"
						onClick={ onClose }
						disabled={ isSaving }
					>
						{ __( 'Cancel', 'layoutberg' ) }
					</Button>

					<Button
						variant="primary"
						onClick={ handleSave }
						disabled={ isSaving || ! templateData.name.trim() }
						icon={ isSaving ? undefined : download }
					>
						{ isSaving ? (
							<Fragment>
								<Spinner />
								{ __( 'Saving...', 'layoutberg' ) }
							</Fragment>
						) : (
							__( 'Save Template', 'layoutberg' )
						) }
					</Button>
				</HStack>
			</VStack>
		</Modal>
	);
};

export default SaveTemplateModal;
