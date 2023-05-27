/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	useRef,
	Fragment,
	useState,
	useEffect,
} from '@wordpress/element';
import classnames from 'classnames';
import { Button } from '@wordpress/components';
import { find, get, noop } from 'lodash';
import { withInstanceId } from '@wordpress/compose';
import { Icon, chevronUp, chevronDown } from '@wordpress/icons';

const ASC = 'asc';
const DESC = 'desc';

const getDisplay = ( cell ) =>
	cell.display || null;

/**
 * A table component, without the Card wrapper. This is a basic table display, sortable, but no default filtering.
 *
 * Row data should be passed to the component as a list of arrays, where each array is a row in the table.
 * Headers are passed in separately as an array of objects with column-related properties. For example,
 * this data would render the following table.
 *
 * ```js
 * const headers = [ { label: 'Month' }, { label: 'Orders' }, { label: 'Revenue' } ];
 * const rows = [
 * 	[
 * 		{ display: 'January', value: 1 },
 * 		{ display: 10, value: 10 },
 * 		{ display: '$530.00', value: 530 },
 * 	],
 * 	[
 * 		{ display: 'February', value: 2 },
 * 		{ display: 13, value: 13 },
 * 		{ display: '$675.00', value: 675 },
 * 	],
 * 	[
 * 		{ display: 'March', value: 3 },
 * 		{ display: 9, value: 9 },
 * 		{ display: '$460.00', value: 460 },
 * 	],
 * ]
 * ```
 *
 * |   Month  | Orders | Revenue |
 * | ---------|--------|---------|
 * | January  |     10 | $530.00 |
 * | February |     13 | $675.00 |
 * | March    |      9 | $460.00 |
 */

const Table = ( {
	instanceId,
	headers = [],
	rows = [],
	ariaHidden,
	caption,
	className,
	onSort = ( f ) => f,
	query = {},
	rowHeader,
	rowKey,
	emptyMessage,
} ) => {
	const [ tabIndex, setTabIndex ] = useState( undefined );
	const [ isScrollableRight, setIsScrollableRight ] = useState( false );
	const [ isScrollableLeft, setIsScrollableLeft ] = useState( false );

	const container = useRef( null );

	const classes = classnames(
		'noptin-table__table',
		className,
		{
			'is-scrollable-right': isScrollableRight,
			'is-scrollable-left': isScrollableLeft,
		}
	);

	const sortBy = ( key ) => {
		return () => {
			const currentKey =
				query.orderby ||
				get( find( headers, { defaultSort: true } ), 'key', false );
			const currentDir =
				query.order ||
				get(
					find( headers, { key: currentKey } ),
					'defaultOrder',
					DESC
				);
			let dir = DESC;
			if ( key === currentKey ) {
				dir = DESC === currentDir ? ASC : DESC;
			}
			onSort( key, dir );
		};
	};

	const getRowKey = ( row, index ) => {
		if ( rowKey && typeof rowKey === 'function' ) {
			return rowKey( row, index );
		}
		return index;
	};

	const updateTableShadow = () => {
		const table = container.current;

		if ( table?.scrollWidth && table?.scrollHeight && table?.offsetWidth ) {
			const scrolledToEnd =
				table?.scrollWidth - table?.scrollLeft <= table?.offsetWidth;
			if ( scrolledToEnd && isScrollableRight ) {
				setIsScrollableRight( false );
			} else if ( ! scrolledToEnd && ! isScrollableRight ) {
				setIsScrollableRight( true );
			}
		}

		if ( table?.scrollLeft ) {
			const scrolledToStart = table?.scrollLeft <= 0;
			if ( scrolledToStart && isScrollableLeft ) {
				setIsScrollableLeft( false );
			} else if ( ! scrolledToStart && ! isScrollableLeft ) {
				setIsScrollableLeft( true );
			}
		}
	};

	const sortedBy =
		query.orderby ||
		get( find( headers, { defaultSort: true } ), 'key', false );
	const sortDir =
		query.order ||
		get( find( headers, { key: sortedBy } ), 'defaultOrder', DESC );
	const hasData = !! rows.length;

	useEffect( () => {
		const scrollWidth = container.current?.scrollWidth;
		const clientWidth = container.current?.clientWidth;

		if ( scrollWidth === undefined || clientWidth === undefined ) {
			return;
		}

		const scrollable = scrollWidth > clientWidth;
		setTabIndex( scrollable ? 0 : undefined );
		updateTableShadow();
		window.addEventListener( 'resize', updateTableShadow );

		return () => {
			window.removeEventListener( 'resize', updateTableShadow );
		};
	}, [] );

	useEffect( updateTableShadow, [ headers, rows, emptyMessage ] );

	return (
		<div
			className={ classes }
			ref={ container }
			tabIndex={ tabIndex }
			aria-hidden={ ariaHidden }
			aria-labelledby={ `caption-${ instanceId }` }
			role="group"
			onScroll={ updateTableShadow }
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
					<tr>
						{ headers.map( ( header, i ) => {
							const {
								cellClassName,
								isLeftAligned,
								isSortable,
								isNumeric,
								key,
								label,
								screenReaderLabel,
							} = header;
							const labelId = `header-${ instanceId }-${ i }`;
							const thProps = {
								className: classnames(
									'noptin-table__header',
									cellClassName,
									{
										'is-left-aligned':
											isLeftAligned || ! isNumeric,
										'is-sortable': isSortable,
										'is-sorted': sortedBy === key,
										'is-numeric': isNumeric,
									}
								),
							};
							if ( isSortable ) {
								thProps[ 'aria-sort' ] = 'none';
								if ( sortedBy === key ) {
									thProps[ 'aria-sort' ] =
										sortDir === ASC
											? 'ascending'
											: 'descending';
								}
							}
							// We only sort by ascending if the col is already sorted descending
							const iconLabel =
								sortedBy === key && sortDir !== ASC
									? sprintf(
											__(
												'Sort by %s in ascending order',
												'newsletter-optin-box'
											),
											screenReaderLabel || label
									  )
									: sprintf(
											__(
												'Sort by %s in descending order',
												'newsletter-optin-box'
											),
											screenReaderLabel || label
									  );

							const textLabel = (
								<Fragment>
									<span
										aria-hidden={ Boolean(
											screenReaderLabel
										) }
									>
										{ label }
									</span>
									{ screenReaderLabel && (
										<span className="screen-reader-text">
											{ screenReaderLabel }
										</span>
									) }
								</Fragment>
							);

							return (
								<th
									role="columnheader"
									scope="col"
									key={ header.key || i }
									{ ...thProps }
								>
									{ isSortable ? (
										<Fragment>
											<Button
												aria-describedby={ labelId }
												onClick={
													hasData
														? sortBy( key )
														: noop
												}
											>
												{ sortedBy === key &&
												sortDir === ASC ? (
													<Icon icon={ chevronUp } />
												) : (
													<Icon
														icon={ chevronDown }
													/>
												) }
												{ textLabel }
											</Button>
											<span
												className="screen-reader-text"
												id={ labelId }
											>
												{ iconLabel }
											</span>
										</Fragment>
									) : (
										textLabel
									) }
								</th>
							);
						} ) }
					</tr>
					{ hasData ? (
						rows.map( ( row, i ) => (
							<tr key={ getRowKey( row, i ) }>
								{ row.map( ( cell, j ) => {
									const {
										cellClassName,
										isLeftAligned,
										isNumeric,
									} = headers[ j ];
									const isHeader = rowHeader === j;
									const Cell = isHeader ? 'th' : 'td';
									const cellClasses = classnames(
										'noptin-table__item',
										cellClassName,
										{
											'is-left-aligned':
												isLeftAligned || ! isNumeric,
											'is-numeric': isNumeric,
											'is-sorted':
												sortedBy === headers[ j ].key,
										}
									);
									const cellKey =
										getRowKey( row, i ).toString() + j;
									return (
										<Cell
											scope={
												isHeader ? 'row' : undefined
											}
											key={ cellKey }
											className={ cellClasses }
										>
											{ getDisplay( cell ) }
										</Cell>
									);
								} ) }
							</tr>
						) )
					) : (
						<tr>
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
