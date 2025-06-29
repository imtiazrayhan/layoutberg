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

import { __, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel, PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post';
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
import { useState, useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { parse, serialize } from '@wordpress/blocks';
import apiFetch from '@wordpress/api-fetch';

// Import styles
import './editor.css';

// Import our toolbar button component
import LayoutBergToolbarButton from './toolbar-button';
import LayoutBergSidebar from './sidebar';
import LayoutBergModal from './modal';
import SaveTemplateModal from './save-template-modal';

/**
 * Main LayoutBerg Editor Plugin
 */
const LayoutBergEditor = () => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isSaveTemplateModalOpen, setIsSaveTemplateModalOpen] = useState(false);
    const [isGenerating, setIsGenerating] = useState(false);
    const [generationError, setGenerationError] = useState(null);
    const [prompt, setPrompt] = useState('');
    const [lastGeneratedBlocks, setLastGeneratedBlocks] = useState('');
    const [lastResponse, setLastResponse] = useState(null);
    const [settings, setSettings] = useState({
        model: window.layoutbergEditor?.settings?.model || 'gpt-3.5-turbo',
        temperature: window.layoutbergEditor?.settings?.temperature || 0.7,
        maxTokens: window.layoutbergEditor?.settings?.maxTokens || 2000
    });

    const { insertBlocks, replaceBlocks } = useDispatch(blockEditorStore);
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

    /**
     * Close the generation modal
     */
    const closeModal = () => {
        setIsModalOpen(false);
        setPrompt('');
        setGenerationError(null);
    };

    /**
     * Handle layout generation
     */
    const handleGenerate = async () => {
        if (!prompt.trim()) {
            setGenerationError(__('Please enter a prompt to generate a layout.', 'layoutberg'));
            return;
        }

        setIsGenerating(true);
        setGenerationError(null);

        try {
            const response = await apiFetch({
                path: '/layoutberg/v1/generate',
                method: 'POST',
                data: {
                    prompt: prompt.trim(),
                    settings: settings,
                    replace_selected: hasSelectedBlocks
                }
            });

            if (response.success && response.data && response.data.blocks) {
                // Store the generated blocks for potential template saving
                setLastGeneratedBlocks(response.data.blocks);
                
                // Store the response for displaying prompts
                setLastResponse(response.data);
                
                // Parse and insert the generated blocks immediately
                const parsedBlocks = parse(response.data.blocks);
                
                if (parsedBlocks.length > 0) {
                    if (hasSelectedBlocks) {
                        // Replace selected blocks
                        replaceBlocks(selectedBlocks, parsedBlocks);
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
                        // Insert at the end
                        insertBlocks(parsedBlocks);
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
                    
                    closeModal();
                } else {
                    setGenerationError(__('No valid blocks found in the generated layout.', 'layoutberg'));
                }
            } else {
                setGenerationError(response.data?.message || __('Failed to generate layout. Please try again.', 'layoutberg'));
            }
        } catch (error) {
            console.error('LayoutBerg generation error:', error);
            setGenerationError(error.message || __('An error occurred while generating the layout.', 'layoutberg'));
        } finally {
            setIsGenerating(false);
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
     * Handle save current post as template
     */
    const handleSaveCurrentAsTemplate = () => {
        if (allBlocks.length === 0) {
            createNotice(
                'warning',
                __('No content to save as template. Please add some blocks first.', 'layoutberg'),
                { type: 'snackbar', isDismissible: true }
            );
            return;
        }

        // Serialize all blocks
        const serializedBlocks = serialize(allBlocks);
        setLastGeneratedBlocks(serializedBlocks);
        setPrompt(__('Saved from current post', 'layoutberg'));
        setIsSaveTemplateModalOpen(true);
    };

    /**
     * Handle keyboard shortcuts
     */
    useEffect(() => {
        const handleKeyDown = (event) => {
            // Ctrl+Shift+L or Cmd+Shift+L
            if ((event.ctrlKey || event.metaKey) && event.shiftKey && event.key === 'L') {
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
                    const parsedBlocks = parse(response.content);
                    if (parsedBlocks.length > 0) {
                        insertBlocks(parsedBlocks);
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
            {/* Sidebar */}
            <PluginSidebarMoreMenuItem 
                target="layoutberg-sidebar"
                icon="layout"
            >
                {__('LayoutBerg', 'layoutberg')}
            </PluginSidebarMoreMenuItem>
            
            <PluginSidebar
                name="layoutberg-sidebar"
                title={__('LayoutBerg', 'layoutberg')}
                icon="layout"
            >
                <LayoutBergSidebar 
                    onGenerate={openModal}
                    onSaveAsTemplate={handleSaveCurrentAsTemplate}
                    settings={settings}
                    onSettingsChange={setSettings}
                />
            </PluginSidebar>

            {/* Generation Modal */}
            {isModalOpen && (
                <LayoutBergModal
                    isOpen={isModalOpen}
                    onClose={closeModal}
                    onGenerate={handleGenerate}
                    isGenerating={isGenerating}
                    error={generationError}
                    prompt={prompt}
                    onPromptChange={setPrompt}
                    settings={settings}
                    onSettingsChange={setSettings}
                    hasSelectedBlocks={hasSelectedBlocks}
                    lastResponse={lastResponse}
                />
            )}

            {/* Save Template Modal */}
            {isSaveTemplateModalOpen && (
                <SaveTemplateModal
                    isOpen={isSaveTemplateModalOpen}
                    onClose={() => setIsSaveTemplateModalOpen(false)}
                    onSave={handleTemplateSave}
                    blocks={lastGeneratedBlocks}
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