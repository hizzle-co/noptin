(function ($) {

	// CSV Parser
	let Papa = require('papaparse')

	$(document).ready(function () {

		// Export subscribers.
		$(document).on('click', '.noptin-export-subscribers', function (e) {

			e.preventDefault();

			let $el = null,
				fields = []

			// Select fields.
			Swal.fire({
				title: 'Export Subscribers',
				html: $('#noptin-subscriber-fields-select-template').html(),
				allowOutsideClick: () => !Swal.isLoading(),
				confirmButtonText: 'Export',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#424242',
				showCloseButton: true,
				width: '40rem',
				onOpen(el) {
					$el = el
					$(el).find('.noptin-subscriber-fields-select').select2({ width: '100%' });
				},
				preConfirm() {
					fields = $($el).find('.noptin-subscriber-fields-select').val();
				}
			}).then((result) => {
				if (result.value) {
					// Select export type.
					Swal.fire({
						title: 'Select file type',
						allowOutsideClick: () => !Swal.isLoading(),
						input: 'radio',
						inputValue: 'json',
						inputOptions: {
							csv: 'CSV',
							json: 'JSON'
						},
						confirmButtonText: 'Download',
						showCancelButton: true,
						confirmButtonColor: '#3085d6',
						cancelButtonColor: '#424242',
						showLoaderOnConfirm: true,
						showCloseButton: true,
						preConfirm( type ) {
							let params = $.param( {
								file_type: type,
								fields: fields
							} );
							let url = $('.noptin-export-subscribers').attr('href') + '&' + params;
							window.location.href = url
						}
					})
				}
			})
		})

		// Import subscribers.
		$(document).on('click', '.noptin-import-subscribers', function (e) {

			e.preventDefault();

			let imported = 0,
				skipped = 0,
				rows = [],
				error = 'All subscribers imported successfully',
				icon = 'info',
				title = 'Done!'

			// Imports subscribers.
			let noptin_import_subscribers = (subscribers, success = false) => {

				// Remove null values from subscriber properties.
				let _subscribers = []
				subscribers.forEach(subscriber => {
					if (typeof subscriber === 'object' && subscriber !== null) {

						// remove null values.
						Object.keys(subscriber).forEach((key) => (subscriber[key] == null) && delete subscriber[key]);
						_subscribers.push(subscriber)
					}
				});

				let request = {
					_wpnonce: noptinSubscribers.nonce,
					subscribers: _subscribers,
					action: 'noptin_import_subscribers'
				}

				jQuery.post(noptinSubscribers.ajaxurl, request)

					.done(function (data) {

						if (typeof data !== 'object' || !data.success) {
							skipped = skipped + _subscribers.length
							error = 'An error occurred while importing subscribers'
							icon = 'error'
							title = 'Error!'
							console.log(data)
						} else {
							imported = imported + data.data.imported
							skipped = skipped + data.data.skipped
						}

					})

					.fail(function (jqXHR) {
						console.log(jqXHR)
						error = jqXHR.statusText
						icon = 'error'
						title = 'Error!'
						skipped = skipped + _subscribers.length
					})

					.always(function () {
						if (success) {
							Swal.fire({
								icon: icon,
								title: title,
								confirmButtonText: 'Close',
								html: `Imported: ${imported} &nbsp; Skipped: ${skipped}`,
								footer: error
							})
						}
					})
			}

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
				cancelButtonColor: '#424242',
				showLoaderOnConfirm: true,
				showCloseButton: true,

				//Fired when the user clicks on the confirm button
				preConfirm(file) {

					if (file) {
						Papa.parse(file, {
							complete() { noptin_import_subscribers(rows, true) },

							step(row) {

								// Ensure there is data.
								if (row.data) {
									let length = rows.push(row.data)
									if (length == 10) {
										setTimeout(function () {
											noptin_import_subscribers(rows)
										}, 100)
										rows = []
									}
								}

							},

							worker: true,
							header: true,
							dynamicTyping: true
						});

						//Return a promise that never resolves
						return jQuery.Deferred()
					}

				}
			})


		})

	});

})(jQuery);
