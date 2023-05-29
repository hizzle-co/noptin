import { addQueryArgs, getQueryArgs } from '@wordpress/url';
import { atom } from "jotai";

// Caches the current query by path.
const queryCache = {};

// Stores the current route.
const route = atom(

	// Reads the current route from the URL.
	() => {

		// Fetch from hash.
		let url = window.location.hash;

		// Maybe remove leading hash.
		if ( '#' === url[0] ) {
			url = url.substr( 1 );
		}

		// Ensure it begins with a slash.
		if ( '/' !== url[0] ) {
			url = '/';
		}

		// Fetch the query args.
		const query = getQueryArgs( url );
		const path  = url ? url.split( '?' )[0] : '/';

		return { path, query };
	},

	// Writes the route to the URL.
	(get, set, { path, query = false }) => {

		path = path || '/';

		if ( false !== query ) {
			queryCache[path] = query;
		}

		// Set this as the URL hash.
		window.location.hash = addQueryArgs( path, queryCache[path] || {} );
	}
)

// Export the URL and route.
export { route };
