/**
 * External dependencies
 */
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { moreVertical, close } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Displays a menu item with an ellipsis icon that opens a dropdown menu.
 */
const EllipsisMenu = ( { actions, children, ...props} ) => (
	<DropdownMenu icon={ moreVertical } {...props}>
		{ ( { onClose } ) => (
			<>
				<MenuGroup label={__( 'Actions', 'newsletter-optin-box' )}>
					<MenuItem icon={ close } onClick={ onClose }>
						{ __( 'Close', 'newsletter-optin-box' ) }
					</MenuItem>
					{ actions && actions( onClose ) }
				</MenuGroup>
				{ children && children( onClose ) }
			</>
		) }
	</DropdownMenu>
);

export default EllipsisMenu;