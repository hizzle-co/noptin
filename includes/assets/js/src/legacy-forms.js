import domReady from './partials/dom-ready';
import subscribe from './partials/frontend/subscribe';

// Init when the DOM is ready.
domReady( function() {

	// Add the subscribe handler to all forms.
	document.querySelectorAll( '.noptin-optin-form-wrapper form, .wp-block-noptin-email-optin form, .noptin-email-optin-widget form, .noptin-optin-form' ).forEach((form) => {
		subscribe( form );
	})

	// Add name attributes to all email fields.
	document.querySelectorAll( '.wp-block-noptin-email-optin form input[type=email], .noptin-email-optin-widget form input[type=email]' ).forEach((input) => {

		// Add name attribute.
		input.setAttribute( 'name', 'email' );
	})

	// Check if jQuery is available.
	if ( typeof jQuery !== 'undefined' ) {

		// Hide slide in forms.
		jQuery( '.noptin-popup-close' ).on( 'click', function(e) {
			e.preventDefault();
			jQuery( this ).closest( '.noptin-showing' ).removeClass( 'noptin-showing' );
		})
	}

	document.addEventListener( 'click', function( e ) {

		// Check if there is an element with a .noptin-showing class.
		const showing = document.querySelector( '.noptin-showing' );

		// Check if the user clicked on a mark as existing subscriber button.
		if ( e.target.matches( '.noptin-mark-as-existing-subscriber' ) ) {
			e.preventDefault();

			let setCookie = cname => {
				let d = new Date();
				d.setTime(d.getTime() + (30*24*60*60*1000)); // 30 days from now in milliseconds
				let expires = "expires="+ d.toUTCString();
				document.cookie = `${cname}=1;${expires};path=${noptin.cookie_path}`;
			}

			if ( noptin.cookie ) {
				setCookie(noptin.cookie)
			}
			setCookie('noptin_email_subscribed')

			if ( showing && jQuery ) {
				jQuery(this).closest('.noptin-showing').removeClass('noptin-showing')
			}

			// popups.close()
			if ( window.noptin_popups ) {
				window.noptin_popups.subscribed = true;
			}
		}
	} );

});
