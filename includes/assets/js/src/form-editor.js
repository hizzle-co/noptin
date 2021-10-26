(function ($) {

	// Switch tabs.
	$('#noptin-form-editor-nav-tab-wrapper .nav-tab').on('click', function (e) {
		e.preventDefault();

		const id = $(this).data('id');

		// Change active/inactive tab classes.
		$(`#noptin-form-editor-nav-tab-wrapper .nav-tab-active:not(.noptin-form-tab-${id})`).removeClass('nav-tab-active')
		$(this).addClass('nav-tab-active').blur()

		// Hide/show tab content.
		$(`.noptin-form-tab-content-active:not(.noptin-form-tab-content-${id})`).removeClass('noptin-form-tab-content-active');
		$(`.noptin-form-tab-content-${id}`).addClass('noptin-form-tab-content-active');

		// Update document title.
		const tab_title = $('.noptin-form-tab-content-active h2:first-of-type').text()

		if (tab_title) {
			const title = document.title.split('-')
			document.title = document.title.replace(title[0], tab_title + ' ')
		}

		// Update address bar.
		if ( window.history.replaceState ) {
			window.history.replaceState( id, tab_title, $(this).attr('href') );
		}

		$(this).closest('form').attr( 'action', $(this).attr('href') );
	});

	// Toggle accordions.
	$( '#noptin-form-editor-app' ).on('click', '#noptin-form-editor-container .noptin-accordion-trigger', function (e) {
		e.preventDefault();

		let panel = $( this ).closest( '.noptin-settings-panel' ),
			button = panel.find('.noptin-accordion-trigger'),
			isExpanded = ( 'true' === button.attr( 'aria-expanded' ) );

		if ( isExpanded ) {
			button.attr( 'aria-expanded', 'false' );
			panel.addClass( 'noptin-settings-panel__hidden', true );
		} else {
			button.attr( 'aria-expanded', 'true' );
			panel.removeClass( 'noptin-settings-panel__hidden', false );
		}

	})

	// Add new field.
	$( '#noptin-form-fields-panel-fields .noptin-button-add-field' ).on( 'click', function( e ) {
		e.preventDefault();

		$( '#noptin-form-fields-panel-fields .form-fields-inner' ).append( $( '#noptin-form-fields-panel-new-field-template' ).html() )
	});

	// Change field type.
	$( '#noptin-form-editor-app' ).on('change', '.noptin-form-settings-field-type', function (e) {
		e.preventDefault();

		// Get field type.
		let val = $( this ).val(),
		    panel_content = $( this ).closest( '.noptin-settings-panel__content' );

		// Update settings template and ids.
		panel_content
			.html( $( `#noptin-form-fields-panel-${val}-template` ).html() )
			.attr( 'id', `noptin-form-fields-panel-fields-${val}-content` )
			.closest( '.noptin-settings-panel' )
			.attr( 'id', `noptin-form-fields-panel-fields-${val}` )
			.find( `.noptin-accordion-trigger` )
			.first()
			.attr( 'aria-controls', `noptin-form-fields-panel-fields-${val}-content` )
			.find( '.badge' )
			.text( val )
			.show()

		panel_content.find( '.noptin-form-field-label' ).trigger( 'input' )

	})

	// Update field labels.
	$( '#noptin-form-fields-panel-fields' ).on('input', '.noptin-form-field-label', function () {

		$( this )
			.closest( '.noptin-settings-panel' )
			.find( '.noptin-accordion-trigger .title' )
			.first()
			.text( $( this ).val() )
	})

	// Delete fields.
	$( '#noptin-form-fields-panel-fields' ).on('click', '.noptin-field-editor-delete', function ( e ) {
		e.preventDefault();
		$( this ).closest( '.noptin-settings-panel' ).fadeOut( 'fast', function () { $( this ).remove() } );
	});

	// Sortable.
	if ( 'object' == typeof Sortable && Sortable.default ) {
		new Sortable.default(
			document.querySelectorAll('#noptin-form-fields-panel-fields-content .form-fields-inner'),
			{
				draggable: '.draggable-source',
				handle: '.dashicons-move',
			}
		);
	}

	// Submit form.
	$( '#noptin-form-editor-app' ).on( 'submit', () => {

		// Save editor content.
		if ( window.tinyMCE ) {
			window.tinyMCE.triggerSave();
		}

		// Update field names.
		$( '#noptin-form-fields-panel-fields-content .form-fields-inner > fieldset' ).each( function( index ) {

			$( this ).find('[name^="noptin_form[settings][fields][]"]').each ( function() {
				let _name = $( this ).attr( 'name' );
				$( this ).attr( 'name', _name.replace( '[]', `[${index}]` ) );
			})

		});

		// Remove template field names.
		$( '#noptin-form-fields-panel-field-templates [name^="noptin_form[settings][fields][]"]' ).each( function() {
			$( this ).attr( 'name', '' );
		});

	});

})(jQuery);
