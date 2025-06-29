/**
 * LayoutBerg Document Panel Component
 * 
 * Adds a panel to the document sidebar for template saving
 * 
 * @package LayoutBerg
 * @since 1.0.0
 */

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { download } from '@wordpress/icons';

/**
 * LayoutBerg Document Panel
 */
const LayoutBergDocumentPanel = ({ onSaveAsTemplate }) => {
    return (
        <PluginDocumentSettingPanel
            name="layoutberg-template-panel"
            title={__('LayoutBerg Templates', 'layoutberg')}
            className="layoutberg-document-panel"
        >
            <p className="layoutberg-panel-description">
                {__('Save your current layout as a reusable template.', 'layoutberg')}
            </p>
            
            <Button 
                variant="secondary" 
                onClick={onSaveAsTemplate}
                icon={download}
                className="layoutberg-save-template-button"
                isBlock
            >
                {__('Save as Template', 'layoutberg')}
            </Button>
        </PluginDocumentSettingPanel>
    );
};

export default LayoutBergDocumentPanel;