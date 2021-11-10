<?php

/**
 * Create an automation
 */

$triggers = noptin()->automation_rules->get_triggers();
$actions  = noptin()->automation_rules->get_actions();
?>

<div id="noptin-automation-rule-editor">
	<p><?php _e( 'Start by selecting a trigger and an action for your rule below.', 'newsletter-optin-box' ); ?></p>
	<form method="POST">

		<div class="noptin-automation-rule-editor-section">

			<select name="trigger" class="noptin-automation-rule-trigger">
				<?php
		
					$value       = '';
					$label       = esc_attr__( 'Trigger', 'newsletter-optin-box' );
					$description = esc_attr__( 'Select a trigger for this rule', 'newsletter-optin-box' );
					echo "<option value='$value' data-description='$description'>$label</option>";

					foreach( $triggers as $trigger ) {
						$value       = esc_attr( $trigger->get_id() );
						$label       = esc_html( $trigger->get_name() );
						$description = esc_attr( $trigger->get_description() );
						echo "<option value='$value' data-description='$description'>$label</option>";
					}
				?>
			</select>

			<input type="hidden" value="" name="trigger" class="noptin-automation-rule-trigger-hidden">
		</div>

		<div class="noptin-automation-rule-editor-section">

			<select name="action" class="noptin-automation-rule-action">

				<?php

					$value       = '';
					$label       = esc_attr__( 'Action', 'newsletter-optin-box' );
					$description = esc_attr__( 'Select an action to take when the above trigger is fired', 'newsletter-optin-box' );
					echo "<option value='$value' data-description='$description'>$label</option>";

					foreach( $actions as $action ) {
						$value       = esc_attr( $action->get_id() );
						$label       = esc_html( $action->get_name() );
						$description = esc_attr( $action->get_description() );
						$image       = esc_url( $action->get_image() );
						echo "<option value='$value' data-description='$description'>$label</option>";
					}

				?>

			</select>
			<input type="hidden" value="" name="action" class="noptin-automation-rule-action-hidden">
		</div>

		<input type="submit" class="button button-primary noptin-automation-rule-create" value="<?php _e( 'Continue', 'newsletter-optin-box' ); ?>">
		<?php wp_nonce_field( 'noptin-admin-create-automation-rule', 'noptin-admin-create-automation-rule' ); ?>
		<input type="hidden" name="noptin_admin_action" value="noptin_create_automation_rule">
	</form>
</div>
