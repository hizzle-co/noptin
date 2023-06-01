/**
 * Internal dependencies
 */
import { DEFAULT_STATE } from './default';

/**
 * The reducer for the store data
 */
export const reducer = (state = DEFAULT_STATE, action) => {

	switch (action.type) {

		case 'SET_SCHEMA':
			return { ...state, schema: action.schema };

		case 'SET_RECORDS':

			// Key records by ID for easy access.
			const cachedRecords = { ...state.record };

			const newRecords = action.records.reduce((records, record) => {
				records[record.id] = record;

				if ( ! cachedRecords[ record.id ] ) {
					cachedRecords[ record.id ] = record;
				}

				return records;
			}, {});

			const queryString = '' === action.queryString ? 'all' : action.queryString;

			return {
				...state,
				records: {
					...state.records,
					record: cachedRecords,
					[ queryString ]: newRecords,
				},
			};

		case 'SET_RECORD':
			return {
				...state,
				record: {
					...state.record,
					[ action.record.id ]: action.record,
				},
			};

		case 'EDIT_RECORD':
			return {
				...state,
				editedRecords: {
					...state.editedRecords,
					[ action.id ]: {
						...state.editedRecords[ action.id ],
						...action.data,
					},
				},
			};

		case 'SET_TAB_CONTENT':
			return {
				...state,
				tabContent: {
					...state.tabContent,
					[ `${action.id}_${action.tab_id}` ]: action.content,
				},
			};

	}
	return state;
}
