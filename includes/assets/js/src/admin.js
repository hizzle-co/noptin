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

		// ... and select 2.
		if ( $.fn.select2 ) {

			$( '.noptin-select2' ).each( function() {
				let options = {
					dropdownParent: $( '#noptin-wrapper' ),
					width: 'resolve',
					createTag: function (params) {
						var term = $.trim(params.term);
					
						if (term === '') {
							return null;
						}

						return {
							id: term,
							text: term,
							newTag: true // add additional parameters
						}
					},
				};

				let messages = $( this ).data( 'messages' );

				if ( messages ) {
					options.language = {};

					Object.keys(messages).forEach( (key) => {
						options.language[ key ] = () => messages[ key ]
					})

				}

				$( this ).select2( options );

			});

		}

	});

})(jQuery);
