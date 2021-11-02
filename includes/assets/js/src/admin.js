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
			$( '.noptin-tip' ).tooltipster();
		}

		// ... and select 2.
		if ( $.fn.select2 ) {
			$( '.noptin-select2' ).select2({
				dropdownParent: $( '#noptin-wrapper' ),
				width: 'resolve'
			});
		}

	});

})(jQuery);
