/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import { DEFAULT_STATE } from './default';

const defaultRecords =  {
	items: [],
	summary: {},
	total: 0,
};

/**
 * Retrieves record IDs.
 *
 * @param {String} queryString
 * @return {Array|null} Records.
 */
export const getRecordIDs = ( state = DEFAULT_STATE, queryString ) => {

	queryString = '' === queryString ? 'all' : queryString;

	// Check if records are already loaded.
	if ( Array.isArray( state.records.queries[ queryString ]?.items ) ) {
		return state.records.queries[ queryString ].items;
	}

	return [];
}

/**
 * Retrieves query total.
 *
 * @param {String} queryString
 * @return {Number} Total Records.
 */
export const getQueryTotal = ( state = DEFAULT_STATE, queryString ) => {

	queryString = '' === queryString ? 'all' : queryString;
	const total = state.records.queries[ queryString ]?.total;

	return total ? total : 0;
}

/**
 * Retrieves query summary.
 *
 * @param {String} queryString
 * @return {Object} Summary.
 */
export const getQuerySummary = ( state = DEFAULT_STATE, queryString ) => {

	queryString = '' === queryString ? 'all' : queryString;
	const summary = state.records.queries[ queryString ]?.summary;

	return summary ? summary : {};
}

/**
 * Retrieves records.
 *
 * @param {String} queryString
 * @return {Object} Records.
 */
export const getRecords = createSelector(
	( state = DEFAULT_STATE, queryString ) => {

		queryString = '' === queryString ? 'all' : queryString;
		const results = state.records.queries[ queryString ] ?? defaultRecords;

		// Loop through records to find the record.
		return {
			items: results.items.map( id => state.records.byID[ id ] ),
			summary: results.summary,
			total: results.total,
		};
	},
	( state = DEFAULT_STATE, queryString ) => [
		state.records.queries[ '' === queryString ? 'all' : queryString ],
		state.records.byId,
	]
);

/**
 * Retrieves partial records.
 *
 * @param {String} queryString
 * @return {Array} Records.
 */
export const getPartialRecords = ( state = DEFAULT_STATE, queryString ) => {

	queryString = '' === queryString ? 'all' : queryString;

	return Array.isArray( state.partialRecords[ queryString ] ) ? state.partialRecords[ queryString ] : [];
}

/**
 * Retrieves a record.
 *
 * @param {string} id
 * @return {Object|null} Record.
 */
export const getRecord = ( state = DEFAULT_STATE, id ) => state.records.byID[ id ] || null;

/**
 * Retrieves the schema for the collection.
 *
 * @return {Object|null} schema.
 */
export const getSchema = ( state = DEFAULT_STATE ) => state.schema || {};

/**
 * Retrieves a single record tab's content.
 *
 * @param {string} id
 * @param {string} tab_id
 */
export const getTabContent = ( state = DEFAULT_STATE, id, tab_id ) => state.tabContent[ `${id}_${tab_id}` ] || {};

/**
 * Retrieves a single record's overview data.
 *
 * @param {string} id
 */
export const getRecordOverview = ( state = DEFAULT_STATE, id ) => state.recordOverview[ id ] || [];
