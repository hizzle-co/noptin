const axios = require('axios').default;
const serialize = require( './serialize' ).default

export default function submit( form ) {

    // Show the loader.
    form.classList.add( 'noptin-submitting' );

    // Handle errors.
    let _error_div = form.querySelector('.noptin-form-notice');
    _error_div.classList.remove( 'noptin-form-error', 'noptin-form-success' );

    // Post the form.
    axios
        .post( noptinParams.ajaxurl, serialize(form) )
        .then(function (response) {

            if ( response.data.success === false ) {
                _error_div.classList.add( 'noptin-form-error' );
                _error_div.textContent = response.data.data;
            }

            if ( response.data.success === true ) {

                if ( response.data.data.action === 'redirect' ) {
                    window.location.href = response.data.data.redirect
                }

                if ( response.data.data.action === 'msg' ) {
                    _error_div.classList.add( 'noptin-form-success' );
                    _error_div.textContent = response.data.data.msg;
                }

            }

        })
        .catch(function (error) {
            // handle errors.
            console.log(error);

            _error_div.classList.add( 'noptin-form-error' );
            _error_div.textContent = 'Could not establish a connection to the server.';
        })

        .then(function () {
            // Hide the loader.
            form.classList.remove( 'noptin-submitting' );
        });

};
