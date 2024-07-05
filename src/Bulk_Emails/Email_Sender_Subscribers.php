<?php

namespace Hizzle\Noptin\Bulk_Emails;

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
	 * Get the sender settings.
	 *
	 * @return array
	 */
	public function get_sender_settings() {
		$fields = array();

		foreach ( get_noptin_subscriber_filters() as $key => $filter ) {

			// Skip status since emails can only be sent to active subscribers.
			if ( 'status' === $key ) {
				continue;
			}

			$multiple = 2 < count( $filter['options'] );
			$options  = $filter['options'];

			$fields[ $key ] = array(
				'label'                => $filter['label'],
				'type'                 => 'select',
				'placeholder'          => __( 'Any', 'newsletter-optin-box' ),
				'canSelectPlaceholder' => true,
				'options'              => $options,
				'description'          => ( empty( $filter['description'] ) || $filter['label'] === $filter['description'] ) ? '' : $filter['description'],
			);

			if ( $multiple || $filter['is_multiple'] ) {

				$fields[ $key ]['placeholder'] = __( 'Optional. Leave blank to send to all', 'newsletter-optin-box' );
				$fields[ $key ]['multiple']    = 'true';

				$fields[ $key . '_not' ] = array_merge(
					$fields[ $key ],
					array(
						'label'       => sprintf(
							// translators: %s is the filter label, e.g, "Tags".
							__( '%s - Exclude', 'newsletter-optin-box' ),
							$filter['label']
						),
						'description' => '',
					)
				);
			}
		}

		$all_tags = array();

		if ( is_callable( array( noptin()->db(), 'get_all_meta_by_key' ) ) ) {
			$all_tags = noptin()->db()->get_all_meta_by_key( 'tags' );
		}

		$fields['tags'] = array(
			'label'       => __( 'Tags', 'newsletter-optin-box' ),
			'type'        => 'token',
			'description' => __( 'Optional. Filter recipients by their tags.', 'newsletter-optin-box' ),
			'suggestions' => $all_tags,
		);

		$fields['tags_not'] = array(
			'label'       => __( 'Tags - Exclude', 'newsletter-optin-box' ),
			'type'        => 'token',
			'description' => __( 'Optional. Exclude recipients by their tags.', 'newsletter-optin-box' ),
			'suggestions' => $all_tags,
		);

		return array(
			'key'    => 'noptin_subscriber_options',
			'fields' => apply_filters( 'noptin_subscriber_sending_options', $fields ),
			'upsell' => array(
				'link'    => noptin_get_upsell_url( '/pricing/', 'filter-subscribers', 'email-campaigns' ),
				'message' => __( 'Premium plans allow you to filter newsletter recipients by their subscription method, tags, lists, and custom fields.', 'newsletter-optin-box' ),
			),
		);
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
		$subscriber = noptin_get_subscriber( $recipient );

		// Bail if the subscriber is not found or is unsubscribed...
		if ( ! $subscriber->exists() ) {
			return null;
		}

		// ... or was already sent the email.
		if ( ! $this->can_email_subscriber( $campaign, $subscriber ) ) {
			update_noptin_subscriber_meta( $subscriber->get_id(), '_campaign_' . $campaign->id, 0 );
			return null;
		}

		// Generate and send the actual email.
		$result = $campaign->send_to(
			array(
				'subscriber' => $subscriber->get_id(),
				'email'      => $subscriber->get_email(),
			),
			false
		);

		if ( is_wp_error( $result ) ) {
			$result = 0;
		}

		// Log the send.
		update_noptin_subscriber_meta( $subscriber->get_id(), '_campaign_' . $campaign->id, (int) $result );

		return $result;
	}

	/**
	 * Checks if a subscriber is valid for a given task.
	 *
	 * @param \Noptin_Newsletter_Email $campaign The current campaign.
	 * @param \Hizzle\Noptin\DB\Subscriber $subscriber The subscriber to check.
	 * @return bool
	 */
	public function can_email_subscriber( $campaign, $subscriber ) {

		// Do not send twice.
		if ( '' !== get_noptin_subscriber_meta( $subscriber->get_id(), '_campaign_' . $campaign->id, true ) ) {
			return null;
		}

		// Check if the subscriber is active.
		if ( ! $subscriber->is_active() ) {
			return false;
		}

		// Prepare sender options.
		$options = $campaign->get( 'noptin_subscriber_options' );
		$options = is_array( $options ) ? $options : array();

		// Apply generic filter.
		if ( ! apply_filters( 'noptin_can_email_recipient_for_bulk_campaign', true, $subscriber->get_email(), $options, $campaign ) ) {
			return false;
		}

		return apply_filters( 'noptin_subscribers_can_email_subscriber_for_campaign', true, $options, $subscriber, $campaign );
	}

	/**
	 * Fired after a campaign is done sending.
	 *
	 * @param @param \Noptin_Newsletter_Email $campaign
	 *
	 */
	public function done_sending( $campaign ) {
		noptin()->db()->delete_all_meta_by_key( '_campaign_' . $campaign->id );
	}

	/**
	 * Get the next recipient for the campaign.
	 *
	 * @param \Noptin_Newsletter_Email $campaign
	 */
	public function get_recipients( $campaign ) {

		// Prepare arguments.
		$args = array(
			'status'     => 'subscribed',
			'number'     => 5,
			'fields'     => 'id',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => '_campaign_' . $campaign->id,
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$manual_recipients = $campaign->get_manual_recipients_ids();
		if ( ! empty( $manual_recipients ) ) {
			$args['include'] = $manual_recipients;
		} else {
			// Handle custom fields.
			$options = $campaign->get( 'noptin_subscriber_options' );

			if ( is_array( $options ) ) {

				// Backward compatibility.
				if ( ! empty( $options['_subscriber_via'] ) ) {
					if ( ! isset( $options['source'] ) ) {
						$args['source'] = $options['_subscriber_via'];
					}
					unset( $options['_subscriber_via'] );
				}

				// Loop through available filters.
				$filters = array_merge(
					array_keys( get_noptin_subscriber_filters() ),
					array( 'tags' )
				);

				foreach ( $filters as $filter ) {

					// Filter by key.
					$filtered = isset( $options[ $filter ] ) ? $options[ $filter ] : '';

					if ( '' !== $filtered && array() !== $filtered ) {
						$args[ $filter ] = $filtered;
					}

					// Exclude by key.
					$filtered = isset( $options[ $filter . '_not' ] ) ? $options[ $filter . '_not' ] : '';

					if ( '' !== $filtered && array() !== $filtered ) {
						$args[ $filter . '_not' ] = $filtered;
					}
				}
			}

			// (Backwards compatibility) Subscription source.
			$source = $campaign->get( '_subscriber_via' );

			if ( '' !== $source && empty( $options['source'] ) ) {
				$args['source'] = $source;
			}

			// Allow other plugins to filter the query.
			$args = apply_filters( 'noptin_mass_mailer_subscriber_query', $args, $campaign );
		}

		// Run the query...
		return noptin_get_subscribers( $args );
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
