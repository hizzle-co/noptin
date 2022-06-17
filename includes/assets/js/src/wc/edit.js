/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder, Button } from '@wordpress/components';
import { getSetting } from '@woocommerce/settings';

// Prepare env.
const { adminUrl } = getSetting('noptin_data');

export const Edit = () => {
    const blockProps = useBlockProps();

    return (
        <div {...blockProps}>
            <Placeholder
                label={ __( 'Noptin Newsletter', 'newsletter-optin-box' ) }
                className="wp-block-noptin-newsletter-block-placeholder"
            >
                <span className="wp-block-noptin-newsletter-block-placeholder__description" style={{ display: 'block', margin: '0 0 1em', } }>
                    { __( 'If the Noptin newsletter subscription checkbox is enabled, it will appear here.', 'newsletter-optin-box' ) }
                </span>
                <Button
                    isPrimary
                    href={`${adminUrl}admin.php?page=noptin-settings&tab=integrations&section=woocommerce#noptin-settings-section-settings_section_woocommerce`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="wp-block-mailpoet-newsletter-block-placeholder__button"
                >
                    { __( 'Enable/Disable', 'newsletter-optin-box' ) }
                </Button>
            </Placeholder>
        </div>
    );
};

export const Save = () =>  null;