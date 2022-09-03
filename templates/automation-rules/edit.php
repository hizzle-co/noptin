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

$trigger_settings = apply_filters( 'noptin_automation_rule_trigger_settings_' . $trigger->get_id(), $trigger->get_settings(), $rule, $trigger );
$action_settings  = apply_filters( 'noptin_automation_rule_action_settings_' . $rule_action->get_id(), $rule_action->get_settings(), $rule, $rule_action );

if ( empty( $trigger_settings ) && empty( $action_settings ) ) {
	printf(
		'<div class="notice notice-info"><p>%s</p></div>',
		esc_html__( 'Nothing to configure for this rule.', 'newsletter-optin-box' )
	);
	return;
}

?>

<div id="noptin-automation-rule-editor" class="edit-automation-rule noptin-fields">
	<form method="POST">

		<?php if ( ! empty( $trigger_settings ) ) : ?>
			<div class="noptin-automation-rule-editor-section">
				<hr>
				<h2 style="margin-bottom: 0.2em;"><?php esc_html_e( 'Trigger Settings', 'newsletter-optin-box' ); ?></h2>
				<p class="description" style="margin-bottom: 16px;"><?php echo wp_kses_post( $trigger->get_description() ); ?></p>
				<?php foreach ( $trigger_settings as $setting_id => $args ) : ?>
					<?php Noptin_Vue::render_el( "trigger_settings['$setting_id']", $args ); ?>
				<?php endforeach; ?>
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
