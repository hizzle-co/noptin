(function ($) {

	//The newsletter editor
	window.noptinNewsletterEditor = require ( './partials/newsletter-editor.js' ).default

	//Init the newsletter editor
	$(document).ready(function(){

		// Init the newsletter editor.
		noptinNewsletterEditor.init()

		// Hide/Show the schedule editor.
		$('.noptin-newsletter-schedule-control .edit-schedule').on( 'click', function( e ) {
			e.preventDefault()

			let parent = $( this ).closest('.noptin-newsletter-schedule-control')
			parent.find('.noptin-schedule').slideDown()
			parent.find('.edit-schedule').fadeOut()
			parent.find('.scheduled').show()
			parent.find('.not-scheduled').hide()
			parent.find('.scheduled-date').hide()
		})

		// Reverts form to original after a data has been saved.
		let hideScheduleEditor = function( el ) {

			el.find('.noptin-schedule').slideUp()
			el.find('.edit-schedule').fadeIn()

			if ( 'scheduled' == el.data( 'status' ) ) {

				el.find('.scheduled-date').show()
				el.find('.scheduled').show()
				el.find('.not-scheduled').hide()

				if ( el.data('schedules') ) {
					let button_id = el.data('schedules')
	
					$( `#${button_id}` ).val( $( `#${button_id}` ).data('scheduled') )
				}

			} else {

				el.find('.scheduled-date').hide()
				el.find('.scheduled').hide()
				el.find('.not-scheduled').show()

				if ( el.data('schedules') ) {
					let button_id = el.data('schedules')
	
					$( `#${button_id}` ).val( $( `#${button_id}` ).data('not-scheduled') )
				}

			}

		}

		hideScheduleEditor( $('.noptin-newsletter-schedule-control') )

		// Save date changes.
		$('.noptin-newsletter-schedule-control .save-timestamp').on( 'click', function( e ) {
			e.preventDefault()

			let parent = $( this ).closest('.noptin-newsletter-schedule-control')

			let selected_date = parent.find('.noptin-schedule-input-date').val()
			let selected_time = parent.find('.noptin-schedule-input-time').val()
			let date_time     = `${selected_date} ${selected_time}`

			parent.find('.scheduled-date').text(date_time)
			parent.find('.noptin-schedule-selected-date').val(date_time)
			parent.data( 'status', 'scheduled' )

			hideScheduleEditor( parent )

		})

		// Hide the schedule editor.
		$('.noptin-newsletter-schedule-control .cancel-timestamp').on( 'click', function( e ) {

			e.preventDefault()
			hideScheduleEditor( $( this ).closest('.noptin-newsletter-schedule-control') )

		})

		// Attach the date pickers
		$('.noptin-schedule-input-date').flatpickr(
			{
				dateFormat: "Y-m-d",
				minDate: "today",
				altInput: true,
				altFormat: 'F j, Y'
			}
		)

		// Attach the time pickers
		$('.noptin-schedule-input-time').flatpickr(
			{
				enableTime: true,
				noCalendar: true,
				dateFormat: "H:i",
				time_24hr: true,
			}
		)

	});

})(jQuery);
