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

	if (! window.FormData) {
		console.error("FormData is not supported.");
		return;
	}

	if (typeof noptinParams === 'undefined') {
		console.error("noptinParams is not defined.");
		return;
	}

	if (typeof noptinParams.resturl === 'undefined') {
		console.error("noptinParams.resturl is not defined.");
		return;
	}

	let form = require('./partials/frontend/init').default;
	let $ = require('./partials/frontend/myquery').default;

	$('.noptin-newsletter-form').each( form )

});
