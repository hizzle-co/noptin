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
	 * Retrieve the actions's rule table description.
	 *
	 * @since 1.11.9
	 * @param Noptin_Automation_Rule $rule
	 * @return array
	 */
	public function get_rule_table_description( $rule ) {
		$settings = $rule->action_settings;

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

		foreach ( get_noptin_custom_fields() as $field ) {

			$settings[ $field['merge_tag'] ] = array(
				'type'        => 'text',
				'el'          => 'input',
				'label'       => $field['label'],
				'map_field'   => true,
				'placeholder' => sprintf(
					/* translators: %s: The field name. */
					__( 'Enter %s', 'newsletter-optin-box' ),
					$field['label']
				),
			);
		}

		return $settings;
	}

	/**
	 * Returns whether or not the action can run (dependancies are installed).
	 *
	 * @since 1.9.0
	 * @param mixed $subject The subject.
	 * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return bool
	 */
	public function can_run( $subject, $rule, $args ) {

		// Check if we have an email address.
		return is_email( $this->get_subject_email( $subject, $rule, $args ) );
	}

	/**
	 * Add / update the subscriber.
	 *
	 * @since 1.3.1
	 * @param mixed $subject The subject.
	 * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return void
	 */
	public function run( $subject, $rule, $args ) {

		$settings = wp_unslash( $rule->action_settings );
		$details  = array(
			'email' => $this->get_subject_email( $subject, $rule, $args ),
		);

		/** @var Noptin_Automation_Rules_Smart_Tags $smart_tags */
		$smart_tags = $args['smart_tags'];

		foreach ( get_noptin_custom_fields() as $field ) {

			if ( 'email' === $field['merge_tag'] || ! isset( $settings[ $field['merge_tag'] ] ) || '' === $settings[ $field['merge_tag'] ] ) {
				continue;
			}

			$details[ $field['merge_tag'] ] = $smart_tags->replace_in_text_field( $settings[ $field['merge_tag'] ] );
		}

		if ( $subject instanceof Noptin_Subscriber ) {
			$subscriber_id = $subject->id;
		} else {
			$subscriber_id = get_noptin_subscriber_id_by_email( $details['email'] );
		}

		if ( $subscriber_id ) {
			update_noptin_subscriber( $subscriber_id, $details );
		} else {
			add_noptin_subscriber( $details );
		}
	}

}
