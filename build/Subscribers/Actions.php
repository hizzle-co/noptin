<?php

namespace Hizzle\Noptin\Subscribers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles subscriber actions.
 *
 * @since 3.0.0
 */
class Actions {

	/**
	 * Initializes the actions.
	 *
	 * @since 3.0.0
	 */
	public static function init() {

		// User unsubscribe.
		add_action( 'noptin_actions_handle_unsubscribe', array( __CLASS__, 'unsubscribe_user' ), 10, 3 );

		// User resubscribe.
		add_action( 'noptin_actions_handle_resubscribe', array( __CLASS__, 'resubscribe_user' ), 10, 3 );
	}

	/**
	 * Retrieves the subscriber.
	 *
	 * @param array $recipient The recipient.
	 */
	public static function get_subscriber( $recipient ) {
		if ( ! empty( $recipient['sid'] ) ) {
			return noptin_get_subscriber( $recipient['sid'] );
		}

		if ( ! empty( $recipient['subscriber_id'] ) ) {
			return noptin_get_subscriber( $recipient['subscriber_id'] );
		}

		if ( ! empty( $recipient['email'] ) ) {
			return noptin_get_subscriber( $recipient['email'] );
		}

		if ( ! empty( $recipient['uid'] ) ) {
			$user = get_user_by( 'id', $recipient['uid'] );
			if ( $user ) {
				return noptin_get_subscriber( $user->user_email );
			}
		}

		return null;
	}

	/**
	 * Unsubscribes a user
	 *
	 * @param array $recipient The recipient.
	 * @param \Noptin_Page $handler The handler.
	 * @since 3.0.0
	 */
	public static function unsubscribe_user( $recipient, $handler ) {

		// Prevent accidental unsubscribes.
		$handler->maybe_autosubmit_form();

		// Fetch the campaign id.
		$campaign_id = ! empty( $recipient['cid'] ) ? $recipient['cid'] : 0;

		// Fetch the subscriber.
		$subscriber = self::get_subscriber( $recipient );
		if ( ! $subscriber || ! $subscriber->exists() ) {
			if ( ! empty( $recipient['email'] ) ) {
				unsubscribe_noptin_subscriber( $recipient['email'], $campaign_id );
			}
		} else {
			// Abort if the subscriber is already unsubscribed.
			if ( 'unsubscribed' === $subscriber->get_status() ) {
				return;
			}

			// Unsubscribe the subscriber.
			unsubscribe_noptin_subscriber( $subscriber, $campaign_id );
		}

		// Process campaigns.
		if ( ! empty( $campaign_id ) ) {
			increment_noptin_campaign_stat( $campaign_id, '_noptin_unsubscribed' );
		}
	}

	/**
	 * Resubscribes a user
	 *
	 * @param array $recipient The recipient.
	 * @param \Noptin_Page $handler The handler.
	 * @since 3.0.0
	 */
	public static function resubscribe_user( $recipient, $handler ) {
		// Prevent accidental resubscribes.
		$handler->maybe_autosubmit_form();

		// Fetch the subscriber.
		$subscriber = self::get_subscriber( $recipient );

		// Abort if the subscriber is already subscribed or does not exist.
		if ( ! $subscriber || ! $subscriber->exists() || $subscriber->is_active() ) {
			return;
		}

		// Resubscribe the subscriber.
		$subscriber->set_status( 'subscribed' );
		$subscriber->save();

		// Fetch the campaign id.
		$campaign_id = ! empty( $recipient['cid'] ) ? $recipient['cid'] : 0;

		// Process campaigns.
		if ( ! empty( $campaign_id ) ) {
			decrease_noptin_campaign_stat( $campaign_id, '_noptin_unsubscribed' );
		}
	}
}
