<?php

/**
 * Bulk Emails API: Email Sender.
 *
 * Contains the main email sender class.
 *
 * @since   1.12.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Bulk_Emails;

defined( 'ABSPATH' ) || exit;

/**
 * The main email sender class.
 */
abstract class Email_Sender {

	/**
	 * The email sender.
	 * @var string
	 */
	protected $sender = 'noptin';

	/**
	 * Initiates new non-blocking asynchronous request.
	 *
	 * @ignore
	 */
	public function __construct() {

		// Displays sender options.
		add_filter( 'noptin_email_senders', array( $this, 'add_sender_settings' ) );

		// Prepares a recipient.
		add_filter( "noptin_{$this->sender}_email_recipient", array( $this, 'filter_recipient' ), 10, 2 );
	}

	/**
	 * Fetch the next recipient.
	 *
	 * @param \Noptin_Newsletter_Email $campaign
	 *
	 * @return int[]|string[]
	 */
	abstract public function get_recipients( $campaign );

	/**
	 * Sends the actual email.
	 *
	 * @param @param \Noptin_Newsletter_Email $campaign
	 * @param int|string $recipient
	 *
	 * @return bool
	 */
	abstract public function send( $campaign, $recipient );

	/**
	 * Fired after a campaign is done sending.
	 *
	 * @param @param \Noptin_Newsletter_Email $campaign
	 *
	 */
	abstract public function done_sending( $campaign );

	/**
	 * Returns the sender settings.
	 *
	 * @return array
	 */
	public function add_sender_settings( $senders ) {

		if ( isset( $senders[ $this->sender ] ) ) {
			$senders[ $this->sender ]['is_installed'] = true;

			if ( noptin_has_active_license_key() ) {
				$senders[ $this->sender ]['settings'] = apply_filters(
					'noptin_email_sender_settings',
					$this->get_sender_settings(),
					$this->sender
				);
			}
		}

		return $senders;
	}

	/**
	 * Get the sender settings.
	 *
	 * @return array
	 */
	public function get_sender_settings() {
		return array();
	}

	/**
	 * Displays setting fields.
	 *
	 * @deprecated
	 * @return bool
	 */
	public function display_sending_fields() {
		_deprecated_function( __METHOD__, '3.0.0' );
	}

	/**
	 * Filters a recipient.
	 *
	 * @param false|array $recipient
	 * @param int $recipient_id
	 *
	 * @return array
	 */
	public function filter_recipient( $recipient, $recipient_id ) {

		if ( ! is_array( $recipient ) ) {
			$recipient = array();
		}

		return $recipient;
	}
}
