/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useMemo, useState } from '@wordpress/element';
import { useDebouncedCallback } from "use-debounce";
import {
	Card,
	SearchControl,
	CardFooter,
	CardHeader,
	Flex,
	FlexBlock,
	FlexItem,
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
	searchPlaceholder,
	headers = [],
	ids,
	isLoading = false,
	onQueryChange = () => {},
	query = {},
	rowHeader = 0,
	rows = [],
	showMenu = true,
	showFooter = true,
	summary,
	title,
	totalRows,
	toggleHiddenCol,
	...props
} ) => {

	const [ searchTerm, setSearchTerm ] = useState( query.search ? query.search : '' );

	// Returns a list of visible cols.
	const visibleCols = useMemo( () => headers.filter( ( { visible } ) => visible ), [ headers ] );
	const allHeaders  = headers;
	const visibleRows = rows.map( ( row ) => {
		return headers
			.map( ( { visible }, i ) => {
				return visible && row[ i ];
			} )
			.filter( Boolean );
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

	// Fired when the search text changes.
	const onSearchTextChange = useDebouncedCallback((value) => {
		onQueryChange({search: value});
	}, 1500);

	return (
		<Card className={ classnames( 'noptin-table', className ) } elevation={2}>

			<CardHeader>
				<Flex gap={2} wrap>

					<FlexItem>
						<h2 className="noptin-heading-text">{ title }</h2>
					</FlexItem>

					{ hasSearch && (
						<FlexBlock style={{minWidth: '200px'}}>
							<SearchControl
								value={ searchTerm }
								onChange={ (value) => {
									setSearchTerm(value);
									onSearchTextChange(value);
								}}
								placeholder={ searchPlaceholder }
								__nextHasNoMarginBottom
							/>
						</FlexBlock>
					) }

					{ actions && (
						<FlexItem>
							{ actions }
						</FlexItem>
					) }

					{ showMenu && (
						<FlexItem>
							<Menu allHeaders={ allHeaders } toggleHiddenCol={ toggleHiddenCol } />
						</FlexItem>
					) }
				</Flex>
			</CardHeader>

			{ isLoading ? <Placeholder { ...theProps } /> : <Table rows={ visibleRows } { ...theProps } /> }

			{ showFooter && (
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
			) }
		</Card>
	);
};

export default TableCard;
