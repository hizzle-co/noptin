import regeneratorRuntime from "regenerator-runtime";

(function ($) {

	$(document).ready(function () {

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
						email: email
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

	});

})(jQuery);
