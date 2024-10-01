<?php

/**
 * Controller for emailing a collection of people.
 *
 * @since 1.0.0
 */

namespace Hizzle\Noptin\Objects;

defined( 'ABSPATH' ) || exit;

/**
 * Controller for emailing a collection of people.
 */
class People_List extends \Hizzle\Noptin\Bulk_Emails\Email_Sender {

	/**
	 * @var string collection type.
	 */
	public $collection_type;

	/**
	 * @var string options key.
	 */
	public $options_key;

	/**
	 * Class constructor.
	 *
	 * @param People $collection
	 */
	public function __construct( $collection ) {
		$this->sender          = $collection->email_sender;
		$this->collection_type = $collection->type;
		$this->options_key     = $collection->email_sender_options;

		add_action( 'noptin_init_current_email_recipient', array( $this, 'prepare_email_test_sender_data' ) );
		parent::__construct();
	}

	/**
	 * Retrieves the current collection.
	 *
	 * @return People|null
	 */
	public function get_collection() {
		return Store::get( $this->collection_type );
	}

	/**
	 * Prepares test data.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $email
	 */
	public function prepare_email_test_sender_data( $email ) {
		$recipient = \Hizzle\Noptin\Emails\Main::$current_email_recipient;

		// Abort if not test mode.
		if ( ! $email || ! $email->is_mass_mail() || $email->get_sender() !== $this->sender || ! empty( $recipient[ $this->collection_type ] ) || ! isset( $recipient['mode'] ) || 'preview' !== $recipient['mode'] ) {
			return;
		}

		if ( 'noptin' !== $this->sender && ! noptin_has_active_license_key() ) {
			return;
		}

		$manual     = $email->get_manual_recipients_ids();
		$collection = $this->get_collection();
		if ( ! empty( $manual ) ) {
			\Hizzle\Noptin\Emails\Main::$current_email_recipient[ $this->collection_type ] = $manual[0];
		} elseif ( $collection ) {
			\Hizzle\Noptin\Emails\Main::$current_email_recipient[ $this->collection_type ] = $collection->get_test_id();
		}
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
		$collection = $this->get_collection();

		if ( ! $collection ) {
			return $recipient;
		}

		/** @var Person $record */
		$record = $collection->get( $recipient_id );

		if ( ! $record || ! $record->exists() ) {
			return $recipient;
		}

		return array(
			'name'  => $record->get_name(),
			'email' => $record->get_email(),
			'url'   => $record->get_edit_url(),
		);
	}

	/**
	 * Fetches relevant contacts for the campaign.
	 *
	 * @param \Noptin_Newsletter_Email $campaign
	 */
	public function get_recipients( $campaign ) {

		// Check if we have contacts.
		$contacts = get_post_meta( $campaign->id, 'contacts_to_send', true );

		if ( is_array( $contacts ) ) {
			return $contacts;
		}

		$collection = $this->get_collection();

		if ( ! $collection ) {
			return array();
		}

		$options = empty( $this->options_key ) ? array() : $campaign->get( $this->options_key );
		$options = is_array( $options ) ? $options : array();
		$unique  = array_unique( $collection->get_newsletter_recipients( $options, $campaign ) );
		$unique  = apply_filters( 'noptin_' . $collection->type . '_newsletter_recipients', $unique, $campaign );

		update_post_meta( $campaign->id, 'contacts_to_send', $unique );
		return $unique;
	}

	/**
	 * Fired after a campaign is done sending.
	 *
	 * @param @param \Noptin_Newsletter_Email $campaign
	 *
	 */
	public function done_sending( $campaign ) {
		delete_post_meta( $campaign->id, 'contacts_to_send' );
	}

	/**
	 * Checks if a contact is valid for a given email.
	 *
	 * @param \Noptin_Newsletter_Email $campaign The current campaign.
	 * @param Person $person The person to check.
	 * @param array $options The sender options.
	 * @return bool
	 */
	public function can_email_contact( $campaign, $person, $options ) {

		// Check per subject conditions.
		$conditional_logic = $campaign->get( 'extra_conditional_logic' );
		if ( $conditional_logic && is_array( $conditional_logic ) ) {
			// Retrieve the conditional logic.
			$smart_tags = new Tags( $this->collection_type );

			$smart_tags->prepare_record_tags( $person );
			$result = $smart_tags->check_conditional_logic( $conditional_logic );
			$smart_tags->restore_record_tags();

			// Check if the conditional logic is met.
			if ( ! $result ) {
				return false;
			}
		}

		// Get user locale.
		if ( ! empty( $options['locale'] ) && $person->get( 'locale' ) !== $options['locale'] ) {
			return false;
		}

		// Apply generic filter.
		if ( ! apply_filters( 'noptin_can_email_recipient_for_bulk_campaign', true, $person->get_email(), $options, $campaign ) ) {
			return false;
		}

		// Apply specific filter.
		return apply_filters( 'noptin_can_email_' . $this->collection_type . '_for_campaign', true, $options, $person->external, $campaign, $person );
	}

	/**
	 * Sends a single email to a contact.
	 *
	 * @param \Noptin_Newsletter_Email $campaign
	 * @param int $contact_id
	 *
	 * @return bool
	 */
	public function send( $campaign, $contact_id ) {

		$collection = $this->get_collection();

		if ( ! $collection ) {
			return new \WP_Error( 'noptin_cannot_email_contact', 'Collection does not exist.' );
		}

		// Check if we have contacts.
		$contacts = get_post_meta( $campaign->id, 'contacts_to_send', true );

		// Remove current contact from the list.
		if ( is_array( $contacts ) ) {
			if ( ! in_array( $contact_id, $contacts, true ) ) {
				return new \WP_Error( 'noptin_cannot_email_contact', 'Contact does not exist in the list.' );
			}

			$contacts = array_diff( $contacts, array( $contact_id ) );
			update_post_meta( $campaign->id, 'contacts_to_send', $contacts );
		}

		// Get the contact.
		/** @var Person $person */
		$person = $collection->get( $contact_id );

		if ( ! $person || ! $person->exists() ) {
			return new \WP_Error( 'noptin_cannot_email_contact', 'Contact does not exist.' );
		}

		$email = $person->get_email();

		// Bail if the contact is not found or is unsubscribed...
		if ( empty( $email ) || noptin_is_email_unsubscribed( $email ) ) {
			return new \WP_Error( 'noptin_cannot_email_contact', 'Contact is unsubscribed.' );
		}

		// ... or does not qualify for the campaign.
		$options = empty( $this->options_key ) ? array() : $campaign->get( $this->options_key );
		$options = is_array( $options ) ? $options : array();
		if ( ! $this->can_email_contact( $campaign, $person, $options ) ) {
			return new \WP_Error( 'noptin_cannot_email_contact', 'Contact does not qualify for the campaign.' );
		}

		// Generate and send the actual email.
		return $campaign->send_to(
			array(
				'email'           => $email,
				$collection->type => $contact_id,
			)
		);
	}

	/**
	 * Get the sender settings.
	 *
	 * @return array
	 */
	public function get_sender_settings() {

		if ( empty( $this->options_key ) ) {
			return array();
		}

		$settings = array(
			'key'    => $this->options_key,
			'fields' => $this->get_collection()->get_sender_settings(),
		);

		if ( 'noptin' === $this->sender ) {
			$settings['upsell'] = array(
				'link'    => noptin_get_upsell_url( '/pricing/', 'filter-subscribers', 'email-campaigns' ),
				'message' => __( 'Premium plans allow you to filter newsletter recipients by their subscription method, tags, lists, and custom fields.', 'newsletter-optin-box' ),
			);
		}

		return $settings;
	}
}
