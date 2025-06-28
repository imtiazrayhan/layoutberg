/**
 * Admin JavaScript for LayoutBerg
 *
 * @package    LayoutBerg
 * @subpackage Admin/JS
 * @since      1.0.0
 */

(function($) {
    'use strict';

    // LayoutBerg Admin Object
    window.LayoutBergAdmin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Template actions
            $(document).on('click', '.layoutberg-template-use', this.useTemplate.bind(this));
            $(document).on('click', '.layoutberg-template-preview', this.previewTemplate.bind(this));
            $(document).on('click', '.layoutberg-template-delete', this.deleteTemplate.bind(this));
            
            // Modal close
            $(document).on('click', '.layoutberg-modal-close, .layoutberg-modal-cancel', this.closeModal.bind(this));
            
            // Generate layout
            $(document).on('click', '#layoutberg-generate', this.generateLayout.bind(this));
            
            // Save template
            $(document).on('click', '#layoutberg-save-template', this.saveTemplate.bind(this));
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            if ($.fn.tipTip) {
                $('.layoutberg-help-tip').tipTip({
                    'attribute': 'data-tip',
                    'fadeIn': 50,
                    'fadeOut': 50,
                    'delay': 200
                });
            }
        },

        /**
         * Show loading state
         */
        showLoading: function(element, text) {
            var $element = $(element);
            $element.addClass('updating-message').html('<span class="spinner is-active"></span> ' + text);
        },

        /**
         * Hide loading state
         */
        hideLoading: function(element, text) {
            var $element = $(element);
            $element.removeClass('updating-message').html(text);
        },

        /**
         * Show notice
         */
        showNotice: function(message, type) {
            type = type || 'success';
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.layoutberg-wrap').prepend($notice);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Make dismissible
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Use template
         */
        useTemplate: function(e) {
            e.preventDefault();
            var templateId = $(e.currentTarget).data('template-id');
            
            // TODO: Implement template usage
            this.showNotice(layoutbergAdmin.strings.generated, 'success');
        },

        /**
         * Preview template
         */
        previewTemplate: function(e) {
            e.preventDefault();
            var templateId = $(e.currentTarget).data('template-id');
            
            // TODO: Implement template preview
            this.openModal('template-preview', {
                templateId: templateId
            });
        },

        /**
         * Delete template
         */
        deleteTemplate: function(e) {
            e.preventDefault();
            
            if (!confirm(layoutbergAdmin.strings.confirmDelete)) {
                return;
            }
            
            var $button = $(e.currentTarget);
            var templateId = $button.data('template-id');
            
            this.showLoading($button, layoutbergAdmin.strings.deleting);
            
            $.ajax({
                url: layoutbergAdmin.apiUrl + '/templates/' + templateId,
                method: 'DELETE',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', layoutbergAdmin.restNonce);
                },
                success: function(response) {
                    $button.closest('.layoutberg-template-card').fadeOut(function() {
                        $(this).remove();
                    });
                    this.showNotice(layoutbergAdmin.strings.deleted, 'success');
                }.bind(this),
                error: function(xhr) {
                    this.hideLoading($button, layoutbergAdmin.strings.delete);
                    this.showNotice(layoutbergAdmin.strings.error, 'error');
                }.bind(this)
            });
        },

        /**
         * Generate layout
         */
        generateLayout: function(e) {
            e.preventDefault();
            
            var $button = $(e.currentTarget);
            var prompt = $('#layoutberg-prompt').val();
            var options = this.getGenerationOptions();
            
            if (!prompt) {
                this.showNotice(layoutbergAdmin.strings.promptRequired, 'error');
                return;
            }
            
            this.showLoading($button, layoutbergAdmin.strings.generating);
            
            $.ajax({
                url: layoutbergAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'layoutberg_generate',
                    nonce: layoutbergAdmin.nonce,
                    prompt: prompt,
                    options: JSON.stringify(options)
                },
                success: function(response) {
                    if (response.success) {
                        this.displayGeneratedLayout(response.data);
                        this.showNotice(layoutbergAdmin.strings.generated, 'success');
                    } else {
                        this.showNotice(response.data || layoutbergAdmin.strings.error, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotice(layoutbergAdmin.strings.error, 'error');
                }.bind(this),
                complete: function() {
                    this.hideLoading($button, layoutbergAdmin.strings.generate);
                }.bind(this)
            });
        },

        /**
         * Get generation options
         */
        getGenerationOptions: function() {
            return {
                style: $('#layoutberg-style').val(),
                colors: $('#layoutberg-colors').val(),
                layout: $('#layoutberg-layout').val(),
                density: $('#layoutberg-density').val()
            };
        },

        /**
         * Display generated layout
         */
        displayGeneratedLayout: function(data) {
            $('#layoutberg-preview').html(data.html);
            $('#layoutberg-blocks').val(data.raw);
            $('#layoutberg-result').show();
        },

        /**
         * Save template
         */
        saveTemplate: function(e) {
            e.preventDefault();
            
            var $button = $(e.currentTarget);
            var name = $('#layoutberg-template-name').val();
            var content = $('#layoutberg-blocks').val();
            var description = $('#layoutberg-template-description').val();
            var category = $('#layoutberg-template-category').val();
            
            if (!name || !content) {
                this.showNotice(layoutbergAdmin.strings.templateNameRequired, 'error');
                return;
            }
            
            this.showLoading($button, layoutbergAdmin.strings.saving);
            
            $.ajax({
                url: layoutbergAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'layoutberg_save_template',
                    nonce: layoutbergAdmin.nonce,
                    name: name,
                    content: content,
                    description: description,
                    category: category
                },
                success: function(response) {
                    if (response.success) {
                        this.showNotice(layoutbergAdmin.strings.saved, 'success');
                        this.closeModal();
                        // Reset form
                        $('#layoutberg-template-name').val('');
                        $('#layoutberg-template-description').val('');
                    } else {
                        this.showNotice(response.data || layoutbergAdmin.strings.error, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.showNotice(layoutbergAdmin.strings.error, 'error');
                }.bind(this),
                complete: function() {
                    this.hideLoading($button, layoutbergAdmin.strings.saveTemplate);
                }.bind(this)
            });
        },

        /**
         * Open modal
         */
        openModal: function(modalId, data) {
            var $modal = $('#layoutberg-modal-' + modalId);
            
            if (data) {
                // Populate modal with data
                $.each(data, function(key, value) {
                    $modal.find('[data-field="' + key + '"]').val(value);
                });
            }
            
            $modal.addClass('active');
            $('body').addClass('modal-open');
        },

        /**
         * Close modal
         */
        closeModal: function() {
            $('.layoutberg-modal').removeClass('active');
            $('body').removeClass('modal-open');
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        LayoutBergAdmin.init();
    });

})(jQuery);