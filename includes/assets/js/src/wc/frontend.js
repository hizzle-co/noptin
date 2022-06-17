/**
 * External dependencies
 */
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import Block from './block';
import metadata from './block.json';

// Prepare env.
const { position } = getSetting('noptin_data');

metadata.parent = [position];

registerCheckoutBlock({
	metadata,
	component: Block,
});
