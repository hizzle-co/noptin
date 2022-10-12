<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Updates a subscriber's custom field.
 *
 * @since       1.2.8
 */
class Noptin_Custom_Field_Action extends Noptin_Abstract_Action {

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'custom-field';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Update Subscriber Field', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( "Add/Update the subscriber's custom field", 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_description( $rule ) {

		$settings = $rule->action_settings;

		if ( empty( $settings['field_name'] ) ) {
			return __( 'update a custom field', 'newsletter-optin-box' );
		}

		$field_name  = esc_html( $settings['field_name'] );
		if ( empty( $settings['field_value'] ) ) {
			return sprintf(
				// translators: %s is the field name
				__( "delete the subscriber's field %s", 'newsletter-optin-box' ),
				"<code>$field_name</code>"
			);
		}

		$field_value = esc_html( $settings['field_value'] );

		return sprintf(
			// translators: %1 is the field name, %2 is the field value
			__( "update the subscriber's field %1\$s to %2\$s", 'newsletter-optin-box' ),
			"<code>$field_name</code>",
			"<code>$field_value</code>"
		);

	}

	/**
	 * @inheritdoc
	 */
	public function get_image() {
		return '';
	}

	/**
	 * @inheritdoc
	 */
	public function get_keywords() {
		return array(
			'noptin',
			'custom',
			'field',
			'custom field',
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {
		return array(

			'field_name'  => array(
				'el'          => 'select',
				'label'       => __( 'Custom Field', 'newsletter-optin-box' ),
				'description' => __( 'Select the custom field to update', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select Field', 'newsletter-optin-box' ),
				'options'     => wp_list_pluck( get_noptin_custom_fields(), 'label', 'merge_tag' ),
			),

			'field_value' => array(
				'el'          => 'input',
				'label'       => __( 'Field Value', 'newsletter-optin-box' ),
				'description' => __( 'Enter a value to assign the field', 'newsletter-optin-box' ),
				'placeholder' => __( 'Sample Value', 'newsletter-optin-box' ),
				'description' => sprintf(
					'<p class="description" v-show="availableSmartTags">%s</p>',
					sprintf(
						/* translators: %1: Opening link, %2 closing link tag. */
						esc_html__( 'You can use %1$s smart tags %2$s to assign dynamic values.', 'newsletter-optin-box' ),
						'<a href="#TB_inline?width=0&height=550&inlineId=noptin-automation-rule-smart-tags" class="thickbox">',
						'</a>'
					)
				),
			),

		);
	}

	/**
	 * Update a subscriber's custom field.
	 *
	 * @since 1.2.8
	 * @param mixed $subject The subject.
	 * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return void
	 */
	public function run( $subject, $rule, $args ) {

		$settings = $rule->action_settings;

		// Fetch the subscriber.
		if ( $subject instanceof Noptin_Subscriber ) {
			$subscriber = $subject;
		} else {
			$subscriber = get_noptin_subscriber( $args['email'] );
		}

		// Nothing to do here.
		if ( empty( $subscriber->id ) ) {
			return;
		}

		// Fetch the custom field.
		$custom_field = current( wp_list_filter( get_noptin_custom_fields(), array( 'merge_tag' => sanitize_text_field( $settings['field_name'] ) ) ) );
		if ( empty( $custom_field ) ) {
			return;
		}

		$field_value = $args['smart_tags']->replace_in_text_field( $settings['field_value'] );
		$field_value = sanitize_noptin_custom_field_value( $field_value, $custom_field['type'], $subscriber );

		update_noptin_subscriber_meta( $subscriber->id, $custom_field['merge_tag'], $field_value );

	}

	/**
	 * Returns whether or not the action can run (dependancies are installed).
	 *
	 * @since 1.3.3
	 * @param mixed $subject The subject.
	 * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return bool
	 */
	public function can_run( $subject, $rule, $args ) {

		// Abort if we do not have field name.
		if ( empty( $rule->action_settings['field_name'] ) ) {
			return false;
		}

		// Check if we have a valid subscriber.
		if ( $subject instanceof Noptin_Subscriber ) {
			return true;
		}

		if ( empty( $args['email'] ) ) {
			return false;
		}

		$subscriber = get_noptin_subscriber( $args['email'] );

		return $subscriber->exists();
	}

}
