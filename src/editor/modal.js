/**
 * LayoutBerg Generation Modal Component
 * 
 * Provides the main interface for AI layout generation
 * 
 * @package LayoutBerg
 * @since 1.0.0
 */

import { __, sprintf } from '@wordpress/i18n';
import { Fragment, useState, useEffect } from '@wordpress/element';
import { 
    Modal,
    Button,
    TextareaControl,
    SelectControl,
    RangeControl,
    ToggleControl,
    Notice,
    Spinner,
    Flex,
    FlexItem,
    FlexBlock,
    Card,
    CardBody,
    CardHeader,
    CardDivider,
    __experimentalGrid as Grid,
    __experimentalVStack as VStack,
    __experimentalHStack as HStack
} from '@wordpress/components';
import { layout, starFilled, cog } from '@wordpress/icons';

/**
 * LayoutBerg Generation Modal
 */
const LayoutBergModal = ({ 
    isOpen,
    onClose, 
    onGenerate, 
    isGenerating, 
    error, 
    prompt, 
    onPromptChange, 
    settings, 
    onSettingsChange,
    hasSelectedBlocks
}) => {
    const [showAdvanced, setShowAdvanced] = useState(false);
    
    // All models have the same completion token limit
    const maxTokensLimit = 4096;

    const updateSetting = (key, value) => {
        onSettingsChange({
            ...settings,
            [key]: value
        });
    };


    const quickPrompts = [
        {
            label: __('Hero Section', 'layoutberg'),
            prompt: __('Create a modern hero section with headline, description, and call-to-action button', 'layoutberg')
        },
        {
            label: __('Feature Grid', 'layoutberg'),
            prompt: __('Create a 3-column grid showcasing product features with icons and descriptions', 'layoutberg')
        },
        {
            label: __('About Section', 'layoutberg'),
            prompt: __('Create an about us section with team member profiles and company description', 'layoutberg')
        },
        {
            label: __('Contact Form', 'layoutberg'),
            prompt: __('Create a contact section with form fields and contact information', 'layoutberg')
        },
        {
            label: __('Blog Layout', 'layoutberg'),
            prompt: __('Create a blog post layout with featured image, title, content, and sidebar', 'layoutberg')
        },
        {
            label: __('Portfolio Grid', 'layoutberg'),
            prompt: __('Create a portfolio grid showcasing projects with images and descriptions', 'layoutberg')
        }
    ];

    if (!isOpen) return null;

    return (
        <Modal
            title={
                <HStack>
                    <FlexItem>{starFilled}</FlexItem>
                    <FlexBlock>
                        {__('Generate AI Layout', 'layoutberg')}
                    </FlexBlock>
                    {hasSelectedBlocks && (
                        <FlexItem>
                            <span className="layoutberg-modal-badge">
                                {__('Replace Mode', 'layoutberg')}
                            </span>
                        </FlexItem>
                    )}
                </HStack>
            }
            onRequestClose={onClose}
            className="layoutberg-generation-modal"
            size="large"
        >
            <VStack spacing={4}>
                {/* Error Notice */}
                {error && (
                    <Notice 
                        status="error" 
                        isDismissible={false}
                        className="layoutberg-modal-error"
                    >
                        {error}
                    </Notice>
                )}

                {/* Selected Blocks Info */}
                {hasSelectedBlocks && (
                    <Notice 
                        status="info" 
                        isDismissible={false}
                        className="layoutberg-modal-info"
                    >
                        {__('The generated layout will replace your selected blocks.', 'layoutberg')}
                    </Notice>
                )}

                {/* Quick Prompts */}
                <Card>
                    <CardHeader>
                        <strong>{__('Quick Start Templates', 'layoutberg')}</strong>
                    </CardHeader>
                    <CardBody>
                        <Grid columns={3} gap={2} className="layoutberg-quick-prompts">
                            {quickPrompts.map((quickPrompt, index) => (
                                <Button
                                    key={index}
                                    variant="secondary"
                                    size="small"
                                    onClick={() => onPromptChange(quickPrompt.prompt)}
                                    className="layoutberg-quick-prompt-button"
                                >
                                    {quickPrompt.label}
                                </Button>
                            ))}
                        </Grid>
                    </CardBody>
                </Card>

                {/* Main Prompt Input */}
                <Card>
                    <CardHeader>
                        <strong>{__('Describe Your Layout', 'layoutberg')}</strong>
                    </CardHeader>
                    <CardBody>
                        <TextareaControl
                            placeholder={__('Describe the layout you want to create...', 'layoutberg')}
                            value={prompt}
                            onChange={onPromptChange}
                            rows={4}
                            className="layoutberg-prompt-input"
                            help={__('Be specific about your needs: sections, content types, styling preferences, etc.', 'layoutberg')}
                        />
                    </CardBody>
                </Card>

                {/* Settings */}
                <Card>
                    <CardHeader>
                        <HStack>
                            <FlexBlock>
                                <strong>{__('Generation Settings', 'layoutberg')}</strong>
                            </FlexBlock>
                            <FlexItem>
                                <Button
                                    variant="tertiary"
                                    size="small"
                                    onClick={() => setShowAdvanced(!showAdvanced)}
                                    icon={cog}
                                >
                                    {showAdvanced ? __('Hide Advanced', 'layoutberg') : __('Show Advanced', 'layoutberg')}
                                </Button>
                            </FlexItem>
                        </HStack>
                    </CardHeader>
                    <CardBody>
                        <Grid columns={2} gap={4}>
                            <SelectControl
                                label={__('Design Style', 'layoutberg')}
                                value={settings.style}
                                options={[
                                    { label: __('Modern - Clean & Contemporary', 'layoutberg'), value: 'modern' },
                                    { label: __('Classic - Timeless & Professional', 'layoutberg'), value: 'classic' },
                                    { label: __('Minimal - Simple & Focused', 'layoutberg'), value: 'minimal' },
                                    { label: __('Bold - Dynamic & Impactful', 'layoutberg'), value: 'bold' }
                                ]}
                                onChange={(value) => updateSetting('style', value)}
                            />

                            <SelectControl
                                label={__('Layout Structure', 'layoutberg')}
                                value={settings.layout}
                                options={[
                                    { label: __('Single Column - Full Width', 'layoutberg'), value: 'single-column' },
                                    { label: __('With Sidebar - Traditional', 'layoutberg'), value: 'sidebar' },
                                    { label: __('Grid Layout - Multi-Column', 'layoutberg'), value: 'grid' },
                                    { label: __('Asymmetric - Creative', 'layoutberg'), value: 'asymmetric' }
                                ]}
                                onChange={(value) => updateSetting('layout', value)}
                            />
                        </Grid>

                        {showAdvanced && (
                            <Fragment>
                                <CardDivider />
                                <VStack spacing={3}>
                                    <SelectControl
                                        label={__('AI Model', 'layoutberg')}
                                        value={settings.model}
                                        options={[
                                            { label: __('GPT-3.5 Turbo (Fast & Affordable)', 'layoutberg'), value: 'gpt-3.5-turbo' },
                                            { label: __('GPT-4 (Most Capable)', 'layoutberg'), value: 'gpt-4' },
                                            { label: __('GPT-4 Turbo (Fast & Capable)', 'layoutberg'), value: 'gpt-4-turbo' }
                                        ]}
                                        onChange={(value) => updateSetting('model', value)}
                                        help={__('Choose the AI model for generation', 'layoutberg')}
                                    />

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
                                        help={__('Lower values produce more focused results, higher values are more creative', 'layoutberg')}
                                    />

                                    <RangeControl
                                        label={__('Max Tokens', 'layoutberg')}
                                        value={settings.maxTokens}
                                        onChange={(value) => updateSetting('maxTokens', value)}
                                        min={500}
                                        max={maxTokensLimit}
                                        step={100}
                                        help={__('Higher values allow more complex layouts but cost more. All models support up to 4096 completion tokens.', 'layoutberg')}
                                    />
                                </VStack>
                            </Fragment>
                        )}
                    </CardBody>
                </Card>

                {/* Action Buttons */}
                <HStack justify="flex-end" spacing={3}>
                    <Button
                        variant="tertiary"
                        onClick={onClose}
                        disabled={isGenerating}
                    >
                        {__('Cancel', 'layoutberg')}
                    </Button>
                    
                    <Button
                        variant="primary"
                        onClick={onGenerate}
                        disabled={isGenerating || !prompt.trim()}
                        icon={isGenerating ? undefined : starFilled}
                    >
                        {isGenerating ? (
                            <Fragment>
                                <Spinner />
                                {__('Generating...', 'layoutberg')}
                            </Fragment>
                        ) : (
                            hasSelectedBlocks 
                                ? __('Replace with AI Layout', 'layoutberg')
                                : __('Generate AI Layout', 'layoutberg')
                        )}
                    </Button>
                </HStack>
            </VStack>

            <style jsx>{`
                .layoutberg-generation-modal {
                    width: 100%;
                    max-width: 800px;
                }
                
                .layoutberg-modal-badge {
                    background: #007cba;
                    color: white;
                    padding: 2px 8px;
                    border-radius: 4px;
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                }
                
                .layoutberg-quick-prompts .layoutberg-quick-prompt-button {
                    width: 100%;
                    height: auto;
                    white-space: normal;
                    text-align: center;
                    padding: 8px;
                    min-height: 44px;
                }
                
                .layoutberg-prompt-input textarea {
                    min-height: 100px;
                    resize: vertical;
                }
                
                .layoutberg-modal-error,
                .layoutberg-modal-info {
                    margin: 0;
                }
            `}</style>
        </Modal>
    );
};

export default LayoutBergModal;