/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useMemo } from '@wordpress/element';
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

/**
 * Displays a placeholder table while the data is loading.
 */
const Placeholder = ( { headers, rowHeader, caption, query } ) => {

	return (
		<>
			<span className="screen-reader-text">
				{ __( 'Your requested data is loading', 'newsletter-optin-box' ) }
			</span>
			<TablePlaceholder
				headers={ headers }
				rowHeader={ rowHeader }
				caption={ caption }
				query={ query }
			/>
		</>
	);
};

/**
 * Displays the table menu.
 */
const Menu = ( { allHeaders, toggleHiddenCol } ) => {

	return (
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
	);
};

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
	onQueryChange = () => {},
	query = {},
	rowHeader = 0,
	rows = [],
	showMenu = true,
	summary,
	title,
	totalRows,
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

	// Common props.
	const theProps = {
		headers: visibleCols,
		caption: title,
		onQueryChange,
		rowHeader,
		query,
		...props,
	};

	return (
		<Card className={ classes }>

			<CardHeader>
				<Text size={ 16 } weight={ 600 } as="h2" color="#23282d">
					{ title }
				</Text>

				<div className="noptin-table__actions">{ actions }</div>

				{ showMenu && <Menu allHeaders={ allHeaders } toggleHiddenCol={ toggleHiddenCol } /> }

			</CardHeader>

			{ isLoading ? <Placeholder { ...theProps } /> : <Table rows={ visibleRows } { ...theProps } /> }

			<CardFooter justify="center">
				{ isLoading ? (
					<TableSummaryPlaceholder />
				) : (
					<>
						<Pagination query={ query } onQueryChange={ onQueryChange } total={ totalRows } />
						{ summary && <TableSummary data={ summary } /> }
					</>
				) }
			</CardFooter>
		</Card>
	);
};

export default TableCard;
