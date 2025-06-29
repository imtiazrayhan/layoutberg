/**
 * LayoutBerg Editor Sidebar Component
 * 
 * Provides sidebar panel for LayoutBerg controls and settings
 * 
 * @package LayoutBerg
 * @since 1.0.0
 */

import { __, sprintf } from '@wordpress/i18n';
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
const LayoutBergSidebar = ({ onGenerate, onSaveAsTemplate, settings, onSettingsChange }) => {
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

            {/* Save as Template Card */}
            <Card className="layoutberg-sidebar-card">
                <CardHeader>
                    <Flex>
                        <FlexItem>
                            <strong>{__('Templates', 'layoutberg')}</strong>
                        </FlexItem>
                    </Flex>
                </CardHeader>
                <CardBody>
                    <p className="layoutberg-sidebar-description">
                        {__('Save your current layout as a reusable template', 'layoutberg')}
                    </p>
                    
                    <Button 
                        variant="secondary" 
                        onClick={onSaveAsTemplate}
                        size="compact"
                        className="layoutberg-save-template-button"
                    >
                        {__('Save as Template', 'layoutberg')}
                    </Button>
                </CardBody>
            </Card>

            {/* Quick Settings Panel */}
            <Panel className="layoutberg-sidebar-panel">
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