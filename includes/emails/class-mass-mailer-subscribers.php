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

		// Bail if the subscriber is not found.
		if ( ! $subscriber->exists() ) {
			return false;
		}

		// TODO: Generate and send the actual email.
		update_noptin_subscriber_meta( $subscriber->id, '_campaign_' . $campaign->id, '1' ); // Success
		update_noptin_subscriber_meta( $subscriber->id, '_campaign_' . $campaign->id, '0' ); // Failure

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
