<?php
/**
 * Emails API: Mass Email Sender.
 *
 * Contains the mass mailer class for sending emails to subscribers.
 *
 * @since   1.7.0
 * @package Noptin
 */

defined( 'ABSPATH' ) || exit;

/**
 * The mass mailer class for sending emails to subscribers.
 */
class Noptin_Mass_Mailer_Subscribers extends Noptin_Mass_Mailer {

	/**
	 * The email sender.
	 * @var string
	 */
	protected $sender = 'noptin';

	/**
	 * Displays newsletter sending options.
	 *
	 * @param Noptin_Newsletter_Email|Noptin_automated_Email $campaign
	 *
	 * @return bool
	 */
	public function display_sending_options( $campaign ) {

		// Render sender options.
		$fields  = array();

		foreach ( get_noptin_subscriber_filters() as $key => $filter ) {
			$fields[ $key ] = array(
				'label'       => $filter['label'],
				'type'        => 'select',
				'options'     => array_replace(
					array(
						'' => __( 'Any', 'newsletter-optin-box' ),
					),
					$filter['options']
				),
				'description' => empty( $filter['description'] ) ? '' : $filter['description'],
			);
		}

		$fields = apply_filters( 'noptin_subscriber_sending_options', $fields, $campaign );

		$this->display_sending_fields( $campaign, 'noptin_subscriber_options', $fields );
	}

	/**
	 * Sends a single email to a subscriber.
	 *
	 * @param Noptin_Newsletter_Email $campaign
	 * @param int|string $recipient
	 *
	 * @return bool
	 */
	public function _send( $campaign, $recipient ) {

		// Fetch the subscriber.
		$subscriber = get_noptin_subscriber( $recipient );

		// Bail if the subscriber is not found or is unsubscribed...
		if ( ! $subscriber->exists() || ! $subscriber->is_active() ) {
			return null;
		}

		// ... or was already sent the email.
		if ( ! $this->can_email_subscriber( $campaign, $subscriber ) ) {
			return null;
		}

		// Generate and send the actual email.
		noptin()->emails->newsletter->subscriber = $subscriber;

		$result = noptin()->emails->newsletter->send( $campaign, $campaign->id, $subscriber->email );

		// Log the send.
		update_noptin_subscriber_meta( $subscriber->id, '_campaign_' . $campaign->id, (int) $result );

		return $result;
	}

	/**
	 * Checks if a subscriber is valid for a given task.
	 *
	 * @param Noptin_Newsletter_Email $campaign The current campaign.
	 * @param Noptin_Subscriber $subscriber The subscriber to check.
	 * @return bool
	 */
	public function can_email_subscriber( $campaign, $subscriber ) {

		// Do not send twice.
		if ( '' !== get_noptin_subscriber_meta( $subscriber->id, '_campaign_' . $campaign->id, true ) ) {
			return null;
		}

		// Prepare sender options.
		$options = $campaign->get( 'noptin_subscriber_options' );
		$options = is_array( $options ) ? $options : array();

		return apply_filters( 'noptin_subscribers_can_email_subscriber_for_campaign', true, $options, $subscriber, $campaign );
	}

	/**
	 * Fired after a campaign is done sending.
	 *
	 * @param @param Noptin_Newsletter_Email $campaign
	 *
	 */
	public function done_sending( $campaign ) {
		global $wpdb;

		$wpdb->delete(
			get_noptin_subscribers_meta_table_name(),
			array(
				'meta_key' => '_campaign_' . $campaign->id,
			)
		);

	}

	/**
	 * Fetches relevant subscribers for the campaign.
	 *
	 * @param Noptin_Newsletter_Email $campaign
	 */
	public function _fetch_recipients( $campaign ) {

		$manual_recipients = $campaign->get_manual_recipients_ids();

		if ( ! empty( $manual_recipients ) ) {
			return $manual_recipients;
		}

		// Prepare arguments.
		$args = array(
			'subscriber_status' => 'active',
			'number'            => -1,
			'fields'            => array( 'id' ),
			'count_total'       => false,
			'meta_query'        => array(

				'relation' => 'AND',
				array(
					'key'     => '_campaign_' . $campaign->id,
					'compare' => 'NOT EXISTS',
				),

			),

		);

		// Handle custom fields.
		$options = $campaign->get( 'noptin_subscriber_options' );

		if ( ! empty( $options ) ) {
			foreach ( get_noptin_subscriber_filters() as $key => $filter ) {

				// Abort if the filter is not set.
				if ( ! isset( $options[ $key ] ) || '' === $options[ $key ] ) {
					continue;
				}

				// If the filter is a checkbox.
				if ( isset( $filter['type'] ) && 'checkbox' === $filter['type'] && '1' !== $options[ $key ] ) {

					// Fetch subscribers where key is either zero or not set.
					$args['meta_query'][] = array(
						'relation' => 'OR',
						array(
							'key'     => $key,
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'   => $key,
							'value' => '0',
						),
					);

					continue;

				}

				// Filter by the selected option.
				$args['meta_query'][] = array(
					'key'   => $key,
					'value' => $options[ $key ],
				);
			}
		}

		// (Backwards compatibility) Subscription source.
		$source = $campaign->get( '_subscriber_via' );

		if ( '' !== $source ) {
			$args['meta_query'][] = array(
				'key'   => '_subscriber_via',
				'value' => $source,
			);
		}

		// Allow other plugins to filter the query.
		$args = apply_filters( 'noptin_mass_mailer_subscriber_query', $args, $campaign );

		// Run the query...
		$query = new Noptin_Subscriber_Query( $args );

		// ... and return the result.
		return $query->get_results();

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
		$subscriber = get_noptin_subscriber( $recipient_id );

		if ( ! $subscriber->exists() ) {
			return $recipient;
		}

		return array(
			'name'  => trim( $subscriber->first_name . ' ' . $subscriber->second_name ),
			'email' => $subscriber->email,
			'url'   => add_query_arg( 'subscriber', $subscriber->id, admin_url( 'admin.php?page=noptin-subscribers' ) ),
		);
	}
}
