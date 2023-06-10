/**
 * External dependencies
 */
import { getQueryArg } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { DEFAULT_STATE } from './default';

/**
 * Retrieves record IDs.
 *
 * @param {String} queryString
 * @return {Array|null} Records.
 */
export const getRecordIDs = ( state = DEFAULT_STATE, queryString ) => {

	queryString = '' === queryString ? 'all' : queryString;

	// Check if records are already loaded.
	if ( Array.isArray( state.recordIDs[ queryString ]?.items ) ) {
		return state.recordIDs[ queryString ].items;
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
	const total = state.recordIDs[ queryString ]?.total;

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
	const summary = state.recordIDs[ queryString ]?.summary;

	return summary ? summary : {};
}

/**
 * Retrieves records.
 *
 * @param {String} queryString
 * @return {Array|null} Records.
 */
export const getRecords = ( state = DEFAULT_STATE, queryString ) => {

	queryString   = '' === queryString ? 'all' : queryString;
	const _fields = getQueryArg( queryString, '_fields' );

	if ( _fields ) {
		return Array.isArray( state.partialRecords[ queryString ]?.items ) ? state.partialRecords[ queryString ]?.items : [];
	}

	// Check if records are already loaded.
	if ( ! Array.isArray( state.recordIDs[ queryString ]?.items ) ) {
		return [];
	}

	// Loop through records to find the record.
	return state.recordIDs[ queryString ].items.map( id => state.records[ id ] );
}

/**
 * Retrieves a record.
 *
 * @param {string} id
 * @return {Object|null} Record.
 */
export const getRecord = ( state = DEFAULT_STATE, id ) => state.records[ id ] || null;

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
