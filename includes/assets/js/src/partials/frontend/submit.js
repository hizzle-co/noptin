import $ from './myquery';

export default function submit( _form ) {

	let form = $( _form );

	// Display the loader.
	form.addClass( 'noptin-submitting' ).removeClass( 'noptin-form-submitted noptin-has-error noptin-has-success' );

	// Prepare errors div.
	let _error_div = form.find('.noptin-response').html( '' );

	window

		// Post the form.
		.fetch( noptinParams.resturl, {
			method: 'POST',
			body: new FormData(_form),
			credentials: 'same-origin',
			headers: {
				'Accept': 'application/json',
    		}
		})

		// Check status.
		.then( ( response ) => {

			if ( response.status >= 200 && response.status < 300 ) {
				return response;
			}

			throw response;
		})

		// Parse JSON.
		.then(response => response.json())

		// Handle the response.
		.then(response => {

			// Was the ajax invalid?
			if ( ! response ) {
				_form.submit();
				return;
			}

			// An error occured.
			if ( response.success === false ) {
				form.addClass( 'noptin-has-error' );
				_error_div.html( response.data );

			// The request was successful.
			} else if ( response.success === true ) {

				// Maybe redirect to success page.
				if ( response.data.action === 'redirect' ) {
					window.location.href = response.data.redirect_url;
				}

				// Display success message.
				if ( response.data.msg ) {
					form.addClass( 'noptin-has-success' );
					_error_div.html( response.data.msg );
				}

			// Invalid response. Submit manually.
			} else {
				_form.submit();
				return;
			}

			// Hide the loader.
			form.removeClass( 'noptin-submitting' ).addClass( 'noptin-form-submitted' );

		})

		// Submit manually on HTTP errors.
		.catch( (e) => _form.submit() );

};
