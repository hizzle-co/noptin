<?php

/**
 * Edit an automation
 */

defined( 'ABSPATH' ) || exit;

$rule = noptin_get_current_automation_rule();

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
$settings         = array();

// Trigger settings.
$settings['trigger'] = array(
	'label'    => __( 'Trigger', 'newsletter-optin-box' ),
	'prop'     => 'trigger_settings',
	'settings' => array_merge(
		array(
			'action_id_info' => array(
				'el'      => 'paragraph',
				'content' => $trigger->get_description(),
			),
		),
		$trigger_settings
	),
);

// Conditional logic.
$settings['conditional_logic'] = array(
	'label'    => __( 'Conditional Logic', 'newsletter-optin-box' ),
	'prop'     => 'trigger_settings',
	'settings' => array(
		'conditional_logic' => array(
			'label'       => __( 'Conditional Logic', 'newsletter-optin-box' ),
			'el'          => 'conditional_logic',
			'comparisons' => noptin_get_conditional_logic_comparisons(),
			'fullWidth'   => true,
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
		),
	),
);

// Map fields.
$map_fields = array();

foreach ( $action_settings as $key => $data ) {

	if ( ! empty( $data['map_field'] ) ) {
		$map_fields[ $key ] = $data;
		unset( $action_settings[ $key ] );
	}
}

// Action settings.
$settings['action'] = array(
	'label'    => __( 'Action', 'newsletter-optin-box' ),
	'prop'     => 'action_settings',
	'settings' => array_merge(
		array(
			'action_id_info' => array(
				'el'      => 'paragraph',
				'content' => $rule_action->get_description(),
			),
		),
		$action_settings
	),
);


if ( ! empty( $map_fields ) ) {
	$settings['map_fields'] = array(
		'label'    => __( 'Map custom fields', 'newsletter-optin-box' ),
		'prop'     => 'action_settings',
		'settings' => array_merge(
			array(
				'map_field_tip' => array(
					'content' => __( 'Click on the merge tag button to insert a dynamic value.', 'newsletter-optin-box' ),
					'el'      => 'paragraph',
				),
			),
			$map_fields
		),
	);
}

$settings = apply_filters( 'noptin_automation_rule_settings', $settings, $rule, $trigger, $rule_action );
?>
<div class="wrap noptin-edit-automation-rule-page" id="noptin-wrapper">
	<div
		id="noptin-automation-rule__editor-app"
		class="noptin-es6-app"
		data-id="<?php echo esc_attr( $rule->id ); ?>"
		data-action="<?php echo esc_attr( $rule->action_id ); ?>"
		data-trigger="<?php echo esc_attr( $rule->trigger_id ); ?>"
		data-create-new-url="<?php echo esc_url( add_query_arg( 'noptin_create_automation_rule', '1', admin_url( 'admin.php?page=noptin-automation-rules' ) ) ); ?>"
		data-settings="<?php echo esc_attr( wp_json_encode( $settings ) ); ?>"
		data-smart-tags="<?php echo esc_attr( wp_json_encode( $trigger->get_known_smart_tags_for_js() ) ); ?>"
	>
		<?php esc_html_e( 'Loading...', 'newsletter-optin-box' ); ?>
		<span class="spinner"></span>
	</div>

	<p class="description"><a href="<?php echo esc_attr( noptin_get_upsell_url( '/guide/automation-rules/', 'learn-more', 'automation-rules' ) ); ?>" target="_blank"><?php esc_html_e( 'Learn more about automation rules', 'newsletter-optin-box' ); ?></a></p>

</div>
