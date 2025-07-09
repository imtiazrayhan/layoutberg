/**
 * Admin JavaScript for LayoutBerg
 * Modern interactive enhancements and UI functionality
 *
 * @package    LayoutBerg
 * @subpackage Admin/JS
 * @since      1.0.0
 */

( function ( $ ) {
	'use strict';

	// LayoutBerg Admin Object
	window.LayoutBergAdmin = {
		/**
		 * Initialize
		 */
		init: function () {
			this.bindEvents();
			this.initTooltips();
			this.initAnimations();
			this.initKeyboardShortcuts();
			this.initThemeHandling();
			this.initCharts();
			this.initProgressBars();
			this.initPricingModal();
			this.startHeartbeat();
		},

		/**
		 * Bind events
		 */
		bindEvents: function () {
			// Template actions
			$( document ).on(
				'click',
				'.layoutberg-template-use',
				this.useTemplate.bind( this )
			);
			$( document ).on(
				'click',
				'.layoutberg-template-preview',
				this.previewTemplate.bind( this )
			);
			$( document ).on(
				'click',
				'.layoutberg-template-delete',
				this.deleteTemplate.bind( this )
			);

			// Modal handling
			$( document ).on(
				'click',
				'.layoutberg-modal-close, .layoutberg-modal-cancel',
				this.closeModal.bind( this )
			);
			$( document ).on(
				'click',
				'.layoutberg-modal-backdrop',
				this.closeModal.bind( this )
			);

			// Generation actions
			$( document ).on(
				'click',
				'#layoutberg-generate',
				this.generateLayout.bind( this )
			);
			$( document ).on(
				'click',
				'.layoutberg-regenerate',
				this.regenerateLayout.bind( this )
			);
			$( document ).on(
				'click',
				'.layoutberg-apply-layout',
				this.applyLayout.bind( this )
			);

			// Template actions
			$( document ).on(
				'click',
				'#layoutberg-save-template',
				this.saveTemplate.bind( this )
			);
			$( document ).on(
				'click',
				'[data-template]',
				this.quickTemplate.bind( this )
			);

			// Interactive elements
			$( document ).on(
				'click',
				'.layoutberg-btn',
				this.handleButtonClick.bind( this )
			);
			$( document ).on(
				'input',
				'.layoutberg-input, .layoutberg-textarea',
				this.handleInputChange.bind( this )
			);
			$( document ).on(
				'change',
				'.layoutberg-select',
				this.handleSelectChange.bind( this )
			);

			// Dashboard interactions
			$( document ).on(
				'click',
				'#layoutberg-dismiss-guide',
				this.dismissGuide.bind( this )
			);
			$( document ).on(
				'click',
				'.layoutberg-refresh-stats',
				this.refreshStats.bind( this )
			);

			// Copy to clipboard
			$( document ).on(
				'click',
				'[data-copy]',
				this.copyToClipboard.bind( this )
			);

			// Form validation
			$( document ).on(
				'submit',
				'form',
				this.validateForm.bind( this )
			);

			// Responsive menu toggle
			$( document ).on(
				'click',
				'.layoutberg-menu-toggle',
				this.toggleMobileMenu.bind( this )
			);

			// Search functionality
			$( document ).on(
				'input',
				'.layoutberg-search',
				this.performSearch.bind( this )
			);

			// Drag and drop
			this.initDragAndDrop();

			// Escape key handler
			$( document ).on( 'keydown', this.handleEscapeKey.bind( this ) );
		},

		/**
		 * Initialize tooltips
		 */
		initTooltips: function () {
			// Enhanced tooltip system
			$( '.layoutberg-help-tip, [data-tooltip]' ).each(
				function () {
					var $this = $( this );
					var content =
						$this.data( 'tooltip' ) ||
						$this.attr( 'title' ) ||
						$this.data( 'tip' );

					if ( content ) {
						$this.attr( 'title', '' ); // Remove default title

						$this.on(
							'mouseenter',
							function ( e ) {
								this.showTooltip( e.currentTarget, content );
							}.bind( this )
						);

						$this.on(
							'mouseleave',
							function () {
								this.hideTooltip();
							}.bind( this )
						);
					}
				}.bind( this )
			);
		},

		/**
		 * Show tooltip
		 */
		showTooltip: function ( element, content ) {
			this.hideTooltip(); // Hide any existing tooltip

			var $tooltip = $(
				'<div class="layoutberg-tooltip">' + content + '</div>'
			);
			$( 'body' ).append( $tooltip );

			var $element = $( element );
			var elementOffset = $element.offset();
			var elementWidth = $element.outerWidth();
			var elementHeight = $element.outerHeight();
			var tooltipWidth = $tooltip.outerWidth();
			var tooltipHeight = $tooltip.outerHeight();

			// Position tooltip
			var left = elementOffset.left + elementWidth / 2 - tooltipWidth / 2;
			var top = elementOffset.top - tooltipHeight - 8;

			// Adjust if tooltip goes off screen
			if ( left < 10 ) left = 10;
			if ( left + tooltipWidth > $( window ).width() - 10 ) {
				left = $( window ).width() - tooltipWidth - 10;
			}
			if ( top < 10 ) {
				top = elementOffset.top + elementHeight + 8;
				$tooltip.addClass( 'bottom' );
			}

			$tooltip.css( { left: left, top: top } ).addClass( 'show' );
		},

		/**
		 * Hide tooltip
		 */
		hideTooltip: function () {
			$( '.layoutberg-tooltip' ).remove();
		},

		/**
		 * Initialize animations
		 */
		initAnimations: function () {
			// Fade in elements with animation class
			$( '.layoutberg-fade-in' ).each( function ( index ) {
				$( this )
					.delay( index * 100 )
					.animate( { opacity: 1 }, 300 );
			} );

			// Intersection Observer for scroll animations
			if ( 'IntersectionObserver' in window ) {
				var observer = new IntersectionObserver(
					function ( entries ) {
						entries.forEach( function ( entry ) {
							if ( entry.isIntersecting ) {
								$( entry.target ).addClass( 'animate-in' );
							}
						} );
					},
					{ threshold: 0.1 }
				);

				$( '.layoutberg-animate-on-scroll' ).each( function () {
					observer.observe( this );
				} );
			}

			// Smooth scrolling for anchor links
			$( 'a[href^="#"]' ).on( 'click', function ( e ) {
				var target = $( this.getAttribute( 'href' ) );
				if ( target.length ) {
					e.preventDefault();
					$( 'html, body' ).animate(
						{ scrollTop: target.offset().top - 100 },
						500
					);
				}
			} );
		},

		/**
		 * Initialize keyboard shortcuts
		 */
		initKeyboardShortcuts: function () {
			$( document ).on(
				'keydown',
				function ( e ) {
					// Ctrl/Cmd + S to save
					if ( ( e.ctrlKey || e.metaKey ) && e.key === 's' ) {
						e.preventDefault();
						var $saveBtn = $(
							'.layoutberg-btn[type="submit"]'
						).first();
						if ( $saveBtn.length ) {
							$saveBtn.click();
							this.showToast(
								'Settings saved with keyboard shortcut!',
								'info'
							);
						}
					}

					// Ctrl/Cmd + Shift + G to generate
					if (
						( e.ctrlKey || e.metaKey ) &&
						e.shiftKey &&
						e.key === 'G'
					) {
						e.preventDefault();
						var $generateBtn = $( '#layoutberg-generate' );
						if ( $generateBtn.length ) {
							$generateBtn.click();
						}
					}

					// Ctrl/Cmd + ? for help
					if ( ( e.ctrlKey || e.metaKey ) && e.key === '/' ) {
						e.preventDefault();
						this.showHelpModal();
					}
				}.bind( this )
			);
		},

		/**
		 * Initialize theme handling
		 */
		initThemeHandling: function () {
			// Dark mode toggle (if implemented)
			$( document ).on( 'click', '.layoutberg-theme-toggle', function () {
				$( 'body' ).toggleClass( 'layoutberg-dark-theme' );
				var isDark = $( 'body' ).hasClass( 'layoutberg-dark-theme' );
				localStorage.setItem(
					'layoutberg-theme',
					isDark ? 'dark' : 'light'
				);
			} );

			// Load saved theme
			var savedTheme = localStorage.getItem( 'layoutberg-theme' );
			if ( savedTheme === 'dark' ) {
				$( 'body' ).addClass( 'layoutberg-dark-theme' );
			}
		},

		/**
		 * Initialize charts
		 */
		initCharts: function () {
			// Simple chart implementation for stats
			$( '.layoutberg-chart' ).each(
				function () {
					var $chart = $( this );
					var data = $chart.data( 'chart' );

					if ( data && data.length ) {
						this.renderChart( $chart, data );
					}
				}.bind( this )
			);
		},

		/**
		 * Render simple chart
		 */
		renderChart: function ( $container, data ) {
			var maxValue = Math.max.apply(
				Math,
				data.map( function ( item ) {
					return item.value;
				} )
			);
			var html = '<div class="layoutberg-chart-bars">';

			data.forEach( function ( item ) {
				var height = ( item.value / maxValue ) * 100;
				html +=
					'<div class="layoutberg-chart-bar" style="height: ' +
					height +
					'%;" data-value="' +
					item.value +
					'">';
				html +=
					'<div class="layoutberg-chart-bar-label">' +
					item.label +
					'</div>';
				html += '</div>';
			} );

			html += '</div>';
			$container.html( html );
		},

		/**
		 * Initialize progress bars
		 */
		initProgressBars: function () {
			$( '.layoutberg-progress-bar' ).each( function () {
				var $bar = $( this );
				var progress = $bar.data( 'progress' ) || 0;
				$bar.find( '.layoutberg-progress-fill' ).animate(
					{ width: progress + '%' },
					1000
				);
			} );
		},

		/**
		 * Start heartbeat for real-time updates
		 */
		startHeartbeat: function () {
			if ( typeof wp !== 'undefined' && wp.heartbeat ) {
				wp.heartbeat.interval( 'fast' );

				$( document ).on( 'heartbeat-send', function ( e, data ) {
					data.layoutberg_heartbeat = {
						page: window.pagenow || 'unknown',
					};
				} );

				$( document ).on(
					'heartbeat-tick',
					function ( e, data ) {
						if ( data.layoutberg_heartbeat ) {
							// Handle real-time updates
							if ( data.layoutberg_heartbeat.notifications ) {
								this.handleNotifications(
									data.layoutberg_heartbeat.notifications
								);
							}
							if ( data.layoutberg_heartbeat.stats ) {
								this.updateStats(
									data.layoutberg_heartbeat.stats
								);
							}
						}
					}.bind( this )
				);
			}
		},

		/**
		 * Handle button clicks with enhanced feedback
		 */
		handleButtonClick: function ( e ) {
			var $button = $( e.currentTarget );

			// Add ripple effect
			if ( ! $button.hasClass( 'no-ripple' ) ) {
				this.addRippleEffect( e );
			}

			// Handle loading state
			if ( $button.hasClass( 'layoutberg-btn-loading' ) ) {
				e.preventDefault();
				return false;
			}
		},

		/**
		 * Add ripple effect to buttons
		 */
		addRippleEffect: function ( e ) {
			var $button = $( e.currentTarget );
			var rect = e.currentTarget.getBoundingClientRect();
			var ripple = $button.find( '.ripple' );

			if ( ! ripple.length ) {
				ripple = $( '<span class="ripple"></span>' );
				$button.append( ripple );
			}

			ripple.removeClass( 'animate' );

			var size = Math.max( rect.width, rect.height );
			var x = e.clientX - rect.left - size / 2;
			var y = e.clientY - rect.top - size / 2;

			ripple
				.css( {
					width: size + 'px',
					height: size + 'px',
					left: x + 'px',
					top: y + 'px',
				} )
				.addClass( 'animate' );
		},

		/**
		 * Handle input changes with validation
		 */
		handleInputChange: function ( e ) {
			var $input = $( e.currentTarget );
			var value = $input.val();

			// Real-time validation
			this.validateField( $input );

			// Auto-save for certain fields
			if ( $input.hasClass( 'auto-save' ) ) {
				clearTimeout( this.autoSaveTimeout );
				this.autoSaveTimeout = setTimeout(
					function () {
						this.autoSave( $input );
					}.bind( this ),
					1000
				);
			}
		},

		/**
		 * Handle select changes
		 */
		handleSelectChange: function ( e ) {
			var $select = $( e.currentTarget );

			// Add selected class for styling
			$select.addClass( 'has-value' );

			// Trigger dependent field updates
			// this.updateDependentFields($select); // Removed as not implemented
		},

		/**
		 * Validate form field
		 */
		validateField: function ( $field ) {
			var value = $field.val();
			var isValid = true;
			var message = '';

			// Check required fields
			if ( $field.prop( 'required' ) && ! value.trim() ) {
				isValid = false;
				message = 'This field is required';
			}

			// Check specific validation rules
			if (
				$field.attr( 'type' ) === 'email' &&
				value &&
				! this.isValidEmail( value )
			) {
				isValid = false;
				message = 'Please enter a valid email address';
			}

			if (
				$field.hasClass( 'api-key' ) &&
				value &&
				! value.startsWith( 'sk-' ) &&
				value.indexOf( '*' ) === -1
			) {
				isValid = false;
				message = 'API key should start with "sk-"';
			}

			// Update field state
			$field.toggleClass( 'invalid', ! isValid );
			$field.removeClass( 'valid' ).addClass( isValid ? 'valid' : '' );

			// Show/hide error message
			var $error = $field.siblings( '.field-error' );
			if ( ! isValid && message ) {
				if ( ! $error.length ) {
					$error = $( '<div class="field-error"></div>' );
					$field.after( $error );
				}
				$error.text( message ).show();
			} else {
				$error.hide();
			}

			return isValid;
		},

		/**
		 * Validate email
		 */
		isValidEmail: function ( email ) {
			var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			return re.test( email );
		},

		/**
		 * Show loading state with enhanced feedback
		 */
		showLoading: function ( element, text ) {
			var $element = $( element );

			// Store original state
			if ( ! $element.data( 'original-html' ) ) {
				$element.data( 'original-html', $element.html() );
			}

			// Apply loading state
			$element
				.addClass( 'layoutberg-btn-loading' )
				.prop( 'disabled', true )
				.html(
					'<span class="layoutberg-spinner"></span> ' +
						( text || 'Loading...' )
				);
		},

		/**
		 * Hide loading state
		 */
		hideLoading: function ( element, text ) {
			var $element = $( element );
			var originalHtml =
				$element.data( 'original-html' ) || text || 'Button';

			$element
				.removeClass( 'layoutberg-btn-loading' )
				.prop( 'disabled', false )
				.html( originalHtml );
		},

		/**
		 * Show toast notification
		 */
		showToast: function ( message, type, duration ) {
			type = type || 'info';
			duration = duration || 3000;

			var $toast = $(
				'<div class="layoutberg-toast layoutberg-toast-' +
					type +
					'">' +
					message +
					'</div>'
			);

			// Add to container
			var $container = $( '.layoutberg-toast-container' );
			if ( ! $container.length ) {
				$container = $(
					'<div class="layoutberg-toast-container"></div>'
				);
				$( 'body' ).append( $container );
			}

			$container.append( $toast );

			// Animate in
			setTimeout( function () {
				$toast.addClass( 'show' );
			}, 10 );

			// Auto remove
			setTimeout( function () {
				$toast.removeClass( 'show' );
				setTimeout( function () {
					$toast.remove();
				}, 300 );
			}, duration );

			// Click to dismiss
			$toast.on( 'click', function () {
				$( this ).removeClass( 'show' );
				setTimeout( function () {
					$toast.remove();
				}, 300 );
			} );
		},

		/**
		 * Enhanced notice system
		 */
		showNotice: function ( message, type, persistent ) {
			type = type || 'success';

			var $notice = $(
				'<div class="layoutberg-alert layoutberg-alert-' +
					type +
					' layoutberg-fade-in">'
			);
			$notice.html(
				'<span class="dashicons dashicons-' +
					this.getNoticeIcon( type ) +
					'"></span><div><strong>' +
					message +
					'</strong></div>'
			);

			if ( ! persistent ) {
				$notice.append(
					'<button class="layoutberg-alert-dismiss" aria-label="Dismiss">&times;</button>'
				);
			}

			// Find container or create one
			var $container = $( '.layoutberg-container' ).first();
			if ( ! $container.length ) {
				$container = $( '.layoutberg-admin-page' ).first();
			}

			$container.prepend( $notice );

			// Auto dismiss
			if ( ! persistent ) {
				setTimeout( function () {
					$notice.fadeOut( function () {
						$( this ).remove();
					} );
				}, 5000 );
			}

			// Manual dismiss
			$notice.on( 'click', '.layoutberg-alert-dismiss', function () {
				$notice.fadeOut( function () {
					$( this ).remove();
				} );
			} );
		},

		/**
		 * Get notice icon
		 */
		getNoticeIcon: function ( type ) {
			var icons = {
				success: 'yes-alt',
				error: 'warning',
				warning: 'warning',
				info: 'info',
			};
			return icons[ type ] || 'info';
		},

		/**
		 * Copy to clipboard
		 */
		copyToClipboard: function ( e ) {
			e.preventDefault();
			var $button = $( e.currentTarget );
			var text =
				$button.data( 'copy' ) ||
				$button
					.closest( '.copy-container' )
					.find( 'input, textarea' )
					.val();

			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( text ).then(
					function () {
						this.showToast(
							'Copied to clipboard!',
							'success',
							1500
						);
					}.bind( this )
				);
			} else {
				// Fallback for older browsers
				var $temp = $( '<textarea>' )
					.val( text )
					.appendTo( 'body' )
					.select();
				document.execCommand( 'copy' );
				$temp.remove();
				this.showToast( 'Copied to clipboard!', 'success', 1500 );
			}
		},

		/**
		 * Initialize drag and drop
		 */
		initDragAndDrop: function () {
			// File upload areas
			$( '.layoutberg-upload-area' )
				.on( 'dragover dragenter', function ( e ) {
					e.preventDefault();
					$( this ).addClass( 'drag-over' );
				} )
				.on( 'dragleave', function ( e ) {
					e.preventDefault();
					$( this ).removeClass( 'drag-over' );
				} )
				.on(
					'drop',
					function ( e ) {
						e.preventDefault();
						$( this ).removeClass( 'drag-over' );

						var files = e.originalEvent.dataTransfer.files;
						this.handleFileUpload( files, $( e.currentTarget ) );
					}.bind( this )
				);
		},

		/**
		 * Handle file upload
		 */
		handleFileUpload: function ( files, $container ) {
			for ( var i = 0; i < files.length; i++ ) {
				var file = files[ i ];

				// Validate file type
				if (
					! this.isValidFileType( file, $container.data( 'accept' ) )
				) {
					this.showNotice(
						'Invalid file type: ' + file.name,
						'error'
					);
					continue;
				}

				// Show progress
				this.uploadFile( file, $container );
			}
		},

		/**
		 * Validate file type
		 */
		isValidFileType: function ( file, accept ) {
			if ( ! accept ) return true;

			var acceptedTypes = accept.split( ',' ).map( function ( type ) {
				return type.trim();
			} );

			return acceptedTypes.some( function ( type ) {
				if ( type.startsWith( '.' ) ) {
					return file.name
						.toLowerCase()
						.endsWith( type.toLowerCase() );
				}
				return file.type.match(
					new RegExp( type.replace( '*', '.*' ) )
				);
			} );
		},

		/**
		 * Handle escape key
		 */
		handleEscapeKey: function ( e ) {
			if ( e.key === 'Escape' ) {
				// Close modals
				if ( $( '.layoutberg-modal.active' ).length ) {
					this.closeModal();
				}

				// Hide tooltips
				this.hideTooltip();

				// Close dropdowns
				$( '.layoutberg-dropdown.open' ).removeClass( 'open' );
			}
		},

		/**
		 * Quick template actions
		 */
		quickTemplate: function ( e ) {
			e.preventDefault();
			var $button = $( e.currentTarget );
			var template = $button.data( 'template' );
			var prompt = $button.data( 'prompt' );

			// If we have a prompt, this is a Popular Template from dashboard
			if ( prompt ) {
				// Build proper URL for new page with modal
				var adminUrl = window.location.origin + '/wp-admin/';
				var url =
					adminUrl +
					'post-new.php?post_type=page&layoutberg_open_modal=1&hide_pattern_modal=1&layoutberg_prompt=' +
					encodeURIComponent( prompt );
				window.location.href = url;
			} else {
				// Fallback for other templates
				window.location.href = $button.data( 'url' ) || '#';
			}
		},

		/**
		 * Dismiss guide
		 */
		dismissGuide: function ( e ) {
			e.preventDefault();
			var $guide = $( e.currentTarget ).closest( '.layoutberg-card' );

			$guide.fadeOut( function () {
				$( this ).remove();
			} );

			// Save dismissal
			$.post( layoutbergAdmin.ajaxUrl, {
				action: 'layoutberg_dismiss_guide',
				nonce: layoutbergAdmin.nonce,
			} );
		},

		/**
		 * Refresh stats
		 */
		refreshStats: function ( e ) {
			e.preventDefault();
			var $button = $( e.currentTarget );

			this.showLoading( $button, 'Refreshing...' );

			$.post(
				layoutbergAdmin.ajaxUrl,
				{
					action: 'layoutberg_refresh_stats',
					nonce: layoutbergAdmin.nonce,
				},
				function ( response ) {
					if ( response.success ) {
						location.reload(); // Simple refresh for now
					} else {
						this.showNotice( 'Failed to refresh stats', 'error' );
					}
				}.bind( this )
			).always(
				function () {
					this.hideLoading( $button, 'Refresh' );
				}.bind( this )
			);
		},

		/**
		 * Toggle mobile menu
		 */
		toggleMobileMenu: function ( e ) {
			e.preventDefault();
			$( '.layoutberg-admin-page' ).toggleClass( 'mobile-menu-open' );
		},

		/**
		 * Perform search
		 */
		performSearch: function ( e ) {
			var query = $( e.currentTarget ).val().toLowerCase();
			var $items = $( '.layoutberg-search-item' );

			if ( ! query ) {
				$items.show();
				return;
			}

			$items.each( function () {
				var $item = $( this );
				var text = $item.text().toLowerCase();
				$item.toggle( text.indexOf( query ) !== -1 );
			} );
		},

		/**
		 * Validate form before submission
		 */
		validateForm: function ( e ) {
			var $form = $( e.currentTarget );
			var isValid = true;

			// Validate all required fields
			$form.find( '[required]' ).each(
				function () {
					if ( ! this.validateField( $( this ) ) ) {
						isValid = false;
					}
				}.bind( this )
			);

			if ( ! isValid ) {
				e.preventDefault();
				this.showNotice( 'Please fix the errors below', 'error' );

				// Focus first invalid field
				$form.find( '.invalid' ).first().focus();
			}
		},

		/**
		 * Enhanced modal system
		 */
		openModal: function ( modalId, data ) {
			var $modal = $( '#layoutberg-modal-' + modalId );

			if ( ! $modal.length ) {
				console.warn( 'Modal not found:', modalId );
				return;
			}

			if ( data ) {
				// Populate modal with data
				$.each( data, function ( key, value ) {
					var $field = $modal.find( '[data-field="' + key + '"]' );
					if ( $field.length ) {
						$field.val( value );
					}
				} );
			}

			$modal.addClass( 'active' );
			$( 'body' ).addClass( 'modal-open' );

			// Focus first input
			setTimeout( function () {
				$modal.find( 'input, textarea, select' ).first().focus();
			}, 100 );
		},

		/**
		 * Close modal
		 */
		closeModal: function ( e ) {
			if ( e && e.target !== e.currentTarget ) {
				return; // Clicked inside modal content
			}

			$( '.layoutberg-modal' ).removeClass( 'active' );
			$( 'body' ).removeClass( 'modal-open' );
		},

		// Placeholder methods for the existing functionality
		useTemplate: function ( e ) {
			e.preventDefault();
			var templateId = $( e.currentTarget ).data( 'template-id' );
			this.showNotice( 'Template functionality coming soon!', 'info' );
		},

		previewTemplate: function ( e ) {
			e.preventDefault();
			var templateId = $( e.currentTarget ).data( 'template-id' );
			this.showNotice( 'Template preview coming soon!', 'info' );
		},

		deleteTemplate: function ( e ) {
			e.preventDefault();
			if (
				! confirm( 'Are you sure you want to delete this template?' )
			) {
				return;
			}

			var $button = $( e.currentTarget );
			this.showLoading( $button, 'Deleting...' );

			// Simulate deletion
			setTimeout(
				function () {
					$button
						.closest( '.layoutberg-template-card' )
						.fadeOut( function () {
							$( this ).remove();
						} );
					this.showNotice(
						'Template deleted successfully',
						'success'
					);
				}.bind( this ),
				1000
			);
		},

		generateLayout: function ( e ) {
			e.preventDefault();
			var $button = $( e.currentTarget );
			var prompt = $( '#layoutberg-prompt' ).val();

			if ( ! prompt ) {
				this.showNotice(
					'Please enter a prompt to generate a layout',
					'error'
				);
				return;
			}

			this.showLoading( $button, 'Generating...' );

			// Simulate generation
			setTimeout(
				function () {
					this.hideLoading( $button, 'Generate Layout' );
					this.showNotice(
						'Layout generated successfully!',
						'success'
					);
				}.bind( this ),
				3000
			);
		},

		regenerateLayout: function ( e ) {
			e.preventDefault();
			this.showNotice( 'Regenerating layout...', 'info' );
		},

		applyLayout: function ( e ) {
			e.preventDefault();
			this.showNotice( 'Layout applied to editor!', 'success' );
		},

		saveTemplate: function ( e ) {
			e.preventDefault();
			var $button = $( e.currentTarget );

			this.showLoading( $button, 'Saving...' );

			setTimeout(
				function () {
					this.hideLoading( $button, 'Save Template' );
					this.showNotice(
						'Template saved successfully!',
						'success'
					);
					this.closeModal();
				}.bind( this ),
				1500
			);
		},

		/**
		 * Initialize pricing modal
		 */
		initPricingModal: function () {
			// Handle clicks on locked buttons
			$( document ).on(
				'click',
				'.layoutberg-pricing-trigger',
				this.openPricingModal.bind( this )
			);

			// Handle clicks on export template locked links
			$( document ).on(
				'click',
				'.export-template-locked',
				this.handleLockedExport.bind( this )
			);

			// Handle pricing modal close
			$( document ).on(
				'click',
				'#layoutberg-pricing-modal .layoutberg-modal-close, #layoutberg-pricing-modal .layoutberg-modal-backdrop',
				this.closePricingModal.bind( this )
			);

			// Track upgrade button clicks
			$( document ).on(
				'click',
				'.layoutberg-upgrade-button',
				this.trackUpgradeClick.bind( this )
			);
		},

		/**
		 * Open pricing modal
		 */
		openPricingModal: function ( e ) {
			e.preventDefault();

			var $trigger = $( e.currentTarget );
			var feature = $trigger.data( 'feature' );
			var requiredPlan = $trigger.data( 'required-plan' );

			// Show the modal
			$( '#layoutberg-pricing-modal' ).fadeIn( 300 ).addClass( 'active' );
			$( 'body' ).addClass( 'modal-open' );

			// Highlight the required plan
			if ( requiredPlan ) {
				$( '.layoutberg-pricing-plan' ).removeClass(
					'layoutberg-pricing-highlight'
				);
				$(
					'.layoutberg-pricing-plan[data-plan="' + requiredPlan + '"]'
				).addClass( 'layoutberg-pricing-highlight' );

				// Scroll to highlighted plan on mobile
				if ( $( window ).width() < 768 ) {
					setTimeout( function () {
						var $highlighted = $( '.layoutberg-pricing-highlight' );
						if ( $highlighted.length ) {
							var modalBody = $(
								'#layoutberg-pricing-modal .layoutberg-modal-body'
							);
							var scrollTop = $highlighted.position().top - 20;
							modalBody.animate( { scrollTop: scrollTop }, 300 );
						}
					}, 100 );
				}
			}

			// Show feature-specific message if available
			if ( feature ) {
				this.showFeatureMessage( feature, requiredPlan );
			}
		},

		/**
		 * Handle locked export clicks
		 */
		handleLockedExport: function ( e ) {
			e.preventDefault();

			// Trigger pricing modal with export feature context
			var $trigger = $( '<button>' ).attr( {
				'data-feature': 'Template Export',
				'data-required-plan': 'professional',
			} );

			$trigger.trigger( 'click.layoutberg-pricing' );
			this.openPricingModal( {
				currentTarget: $trigger[ 0 ],
				preventDefault: function () {},
			} );
		},

		/**
		 * Close pricing modal
		 */
		closePricingModal: function ( e ) {
			if ( e && e.target !== e.currentTarget ) {
				return; // Clicked inside modal content
			}

			$( '#layoutberg-pricing-modal' )
				.fadeOut( 300 )
				.removeClass( 'active' );
			$( 'body' ).removeClass( 'modal-open' );
			$( '.layoutberg-pricing-plan' ).removeClass(
				'layoutberg-pricing-highlight'
			);
			$( '.layoutberg-pricing-feature-message' ).remove();
		},

		/**
		 * Show feature-specific message
		 */
		showFeatureMessage: function ( feature, requiredPlan ) {
			// Remove any existing feature message
			$( '.layoutberg-pricing-feature-message' ).remove();

			var planNames = {
				starter: 'Starter',
				professional: 'Professional',
				agency: 'Agency',
			};

			var planName = planNames[ requiredPlan ] || 'Professional';
			var message =
				'Upgrade to ' + planName + ' to unlock ' + feature + '.';

			var $message = $(
				'<div class="layoutberg-pricing-feature-message layoutberg-alert layoutberg-alert-info">'
			).html(
				'<span class="dashicons dashicons-info"></span> ' + message
			);

			$( '#layoutberg-pricing-modal .layoutberg-pricing-intro' ).after(
				$message
			);
		},

		/**
		 * Track upgrade button clicks
		 */
		trackUpgradeClick: function ( e ) {
			var $button = $( e.currentTarget );
			var plan = $button.data( 'plan' );

			// Track the click (for analytics if needed)
			if ( window.layoutbergAdmin && window.layoutbergAdmin.track ) {
				window.layoutbergAdmin.track( 'upgrade_clicked', {
					plan: plan,
				} );
			}

			// The button already has the correct href, so let it proceed
		},
	};

	// Initialize when document is ready
	$( document ).ready( function () {
		LayoutBergAdmin.init();
	} );

	// Handle window resize for responsive features
	$( window ).on( 'resize', function () {
		// Update responsive elements
		$( '.layoutberg-admin-page' ).toggleClass(
			'mobile',
			$( window ).width() < 768
		);
	} );
} )( jQuery );
