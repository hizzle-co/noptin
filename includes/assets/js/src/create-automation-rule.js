jQuery(function ( $ ) {

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
		.on( 'change', function( e ) {

			// Set up the current trigger and action.
			let trigger = jQuery('.noptin-automation-rules-dropdown-trigger').val()
			let action = jQuery('.noptin-automation-rules-dropdown-action').val()

			// Are both of them set-up?
			if ( trigger && action ) {
				$('.noptin-automation-rule-create')
					.prop("disabled", false)
					.removeClass('button-secondary')
					.addClass('button-primary')
			} else {
				$('.noptin-automation-rule-create')
					.prop("disabled", true)
					.removeClass('button-primary')
					.addClass('button-secondary')
			}

		})
		.trigger( 'change' );

});
