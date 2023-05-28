/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Fragment, useMemo } from '@wordpress/element';
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
	showMenu = true,
	summary,
	title,
	totalRows,
	emptyMessage = undefined,
	toggleHiddenCol,
	...props
} ) => {

	// Returns a list of visible cols.
	const visibleCols = useMemo( () => headers.filter( ( { visible } ) => visible ), [ headers ] );

	const allHeaders = headers;
	const visibleRows = rows.map( ( row ) => {
		return headers
			.map( ( { visible }, i ) => {
				return visible && row[ i ];
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
										( { key, label, required, visible } ) => {

											// Don't allow hiding required cols.
											if ( required || key === undefined ) {
												return null;
											}

											return (
												<MenuItem key={ key }>
													<ToggleControl
														checked={ visible }
														onChange={ () => toggleHiddenCol( key ) }
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
							numberOfRows={ query.per_page ? query.per_page : 25 }
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
							perPage={ query.per_page ? query.per_page : 25 }
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
