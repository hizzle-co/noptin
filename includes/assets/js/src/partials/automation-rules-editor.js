import noptinSelectComponent from './noptin-select.vue'

var rulesApp = new Vue({

	components: {

		// Select2.
		'noptin-select': noptinSelectComponent,

	},

	el: '#noptin-automation-rule-editor',

	data: jQuery.extend( true, {}, noptinRules ),

	methods: {

		saveRule () {

			var $ = jQuery

			// Provide visual feedback by fading the form.
			$(this.$el).fadeTo("fast", 0.33);

			//Prepare state data
			var data  = this.$data
			var error = this.error
			var saved = this.saved
			var el    = this.$el

			//Hide form notices
			$(this.$el).find('.noptin-save-saved').hide()
			$(this.$el).find('.noptin-save-error').hide()

			//Post the state data to the server
			jQuery.post(noptinRules.ajaxurl, {
				_ajax_nonce: noptinRules.nonce,
				action: "noptin_save_automation_rule",
				data
			})

				//Show a success msg after we are done
				.done( () => {
					$(el)
						.fadeTo("fast", 1)
						.find('.noptin-save-saved')
						.show()
						.html(`<p>${saved}</p>`)
				})

				//Else alert the user about the error
				.fail( () => {
					$(el)
						.fadeTo("fast", 1)
						.find('.noptin-save-error')
						.show()
						.html(`<p>${error}</p>`)
				})

		},

	},

	mounted () {
		//Runs after mounting
	},
})

export default rulesApp
