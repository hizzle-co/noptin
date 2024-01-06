(function ($) {

	//The newsletter editor
	window.noptinNewsletterEditor = require ( './partials/newsletter-editor.js' ).default

	//Init the newsletter editor
	$(document).ready(function(){

		// Init the newsletter editor.
		noptinNewsletterEditor.init()

		// Post digest timing.
		$('#noptin-post-digest-frequency').on( 'change', function() {
			$('.noptin-post-digest-day').toggle( $( this ).val() == 'weekly' );
			$('.noptin-post-digest-date').toggle( $( this ).val() == 'monthly' );
			$('.noptin-post-digest-year-day').toggle( $( this ).val() == 'yearly' );
			$('.noptin-post-digest-x-days').toggle( $( this ).val() == 'x_days' );
		})

	});

})(jQuery);
