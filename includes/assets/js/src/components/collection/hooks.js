import { __experimentalUseNavigator as useNavigator } from "@wordpress/components";
import { useMemo, createContext, useContext } from "@wordpress/element";
import { getQueryArgs, getQueryArg, addQueryArgs } from '@wordpress/url';
import { useSchema } from "../../store-data/hooks";

/**
 * Pass the current URL via context to ensure that the URL is always up to date.
 */
export const URLContext = createContext( window.location.href );

/**
 * Catches last query args per store to ensure that the query args are always up to date.
 */
const lastQueryArgs = {};

/**
 * Adds a slash to the start of a path and removes a slash from the end.
 */
const normalizePath = ( path ) => '/' + path.replace( /^\/|\/$/g, '' );

/**
 * Checks if a given path is a root path.
 */
const isRootPath = ( path ) => 3 === normalizePath( path ).split( '/' ).length;

/**
 * Resolves the current route.
 *
 * @return {Object} The record resolution.
 */
export function useRoute() {
	const { location, goTo, goBack, goToParent, params } = useNavigator();
	const [url, setURL] = useContext( URLContext );

	// Current query args.
	const { hizzle_path, page, ...query } = getQueryArgs( url );

	// Get current path.
	const path = normalizePath( hizzle_path ? hizzle_path : location.path );

	// Split the first two slashes to get namespace and collection.
	const parts      = path.split( '/' );
	const namespace  = parts[1];
	const collection = parts[2];

	// If no collection, or namespace, throw an error.
	if ( ! namespace || ! collection ) {
		throw new Error( 'Invalid route.' );
	}

	// Maybe set last query args.
	if ( isRootPath( path ) && ! lastQueryArgs[ path ] ) {
		lastQueryArgs[ path ] = query;
	}

	// Merge the args with the query args.
	const mergedArgs = useMemo( () => ({ ...params, ...query }), [ params, query ] );

	// Navigates to a new route.
	const navigate = ( path, args = null ) => {

		// Normalize the path.
		path = normalizePath( path );

		// Maybe set last query args.
		if ( isRootPath( path ) ) {

			if ( args ) {
				lastQueryArgs[ path ] = args;
			} else if( lastQueryArgs[ path ] ) {
				args = lastQueryArgs[ path ];
			}
		}

		args = args ? args : {};

		goTo( path );

		const newArgs = { ...args, hizzle_path: path, page: getQueryArg( window.location.href, 'page' ) };
		const newURL  = addQueryArgs( window.location.href.split('?')[0], newArgs );

		setURL( newURL );
		history.pushState( null, null, newURL );
	}

	return {
		path,
		namespace,
		collection,
		args: mergedArgs,
		navigate,
		goBack,
		goToParent,
	}
}

/**
 * Resolves the current path schema.
 *
 * @return {Object} The schema resolution.
 */
export function useCurrentPath() {
	const { path, namespace, collection, args } = useRoute();
	const { data } = useSchema( namespace, collection );

	const toReturn = { path, namespace, collection, args };

	// Abort if path is not found.
	if ( ! data?.routes?.[ path ] ) {
		return {
			...toReturn,
			schema: {},
		}
	}

	return {
		...toReturn,
		schema: data?.routes?.[ path ],
	}
}
