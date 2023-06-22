/**
 * External dependencies
 */
import { combineReducers } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { DEFAULT_STATE } from './default';

/**
 * Reducer managing collection schema state.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */
export function schema( state = {}, action ) {
	switch ( action.type ) {
		case 'SET_SCHEMA':
			return action.schema;
	}

	return state;
}

/**
 * Reducer managing partial records state.
 *
 * @param {Object} state  Current state, keyed by query string.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */
export function partialRecords( state = {}, action ) {
	switch ( action.type ) {
		case 'SET_PARTIAL_RECORDS':
			const queryString = '' === action.queryString ? 'all' : action.queryString;

			return {
				...state,
				[ queryString ]: action.records,
			};
	}

	return state;
}

/**
 * Reducer managing records state. Keyed by id.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */
export function records( state = { byID: {}, queries: {} }, action ) {

	switch ( action.type ) {

		// Set multiple records.
		case 'SET_RECORDS':
			const queryString = '' === action.queryString ? 'all' : action.queryString;

			return {
				byID: {
					...state.byID,
					// Key users by their ID.
					...action.records.items.reduce(
						( newRecords, record ) => ( {
							...newRecords,
							[ record.id ]: record,
						} ),
						{}
					),
				},
				queries: {
					...state.queries,
					[ queryString ]: {
						items: action.records.items.map( ( item ) => item.id ),
						summary: action.records.summary,
						total: action.records.total,
					},
				},
			};

		// Set a single record.
		case 'SET_RECORD':
			return {
				...state,
				byID: {
					...state.byID,
					[ action.record.id ]: action.record,
				},
			};

		// Delete a single record.
		case 'BEFORE_DELETE_RECORD':

			const queries = { ...state.queries };

			// Loop through the record IDs and remove the deleted record.
			Object.keys( queries ).forEach((queryString) => {
				const index = queries[ queryString ].items.indexOf( action.id );

				if ( -1 !== index ) {
					queries[ queryString ].items.splice( index, 1 );
					queries[ queryString ].total -= 1;
				}
			});

			return { ...state, queries };

		// Deletes a single record.
		case 'DELETE_RECORD':
			const byID = { ...state.byID };
			delete byID[ action.id ];
			return { ...state, byID };

		// Delete all records.
		case 'DELETE_RECORDS':
			return {
				...state,
				queries: {},
				byID: {},
			};
	}

	return state;
}

/**
 * Reducer managing tab content state.
 *
 * @param {Object} state  Current state, keyed by record id and tab name.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */
export function tabContent( state = {}, action ) {
	switch ( action.type ) {
		case 'SET_TAB_CONTENT':
			return {
				...state,
				[ `${action.id}_${action.tab_id}` ]: action.content,
			};
	}

	return state;
}

/**
 * Reducer managing record overview state.
 *
 * @param {Object} state  Current state, keyed by record id.
 * @param {Object} action Dispatched action.
 *
 * @return {Object} Updated state.
 */
export function recordOverview( state = {}, action ) {
	switch ( action.type ) {
		case 'SET_RECORD_OVERVIEW':
			return {
				...state,
				[ action.id ]: action.overview,
			};
	}

	return state;
}

export default combineReducers( {
	schema,
	records,
	partialRecords,
	tabContent,
	recordOverview,
} );
