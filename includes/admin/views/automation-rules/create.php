<?php defined( 'ABSPATH' ) || exit; ?>

<div class="wrap noptin-add-automation-rule-page" id="noptin-wrapper">

	<h1 class="title"><?php esc_html_e( 'Create an Automation Rule', 'newsletter-optin-box' ); ?></h1>

	<?php do_action( 'noptin_before_automation_rules_create_page' ); ?>

	<div id="noptin-automation-rule-editor">
		<p><?php esc_html_e( 'Start by selecting a trigger and an action for your rule below.', 'newsletter-optin-box' ); ?></p>
		<form method="POST">

			<div class="noptin-automation-rule-editor-section">

				<select name="noptin-automation-rule-trigger" class="noptin-automation-rule-trigger noptin-automation-rules-dropdown noptin-automation-rules-dropdown-trigger">
					<?php

						printf(
							'<option value="" data-description="%s">%s</option>',
							esc_attr__( 'Select a trigger for this rule', 'newsletter-optin-box' ),
							esc_attr__( 'Select Trigger', 'newsletter-optin-box' )
						);

						foreach ( noptin()->automation_rules->get_triggers() as $trigger ) {
							printf(
								'<option value="%s" data-description="%s">%s</option>',
								esc_attr( $trigger->get_id() ),
								esc_attr( $trigger->get_description() ),
								esc_html( $trigger->get_name() )
							);
						}
					?>
				</select>

				<p class="noptin-automation-rule-editor-section-description description"><?php esc_html_e( 'Select a trigger for this rule', 'newsletter-optin-box' ); ?></p>
			</div>

			<div class="noptin-automation-rule-editor-section">

				<select name="noptin-automation-rule-action" class="noptin-automation-rule-action noptin-automation-rules-dropdown noptin-automation-rules-dropdown-action">

					<?php

						printf(
							'<option value="" data-description="%s">%s</option>',
							esc_attr__( 'Select an action to take when the above trigger is fired', 'newsletter-optin-box' ),
							esc_attr__( 'Select Action', 'newsletter-optin-box' )
						);

						foreach ( noptin()->automation_rules->get_actions() as $rule_action ) {
							printf(
								'<option value="%s" data-description="%s">%s</option>',
								esc_attr( $rule_action->get_id() ),
								esc_attr( $rule_action->get_description() ),
								esc_html( $rule_action->get_name() )
							);
						}
					?>

				</select>

				<p class="noptin-automation-rule-editor-section-description description"><?php esc_html_e( 'Select an action to take when the above trigger is fired', 'newsletter-optin-box' ); ?></p>
			</div>

			<input type="submit" class="button button-primary noptin-automation-rule-create" value="<?php esc_html_e( 'Continue', 'newsletter-optin-box' ); ?>">
			<?php wp_nonce_field( 'noptin-admin-create-automation-rule', 'noptin-admin-create-automation-rule' ); ?>
			<input type="hidden" name="noptin_admin_action" value="noptin_create_automation_rule">
		</form>
	</div>

	<?php do_action( 'noptin_after_automation_rules_create_page' ); ?>

	<p class="description"><a href="https://noptin.com/guide/automation-rules" target="_blank"><?php esc_html_e( 'Learn more about automation rules', 'newsletter-optin-box' ); ?></a></p>

</div>
