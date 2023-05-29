/**
 * External dependencies
 */
import { useMemo, useState } from "@wordpress/element";
import { Notice, Spinner, CardBody } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useAtom, useAtomValue } from "jotai";
import { without } from 'lodash';

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
 * @param {Array} props.schema.ignore
 * @param {Array} props.schema.hidden
 * @param {Object} props.records
 * @param {String} props.records.state
 * @param {Array} props.records.data
 * @param {Object} props.extra
 * @returns {JSX.Element}
 */
export function DisplayRecords( { schema: {count, schema, hidden, ignore }, records: { state, data }, extra } ) {

	// Prepare the current query.
	const [route, setRoute]             = useAtom( store.route );
	const collection                    = useAtomValue( store.collection );
	const namespace                     = useAtomValue( store.namespace );
	const [ hiddenCols, setHiddenCols ] = useState( hidden );

	// Updates the query.
	const updateQuery = ( newQuery ) => {
		const { path, query } = route;

		// If we're not updating the page, reset it.
		if ( ! newQuery.page ) {
			newQuery.page = 1;
		}

		setRoute( { path, query: { ...query, ...newQuery } } );
	}

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
				visible: ! hiddenCols.includes( column.name ),
				isSortable: ! column.is_dynamic,
				isNumeric: column.is_numeric || column.is_float,
				...column
			});
		} );

		return columns;
	}, [ schema, hiddenCols ] );

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
			onQueryChange={ updateQuery }
			query={ route.query }
			className={ `${namespace}-${collection}__records-table` }
			hasSearch={ true }
			toggleHiddenCol={ ( col ) => {

				if ( hiddenCols.includes( col ) ) {
					setHiddenCols( without( hiddenCols, col ) );
				} else {
					setHiddenCols( [ ...hiddenCols, col ] );
				}
			} }
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
