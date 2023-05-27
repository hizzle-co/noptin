/**
 * `MenuTitle` is another valid Menu child, but this does not have any accessibility attributes associated
 * (so this should not be used in place of the `EllipsisMenu` prop `label`).
 */

const MenuTitle = ( { children } ) => {
	return <div className="noptin-ellipsis-menu__title">{ children }</div>;
};

export default MenuTitle;
