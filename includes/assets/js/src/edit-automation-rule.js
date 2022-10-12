import domReady from '@wordpress/dom-ready';
import { createApp } from 'vue';
import App from './partials/automation-rules-editor.js';

domReady( function () {
	window.noptin = window.noptin || {};

	window.noptin.AutomationRulesEditor = createApp( App ).mount( '#noptin-automation-rule-editor' );
} );
