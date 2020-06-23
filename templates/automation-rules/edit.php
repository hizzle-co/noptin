<?php

/**
 * Edit an automation
 */

$rule = new Noptin_Automation_Rule( $rule_id );

if ( ! $rule->exists() ) {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		__( 'Rule not found. It might have been deleted.', 'newsletter-optin-box' )
	);
	return;
}

$trigger  = noptin()->automation_rules->get_trigger( $rule->trigger_id );
if ( empty( $trigger ) ) {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		__( 'Your website does not support that trigger.', 'newsletter-optin-box' )
	);
	return;
}

$action  = noptin()->automation_rules->get_action( $rule->action_id );
if ( empty( $action ) ) {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		__( 'Your website does not support that action.', 'newsletter-optin-box' )
	);
	return;
}

$trigger_settings = $trigger->get_settings();
$action_settings  = $action->get_settings();

if ( empty( $trigger_settings ) && empty( $action_settings ) ) {
	printf(
		'<div class="notice notice-info"><p>%s</p></div>',
		__( 'Nothing to configure for this rule.', 'newsletter-optin-box' )
	);
	return;
}

?>

<div id="noptin-automation-rule-editor" class="edit-automation-rule noptin-fields">
	<form method="POST">

		<?php if ( ! empty( $trigger_settings ) ) { ?>
			<div class="noptin-automation-rule-editor-section">
				<hr>
				<h2 style="margin-bottom: 0.2em;"><?php _e( 'Trigger Settings', 'newsletter-optin-box' ); ?></h2>
				<p class="description" style="margin-bottom: 16px;"><?php echo $trigger->get_description(); ?></p>
				<?php foreach ( $trigger_settings as $id => $args ) { ?>
					<?php Noptin_Vue::render_el( "trigger_settings['$id']", $args ); ?>
				<?php } ?>
			</div>
		<?php } ?>

		<?php if ( ! empty( $action_settings ) ) { ?>
			<div class="noptin-automation-rule-editor-section">
				<hr>
				<h2 style="margin-bottom: 0.2em;"><?php _e( 'Action Settings', 'newsletter-optin-box' ); ?></h2>
				<p class="description" style="margin-bottom: 16px;"><?php echo $action->get_description(); ?></p>
				<?php foreach ( $action_settings as $id => $args ) { ?>
					<?php Noptin_Vue::render_el( "action_settings['$id']", $args ); ?>
				<?php } ?>
			</div>
		<?php } ?>

		<div>
			<input @click.prevent="saveRule" type="submit" class="button button-primary noptin-automation-rule-edit" value="<?php _e( 'Save Changes', 'newsletter-optin-box' ); ?>">
			<span class="spinner save-automation-rule"></span>
		</div>

		<div class="noptin-save-saved" style="display:none"></div>
		<div class="noptin-save-error" style="display:none"></div>
	</form>
</div>
