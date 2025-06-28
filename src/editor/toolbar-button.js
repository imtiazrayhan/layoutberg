/**
 * LayoutBerg Toolbar Button Component
 * 
 * Provides a toolbar button for quick access to AI layout generation
 * 
 * @package LayoutBerg
 * @since 1.0.0
 */

import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import { Button, Toolbar, ToolbarItem } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { layout, plus } from '@wordpress/icons';

/**
 * LayoutBerg Toolbar Button
 */
const LayoutBergToolbarButton = ({ onGenerate }) => {
    const { hasSelectedBlocks } = useSelect((select) => {
        const selectedBlockIds = select(blockEditorStore).getSelectedBlockClientIds();
        return {
            hasSelectedBlocks: selectedBlockIds.length > 0
        };
    }, []);

    return (
        <ToolbarItem as={Fragment}>
            {(toolbarItemHTMLProps) => (
                <Button
                    {...toolbarItemHTMLProps}
                    icon={layout}
                    label={
                        hasSelectedBlocks 
                            ? __('Replace with AI Layout', 'layoutberg')
                            : __('Generate AI Layout', 'layoutberg')
                    }
                    onClick={onGenerate}
                    className="layoutberg-toolbar-button"
                    variant="primary"
                    size="small"
                >
                    {hasSelectedBlocks 
                        ? __('Replace with AI', 'layoutberg')
                        : __('AI Layout', 'layoutberg')
                    }
                </Button>
            )}
        </ToolbarItem>
    );
};

export default LayoutBergToolbarButton;