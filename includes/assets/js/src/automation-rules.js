(function ($) {

	// Enables/Disables the submit button depending on whether or not
	// both a trigger and an action have been set.
	let maybeEnableSubmit = () => {

		// Set up the current trigger and action.
		let trigger = $('.noptin-automation-rule-trigger-hidden').val()
		let action = $('.noptin-automation-rule-action-hidden').val()

		// Are both of them set-up?
		if ( trigger && action ) {
			$('.noptin-automation-rule-create')
				.prop("disabled", false)
				.removeClass('button-secondary')
				.addClass('button-primary')
		} else {
			$('.noptin-automation-rule-create')
				.prop("disabled", true)
				.removeClass('button-primary')
				.addClass('button-secondary')
		}

	}

	$( '#noptin-automation-rule-editor .noptin-automation-rule-trigger' ).ddslick(
		{
			width: 400,
			onSelected: function(data){
				let selected = data.selectedData.value
				$('.noptin-automation-rule-trigger-hidden').val(selected)
				maybeEnableSubmit()
			}
		}
	)

	$( '#noptin-automation-rule-editor .noptin-automation-rule-action' ).ddslick(
		{
			width: 400,
			onSelected: function(data){
				let selected = data.selectedData.value
				$('.noptin-automation-rule-action-hidden').val(selected)
				maybeEnableSubmit()
			}
		}
	)

	if ( $( '#noptin-automation-rule-editor.edit-automation-rule' ).length ) {
		let app = require ( './partials/automation-rules-editor.js' ).default;
		app.mount( '#noptin-automation-rule-editor' );
	}

})(jQuery);
