/**
 * External dependencies
 */
import { createSelector } from 'reselect';

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
 * @return {Object} Records.
 */
export const getRecordIDs = ( state = DEFAULT_STATE, queryString ) => {
	queryString = '' === queryString ? 'all' : queryString;
	return state.records.queries[ queryString ] ?? defaultRecords;
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
 * Retrieves all records.
 *
 * @return {Object} Records.
 */
export const getAllRecords = ( state = DEFAULT_STATE ) => state.records.byID || {};

/**
 * Retrieves matching records for the specified query.
 */
/**
 * Retrieves records.
 *
 * @param {String} queryString
 * @return {Object} Records.
 */
export const getRecords = createSelector(
	getRecordIDs,
	getAllRecords,
	( recordIds, allRecords ) => {
		// Loop through records to find the record.
		return {
			...recordIds,
			items: recordIds.items.map( id => allRecords[ id ] ),
		};
	}
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
