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

import { __ } from '@wordpress/i18n';
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

/**
 * Main LayoutBerg Editor Plugin
 */
const LayoutBergEditor = () => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isGenerating, setIsGenerating] = useState(false);
    const [generationError, setGenerationError] = useState(null);
    const [prompt, setPrompt] = useState('');
    const [settings, setSettings] = useState({
        model: 'gpt-3.5-turbo',
        temperature: 0.7,
        maxTokens: 2000,
        style: 'modern',
        layout: 'single-column'
    });

    const { insertBlocks, replaceBlocks } = useDispatch(blockEditorStore);
    const { createNotice } = useDispatch('core/notices');
    
    const { selectedBlocks, hasSelectedBlocks } = useSelect((select) => {
        const selectedBlockIds = select(blockEditorStore).getSelectedBlockClientIds();
        return {
            selectedBlocks: selectedBlockIds,
            hasSelectedBlocks: selectedBlockIds.length > 0
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

            if (response.success && response.data.blocks) {
                // Parse the generated blocks
                const parsedBlocks = parse(response.data.blocks);
                
                if (parsedBlocks.length > 0) {
                    if (hasSelectedBlocks) {
                        // Replace selected blocks
                        replaceBlocks(selectedBlocks, parsedBlocks);
                        createNotice(
                            'success',
                            __('Layout generated and replaced selected blocks!', 'layoutberg'),
                            { type: 'snackbar', isDismissible: true }
                        );
                    } else {
                        // Insert at the end
                        insertBlocks(parsedBlocks);
                        createNotice(
                            'success',
                            __('Layout generated and inserted!', 'layoutberg'),
                            { type: 'snackbar', isDismissible: true }
                        );
                    }
                    
                    closeModal();
                } else {
                    setGenerationError(__('No valid blocks were generated. Please try a different prompt.', 'layoutberg'));
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