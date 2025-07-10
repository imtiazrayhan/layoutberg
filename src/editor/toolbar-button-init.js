/**
 * LayoutBerg Toolbar Button Initialization
 *
 * Initializes the toolbar button immediately when the editor loads
 *
 * @package LayoutBerg
 * @since 1.0.0
 */

import { __ } from '@wordpress/i18n';
import { addAction } from '@wordpress/hooks';
import domReady from '@wordpress/dom-ready';

/**
 * Add LayoutBerg button to the editor toolbar
 */
const addLayoutBergButton = () => {
	let buttonAdded = false;

	// Function to create and insert the button
	const insertButton = () => {
		// If button already added, check if it still exists
		if (
			buttonAdded &&
			document.getElementById( 'layoutberg-toolbar-button' )
		) {
			return true;
		}

		// Try multiple selectors for the right side of the toolbar
		const selectors = [
			'.editor-header__settings',
			'.edit-post-header__settings',
			'.interface-interface-skeleton__header .editor-header__settings',
			'.editor-header__toolbar',
			'.edit-post-header-toolbar',
		];

		let targetElement = null;
		for ( const selector of selectors ) {
			targetElement = document.querySelector( selector );
			if ( targetElement ) {
				break;
			}
		}

		if ( ! targetElement ) {
			return false;
		}

		// Remove existing button if it exists
		const existingButton = document.getElementById(
			'layoutberg-toolbar-button'
		);
		if ( existingButton ) {
			existingButton.remove();
		}

		// Create button container
		const buttonContainer = document.createElement( 'div' );
		buttonContainer.className = 'layoutberg-toolbar-container';
		buttonContainer.id = 'layoutberg-toolbar-button';
		buttonContainer.style.cssText =
			'display: inline-flex; align-items: center; margin: 0 8px;';

		// Create the button
		const button = document.createElement( 'button' );
		button.className =
			'components-button layoutberg-top-toolbar-button has-icon has-text';
		button.type = 'button';
		button.setAttribute(
			'aria-label',
			__( 'Generate AI Layout', 'layoutberg' )
		);
		button.style.cssText =
			'display: inline-flex !important; align-items: center !important;';

		// Add logo icon
		const iconImg = document.createElement( 'img' );
		iconImg.src =
			window.layoutbergEditor && window.layoutbergEditor.pluginUrl
				? window.layoutbergEditor.pluginUrl +
				  'assets/images/layoutberg-logo.png'
				: '/wp-content/plugins/layoutberg/assets/images/layoutberg-logo.png';
		iconImg.alt = 'LayoutBerg';
		iconImg.style.cssText =
			'width: 20px; height: 20px; object-fit: contain;';
		button.appendChild( iconImg );

		// Add text
		const textSpan = document.createElement( 'span' );
		textSpan.className = 'layoutberg-button-text';
		textSpan.style.cssText =
			'display: inline-block !important; margin-left: 4px; font-size: 13px; font-weight: 600;';
		textSpan.textContent = __( 'AI Layout', 'layoutberg' );
		button.appendChild( textSpan );

		// Add click handler
		button.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			e.stopPropagation();

			// Try to use the global function if available
			if ( window.layoutbergOpenModal ) {
				window.layoutbergOpenModal();
			} else {
				// Fallback: trigger the sidebar button click
				const sidebarButton = document.querySelector(
					'[aria-label="LayoutBerg"]'
				);
				if ( sidebarButton ) {
					sidebarButton.click();
					setTimeout( () => {
						const generateButton = document.querySelector(
							'.layoutberg-generate-button'
						);
						if ( generateButton ) {
							generateButton.click();
						}
					}, 100 );
				} else {
					console.log( 'LayoutBerg: Could not find modal trigger' );
				}
			}
		} );

		// Append button to container
		buttonContainer.appendChild( button );

		// Find the best insertion point in the right toolbar
		const insertInRightToolbar = () => {
			// Always insert at the very beginning of the right toolbar area
			if (
				targetElement.classList.contains( 'editor-header__settings' )
			) {
				targetElement.insertBefore(
					buttonContainer,
					targetElement.firstChild
				);
				return true;
			}

			// For other toolbar containers, insert at the beginning
			if ( targetElement.firstChild ) {
				targetElement.insertBefore(
					buttonContainer,
					targetElement.firstChild
				);
				return true;
			}

			// Final fallback
			targetElement.appendChild( buttonContainer );
			return true;
		};

		insertInRightToolbar();

		// Update button text based on selection
		const updateButtonText = () => {
			if ( ! wp.data || ! textSpan ) return;

			try {
				const { select } = wp.data;
				const blockEditorStore = select( 'core/block-editor' );
				if ( ! blockEditorStore ) return;

				const selectedBlockIds =
					blockEditorStore.getSelectedBlockClientIds();
				const hasBlocks = selectedBlockIds.length > 0;

				textSpan.textContent = hasBlocks
					? __( 'Replace with AI', 'layoutberg' )
					: __( 'AI Layout', 'layoutberg' );
			} catch ( e ) {
				// Ignore errors from data store
			}
		};

		// Subscribe to selection changes
		if ( wp.data && wp.data.subscribe ) {
			const unsubscribe = wp.data.subscribe( updateButtonText );

			// Store unsubscribe function on the button for cleanup
			button._unsubscribe = unsubscribe;
		}

		buttonAdded = true;
		return true;
	};

	// Function to monitor and maintain button presence
	const maintainButton = () => {
		if ( ! document.getElementById( 'layoutberg-toolbar-button' ) ) {
			insertButton();
		}
	};

	// Try to insert immediately
	if ( ! insertButton() ) {
		// If not successful, try again with delays
		const attempts = [ 100, 500, 1000, 2000, 3000, 5000 ];
		attempts.forEach( ( delay ) => {
			setTimeout( () => {
				if (
					! document.getElementById( 'layoutberg-toolbar-button' )
				) {
					insertButton();
				}
			}, delay );
		} );
	}

	// Monitor for button removal (WordPress might recreate the toolbar)
	setInterval( maintainButton, 2000 );

	// Also monitor for DOM changes
	if ( window.MutationObserver ) {
		const observer = new MutationObserver( ( mutations ) => {
			// Check if our button was removed
			if ( ! document.getElementById( 'layoutberg-toolbar-button' ) ) {
				// Debounce the re-insertion
				clearTimeout( observer._timeout );
				observer._timeout = setTimeout( () => {
					maintainButton();
				}, 100 );
			}
		} );

		// Observe the entire editor for changes
		const editorElement = document.querySelector(
			'.edit-post-layout, .editor-editor-interface'
		);
		if ( editorElement ) {
			observer.observe( editorElement, {
				childList: true,
				subtree: true,
			} );
		}
	}
};

// Initialize when DOM is ready
domReady( () => {
	addLayoutBergButton();
} );

// Also try when the editor is initialized
addAction( 'editor.PostEdit.mounted', 'layoutberg/toolbar-button', () => {
	addLayoutBergButton();
} );

// Try on window load as well
window.addEventListener( 'load', () => {
	addLayoutBergButton();
} );

// Export for use in other files if needed
export default addLayoutBergButton;
