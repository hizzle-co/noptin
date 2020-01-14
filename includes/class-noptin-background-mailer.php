<?php
/**
 * class Noptin_Background_Mailer class.
 *
 * @extends Noptin_Background_Process
 */

defined( 'ABSPATH' ) || exit;

class Noptin_Background_Mailer extends Noptin_Background_Process {

	/**
	 * @var string
	 */
	protected $action  = 'noptin_bg_mailer';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param array $item
	 *
	 * @return mixed
	 */
	public function task( $item ) {
		global $wpdb;

		// First, prepare the campaign data
		$item 		    = $this->prepare_campaign_data( $item );

		// And abort in case of an error
		if( empty( $item ) ) {
			return false;
		}

		$recipient_data = $item['next_recipient_data'];
		unset( $item['next_recipient_data'] );

		// If no email is set, move on to the next recipient
		if( empty( $recipient_data['email'] ) ) {
			return $item;
		}

		// Ensure that this subscriber is yet to be sent this campaign
		$key = '_campaign_' . $item['key'];
		if( isset( $recipient_data['merge_tags'][$key] ) ) {
			return $item;
		}


		// Try sending the email
		$mailer   = new Noptin_Mailer();
		$email    = $mailer->get_email( $recipient_data );
		$subject  = $mailer->get_subject( $recipient_data );

		if( $mailer->send( $recipient_data['email'], $subject, $email ) ) {

			if(! empty( $item['campaign_id'] ) ) {

				$sents = (int) get_post_meta( $item['campaign_id'], '_noptin_sends', true );
				update_post_meta( $item['campaign_id'], '_noptin_sends', $sents + 1 );

			}

			if(! empty( $recipient_data['merge_tags']['id'] ) ) {
				update_noptin_subscriber_meta( $recipient_data['merge_tags']['id'], $key, '1' );
			}



		} else {

			if(! empty( $item['campaign_id'] ) ) {

				$fails = (int) get_post_meta( $item['campaign_id'], '_noptin_fails', true );
				update_post_meta( $item['campaign_id'], '_noptin_fails', $fails + 1 );

			}

			if(! empty( $recipient_data['merge_tags']['id'] ) ) {
				update_noptin_subscriber_meta( $recipient_data['merge_tags']['id'], $key, '0' );
			}

		}

		return $item;

	}

	/**
	 * Prepares campaign data
	 */
	public function prepare_campaign_data( $item ) {

		// A unique key for this campaign
		$key = time() . wp_generate_password( 6, false );

		// If this is a normal campaign, ensure it is published
		if( isset( $item['campaign_id'] ) ) {

			// Set the unique key to the campaign id
			$key = $item['campaign_id'];

			// Fetch the post and ensure it is a published campaign
			$post        = get_post( $item['campaign_id'] );
			if( 'noptin-campaign' != $post->post_type || 'publish' != $post->post_status ) {
				return false;
			}

		}

		if( empty( $item['key'] ) ) {
			$item['key'] = $key;
		}

		// Fetch the next recipient of this campaign
		$next_recipient = false;

		// User can set recipients to be an array of subcriber ids or emails...
		if( isset( $item['recipients'] ) ) {
			$next_recipient = array_shift(  $item['recipients']  );
		}

		// Or a WHERE query to be executed on the subscribers table
		if( empty( $next_recipient ) && isset( $item['subscribers_query'] ) ) {
			$next_recipient = $this->fetch_subscriber_from_query( $item );
		}

		// If there is no other subscriber... abort
		if( empty( $next_recipient ) ) {

			if(! empty( $item['campaign_id'] ) ) {
				update_post_meta( $item['campaign_id'], 'completed', 1 );
			}

			do_action( 'noptin_background_mailer_complete', $item );

			return false;
		}

		$item['current_recipient'] = $next_recipient;

		// Recipient data
		$item['next_recipient_data'] = array();
		if( isset( $item['campaign_data'] ) ) {
			$item['next_recipient_data'] = $item['campaign_data'];
		}

		// If this is a campaign, fetch the email body and subject
		if( isset( $item['campaign_id'] ) && empty( $item['next_recipient_data']['email_body'] ) ) {

			// Email body is the post content
			$item['next_recipient_data']['email_body'] = $post->post_content;

			// Email subject is the post title for newsletters and post_meta for campaign automations
			if( 'newsletter' == get_post_meta( $post->ID, 'campaign_type', true ) ) {
				$item['next_recipient_data']['email_subject'] = $post->post_title;
			} else {
				$item['next_recipient_data']['email_subject'] = get_post_meta( $post->ID, 'email_subject', true );
			}

			// Finally, fetch the preview text
			$item['next_recipient_data']['preview_text'] = get_post_meta( $post->ID, 'preview_text', true );

		}

		// Maybe fetch a subscriber from an email...
		if( is_email( $next_recipient ) ) {
			$item['next_recipient_data']['email'] = $next_recipient;
			$subscriber = get_noptin_subscriber_by_email( $next_recipient );
		}

		// Or a subscriber id
		if( is_numeric( $next_recipient ) ) {
			$subscriber = get_noptin_subscriber( $next_recipient );
		}

		// Prepare the email merge tags
		if( empty( $item['next_recipient_data']['merge_tags'] ) ) {
			$item['next_recipient_data']['merge_tags'] = array();
		}

		// Add the subscriber details as merge tags
		if(! empty( $subscriber ) ) {

			$item['next_recipient_data']['email']         = $subscriber->email;
			$item['next_recipient_data']['subscriber_id'] = $subscriber->id;

			$item['next_recipient_data']['merge_tags'] = array_replace( $item['next_recipient_data']['merge_tags'], (array) $subscriber );

			$item['next_recipient_data']['merge_tags']['unsubscribe_url'] = get_noptin_action_url( 'unsubscribe', $subscriber->confirm_key );

			$meta = get_noptin_subscriber_meta( $subscriber->id );
			foreach( $meta as $key=>$values ) {

				if( isset( $values[0] ) && is_string( $values[0] ) ) {
					$item['next_recipient_data']['merge_tags'][$key] = esc_html( $values[0] );
				}

			}
		}

		return $item;
	}

	/**
	 * Fetches a subscriber from a subscribers query
	 */
	public function fetch_subscriber_from_query( $item ) {
		global $wpdb;

		// Avoid sending the same campaign twice
		$meta_query = new WP_Meta_Query( array(
			'relation' => 'AND',
			array(
				'key'     => '_campaign_' . $item['key'],
				'compare' => 'NOT EXISTS',
			),
		));

		// Subscribers' table
		$table  = get_noptin_subscribers_table_name();

		// Retrieve join and where clauses
		$clauses = $meta_query->get_sql( 'noptin_subscriber', $table, 'id' );

		$query = "SELECT $table.id FROM $table {$clauses['join']} WHERE {$item['subscribers_query']} AND $table.active = 0 {$clauses['where']} LIMIT 1";

		return $wpdb->get_var( $query );

	}

}