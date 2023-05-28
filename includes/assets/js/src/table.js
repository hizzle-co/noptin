/**
 * External dependencies.
 */
import domReady from '@wordpress/dom-ready';
import {render, createRoot} from "@wordpress/element";
import { Provider } from "jotai";

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

		const TheApp = (
			<Provider>
				<Collection {...data.config} />
			</Provider>
		);

		// React 18.
		if ( createRoot ) {
			createRoot( app ).render( TheApp );
		} else {
			render( TheApp, app );
		}
	}
} );
