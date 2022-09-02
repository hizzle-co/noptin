<?php
/**
 * Emails API: One Time Email Type.
 *
 * Sends a one-time email.
 *
 * @since   1.7.8
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Container for the one-time email type.
 *
 * @since 1.7.8
 * @internal
 * @ignore
 */
class Noptin_One_Time_Email_Type extends Noptin_Email_Type {

	/**
	 * @var string
	 */
	public $type = 'one_time';

	/**
	 * (Maybe) Sends a new email.
	 *
	 * @param Noptin_One_Time_Email $email The actual email.
	 */
	public function maybe_send_campaign( $email ) {

		// Abort if the email is not set-up correctly.
		if ( ! $email->can_send() ) {
			return false;
		}

		// Prepare object vars.
		$this->user       = $email->get_user();
		$this->subscriber = $email->get_subscriber();

		// Skip inactive subscribers.
		if ( $this->subscriber && ! $this->subscriber->is_active() ) {
			return false;
		}

		// Skip inactive users.
		if ( $this->user && noptin_is_wp_user_unsubscribed( $this->user->ID ) ) {
			return false;
		}

		// Send the actual email.
		return $this->send( $email, $email->get_key(), array( sanitize_email( $email->recepient ) => false ) );

	}

	/**
	 * Sends a test email.
	 *
	 * @param Noptin_One_Time_Email $email The actual email.
	 * @param string $recipient
	 * @return bool Whether or not the test email was sent
	 */
	public function send_test( $email, $recipient ) {
		$email->recepient = $recipient;
		return $this->maybe_send_campaign( $email );
	}

}
