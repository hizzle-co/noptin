<?php

defined( 'ABSPATH' ) || exit;

/**
 * @var Noptin_Automated_Email $campaign
 */

$rule = noptin_get_current_automation_rule();

noptin_hidden_field( 'noptin_email[automation_rule]', $campaign->get( 'automation_rule' ) );

if ( ! $rule->exists() && ! $rule->is_creating ) {
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

// Normal settings.
$trigger_settings = apply_filters( 'noptin_automation_rule_trigger_settings_' . $trigger->get_id(), $trigger->get_settings(), $rule, $trigger );

// Conditional logic.
$trigger_settings['conditional_logic'] = array(
	'label'       => __( 'Conditional Logic', 'newsletter-optin-box' ),
	'el'          => 'conditional_logic',
	'comparisons' => noptin_get_conditional_logic_comparisons(),
	'toggle_text' => __( 'Optional. Send this email only if certain conditions are met.', 'newsletter-optin-box' ),
	'default'     => array(
		'enabled' => false,
		'action'  => 'allow',
		'type'    => 'all',
		'rules'   => array(
			array(
				'condition' => 'is',
				'type'      => 'date',
				'value'     => gmdate( 'Y-m-d' ),
			),
		),
	),
);

// Heading.
$trigger_settings = array_merge(
	array(
		'heading' => array(
			'content' => sprintf(
				/* translators: %s: Trigger description. */
				__( 'Noptin will send this email %s', 'newsletter-optin-box' ),
				$trigger->get_description()
			),
			'el'      => 'paragraph',
		),
	),
	$trigger_settings
);

?>

<div id="noptin-emails-conditional-logic-editor" style="margin-top: 1.5em;">

	<div
		id="noptin-emails-conditional-logic__editor-app"
		class="noptin-es6-app"
		data-id="<?php echo esc_attr( $rule->id ); ?>"
		data-action="<?php echo esc_attr( $rule->action_id ); ?>"
		data-trigger="<?php echo esc_attr( $rule->trigger_id ); ?>"
		data-saved="<?php echo esc_attr( wp_json_encode( (object) $rule->trigger_settings ) ); ?>"
		data-settings="<?php echo esc_attr( wp_json_encode( $trigger_settings ) ); ?>"
		data-smart-tags="<?php echo esc_attr( wp_json_encode( $trigger->get_known_smart_tags_for_js() ) ); ?>"
	>
		<?php esc_html_e( 'Loading...', 'newsletter-optin-box' ); ?>
		<span class="spinner"></span>
	</div>
</div>
