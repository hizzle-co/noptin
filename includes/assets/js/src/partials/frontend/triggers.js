import display from './display-popup';
import $ from './myquery';

// Calculates the scroll percentage.
const getScrollPercent = () => {
	let doc = document.documentElement, 
		body = document.body;
	return (doc.scrollTop||body.scrollTop) / ((doc.scrollHeight||body.scrollHeight) - doc.clientHeight) * 100;
}

// Throttle from lodash.
var throttle = require('lodash.throttle');

export default function trigger( popup ) {

	return {

		// Displays a popup immeadiately.
		immeadiate() {
			display( popup )
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
					display( popup );

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

			// Maximum scroll percentage.
			const percent = parseFloat( popup.dataset.value );

			if ( isNaN( percent ) ) {
				return;
			}

			// Watch no more than once every 500ms
			let watchScroll = throttle(
				() => {

					if ( getScrollPercent() > percent ) {
						display( popup );
						$(window).off('scroll', watchScroll)
					}

				},
				500
			);

			$(window).on('scroll', watchScroll)
		},

		// after_delay.
		after_delay() {

			const delay = parseFloat( popup.dataset.value ) * 1000;

			if ( isNaN( delay ) ) {
				return;
			}

			setTimeout(() => {
				display( popup );
			}, delay)
		},

		// after_click.
		after_click() {

			// Abort if target not set.
			if ( ! popup.dataset.value ) {
				return;
			}

			$( 'body' ).on( 'click', popup.dataset.value, ( event ) => {

				event.preventDefault();

				display( popup );
			});

		}

	}

};
