export {apiFetch} from '@wordpress/data-controls';

/**
 * Sets partial records.
 *
 * @param {Array} records
 * @param {string} queryString
 * @return {Object} Action.
 */
export const setPartialRecords = ( records, queryString ) => ( {
	type: 'SET_PARTIAL_RECORDS',
	records,
	queryString,
} );

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
 * Before deleting a record.
 *
 * @param {string} id
 * @return {Object} Action.
 */
export const beforeDeleteRecord = ( id ) => ( {
	type: 'BEFORE_DELETE_RECORD',
	id,
} );

/**
 * Sets a single record's schema.
 *
 * @param {string} id
 * @param {string} tab_id
 * @param {Object} content
 * @return {Object} Action.
 */
export const setTabContent = ( id, tab_id, content ) => ( {
	type: 'SET_TAB_CONTENT',
	id,
	tab_id,
	content,
} );
