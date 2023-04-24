jQuery(function ( $ ) {

	// Wait for the dom to load...
	$( document ).ready( function() {

		// Prevent clicks on .noptin-automation-rule-create if the link is disabled.
		$( '.noptin-automation-rule-create' ).on( 'click', function( e ) {
			if ( $( this ).prop( 'disabled' ) ) {
				e.preventDefault();
			}
		});

		// Listens to select changes and updates the description.
		const handleSelectChange = function( e ) {

			// Set up the current trigger and action.
			const trigger = $('.noptin-automation-rules-dropdown-trigger').val();
			const action = $('.noptin-automation-rules-dropdown-action').val();
			const button = $( '.noptin-automation-rule-create' );

			// Are both of them set-up?
			if ( trigger && action ) {
				button
					.removeClass('button-secondary disabled')
					.addClass('button-primary')

				// Update the button href.
				const urlTemplate = button.data( `${action}-url` );
				const url = new URL( urlTemplate ||button.data( 'default-url' ) );

				// Add the trigger.
				url.searchParams.set( 'noptin-trigger', trigger );

				// Add the action.
				url.searchParams.set( 'noptin-action', action );

				// Update the button href.
				button.attr( 'href', url.toString() );
			} else {
				$('.noptin-automation-rule-create')
					.removeClass('button-primary')
					.addClass('button-secondary disabled')
			}

		};

		// Attach select2 to the select fields.
		$( '.noptin-automation-rules-dropdown' )
		.select2({
			templateResult: function( option ) {
				const description = $( option.element ).data( 'description' );
				return $( `<div><strong>${option.text}</strong><p class="description">${description}</p></div>` );
			},
			templateSelection: function( option ) {
				$( option.element )
					.closest( '.noptin-automation-rule-editor-section' )
					.find( '.noptin-automation-rule-editor-section-description' )
					.text( $( option.element ).data( 'description' ) );

				return option.text;
			},
		})
		.on( 'change', handleSelectChange )
		.trigger( 'change' );
	});

});
