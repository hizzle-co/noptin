import { addQueryArgs, getQueryArgs } from '@wordpress/url';
import { atom } from "jotai";

// Caches the current query by path.
const queryCache = {};

// We need to store the hash in an atom to force updates.
const hashAtom = atom( window.location.hash );

// Stores the current route.
const route = atom(

	// Reads the current route from the URL.
	( get ) => {

		// Fetch from hash.
		let url = get( hashAtom );

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

		const hash = addQueryArgs( path, queryCache[path] || {} );

		// Set this as the URL hash.
		window.location.hash = addQueryArgs( path, queryCache[path] || {} );

		// Force an update.
		set( hashAtom, hash );

		console.log( queryCache[path] );
	}
)

// Export the URL and route.
export { route };
