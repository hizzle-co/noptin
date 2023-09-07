import domReady from '@wordpress/dom-ready';
import AutomationRuleEditor from './components/automation-rules/editor';
import {render, createRoot, StrictMode} from "@wordpress/element";
import ErrorBoundary from './components/collection/error-boundary';

domReady( () => {

	// Fetch rule ID and action and trigger editor div.
	const app = document.getElementById( 'noptin-automation-rule__editor-app' );

	if ( app ) {
		const data = {...app.dataset}
		data.id = parseInt( data.id );
		data.settings = JSON.parse( data.settings );
		data.smartTags = JSON.parse( data.smartTags );

		const Editor = (
			<StrictMode>
				<ErrorBoundary>
					<AutomationRuleEditor {...data} />
				</ErrorBoundary>
			</StrictMode>
		)

		// React 18.
		if ( createRoot ) {
			createRoot( app ).render( Editor );
		} else {
			render( Editor, app );
		}
	}
} );
