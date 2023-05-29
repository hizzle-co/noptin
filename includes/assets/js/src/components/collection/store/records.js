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

// Stores the current records.
const records = loadable( atom(async (get) => {

    // Get the current query.
    const { query } = get(route);

	// Get the current namespace.
	const currentNamespace = get(namespace);

	// Get the current collection.
	const currentCollection = get(collection);

	// Abort if the namespace or collection is not set.
	if (!currentNamespace || !currentCollection) {
		throw new Error( __("The namespace or collection is not set.", "newsletter-optin-box") );
	}

    // Convert the query to a query args string.
    const path = addQueryArgs( `${currentNamespace}/v1/${currentCollection}`, query );

	return await apiFetch({ path });
}));

// Export the records query and records.
export { records };
