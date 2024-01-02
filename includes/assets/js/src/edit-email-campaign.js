import domReady from '@wordpress/dom-ready';
import EmailCampaignEditor from './components/email-campaigns/editor';
import {render, createRoot, StrictMode} from "@wordpress/element";

domReady( () => {

	// Fetch rule ID and action and trigger editor div.
	const app = document.getElementById( 'noptin-emails-conditional-logic__editor-app' );

	if ( app ) {
		const data = {...app.dataset}
		data.id = parseInt( data.id );
		data.saved = JSON.parse( data.saved );
		data.settings = JSON.parse( data.settings );
		data.smartTags = JSON.parse( data.smartTags );

		const Editor = (
			<StrictMode>
				<EmailCampaignEditor {...data} />
			</StrictMode>
		)

		// React 18.
		if ( createRoot ) {
			createRoot( app ).render( Editor );
		} else {
			render( Editor, app );
		}
	}
} );

(function ($) {

	//The newsletter editor
	window.noptinNewsletterEditor = require ( './partials/newsletter-editor.js' ).default

	//Init the newsletter editor
	$(document).ready(function(){

		// Init the newsletter editor.
		noptinNewsletterEditor.init()

		// Hide/Show sending options.
		$('.noptin-email_sender').on( 'change', function( e ) {
			let val = $( this ).val()
			$('.noptin-sender-options').hide()
			$(`.noptin-sender-options.sender-${val}`).show()
		})

		// Hide/Show select 2 options.
		$('.noptin-newsletter-select_2').select2()

		// Change email type.
		$('#noptin-email-type').on( 'change', function() {
			$( this )
				.closest( 'form' )
				.attr( 'data-type', $( this ).val() )
		})

		// Change timing.
		$('#noptin-automated-email-when-to-run').on( 'change', function() {
			$('.noptin-automation-delay-wrapper').toggle( $( this ).val() == 'delayed' );
		})

		// Post digest timing.
		$('#noptin-post-digest-frequency').on( 'change', function() {
			$('.noptin-post-digest-day').toggle( $( this ).val() == 'weekly' );
			$('.noptin-post-digest-date').toggle( $( this ).val() == 'monthly' );
			$('.noptin-post-digest-year-day').toggle( $( this ).val() == 'yearly' );
			$('.noptin-post-digest-x-days').toggle( $( this ).val() == 'x_days' );
		})

		// Remove newsletter recipient.
		$('.noptin-manual-email-recipients').on( 'click', '.noptin-manual-email-recipient-remove', function( e ) {
			e.preventDefault()
			$( this ).closest( '.noptin-manual-email-recipient' ).remove()
		})
	});

})(jQuery);
