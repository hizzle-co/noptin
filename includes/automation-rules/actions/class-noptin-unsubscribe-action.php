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
		return __( 'Subscriber > Unsubscribe', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Unsubscribe from the newsletter', 'newsletter-optin-box' );
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
		unsubscribe_noptin_subscriber( $this->get_subject_email( $subject, $rule, $args ) );
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

		// Fetch the subscriber.
		$subscriber = noptin_get_subscriber( $this->get_subject_email( $subject, $rule, $args ) );

		return $subscriber->exists();
	}
}
