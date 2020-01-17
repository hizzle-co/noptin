(function ($) {

	//Settings app
	if ('undefined' == typeof noptinSettings) {
		window.noptinSettings = {}
	}

	//The settings app
	window.noptinSettingsApp = require ( './partials/settings.js' ).default


})(jQuery);
