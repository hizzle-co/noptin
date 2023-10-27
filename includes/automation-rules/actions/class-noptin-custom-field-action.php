<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Updates a subscriber's custom field.
 *
 * @since 1.2.8
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
		return __( 'Subscriber > Update Custom Field', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Add/Update subscriber field', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_table_description( $rule ) {
		$settings = $rule->get_action_settings();

		// Ensure we have a field name.
		if ( empty( $settings['field_name'] ) ) {
			return sprintf(
				'<span class="noptin-rule-error">%s</span>',
				esc_html__( 'Error: No field name specified', 'newsletter-optin-box' )
			);
		}

		$field = get_noptin_custom_field( $settings['field_name'] );
		$label = $field ? $field['label'] : $settings['field_name'];
		$label = $label ? $label : $settings['field_name'];
		$meta  = array(
			esc_html__( 'Field', 'newsletter-optin-box' ) => esc_html( $label ),
			esc_html__( 'Value', 'newsletter-optin-box' ) => isset( $settings['field_value'] ) ? esc_html( $settings['field_value'] ) : '',
		);

		return $this->rule_action_meta( $meta, $rule );
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
				'options'     => wp_list_pluck( get_editable_noptin_subscriber_fields(), 'label' ),
			),

			'field_value' => array(
				'el'          => 'input',
				'label'       => __( 'Field Value', 'newsletter-optin-box' ),
				'description' => __( 'Enter a value to assign the field', 'newsletter-optin-box' ),
			),

		);
	}

	/**
	 * @inheritdoc
	 */
	public function run( $subject, $rule, $args ) {

		$field  = $rule->get_action_setting( 'field_name' );
		$fields = get_editable_noptin_subscriber_fields();

		if ( empty( $field ) || ! isset( $fields[ $field ] ) ) {
			return;
		}

		// Fetch the subscriber email.
		$subscriber_email = $this->get_subject_email( $subject, $rule, $args );
		if ( empty( $subscriber_email ) || ! is_email( $subscriber_email ) ) {
			return;
		}

		// Fetch the subscriber.
		$subscriber = noptin_get_subscriber( $subscriber_email );

		// Nothing to do here.
		if ( ! $subscriber->exists() ) {
			return;
		}

		$value = map_deep( $rule->get_action_setting( 'field_value' ), array( $args['smart_tags'], 'replace_in_text_field' ) );

		$subscriber->set( $field, $value );
		$subscriber->save();

	}

	/**
	 * @inheritdoc
	 */
	public function can_run( $subject, $rule, $args ) {

		// Abort if we do not have field name.
		if ( ! $rule->get_action_setting( 'field_name' ) ) {
			return false;
		}

		$email = $this->get_subject_email( $subject, $rule, $args );

		if ( empty( $email ) ) {
			return false;
		}

		$subscriber = noptin_get_subscriber( $email );

		return $subscriber->exists();
	}

}
