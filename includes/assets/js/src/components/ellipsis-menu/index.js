/**
 * External dependencies
 */
import classnames from 'classnames';
import { Button, Dropdown, NavigableMenu } from '@wordpress/components';
import { Icon, moreVertical } from '@wordpress/icons';

/**
 * Displays a menu item with an ellipsis icon that opens a dropdown menu.
 */
const EllipsisMenu = ( { label, children, className, onToggle, } ) => {

	const renderEllipsis = ( { onToggle: toggleHandlerOverride, isOpen, } ) => {
		const toggleClassname = classnames(
			'noptin-ellipsis-menu__toggle',
			{
				'is-opened': isOpen,
			}
		);

		return (
			<Button
				className={ toggleClassname }
				onClick={ ( e ) => {
					if ( onToggle ) {
						onToggle( e );
					}
					if ( toggleHandlerOverride ) {
						toggleHandlerOverride();
					}
				} }
				title={ label }
				aria-expanded={ isOpen }
			>
				<Icon icon={ moreVertical } />
			</Button>
		);
	};

	const renderMenu = ( renderContentArgs ) => (
		<NavigableMenu className="noptin-ellipsis-menu__content">
			{ renderContent( renderContentArgs ) }
		</NavigableMenu>
	);

	return (
		<div className={ classnames( className, 'noptin-ellipsis-menu' ) }>
			<Dropdown
				contentClassName="noptin-ellipsis-menu__popover"
				position="bottom left"
				renderToggle={ renderEllipsis }
				renderContent={ renderMenu }
			/>
		</div>
	);
};

export default EllipsisMenu;