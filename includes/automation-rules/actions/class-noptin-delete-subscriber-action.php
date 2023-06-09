<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Deletes a subscriber.
 *
 * @since 1.12.0
 */
class Noptin_Delete_Subscriber_Action extends Noptin_Abstract_Action {

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'delete_subscriber';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Subscriber > Delete', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Delete the subscriber', 'newsletter-optin-box' );
	}

	/**
	 * Delete the subscriber.
	 *
	 * @since 1.12.0
	 * @param mixed $subject The subject.
	 * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return void
	 */
	public function run( $subject, $rule, $args ) {

		// Fetch the subscriber.
		$subscriber_id = get_noptin_subscriber_id_by_email( $this->get_subject_email( $subject, $rule, $args ) );

		// Delete the subscriber.
		if ( $subscriber_id ) {
			delete_noptin_subscriber( $subscriber_id );
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

		// Fetch the subscriber.
		$subscriber = noptin_get_subscriber( $this->get_subject_email( $subject, $rule, $args ) );

		return $subscriber->exists();
	}
}
