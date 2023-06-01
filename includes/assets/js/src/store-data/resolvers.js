/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';
import { controls } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { setRecords, setRecord, setSchema, setTabContent } from './actions';

/**
 * Creates resolvers for the store.
 * @param {string} namespace The namespace.
 * @param {string} collection The collection.
 * @link https://unfoldingneurons.com/2020/wordpress-data-store-properties-resolvers
 */
export default function createResolvers( namespace, collection ) {

	return {

		/**
		 * Fetches the records from the API.
		 *
		 * @param {String} queryString
		 * @return {Object} Action.
		 */
		*getRecords( queryString ) {
			const path    = `${namespace}/v1/${collection}${queryString}`;
			const records = yield apiFetch( { path } );

			if ( records ) {

				// Resolve each record to avoid further network requests.
				const STORE_NAME = `${namespace}/${collection}`;

				// Resolve to avoid further network requests.
				const resolutionsArgs = records.map( ( record ) => [ record.id ] );

				yield controls.dispatch(
					STORE_NAME,
					'startResolutions',
					'getRecord',
					resolutionsArgs
				);

				yield controls.dispatch(
					STORE_NAME,
					'finishResolutions',
					'getRecord',
					resolutionsArgs
				);

				return setRecords( records, queryString );
			}

			return setRecords( [], queryString );
		},

		/**
		 * Fetches a record from the API.
		 *
		 * @param {string} id
		 * @return {Object} Action.
		 */
		*getRecord( id ) {
			const path   = `${namespace}/v1/${collection}/${id}`;
			const record = yield apiFetch( { path } );

			return setRecord( record );
		},

		/**
		 * Fetch the collection schema from the API.
		 *
		 * @return {Object} Action.
		 */
		*getSchema() {
			const path   = `${namespace}/v1/${collection}/collection_schema`;
			const schema = yield apiFetch( { path } );

			return setSchema( schema );
		},

		/**
		 * Fetch a single record tab's content from the API.
		 *
		 * @param {string} id
		 * @param {string} tab_id
		 * @return {Object} Action.
		 */
		*getTabContent( id, tab_id ) {
			const path    = `${namespace}/v1/${collection}/${id}/${tab_id}`;
			const content = yield apiFetch( { path } );

			return setTabContent( id, tab_id, content );
		}
	}
}
