<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Adds a noptin subscriber.
 *
 * @since 1.9.0
 */
class Noptin_Subscribe_Action extends Noptin_Abstract_Action {

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'subscribe';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Subscriber > Create / Update', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Create / Update Noptin Subscriber', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_table_description( $rule ) {
		$settings = $rule->get_action_settings();

		// Abort if we have no email address.
		if ( empty( $settings['email'] ) ) {
			return sprintf(
				'<span class="noptin-rule-error">%s</span>',
				esc_html__( 'Error: Email address not specified', 'newsletter-optin-box' )
			);
		}

		$meta = array();

		foreach ( get_noptin_custom_fields() as $field ) {
			$meta[ esc_html( $field['label'] ) ] = isset( $settings[ $field['merge_tag'] ] ) ? esc_html( $settings[ $field['merge_tag'] ] ) : '';
		}

		return $this->rule_action_meta( $meta, $rule );
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {

		$settings = array();

		foreach ( get_editable_noptin_subscriber_fields() as $key => $field ) {

			$label = empty( $field['label'] ) ? $field['description'] : $field['label'];

			$settings[ $key ] = array(
				'type'        => 'text',
				'el'          => 'input',
				'label'       => $label,
				'map_field'   => true,
				'placeholder' => sprintf(
					/* translators: %s: The field name. */
					__( 'Enter %s', 'newsletter-optin-box' ),
					strtolower( $label )
				),
			);

			if ( $label !== $field['description'] ) {
				$settings[ $key ]['description'] = $field['description'];
			}
		}

		return $settings;
	}

	/**
	 * @inheritdoc
	 */
	public function can_run( $subject, $rule, $args ) {

		// Check if we have an email address.
		return is_email( $this->get_subject_email( $subject, $rule, $args ) );
	}

	/**
	 * @inheritdoc
	 */
	public function run( $subject, $rule, $args ) {

		$details  = array(
			'email' => $this->get_subject_email( $subject, $rule, $args ),
		);

		/** @var Noptin_Automation_Rules_Smart_Tags $smart_tags */
		$smart_tags = $args['smart_tags'];

		foreach ( array_keys( get_editable_noptin_subscriber_fields() ) as $field ) {

			$value = $rule->get_action_setting( $field );

			if ( is_null( $value ) || '' === $value ) {
				continue;
			}

			$details[ $field ] = map_deep( $value, array( $smart_tags, 'replace_in_text_field' ) );
		}

		$subscriber_id = get_noptin_subscriber_id_by_email( $this->get_subject_email( $subject, $rule, $args ) );
		if ( empty( $subscriber_id ) ) {
			$subscriber_id = get_noptin_subscriber_id_by_email( $details['email'] );
		}

		if ( $subscriber_id ) {
			update_noptin_subscriber( $subscriber_id, $details );
		} else {
			add_noptin_subscriber( $details );
		}
	}

}
