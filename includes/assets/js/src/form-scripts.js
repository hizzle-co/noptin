"use strict";
document.addEventListener( 'DOMContentLoaded', event => {

	if ( typeof noptinParams === 'undefined' ) {
		console.error( "noptinParams is not defined." );
		return;
	}

	if ( typeof noptinParams.ajaxurl === 'undefined' ) {
		console.error( "noptinParams.ajaxurl is not defined." );
		return;
	}

	window.noptinForm = require( './partials/frontend/init' ).default
	const forms = document.querySelectorAll( '.noptin-newsletter-form' );

	let i;
	for ( i = 0; i < forms.length; i++ ) {
		noptinForm( forms[i] );
	}

} );
