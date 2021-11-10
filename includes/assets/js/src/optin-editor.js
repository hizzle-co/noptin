(function ($) {

	if ('undefined' == typeof noptinEditor) {
		window.noptinEditor = {}
	}

	// Optin forms editor app.
	if ( jQuery('#noptin_form_editor').length ) {
		window.noptinOptinEditor = require('./partials/optin-editor.js').default
	}

})(jQuery);
