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
		},

		// Returns an array of available conditions.
		availableConditions() {
			let conditions = {};

			this.availableSmartTags.forEach( ( tag ) => {

				if ( this.smart_tags[ tag.smart_tag ].conditional_logic ) {
					conditions[ tag.smart_tag ] = {
						key: tag.smart_tag,
						label: tag.label,
						options: this.smart_tags[ tag.smart_tag ].options ? this.smart_tags[ tag.smart_tag ].options : false,
						type: this.smart_tags[ tag.smart_tag ].conditional_logic,
					}
				}
			})

			return conditions;
		},

		// Checks if there are any conditions.
		hasConditions() {
			return Object.keys(this.availableConditions).length > 0;
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

		// Fetches rule data type.
		getConditionDataType( condition ) {
			let config = this.availableConditions[ condition ]

			if ( config ) {
				return config.type;
			}

			return 'string';
		},

		// Checks if there are rule options.
		hasConditionOptions( rule_type ) {
			return this.availableConditions[ rule_type ] !== undefined && this.availableConditions[ rule_type ].options !== false;
		},

		// Retrieves the rule options.
		getConditionOptions( rule_type ) {
			return this.availableConditions[ rule_type ].options;
		},

		// Adds a conditional logic rule.
		addConditionalLogicRule() {

			// Fetch first value in this.availableConditions object.
			let type = Object.keys(this.availableConditions)[0]
			let value = this.availableConditions[type].options ? Object.keys(this.availableConditions[type].options)[0] : ''

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
		},

		// Fetch conditional comparison options.
		getConditionalComparisonOptions( type ) {
			let data_type = this.getConditionDataType( type );
			let comparisons = {};

			Object.keys( this.comparisons ).forEach( key => {
				let comparison_type = this.comparisons[ key ].type;

				if ( 'any' == comparison_type || comparison_type == data_type ) {
					comparisons[ key ] = this.comparisons[ key ].name;
				}
			});

			return comparisons;
		},
	},

}
