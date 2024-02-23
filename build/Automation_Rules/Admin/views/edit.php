<?php

/**
 * Edit an automation
 */

defined( 'ABSPATH' ) || exit;

$rule = noptin_get_current_automation_rule();

if ( is_wp_error( $rule ) ) {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Rule not found. It might have been deleted.', 'newsletter-optin-box' )
	);
	return;
}

if ( ! $rule->exists() && ! $rule->is_creating ) {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Rule not found. It might have been deleted.', 'newsletter-optin-box' )
	);
	return;
}

if ( ! $rule->get_trigger() ) {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Your site does not support this trigger.', 'newsletter-optin-box' )
	);
	return;
}

if ( ! $rule->get_action() ) {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Your website does not support that action.', 'newsletter-optin-box' )
	);
	return;
}

?>
<div class="wrap noptin-edit-automation-rule-page" id="noptin-wrapper">
	<div id="noptin-automation-rule__editor-app">
		<?php esc_html_e( 'Loading...', 'newsletter-optin-box' ); ?>
		<span class="spinner" style="visibility: visible; float: none;"></span>
	</div>

	<p class="description"><a href="<?php echo esc_attr( noptin_get_upsell_url( '/guide/automation-rules/', 'learn-more', 'automation-rules' ) ); ?>" target="_blank"><?php esc_html_e( 'Learn more about automation rules', 'newsletter-optin-box' ); ?></a></p>

</div>
