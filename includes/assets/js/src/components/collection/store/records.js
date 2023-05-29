/**
 * External dependencies
 */
import apiFetch from "@wordpress/api-fetch";
import { atom } from "jotai";
import { loadable } from "jotai/utils";
import { __ } from "@wordpress/i18n";
import { addQueryArgs } from '@wordpress/url';

/**
 * Local dependencies.
 */
import { collection, namespace } from "./schema";
import { route } from "./route";

// Stores the current records query.
const recordsQuery = atom(

	// Reads the current route from the URL.
	( get ) => {

		const { query } = get( route );console.log( query );
		return { ...{per_page: 25, page: 1}, ...query };
	},

	// Writes the route to the URL.
	(get, set, query) => {
		const { path } = get( route );
		set( route, { path, query } );
	}
)

// Stores the current records.
const records = loadable( atom(async (get) => {

    // Get the current query.
    const currentQuery = get(recordsQuery);

	// Get the current namespace.
	const currentNamespace = get(namespace);

	// Get the current collection.
	const currentCollection = get(collection);

	// Abort if the namespace or collection is not set.
	if (!currentNamespace || !currentCollection) {
		throw new Error( __("The namespace or collection is not set.", "newsletter-optin-box") );
	}

    // Convert the query to a query args string.
    const path = addQueryArgs( `${currentNamespace}/v1/${currentCollection}`, currentQuery );

	return await apiFetch( { path } );

}));

// Export the records query and records.
export { recordsQuery, records };
