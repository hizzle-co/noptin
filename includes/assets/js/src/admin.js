(function ($) {

	if ('undefined' == typeof noptinEditor) {
		window.noptinEditor = {}
	}

	//Settings app
	if ('undefined' == typeof noptinSettings) {
		window.noptinSettings = {}
	}

	// Global noptin object
	window.noptin = window.noptin || {}

	// Attach the tooltips
	$(document).ready(function(){

		$('.noptin-tip').tooltipster();

	});


})(jQuery);
