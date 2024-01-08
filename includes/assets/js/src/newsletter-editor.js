(function ($) {

	//The newsletter editor
	window.noptinNewsletterEditor = require ( './partials/newsletter-editor.js' ).default

	//Init the newsletter editor
	$(document).ready(function(){

		// Init the newsletter editor.
		noptinNewsletterEditor.init()

	});

})(jQuery);
