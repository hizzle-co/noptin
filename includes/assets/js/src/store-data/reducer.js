/**
 * Internal dependencies
 */
import { DEFAULT_STATE } from './default';

/**
 * The reducer for the store data
 */
export const reducer = (state = DEFAULT_STATE, action) => {

	switch (action.type) {

		/**
		 * Sets the collection schema.
		 */
		case 'SET_SCHEMA':
			return { ...state, schema: action.schema };

		/**
		 * Sets record IDs keyed by the query, e.g.: { "page=1": [ 1, 2, 3 ] },
		 * and records keyed by ID, e.g.: { 1: { id: 1, ... } }
		 */
		case 'SET_RECORDS':

			// Prepare constants.
			const queryString   = '' === action.queryString ? 'all' : action.queryString;
			const cachedRecords = { ...state.records };
			const recordIds     = [];

			// Loop through the records and add them to the cache.
			action.records.forEach((record) => {
				cachedRecords[ record.id ] = record;
				recordIds.push( record.id );
			});

			return {
				...state,
				records: cachedRecords,
				recordIDs: {
					...state.recordIDs,
					[ queryString ]: recordIds,
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
				const index = recordIDsBeforeDelete[ queryString ].indexOf( action.id );
			
				if ( -1 !== index ) {
					recordIDsBeforeDelete[ queryString ].splice( index, 1 );
				}
			});

			return { ...state, recordIDs: recordIDsBeforeDelete };

		/**
		 * Deletes a record keyed by ID, e.g.: { 1: { id: 1, ... } }
		 */
		case 'DELETE_RECORD':
			const records   = { ...state.records };
			delete records[ action.id ];
			return { ...state, records, recordIDs };

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

	}

	// Return the state.
	return state;
}
