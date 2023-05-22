<?php

namespace Hizzle\Noptin\Bulk_Emails;

/**
 * Bulk Emails API: Email Sender (subscribers).
 *
 * Contains the main email sender class.
 *
 * @since   1.12.0
 * @package Noptin
 */

defined( 'ABSPATH' ) || exit;

/**
 * The mass mailer class for sending emails to subscribers.
 */
class Email_Sender_Subscribers extends Email_Sender {

	/**
	 * The email sender.
	 * @var string
	 */
	protected $sender = 'noptin';

	/**
	 * Displays newsletter sending options.
	 *
	 * @param \Noptin_Newsletter_Email|\Noptin_automated_Email $campaign
	 *
	 * @return bool
	 */
	public function display_sending_options( $campaign ) {

		$current = $campaign->get( 'noptin_subscriber_options' );

		if ( empty( $current ) && ! defined( 'NOPTIN_ADDONS_PACK_VERSION' ) ) {
			?>
			<p><?php esc_html_e( 'The add-ons pack allows you to filter newsletter recipients by their subscription method, tags, lists, and custom fields.', 'newsletter-optin-box' ); ?></p>
			<p><a href="<?php echo esc_url( noptin_get_upsell_url( '/pricing/', 'filter-subscribers', 'email-campaigns' ) ); ?>" class="button noptin-button-standout" target="_blank"><?php esc_html_e( 'View Pricing', 'newsletter-optin-box' ); ?>&nbsp;<i class="dashicons dashicons-arrow-right-alt"></i></a></p>
			<?php
			return;
		}

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
	 * @param \Noptin_Newsletter_Email $campaign
	 * @param int|string $recipient
	 *
	 * @return bool
	 */
	public function send( $campaign, $recipient ) {

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
	 * @param \Noptin_Newsletter_Email $campaign The current campaign.
	 * @param \Noptin_Subscriber $subscriber The subscriber to check.
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
	 * @param @param \Noptin_Newsletter_Email $campaign
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
	 * Get the next recipient for the campaign.
	 *
	 * @param \Noptin_Newsletter_Email $campaign
	 */
	public function get_recipients( $campaign ) {

		$manual_recipients = $campaign->get_manual_recipients_ids();
		if ( ! empty( $manual_recipients ) ) {
			return $manual_recipients;
		}

		// Prepare arguments.
		$args = array(
			'subscriber_status' => 'active',
			'number'            => 5,
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

				// Add the filter.
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
		$query = new \Noptin_Subscriber_Query( $args );

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
		$subscriber = noptin_get_subscriber( $recipient_id );

		if ( ! $subscriber->exists() ) {
			return $recipient;
		}

		return array(
			'name'  => $subscriber->get_name(),
			'email' => $subscriber->get_email(),
			'url'   => $subscriber->get_edit_url(),
		);
	}
}
