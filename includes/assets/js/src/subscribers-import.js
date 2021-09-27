import regeneratorRuntime from "regenerator-runtime";

(function ($) {

	window.noptin = window.noptin || {}

	// CSV Parser
	window.noptin.Papa = require('papaparse')

	window.noptin.csv_parser = {

		// Whether or not this is the first step.
		first_step: true,

		// An array of headers in the CSV file.
		headers: [],

		// An array of subscribers to import.
		rows: [],

		// A map of Noptin fields to CSV headers.
		mapped: {},

		// Custom field values.
		custom: {},

		// Whether or not to update existing subscribers.
		update: false,

		// The number of subscribers that were updated in the import.
		updated: 0,

		// The number of imports that failed.
		failed: 0,

		// The number of new imported subscribers.
		imported: 0,

		// The number of skipped subscribers.
		skipped: 0,

		// Validates CSV files
		validate_csv_file( file_data ) {

			// Get extension.
			var ext = file_data.name.match(/\.([^\.]+)$/)[1];

			// Ensure it is either a CSV or TXT file.
			switch (ext.toString().toLowerCase()) {
				case 'csv':
				case 'txt':
					return true
				default:
					console.log(ext)
					return false
			}

		},

		// Runs the actual import.
		import() {

			// Prepare the request.
			let request = {
				_wpnonce: noptinSubscribers.nonce,
				action: 'noptin_import_subscribers',

				// Stringify to workaround post_max_size of 1000.
				data: JSON.stringify({
					rows: window.noptin.csv_parser.rows,
					headers: window.noptin.csv_parser.headers,
					mapped: window.noptin.csv_parser.mapped,
					update: window.noptin.csv_parser.update,
					custom: window.noptin.csv_parser.custom,
				})
			}

			// Post the subscribers.
			return jQuery.post(noptinSubscribers.ajaxurl, request)

				.done( ( result ) => {

					// Checked whether the request succeeded or failed
					if ( typeof result !== 'object' || ! result.success ) {
						this.failed = Number( this.failed ) + this.rows.length
					} else {
						this.imported = Number( this.imported ) + Number( result.data.imported )
						this.updated  = Number( this.updated ) + Number ( result.data.updated )
						this.skipped  = Number( this.skipped ) + Number ( result.data.skipped )
					}

				})

				// The request failed.
				.fail( ( jqXHR ) => {
					console.log(jqXHR)
					this.failed = Number( this.failed ) + this.rows.length
				})

				// Reset the rows.
				.always( () => {
					this.rows = []
					$('.noptin-imported').text( this.imported )
					$('.noptin-failed').text( this.failed )
					$('.noptin-updated').text( this.updated )
					$('.noptin-skipped').text( this.skipped )
				})
		},

		// Handles the actual import of subscribers.
		handle_import_form_submission ( e ) {
			e.preventDefault();

			// Ensure a file is selected.
			let file_data = $('#noptin-upload').prop('files')[0];

			if ( ! file_data ) {
				alert( 'Select a CSV file first' );
				return
			}

			if ( ! this.validate_csv_file( file_data ) ) {
				alert( 'Invalid file type. Only CSV files are allowed' );
				return
			}

			// Show the spinner.
			$('.noptin-import-subscribers-form .spinner').css( 'visibility', 'visible' );

			// Should we update existing subscribers?
			this.update = $('#noptin-importer-update-existing:checked').length > 0;

			// Parse the CSV.
			window.noptin.Papa.parse(
				file_data,
				{

					step: (results, parser) => {
	
						// Ensure there is data.
						if ( !results.data) {
							return;
						}

						if ( this.first_step ) {
							this.handle_first_step(results, parser )
						} else {
							this.handle_row( results, parser )
						}

					},

					complete: () => {
						if ( this.rows.length ) {
							this.import()
								.always( () => {
									$('.noptin-importing').addClass('hidden')
									$('.noptin-import-complete').removeClass('hidden')
								})
						} else {
							$('.noptin-importing').addClass('hidden')
							$('.noptin-import-complete').removeClass('hidden')
						}
						
					},

					error(error, file) {
						console.log(error);
					},
	
					skipEmptyLines: 'greedy',
					dynamicTyping: true,
				}
			);
		},

		// Handles the first step of the import.
		handle_first_step(results, parser ) {

			// Pause the parsing.
			parser.pause()

			// Prepare file headers.
			this.headers = results.data
			this.first_step = false

			// Send headers to API, and receive Map Fields HTML
			let request = {
				_wpnonce: noptinSubscribers.nonce,
				headers: results.data,
				action: 'noptin_prepare_subscriber_fields'
			}

			jQuery
				.post(noptinSubscribers.ajaxurl, request)

				.done( (data) => {

					if ( ! data.success ) {
						alert( data.data )
					} else {
						$('.noptin-import-subscribers-form').replaceWith(data.data)

						// Handles the actual import of subscribers.
						$('.noptin-import-subscribers-form-map-fields').on( 'submit', ( e ) => {
							e.preventDefault();
							this.handle_field_map( parser )
						});

					}

				})

				.fail( (jqXHR) => {
					alert( 'An error occured. Please reload and try again.' )
				})

				.always( () => {
					$('.noptin-import-subscribers-form .spinner').css( 'visibility', 'hidden' );
				})
		},

		// Handles the field maps.
		handle_field_map( parser ) {

			// map form fields.
			let that = this
			$('.noptin-map-field').each(function(){

				if ( $(this).val() != '0' ) {
					that.mapped[$(this).data('maps')] = $(this).val()
				}

				if ( $(this).val() == '-1' ) {
					that.custom[$(this).data('maps')] = $(this).closest('td').find('.noptin-custom-field-value input').val()
				}

			})

			// Ensure that we have an email.
			if ( ! this.mapped['email'] ) {
				alert( 'You need to map the email field.' );
				return;
			}

			// Hide the form.
			$('.noptin-import-subscribers-form-map-fields').addClass('hidden')

			// Display the progress.
			$('.noptin-import-progress').removeClass('hidden')

			// Scroll to top.
			$('html, body').animate({ scrollTop: 0 }, 'slow');

			// Resume the parsing.
			parser.resume()
		},

		// Processes a single row.
		handle_row( results, parser ) {

			// Push to the list of rows and process imports in batches of 10.
			if ( this.rows.push( results.data ) == 10) {
				parser.pause()

				this.import()
					.always( () => {
						parser.resume()
					})

			}

		},

	}

	$(document).ready(function () {

		// Upload subscribers import file.
		$('.noptin-import-subscribers-form #noptin-upload').on('change', function() {
			let file_data = $(this).prop('files')[0];

			// Enable disable the continue button based on whether a file has been selected.
			if ( file_data ) {
				$('.noptin-import-continue').removeAttr('disabled')
			}else{
				$('.noptin-import-continue').attr('disabled','disabled')
			}

		});

		// Handles the actual import of subscribers.
		$('.noptin-import-subscribers-form').on('submit', ( e ) => { window.noptin.csv_parser.handle_import_form_submission( e ) } );

		// Manually enter field value.
		$( 'body').on('change', '.noptin-field-can-have-custom-value', function() {

			if ( '-1' == $(this).val() ) {
				$(this).closest('td').find('.noptin-custom-field-value').removeClass('hidden')
			}else{
				$(this).closest('td').find('.noptin-custom-field-value').addClass('hidden')
			}

		});
	});

})(jQuery);
