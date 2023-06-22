/**
 * External dependencies.
 */
import domReady from '@wordpress/dom-ready';
import {render, createRoot} from "@wordpress/element";
import { getQueryArg } from '@wordpress/url';

/**
 * Local dependencies.
 */
import { App } from './components/collection';

domReady( () => {

	// Prepare the app container.
	const app = document.getElementById( 'noptin-collection__overview-app' );

	if ( app ) {
		let defaultRoute = app.dataset.defaultRoute;

		// Check if we have a hizzle_path query arg.
		const hizzlePath = getQueryArg( window.location.search, 'hizzle_path' );

		// If it exists, ensure it has 2 slashes, example: /namespace/collection.
		if ( hizzlePath ) {
			const parts = hizzlePath.split( '/' );

			if ( parts.length > 1 ) {
				defaultRoute = hizzlePath;
			}
		}

		// React 18.
		if ( createRoot ) {
			createRoot( app ).render( <App defaultRoute={defaultRoute} /> );
		} else {
			render( <App defaultRoute={defaultRoute} />, app );
		}
	}
} );
