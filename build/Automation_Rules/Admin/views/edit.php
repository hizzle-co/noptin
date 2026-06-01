<?php

/**
 * Edit an automation
 */

defined( 'ABSPATH' ) || exit;

$rule = noptin_get_current_automation_rule();

if ( is_wp_error( $rule ) ) {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		'Rule not found. It might have been deleted.'
	);
	return;
}

?>
<div class="noptin-edit-automation-rule-page" id="noptin-wrapper">
	<div id="noptin-automation-rule__editor-app">
		<span class="spinner" style="visibility: visible; float: none;"></span>
	</div>
</div>
