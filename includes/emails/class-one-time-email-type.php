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
	 * @var array
	 */
	public $custom_merge_tags = array();

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
		$this->user              = $email->get_user();
		$this->subscriber        = $email->get_subscriber();
		$this->custom_merge_tags = $email->get_merge_tags();

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

	/**
	 * Retrieves an array of supported merge tags.
	 *
	 * @return array
	 */
	public function get_merge_tags() {

		$merge_tags = array();
		foreach ( array_keys( $this->custom_merge_tags ) as $key ) {

			$merge_tags[ $key ] = array(
				'description' => $key,
				'callback'    => array( $this, 'process_custom_merge_tag' ),
				'example'     => $key,
				'partial'     => true,
			);
		}

		return array(
			__( 'Custom', 'newsletter-optin-box' ) => $merge_tags,
		);
	}

	/**
	 * Processes custom merge tags.
	 *
	 * @param array $args
	 * @param string $field
	 * @return string
	 */
	public function process_custom_merge_tag( $args = array(), $key = 'none' ) {

		$default = isset( $args['default'] ) ? $args['default'] : '';

		if ( ! isset( $this->custom_merge_tags[ $key ] ) ) {
			return wp_kses_post( $default );
		}

		return wp_kses_post( $this->custom_merge_tags[ $key ] );
	}

}
