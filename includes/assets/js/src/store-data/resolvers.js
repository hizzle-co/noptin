/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { setRecords, setRecord, setSchema, setRecordSchema } from './actions';

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

            return setRecords( records, queryString );
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
         * Fetch a single record's schema from the API.
         *
         * @param {string} id
         * @return {Object} Action.
         */
        *getRecordSchema( id ) {
            const path   = `${namespace}/v1/${collection}/${id}/schema`;
            const schema = yield apiFetch( { path } );

            return setRecordSchema( id, schema );
        }
    }
}
