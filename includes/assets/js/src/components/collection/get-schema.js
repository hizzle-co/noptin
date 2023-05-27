/**
 * External dependencies
 */
import apiFetch from "@wordpress/api-fetch";

const schemas = {};

/**
 * Fetches a collection schema.
 * @param {string} namespace
 * @param {string} collection
 * @returns {Promise}
 * @throws {Error}
 */
export async function getSchema( namespace, collection ) {
    if ( ! schemas[namespace] ) {
        schemas[namespace] = {};
    }

    if ( ! schemas[namespace][collection] ) {
        schemas[namespace][collection] = await apiFetch( {
            path: `${namespace}/v1/${collection}/collection_schema`,
        } );
    }

    return schemas[namespace][collection];
}
