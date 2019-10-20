(function ($) {
	"use strict";

	if ('undefined' == typeof noptinEditor) {
		window.noptinEditor = {}
	}

	//Settings app
	if ('undefined' == typeof noptinSettings) {
		window.noptinSettings = {}
	}

	//The main Editor apps
	window.noptinOptinEditor = require ( './optin-editor.js' ).default
	window.noptinSettingsApp = require ( './settings.js' ).default

	//Newsletter select recipients
	$(document).ready(function(){

		//Attach the tooltips
		$('.noptin-tip').tooltipster();

		//Create new automation
		$('.no-campaign-create-new-campaign.thickbox, .create-new-campaign.thickbox').on( 'click', function() {
			$('#noptin-automations-popup').addClass('showing')
		})

		//Automations select
		let initial_form = null;
		let automation_events = function( e ) {

			e.preventDefault()

			initial_form   = $('#noptin-automations-popup').clone()
			let automation = $(this)

			$('.noptin-automation-type-select').not(automation).fadeOut( 'fast', () => {

				//Replace title with our title
				$('#noptin-automations-popup').find( 'h2' ).html( automation.find( 'h3' ).html() )

				//Remove unnecessary elements
				automation.find( 'h3' ).remove()
				automation.find( 'span.button' ).remove()

				//Display the automations form
				automation.find( 'form' ).show()

				$( '.noptin-automation-setup-form' ).off( 'submit', createAutomation)
				$( '.noptin-automation-setup-form' ).on( 'submit', createAutomation)

				//Hide errors
				automation.find('.noptin_feedback_success, .noptin_feedback_error').empty()

				//Find the ul and replace it with our inner form
				$('#noptin-automations-popup').find( 'ul' ).replaceWith( automation.html() )

			});

			let func = function () {
				$('#noptin-automations-popup').replaceWith( initial_form )
				$('.noptin-automation-type-select.enabled').off( 'click', automation_events )
				$('.noptin-automation-type-select.enabled').on( 'click', automation_events )
				$(window).unbind('tb_unload', func );
			}

			$(window).bind('tb_unload', func);

		}
		$('.noptin-automation-type-select.enabled').on( 'click', automation_events )

		var createAutomation = function( e ){
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

			data.action = "noptin_setup_automation";

			//Post it to the server
			$.post(noptin_params.ajaxurl, data)

				//Redirect to the form edit page
				.done( (data, status, xhr) => {

					if( 'string' == typeof data ) {
						$(this)
							.find('.noptin_feedback_error')
							.text(data)
							.show();
						return;
					}

					window.location = data.redirect;

				} )

				.fail( () => {
					var msg = 'Could not establish a connection to the server.'
					$(this)
							.find('.noptin_feedback_error')
							.text(msg)
							.show();
				} )

				.always( () => {
					$(this).fadeTo(600, 1)
				})
		}

	});


})(jQuery);
