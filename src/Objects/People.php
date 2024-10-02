<?php

/**
 * Container for a collection of people.
 *
 * @since   1.0.0
 */

namespace Hizzle\Noptin\Objects;

defined( 'ABSPATH' ) || exit;

/**
 * Container for a collection of people.
 */
abstract class People extends Collection {

	/**
	 * @var string object type.
	 */
	public $object_type = 'person';

	/**
	 * @var string The email sender for this collection.
	 */
	public $email_sender;

	/**
	 * @var string The email sender options for this collection.
	 */
	public $email_sender_options;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		if ( ! empty( $this->email_sender ) ) {
			add_filter( 'noptin_' . $this->email_sender . '_email_sender_collection_object', array( $this, 'get_instance' ) );

			if ( ! empty( $this->email_sender_options ) ) {
				add_filter( 'noptin_bulk_email_senders', array( $this, 'add_email_sender' ) );
			} else {
				add_action( 'noptin_init_current_email_recipient', array( $this, 'prepare_email_test_sender_data' ) );
			}
		}

		parent::__construct();
	}

	/**
	 * Retrieves a single person from a WordPress user.
	 *
	 * @param \WP_User $user The user.
	 * @return Person $person The person.
	 */
	public function get_from_user( $user ) {
		return $this->get_from_email( $user->user_email );
	}

	/**
	 * Retrieves a single person from an email address.
	 *
	 * @param string $email The email address.
	 * @return Person $person The person.
	 */
	abstract public function get_from_email( $email );

	/**
	 * Retrieves fields that can be calculated from an email address.
	 */
	public function provides() {
		return array();
	}

	/**
	 * Adds provided fields.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	protected function add_provided( $fields ) {
		foreach ( $this->get_related_collections() as $collection ) {

			/** @var People $collection */
			$provides = $collection->provides();

			if ( empty( $provides ) || $this->integration === $collection->integration ) {
				continue;
			}

			foreach ( $provides as $key => $field ) {
				if ( is_array( $field ) ) {
					$fields[ "{$collection->type}.{$key}" ] = array_merge(
						$field,
						array(
							'label' => $collection->singular_label . ' >> ' . $field['label'],
						)
					);
				}
			}
		}

		return $fields;
	}

	/**
	 * (Maybe) Registers the object.
	 */
	public function register_object( $objects ) {
		$objects = parent::register_object( $objects );

		if ( ! empty( $this->email_sender ) && isset( $objects[ $this->type ] ) ) {
			$objects[ $this->type ]['sender'] = $this->email_sender;
		}

		return $objects;
	}

	/**
	 * Prepares test data.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $email
	 */
	public function prepare_email_test_sender_data( $email ) {
		$recipient = \Hizzle\Noptin\Emails\Main::$current_email_recipient;

		// Abort if not test mode.
		if ( ! $email || ! $email->is_mass_mail() || $email->get_sender() !== $this->email_sender || ! empty( $recipient[ $this->type ] ) || ! isset( $recipient['mode'] ) || 'preview' !== $recipient['mode'] ) {
			return;
		}

		if ( 'noptin' !== $this->email_sender && ! noptin_has_active_license_key() ) {
			return;
		}

		$manual = $email->get_manual_recipients_ids();

		if ( ! empty( $manual ) ) {
			\Hizzle\Noptin\Emails\Main::$current_email_recipient[ $this->type ] = $manual[0];
		} else {
			\Hizzle\Noptin\Emails\Main::$current_email_recipient[ $this->type ] = $this->get_test_id();
		}
	}

	/**
	 * Retrieves a test ID.
	 *
	 */
	abstract public function get_test_id();

	/**
	 * Retrieves email sender settings.
	 *
	 * @return array
	 */
	public function get_sender_settings() {
		return array();
	}

	/**
	 * Retrieves email recipients.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $email
	 */
	public function get_newsletter_recipients( $options, $email ) {
		return array();
	}

	/**
	 * Adds email sender to the list of available email senders.
	 *
	 * @param array $senders
	 * @return array
	 */
	public function add_email_sender( $senders ) {
		$senders[ $this->email_sender ] = new People_List( $this );

		return $senders;
	}
}
