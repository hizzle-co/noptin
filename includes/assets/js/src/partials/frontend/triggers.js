import display from './display-popup';
import $ from './myquery';

// Calculates the scroll percentage.
const getScrollPercent = () => {
	let doc = document.documentElement, 
		body = document.body;
	return (doc.scrollTop||body.scrollTop) / ((doc.scrollHeight||body.scrollHeight) - doc.clientHeight) * 100;
}

// Throttle form lodash.
var throttle = require('lodash.throttle');

export default function trigger( form ) {

	return {

		// Displays a popup immeadiately.
		immeadiate() {
			display( form )
		},

		// Exit intent.
		before_leave() {
			let _delayTimer = null,
				sensitivity = 0, //how many pixels from the top should we display the popup?
				delay = 200; // wait 200ms before displaying popup

			// Fired when the user scrolls out of view.
			let watchLeave = ( e ) => {

				// Verify the sensitivity.
				if ( e.clientY > sensitivity ) {
					return;
				}

				// Wait for a while just in case the user changes their mind.
				_delayTimer = setTimeout(() => {

					// Display the popup.
					display( form );

					// Remove watchers.
					$(document).off('mouseleave', watchLeave);
					$(document).off('mouseenter', watchEnter);
				}, delay);

			};

			// Fired when the user scrolls into view.
			let watchEnter = () => {
				if (_delayTimer) {
					clearTimeout(_delayTimer);
					_delayTimer = null;
				}
			};

			//Display popup when the user tries to leave...
			$(document).on( 'mouseleave', watchLeave );

			//...unless they decide to come back
			$(document).on('mouseenter', watchEnter );

		},

		// After the user starts scrolling.
		on_scroll() {

			// Abort if scroll % set.
			if ( ! form.dataset.on_scroll ) {
				return;
			}

			// Maximum scroll percentage.
			const percent = parseInt( form.dataset.on_scroll );

			// Watch no more than once every 500ms
			let watchScroll = throttle(
				() => {

					if ( getScrollPercent() > percent ) {
						display( form );
						$(window).off('scroll', watchScroll)
					}

				},
				500
			);

			$(window).on('scroll', watchScroll)
		},

		// after_delay.
		after_delay() {

			// Abort if delay not set.
			if ( ! form.dataset.after_delay ) {
				return;
			}

			const delay = parseInt( form.dataset.after_delay ) * 1000;

			setTimeout(() => {
				display( form );
			}, delay)
		},

		// after_click.
		after_click() {

			// Abort if target not set.
			if ( ! form.dataset.after_click ) {
				return;
			}

			$( 'body' ).on( 'click', form.dataset.after_click, ( event ) => {

				event.preventDefault();

				display( form );
			});

		}

	}

};
