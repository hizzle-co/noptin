/**
 * Default store data.
 */
export const DEFAULT_STATE = {

	/**
	 * Contains record IDs keyed by the query, e.g.: { "page=1": [ 1, 2, 3 ] }
	 */
	recordIDs: {},

	/**
	 * Contains the records keyed by ID, e.g.: { 1: { id: 1, ... } }
	 */
	records: {},

	/**
	 * Contains partial records keyed by query string, e.g.: { "page=1": { 1: { id: 1, ... } } }
	 */
	partialRecords: {},

	/**
	 * Contains the collection schema.
	 */
	schema: {},

	/**
	 * Contains tab content key by subscriber ID and tab name, e.g.: { 1: { overview: <p>...</p> } }
	 */
	tabContent: {},
};
