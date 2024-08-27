(function ($) {

	// Settings app.
	if ( 'undefined' == typeof noptinSettings ) {
		window.noptinSettings = {}
	}

	// Global noptin object.
	window.noptin = window.noptin || {}

	// Wait for the dom to load...
	$( document ).ready( function() {

		// ... then init tooltips...
		if ( $.fn.tooltipster ) {
			$( '.noptin-tip' ).tooltipster(
				{
					interactive: true,
				}
			);
		}
	});

})(jQuery);
