/**
 * External dependencies
 */
import {apiFetch} from '@wordpress/data-controls';

/**
 * Creates dynamic actions for the store.
 * @param {string} namespace The namespace.
 * @param {string} collection The collection.
 * @link https://unfoldingneurons.com/2020/wordpress-data-store-properties-action-creator-generators
 */
export default function createDynamicActions( namespace, collection ) {

	return {

		/**
		 * Creates a record.
		 *
		 * @param {Object} data
		 * @return {Object} Action.
		 */
		*createRecord( data, dispatch ) {
			const path   = `${namespace}/v1/${collection}`;
			const method = 'POST';
			const result = yield apiFetch( { path, method, data } );

			if ( result ) {

				// Invalidate the getRecords selector.
				yield dispatch.invalidateResolutionForStoreSelector( 'getRecords' );

				// Invalidate the getRecord selector.
				yield dispatch.invalidateResolution( 'getRecord', [ result.id ] );

				// Resolve to avoid further network requests.
				yield dispatch.startResolution( 'getRecord', [ result.id ] );
				yield dispatch.finishResolution( 'getRecord', [ result.id ] );

				// Set the record.
				return setRecord( result );
			}
		},

		/**
		 * Updates a record.
		 *
		 * @param {string} id
		 * @param {Object} data
		 * @return {Object} Action.
		 */
		*updateRecord( id, data, dispatch ) {
			const path   = `${namespace}/v1/${collection}/${id}`;
			const method = 'PUT';
			const result = yield apiFetch( { path, method, data } );

			if ( result ) {

				// Resolve to avoid further network requests.
				yield dispatch.startResolution( 'getRecord', [ result.id ] );
				yield dispatch.finishResolution( 'getRecord', [ result.id ] );

				// Set the record.
				return dispatch.setRecord( result );
			}
		},

		/**
		 * Deletes a record.
		 *
		 * @param {string} id
		 * @return {Object} Action.
		 */
		*deleteRecord( id, dispatch ) {

			/**
			 * Fire action before deleting record.
			 */
			yield dispatch.beforeDeleteRecord( id );

			// Delete the record.
			const path   = `${namespace}/v1/${collection}/${id}`;
			const method = 'DELETE';
			const result = yield apiFetch( { path, method } );

			if ( result ) {

				// Invalidate the getRecord selector.
				yield dispatch.invalidateResolution( 'getRecord', [ result.id ] );

				return {
					type: 'DELETE_RECORD',
					id
				};
			}
		},
	}
}