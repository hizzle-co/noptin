(function ($) {

	if ('undefined' == typeof noptinEditor) {
		window.noptinEditor = {}
	}

	// Attach the tooltips
	$(document).ready(function () {

		$(`
			<a href="#" class="noptin-import-forms-button page-title-action">Import</a>
			<a href="${noptin_params.donwload_forms}" class="noptin-export-forms-button page-title-action">Export</a>
		`).insertBefore('.post-type-noptin-form .wrap .wp-header-end');


		$(document).on('click', '.noptin-import-forms-button', function (e) {

			e.preventDefault();

			Swal.fire({
				//title: 'Import Forms',
				text: 'Select your Noptin Export file',
				footer: `Import forms from a Noptin export file`,
				input: 'file',
				inputAttributes: {
					accept: '.json',
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

						let reader = new FileReader();

						reader.onload = function (event) {

							let request = {
								_wpnonce: noptin_params.nonce,
								forms: event.target.result,
								action: 'noptin_import_forms'
							}

							jQuery.post(noptin_params.ajaxurl, request)

								.done((data) => {

									if (typeof data === 'object') {
										window.location = window.location
									} else {
										Swal.fire(
											'Error!',
											data,
											'error'
										)
									}

								})

								.fail((jqXHR) => {
									console.log(jqXHR)
									Swal.fire(
										'Error!',
										jqXHR.statusText,
										'error'
									)
								})

						};

						reader.onerror = function (event) {
							Swal.fire(
								'Error!',
								`File could not be read! Code ${event.target.error.code}`,
								'error'
							)
							console.error(event.target.error);
						};

						reader.readAsText(file);

						//Return a promise that never resolves
						return jQuery.Deferred()
					}

				}
			})

		})

	});

	// Optin forms editor app.
	if ( jQuery('#noptin_form_editor').length ) {
		window.noptinOptinEditor = require('./partials/optin-editor.js').default
	}

})(jQuery);
