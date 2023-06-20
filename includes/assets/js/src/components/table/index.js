/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useMemo, useState, useCallback } from '@wordpress/element';
import {
	Card,
	CardFooter,
	CardHeader,
	Flex,
	FlexItem,
	MenuGroup,
	ToggleControl,
	MenuItem
} from '@wordpress/components';
import { without } from 'lodash';

/**
 * Internal dependencies
 */
import EllipsisMenu from '../ellipsis-menu';
import Pagination from '../pagination';
import Table from './table';
import TablePlaceholder from './placeholder';
import TableSummary, { TableSummaryPlaceholder } from './summary';
import { CardHeadingText, Bordered } from '../styled-components';
import SearchForm from './search-form';

/**
 * Displays a placeholder table while the data is loading.
 *
 * @param {Object} props Component props.
 * @param {Array} props.headers Visible table headers.
 * @param {string} props.caption Table caption.
 * @param {Object} props.query Query object.
 * @return {JSX.Element} Placeholder table.
 */
const Placeholder = ( { headers, caption, query } ) => {

	return (
		<>
			<span className="screen-reader-text">
				{ __( 'Your requested data is loading', 'newsletter-optin-box' ) }
			</span>
			<TablePlaceholder
				headers={ headers }
				caption={ caption }
				query={ query }
			/>
		</>
	);
};

/**
 * Displays the table menu.
 *
 * @param {Object} props Component props.
 * @param {Array} props.headers Table headers.
 * @param {Array} props.hiddenHeaders Keys of hidden table headers.
 * @param {Function} props.setHiddenHeaders Callback to update the hidden table headers.
 * @return {JSX.Element} Table menu.
 */
export const Menu = ( { headers, hiddenHeaders, setHiddenHeaders } ) => {

	// Toggle a header.
	const toggleHiddenCol = useCallback( ( key ) => {
		setHiddenHeaders(
			hiddenHeaders.includes( key )
				? without( hiddenHeaders, key )
				: [ ...hiddenHeaders, key ]
		);
	}, [ headers, hiddenHeaders, setHiddenHeaders ] );

	return (
		<EllipsisMenu label={ __( 'Choose the columns to display', 'newsletter-optin-box' ) }>
			{ () => (
				<>
					<MenuGroup label={__( 'Columns', 'newsletter-optin-box' )}>
						{ headers.map(
							( { key, label, required } ) => {

								// Don't allow hiding required cols.
								if ( required || key === undefined ) {
									return null;
								}

								return (
									<MenuItem key={ key }>
										<ToggleControl
											checked={ ! hiddenHeaders.includes( key ) }
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
 * Displays the table header.
 *
 * @param {Object} props Component props.
 * @param {string} props.title Table title.
 * @param {boolean} props.hasSearch Whether the table has a search field.
 * @param {Object} props.query Query object.
 * @param {Object} props.query.search Search query.
 * @param {Function} props.onQueryChange Callback to update the query.
 * @param {string} props.searchPlaceholder Search field placeholder.
 * @param {JSX.Element} props.actions Table actions.
 * @param {Array} props.headers Table headers.
 * @param {Array} props.hiddenHeaders Keys of hidden table headers.
 * @param {Function} props.setHiddenHeaders Callback to update the hidden table headers.
 */
export const TableHeader = ( { title, hasSearch, query, onQueryChange, searchPlaceholder, actions, headers, hiddenHeaders, setHiddenHeaders } ) => {

	// Memoize the actions.
	const theActions = useMemo( () => (
		actions ? (
			<FlexItem>
				{ actions }
			</FlexItem>
		) : null
	), [ actions ] );

	// Memoize the search field.
	const theSearch = useMemo( () => (
		hasSearch ? (
			<SearchForm
				value={ query.search }
				onChange={ ( value ) => onQueryChange( { search: value } ) }
				placeholder={ searchPlaceholder }
			/>
		) : null
	), [ hasSearch, query.search, onQueryChange, searchPlaceholder ] );

	// Memoize the menu.
	const theMenu = useMemo( () => (
		<FlexItem>
			<Menu headers={ headers } hiddenHeaders={ hiddenHeaders } setHiddenHeaders={ setHiddenHeaders } />
		</FlexItem>
	), [ headers, hiddenHeaders, setHiddenHeaders ] );

	return (
		<CardHeader>
			<Flex gap={2} wrap>

				<FlexItem>
					<CardHeadingText as="h2">{ title }</CardHeadingText>
				</FlexItem>

				{ theSearch }

				{ theActions }

				{ theMenu }
			</Flex>
		</CardHeader>
	)
}

/**
 * Displays the table footer.
 *
 * @param {Object} props Component props.
 * @param {boolean} props.isLoading Whether the table is loading.
 * @param {Array} props.summary Table summary.
 * @param {Object} props.query Query object.
 * @param {Function} props.onQueryChange Callback to update the query.
 * @param {number} props.totalRows Total number of rows.
 * @return {JSX.Element} Table footer.
 */
export const TableFooter = ( { isLoading, summary, query, onQueryChange, totalRows } ) => (
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
);

/**
 * This is a Card wrapper containing a card header, `<Table />`, `<TableSummary />`, and `<Pagination />`.
 *
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
	initialHiddenHeaders = [],
	rows = [],
	showMenu = true,
	showFooter = true,
	summary,
	title,
	totalRows,
	canSelectRows,
	DisplayCell,
	...props
} ) => {

	// An array of hidden header keys.
	const [ hiddenHeaders, setHiddenHeaders ] = useState( initialHiddenHeaders );

	// Prepare visible headers.
	const visibleHeaders = useMemo( () => headers.filter( ( header ) => ! hiddenHeaders.includes( header.key ) ), [ headers, hiddenHeaders ] );

	// Common props.
	const theProps = {
		headers: visibleHeaders,
		caption: title,
		onQueryChange,
		query,
		...props,
	};

	const BorderedCard = Bordered.withComponent(Card);

	return (
		<BorderedCard className={ classnames( 'noptin-table', className ) } elevation={0}>

			<TableHeader
				title={ title }
				hasSearch={ hasSearch }
				query={ query }
				onQueryChange={ onQueryChange }
				searchPlaceholder={ searchPlaceholder }
				actions={ actions }
				headers={ headers }
				hiddenHeaders={ hiddenHeaders }
				setHiddenHeaders={ setHiddenHeaders }
			/>

			{ isLoading ? (
				<Placeholder { ...theProps } />
			) : (
				<Table
					rows={ rows }
					DisplayCell={ DisplayCell }
					canSelectRows={ canSelectRows }
					{ ...theProps }
				/>
			) }

			{ showFooter && (
				<TableFooter
					isLoading={isLoading}
					summary={summary}
					query={query}
					onQueryChange={onQueryChange}
					totalRows={totalRows}
				/>
			) }
		</BorderedCard>
	);
};

export default TableCard;
