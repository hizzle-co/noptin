/**
 * External dependencies.
 */
import domReady from '@wordpress/dom-ready';
import {render, createRoot} from "@wordpress/element";

/**
 * Local dependencies.
 */
import Collection from './components/collection';

domReady( () => {

	// Prepare the app container.
	const app = document.getElementById( 'noptin-collection__overview-app' );

	if ( app ) {
		const config = app.dataset.config;
		const data = {};

		// Parse the config.
		try {
			data.config = JSON.parse( config );
		} catch ( e ) {
			data.config = {};
		}

		// React 18.
		if ( createRoot ) {
			createRoot( app ).render( <Collection {...data.config} /> );
		} else {
			render( <Collection {...data.config} />, app );
		}
	}
} );
