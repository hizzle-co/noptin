<?php

/**
 * Create an automation rule.
 */

defined( 'ABSPATH' ) || exit;

// Edit URLs.
$edit_urls = array(
	'default' => add_query_arg(
		array(
			'page'                        => 'noptin-automation-rules',
			'noptin_edit_automation_rule' => 0,
		),
		admin_url( 'admin.php' )
	),
	'email'   => admin_url( 'admin.php?page=noptin-email-campaigns&section=automations&sub_section=edit_campaign&campaign=automation_rule_NOPTIN_TRIGGER_ID' ),
);

$edit_urls = apply_filters( 'noptin_create_automation_rule_edit_urls', $edit_urls );
?>

<div class="wrap noptin-add-automation-rule-page" id="noptin-wrapper">

	<h1 class="title"><?php esc_html_e( 'Create an Automation Rule', 'newsletter-optin-box' ); ?></h1>

	<?php do_action( 'noptin_before_automation_rules_create_page' ); ?>

	<div id="noptin-automation-rule-editor">
		<p><?php esc_html_e( 'Start by selecting a trigger and an action for your rule below.', 'newsletter-optin-box' ); ?></p>

			<div class="noptin-automation-rule-editor-section">

				<select name="noptin-automation-rule-trigger" class="noptin-automation-rule-trigger noptin-automation-rules-dropdown noptin-automation-rules-dropdown-trigger">
					<?php

						printf(
							'<option value="" data-description="%s">%s</option>',
							esc_attr__( 'Select a trigger for this rule', 'newsletter-optin-box' ),
							esc_attr__( 'Select Trigger', 'newsletter-optin-box' )
						);

						// Fetch triggers.
						$triggers = noptin()->automation_rules->get_triggers();

						// Sort.
						uasort( $triggers, 'noptin_sort_by_name' );

						foreach ( $triggers as $trigger ) {

							if ( ! empty( $trigger->depricated ) ) {
								continue;
							}

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

						// Fetch actions.
						$actions = noptin()->automation_rules->get_actions();

						// Sort.
						uasort( $actions, 'noptin_sort_by_name' );

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

				<p class="noptin-automation-rule-editor-section-description description"><?php esc_html_e( 'Select an action to take when the above trigger is fired', 'newsletter-optin-box' ); ?></p>
			</div>

			<a
				href="#"
				class="button button-primary noptin-automation-rule-create"
				<?php
					foreach ( $edit_urls as $key => $url ) {
						printf( 'data-%s-url="%s" ', esc_attr( $key ), esc_url( $url ) );
					}
				?>
			><?php esc_html_e( 'Continue', 'newsletter-optin-box' ); ?></a>
		</form>
	</div>

	<?php do_action( 'noptin_after_automation_rules_create_page' ); ?>

	<p class="description"><a href="https://noptin.com/guide/automation-rules" target="_blank"><?php esc_html_e( 'Learn more about automation rules', 'newsletter-optin-box' ); ?></a></p>

</div>
