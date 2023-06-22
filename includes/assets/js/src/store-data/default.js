/**
 * Default store data.
 */
export const DEFAULT_STATE = {

	/**
	 * Contains the records keyed by ID, e.g.: { 1: { id: 1, ... } }
	 */
	records: {

		/**
		 * Record data keyed by ID.
		 */
		byID: {},

		/**
		 * Record IDs keyed by query string, e.g.: { "page=1": { items: [ 1, 2, 3 ], summary: {}, total: 3 } }
		 */
		queries: {},
	},

	/**
	 * Contains partial records keyed by query string, e.g.: { "page=1": [ { id: 1, ... } ] }
	 */
	partialRecords: {},

	/**
	 * Contains the collection schema.
	 */
	schema: {},

	/**
	 * Contains tab content key by record ID and tab name, e.g.: { 1: { overview: <p>...</p> } }
	 */
	tabContent: {},

	/**
	 * Record overview keyed by the record ID.
	 */
	recordOverview: {},
};
