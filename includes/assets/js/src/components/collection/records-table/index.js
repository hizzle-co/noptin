/**
 * External dependencies
 */
import { useMemo } from "@wordpress/element";
import { Notice, Spinner, CardBody } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useAtom, useAtomValue } from "jotai";

/**
 * Local dependencies.
 */
import * as store from "../store";
import Wrap from "../wrap";
import TableCard from "../../table";
import DisplayCell from "./display-cell";

/**
 * Displays the records table.
 * @param {Object} props
 * @param {Object} props.schema
 * @param {Number} props.schema.count
 * @param {Array} props.schema.schema
 * @param {Object} props.records
 * @param {String} props.records.state
 * @param {Array} props.records.data
 * @param {Object} props.extra
 * @returns {JSX.Element}
 */
export function DisplayRecords( { schema: {count, schema }, records: { state, data }, extra } ) {

	// Prepare the current query.
	const [query, setQuery] = useAtom( store.recordsQuery );
	const collection        = useAtomValue( store.collection );
	const namespace         = useAtomValue( store.namespace );
	const [route, setRoute] = useAtom( store.route );

	// Make some columns from the schema.
	const columns = useMemo( () => {

		const columns = [];

		schema.forEach( ( column ) => {

			// Abort if dynamic column.
			if ( column.is_dynamic ) {
				return;
			}

			columns.push( {
				key: column.name,
				visible: ! column.is_dynamic && 'id' !== column.name,
				isSortable: ! column.is_dynamic,
				isNumeric: column.is_numeric || column.is_float,
				...column
			});
		} );

		return columns;
	}, [ schema ] );

	// Convert records into data array.
	const records = useMemo( () => {

		if ( ! Array.isArray( data ) ) {
			return [];
		}

		return data.map( ( row ) => {

			return columns.map( ( column ) => {
				return {
					display: <DisplayCell { ...column } record={ row } />,
					value: row[column.key]
				}
			});
		});
	}, [ data, columns ] );

	return (
		<TableCard
			rows={ records }
			headers={ columns }
			totalRows={ count }
			summary={ [] }
			isLoading={ state === 'loading' }
			onQueryChange={ setQuery }
			query={ query }
			className={ `${namespace}-${collection}__records-table` }
			{ ...extra }
		/>
	);
}

/**
 * Renders a records overview table for the matching path.
 *
 * @returns The records table.
 */
export default function RecordsTable( { path, component } ) {

	const schema  = useAtomValue( store.schema );
	const records = useAtomValue( store.records );

	// Show error if any.
	if ( schema.state === 'hasError' || records.state === 'hasError' ) {
		const theError = schema.state === 'hasError' ? schema.error : records.error;

		return (
			<Wrap title={ component.title }>
				<CardBody>
					<Notice status="error" isDismissible={ false }>
						<strong>{ __( 'Error:', 'newsletter-optin-box' ) }</strong>&nbsp;
						{ theError.message }
					</Notice>
				</CardBody>
			</Wrap>
		);
	}

	// Show the loading indicator if we're loading the schema.
	if ( schema.state === 'loading' ) {

		return (
			<Wrap title={ component.title }>
				<CardBody>
					<Spinner />
				</CardBody>
			</Wrap>
		);
	}

	return <DisplayRecords schema={ schema.data } records={ records } path={ path } extra={ component } />;
}
