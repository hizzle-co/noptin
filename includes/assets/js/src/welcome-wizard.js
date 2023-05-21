/**
 * Wordpress dependancies.
 */
import { render, createRoot, useState, useEffect, StrictMode } from '@wordpress/element';
import { Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import apiFetch from '@wordpress/api-fetch';

/**
 * Local dependancies.
 */
import SettingsWizard from './components/settings-wizard';

/**
 * Renders the welcome wizard.
 */
const Wizard = () => {

	const [settings, setSettings] = useState({});
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState(false);

	// Fetch settings on mount.
	useEffect(() => {
		apiFetch({
			path: '/noptin/v1/settings',
		}).then((data) => {
			setSettings(data);
		}).catch((err) => {
			setError(err.message ? err.message : __('An unknown error occurred.', 'noptin'));
		}).finally(() => {
			setLoading(false);
		});
	}, []);

	// Render loading spinner.
	if (loading) {
		return <Spinner />;
	}

	// Render error notice.
	if (error) {
		return (
			<Notice status="error" isDismissible={false}>
				{error}
			</Notice>
		);
	}

	// Render settings wizard.
	return (
		<StrictMode>
			<SettingsWizard saved={settings} />
		</StrictMode>
	);
}

// Init the wizard.
domReady(() => {

	// The welcome wizard div.
	const app = document.getElementById( 'noptin-welcome-wizard' );

	// Abort if the div is not found.
	if ( ! app ) {
		return;
	}

	// React 18.
	if ( createRoot ) {
		createRoot( app ).render( <Wizard /> );
	} else {
		render( <Wizard />, app );
	}

});
