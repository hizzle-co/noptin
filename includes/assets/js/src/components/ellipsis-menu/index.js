/**
 * External dependencies
 */
import { DropdownMenu } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Displays a menu item with an ellipsis icon that opens a dropdown menu.
 */
const EllipsisMenu = ( {  children, ...props} ) => (
	<DropdownMenu icon={ moreVertical } {...props}>
		{ ( { onClose } ) => (
			<>
				{ children && children( onClose ) }
			</>
		) }
	</DropdownMenu>
);

export default EllipsisMenu;