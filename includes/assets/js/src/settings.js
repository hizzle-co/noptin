import domReady from '@wordpress/dom-ready';
import { createApp } from 'vue';
import App from './partials/settings.js';

domReady( function () {
	window.noptin = window.noptin || {};

	window.noptin.settingsApp = createApp( App ).mount( '#noptin-settings-app' );
} );
