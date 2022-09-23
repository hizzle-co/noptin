import { createApp } from 'vue';

export default createApp({

	data() {
		return jQuery.extend( true, {}, noptinRules )
	},

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
				'conditional_logic' : this.conditional_logic,
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

		// Checks if there are rule options.
		hasConditionalLogicRuleOptions( rule_type ) {
			return this.condition_rules[ rule_type ] !== undefined && this.condition_rules[ rule_type ].options !== undefined;
		},

		// Retrieves the rule options.
		getConditionalLogicRuleOptions( rule_type ) {
			return this.condition_rules[ rule_type ].options;
		},

		// Adds a conditional logic rule.
		addConditionalLogicRule() {

			// Fetch first value in this.condition_rules object.
			let type = Object.keys(this.condition_rules)[0]
			let value = this.condition_rules[type].options ? Object.keys(this.condition_rules[type].options)[0] : ''

			this.conditional_logic.rules.push({
				type,
				condition: 'is',
				value
			});
		},

		// Removes an existing rule.
		removeConditionalLogicRule( rule ) {
			this.conditional_logic.rules.splice( this.conditional_logic.rules.indexOf( rule ), 1 );
		},

		// Checks if a rule is the last one.
		isLastConditionalLogicRule( index ) {
			return index === this.conditional_logic.rules.length - 1;
		}
	},

})
