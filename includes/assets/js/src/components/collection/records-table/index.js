/**
 * External dependencies
 */
import { useMemo, useCallback, useState } from "@wordpress/element";
import { Notice, CardBody, Flex, FlexItem, FlexBlock, Button } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useParams } from 'react-router-dom';
import { useDispatch } from '@wordpress/data';

/**
 * Local dependencies.
 */
import Wrap from "../wrap";
import TableCard from "../../table";
import DisplayCell from "./display-cell";
import { useAppendNavigation } from "../hooks";
import { useRecords, useSchema } from "../../../store-data/hooks";
import ExportButton from "./export";
import DeleteButton from "./delete-button";
import BulkEditButton from "./bulk-edit-button";
import FiltersButton from "./filters";
import { updateQueryString, useQuery } from "../../navigation";
import { withSchema } from "../page";
import { useSelected } from "../../table/selected-context";

/**
 * Displays the modal add button.
 *
 */
const ModalAddButton = ( { namespace, collection } ) => {

	const navigateTo = useAppendNavigation();
	const { data } = useSchema( namespace, collection );

	// Display the button.
	return (
		<Button
			onClick={() => navigateTo( `${namespace}/${collection}/add` )}
			variant="primary"
			text={data?.labels?.add_new || __( 'Add New', 'newsletter-optin-box' )}
		/>
	);

}

/**
 * Displays the table actions.
 *
 * @returns {JSX.Element}
 */
const TableActions = ( { namespace, collection, inline, query } ) => {

	const records = useRecords( namespace, collection, query );
	const count = records.data?.total || 0;
	const [selected, setSelected]  = useSelected( `${namespace}/${collection}` );

	return (
		<Flex gap={2} wrap>
			{inline && (
				<FlexItem>
					<ModalAddButton namespace={namespace} collection={collection} query={query} count={count} selected={selected} />
				</FlexItem>
			)}
			<FlexItem>
				<BulkEditButton namespace={namespace} collection={collection} query={query} count={count} selected={selected} isBulkEditing />
			</FlexItem>
			<FlexItem>
				<ExportButton namespace={namespace} collection={collection} query={query} count={count} selected={selected} />
			</FlexItem>
			<FlexItem>
				<DeleteButton namespace={namespace} collection={collection} query={query} count={count} selected={selected} setSelected={setSelected} />
			</FlexItem>
		</Flex>
	)
};

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
const DisplayRecords = ( {
	schema: { schema, hidden, ignore, labels },
	total,
	summary,
	records,
	isLoading,
	namespace,
	collection,
	updateQueryString,
	query,
	emptyMessage,
	inline,
	basePath = '',
} ) => {

	// Make some columns from the schema.
	const columns = useMemo( () => {

		const columns = [];

		schema.forEach( ( column ) => {

			// Abort if dynamic column.
			if ( column.is_textarea || ignore.includes( column.name ) || 'hide' === column.js_props?.table ) {
				return;
			}

			columns.push( {
				key: column.name,
				isSortable: !column.is_dynamic && !column.is_meta,
				isNumeric: column.is_numeric || column.is_float,
				basePath,
				...column
			} );
		} );

		return columns;
	}, [schema, ignore] );

	// Updates the query.
	const updateQuery = useCallback( ( newQuery ) => {

		// If we're not updating the page, reset it.
		if ( !newQuery.paged ) {
			newQuery.paged = 1;
		}

		updateQueryString( { ...query, ...newQuery } );
	}, [updateQueryString] );

	// Refreshes the data.
	const dispatch = useDispatch( `${namespace}/${collection}` );
	const refresh = useCallback( () => {
		dispatch.invalidateResolutionForStoreSelector( 'getRecords' );
	}, [namespace, collection] );

	return (
		<TableCard
			actions={<TableActions namespace={namespace} collection={collection} inline={inline} query={query} />}
			rows={records}
			headers={columns}
			totalRows={total}
			summary={summary ? Object.values( summary ) : []}
			isLoading={isLoading}
			onQueryChange={updateQuery}
			onRefresh={refresh}
			query={query}
			className={`${namespace}-${collection}__records-table`}
			hasSearch={true}
			title={labels?.name}
			emptyMessage={emptyMessage || labels?.not_found}
			emptyAction={inline ? <div style={{ marginTop: '1rem' }}><ModalAddButton namespace={namespace} collection={collection} /></div> : null}
			searchPlaceholder={labels?.search_items}
			canSelectRows={true}
			idProp="id"
			DisplayCell={DisplayCell}
			initialHiddenHeaders={hidden}
			storeName={`${namespace}/${collection}`}
		/>
	);
};

/**
 * Prepares and renders the records table.
 *
 * @returns The records table.
 */
const TheRecordsTable = withSchema( function TheRecordsTable( { namespace, collection, query, setQuery, schema, ...props } ) {
	const records = useRecords( namespace, collection, query );

	// Show error if any.
	if ( 'ERROR' === records.status ) {

		return (
			<Wrap title={__( 'Error', 'newsletter-optin-box' )}>
				<CardBody>
					<Notice status="error" isDismissible={false}>
						{records.error?.message || __( 'An unknown error occurred.', 'newsletter-optin-box' )}
					</Notice>
				</CardBody>
			</Wrap>
		);
	}

	return (
		<DisplayRecords
			schema={schema}
			records={records.data.items}
			total={records.data.total}
			summary={records.data.summary}
			isLoading={records.isResolving}
			namespace={namespace}
			collection={collection}
			updateQueryString={setQuery}
			query={query}
			{...props}
		/>
	);
} );

/**
 * Renders a records overview table for the matching path.
 *
 * @returns The records table.
 */
export default function RecordsTable() {
	const { namespace, collection } = useParams();
	const query = useQuery();

	return (
		<Flex gap={2} direction="column">
			<FlexItem>
				<FiltersButton namespace={namespace} collection={collection} query={query} setQuery={updateQueryString} />
			</FlexItem>
			<FlexBlock>
				<TheRecordsTable
					namespace={namespace}
					collection={collection}
					setQuery={updateQueryString}
					query={query}
				/>
			</FlexBlock>
		</Flex>
	);
}

/**
 * Renders a mini-records overview table for the provided path.
 *
 * @returns The records table.
 */
export const MiniRecordsTable = ( { namespace, collection, defaultProps, inline, ...props } ) => {
	const [filter, setFilter] = useState( defaultProps );
	const { id, tab } = useParams();

	return (
		<TheRecordsTable
			namespace={namespace}
			collection={collection}
			setQuery={setFilter}
			query={filter}
			inline={inline}
			basePath={inline ? `${id}/${tab}/${namespace}/${collection}/` : ''}
			{...props}
		/>
	);
};
