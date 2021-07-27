<?php
/**
 * Class Noptin_Background_Mailer class.
 *
 * @extends Noptin_Background_Process
 */

defined( 'ABSPATH' ) || exit;

/**
 * The background mailing class. 
 */
class Noptin_Background_Mailer extends Noptin_Background_Process {

	/**
	 * The background action for our mailing process.
	 * @var string
	 */
	protected $action = 'noptin_bg_mailer';

	/**
	 * Pushes the qeue forward to be handled in the future.
	 *
	 */
	protected function push_forwards() {
		$this->unlock_process();
		wp_die();
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param array $item The current item being processed.
	 *
	 * @return mixed
	 */
	public function task( $item ) {

		// Abort if we've exceeded the hourly limit.
		if ( $this->exceeded_hourly_limit() ) {
			$this->push_forwards();
		}

		// Then, prepare the campaign data.
		$item = $this->prepare_campaign_data( $item );

		// And abort in case of an error.
		if ( empty( $item ) ) {
			return false;
		}

		$recipient_data = $item['next_recipient_data'];
		unset( $item['next_recipient_data'] );

		// If no email is set, move on to the next recipient.
		if ( empty( $recipient_data['email'] ) ) {
			return $item;
		}

		// Ensure that this subscriber is yet to be sent this campaign.
		$key = '_campaign_' . $item['key'];
		if ( isset( $recipient_data['merge_tags'][ $key ] ) ) {
			return $item;
		}

		// Try sending the email.
		if ( noptin()->mailer->prepare_then_send( $recipient_data ) ) {

			if ( ! empty( $item['campaign_id'] ) ) {

				$sents = (int) get_post_meta( $item['campaign_id'], '_noptin_sends', true );
				update_post_meta( $item['campaign_id'], '_noptin_sends', $sents + 1 );

			}

			if ( ! empty( $recipient_data['merge_tags']['id'] ) ) {
				update_noptin_subscriber_meta( $recipient_data['merge_tags']['id'], $key, '1' );
			}
		} else {

			if ( ! empty( $item['campaign_id'] ) ) {

				$fails = (int) get_post_meta( $item['campaign_id'], '_noptin_fails', true );
				update_post_meta( $item['campaign_id'], '_noptin_fails', $fails + 1 );

			}

			if ( ! empty( $recipient_data['merge_tags']['id'] ) ) {
				update_noptin_subscriber_meta( $recipient_data['merge_tags']['id'], $key, '0' );
			}

		}

		$this->increase_emails_sent_this_hour();
		return $item;

	}

	/**
	 * Prepares campaign data.
	 * 
	 * @param array item The item to prepare campaign data for.
	 */
	public function prepare_campaign_data( $item ) {

		// A unique key for this campaign.
		$key = time() . wp_generate_password( 6, false );

		// If this is a normal campaign, ensure it is published.
		if ( isset( $item['campaign_id'] ) ) {

			// Set the unique key to the campaign id.
			$key = $item['campaign_id'];

			// Fetch the post and ensure it is a published campaign.
			$post = get_post( $item['campaign_id'] );
			if ( 'noptin-campaign' !== $post->post_type || 'publish' !== $post->post_status ) {
				return false;
			}
		}

		if ( empty( $item['key'] ) ) {
			$item['key'] = $key;
		}

		// Fetch the next recipient of this campaign.
		$next_recipient = false;

		// User can set recipients to be an array of subcriber ids or emails...
		if ( isset( $item['recipients'] ) ) {
			$next_recipient = array_shift( $item['recipients'] );
		} else if ( isset( $item['subscribers_query'] ) ) {
			// Or a WHERE query to be executed on the subscribers table.
			$next_recipient = $this->fetch_subscriber_from_query( $item );
		}

		// If there is no other subscriber, abort.
		if ( empty( $next_recipient ) ) {

			if ( ! empty( $item['campaign_id'] ) ) {
				update_post_meta( $item['campaign_id'], 'completed', 1 );
			}

			do_action( 'noptin_background_mailer_complete', $item );

			return false;
		}

		$item['current_recipient'] = $next_recipient;

		// Recipient data.
		$item['next_recipient_data'] = array();
		if ( isset( $item['campaign_data'] ) ) {
			$item['next_recipient_data'] = $item['campaign_data'];
		}

		// If this is a campaign, fetch the email body and subject.
		if ( isset( $item['campaign_id'] ) && empty( $item['next_recipient_data']['email_body'] ) ) {

			// Email body is the post content
			$item['next_recipient_data']['email_body'] = $post->post_content;

			// Email subject is the post title for newsletters and post_meta for campaign automations.
			if ( 'newsletter' === get_post_meta( $post->ID, 'campaign_type', true ) ) {
				$item['next_recipient_data']['email_subject'] = $post->post_title;
			} else {
				$item['next_recipient_data']['email_subject'] = get_post_meta( $post->ID, 'email_subject', true );
			}

			// Finally, fetch the preview text.
			$item['next_recipient_data']['preview_text'] = get_post_meta( $post->ID, 'preview_text', true );

		}

		// Maybe fetch a subscriber from an email...
		if ( is_email( $next_recipient ) ) {
			$item['next_recipient_data']['email'] = $next_recipient;
		}

		// Or a subscriber id.
		if ( is_scalar( $next_recipient ) ) {
			$subscriber = get_noptin_subscriber( $next_recipient );
		}

		// Prepare the email merge tags.
		if ( empty( $item['next_recipient_data']['merge_tags'] ) ) {
			$item['next_recipient_data']['merge_tags'] = array();
		}

		if ( isset( $item['custom_merge_tags'] ) ) {
			$item['next_recipient_data']['merge_tags'] = array_merge( $item['next_recipient_data']['merge_tags'], $item['custom_merge_tags'] );
		}

		// Add the subscriber details as merge tags.
		if ( $subscriber->exists() ) {

			$item['next_recipient_data']['email']         = $subscriber->email;
			$item['next_recipient_data']['subscriber_id'] = $subscriber->id;

			$item['next_recipient_data']['merge_tags'] = array_merge( $item['next_recipient_data']['merge_tags'], get_noptin_subscriber_merge_fields( $subscriber ) );

		}

		return $item;
	}

	/**
	 * Fetches a subscriber from a subscribers query.
	 *
	 * @param array item The item to fetch a subscriber for.
	 */
	public function fetch_subscriber_from_query( $item ) {

		// Ensure that the query is an array...
		$subscriber_query = ( ! is_array( $item['subscribers_query'] ) ) ? array() : $item['subscribers_query'];

		// ... and it has a meta query too.
		if ( empty( $subscriber_query['meta_query'] ) ) {
			$subscriber_query['meta_query'] = array();
		}

		$subscriber_query['meta_query']['relation'] = 'AND';

		// Avoid sending the same campaign twice.
		$subscriber_query['meta_query'][] = array(
			'key'     => '_campaign_' . $item['key'],
			'compare' => 'NOT EXISTS',
		);

		// Retrieve the id...
		$subscriber_query['fields'] = array( 'id' );

		// ... of the next subscriber...
		$subscriber_query['number'] = 1;

		// who is active.
		$subscriber_query['subscriber_status'] = 'active';

		// We do not need to count the total number of subscribers.
		$subscriber_query['count_total'] = false;

		// Filters the query used to find the next recipient of a mass mail.
		$query = apply_filters( 'noptin_background_mailer_subscriber_query', $subscriber_query, $item );

		// Run the query...
		$subscriber = new Noptin_Subscriber_Query( $query );
		$result     = $subscriber->get_results();

		// ... and return the result.
		return empty( $result ) ? null : $result[0];

	}

	/**
	 * Returns the current hour.
	 *
	 * @return string
	 */
	public function current_hour() {
		return date( 'YmdH' );
	}

	/**
	 * Returns the number sent this hour.
	 *
	 * @return int
	 */
	public function emails_sent_this_hour() {
		return (int) get_transient( 'noptin_emails_sent_' . $this->current_hour() );
	}

	/**
	 * Increase sent this hour.
	 *
	 * @return void
	 */
	public function increase_emails_sent_this_hour() {
		set_transient( 'noptin_emails_sent_' . $this->current_hour(), $this->emails_sent_this_hour() + 1, 2 * HOUR_IN_SECONDS );
	}

	/**
	 * Checks if we've exceeded the hourly limit.
	 *
	 * @return bool
	 */
	public function exceeded_hourly_limit() {
		$limited = get_noptin_option( 'per_hour', 0 );
		return ! empty( $limited ) && $this->emails_sent_this_hour() > (int) $limited;
	}

	/**
	 * Push to queue
	 *
	 * @param mixed $data Data.
	 *
	 * @return $this
	 */
	public function push_to_queue( $data ) {
		$this->data[] = apply_filters( 'noptin_bg_mailer_push_to_queue', $data );
		return $this;
	}

}
