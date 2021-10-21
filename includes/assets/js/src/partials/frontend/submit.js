import $ from './myquery';
import axios from 'axios';

export default function submit( _form ) {

	let form = $( _form );

	// Display the loader.
	form.addClass( 'noptin-submitting' ).removeClass( 'noptin-form-submitted noptin-has-error noptin-has-success' );

	// Prepare errors div.
	let _error_div = form.find('.noptin-response').html( '' );

	// Post the form.
	axios
		.post( noptinParams.resturl, new FormData(_form) )
		.then(function (response) {

			// Prepare response data.
			const res = response.data;
			if ( ! res ) {
				_form.submit();
			}

			// An error occured.
			if ( res.success === false ) {
				form.addClass( 'noptin-has-error' );
				_error_div.html( res.data );

			// The request was successful.
			} else if ( res.success === true ) {

				// Maybe redirect to success page.
				if ( res.data.action === 'redirect' ) {
					window.location.href = res.data.redirect
				}

				// Display success message.
				if ( res.data.msg ) {
					form.addClass( 'noptin-has-success' );
					_error_div.html( res.data.msg );
				}

			// Invalid response. Submit manually.
			} else {
				_form.submit();
			}

			// Hide the loader.
			form.removeClass( 'noptin-submitting' ).addClass( 'noptin-form-submitted' );
		})

		// Submit manually on HTTP errors.
		.catch( () => _form.submit() )

};
