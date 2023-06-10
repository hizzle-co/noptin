/**
 * Internal dependencies
 */
import { DEFAULT_STATE } from './default';

/**
 * The reducer for the store data
 */
export const reducer = (state = DEFAULT_STATE, action) => {
	const queryString = '' === action.queryString ? 'all' : action.queryString;

	switch (action.type) {

		/**
		 * Sets the collection schema.
		 */
		case 'SET_SCHEMA':
			return { ...state, schema: action.schema };

		/**
		 * Sets partial records keyed by query string, e.g.: { "page=1": { 1: { id: 1, ... } } }
		 */
		case 'SET_PARTIAL_RECORDS':

			return {
				...state,
				partialRecords: {
					...state.partialRecords,
					[ queryString ]: action.records,
				},
			};

		/**
		 * Sets record IDs keyed by the query, e.g.: { "page=1": { items: [ 1, 2, 3 ], summary: {}, total: 3 } },
		 * and records keyed by ID, e.g.: { 1: { id: 1, ... } }
		 */
		case 'SET_RECORDS':

			// Prepare constants.
			const cachedRecords = { ...state.records };
			const recordIds     = [];
			const summary       = action.records.summary;
			const total         = action.records.total;

			// Loop through the records and add them to the cache.
			action.records.items.forEach((record) => {
				cachedRecords[ record.id ] = record;
				recordIds.push( record.id );
			});

			return {
				...state,
				records: cachedRecords,
				recordIDs: {
					...state.recordIDs,
					[ queryString ]: {
						items: recordIds,
						summary,
						total,
					},
				},
			};

		/**
		 * Sets a record keyed by ID, e.g.: { 1: { id: 1, ... } }
		 */
		case 'SET_RECORD':
			return {
				...state,
				records: {
					...state.records,
					[ action.record.id ]: action.record,
				},
			};

		/**
		 * Before deleting a record, we need to remove it from the record IDs.
		 */
		case 'BEFORE_DELETE_RECORD':
			const recordIDsBeforeDelete = { ...state.recordIDs };

			// Loop through the record IDs and remove the deleted record.
			Object.keys( recordIDsBeforeDelete ).forEach((queryString) => {
				const index = recordIDsBeforeDelete[ queryString ].items.indexOf( action.id );
			
				if ( -1 !== index ) {
					recordIDsBeforeDelete[ queryString ].items.splice( index, 1 );
					recordIDsBeforeDelete[ queryString ].total -= 1;
				}
			});

			return { ...state, recordIDs: recordIDsBeforeDelete };

		/**
		 * Deletes a record keyed by ID, e.g.: { 1: { id: 1, ... } }
		 */
		case 'DELETE_RECORD':
			const records   = { ...state.records };
			delete records[ action.id ];
			return { ...state, records };

		/**
		 * Empty caches when deleting multiple records.
		 */
		case 'DELETE_RECORDS':
			return {
				...state,
				records: {},
				recordIDs: {},
				tabContent: {},
				recordOverview: {},
				partialRecords: {},
			};

		/**
		 * Set tab content key by subscriber ID and tab name, e.g.: { 1_overview:{} } }
		 */
		case 'SET_TAB_CONTENT':
			return {
				...state,
				tabContent: {
					...state.tabContent,
					[ `${action.id}_${action.tab_id}` ]: action.content,
				},
			};

		/**
		 * Set record overview keyed by the record ID.
		 */
		case 'SET_RECORD_OVERVIEW':
			return {
				...state,
				recordOverview: {
					...state.recordOverview,
					[ action.id ]: action.overview,
				},
			};
	}

	// Return the state.
	return state;
}
