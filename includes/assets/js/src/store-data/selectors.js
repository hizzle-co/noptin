/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Retrieves records.
 *
 * @param {Object} query
 * @return {Array|null} Records.
 */
export const getRecords = ( state, query = {} ) => {

	// Convert query to string.
	const queryString = addQueryArgs( '', query );

	// Check if records are already loaded.
	if ( state.records[ queryString ] ) {
		return Object.values( state.records[ queryString ] );
	}

	return null;
}

/**
 * Retrieves a record.
 *
 * @param {string} id
 * @return {Object|null} Record.
 */
export const getRecord = ( state, id ) => {

	// Check if record is already loaded.
	if ( state.record[ id ] ) {
		return state.record[ id ];
	}

	// Loop through records to find the record.
	for ( const records of Object.values( state.records ) ) {
		if ( records[ id ] ) {
			return records[ id ];
		}
	}

	// Record not found.
	return null;
}

/**
 * Retrieves the schema for the collection.
 *
 * @return {Object|null} schema.
 */
export const getSchema = ( state ) => state.schema || {};

/**
 * Retrieves the schema for a single record.
 *
 * @param {string} id
 */
export const getRecordSchema = ( state, id ) => state.recordSchema[ id ] || {};
