/**
 * External dependencies.
 */
import domReady from '@wordpress/dom-ready';
import {render, createRoot, StrictMode} from "@wordpress/element";

/**
 * Local dependencies.
 */
import Table from './components/records-table';

domReady( () => {

	// Fetch rule ID and action and trigger editor div.
	const app = document.getElementById( 'noptin-records__overview-app' );

	if ( app ) {
		const data = {...app.dataset}

		const Overview = (
			<StrictMode>
				<Table {...data} />
			</StrictMode>
		)

		// React 18.
		if ( createRoot ) {
			createRoot( app ).render( Overview );
		} else {
			render( Overview, app );
		}
	}
} );
