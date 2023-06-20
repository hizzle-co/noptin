/**
 * External dependencies
 */
import { useMemo, useCallback } from "@wordpress/element";
import { Notice, CardBody, Flex, FlexItem } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

/**
 * Local dependencies.
 */
import Wrap from "../wrap";
import TableCard from "../../table";
import { SelectedContextProvider } from "../../table/selected-context";
import DisplayCell from "./display-cell";
import { useSchema, useRecords } from "../../../store-data/hooks";
import { useRoute } from "../hooks";
import ExportButton from "./export";
import DeleteButton from "./delete-button";

/**
 * Displays the table actions.
 *
 * @returns {JSX.Element}
 */
const TableActions = () => (
	<Flex gap={2} wrap>
		<FlexItem>
			<DeleteButton />
		</FlexItem>
		<ExportButton />
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
 * @param {Function} props.updateQuery
 * @param {Object} props.args
 * @param {Object} props.extra
 * @returns {JSX.Element}
 */
export function DisplayRecords( { schema: { schema, hidden, ignore, labels }, total, summary, records, isLoading, updateQuery, query, extra } ) {

	// Prepare the current query.
	const { namespace, collection } = useRoute();

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
				isSortable: ! column.is_dynamic,
				isNumeric: column.is_numeric || column.is_float,
				...column
			});
		} );

		return columns;
	}, [ schema, ignore ] );
	console.log('Before Actions' );
	// Prepare the actions.
	const actions = useMemo ( () => <TableActions />, [] );

	return (
		<TableCard
			actions={ actions }
			rows={ records }
			headers={ columns }
			totalRows={ total }
			summary={ summary ? Object.values( summary ) : [] }
			isLoading={ isLoading }
			onQueryChange={ updateQuery }
			query={ query }
			className={ `${namespace}-${collection}__records-table` }
			hasSearch={ true }
			emptyMessage={ labels?.not_found }
			searchPlaceholder={ labels?.search_items }
			canSelectRows={ true }
			idProp="id"
			DisplayCell={ DisplayCell }
			initialHiddenHeaders={ hidden }
			{ ...extra }
		/>
	);
}

/**
 * Renders a records overview table for the matching path.
 *
 * @returns The records table.
 */
export default function RecordsTable( { component } ) {

	const { namespace, collection, args, path, navigate } = useRoute();
	const records = useRecords( namespace, collection, args );
	const schema  = useSchema( namespace, collection );

	// Show error if any.
	if ( 'ERROR' === records.status ) {

		return (
			<Wrap title={ component.title }>
				<CardBody>
					<Notice status="error" isDismissible={ false }>
						{ records.error?.message || __( 'An unknown error occurred.', 'newsletter-optin-box' ) }
					</Notice>
				</CardBody>
			</Wrap>
		);
	}

	// Updates the query.
	const updateQuery = useCallback(( newQuery ) => {

		// If we're not updating the page, reset it.
		if ( ! newQuery.paged ) {
			delete newQuery.paged;
		}

		navigate( path, { ...args, ...newQuery } );
	}, [ navigate, path, args ] );

	return (
		<SelectedContextProvider>
			<DisplayRecords
				schema={ schema.data }
				records={ records.data.items }
				total={ records.data.total }
				summary={ records.data.summary }
				isLoading={ records.isResolving }
				updateQuery={ updateQuery }
				query={ args }
				extra={ component }
			/>
		</SelectedContextProvider>
	);
}
