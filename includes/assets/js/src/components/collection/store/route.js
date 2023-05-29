import { addQueryArgs, getQueryArg, getQueryArgs } from '@wordpress/url';
import { atom } from "jotai";

// Contains the current URL.
const url = atom(window.location.href);

// Stores the current route.
const route = atom(

	// Reads the current route from the URL.
	( get ) => {

		const queryArg = decodeURI( getQueryArg( get( url ), 'hizzle_route' ) );
		const path     = queryArg ? queryArg.split( '?' )[0] : '/';
		const query    = path ? getQueryArgs( path ) : {};
console.log( query );
		return { path, query };
	},

	// Writes the route to the URL.
	(get, set, { path, query }) => {

		path  = path || '/';
		query = query || {};

		// Update the URL.
		const hizzle_route = addQueryArgs( path, query );
		const fullURL      = addQueryArgs( window.location.href, { hizzle_route } );

		// Update the URL.
		window.history.pushState( null, null, fullURL );

	}
)

// Export the URL and route.
export { url, route };
