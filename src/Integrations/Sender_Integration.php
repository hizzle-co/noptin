<?php

namespace Hizzle\Noptin\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base sender integration
 *
 * @since 3.0.0
 */
abstract class Sender_Integration {

	/**
	 * @var array The senders to exclude.
	 * @since 2.0.0
	 */
	protected $exclude = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'noptin_email_sender_settings', array( $this, 'add_sender_settings' ), 10, 2 );
		add_filter( 'noptin_can_email_recipient_for_bulk_campaign', array( $this, 'check_can_email_recipient' ), 10, 3 );
	}

	/**
	 * Registers sender settings.
	 *
	 * @return array
	 */
	public function add_sender_settings( $settings, $sender ) {

		if ( ! in_array( $sender, $this->exclude, true ) && ! empty( $settings['fields'] ) ) {
			$settings['fields'] = array_merge( $settings['fields'], $this->get_settings() );
		}

		return $settings;
	}

	/**
	 * Retrieves sender settings.
	 *
	 * @return array
	 */
	abstract protected function get_settings();

	/**
	 * Checks if we can email a given recipient.
	 *
	 * @return boolean
	 */
	public function check_can_email_recipient( $can_email, $email_address, $options ) {

		if ( ! $can_email || empty( $options ) ) {
			return $can_email;
		}

		return $this->can_email_recipient( $email_address, $options );
	}

	/**
	 * Checks if we can email a certain recipient.
	 *
	 * @return array
	 */
	abstract protected function can_email_recipient( $email_address, $options );
}
