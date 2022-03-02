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
			return false;
		}

		// ... or was already sent the email.
		if ( '' !== get_noptin_subscriber_meta( $subscriber->id, '_campaign_' . $campaign->id, true ) ) {
			return false;
		}

		// Generate and send the actual email.
		noptin()->emails->newsletter->subscriber = $subscriber;
		$result = noptin()->emails->newsletter->send( $campaign, $campaign->ID, $subscriber->email );

		// Log the send.
		update_noptin_subscriber_meta( $subscriber->id, '_campaign_' . $campaign->id, (int) $result );

		return $result;
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

		// Prepare arguments.
		$args = array(
			'subscriber_status' => 'active',
			'number'            => -1,
			'fields'            => array( 'id' ),
			'count_total'       => false,
			'meta_query'        => array(

				'relation'      => 'AND',
				array(
					'key'     => '_campaign_' . $campaign->id,
					'compare' => 'NOT EXISTS',
				),

			),

		);

		// Handle custom fields.
		foreach ( get_noptin_custom_fields() as $custom_field ) {

			// Limit to checkboxes, dropdowns and radio buttons.
			if ( in_array( $custom_field['type'], array( 'checkbox', 'dropdown', 'radio' ) ) ) {

				// Fetch the appropriate filter.
				$filter = $campaign->get( 'noptin_custom_field_' . $custom_field['merge_tag'] );
	
				// Filter.
				if ( '' !== $filter ) {
					$args['meta_query'][] = array(
						'key'     => $custom_field['merge_tag'],
						'value'   => $filter,
					);
				}

			}

		}

		// Allow other plugins to filter the query.
		$args = apply_filters( 'noptin_mass_mailer_subscriber_query', $args, $campaign );

		// Run the query...
		$query = new Noptin_Subscriber_Query( $args );

		// ... and return the result.
		return $query->get_results();

	}

}
