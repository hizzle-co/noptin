(function ($) {

	// CSV Parser
	let Papa = require('papaparse')

	$(document).ready(function () {

		// Add subscriber.
		$(document).on('click', '.noptin-add-subscriber', function (e) {

			e.preventDefault();
			let $el   = null

			Swal.fire({
				title: 'Add Subscriber',
				html: $('#noptin-create-subscriber-template').html(),
				allowOutsideClick: () => !Swal.isLoading(),
				confirmButtonText: 'Save',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#424242',
				showCloseButton: true,
				focusConfirm: false,
				showLoaderOnConfirm: true,
				onOpen(el) {
					$el = el
					$($el).find('.noptin-create-subscriber-name').focus()
				},
				preConfirm() {

					let request = {
						_wpnonce: noptinSubscribers.nonce,
						action: 'noptin_admin_add_subscriber',
						name: $($el).find('.noptin-create-subscriber-name').val(),
						email:$($el).find('.noptin-create-subscriber-email').val()
					}

					if ( ! request.email ) {
						Swal.showValidationMessage('Enter an email address')
						Swal.hideLoading()
						return;
					}

					jQuery.post(noptinSubscribers.ajaxurl, request)
	
						.done(function ( data ) {

							if (typeof data === 'object' && data.success) {

								Swal.fire({
									icon: 'success',
									title: 'New subscriber added',
									showConfirmButton: false,
									footer: `Reloading the page`
								})

                                window.location = window.location
                            } else {
                                Swal.showValidationMessage(data)
                                Swal.hideLoading()
							}
							
						})
	
						.fail(function (jqXHR) {
							Swal.showValidationMessage(jqXHR.statusText)
							Swal.hideLoading()
							console.log(jqXHR)
						})
	
					return jQuery.Deferred()

				}
			})

		})
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
							json: 'JSON',
							xml: 'XML'
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

			// Simple object for concurrent uploads.
			let importer    = {
				totalBatches: 0,
				processedBatches: 0,
				imported: 0,
				skipped: 0,
				running: false,
				queue : [],

				async import( batch ) {
					batch = await this.clean( batch )
					this.queue.push(batch);
					this.totalBatches++;
  					this.run();
				},

				async clean( batch ) {
					let _clean = []
					batch.forEach(subscriber => {
						if (typeof subscriber === 'object' && subscriber !== null ) {

							// remove null values.
							Object.keys(subscriber).forEach((key) => (subscriber[key] == null) && delete subscriber[key]);
							_clean.push(subscriber)
						}
					});
					return _clean
				},

				async run() {

					if ( this.queue.length && ! this.running ) {
						this.running = true
						$('.swal2-footer').find('.noptin-imported').text(this.imported)
						$('.swal2-footer').find('.noptin-skipped').text(this.skipped)
						this.doImport()
					}

					if ( this.totalBatches == this.processedBatches ) {
						this.done()
					}

				},

				async doImport() {
					let subscribers = this.queue.shift()

					let request = {
						_wpnonce: noptinSubscribers.nonce,
						subscribers,
						action: 'noptin_import_subscribers'
					}

					jQuery.post(noptinSubscribers.ajaxurl, request)

						.done( (data) => {

							if ( typeof data !== 'object' || !data.success ) {
								this.skipped = this.skipped + subcribers.length
								console.log(data)
							} else {
								this.imported = this.imported + data.data.imported
								this.skipped  = this.skipped + data.data.skipped
							}

						})

						.fail( (jqXHR) => {
							console.log(jqXHR)
							this.skipped = this.skipped + subscribers.length
						})

						.always( () => {
							// Then move on to the next batch.
							this.processedBatches++
							this.running = false
							this.run();
						})

				},

				async done() {
					Swal.fire({
						icon: ( this.imported > 0 ) ? 'success' : 'info',
						title: 'Done!',
						confirmButtonText: 'Close',
						html: `Imported: ${this.imported} &nbsp; Skipped: ${this.skipped}`,
						footer: ( this.imported > 0 ) ? '' : 'Check your browser console to see why your subscribers were not imported.',
					})
					if ( this.imported > 0 ) {
						window.location = window.location
					}
				}
			}

			let rows = []

			Swal.fire({
				//title: 'Import Subscribers',
				text: 'Select your CSV file',
				footer: `Import subscribers from any system into Noptin`,
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

						// Change the modal footer.
						$('.swal2-footer').html('<div>Imported: <span class="noptin-imported">0</span></div><div>&nbsp; Skipped: <span class="noptin-skipped">0</span></div>')
						Papa.parse(file, {
							complete() {
								// Import the remaining rows
								importer.import(rows)
							},

							step(row) {

								// Ensure there is data.
								if (row.data) {
									if ( rows.push( row.data ) == 10) {
										importer.import(rows)
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

		// Delete a subscriber.
		$('.noptin-delete-single-subscriber').on('click', function( e ){
			e.preventDefault();

			let href = $( this ).attr( 'href' )
			let email = $( this ).data( 'email' )

			//Init sweetalert
			Swal.fire({
				icon: 'warning',
				titleText: `Delete subscriber`,
				text: email,
				footer: `This will delete the subscriber and all associated data`,
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Delete',
				showCloseButton: true,

				//Fired when the user clicks on the confirm button
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
				html: `Send a new double opt-in confirmation email to <code>${email}<code>`,
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Send',
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
									'Success',
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
									footer: `<a href="https://noptin.com/guide/sending-emails/troubleshooting/">How to troubleshoot this error.</a>`
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
				text: `Are you sure you want to delete all subscribers?`,
				footer: `You won't be able to revert this!`,
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Delete',
				showCloseButton: true,
				allowOutsideClick: () => !Swal.isLoading(),
				showLoaderOnConfirm: true,

				//Fired when the user clicks on the confirm button
				preConfirm() {

					let request = {
						_wpnonce: noptinSubscribers.nonce,
						action: 'noptin_delete_all_subscribers'
					}
	
					jQuery.post(noptinSubscribers.ajaxurl, request)
	
						.done(function () {
							
							Swal.fire({
								icon: 'success',
								title: 'Deleted Subscribers',
								showConfirmButton: false,
								footer: `Reloading the page`
							})
							window.location = window.location
						})
	
						.fail(function (jqXHR) {
							Swal.fire({
								icon: 'error',
								title: 'Could not delete subscribers',
								confirmButtonText: 'Close',
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
