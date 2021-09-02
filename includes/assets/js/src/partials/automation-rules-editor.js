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
			$(this.$el).fadeTo('fast', 0.33);
			$(this.$el)
				.find('.save-automation-rule')
				.css(
					{ 'visibility': 'visible' }
				);

			// Prepare rule data.
			let data  = {
				'id' : this.rule_id,
				'action_settings' : this.action_settings,
				'trigger_settings' : this.trigger_settings,
				'action' : 'noptin_save_automation_rule',
				'_ajax_nonce' : noptinRules.nonce
			}

			if ( jQuery( '#wp-noptinemailbody-wrap').length ) {
				if ( tinyMCE.get('noptinemailbody') ) {
					this.action_settings.email_content = tinyMCE.get('noptinemailbody').getContent()
				} else {
					this.action_settings.email_content = $('#noptinemailbody').val()
				}
			}

			let error = this.error
			let saved = this.saved
			let el    = this.$el

			// Hide form notices.
			$(this.$el).find('.noptin-save-saved').hide()
			$(this.$el).find('.noptin-save-error').hide()

			// Post the state data to the server.
			jQuery.post(noptinRules.ajaxurl, data)

				// Show a success msg after we are done.
				.done( () => {
					$(el)
						.find('.noptin-save-saved')
						.show()
						.html(`<p>${saved}</p>`)
				})

				// Else alert the user about the error.
				.fail( () => {
					$(el)
						.find('.noptin-save-error')
						.show()
						.html(`<p>${error}</p>`)
				})

				.always( () => {

					$(el)
						.fadeTo('fast', 1)
						.find('.save-automation-rule')
						.css(
							{ 'visibility': 'hidden' }
						);

				})

		},

	},

	mounted () {

	},
})

export default rulesApp
