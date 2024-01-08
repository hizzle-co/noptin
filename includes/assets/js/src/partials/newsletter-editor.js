export default {

	initial_form: null,

	init() {

		var $ = jQuery

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

}
