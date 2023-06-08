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
	if ( Array.isArray( state.recordIDs[ queryString ] ) ) {
		return state.recordIDs[ queryString ];
	}

	return [];
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
		return Array.isArray( state.partialRecords[ queryString ] ) ? state.partialRecords[ queryString ] : [];
	}

	// Check if records are already loaded.
	if ( ! Array.isArray( state.recordIDs[ queryString ] ) ) {
		return [];
	}

	// Loop through records to find the record.
	return state.recordIDs[ queryString ].map( id => state.records[ id ] );
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
export const getRecordOverview = ( state = DEFAULT_STATE, id ) => state.recordOverview[ id ] || {};
