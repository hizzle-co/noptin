/**
 * Displays a single item and value.
 *
 * @param {Object} props
 * @param {string} props.label The item label.
 * @param {string} props.value The item value.
 * @return {JSX.Element}
 */
export function ListItem( { label, value } ) {

	// Convert label to class name by replacing all non-alphanumeric characters with a dash.
	const className = label.toLowerCase().replace( /[^a-z0-9]/g, '-' );

	return (
		<li className={`noptin-list-item noptin-list-item__${className}`}>
			<div className="noptin-list-item__key">
				{label}
			</div>
			<div className="noptin-list-item__value">
				{value}
			</div>
		</li>
	);
}

/**
 * Displays an item and value list.
 *
 * @param {Object} props
 * @param {Array} props.items An array of key value pairs.
 * @return {JSX.Element}
 */
export default function List( { items } ) {

	return (
		<ul className="noptin-component__list">
			{items.map( ( item ) => (
				<ListItem key={item.label} label={item.label} value={item.value} />
			) )}
		</ul>
	);
}
