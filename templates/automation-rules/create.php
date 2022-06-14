<?php

/**
 * Create an automation
 */

defined( 'ABSPATH' ) || exit;

$triggers = noptin()->automation_rules->get_triggers();
$actions  = noptin()->automation_rules->get_actions();
?>

<div id="noptin-automation-rule-editor">
	<p><?php esc_html_e( 'Start by selecting a trigger and an action for your rule below.', 'newsletter-optin-box' ); ?></p>
	<form method="POST">

		<div class="noptin-automation-rule-editor-section">

			<select name="trigger" class="noptin-automation-rule-trigger">
				<?php

					printf(
						'<option value="" data-description="%s">%s</option>',
						esc_attr__( 'Select a trigger for this rule', 'newsletter-optin-box' ),
						esc_attr__( 'Trigger', 'newsletter-optin-box' )
					);

					foreach ( $triggers as $trigger ) {
						printf(
							'<option value="%s" data-description="%s">%s</option>',
							esc_attr( $trigger->get_id() ),
							esc_attr( $trigger->get_description() ),
							esc_html( $trigger->get_name() )
						);
					}
				?>
			</select>

			<input type="hidden" value="" name="trigger" class="noptin-automation-rule-trigger-hidden">
		</div>

		<div class="noptin-automation-rule-editor-section">

			<select name="action" class="noptin-automation-rule-action">

				<?php

					printf(
						'<option value="" data-description="%s">%s</option>',
						esc_attr__( 'Select an action to take when the above trigger is fired', 'newsletter-optin-box' ),
						esc_attr__( 'Action', 'newsletter-optin-box' )
					);

					foreach ( $actions as $rule_action ) {
						printf(
							'<option value="%s" data-description="%s">%s</option>',
							esc_attr( $rule_action->get_id() ),
							esc_attr( $rule_action->get_description() ),
							esc_html( $rule_action->get_name() )
						);
					}
				?>

			</select>
			<input type="hidden" value="" name="action" class="noptin-automation-rule-action-hidden">
		</div>

		<input type="submit" class="button button-primary noptin-automation-rule-create" value="<?php esc_html_e( 'Continue', 'newsletter-optin-box' ); ?>">
		<?php wp_nonce_field( 'noptin-admin-create-automation-rule', 'noptin-admin-create-automation-rule' ); ?>
		<input type="hidden" name="noptin_admin_action" value="noptin_create_automation_rule">
	</form>
</div>
