import noptin from './noptin.js'

export default {

	initial_form: null,

	init() {

		var $ = jQuery

		// Are we sending a test email?
		$('.noptin-send-test-email').on('click', this.send_test_email)

		//Upsells
		$('.noptin-filter-recipients').on('click', this.filter_recipients)
		$('.noptin-filter-post-notifications-post-types').on('click', this.new_post_notifications_filter_post_types)
		$('.noptin-filter-post-notifications-taxonomies').on('click', this.new_post_notifications_filter_taxonomies)

		// Stop sending a campaign.
		$('.noptin-stop-campaign').on('click', this.stop_campaign)

	},

	// Stops a sending campaign.
	stop_campaign(e) {
		e.preventDefault();

		let data = {
			id: jQuery(this).data('id'),
			_wpnonce: noptin_params.nonce,
			action: 'noptin_stop_campaign'
		}

		// Init sweetalert.
		Swal.fire({
			titleText: `Are you sure?`,
			text: "This campaign will stop sending and be reverted to draft status.",
			type: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#d33',
			cancelButtonColor: '#9e9e9e',
			confirmButtonText: 'Yes, stop it!',
			showLoaderOnConfirm: true,
			showCloseButton: true,
			focusConfirm: false,
			allowOutsideClick: () => !Swal.isLoading(),

			//Fired when the user clicks on the confirm button
			preConfirm() {

				jQuery.get(noptin_params.ajaxurl, data)
					.done(function () {

						window.location = window.location

						Swal.fire(
							'Success',
							'Your campaign was reverted to draft',
							'success'
						)

					})
					.fail(function () {

						Swal.fire(
							'Error',
							'Unable to stop your campaign. Try again.',
							'error'
						)

					})

				//Return a promise that never resolves
				return jQuery.Deferred()

			},
		})

	},

	//Sends an ajax request to the server requesting it to send a test email
	send_test_email(e) {

		e.preventDefault();

		//Save tinymce
		tinyMCE.triggerSave();

		//Form data
		let data = noptin.getFormData(jQuery(this).closest('form'))

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

				jQuery.post(noptin_params.ajaxurl, data)
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
								footer: `<a href="https://noptin.com/guide/sending-emails/troubleshooting/">How to troubleshoot this error.</a>`
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

}
