import regeneratorRuntime from "regenerator-runtime";

(function ($) {

	let subscribersPageData = $('#noptin-subscribers-page-data').data()

	$(document).ready(function () {

		// Delete a subscriber.
		$('.noptin-delete-single-subscriber').on('click', function( e ){
			e.preventDefault();

			let href = $( this ).attr( 'href' )
			let email = $( this ).data( 'email' )

			//Init sweetalert
			Swal.fire({
				icon: 'warning',
				titleText: noptinSubscribers.delete_subscriber,
				text: email,
				footer: noptinSubscribers.delete_footer,
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: noptinSubscribers.delete,
				cancelButtonText: noptinSubscribers.cancel,

				// Fired when the user clicks on the confirm button.
				preConfirm() {
					window.location.href = href
				}
			})
		})

		// Resend a double opt-in email
		$(document).on('click', '.send-noptin-subscriber-double-optin-email', function (e) {
			e.preventDefault();

			let email = $( this ).data( 'email' )

			//Init sweetalert
			Swal.fire({
				icon: 'info',
				html: `${noptinSubscribers.double_optin} <code>${email}<code>`,
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: noptinSubscribers.send,
				showCloseButton: true,
				allowOutsideClick: () => !Swal.isLoading(),
				showLoaderOnConfirm: true,

				// Fired when the user clicks on the confirm button
				preConfirm() {

					let request = {
						_wpnonce: noptinSubscribers.nonce,
						action: 'noptin_send_double_optin_email',
						email: email,
						data: subscribersPageData
					}

					jQuery.post(noptinSubscribers.ajaxurl, request)

						.done( function ( data ) {

							if (data.success) {

								Swal.fire(
									noptinSubscribers.success,
									data.data,
									'success'
								)

							} else {

								Swal.fire({
									icon: 'error',
									title: noptinSubscribers.error,
									text: data.data,
									showCloseButton: true,
									cancelButtonText: noptinSubscribers.cancel,
									confirmButtonText: noptinSubscribers.close,
									confirmButtonColor: '#9e9e9e',
									footer: `<a href="https://noptin.com/guide/sending-emails/troubleshooting/">${noptinSubscribers.troubleshoot}</a>`
								})

							}
						})

						.fail(function (jqXHR) {

							Swal.fire({
								icon: 'error',
								title: noptinSubscribers.connect_error,
								text: noptinSubscribers.connect_info,
								showCloseButton: true,
								confirmButtonText: noptinSubscribers.close,
								cancelButtonText: noptinSubscribers.cancel,
								confirmButtonColor: '#9e9e9e',
								footer: `<code>Status: ${jqXHR.status} &nbsp; Status text: ${jqXHR.statusText}</code>`
							})

						})

					return jQuery.Deferred()
				}
			})
		})

		// Delete all subcribers.
		$('.noptin-delete-subscribers').on('click', function( e ){
			e.preventDefault();

			//Init sweetalert
			Swal.fire({
				icon: 'question',
				text: noptinSubscribers.delete_all,
				footer: noptinSubscribers.no_revert,
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: noptinSubscribers.delete,
				cancelButtonText: noptinSubscribers.cancel,
				showCloseButton: true,
				allowOutsideClick: () => !Swal.isLoading(),
				showLoaderOnConfirm: true,

				//Fired when the user clicks on the confirm button
				preConfirm() {

					let request = {
						_wpnonce: noptinSubscribers.nonce,
						action: 'noptin_delete_all_subscribers',
						data: subscribersPageData
					}

					jQuery.post(noptinSubscribers.ajaxurl, request)

						.done(function () {

							Swal.fire({
								icon: 'success',
								title: noptinSubscribers.deleted,
								showConfirmButton: false,
								footer: noptinSubscribers.reloading
							})
							window.location = window.location
						})

						.fail(function (jqXHR) {
							Swal.fire({
								icon: 'error',
								title: noptinSubscribers.no_delete,
								confirmButtonText: noptinSubscribers.close,
								footer: jqXHR.statusText
							})
							console.log(jqXHR)
						})

					return jQuery.Deferred()
				}
			})
		})

	});

})(jQuery);
