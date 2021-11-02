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

	// Warn if a user is leaving the page without saving changes.
	let isSaving = false;
	let initialState = $( '#noptin-form-editor-app' ).serialize();

	jQuery(window).on('beforeunload', (e) => {
		let currentState = $( '#noptin-form-editor-app' ).serialize();

		if ( ! isSaving && initialState != currentState ) {
			let confirmationMessage = 'Do you wish to save your changes first? Your changes will be discarded if you choose leave without saving them.';

			(e || window.event).returnValue = confirmationMessage; // Gecko + IE.
        	return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
		}
	});

	// Save tinymce when submitting the form.
	$( '#noptin-form-editor-app' ).on( 'submit', () => {
		isSaving = true;

		// Save editor content.
		if ( window.tinyMCE ) {
			window.tinyMCE.triggerSave();
		}

	});

})(jQuery);
