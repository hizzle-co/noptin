(function ($) {

	if ('undefined' == typeof noptinEditor) {
		window.noptinEditor = {}
	}

	//optin forms editor app
	window.noptinOptinEditor = require ( './partials/optin-editor.js' ).default

})(jQuery);
