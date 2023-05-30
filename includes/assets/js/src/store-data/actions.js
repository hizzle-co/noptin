export {apiFetch} from '@wordpress/data-controls';

/**
 * Sets new records.
 *
 * @param {Array} records
 * @param {string} query
 * @return {Object} Action.
 */
export const setRecords = ( records, query ) => ( {
	type: 'SET_RECORDS',
	records,
	query,
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
