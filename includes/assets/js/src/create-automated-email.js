import domReady from '@wordpress/dom-ready';
import CreateAutomatedEmail from './components/email-campaigns/create-automated-email';
import {render, createRoot, StrictMode} from "@wordpress/element";

domReady( () => {

	// Fetch rule ID and action and trigger editor div.
	const container = document.getElementById( 'noptin-create-automated-email__app' );

	if ( container ) {
		const App = (
			<StrictMode>
				<CreateAutomatedEmail />
			</StrictMode>
		)

		// React 18.
		if ( createRoot ) {
			createRoot( container ).render( App );
		} else {
			render( App, container );
		}
	}
} );
