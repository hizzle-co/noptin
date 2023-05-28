/**
 * External dependencies
 */
import apiFetch from "@wordpress/api-fetch";
import { atom } from "jotai";
import { loadable } from "jotai/utils";
import { __ } from "@wordpress/i18n";

// Stores the current collection.
const collection = atom(null);

// Stores the current namespace.
const namespace = atom(null);

// Stores all schemas.
const schemas = {};

// Stores the current schema.
// This contains {count, schema }.
const schema = loadable( atom(async (get) => {

	// Get the current namespace.
	const currentNamespace = get(namespace);

	// Get the current collection.
	const currentCollection = get(collection);

	// Abort if the namespace or collection is not set.
	if (!currentNamespace || !currentCollection) {
		return new Error( __("The namespace or collection is not set.", "newsletter-optin-box") );
	}

	// Abort if the schema is already set.
	if ( ! schemas[currentNamespace] ) {
		schemas[currentNamespace] = {};
	}

	if ( ! schemas[currentNamespace][currentCollection] ) {
		schemas[currentNamespace][currentCollection] = await apiFetch( {
			path: `${currentNamespace}/v1/${currentCollection}/collection_schema`,
		} );
	}

	return schemas[currentNamespace][currentCollection];
}));

// Export the collection, namespace and schema.
export { collection, namespace, schema };
