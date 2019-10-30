import noptin from './noptin.js'

export default {

	initial_form: null,

	init() {

		//Watch for clicks on the create new automation button
		$('.no-campaign-create-new-campaign.thickbox, .create-new-campaign.thickbox').on('click', function () {
			$('#noptin-automations-popup').addClass('showing')
		})

		$('.noptin-automation-type-select.enabled').on('click', this.automation_events)

		//Send test email
		$('#wp-noptinemailbody-media-buttons').append('&nbsp;<a class="button noptin-send-test-email"><span class="wp-menu-image dashicons-before dashicons-email-alt"></span>Send a test email</a>')

		//Are we sending a test email?
		$('.noptin-send-test-email').on('click', this.send_test_email)

		//Filter email recipients
		$('.noptin-filter-recipients').on('click', this.filter_recipients)

	},

	automation_events(e) {

		e.preventDefault()

		this.initial_form = $('#noptin-automations-popup').clone()
		let automation = $(this)

		$('.noptin-automation-type-select').not(automation).fadeOut('fast', () => {

			//Replace title with our title
			$('#noptin-automations-popup').find('h2').html(automation.find('h3').html())

			//Remove unnecessary elements
			automation.find('h3').remove()
			automation.find('span.button').remove()

			//Display the automations form
			automation.find('form').show()

			$('.noptin-automation-setup-form').off('submit', this.createAutomation)
			$('.noptin-automation-setup-form').on('submit', this.createAutomation)

			//Hide errors
			automation.find('.noptin_feedback_success, .noptin_feedback_error').empty()

			//Find the ul and replace it with our inner form
			$('#noptin-automations-popup').find('ul').replaceWith(automation.html())

		});

		let func = function () {
			$('#noptin-automations-popup').replaceWith(initial_form)
			$('.noptin-automation-type-select.enabled').off('click', automation_events)
			$('.noptin-automation-type-select.enabled').on('click', automation_events)
			$(window).unbind('tb_unload', func);
		}

		$(window).bind('tb_unload', func);;


	},

	createAutomation(e) {

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
			.done((data, status, xhr) => {

				if ('string' == typeof data) {
					$(this)
						.find('.noptin_feedback_error')
						.text(data)
						.show();
					return;
				}

				window.location = data.redirect;

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
	},

	//Sends an ajax request to the server requesting it to send a test email
	send_test_email(e) {

		e.preventDefault();

		//Form data
		let data = noptin.getFormData($(this).closest('form'))

		//Init sweetalert
		Swal.fire({
			titleText: `Send a test email to:`,
			showCancelButton: true,
			confirmButtonColor: '#3085d6',
			cancelButtonColor: '#d33',
			confirmButtonText: 'Send',
			showLoaderOnConfirm: true,
			showCloseButton: true,
			input: 'email',
			inputValue: noptin_params.admin_email,
			inputPlaceholder: noptin_params.admin_email,
			allowOutsideClick: () => !Swal.isLoading(),

			//Fired when the user clicks on the confirm button
			preConfirm(email) {

				//Add the test email
				data.email = email

				//Add action
				data.action = "noptin_send_test_email"

				$.post(noptin_params.ajaxurl, data)
					.done(function (data) {

						if (data.success) {

							Swal.fire(
								'Success',
								data.data,
								'success'
							)

						} else {

							Swal.fire({
								type: 'error',
								title: 'Error!',
								text: data.data,
								showCloseButton: true,
								confirmButtonText: 'Close',
								confirmButtonColor: '#9e9e9e',
								footer: `<a href="https://noptin.com/guide/email-troubleshooting">How to troubleshoot this error.</a>`
							})

						}

					})
					.fail(function (jqXHR) {

						Swal.fire({
							type: 'error',
							title: 'Unable to connect',
							text: 'This might be a problem with your server or your internet connection',
							showCloseButton: true,
							confirmButtonText: 'Close',
							confirmButtonColor: '#9e9e9e',
							footer: `<code>Status: ${jqXHR.status} &nbsp; Status text: ${jqXHR.statusText}</code>`
						})

					})

				//Return a promise that never resolves
				return jQuery.Deferred()

			},
		})

	},

	//Filters email recipients
	filter_recipients(e) {

		e.preventDefault();

		if ( $('#noptin_recipients_filter_div').length ) {

		} else {

			Swal.fire({
				titleText: `Addon Needed!`,
				html: `Install the addon to filter recipients by their sign up method/form, tags or the time in which they signed up.`,
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Install Addon',
				showCloseButton: true,
			}).then( (result) => {

				if (result.value) {
				  window.location.href = 'https://noptin.com/product/ultimate-addons'
				}

			  })

		}
	}
}
