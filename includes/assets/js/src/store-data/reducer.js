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
			const newRecords = action.records.reduce((records, record) => {
				records[record.id] = record;
				return records;
			}, {});

			return {
				...state,
				records: {
					...state.records,
					[ action.query ]: newRecords,
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

		case 'SET_RECORD_SCHEMA':
			return {
				...state,
				recordSchema: {
					...state.recordSchema,
					[ action.id ]: action.schema,
				},
			};

	}
	return state;
}
