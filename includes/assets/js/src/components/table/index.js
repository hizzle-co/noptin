/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Fragment, useMemo, useState, useCallback, useEffect } from '@wordpress/element';
import { without, compact, uniq } from 'lodash';
import {
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	__experimentalText as Text,
	MenuGroup,
	ToggleControl,
	MenuItem
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import EllipsisMenu from '../ellipsis-menu';
import Pagination from '../pagination';
import Table from './table';
import TablePlaceholder from './placeholder';
import TableSummary, { TableSummaryPlaceholder } from './summary';

const defaultOnQueryChange   =  ( ...query ) => { console.log( query ) };
const defaultOnColumnsChange = () => () => {};

/**
 * This is an accessible, sortable, and scrollable table for displaying tabular data (like revenue and other analytics data).
 * It accepts `headers` for column headers, and `rows` for the table content.
 * `rowHeader` can be used to define the index of the row header (or false if no header).
 *
 * `TableCard` serves as Card wrapper & contains a card header, `<Table />`, `<TableSummary />`, and `<Pagination />`.
 * This includes filtering and comparison functionality for report pages.
 */
const TableCard = ( {
	actions,
	className,
	hasSearch,
	headers = [],
	ids,
	isLoading = false,
	onQueryChange = defaultOnQueryChange,
	onColumnsChange = defaultOnColumnsChange,
	query = {},
	rowHeader = 0,
	rows = [],
	rowsPerPage,
	showMenu = true,
	summary,
	title,
	totalRows,
	emptyMessage = undefined,
	onClickDownload,
	onClickImport,
	...props
} ) => {

	// Contains an array of hidden cols.
	const [ hiddenCols, setHiddenCols ] = useState( [] );

	// Hide cols when headers change.
	useEffect( () => {

		// Fetch hidden cols.
		const newHiddenCols = compact( headers.map( ( { key, visible } ) => {
			if ( typeof visible === 'undefined' || visible ) {
				return false;
			}
			return key;
		} ) );

		// Combine new hidden cols with existing hidden cols.
		setHiddenCols( uniq( [ ...hiddenCols, ...newHiddenCols ] ) );
	}, [ headers ] );

	// Checks if a given col is hidden.
	const isHiddenCol = useCallback( ( key ) => hiddenCols.includes( key ), [ hiddenCols ] );

	// Returns a list of visible cols.
	const visibleCols = useMemo( () => headers.filter( ( { key } ) => ! isHiddenCol( key ) ), [ headers, isHiddenCol ] );

	// Toggle a column's visibility.
	const onColumnToggle = ( key ) => {
		return () => {

			if ( isHiddenCol( key ) ) {
				setHiddenCols( without( hiddenCols, key ) );
			} else {
				setHiddenCols( [ ...hiddenCols, key ] );
			}
		};
	};

	const allHeaders = headers;
	const visibleRows = rows.map( ( row ) => {
		return headers
			.map( ( { key }, i ) => {
				return ! hiddenCols.includes( key ) && row[ i ];
			} )
			.filter( Boolean );
	} );
	const classes = classnames( 'noptin-table', className, {
		'has-actions': !! actions,
		'has-menu': showMenu,
		'has-search': hasSearch,
	} );

	return (
		<Card className={ classes }>

			<CardHeader>
				<Text size={ 16 } weight={ 600 } as="h2" color="#23282d">
					{ title }
				</Text>

				<div className="noptin-table__actions">{ actions }</div>

				{ showMenu && (
					<EllipsisMenu label={ __( 'Choose which values to display', 'newsletter-optin-box' ) }>
						{ () => (
							<>
								<MenuGroup label={__( 'Columns', 'newsletter-optin-box' )}>
									{ allHeaders.map(
										( { key, label, required } ) => {

											// Don't allow hiding required cols.
											if ( required || key === undefined ) {
												return null;
											}

											return (
												<MenuItem onClick={ onColumnToggle( key ) } key={ key }>
													<ToggleControl
														checked={ ! hiddenCols.includes( key ) }
														onChange={ onColumnToggle( key ) }
														label={ label }
														__nextHasNoMarginBottom
													/>
												</MenuItem>
											);
										}
									) }
								</MenuGroup>
							</>
						) }
					</EllipsisMenu>
				) }
			</CardHeader>

			{ /* Ignoring the error to make it backward compatible for now. */ }
			{ /* @ts-expect-error: size must be one of small, medium, largel, xSmall, extraSmall. */ }
			<CardBody size={ null }>
				{ isLoading ? (
					<Fragment>
						<span className="screen-reader-text">
							{ __(
								'Your requested data is loading',
								'newsletter-optin-box'
							) }
						</span>
						<TablePlaceholder
							numberOfRows={ rowsPerPage }
							headers={ visibleCols }
							rowHeader={ rowHeader }
							caption={ title }
							query={ query }
						/>
					</Fragment>
				) : (
					<Table
						rows={ visibleRows }
						headers={ visibleCols }
						rowHeader={ rowHeader }
						caption={ title }
						emptyMessage={ emptyMessage }
						sortBy={ query.orderby || 'id' }
						sortDir={ query.order || 'desc' }
						onChangeSortBy={ ( orderby ) => onQueryChange( { orderby } ) }
						onChangeSortDir={ ( order ) => onQueryChange( { order } ) }
					/>
				) }
			</CardBody>

			<CardFooter justify="center">
				{ isLoading ? (
					<TableSummaryPlaceholder />
				) : (
					<Fragment>
						<Pagination
							key={ parseInt( query.paged, 10 ) || 1 }
							page={ parseInt( query.paged, 10 ) || 1 }
							perPage={ rowsPerPage }
							total={ totalRows }
							onPageChange={ ( page ) => onQueryChange( {page} ) }
							onPerPageChange={ ( per_page ) => onQueryChange( {per_page} ) }
						/>

						{ summary && <TableSummary data={ summary } /> }
					</Fragment>
				) }
			</CardFooter>
		</Card>
	);
};

export default TableCard;
