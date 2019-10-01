(function ($) {


	if ('undefined' == typeof noptinEditor) {
		noptinEditor = {}
	}

	//Settings app
	if ('undefined' == typeof noptinSettings) {
		noptinSettings = {}
	}

	//List filterf
	$(document).ready( () => {
		$(".noptin-list-filter input").on("keyup", () => {
			var value = $(this).val().toLowerCase();
			$('.noptin-list-table tbody tr').filter( () => {
				$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
			});
		});

	});

	//The main Editor app
	window.noptinOptinEditor = require ( './optin-editor.js' ).default

	window.noptinSettingsApp = require ( './settings.js' ).default

	//Attach the tooltips
	$('.noptin-tip').tooltipster();

})(jQuery);
