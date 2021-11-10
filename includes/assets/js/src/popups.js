"use strict";

// Our own version of jQuery document ready.
let noptinReady = (cb) => {
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', cb);
	}
	else {
		cb();
	}
}

// Init the plugin on dom ready.
noptinReady( () => {

	window.noptinPopups = {};
	let popup = require( './partials/frontend/popup' ).default
	let $ = require('./partials/frontend/myquery').default;

	$('.noptin-popup-wrapper').each( popup )

});
