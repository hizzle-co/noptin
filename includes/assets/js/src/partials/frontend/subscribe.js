/**
 * Adds a honey pot field to the form then watches for submissions.
 *
 * Only handles legacy forms.
 *
 * @param {Element} form The form element.
 *
 * @return {void}
 */
export default function subscribe( form ) {

	// Displays an error message.
	function showError( message ) {
		form.querySelector( '.noptin_feedback_error' ).innerHTML = message;
		form.querySelector( '.noptin_feedback_error' ).style.display = 'block';
		form.querySelector( '.noptin_feedback_success' ).style.display = 'none';
	}

	// Displays a success message.
	function showSuccess( message ) {
		form.querySelector( '.noptin_feedback_success' ).innerHTML = message;
		form.querySelector( '.noptin_feedback_success' ).style.display = 'block';
		form.querySelector( '.noptin_feedback_error' ).style.display = 'none';
	}

	// Without using jQuery
	// Prepend <label style="display: none;"><input type="checkbox" name="noptin_confirm_submit"/>Are you sure?</label>
	const honey_pot = document.createElement( 'label' );
	honey_pot.style.display = 'none';
	honey_pot.innerHTML = '<input type="checkbox" name="noptin_confirm_submit"/>Are you sure?';
	form.prepend( honey_pot );

	// Watch for form submissions
	form.addEventListener( 'submit', ( e ) => {

		// Prevent the form from submitting
		e.preventDefault();

		// Fade the form to 0.5 opacity
		form.style.opacity = 0.5;

		// Remove any previous feedback and hide it.
		form.querySelector( '.noptin_feedback_success' ).innerHTML = '';
		form.querySelector( '.noptin_feedback_error' ).innerHTML = '';
		form.querySelector( '.noptin_feedback_success' ).style.display = 'none';
		form.querySelector( '.noptin_feedback_error' ).style.display = 'none';

		// Prep all form data
		const data = {};
		const fields = new FormData( form );

		fields.forEach( ( value, key ) => {
			data[ key ] = value;
		});

		data.action = 'noptin_new_subscriber';
		data.nonce = noptin.nonce;
		data.conversion_page = window.location.href;

		// Send the data to the server
		window

			// Post the form.
			.fetch( noptin.ajaxurl, {
				method: 'POST',
				body: new URLSearchParams( data ),
				credentials: 'same-origin',
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/x-www-form-urlencoded',
				}
			})

			// Check status.
			.then( ( response ) => {

				if ( response.status >= 200 && response.status < 300 ) {
					return response;
				}

				throw response.text();
			})

			// Parse JSON.
			.then(response => response.json())

			// Handle the response.
			.then(response => {

				// Was the ajax invalid?
				if ( ! response ) {
					throw 'Invalid response';
				}

				// An error occured.
				if ( response.success === false ) {
					throw response.data;
				}

				if ( response.success === true ) {

					// Maybe redirect to success page.
					if ( response.data.action === 'redirect' ) {
						window.location.href = response.data.redirect;
					}

					// Display success message.
					if ( response.data.msg ) {

						form.innerHTML = '<div class="noptin-big noptin-padded">' + response.data.msg + '</div>';
						form.style.opacity = 1;
						form.style.display = 'flex';
						form.style.justifyContent = 'center';

						setTimeout(() => {
							document.querySelector( '.noptin-showing' ).classList.remove( 'noptin-showing' );
						}, 2000)
					}
				} else {
					throw 'Invalid response';
				}

			})

			// Google Analytics.
			.then( () => {
				try {

					// Track the event.
					if ( typeof window.gtag === 'function' ) {
						window.gtag('event', 'subscribe', { 'method': 'Noptin Form' });
					} else if (typeof window.ga === 'function') {
						window.ga('send', 'event', 'Noptin Form', 'Subscribe', 'Noptin');
					}

				} catch (err) {
					console.error(err.message);
				}
			})

			// Display error.
			.catch( (e) => {
				console.log( e );

				if ( typeof e === 'string' ) {
					showError( e );
				} else {
					showError( 'Could not establish a connection to the server.' );
				}

				form.style.opacity = 1;
			} );
	});
}
