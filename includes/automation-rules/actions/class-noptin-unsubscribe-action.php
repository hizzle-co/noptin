<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * De-activates a subscriber's custom field.
 *
 * @since       1.3.1
 */
class Noptin_Unsubscribe_Action extends Noptin_Abstract_Action {

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'unsubscribe';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Unsubscribe', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Unsubscribe from the newsletter', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {
		return array();
	}

	/**
	 * Deactivates the subscriber.
	 *
	 * @since 1.3.1
	 * @param mixed $subject The subject.
	 * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return void
	 */
	public function run( $subject, $rule, $args ) {

		// Fetch the subscriber.
		if ( $subject instanceof Noptin_Subscriber ) {
			$subscriber = $subject;
		} else {
			$subscriber = get_noptin_subscriber( $args['email'] );
		}

		// Unsubscribe the subscriber.
		if ( ! empty( $subscriber->id ) ) {
			unsubscribe_noptin_subscriber( $subscriber );
		}

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
