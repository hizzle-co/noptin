/**
 * External dependencies
 */
import { useMemo, useState } from "@wordpress/element";
import { Notice, CardBody } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { without } from 'lodash';

/**
 * Local dependencies.
 */
import Wrap from "../wrap";
import TableCard from "../../table";
import DisplayCell from "./display-cell";
import { useSchema, useRecords } from "../../../store-data/hooks";
import { useRoute } from "../hooks";

/**
 * Displays the records table.
 * @param {Object} props
 * @param {Object} props.schema
 * @param {Number} props.schema.count
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
export function DisplayRecords( { schema: {count, schema, hidden, ignore }, records, isLoading, updateQuery, query, extra } ) {

	// Prepare the current query.
	const { namespace, collection }     = useRoute();
	const [ hiddenCols, setHiddenCols ] = useState( hidden );

	// Make some columns from the schema.
	let rowHeader = 0;
	const columns = useMemo( () => {

		const columns = [];

		schema.forEach( ( column ) => {

			if ( column.is_primary ) {
				rowHeader = index;
			}

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
	const rows = useMemo( () => {

		if ( ! Array.isArray( records ) ) {
			return [];
		}

		return records.map( ( row ) => {

			return columns.map( ( column ) => {
				return {
					display: <DisplayCell { ...column } record={ row } />,
					value: row[column.key]
				}
			});
		});
	}, [ records, columns ] );

	return (
		<TableCard
			rows={ rows }
			headers={ columns }
			totalRows={ count }
			summary={ [] }
			isLoading={ isLoading }
			onQueryChange={ updateQuery }
			query={ query }
			className={ `${namespace}-${collection}__records-table` }
			hasSearch={ true }
			rowHeader={ rowHeader }
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
export default function RecordsTable( { component } ) {

	const { namespace, collection, args, path, navigate } = useRoute();
	const records = useRecords( namespace, collection, args );
	const schema  = useSchema( namespace, collection );

	// Show error if any.
	if ( records.hasResolutionFailed() ) {

		const error = records.getResolutionError();
		return (
			<Wrap title={ component.title }>
				<CardBody>
					<Notice status="error" isDismissible={ false }>
						{ error.message || __( 'An unknown error occurred.', 'newsletter-optin-box' ) }
					</Notice>
				</CardBody>
			</Wrap>
		);
	}

	// Updates the query.
	const updateQuery = ( newQuery ) => {

		// If we're not updating the page, reset it.
		if ( ! newQuery.paged ) {
			newQuery.paged = 1;
		}

		navigate( path, { ...args, ...newQuery } );
	}

	return (
		<DisplayRecords
			schema={ schema.data }
			records={ records.data }
			isLoading={ records.isResolving() }
			updateQuery={ updateQuery }
			query={ args }
			extra={ component }
		/>
	);
}
