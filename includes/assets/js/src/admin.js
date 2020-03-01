(function ($) {

	if ('undefined' == typeof noptinEditor) {
		window.noptinEditor = {}
	}

	//Settings app
	if ('undefined' == typeof noptinSettings) {
		window.noptinSettings = {}
	}

	// Global noptin object
	window.noptin = {}

	// Hook management
	noptin.hooks = require ( '@wordpress/hooks' )

	// Attach the tooltips
	$(document).ready(function(){

		$('.noptin-tip').tooltipster();

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

	});


})(jQuery);
