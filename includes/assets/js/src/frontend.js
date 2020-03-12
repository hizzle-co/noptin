(function ($) {
	"use strict"

	//throttle form lodash
	var throttle = require('lodash.throttle');

	//Quickly generates a random string
	var randomString = () => {
		var rand = Math.random()
		return 'key' + rand.toString(36).replace(/[^a-z]+/g, '')
	}

	var ipAddress = ''
	$.getJSON("https://api.ipify.org?format=jsonp&callback=?",
		function (json) {
			if (json.ip) {
				ipAddress = json.ip
			}
		}
	);


	//Avoid displaying several popups at once...
	var displayingPopup = false

	//... or when the user subscribes via one popup
	var subscribed = false

	//Hides a displayed popup
	var hidePopup = (inner) => {

		displayingPopup = false;

		var wrapper = $(inner).closest('.noptin-popup-main-wrapper')
		var form = $(wrapper).find('.noptin-optin-form-wrapper')

		//Maybe animate
		$(form).removeClass('noptin-animate-after')

		var timing = parseFloat($(form).css('transition-duration'))
		if (timing) {
			timing = timing * 1000
		} else {
			timing = 500
		}

		setTimeout(() => {
			$("body").css("overflow", "auto");
			$(wrapper).removeClass('open')
		}, timing)
	}

	// Logs a form view
	var logFormView = (form_id) => {
		$.post(noptin.ajaxurl, {
			action: "noptin_log_form_impression",
			_wpnonce: noptin.nonce,
			form_id: form_id,
		})
	}

	//Displays a popup and attaches "close" event handlers
	var displayPopup = (popup, force) => {

		if ($(popup).closest('.noptin-optin-main-wrapper').hasClass('noptin-slide_in-main-wrapper')) {
			return displaySlideIn(popup, force)
		}

		if (!force && (displayingPopup || subscribed)) {
			return;
		}

		//Log form view
		logFormView($(popup).find('input[name=noptin_form_id]').val())

		var closeButton = $(popup)
			.closest('.noptin-popup-main-wrapper')
			.addClass('open')
			.on('click', (e) => {

				// if the target of the click isn't the form nor a descendant of the form.
				if (!$(popup).is(e.target) && $(popup).has(e.target).length === 0) {
					hidePopup(popup)
				}

			})
			.find('.noptin-form-close')
			.on('click', () => {
				hidePopup(popup)
			})

		//position fixed does not work on elements with transformed parents
		if ($(closeButton).hasClass('top-right')) {
			var wrapper = $(closeButton).closest('.noptin-popup-main-wrapper')
			$(closeButton).appendTo(wrapper)
		}

		//Maybe animate
		setTimeout(() => {
			$("body").css("overflow", "hidden");
			$(popup).addClass('noptin-animate-after')
		}, 100)

		//Some forms are only set to be displayed once per session
		var id = $(popup).find('input[name=noptin_form_id]').val()
		if (typeof $(popup).data('once-per-session') !== 'undefined') {
			localStorage.setItem("noptinFormDisplayed" + id, new Date().getTime());
		} else {
			sessionStorage.setItem("noptinFormDisplayed" + id, '1');
		}

	}

	//Displays a slide in and attaches "close" event handlers
	var displaySlideIn = (slide_in, force) => {

		if (!force && subscribed) {
			return;
		}

		//Log form view
		logFormView($(slide_in).find('input[name=noptin_form_id]').val())

		//Display the form
		$(slide_in).addClass('noptin-showing')

	}

	//Contains several triggers for displaying popups
	var noptinDisplayPopup = {

		//Displays a popup immeadiately
		immeadiate() {
			displayPopup(this)
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
					displayPopup(this)

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
					displayPopup(popup)
					$(window).off('scroll.' + key)
				}

			}

			$(window).on('scroll.' + key, throttle(watchScroll, 500))
		},

		//after_delay
		after_delay() {
			var delay = parseInt($(this).data('after-delay')) * 1000

			setTimeout(() => {
				displayPopup(this)
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

			$(el).on('click', (e) => {
				e.preventDefault()
				displayPopup(popup, true)
			})

		}
	}

	//Loop through all popups and attach triggers
	$('.noptin-popup-main-wrapper .noptin-optin-form-wrapper').each(function () {

		var trigger = $(this).data('trigger')

		//Some forms are only set to be displayed once per session
		var id = $(this).find('input[name=noptin_form_id]').val()
		if (typeof $(this).data('once-per-session') !== 'undefined' && 'after_click' != trigger) {

			if (id) {

				var addedTime = localStorage.getItem("noptinFormDisplayed" + id)
				var time = new Date().getTime()

				// Only display the popup once per week.
				if (addedTime && (time - addedTime) < 604800000) {
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

		//Take it to its initial state
		$(this).addClass('noptin-animate-from')

		if (noptinDisplayPopup[trigger]) {
			var cb = noptinDisplayPopup[trigger]
			cb.call(this)
		}
	})

	//Loop through all slide ins and attach triggers
	$('.noptin-slide_in-main-wrapper .noptin-optin-form-wrapper').each(function () {

		var trigger = $(this).data('trigger')

		if (noptinDisplayPopup[trigger]) {
			var cb = noptinDisplayPopup[trigger]
			cb.call(this)
		}
	})

	$(document).ready(function () {
		$(document).on('click', '.noptin-showing .noptin-form-close', function (e) {
			$(this).closest('.noptin-showing').removeClass('noptin-showing')
		});
	});

	//Submits forms via ajax
	function subscribe_user(form) {

		$(form)

			//https://stackoverflow.com/questions/15319942/preventing-bot-form-submission
			.prepend('<label style="display: none;"><input type="checkbox" name="noptin_confirm_submit"/>Are you sure?</label>')

		//select the form
		$(form)

			//what for submit events
			.on('submit', function (e) {

				//Prevent the form from submitting
				e.preventDefault();

				//Modify form state
				$(this)
					.fadeTo(600, 0.5)
					.find('.noptin_feedback_success, .noptin_feedback_error')
					.empty()
					.hide()


				//Prep all form data
				var data = {},
					fields = $(this).serializeArray()

				jQuery.each(fields, (i, field) => {
					data[field.name] = field.value
				});

				//Add nonce and action
				data.action = "noptin_new_subscriber"
				data._wpnonce = noptin.nonce
				data.ipAddress = ipAddress

				//Post it to the server
				$.post(noptin.ajaxurl, data)

					//Update the user of success
					.done((data, status, xhr) => {

						if ('string' == typeof data) {
							$(this)
								.find('.noptin_feedback_error')
								.text(data)
								.show();
							return;
						}

						// Google Analytics
						try {

							if (typeof ga === 'function') {
								ga('send', 'event', 'Noptin Form', 'Subscribe', 'Noptin');
							} else if (typeof gtag === 'function') {
								gtag('event', 'subscribe', { 'method': 'Noptin Form' });
							}

						} catch (err) {
							console.error(err.message);
						}

						subscribed = true

						if (data.action == 'redirect') {
							window.location = data.redirect;
							return;
						}

						// Gutenberg
						var url = $(this).find('.noptin_form_redirect').val();

						if (url) {
							window.location = url;
							return;
						}

						if (data.action == 'msg') {
							$(this).html('<div class="noptin-big noptin-padded">' + data.msg + '</div>');
							$(this).css({
								display: 'flex',
								justifyContent: 'center'
							})
							setTimeout(() => {
								$(this).closest('.noptin-showing').removeClass('noptin-showing')
							}, 2000)

						}


					})
					.fail(() => {
						var msg = 'Could not establish a connection to the server.'
						$(this)
							.find('.noptin_feedback_error')
							.text(msg)
							.show();
					})
					.always(() => {
						$(this).fadeTo(600, 1)
					})
			})
	}

	//Normal forms
	subscribe_user('.noptin-optin-form-wrapper form');

	//Gutenberg forms
	$('.wp-block-noptin-email-optin form, .noptin-email-optin-widget form')
		.find('input[type=email]')
		.attr('name', 'email')

	subscribe_user('.wp-block-noptin-email-optin form, .noptin-email-optin-widget form');

})(jQuery);
