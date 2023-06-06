/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef, useState, useEffect, } from '@wordpress/element';
import classnames from 'classnames';
import { withInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import BodyCell from './body-cell';
import HeaderCell from './header-cell';

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
 * @param {string} props.instanceId Instance ID for the component.
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
	instanceId,
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
	const [ tabIndex, setTabIndex ] = useState( undefined );
	const sortBy = query.orderby || 'id';
	const sortDir = query.order || DESC;
	const container = useRef( null );

	const classes = classnames( className, 'noptin-table__table' );

	const setSortBy = ( col ) => {

		// Maybe change the sort direction.
		if ( col === sortBy ) {
			onQueryChange( { order: sortDir === ASC ? DESC : ASC } );
		} else {
			onQueryChange( { orderby: col } );
		}
	};

	const hasData = !! rows.length;

	useEffect( () => {
		const scrollWidth = container.current?.scrollWidth;
		const clientWidth = container.current?.clientWidth;

		if ( scrollWidth === undefined || clientWidth === undefined ) {
			return;
		}

		const scrollable = scrollWidth > clientWidth;
		setTabIndex( scrollable ? 0 : undefined );
	}, [] );

	return (
		<div
			className={ classes }
			ref={ container }
			tabIndex={ tabIndex }
			aria-hidden={ ariaHidden }
			aria-labelledby={ `caption-${ instanceId }` }
			role="group"
		>
			<table>
				<caption
					id={ `caption-${ instanceId }` }
					className="noptin-table__caption screen-reader-text"
				>
					{ caption }
					{ tabIndex === 0 && (
						<small>
							{ __( '(scroll to see more)', 'newsletter-optin-box' ) }
						</small>
					) }
				</caption>
				<tbody>

					<tr className="noptin-table__row">
						{ headers.map( ( header, i ) => {
							
							const { key, label, isSortable, ...props } = header;
							return (
								<HeaderCell
									key={ key }
									instanceId={ instanceId }
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
					</tr>

					{ hasData ? (
						rows.map( ( row, i ) => (
							<tr className="noptin-table__row" key={ getRowKey( row, i ) }>
								{ row.map( ( cell, j ) => (
									<BodyCell
										{...headers[ j ]}
										key={ getRowKey( row, i ).toString() + j }
										cell={ cell }
										isHeader={ rowHeader === j }
										isSorted={ sortBy === headers[ j ].key }
									/>
								) ) }
							</tr>
						) )
					) : (
						<tr className="noptin-table__row">
							<td
								className="noptin-table__empty-item"
								colSpan={ headers.length }
							>
								{ emptyMessage ??
									__( 'No data to display', 'newsletter-optin-box' ) }
							</td>
						</tr>
					) }
				</tbody>
			</table>
		</div>
	);
};

export default withInstanceId( Table );
