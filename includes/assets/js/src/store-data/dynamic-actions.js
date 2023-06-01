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
				yield dispatch.invalidateResolutionForStoreSelector( 'getRecord' );

				// Finish the resolution for the getRecord selector, if id matches.
				yield dispatch.finishResolution( 'getRecord', result.id );

				// Resolve to avoid further network requests.
				yield dispatch.startResolution( 'getRecord', [ result.id ] );
				yield dispatch.finishResolution( 'getRecord', [ result.id ] );

				// Set the record.
				yield dispatch.setRecord( result );

				return {
					type: 'CREATE_RECORD',
					result
				};
			}

			return;
		},

		/**
		 * Updates a record.
		 *
		 * @param {string} id
		 * @param {Object} data
		 * @return {Object} Action.
		 */
		*updateRecord( id, data ) {
			const path   = `${namespace}/v1/${collection}/${id}`;
			const method = 'PUT';
			const result = yield apiFetch( { path, method, data } );

			if ( result ) {
				return {
					type: 'UPDATE_RECORD',
					result,
					id
				};
			}

			return;
		},

		/**
		 * Deletes a record.
		 *
		 * @param {string} id
		 * @return {Object} Action.
		 */
		*deleteRecord( id ) {
			const path   = `${namespace}/v1/${collection}/${id}`;
			const method = 'DELETE';
			const result = yield apiFetch( { path, method } );

			if ( result ) {
				return {
					type: 'DELETE_RECORD',
					id
				};
			}

			return;
		},
	}
}
