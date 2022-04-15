/**
 * External dependencies
 */
import { useEffect, useState, RawHTML } from '@wordpress/element';
import { CheckboxControl } from '@woocommerce/blocks-checkout';
import { getSetting } from '@woocommerce/settings';

const { optinEnabled, defaultText, defaultStatus } = getSetting('noptin_data', '');

const Block = ({ checkoutExtensionData }) => {

    if ( ! optinEnabled ) {
        return null;
    }

    const [checked, setChecked] = useState(defaultStatus);
    const { setExtensionData } = checkoutExtensionData || {};

    useEffect(() => {
        setExtensionData('noptin', 'optin', checked);
    }, [checked, setExtensionData]);

    return (
        <CheckboxControl
            className="wc-block-components-noptin-newsletter-subscription"
            checked={checked}
            onChange={setChecked}
        >
            <RawHTML>{defaultText}</RawHTML>
        </CheckboxControl>
    );
};

export default Block;
