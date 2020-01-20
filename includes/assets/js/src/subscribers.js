(function ($) {

	// CSV Parser
	let Papa = require('papaparse')

	const Toast = Swal.mixin({
		toast: true,
		position: 'bottom-end',
		showConfirmButton: false,
		timer: 3000,
		timerProgressBar: true,
		onOpen: (toast) => {
			toast.addEventListener('mouseenter', Swal.stopTimer)
			toast.addEventListener('mouseleave', Swal.resumeTimer)
		}
	})

	// Imports
	$(document).ready(function () {

		$(document).on('click', '.noptin-import-subscribers', function (e) {

			e.preventDefault();

			Swal.fire({
				//title: 'Import Subscribers',
				text: 'Select your Noptin export file below to import subscribers',
				input: 'file',
				inputAttributes: {
					accept: '.csv',
					'aria-label': 'select your import file'
				},
				allowOutsideClick: () => !Swal.isLoading(),
				confirmButtonText: 'Import',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				showLoaderOnConfirm: true,
				showCloseButton: true,

				//Fired when the user clicks on the confirm button
				preConfirm(file) {

					if (file) {
						Papa.parse(file, {
							complete: function (data) {

								let request = {
									_wpnonce: noptinSubscribers.nonce,
									subscribers: data.data,
									action: 'noptin_import_subscribers'
								}

								jQuery.post(noptinSubscribers.ajaxurl, request)

									.done(function (data) {

										if (data.success) {

											Swal.fire(
												'',
												data.data,
												'success'
											)

										} else {

											Swal.fire({
												icon: 'error',
												title: 'Error!',
												text: data.data,
												showCloseButton: true,
												confirmButtonText: 'Close',
												confirmButtonColor: '#9e9e9e',
											})

										}

									})

									.fail(function (jqXHR) {

										Swal.fire({
											icon: 'error',
											title: 'Unable to connect',
											text: 'This might be a problem with your server or your internet connection',
											showCloseButton: true,
											confirmButtonText: 'Close',
											confirmButtonColor: '#9e9e9e',
											footer: `<code>Status: ${jqXHR.status} &nbsp; Status text: ${jqXHR.statusText}</code>`
										})
				
									})

							},
							worker: true,
							header: true
						});

						//Return a promise that never resolves
						return jQuery.Deferred()
					}

				}
			})


		})

	});

})(jQuery);
