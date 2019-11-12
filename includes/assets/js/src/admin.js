(function ($) {

	if ('undefined' == typeof noptinEditor) {
		window.noptinEditor = {}
	}

	//Settings app
	if ('undefined' == typeof noptinSettings) {
		window.noptinSettings = {}
	}

	//The main Editor apps
	window.noptinOptinEditor = require ( './optin-editor.js' ).default
	window.noptinSettingsApp = require ( './settings.js' ).default
	window.noptinNewsletterEditor = require ( './newsletter-editor.js' ).default

	//Newsletter select recipients
	$(document).ready(function(){

		//Attach the tooltips
		$('.noptin-tip').tooltipster();

		//Init the newsletter editor
		noptinNewsletterEditor.init()

	});


})(jQuery);
