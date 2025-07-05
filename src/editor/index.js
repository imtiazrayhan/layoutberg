/**
 * LayoutBerg Editor Integration
 * 
 * Provides Gutenberg editor integration including:
 * - Toolbar button for AI layout generation
 * - Keyboard shortcuts
 * - Generation modal
 * 
 * @package LayoutBerg
 * @since 1.0.0
 */

// Initialize toolbar button immediately
import './toolbar-button-init';

import { __, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { Fragment } from '@wordpress/element';
import { 
    Button, 
    Modal, 
    TextareaControl,
    SelectControl,
    RangeControl,
    ToggleControl,
    Notice,
    Spinner,
    Panel,
    PanelBody,
    PanelRow
} from '@wordpress/components';
import { layout } from '@wordpress/icons';
import { useState, useEffect, useReducer } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { serialize } from '@wordpress/blocks';
// Note: We use wp.blocks.parse() instead of importing parse to match Pattern Pal's approach
import apiFetch from '@wordpress/api-fetch';

// Import state management
import { 
    generationReducer, 
    initialGenerationState, 
    GENERATION_ACTIONS,
    getStateDescription,
    isLoadingState
} from './state/generationReducer';

// Import styles
import './editor.css';

// Import our components
import LayoutBergModal from './modal';
import SaveTemplateModal from './save-template-modal';
import LayoutBergDocumentPanel from './document-panel';

/**
 * Main LayoutBerg Editor Plugin
 */
const LayoutBergEditor = () => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isSaveTemplateModalOpen, setIsSaveTemplateModalOpen] = useState(false);
    const [prompt, setPrompt] = useState('');
    const [settings, setSettings] = useState({
        model: window.layoutbergEditor?.settings?.model || 'gpt-3.5-turbo',
        temperature: window.layoutbergEditor?.settings?.temperature || 0.7,
        maxTokens: window.layoutbergEditor?.settings?.maxTokens || 2000
    });

    // Use reducer for generation state management
    const [generationState, dispatch] = useReducer(generationReducer, initialGenerationState);

    // Use direct dispatch like Pattern Pal instead of hooks
    const { createNotice } = useDispatch('core/notices');
    
    const { selectedBlocks, hasSelectedBlocks, allBlocks } = useSelect((select) => {
        const selectedBlockIds = select(blockEditorStore).getSelectedBlockClientIds();
        const blocks = select(blockEditorStore).getBlocks();
        return {
            selectedBlocks: selectedBlockIds,
            hasSelectedBlocks: selectedBlockIds.length > 0,
            allBlocks: blocks
        };
    }, []);

    /**
     * Open the generation modal
     */
    const openModal = () => {
        setIsModalOpen(true);
        setGenerationError(null);
    };

    // Make openModal available globally for toolbar integration
    window.layoutbergOpenModal = openModal;

    /**
     * Close the generation modal
     */
    const closeModal = () => {
        setIsModalOpen(false);
        setPrompt('');
        dispatch({ type: GENERATION_ACTIONS.RESET });
    };

    /**
     * Handle layout generation
     */
    const handleGenerate = async () => {
        if (!prompt.trim()) {
            dispatch({ 
                type: GENERATION_ACTIONS.ERROR, 
                payload: __('Please enter a prompt to generate a layout.', 'layoutberg') 
            });
            return;
        }

        dispatch({ type: GENERATION_ACTIONS.START });

        try {
            // Small delay for UI feedback
            await new Promise(resolve => setTimeout(resolve, 300));
            
            dispatch({ type: GENERATION_ACTIONS.UPDATE_STATE, payload: 'sending' });
            
            const response = await apiFetch({
                path: '/layoutberg/v1/generate',
                method: 'POST',
                data: {
                    prompt: prompt.trim(),
                    settings: settings,
                    replace_selected: hasSelectedBlocks
                }
            });

            dispatch({ type: GENERATION_ACTIONS.UPDATE_STATE, payload: 'processing' });

            if (response.success && response.data && response.data.blocks) {
                // Store the generated blocks for potential template saving
                setLastGeneratedBlocks(response.data.blocks);
                
                // Store the response for displaying prompts
                setLastResponse(response.data);
                
                // Parse blocks using wp.blocks.parse exactly like Pattern Pal
                const parsedBlocks = wp.blocks.parse(response.data.blocks);
                
                // Debug logging to verify parsing
                if (window.layoutbergDebug) {
                    console.log('LayoutBerg: Raw blocks:', response.data.blocks);
                    console.log('LayoutBerg: Parsed blocks:', parsedBlocks);
                }
                
                // Use Pattern Pal's dispatch approach instead of hooks
                const { removeBlocks, insertBlocks: insertBlocksAction } = wp.data.dispatch('core/block-editor');
                
                if (parsedBlocks && parsedBlocks.length > 0) {
                    if (hasSelectedBlocks) {
                        // Pattern Pal's approach: remove then insert
                        removeBlocks(selectedBlocks);
                        insertBlocksAction(parsedBlocks);
                        createNotice(
                            'success',
                            __('Layout generated and replaced selected blocks!', 'layoutberg'),
                            { 
                                type: 'snackbar', 
                                isDismissible: true,
                                actions: [{
                                    label: __('Save as Template', 'layoutberg'),
                                    onClick: () => setIsSaveTemplateModalOpen(true)
                                }]
                            }
                        );
                    } else {
                        // Insert at the end using Pattern Pal's approach
                        insertBlocksAction(parsedBlocks);
                        createNotice(
                            'success',
                            __('Layout generated and inserted!', 'layoutberg'),
                            { 
                                type: 'snackbar', 
                                isDismissible: true,
                                actions: [{
                                    label: __('Save as Template', 'layoutberg'),
                                    onClick: () => setIsSaveTemplateModalOpen(true)
                                }]
                            }
                        );
                    }
                    
                    dispatch({ 
                        type: GENERATION_ACTIONS.SUCCESS, 
                        payload: { 
                            response: response.data, 
                            blocks: parsedBlocks 
                        } 
                    });
                    
                    // Small delay to show completion
                    await new Promise(resolve => setTimeout(resolve, 500));
                    
                    closeModal();
                } else {
                    dispatch({ 
                        type: GENERATION_ACTIONS.ERROR, 
                        payload: __('No valid blocks found in the generated layout.', 'layoutberg') 
                    });
                }
            } else {
                dispatch({ 
                    type: GENERATION_ACTIONS.ERROR, 
                    payload: response.data?.message || __('Failed to generate layout. Please try again.', 'layoutberg') 
                });
            }
        } catch (error) {
            console.error('LayoutBerg generation error:', error);
            dispatch({ 
                type: GENERATION_ACTIONS.ERROR, 
                payload: error.message || __('An error occurred while generating the layout.', 'layoutberg') 
            });
        }
    };


    /**
     * Handle template save
     */
    const handleTemplateSave = (template) => {
        createNotice(
            'success',
            sprintf(
                __('Template "%s" saved successfully!', 'layoutberg'),
                template.name
            ),
            { 
                type: 'snackbar', 
                isDismissible: true,
                actions: [{
                    label: __('View Templates', 'layoutberg'),
                    url: layoutbergEditor.templatesUrl || '/wp-admin/admin.php?page=layoutberg-templates'
                }]
            }
        );
        setIsSaveTemplateModalOpen(false);
    };

    /**
     * Handle save as template from sidebar
     */
    const handleSaveAsTemplate = () => {
        // Get the current editor blocks
        const editorBlocks = wp.data.select('core/block-editor').getBlocks();
        const serializedBlocks = serialize(editorBlocks);
        
        // Open the save template modal with current blocks
        setIsSaveTemplateModalOpen(true);
        
        // Store blocks in a temporary variable for the modal
        window.layoutbergCurrentBlocks = serializedBlocks;
    };

    /**
     * Handle generation cancel
     */
    const handleCancelGeneration = () => {
        dispatch({ 
            type: GENERATION_ACTIONS.ERROR, 
            payload: __('Generation cancelled by user.', 'layoutberg') 
        });
    };



    /**
     * Handle keyboard shortcuts
     */
    useEffect(() => {
        const handleKeyDown = (event) => {
            // Ctrl+Shift+L or Cmd+Shift+L
            if ((event.ctrlKey || event.metaKey) && event.shiftKey && (event.key === 'L' || event.key === 'l')) {
                event.preventDefault();
                openModal();
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, []);
    
    /**
     * Prevent pattern modal from opening if requested
     */
    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('hide_pattern_modal') === '1') {
            // Override the pattern modal behavior
            const { dispatch } = wp.data;
            if (dispatch('core/edit-post')) {
                // Close the pattern modal if it's open
                dispatch('core/edit-post').closeGeneralSidebar();
                
                // Prevent it from auto-opening
                const unsubscribe = wp.data.subscribe(() => {
                    const isModalOpen = wp.data.select('core/edit-post')?.isModalActive('core/edit-post/start-page-options');
                    if (isModalOpen) {
                        dispatch('core/edit-post').closeModal();
                    }
                });
                
                // Clean up after a few seconds
                setTimeout(() => unsubscribe(), 5000);
            }
        }
    }, []);

    /**
     * Load template if specified in URL or open modal if requested
     */
    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const templateId = urlParams.get('layoutberg_template');
        const openModal = urlParams.get('layoutberg_open_modal');
        const hidePatternModal = urlParams.get('hide_pattern_modal');
        const prefilledPrompt = urlParams.get('layoutberg_prompt');
        
        // Prevent WordPress pattern modal from opening if requested
        if (hidePatternModal === '1') {
            // Close any open modals first
            const closeExistingModals = () => {
                const modalCloseButtons = document.querySelectorAll('.components-modal__header button[aria-label="Close"]');
                modalCloseButtons.forEach(button => button.click());
            };
            
            // Try to close immediately and after a delay
            closeExistingModals();
            setTimeout(closeExistingModals, 100);
            setTimeout(closeExistingModals, 500);
            
            // Remove the parameter
            urlParams.delete('hide_pattern_modal');
        }
        
        // Check if we should open the modal
        if (openModal === '1') {
            // Set pre-filled prompt if provided
            if (prefilledPrompt) {
                setPrompt(decodeURIComponent(prefilledPrompt));
                urlParams.delete('layoutberg_prompt');
            }
            
            // Small delay to ensure editor is fully loaded
            setTimeout(() => {
                setIsModalOpen(true);
            }, 1000);
            
            // Remove the parameter from URL to prevent reopening on refresh
            urlParams.delete('layoutberg_open_modal');
            const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
            window.history.replaceState({}, '', newUrl);
        }
        
        if (templateId) {
            // Load and insert the template
            apiFetch({
                path: `/layoutberg/v1/templates/${templateId}`,
                method: 'GET'
            }).then(response => {
                if (response && response.content) {
                    // Use global wp.blocks.parse and direct dispatch like Pattern Pal
                    const parsedBlocks = wp.blocks.parse(response.content);
                    if (parsedBlocks && parsedBlocks.length > 0) {
                        const { insertBlocks: insertBlocksAction } = wp.data.dispatch('core/block-editor');
                        insertBlocksAction(parsedBlocks);
                        createNotice(
                            'success',
                            sprintf(
                                __('Template "%s" loaded successfully!', 'layoutberg'),
                                response.name
                            ),
                            { type: 'snackbar', isDismissible: true }
                        );
                    }
                }
            }).catch(error => {
                // Fallback to AJAX
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'GET',
                    data: {
                        action: 'layoutberg_get_template',
                        template_id: templateId,
                        _wpnonce: layoutbergEditor.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data && response.data.content) {
                            const parsedBlocks = parse(response.data.content);
                            if (parsedBlocks.length > 0) {
                                insertBlocks(parsedBlocks);
                                createNotice(
                                    'success',
                                    sprintf(
                                        __('Template "%s" loaded successfully!', 'layoutberg'),
                                        response.data.name
                                    ),
                                    { type: 'snackbar', isDismissible: true }
                                );
                            }
                        } else {
                            createNotice(
                                'error',
                                __('Failed to load template.', 'layoutberg'),
                                { type: 'snackbar', isDismissible: true }
                            );
                        }
                    }
                });
            });
            
            // Remove the parameter from URL to prevent reloading on refresh
            urlParams.delete('layoutberg_template');
            const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
            window.history.replaceState({}, '', newUrl);
        }
    }, []);

    return (
        <Fragment>
            {/* Document Panel */}
            <LayoutBergDocumentPanel
                onSaveAsTemplate={handleSaveAsTemplate}
            />

            {/* Generation Modal */}
            {isModalOpen && (
                <LayoutBergModal
                    isOpen={isModalOpen}
                    onClose={closeModal}
                    onGenerate={handleGenerate}
                    isGenerating={generationState.isGenerating}
                    error={generationState.error}
                    prompt={prompt}
                    onPromptChange={setPrompt}
                    settings={settings}
                    onSettingsChange={setSettings}
                    hasSelectedBlocks={hasSelectedBlocks}
                    lastResponse={generationState.lastResponse}
                    generationState={generationState.state}
                    onCancel={handleCancelGeneration}
                />
            )}

            {/* Save Template Modal */}
            {isSaveTemplateModalOpen && (
                <SaveTemplateModal
                    isOpen={isSaveTemplateModalOpen}
                    onClose={() => setIsSaveTemplateModalOpen(false)}
                    onSave={handleTemplateSave}
                    blocks={window.layoutbergCurrentBlocks || generationState.lastGeneratedBlocks}
                    prompt={prompt}
                />
            )}
        </Fragment>
    );
};

// Register the plugin
registerPlugin('layoutberg-editor', {
    render: LayoutBergEditor,
    icon: 'layout',
    title: __('LayoutBerg', 'layoutberg')
});

console.log('LayoutBerg editor integration loaded');