/**
 * LayoutBerg Editor Sidebar Component
 * 
 * Provides sidebar panel for LayoutBerg controls and settings
 * 
 * @package LayoutBerg
 * @since 1.0.0
 */

import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { 
    Button, 
    Panel, 
    PanelBody, 
    PanelRow,
    SelectControl,
    RangeControl,
    ToggleControl,
    TextControl,
    Flex,
    FlexBlock,
    FlexItem,
    Card,
    CardBody,
    CardHeader
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { layout, help, info, cog } from '@wordpress/icons';

/**
 * LayoutBerg Sidebar Component
 */
const LayoutBergSidebar = ({ onGenerate, settings, onSettingsChange }) => {
    const { selectedBlocks, hasSelectedBlocks, totalBlocks } = useSelect((select) => {
        const selectedBlockIds = select(blockEditorStore).getSelectedBlockClientIds();
        const allBlocks = select(blockEditorStore).getBlocks();
        return {
            selectedBlocks: selectedBlockIds,
            hasSelectedBlocks: selectedBlockIds.length > 0,
            totalBlocks: allBlocks.length
        };
    }, []);

    const updateSetting = (key, value) => {
        onSettingsChange({
            ...settings,
            [key]: value
        });
    };

    return (
        <Fragment>
            {/* Quick Action Card */}
            <Card className="layoutberg-sidebar-card">
                <CardHeader>
                    <Flex>
                        <FlexItem>
                            <strong>{__('AI Layout Generator', 'layoutberg')}</strong>
                        </FlexItem>
                        <FlexItem>
                            {layout}
                        </FlexItem>
                    </Flex>
                </CardHeader>
                <CardBody>
                    <p className="layoutberg-sidebar-description">
                        {hasSelectedBlocks 
                            ? __('Replace selected blocks with AI-generated layout', 'layoutberg')
                            : __('Generate a new layout with AI assistance', 'layoutberg')
                        }
                    </p>
                    
                    <Button 
                        variant="primary" 
                        onClick={onGenerate}
                        icon={layout}
                        size="compact"
                        className="layoutberg-generate-button"
                    >
                        {hasSelectedBlocks 
                            ? __('Replace with AI Layout', 'layoutberg')
                            : __('Generate AI Layout', 'layoutberg')
                        }
                    </Button>

                    {hasSelectedBlocks && (
                        <p className="layoutberg-sidebar-note">
                            {selectedBlocks.length === 1 
                                ? __('1 block selected', 'layoutberg')
                                : sprintf(__('%d blocks selected', 'layoutberg'), selectedBlocks.length)
                            }
                        </p>
                    )}
                </CardBody>
            </Card>

            {/* Quick Settings Panel */}
            <Panel className="layoutberg-sidebar-panel">
                <PanelBody 
                    title={__('Generation Settings', 'layoutberg')} 
                    icon={cog}
                    initialOpen={false}
                >
                    <PanelRow>
                        <SelectControl
                            label={__('AI Model', 'layoutberg')}
                            value={settings.model}
                            options={[
                                { label: __('GPT-3.5 Turbo (Fast)', 'layoutberg'), value: 'gpt-3.5-turbo' },
                                { label: __('GPT-4 (Pro)', 'layoutberg'), value: 'gpt-4' },
                                { label: __('GPT-4 Turbo (Pro)', 'layoutberg'), value: 'gpt-4-turbo' }
                            ]}
                            onChange={(value) => updateSetting('model', value)}
                            help={__('Choose the AI model for generation', 'layoutberg')}
                        />
                    </PanelRow>

                    <PanelRow>
                        <RangeControl
                            label={__('Creativity Level', 'layoutberg')}
                            value={settings.temperature}
                            onChange={(value) => updateSetting('temperature', value)}
                            min={0}
                            max={2}
                            step={0.1}
                            marks={[
                                { value: 0, label: __('Focused', 'layoutberg') },
                                { value: 1, label: __('Balanced', 'layoutberg') },
                                { value: 2, label: __('Creative', 'layoutberg') }
                            ]}
                            help={__('Lower = more focused, Higher = more creative', 'layoutberg')}
                        />
                    </PanelRow>

                    <PanelRow>
                        <SelectControl
                            label={__('Design Style', 'layoutberg')}
                            value={settings.style}
                            options={[
                                { label: __('Modern', 'layoutberg'), value: 'modern' },
                                { label: __('Classic', 'layoutberg'), value: 'classic' },
                                { label: __('Minimal', 'layoutberg'), value: 'minimal' },
                                { label: __('Bold', 'layoutberg'), value: 'bold' }
                            ]}
                            onChange={(value) => updateSetting('style', value)}
                        />
                    </PanelRow>

                    <PanelRow>
                        <SelectControl
                            label={__('Layout Structure', 'layoutberg')}
                            value={settings.layout}
                            options={[
                                { label: __('Single Column', 'layoutberg'), value: 'single-column' },
                                { label: __('With Sidebar', 'layoutberg'), value: 'sidebar' },
                                { label: __('Grid Layout', 'layoutberg'), value: 'grid' },
                                { label: __('Asymmetric', 'layoutberg'), value: 'asymmetric' }
                            ]}
                            onChange={(value) => updateSetting('layout', value)}
                        />
                    </PanelRow>
                </PanelBody>

                {/* Tips Panel */}
                <PanelBody 
                    title={__('Tips & Tricks', 'layoutberg')} 
                    icon={help}
                    initialOpen={false}
                >
                    <div className="layoutberg-tips">
                        <div className="layoutberg-tip">
                            <strong>{__('Keyboard Shortcut:', 'layoutberg')}</strong>
                            <p>{__('Press Ctrl+Shift+L (or Cmd+Shift+L on Mac) to quickly open the generation modal.', 'layoutberg')}</p>
                        </div>
                        
                        <div className="layoutberg-tip">
                            <strong>{__('Better Prompts:', 'layoutberg')}</strong>
                            <p>{__('Be specific about your needs: "Create a hero section for a tech startup with a call-to-action button"', 'layoutberg')}</p>
                        </div>
                        
                        <div className="layoutberg-tip">
                            <strong>{__('Replace vs Insert:', 'layoutberg')}</strong>
                            <p>{__('Select blocks to replace them, or place cursor where you want to insert new content.', 'layoutberg')}</p>
                        </div>
                    </div>
                </PanelBody>

                {/* Status Panel */}
                <PanelBody 
                    title={__('Document Status', 'layoutberg')} 
                    icon={info}
                    initialOpen={false}
                >
                    <PanelRow>
                        <Flex direction="column" gap={2}>
                            <FlexItem>
                                <strong>{__('Total Blocks:', 'layoutberg')} </strong>
                                {totalBlocks}
                            </FlexItem>
                            <FlexItem>
                                <strong>{__('Selected:', 'layoutberg')} </strong>
                                {hasSelectedBlocks ? selectedBlocks.length : 0}
                            </FlexItem>
                            <FlexItem>
                                <strong>{__('Next Action:', 'layoutberg')} </strong>
                                {hasSelectedBlocks 
                                    ? __('Will replace selected blocks', 'layoutberg')
                                    : __('Will insert at current position', 'layoutberg')
                                }
                            </FlexItem>
                        </Flex>
                    </PanelRow>
                </PanelBody>
            </Panel>
        </Fragment>
    );
};

export default LayoutBergSidebar;