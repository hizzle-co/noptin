import Table from '../../table/table';
import { Icon } from '@wordpress/components';
import { Fragment, useMemo } from '@wordpress/element';

/**
 * Displays a single cell in the records table.
 * @param {Object} props
 * @param {Object} props.display The value to display.
 * @returns
 */
const DisplayCell = ( { display } ) => {

	// If value is not an object, just display it.
	if ( typeof display !== 'object' ) {
		return display;
	}

	// Icons.
	if ( 'icon' === display.el ) {
		return <Icon icon={ display.icon } />;
	}

	// Break separated lists.
	if ( 'break_separated_list' === display.el ) {
		return display.items.map( ( item, index ) => (
			<Fragment key={ index }>
				{ item }
				<br />
			</Fragment>
		) );
	}
}

/**
 * Renders a custom table on the record overview page.
 *
 * @param {Object} props
 * @param {Object} props.actions
 * @param {Array} props.rows
 * @returns {JSX.Element}
 */
export default function OverviewTable( { actions={}, rows=[], ...props } ) {

	// Prepare the rows.
	const preparedRows = useMemo( () => {

		if ( ! Array.isArray( rows ) ) {
			return [];
		}

		return rows.map( ( row ) => ({ ...row, display: <DisplayCell display={ row.display } /> }));
	}, [ rows ] );

	return (
		<Table rows={ preparedRows } {...props} />
	);
}
