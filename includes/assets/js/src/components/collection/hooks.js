import { useSchema, useRecords, useRecord } from "../../store-data/hooks";
import { useParams } from 'react-router-dom';
import { getQuery, getNewPath, navigateTo } from "../navigation";

/**
 * Returns a function to navigate back home.
 *
 * @returns {Function}
 */
export function useNavigateCollection() {
	// Get the collection and namespace from the URL.
    const { namespace, collection } = useParams();
	return ( suffix = '' ) => navigateTo( getNewPath( {}, `/${namespace}/${collection}/${suffix}` ) );
}

/**
 * Allows components to use the current collection's schema.
 *
 * @returns {ReturnType<useSchema>}
 */
export function useCurrentSchema() {
	// Get the collection and namespace from the URL.
    const { namespace, collection } = useParams();

	return useSchema( namespace, collection );
}

/**
 * Allows components to use the current collection's record.
 *
 * @returns {ReturnType<useRecord>}
 */
export function useCurrentRecord() {

	// Get the collection and namespace from the URL.
    const { namespace, collection, id } = useParams();

	// Get the records.
    return useRecord( namespace, collection, id );
}

/**
 * Allows components to use the current collection's records.
 *
 * @returns {ReturnType<useRecords>}
 */
export function useCurrentRecords() {

	// Get the collection and namespace from the URL.
    const { namespace, collection } = useParams();

	// Get the records.
    return useRecords( namespace, collection, getQuery() );
}

/**
 * Allows components to use the current collection's total query records.
 *
 * @returns {number}
 */
export function useCurrentQueryRecordCount() {
	const records = useCurrentRecords();
	return records.data.total || 0;
}

/**
 * Returns a query for bulk actions.
 * @param {Array} selected
 * @returns {Object}
 */
export function useQueryOrSelected( selected ) {
	if ( selected.length > 0 ) {
		return { include: selected.join( ',' ), number: -1 };
	}

	const query = { ...getQuery(), number: -1 };;

	[ 'order', 'hizzle_path', 'orderby', 'paged', 'page' ].forEach( ( key ) => {
		delete query[ key ];
	} );
	
	return query;
}
