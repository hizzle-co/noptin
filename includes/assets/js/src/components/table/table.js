/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import BodyCell from './body-cell';
import HeaderCell from './header-cell';
import { ScrollableTable, TableRow, TableCellNoData } from '../styled-components';

const ASC = 'asc';
const DESC = 'desc';

/**
 * Calculates a row key.
 */
const getRowKey = ( row, index ) => {

	// If the row has an ID, use that.
	if ( row.id ) {
		return row.id;
	}

	// If the row has a key, use that.
	if ( row.key ) {
		return row.key;
	}

	// Otherwise, use the index.
	return index;
}

/**
 * A table component, without the Card wrapper. This is a basic table display, sortable, but no default filtering.
 *
 * Row data should be passed to the component as a list of arrays, where each array is a row in the table.
 * Headers are passed in separately as an array of objects with column-related properties.
 *
 * @param {Object} props Component props.
 * @param {Array} props.headers Array of objects with column-related properties.
 * @param {Array} props.rows Array of arrays, where each array is a row in the table.
 * @param {boolean} props.ariaHidden Whether the table should be hidden from screen readers.
 * @param {string} props.caption Caption for the table.
 * @param {string} props.className Additional class names to add to the table.
 * @param {number} props.rowHeader Index of the row header (or false if no header).
 * @param {string} props.emptyMessage Message to display when there are no rows.
 * @param {string} props.sortBy Column key to sort by.
 * @param {string} props.sortDir Sort direction.
 * @param {Function} props.onChangeSortBy Callback function for when the sort column is changed.
 */
const Table = ( {
	query,
	headers = [],
	rows = [],
	ariaHidden,
	caption,
	className,
	rowHeader,
	emptyMessage,
	onQueryChange,
} ) => {
	const sortBy = query.orderby || 'id';
	const sortDir = query.order || DESC;

	const setSortBy = ( col ) => {

		// Maybe change the sort direction.
		if ( col === sortBy ) {
			onQueryChange( { order: sortDir === ASC ? DESC : ASC } );
		} else {
			onQueryChange( { orderby: col } );
		}
	};

	const hasData = !! rows.length;

	return (
		<ScrollableTable
			className={ className }
			tabIndex="0"
			aria-hidden={ ariaHidden }
			aria-label={ `${ caption } - ${ __( '(scroll to see more)', 'newsletter-optin-box' ) }` }
			role="group"
		>
			<table>
				<tbody>

					<TableRow className="noptin-table__row">
						{ headers.map( ( header, i ) => {
							
							const { key, label, isSortable, ...props } = header;
							return (
								<HeaderCell
									key={ key }
									columnLabel={ label }
									columnKey={ key }
									isSortable={ isSortable && hasData }
									isSorted={ sortBy === key }
									sortDir={ sortDir }
									onClick={ () => setSortBy( key ) }
									{ ...props }
								/>
							);

						} ) }
					</TableRow>

					{ hasData ? (
						rows.map( ( row, i ) => (
							<TableRow className="noptin-table__row" key={ getRowKey( row, i ) }>
								{ row.map( ( cell, j ) => (
									<BodyCell
										{...headers[ j ]}
										key={ getRowKey( row, i ).toString() + j }
										cell={ cell }
										isHeader={ rowHeader === j }
										isSorted={ sortBy === headers[ j ].key }
									/>
								) ) }
							</TableRow>
						) )
					) : (
						<TableRow className="noptin-table__row">
							<TableCellNoData colSpan={ headers.length }>
								{ emptyMessage ?? __( 'No data to display', 'newsletter-optin-box' ) }
							</TableCellNoData>
						</TableRow>
					) }
				</tbody>
			</table>
		</ScrollableTable>
	);
};

export default Table;