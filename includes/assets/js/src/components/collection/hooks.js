import { __experimentalUseNavigator as useNavigator } from "@wordpress/components";
import { useState, useEffect, useMemo } from "@wordpress/element";
import { getQueryArgs, getQueryArg, addQueryArgs } from '@wordpress/url';

/**
 * Resolves the current route.
 *
 * @return {Object} The record resolution.
 */
export function useRoute() {
	const { location, goTo, goBack, goToParent, params } = useNavigator();
	const [url, setURL] = useState( window.location.href );

	// Watch for hash changes.
	useEffect( () => {
		const handleRouteChange = () => {
			setURL( window.location.href );

			const newPath = getQueryArg( window.location.href, 'hizzle_path' );

			if ( newPath ) {
				goTo( newPath );
			}
		};

		window.addEventListener( 'onpopstate', handleRouteChange );
		return () => {
			window.removeEventListener( 'onpopstate', handleRouteChange );
		};
	}, [] );

	// Current query args.
	const { hizzle_path, page, ...query } = getQueryArgs( url );

	// Get current path.
	const path = hizzle_path ? hizzle_path : location.path;

	// Split the first two slashes to get namespace and collection.
	const parts      = path.split( '/' );
	const namespace  = parts[1];
	const collection = parts[2];

	// If no collection, or namespace, throw an error.
	if ( ! namespace || ! collection ) {
		throw new Error( 'Invalid route.' );
	}

	// Merge the args with the query args.
	const mergedArgs = useMemo( () => ({ ...params, ...query }), [ params, query ] );

	// Navigates to a new route.
	const navigate = ( path, args = {} ) => {
		// Ensure the path begins with a slash.
		path = '/' === path[0] ? path : '/' + path;

		goTo( path );

		const newArgs = { ...args, hizzle_path: path };
		const newURL  = addQueryArgs( window.location.href, newArgs );

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
