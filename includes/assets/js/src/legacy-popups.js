import throttle from 'lodash.throttle';
import randomString from './partials/random-string';
import subscribe from './partials/frontend/subscribe';

(function ($) {
	"use strict"

	// Our little library for displaying popup forms.
	let popups = require('./partials/popups')

	/**
	 * Popup Manager.
	 */
	let popup_manager = function() {

		return {

			//Avoid displaying a popup when the user subscribes via one popup
			subscribed: false,

			// Hides a displayed popup
			hidePopup() {
				popups.close()
			},

			// Log form view.
			logFormView(form_id) {
				$.post(noptin.ajaxurl, {
					action: "noptin_log_form_impression",
					_wpnonce: noptin.nonce,
					form_id: form_id,
				})
			},

			// Display a popup.
			displayPopup(popup, force) {

				if ($(popup).closest('.noptin-optin-main-wrapper').hasClass('noptin-slide_in-main-wrapper')) {
					return this.displaySlideIn(popup, force)
				}

				// Do not display several popups at once.
				if ( ! force && ( popups.is_showing || this.subscribed ) ) {
					return;
				}

				// Log form view
				this.logFormView($(popup).find('input[name=noptin_form_id]').val())

				// Replace the content if a popup is already showing.
				if ( popups.is_showing ) {
					popups.replaceContent( $( popup ).closest('.noptin-popup-main-wrapper'), subscribe )
				} else {
					popups.open( $( popup ).closest('.noptin-popup-main-wrapper'), subscribe )
				}

				// Some forms are only set to be displayed once per session.
				var id = $(popup).find('input[name=noptin_form_id]').val()
				if (typeof $(popup).data('hide-seconds') !== 'undefined') {
					localStorage.setItem("noptinFormDisplayed" + id, new Date().getTime());
				} else {
					sessionStorage.setItem("noptinFormDisplayed" + id, '1');
				}

			},

			// Displays a slide in and attaches "close" event handlers.
			displaySlideIn( slide_in, force ) {

				if (!force && this.subscribed) {
					return;
				}

				//Log form view
				this.logFormView($(slide_in).find('input[name=noptin_form_id]').val())

				// Display the form
				$(slide_in).addClass('noptin-showing')

				// Some forms are only set to be displayed once per session.
				var id = $(slide_in).find('input[name=noptin_form_id]').val()
				if (typeof $(slide_in).data('hide-seconds') !== 'undefined') {
					localStorage.setItem("noptinFormDisplayed" + id, new Date().getTime());
				} else {
					sessionStorage.setItem("noptinFormDisplayed" + id, '1');
				}
			}
		}

	}

	var noptin_popups = popup_manager();

	//Contains several triggers for displaying popups
	var noptinDisplayPopup = {

		//Displays a popup immeadiately
		immeadiate() {
			noptin_popups.displayPopup(this)
		},

		//Exit intent
		before_leave() {
			var key = randomString(),
				_delayTimer = null,
				sensitivity = 0, //how many pixels from the top should we display the popup?
				delay = 200; //wait 200ms before displaying popup

			//Display popup when the user tries to leave...
			$(document).on('mouseleave.' + key, (e) => {
				if (e.clientY > sensitivity) { return; }
				_delayTimer = setTimeout(() => {

					//Display the popup
					noptin_popups.displayPopup(this)

					//Remove watchers
					$(document).off('mouseleave.' + key)
					$(document).off('mouseenter.' + key)
				}, delay);
			});

			//...unless they decide to come back
			$(document).on('mouseenter.' + key, (e) => {
				if (_delayTimer) {
					clearTimeout(_delayTimer);
					_delayTimer = null;
				}
			});

		},

		//After the user starts scrolling
		on_scroll() {
			var popup = this,
				key = randomString(),
				showPercent = parseInt($(this).data('on-scroll'))

			var watchScroll = () => {
				var scrolled = $(window).scrollTop(),
					Dheight = $(document).height(),
					Wheight = $(window).height();

				var scrollPercent = (scrolled / (Dheight - Wheight)) * 100;

				if (scrollPercent > showPercent) {
					noptin_popups.displayPopup(popup)
					$(window).off('scroll.' + key)
				}

			}

			$(window).on('scroll.' + key, throttle(watchScroll, 500))
		},

		//after_delay
		after_delay() {
			var delay = parseInt($(this).data('after-delay')) * 1000

			setTimeout(() => {
				noptin_popups.displayPopup(this)
			}, delay)
		},

		//after_comment
		after_comment() {
			$('#commentform').on('submit', (e) => {
				//TODO
			})
		},

		//after_click
		after_click() {

			var el = $(this).data('after-click'),
				popup = this
			
			if ( el ) {

				$('body').on('click', el,(e) => {
					e.preventDefault()
					noptin_popups.displayPopup(popup, true)
				})

			}
			
		}
	}

	// Loop through all popups and attach triggers.
	$('.noptin-popup-main-wrapper .noptin-optin-form-wrapper, .noptin-slide_in-main-wrapper .noptin-optin-form-wrapper').each(function () {

		var trigger = $(this).data('trigger')

		// Some forms are only set to be displayed once per session
		var id = $(this).find('input[name=noptin_form_id]').val();
		var hideSeconds = $(this).data('hide-seconds');

		if ( hideSeconds && parseInt( hideSeconds ) > 0 && 'after_click' != trigger) {

			if (id) {

				var addedTime = localStorage.getItem("noptinFormDisplayed" + id)
				var time = new Date().getTime();

				// Only display the popup once per week.
				if (addedTime && (time - addedTime) < parseInt( hideSeconds )) {
					return true;
				}
				localStorage.removeItem("noptinFormDisplayed" + id)
			}

		} else {
			if (id && 'after_click' != trigger) {
				if (sessionStorage.getItem("noptinFormDisplayed" + id)) {
					return;
				}
			}
		}

		if (noptinDisplayPopup[trigger]) {
			var cb = noptinDisplayPopup[trigger]
			cb.call(this)
		}

	})
})(jQuery);
