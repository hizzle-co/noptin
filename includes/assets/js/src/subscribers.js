import regeneratorRuntime from "regenerator-runtime";

(function ($) {

	// CSV Parser
	let Papa = require('papaparse')
	let subscribersPageData = $('#noptin-subscribers-page-data').data()

	$(document).ready(function () {

		// Add subscriber.
		$(document).on('click', '.noptin-add-subscriber', function (e) {

			e.preventDefault();
			let $el   = null

			Swal.fire({
				title: noptinSubscribers.add,
				html: $('#noptin-create-subscriber-template').html(),
				allowOutsideClick: () => !Swal.isLoading(),
				confirmButtonText: noptinSubscribers.save,
				showCancelButton: true,
				cancelButtonText: noptinSubscribers.cancel,
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
						email:$($el).find('.noptin-create-subscriber-email').val(),
						data: subscribersPageData
					}

					if ( ! request.email ) {
						Swal.showValidationMessage( noptinSubscribers.missing_email )
						Swal.hideLoading()
						return;
					}

					jQuery.post(noptinSubscribers.ajaxurl, request)
	
						.done(function ( data ) {

							if (typeof data === 'object' && data.success) {

								Swal.fire({
									icon: 'success',
									title: noptinSubscribers.add_success,
									showConfirmButton: false,
									footer: noptinSubscribers.reloading
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
				title: noptinSubscribers.export,
				html: $('#noptin-subscriber-fields-select-template').html(),
				allowOutsideClick: () => !Swal.isLoading(),
				confirmButtonText: noptinSubscribers.exportbtn,
				showCancelButton: true,
				cancelButtonText: noptinSubscribers.cancel,
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
						title: noptinSubscribers.file,
						allowOutsideClick: () => !Swal.isLoading(),
						input: 'radio',
						inputValue: 'json',
						inputOptions: {
							csv: 'CSV',
							json: 'JSON',
							xml: 'XML'
						},
						confirmButtonText: noptinSubscribers.download,
						showCancelButton: true,
						cancelButtonText: noptinSubscribers.cancel,
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
						action: 'noptin_import_subscribers',
						data: subscribersPageData,
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
						title: noptinSubscribers.done,
						confirmButtonText: noptinSubscribers.close,
						html: `${noptinSubscribers.imported}: ${this.imported} &nbsp; ${noptinSubscribers.skipped}: ${this.skipped}`,
						footer: ( this.imported > 0 ) ? '' : noptinSubscribers.import_fail,
					})
					if ( this.imported > 0 ) {
						window.location = window.location
					}
				}
			}

			let rows = []

			Swal.fire({
				text: noptinSubscribers.import_title,
				footer: noptinSubscribers.import_footer,
				input: 'file',
				inputAttributes: {
					accept: '.csv',
					'aria-label': noptinSubscribers.import_label
				},
				allowOutsideClick: () => !Swal.isLoading(),
				confirmButtonText: noptinSubscribers.import,
				showCancelButton: true,
				cancelButtonText: noptinSubscribers.cancel,
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
				titleText: noptinSubscribers.delete_subscriber,
				text: email,
				footer: noptinSubscribers.delete_footer,
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: noptinSubscribers.delete,
				cancelButtonText: noptinSubscribers.cancel,

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
