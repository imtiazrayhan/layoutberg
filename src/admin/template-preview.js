/**
 * Template Preview Component
 *
 * This component renders a template preview using Gutenberg's block editor components
 * for a more accurate representation of how the template will look in the editor.
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import {
	BlockPreview,
	BlockEditorProvider,
	BlockList,
} from '@wordpress/block-editor';
import { parse, serialize, createBlock } from '@wordpress/blocks';
import { Spinner, TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createRoot } from '@wordpress/element';
import './template-preview.css';

const TemplatePreview = ( {
	templateContent,
	isLoading = false,
	showCode = false,
} ) => {
	const [ blocks, setBlocks ] = useState( [] );
	const [ error, setError ] = useState( null );
	const [ viewportWidth, setViewportWidth ] = useState( 1200 );
	const [ useHtmlPreview, setUseHtmlPreview ] = useState( false );
	const containerRef = useRef( null );

	useEffect( () => {
		if ( templateContent ) {
			try {
				// Decode HTML entities if present
				const textarea = document.createElement( 'textarea' );
				textarea.innerHTML = templateContent;
				let decodedContent = textarea.value;

				// Ensure proper line breaks between block comments and HTML
				// The parse function needs proper formatting to work correctly
				decodedContent = decodedContent
					.replace( /-->(\s*)</g, '-->\n<' ) // Add line break after closing comment before HTML
					.replace( />(\s*)<!--/g, '>\n<!--' ) // Add line break after HTML before opening comment
					.replace( /<!--\s+\/wp:/g, '\n<!-- /wp:' ) // Ensure closing blocks are on new lines
					.replace( /<!--\s+wp:/g, '\n<!-- wp:' ); // Ensure opening blocks are on new lines

				// Remove any leading newlines
				decodedContent = decodedContent.trim();

				// Try different parsing approaches
				let parsedBlocks = [];

				// First try the imported parse function
				try {
					parsedBlocks = parse( decodedContent );
				} catch ( e ) {
					// If no blocks found, try using wp.blocks if available
					if (
						parsedBlocks.length === 0 &&
						window.wp &&
						window.wp.blocks &&
						window.wp.blocks.parse
					) {
						try {
							parsedBlocks = window.wp.blocks.parse( decodedContent );
						} catch ( e ) {
							// If still no blocks, try a more aggressive formatting approach
							if ( parsedBlocks.length === 0 ) {
								// Ensure each block is on its own line with proper spacing
								const reformatted = decodedContent
									.replace( /-->\s*/g, '-->\n\n' )
									.replace( /\s*<!--/g, '\n\n<!--' )
									.trim();

								try {
									parsedBlocks = parse( reformatted );
								} catch ( e ) {
									setError(
										__( 'Failed to parse template content', 'layoutberg' )
									);
									setBlocks( [] );
									return;
								}
							}
						}
					}
				}

				// Check if we got any valid blocks
				const validBlocks = parsedBlocks.filter(
					( block ) => block.name !== null
				);

				// If no valid blocks found, use HTML preview
				if ( validBlocks.length === 0 ) {
					setUseHtmlPreview( true );
				} else {
					setUseHtmlPreview( false );
				}

				setBlocks( parsedBlocks );
				setError( null );
			} catch ( err ) {
				setError(
					__( 'Failed to parse template content', 'layoutberg' )
				);
				setBlocks( [] );
			}
		} else {
		}
	}, [ templateContent ] );

	// Adjust viewport width based on container size
	useEffect( () => {
		const updateViewportWidth = () => {
			if ( containerRef.current ) {
				const containerWidth = containerRef.current.offsetWidth;
				setViewportWidth( Math.min( containerWidth - 40, 1200 ) );
			}
		};

		updateViewportWidth();
		window.addEventListener( 'resize', updateViewportWidth );
		return () =>
			window.removeEventListener( 'resize', updateViewportWidth );
	}, [] );

	if ( isLoading ) {
		return (
			<div className="layoutberg-template-preview-loading">
				<Spinner />
				<p>{ __( 'Loading preview...', 'layoutberg' ) }</p>
			</div>
		);
	}

	if ( error ) {
		return (
			<div className="layoutberg-template-preview-error">
				<p>{ error }</p>
			</div>
		);
	}

	// Filter out empty blocks (blocks with name === null)
	const validBlocks = blocks.filter( ( block ) => block.name !== null );

	const tabs = [
		{
			name: 'visual',
			title: __( 'Visual Preview', 'layoutberg' ),
			className: 'layoutberg-preview-tab-visual',
		},
		{
			name: 'code',
			title: __( 'Block Code', 'layoutberg' ),
			className: 'layoutberg-preview-tab-code',
		},
	];

	// Create the HTML preview component
	const HtmlPreview = () => (
		<div className="layoutberg-template-html-preview-wrapper">
			<iframe
				srcDoc={ `
					<!DOCTYPE html>
					<html>
					<head>
						<meta charset="UTF-8">
						<meta name="viewport" content="width=device-width, initial-scale=1">
						<style>
							body {
								margin: 0;
								padding: 20px;
								font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
								line-height: 1.6;
								color: #1e1e1e;
								background: #fff;
							}
							* {
								box-sizing: border-box;
							}
							img {
								max-width: 100%;
								height: auto;
							}
							.wp-block-cover {
								position: relative;
								background-size: cover;
								background-position: center;
								min-height: 430px;
								display: flex;
								align-items: center;
								justify-content: center;
								padding: 1em;
							}
							.wp-block-cover.has-parallax {
								background-attachment: fixed;
							}
							.wp-block-cover__inner-container {
								position: relative;
								z-index: 1;
								width: 100%;
							}
							.wp-block-cover.has-background-dim::before {
								content: "";
								position: absolute;
								top: 0;
								left: 0;
								right: 0;
								bottom: 0;
								background: rgba(0, 0, 0, 0.5);
								z-index: 1;
							}
							.wp-block-cover.has-background-dim-40::before {
								opacity: 0.4;
							}
							.wp-block-group {
								padding: 0;
							}
							.wp-block-columns {
								display: flex;
								flex-wrap: wrap;
								gap: 2em;
							}
							.wp-block-column {
								flex: 1;
								min-width: 250px;
							}
							.wp-block-buttons {
								display: flex;
								flex-wrap: wrap;
								gap: 0.5em;
							}
							.wp-block-button__link {
								display: inline-block;
								text-decoration: none;
								padding: 0.75em 1.5em;
								background: #007cba;
								color: #fff;
								border-radius: 4px;
								transition: opacity 0.2s;
							}
							.wp-block-button__link:hover {
								opacity: 0.9;
							}
							.has-text-align-center {
								text-align: center;
							}
							.aligncenter {
								display: block;
								margin-left: auto;
								margin-right: auto;
							}
							.alignfull {
								width: 100vw;
								position: relative;
								left: 50%;
								right: 50%;
								margin-left: -50vw;
								margin-right: -50vw;
								max-width: none;
							}
							h1, h2, h3, h4, h5, h6 {
								margin-top: 0;
								margin-bottom: 0.5em;
								font-weight: 600;
							}
							p {
								margin-top: 0;
								margin-bottom: 1em;
							}
						</style>
					</head>
					<body>
						${ templateContent }
					</body>
					</html>
				` }
				style={ {
					width: '100%',
					height: '600px',
					border: '1px solid #ddd',
					borderRadius: '4px',
					background: '#fff',
				} }
				title={ __( 'Template Preview', 'layoutberg' ) }
			/>
		</div>
	);

	// If using HTML preview or no valid blocks
	if ( useHtmlPreview || validBlocks.length === 0 ) {
		if ( ! templateContent ) {
			return (
				<div className="layoutberg-template-preview-empty">
					<p>
						{ __( 'No template content available.', 'layoutberg' ) }
					</p>
				</div>
			);
		}

		if ( showCode ) {
			return (
				<div
					className="layoutberg-template-preview-wrapper"
					ref={ containerRef }
				>
					<TabPanel
						className="layoutberg-template-preview-tabs"
						activeClass="is-active"
						tabs={ tabs }
					>
						{ ( tab ) => (
							<>
								{ tab.name === 'visual' ? (
									<HtmlPreview />
								) : (
									<div className="layoutberg-template-code-container">
										<pre>
											<code>{ templateContent }</code>
										</pre>
									</div>
								) }
							</>
						) }
					</TabPanel>
				</div>
			);
		}

		return (
			<div
				className="layoutberg-template-preview-wrapper"
				ref={ containerRef }
			>
				<HtmlPreview />
			</div>
		);
	}

	return (
		<div
			className="layoutberg-template-preview-wrapper"
			ref={ containerRef }
		>
			{ showCode ? (
				<TabPanel
					className="layoutberg-template-preview-tabs"
					activeClass="is-active"
					tabs={ tabs }
				>
					{ ( tab ) => (
						<>
							{ tab.name === 'visual' ? (
								<div className="layoutberg-template-preview-container">
									<div className="layoutberg-template-preview-frame">
										<BlockPreview
											blocks={ validBlocks }
											viewportWidth={ viewportWidth }
											minHeight={ 400 }
											additionalStyles={ [
												{
													css: `
														.block-editor-block-preview__container {
															overflow: auto;
															max-height: 600px;
														}
														.block-editor-block-preview__content {
															position: relative !important;
															transform: none !important;
															width: 100% !important;
															height: auto !important;
															min-height: 100% !important;
															margin: 0 !important;
															padding: 20px !important;
															background: #fff;
														}
														.block-editor-block-preview__content-iframe {
															width: 100% !important;
															height: auto !important;
															min-height: 400px !important;
														}
													`,
												},
											] }
										/>
									</div>
								</div>
							) : (
								<div className="layoutberg-template-code-container">
									<pre>
										<code>
											{ serialize( validBlocks ) }
										</code>
									</pre>
								</div>
							) }
						</>
					) }
				</TabPanel>
			) : (
				<div className="layoutberg-template-preview-container">
					<div className="layoutberg-template-preview-frame">
						<BlockPreview
							blocks={ validBlocks }
							viewportWidth={ viewportWidth }
							minHeight={ 400 }
							additionalStyles={ [
								{
									css: `
										.block-editor-block-preview__container {
											overflow: auto;
											max-height: 600px;
										}
										.block-editor-block-preview__content {
											position: relative !important;
											transform: none !important;
											width: 100% !important;
											height: auto !important;
											min-height: 100% !important;
											margin: 0 !important;
											padding: 20px !important;
											background: #fff;
										}
										.block-editor-block-preview__content-iframe {
											width: 100% !important;
											height: auto !important;
											min-height: 400px !important;
										}
									`,
								},
							] }
						/>
					</div>
				</div>
			) }
		</div>
	);
};

export default TemplatePreview;

/**
 * Initialize the template preview for a specific container
 */
export function initTemplatePreview(
	containerId,
	templateContent,
	options = {}
) {
	const container = document.getElementById( containerId );
	if ( ! container ) {
		console.error( `Container with ID ${ containerId } not found` );
		return;
	}

	// Use createRoot for React 18+
	const root = createRoot( container );
	root.render(
		<TemplatePreview
			templateContent={ templateContent }
			showCode={ options.showCode || false }
			isLoading={ options.isLoading || false }
		/>
	);

	return root;
}

// Expose the initialization function globally
window.layoutbergTemplatePreview = {
	init: initTemplatePreview,
	TemplatePreview,
};
