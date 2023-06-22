/**
 * External dependencies
 */
import {
	useState,
	useEffect,
	useLayoutEffect,
} from '@wordpress/element';
import { addQueryArgs, getQueryArgs } from '@wordpress/url';
import { Slot, Fill } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getHistory } from './history';

// Expose history so all uses get the same history object.
export { getHistory };

// Export all filter utilities
export * from './filters';

/**
 * Get the current path from history.
 *
 * @return {string}  Current path.
 */
export const getPath = () => getHistory().location.pathname;

/**
 * Get the current query string, parsed into an object, from history.
 *
 * @return {Object}  Current query object, defaults to empty object.
 */
export function getQuery() {
	const search = getHistory().location.search;
	if ( search.length ) {
		return getQueryArgs( search ) || {};
	}
	return {};
}

/**
 * Return a URL with set query parameters.
 *
 * @param {Object} query object of params to be updated.
 * @param {string} path  Relative path (defaults to current path).
 * @return {string}  Updated URL merging query params into existing params.
 */
export function getNewPath(
	query,
	path = getPath(),
	currentQuery = getQuery(),
) {
	const args = { ...currentQuery, ...query };
	if ( path !== '/' ) {
		args.hizzle_path = path;
	}

	return addQueryArgs( 'admin.php', args );
}

/**
 * Updates the query parameters of the current page.
 *
 * @param {Object} query        object of params to be updated.
 * @param {string} path         Relative path (defaults to current path).
 * @param {Object} currentQuery object of current query params (defaults to current querystring).
 */
export function updateQueryString(
	query,
	path = getPath(),
	currentQuery = getQuery(),
) {
	const newPath = getNewPath( query, path, currentQuery );
	getHistory().push( newPath );
}

/**
 * Adds a listener that runs on history change.
 *
 * @param {Function} listener Listener to add on history change.
 * @return {Function} Function to remove listeners.
 */
export const addHistoryListener = ( listener ) => {
	// Monkey patch pushState to allow trigger the pushstate event listener.

	window.hizzleNavigation = window.hizzleNavigation ?? {};

	if ( ! window.hizzleNavigation.historyPatched ) {
		( ( history ) => {
			/* global CustomEvent */
			const pushState = history.pushState;
			const replaceState = history.replaceState;
			history.pushState = function ( state ) {
				const pushStateEvent = new CustomEvent( 'pushstate', {
					state,
				} );
				window.dispatchEvent( pushStateEvent );
				return pushState.apply( history, arguments );
			};
			history.replaceState = function ( state ) {
				const replaceStateEvent = new CustomEvent( 'replacestate', {
					state,
				} );
				window.dispatchEvent( replaceStateEvent );
				return replaceState.apply( history, arguments );
			};

			window.hizzleNavigation.historyPatched = true;
		} )( window.history );
	}

	window.addEventListener( 'popstate', listener );
	window.addEventListener( 'pushstate', listener );
	window.addEventListener( 'replacestate', listener );

	return () => {
		window.removeEventListener( 'popstate', listener );
		window.removeEventListener( 'pushstate', listener );
		window.removeEventListener( 'replacestate', listener );
	};
};

/**
 * Like getQuery but in useHook format for easy usage in React functional components
 *
 * @return {Record<string, string>} Current query object, defaults to empty object.
 */
export const useQuery = () => {
	const [ queryState, setQueryState ] = useState( {} );
	const [ locationChanged, setLocationChanged ] = useState( true );
	useLayoutEffect( () => {
		return addHistoryListener( () => {
			setLocationChanged( true );
		} );
	}, [] );

	useEffect( () => {
		if ( locationChanged ) {
			const query = getQuery();
			setQueryState( query );
			setLocationChanged( false );
		}
	}, [ locationChanged ] );
	return queryState;
};

/**
 * This function returns an event handler for the given `param`
 *
 * @param {string} param The parameter in the querystring which should be updated (ex `paged`, `per_page`)
 * @param {string} path  Relative path (defaults to current path).
 * @param {string} query object of current query params (defaults to current querystring).
 * @return {Function} A callback which will update `param` to the passed value when called.
 */
export function onQueryChange( param, path = getPath(), query = getQuery() ) {
	switch ( param ) {
		case 'sort':
			return ( key, dir ) =>
				updateQueryString( { orderby: key, order: dir }, path, query );
		default:
			return ( value ) =>
				updateQueryString( { [ param ]: value }, path, query );
	}
}

/**
 * A utility function that navigates to a page.
 *
 * @param {Object} args     - All arguments.
 * @param {string} args.url - Relative path or absolute url to navigate to
 */
export const navigateTo = ( url ) => {

	// Update the URL.
	getHistory().push( addQueryArgs( 'admin.php', getQueryArgs( url ) ) );

	// Scroll to the top.
	window.scrollTo( { top: 0, behavior: 'smooth' } );

};

/**
 * A Fill for extensions to add client facing custom Navigation Items.
 *
 * @slotFill HizzleNavigationItem
 * @param {Object} props          React props.
 * @param {Array}  props.children Node children.
 * @param {string} props.item     Navigation item slug.
 */
export const HizzleNavigationItem = ( { children, item } ) => {
	return <Fill name={ 'hizzle_navigation_' + item }>{ children }</Fill>;
};

HizzleNavigationItem.Slot = ( { name } ) => (
	<Slot name={ 'hizzle_navigation_' + name } />
);
