<?php

defined( 'ABSPATH' ) || exit;

/**
 * @var Noptin_Automated_Email $campaign
 */

$rule = new Noptin_Automation_Rule( absint( $campaign->get( 'automation_rule' ) ) );

noptin_hidden_field( 'noptin_email[automation_rule]', $campaign->get( 'automation_rule' ) );

if ( ! $rule->exists() ) {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Rule not found. It might have been deleted.', 'newsletter-optin-box' )
	);
	return;
}

$trigger = noptin()->automation_rules->get_trigger( $rule->trigger_id );
if ( empty( $trigger ) ) {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Your website does not support that trigger.', 'newsletter-optin-box' )
	);
	return;
}

$trigger_settings = apply_filters( 'noptin_automation_rule_trigger_settings_' . $trigger->get_id(), $trigger->get_settings(), $rule, $trigger );

add_filter( 'noptin_email_has_listed_available_merge_tags', '__return_true' );

// Do not display the trigger subject field. Instead, use the recipient value as the trigger subject.
// Adding the timing metabox to the Ultimate Addons Pack.
?>

<div id="noptin-automation-rule-editor" class="edit-automation-rule noptin-email-editor-fields noptin-fields" style="margin-top: 1.5em;">

	<div class="noptin-automation-rule-editor-section">

		<div class="noptin-select-wrapper field-wrapper">
			<label class="noptin-select-label"><?php esc_html_e( 'Trigger', 'newsletter-optin-box' ); ?></label>
			<div class="noptin-content">
				<select disabled>
					<option selected="selected" value="<?php echo esc_attr( $trigger->get_id() ); ?>"><?php echo esc_html( $trigger->get_name() ); ?></option>
				</select>
				<p class="description"><?php echo esc_html( $trigger->get_description() ); ?></p>
			</div>
		</div>

		<?php foreach ( $trigger_settings as $setting_id => $args ) : ?>
			<?php Noptin_Vue::render_el( "trigger_settings['$setting_id']", $args ); ?>
		<?php endforeach; ?>

		<!-- Conditional logic -->
		<div v-if="hasConditions" class="noptin-text-wrapper field-wrapper">
			<label for="noptin-enable-conditional-logic" class="noptin-label"><?php esc_html_e( 'Conditional Logic', 'newsletter-optin-box' ); ?></label>
			<div class="noptin-content">

				<p class="description">
					<label>
						<input type="checkbox" id="noptin-enable-conditional-logic" v-model="conditional_logic.enabled" />
						<span style="font-weight: 400;"><?php esc_html_e( 'Optionally enable/disable this trigger depending on specific conditions.', 'newsletter-optin-box' ); ?></span>
					</label>
				</p>

				<div class="noptin-conditional-logic-wrapper card" v-show="conditional_logic.enabled">

					<p>
						<select v-model="conditional_logic.action">
							<option value="allow"><?php esc_html_e( 'Only run if', 'newsletter-optin-box' ); ?></option>
							<option value="prevent"><?php esc_html_e( 'Do not run if', 'newsletter-optin-box' ); ?></option>
						</select>

						<select v-model="conditional_logic.type">
							<option value="all"><?php esc_html_e( 'all', 'newsletter-optin-box' ); ?></option>
							<option value="any"><?php esc_html_e( 'any', 'newsletter-optin-box' ); ?></option>
						</select>

						<span>&nbsp;<?php esc_html_e( 'of the following rules are true:', 'newsletter-optin-box' ); ?>&nbsp;</span>
					</p>

					<p v-for="(rule, index) in conditional_logic.rules" class="noptin-conditional-logic-rule">

						<select class="noptin-condition-field" v-model="rule.type" @change="rule.value=getConditionPlaceholder(rule.type); rule.condition='is'">
							<option v-for="condition_type in availableConditions" :value="condition_type.key">{{ condition_type.label }}</option>
						</select>

						<select class="noptin-condition-comparison" v-model="rule.condition">
							<option
								v-for="(comparison_label, comparison_key) in getConditionalComparisonOptions( rule.type )"
								:value="comparison_key"
							>{{ comparison_label }}</option>
						</select>

						<select class="noptin-condition-value" v-model="rule.value" v-if="hasConditionOptions(rule.type)">
							<option value="" disabled><?php esc_html_e( 'Select a value', 'newsletter-optin-box' ); ?></option>
							<option v-for="(option_label, option_value) in getConditionOptions(rule.type)" :value="option_value">{{ option_label }}</option>
						</select>

						<input :type="getConditionInputType(rule.type)" class="noptin-condition-value" v-model="rule.value" :placeholder="getConditionPlaceholder(rule.type)" v-else />

						<a href="#" @click.prevent="removeConditionalLogicRule(rule)" class="noptin-remove-conditional-rule">
							<span class="dashicons dashicons-remove"></span>&nbsp;
						</a>

						<span v-if="! isLastConditionalLogicRule(index) && 'all' == conditional_logic.type">&nbsp;<?php esc_html_e( 'and', 'newsletter-optin-box' ); ?></span>
						<span v-if="! isLastConditionalLogicRule(index) && 'any' == conditional_logic.type">&nbsp;<?php esc_html_e( 'or', 'newsletter-optin-box' ); ?></span>

					</p>

					<p>
						<button class="button" @click.prevent="addConditionalLogicRule">
							<span class="dashicons dashicons-plus" style="vertical-align: middle;"></span>
							<span v-if="conditional_logic.rules && conditional_logic.rules.length"><?php esc_html_e( 'Add another rule', 'newsletter-optin-box' ); ?></span>
							<span v-else><?php esc_html_e( 'Add rule', 'newsletter-optin-box' ); ?></span>
						</button>
					</p>

				</div>
			</div>
		</div>

		<textarea name="noptin_trigger_settings" :value="JSON.stringify(trigger_settings)" style="display: none;"></textarea>
		<textarea name="noptin_conditional_logic" :value="JSON.stringify(conditional_logic)" style="display: none;"></textarea>

		<div id="noptin-available-smart-tags" style="display: none;">
			<?php require noptin()->plugin_path . 'includes/admin/views/automation-rules/dynamic-content-tags.php'; ?>
		</div>
	</div>
</div>
