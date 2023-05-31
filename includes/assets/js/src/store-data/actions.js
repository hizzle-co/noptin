export {apiFetch} from '@wordpress/data-controls';

/**
 * Sets new records.
 *
 * @param {Array} records
 * @param {string} queryString
 * @return {Object} Action.
 */
export const setRecords = ( records, queryString ) => ( {
	type: 'SET_RECORDS',
	records,
	queryString,
} );

/**
 * Sets a new record.
 *
 * @param {Object} record
 * @return {Object} Action.
 */
export const setRecord = ( record ) => ( {
	type: 'SET_RECORD',
	record,
} );

/**
 * Edits a record.
 *
 * @param {string} id
 * @param {Object} data
 */
export const editRecord = ( id, data ) => ( {
	type: 'EDIT_RECORD',
	id,
	data,
} );

/**
 * Sets the collection schema.
 *
 * @param {Object} schema
 * @return {Object} Action.
 */
export const setSchema = ( schema ) => ( {
	type: 'SET_SCHEMA',
	schema,
} );

/**
 * Sets a single record's schema.
 *
 * @param {string} id
 * @param {Object} schema
 * @return {Object} Action.
 */
export const setRecordSchema = ( id, schema ) => ( {
	type: 'SET_RECORD_SCHEMA',
	schema,
	id,
} );
