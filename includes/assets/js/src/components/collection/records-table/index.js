/**
 * External dependencies
 */
import { useMemo, useCallback } from "@wordpress/element";
import { Notice, CardBody, Flex, FlexItem } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useParams } from 'react-router-dom';

/**
 * Local dependencies.
 */
import Wrap from "../wrap";
import TableCard from "../../table";
import { SelectedContextProvider } from "../../table/selected-context";
import DisplayCell from "./display-cell";
import { useCurrentSchema, useCurrentRecords } from "../hooks";
import ExportButton from "./export";
import DeleteButton from "./delete-button";
import { updateQueryString, getQuery } from "../../navigation";

/**
 * Displays the table actions.
 *
 * @returns {JSX.Element}
 */
const TableActions = (
	<Flex gap={2} wrap>
		<FlexItem>
			<ExportButton />
		</FlexItem>
		<FlexItem>
			<DeleteButton />
		</FlexItem>
	</Flex>
);

/**
 * Displays the records table.
 * @param {Object} props
 * @param {Object} props.schema
 * @param {Array} props.schema.schema
 * @param {Array} props.schema.ignore
 * @param {Array} props.schema.hidden
 * @param {Array} props.records
 * @param {Boolean} props.isLoading
 * @param {Object} props.args
 * @returns {JSX.Element}
 */
const DisplayRecords = ( { schema: { schema, hidden, ignore, labels }, total, summary, records, isLoading } ) => {

	// Prepare the current query.
	const { namespace, collection } = useParams();

	// Make some columns from the schema.
	const columns = useMemo( () => {

		const columns = [];

		schema.forEach( ( column ) => {

			// Abort if dynamic column.
			if ( ignore.includes( column.name ) ) {
				return;
			}

			columns.push( {
				key: column.name,
				isSortable: ! column.is_dynamic && ! column.is_meta,
				isNumeric: column.is_numeric || column.is_float,
				...column
			});
		} );

		return columns;
	}, [ schema, ignore ] );

	// Updates the query.
	const updateQuery = useCallback(( newQuery ) => {

		// If we're not updating the page, reset it.
		if ( ! newQuery.paged ) {
			newQuery.paged = 1;
		}

		updateQueryString( newQuery );
	}, [] );

	return (
		<TableCard
			actions={ TableActions }
			rows={ records }
			headers={ columns }
			totalRows={ total }
			summary={ summary ? Object.values( summary ) : [] }
			isLoading={ isLoading }
			onQueryChange={ updateQuery }
			query={ getQuery() }
			className={ `${namespace}-${collection}__records-table` }
			hasSearch={ true }
			title={ labels?.name }
			emptyMessage={ labels?.not_found }
			searchPlaceholder={ labels?.search_items }
			canSelectRows={ true }
			idProp="id"
			DisplayCell={ DisplayCell }
			initialHiddenHeaders={ hidden }
		/>
	);
};

/**
 * Renders a records overview table for the matching path.
 *
 * @returns The records table.
 */
export default function RecordsTable() {
	const records = useCurrentRecords();
	const {data}  = useCurrentSchema();

	return (
		<>
			{ 'ERROR' === records.status ? (
				<Wrap title={ data.labels?.name || __( 'Records', 'newsletter-optin-box' ) }>
					<CardBody>
						<Notice status="error" isDismissible={ false }>
							{ records.error?.message || __( 'An unknown error occurred.', 'newsletter-optin-box' ) }
						</Notice>
					</CardBody>
				</Wrap>
			) : (
				<SelectedContextProvider>
					<DisplayRecords
						schema={ data }
						records={ records.data.items }
						total={ records.data.total }
						summary={ records.data.summary }
						isLoading={ records.isResolving }
					/>
				</SelectedContextProvider>
			) }
		</>
	);
}
