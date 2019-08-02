(function ($) {

	function ouibounce(el, custom_config) {

		var config     = custom_config || {},
		  aggressive   = config.aggressive || false,
		  sensitivity  = setDefault(config.sensitivity, 20),
		  timer        = setDefault(config.timer, 1000),
		  delay        = setDefault(config.delay, 0),
		  callback     = config.callback || function() {},
		  cookieExpire = setDefaultCookieExpire(config.cookieExpire) || '',
		  cookieDomain = config.cookieDomain ? ';domain=' + config.cookieDomain : '',
		  cookieName   = config.cookieName ? config.cookieName : 'viewedOuibounceModal',
		  sitewide     = config.sitewide === true ? ';path=/' : '',
		  _delayTimer  = null,
		  _html        = document.documentElement;

		function setDefault(_property, _default) {
		  return typeof _property === 'undefined' ? _default : _property;
		}

		function setDefaultCookieExpire(days) {
		  // transform days to milliseconds
		  var ms = days*24*60*60*1000;

		  var date = new Date();
		  date.setTime(date.getTime() + ms);

		  return "; expires=" + date.toUTCString();
		}

		setTimeout(attachOuiBounce, timer);
		function attachOuiBounce() {
		  if (isDisabled()) { return; }

		  _html.addEventListener('mouseleave', handleMouseleave);
		  _html.addEventListener('mouseenter', handleMouseenter);
		  _html.addEventListener('keydown', handleKeydown);
		}

		function handleMouseleave(e) {
		  if (e.clientY > sensitivity) { return; }

		  _delayTimer = setTimeout(fire, delay);
		}

		function handleMouseenter() {
		  if (_delayTimer) {
			clearTimeout(_delayTimer);
			_delayTimer = null;
		  }
		}

		var disableKeydown = false;
		function handleKeydown(e) {
		  if (disableKeydown) { return; }
		  else if(!e.metaKey || e.keyCode !== 76) { return; }

		  disableKeydown = true;
		  _delayTimer = setTimeout(fire, delay);
		}

		function checkCookieValue(cookieName, value) {
		  return parseCookies()[cookieName] === value;
		}

		function parseCookies() {
		  // cookies are separated by '; '
		  var cookies = document.cookie.split('; ');

		  var ret = {};
		  for (var i = cookies.length - 1; i >= 0; i--) {
			var el = cookies[i].split('=');
			ret[el[0]] = el[1];
		  }
		  return ret;
		}

		function isDisabled() {
		  return checkCookieValue(cookieName, 'true') && !aggressive;
		}

		// You can use ouibounce without passing an element
		// https://github.com/carlsednaoui/ouibounce/issues/30
		function fire() {
		  if (isDisabled()) { return; }

		  if (el) { el.style.display = 'block'; }

		  callback();
		  disable();
		}

		function disable(custom_options) {
		  var options = custom_options || {};

		  // you can pass a specific cookie expiration when using the OuiBounce API
		  // ex: _ouiBounce.disable({ cookieExpire: 5 });
		  if (typeof options.cookieExpire !== 'undefined') {
			cookieExpire = setDefaultCookieExpire(options.cookieExpire);
		  }

		  // you can pass use sitewide cookies too
		  // ex: _ouiBounce.disable({ cookieExpire: 5, sitewide: true });
		  if (options.sitewide === true) {
			sitewide = ';path=/';
		  }

		  // you can pass a domain string when the cookie should be read subdomain-wise
		  // ex: _ouiBounce.disable({ cookieDomain: '.example.com' });
		  if (typeof options.cookieDomain !== 'undefined') {
			cookieDomain = ';domain=' + options.cookieDomain;
		  }

		  if (typeof options.cookieName !== 'undefined') {
			cookieName = options.cookieName;
		  }

		  document.cookie = cookieName + '=true' + cookieExpire + cookieDomain + sitewide;

		  // remove listeners
		  _html.removeEventListener('mouseleave', handleMouseleave);
		  _html.removeEventListener('mouseenter', handleMouseenter);
		  _html.removeEventListener('keydown', handleKeydown);
		}

		return {
		  fire: fire,
		  disable: disable,
		  isDisabled: isDisabled
		};
	  }


	$('body').on('click', function (event) {
		if (!$(event.target).is('.noptin-optin-form-wrapper')) {
			//$(".noptin-popup-main-wrapper").removeClass("open");
		}
	});

	var displayPopup = function (popup) {
		$(popup)
			.closest('.noptin-popup-main-wrapper')
			.addClass('open')
			.on('click', function ( e ) {

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

	var noptinDisplayPopup = {

		//Displays a popup immeadiately
		immeadiate: function () {
			displayPopup(this)
		},

		//Exit intent
		before_leave: function () {
			var popup = this

			ouibounce(false,
				{
					callback: function () { displayPopup(popup) },
					aggressive: true
				}
			);
		},

		//After the user starts scrolling
		on_scroll: function () {
			var scrollDepth = parseInt( $(this).data('on-scroll') ),
			screenheight = parseInt($(document).height());
			popup = this

			$(document).scroll(function() {
				console.log($(document).scrollTop());
			})
		},

		//after_delay
		after_delay: function () {
			var delay = parseInt( $(this).data('after-delay') ),
			popup = this

			setTimeout(function () {
				displayPopup(popup)
			}, delay * 1000)
		},

		//after_comment
		after_comment: function () {
			$('#commentform').on( 'submit', function( e ){


			})
		},

		//after_click
		after_click: function () {
			var el = $(this).data('after-click'),
			popup = this

			$( el ).on( 'click', function( e ){
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

	//Select apply forms
	$('.wp-block-noptin-email-optin form, .noptin-email-optin-widget form')
		//Watch for form submit events
		.on('submit', function (e) {

			//Prevent the form from submitting
			e.preventDefault();

			//Fade out the form
			var that = $(this);
			$(this).fadeTo(600, 0.2);

			//Hide feedback divs
			$(that).find('.noptin_feedback_success').hide();
			$(that).find('.noptin_feedback_error').hide();

			//Retrieve the email
			var _email = $(this).find('.noptin_form_input_email').val();

			//Send an ajax request to the server
			$.post(noptin.ajaxurl, {
				email: _email,
				action: 'noptin_new_user',
				noptin_subscribe: noptin.noptin_subscribe
			},
				function (data, status, xhr) {
					data = JSON.parse(data);
					$(that).fadeTo(600, 1);
					if (data.result == '1') {
						$(that).find('.noptin_feedback_success').text(data.msg).show();

						var url = $(that).find('.noptin_form_redirect').val();
						if (url) {
							window.location = url;
						}
					} else {
						$(that).find('.noptin_feedback_error').text(data.msg).show();
					}
				})
				.fail(function () {
					$(that).fadeTo(600, 1);
					$(that).find('.noptin_feedback_error').text('Could not establish a connection to the server.').show();
				});
		})
})(jQuery);
