(function ($) {
	"use strict"

	//throttle form lodash
	var throttle = require('lodash.throttle');

	//Quickly generates a random string
	var randomString = function () {
		var rand = Math.random()
		return 'key' + rand.toString(36).replace(/[^a-z]+/g, '')
	}

	//Displays a popup and attaches "close" event handlers
	var displayPopup = function (popup) {
		$(popup)
			.closest('.noptin-popup-main-wrapper')
			.addClass('open')
			.on('click', function (e) {

				// if the target of the click isn't the form nor a descendant of the form
				if (!$(popup).is(e.target) && $(popup).has(e.target).length === 0) {
					$(popup)
						.closest('.noptin-popup-main-wrapper')
						.removeClass("open");
				}

			})
			.find('.noptin-form-close')
			.on('click', function () {
				$(".noptin-popup-main-wrapper").removeClass("open");
			})
	}

	//Contains several triggers for displaying popups
	var noptinDisplayPopup = {

		//Displays a popup immeadiately
		immeadiate: function () {
			displayPopup(this)
		},

		//Exit intent
		before_leave: function () {
			var popup = this,
				key = randomString(),
				_delayTimer = null,
				sensitivity = 20, //how many pixels from the top should we display the popup?
				delay = 200; //wait 100ms before displaying popup

			//Display popup when the user tries to leave...
			$(document).on('mouseleave.' + key, function (e) {
				if (e.clientY > sensitivity) { return; }
				_delayTimer = setTimeout(function () {

					//Display the popup
					displayPopup(popup)

					//Remove watchers
					$(document).off('mouseleave.' + key)
					$(document).off('mouseenter.' + key)
				}, delay);
			});

			//...unless they decide to come back
			$(document).on('mouseenter.' + key, function (e) {
				if (_delayTimer) {
					clearTimeout(_delayTimer);
					_delayTimer = null;
				}
			});

		},

		//After the user starts scrolling
		on_scroll: function () {
			var popup = this,
				key = randomString(),
				showPercent = parseInt($(this).data('on-scroll'))

			var watchScroll = function () {
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
		after_delay: function () {
			var delay = parseInt($(this).data('after-delay')),
				popup = this

			setTimeout(function () {
				displayPopup(popup)
			}, delay * 1000)
		},

		//after_comment
		after_comment: function () {
			$('#commentform').on('submit', function (e) {
				//TODO
			})
		},

		//after_click
		after_click: function () {

			var el = $(this).data('after-click'),
				popup = this

			$(el).on('click', function (e) {
				e.preventDefault()
				displayPopup(popup)
			})

		}
	}

	//Loop through all popups and attach triggers
	$('.noptin-popup-main-wrapper .noptin-optin-form-wrapper').each(function () {
		var trigger = $(this).data('trigger')

		if (noptinDisplayPopup[trigger]) {
			var cb = noptinDisplayPopup[trigger]
			cb.call(this)
		}
	})

	//Submits forms via ajax
	function subscribe_user(form) {

		//select the form
		$(form)

			//what for submit events
			.on('submit', function (e) {

				//Prevent the form from submitting
				e.preventDefault();

				var that = $(this)

				//Modify form state
				$(this)
					.fadeTo(600, 0.5)
					.find('.noptin_feedback_success, .noptin_feedback_error')
					.empty()
					.hide()


				//Prep all form data
				var data = {},
					fields = $(this).serializeArray()

				jQuery.each(fields, function (i, field) {
					data[field.name] = field.value
					$("#results").append(field.value + " ");
				});

				//Add nonce and action
				data.action = "noptin_new_user"
				data._wpnonce = "noptin.noptin_subscribe"

				//Post it to the server
				$.post(noptin.ajaxurl, data)

					//Update the user of success
					.done( function (data, status, xhr) {

						if( string == typeof data ) {
							$(that)
								.find('.noptin_feedback_error')
								.text(data)
								.show();
							return;
						}

						if (data.action == redirect) {
							window.location = data.redirect;
						}

						if (data.action == msg) {
							$(that)
								.find('.noptin_feedback_success')
								.text(data.msg)
								.show();
						}

						//Gutenberg
						var url = $(that).find('.noptin_form_redirect').val();

						if (url) {
							window.location = url;
						}
					})
					.fail( function() {
						var msg = 'Could not establish a connection to the server.'
						$(that)
								.find('.noptin_feedback_error')
								.text(msg)
								.show();
					} )
					.always(function(){
						$(this).fadeTo(600, 1)
					})
			})
	}

	//Normal forms
	subscribe_user('.noptin-optin-form-wrapper form');

	//Gutenberg forms
	subscribe_user('.wp-block-noptin-email-optin form, .noptin-email-optin-widget form');

}) (jQuery);
