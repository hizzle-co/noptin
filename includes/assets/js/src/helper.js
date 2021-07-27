(function ($) {
	"use strict";

	// Attach the tooltips
	$(document).ready(function () {

		// Activate license.
		$('.noptin-helper-activate-license-modal').on('click', function (e) {
			e.preventDefault();

			var data = {
				'product_id': $(this).data('id'),
				'_wpnonce': noptin_helper.rest_nonce
			}

			// Init sweetalert.
			var activating = $(this).data('activating')
			Swal.fire({
				titleText: noptin_helper.activate_license,
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: noptin_helper.activate,
				cancelButtonText: noptin_helper.cancel,
				showLoaderOnConfirm: true,
				showCloseButton: true,
				input: 'text',
				inputPlaceholder: noptin_helper.license_key,
				footer: activating,
				allowOutsideClick: function () { return !Swal.isLoading() },

				inputValidator: function (value) {
					if (!value) {
						return noptin_helper.license_key
					}
				},

				//Fired when the user clicks on the confirm button.
				preConfirm(license_key) {
					data.license_key = license_key

					jQuery.post(noptin_helper.license_activate_url, data)

						.done(function () {

							Swal.fire({
								position: 'top-end',
								icon: 'success',
								title: noptin_helper.license_activated,
								showConfirmButton: false,
								timer: 1500
							})
							window.location = window.location
						})

						.fail(function (jqXHR) {
							var footer = jqXHR.statusText

							if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
								footer = jqXHR.responseJSON.message
							}

							Swal.fire({
								icon: 'error',
								title: footer,
								footer: '<code>' + noptin_helper.license_activation_error + '</code>',
								showCloseButton: true,
								confirmButtonText: noptin_helper.close,
								confirmButtonColor: '#9e9e9e',
								showConfirmButton: false,
							})

						})

					//Return a promise that never resolves
					return jQuery.Deferred()
				}
			})
		})

		// Deactivate license.
		$('.noptin-helper-deactivate-license-modal').on('click', function (e) {
			e.preventDefault();

			var data = {
				'license_key': $(this).data('license_key'),
				'_wpnonce': noptin_helper.rest_nonce
			}

			//Init sweetalert
			Swal.fire({
				icon: 'warning',
				titleText: noptin_helper.deactivate_license,
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: noptin_helper.deactivate,
				cancelButtonText: noptin_helper.cancel,
				showLoaderOnConfirm: true,
				showCloseButton: true,
				footer: noptin_helper.deactivate_warning,
				allowOutsideClick: function () { return !Swal.isLoading() },

				//Fired when the user clicks on the confirm button.
				preConfirm() {

					jQuery.post(noptin_helper.license_deactivate_url, data)

						.done(function () {

							Swal.fire({
								position: 'top-end',
								icon: 'success',
								title: noptin_helper.license_deactivated,
								showConfirmButton: false,
								timer: 1500
							})
							window.location = window.location
						})

						.fail(function (jqXHR) {
							var footer = jqXHR.statusText

							if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
								footer = jqXHR.responseJSON.message
							}

							Swal.fire({
								icon: 'error',
								title: noptin_helper.license_deactivation_error,
								footer: footer,
								showCloseButton: true,
								confirmButtonText: noptin_helper.close,
								confirmButtonColor: '#9e9e9e',
								showConfirmButton: false,
							})

						})

					//Return a promise that never resolves
					return jQuery.Deferred()
				}
			})
		})

	});


})(jQuery);
