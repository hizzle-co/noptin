export default {

	data() {
		return JSON.parse( JSON.stringify( noptinRules ) );
	},

	computed: {

		// Returns an array of available smart tags.
		availableSmartTags() {
			let tags = [];

			if ( ! this.smart_tags ) {
				return tags;
			}

			Object.keys( this.smart_tags ).forEach( key => {

				// Check if conditions have been met.
				if ( this.smart_tags[ key ].conditions ) {

					// Check if all conditions have been met.
					const condition_matched = this.smart_tags[ key ].conditions.every( condition => {

						if ( Array.isArray( condition.value ) ) {
							var matched = condition.value.some( val => val === this[ condition.key ] );
						} else {
							var matched = condition.value === this[ condition.key ];
						}

						const should_match = condition.operator === 'is';

						return matched === should_match;
					});

					if ( ! condition_matched ) {
						return;
					}
				}

				let label = key;

				if ( this.smart_tags[ key ].label ) {
					label = this.smart_tags[ key ].label;
				} else if ( this.smart_tags[ key ].description ) {
					label = this.smart_tags[ key ].description;
				}

				tags.push( {
					smart_tag: key,
					label,
					example: this.smart_tags[ key ].example ? this.smart_tags[ key ].example : '',
					description: this.smart_tags[ key ].description ? this.smart_tags[ key ].description : '',
				})
			});

			return tags;
		}
	},

	methods: {

		saveRule () {

			var $ = jQuery

			// Provide visual feedback by fading the form.
			$('#noptin-automation-rule-editor').fadeTo('fast', 0.33);
			$('#noptin-automation-rule-editor')
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

			// Hide form notices.
			$('#noptin-automation-rule-editor').find('.noptin-save-saved').hide()
			$('#noptin-automation-rule-editor').find('.noptin-save-error').hide()

			// Post the state data to the server.
			jQuery.post(noptinRules.ajaxurl, data)

				// Show a success msg after we are done.
				.done( () => {
					$('#noptin-automation-rule-editor')
						.find('.noptin-save-saved')
						.show()
						.html(`<p>${saved}</p>`)
				})

				// Else alert the user about the error.
				.fail( () => {
					$('#noptin-automation-rule-editor')
						.find('.noptin-save-error')
						.show()
						.html(`<p>${error}</p>`)
				})

				.always( () => {

					$('#noptin-automation-rule-editor')
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
		},

		// Fetch a smart tags example.
		fetchSmartTagExample( config ) {
			const example = config.example ? config.example : `${config.smart_tag} default="default value"`;
			return `[[${example}]]`;
		}
	},

}
