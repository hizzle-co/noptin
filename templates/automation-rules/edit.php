<?php

/**
 * Edit an automation
 */

defined( 'ABSPATH' ) || exit;

$rule = new Noptin_Automation_Rule( $rule_id );

if ( ! $rule->exists() ) {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Rule not found. It might have been deleted.', 'newsletter-optin-box' )
	);
	return;
}

$trigger  = noptin()->automation_rules->get_trigger( $rule->trigger_id );
if ( empty( $trigger ) ) {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Your website does not support that trigger.', 'newsletter-optin-box' )
	);
	return;
}

$rule_action  = noptin()->automation_rules->get_action( $rule->action_id );
if ( empty( $rule_action ) ) {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Your website does not support that action.', 'newsletter-optin-box' )
	);
	return;
}

$conditional_logic = 0 < count( $trigger->get_conditional_logic_filters() );

$trigger_settings = apply_filters( 'noptin_automation_rule_trigger_settings_' . $trigger->get_id(), $trigger->get_settings(), $rule, $trigger );
$action_settings  = apply_filters( 'noptin_automation_rule_action_settings_' . $rule_action->get_id(), $rule_action->get_settings(), $rule, $rule_action );

if ( empty( $trigger_settings ) && empty( $action_settings ) && empty( $conditional_logic ) ) {
	printf(
		'<div class="notice notice-info"><p>%s</p></div>',
		esc_html__( 'Nothing to configure for this rule.', 'newsletter-optin-box' )
	);
	return;
}

?>

<div id="noptin-automation-rule-editor" class="edit-automation-rule noptin-fields">
	<form method="POST">

		<?php if ( ! empty( $trigger_settings ) || ! empty( $conditional_logic ) ) : ?>
			<div class="noptin-automation-rule-editor-section">
				<hr>
				<h2 style="margin-bottom: 0.2em;"><?php esc_html_e( 'Trigger Settings', 'newsletter-optin-box' ); ?></h2>
				<p class="description" style="margin-bottom: 16px;"><?php echo wp_kses_post( $trigger->get_description() ); ?></p>
				<?php foreach ( $trigger_settings as $setting_id => $args ) : ?>
					<?php Noptin_Vue::render_el( "trigger_settings['$setting_id']", $args ); ?>
				<?php endforeach; ?>
				<!-- Conditional logic -->
				<div v-if="condition_rules" class="noptin-text-wrapper field-wrapper">
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

								<select v-model="rule.type" @change="rule.value=''">
									<option v-for="(rule_type, rule_key) in condition_rules" :value="rule_key">{{ rule_type.label }}</option>
								</select>

								<select v-model="rule.condition">
									<option value="is"><?php esc_html_e( 'is', 'newsletter-optin-box' ); ?></option>
									<option value="is_not"><?php esc_html_e( 'is not', 'newsletter-optin-box' ); ?></option>
								</select>

								<select v-model="rule.value" v-if="hasConditionalLogicRuleOptions(rule.type)" style="min-width: 220px;">
									<option v-for="(option_label, option_value) in getConditionalLogicRuleOptions(rule.type)" :value="option_value">{{ option_label }}</option>
								</select>

								<input type="text" v-model="rule.value" v-else />

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
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $action_settings ) ) : ?>
			<div class="noptin-automation-rule-editor-section">
				<hr>
				<h2 style="margin-bottom: 0.2em;"><?php esc_html_e( 'Action Settings', 'newsletter-optin-box' ); ?></h2>
				<p class="description" style="margin-bottom: 16px;"><?php echo wp_kses_post( $rule_action->get_description() ); ?></p>
				<?php foreach ( $action_settings as $setting_id => $args ) : ?>
					<?php Noptin_Vue::render_el( "action_settings['$setting_id']", $args ); ?>
					<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div>
			<input @click.prevent="saveRule" type="submit" class="button button-primary noptin-automation-rule-edit" value="<?php esc_attr_e( 'Save Changes', 'newsletter-optin-box' ); ?>">
			<span class="spinner save-automation-rule"></span>
		</div>

		<div class="noptin-save-saved" style="display:none"></div>
		<div class="noptin-save-error" style="display:none"></div>
	</form>
</div>
