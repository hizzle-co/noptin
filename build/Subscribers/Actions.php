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
		add_action( 'noptin_actions_handle_unsubscribe', array( __CLASS__, 'unsubscribe_user' ) );

		// User resubscribe.
		add_action( 'noptin_actions_handle_resubscribe', array( __CLASS__, 'resubscribe_user' ) );

		// User confirm.
		add_action( 'noptin_actions_handle_confirm', array( __CLASS__, 'handle_confirm' ) );

		// User manage preferences.
		add_action( 'noptin_page_manage_preferences', array( __CLASS__, 'display_manage_preferences_form' ) );
	}

	/**
	 * Retrieves the subscriber.
	 *
	 */
	public static function get_subscriber() {
		if ( is_array( \Hizzle\Noptin\Emails\Main::$current_email_recipient ) ) {
			$recipient = \Hizzle\Noptin\Emails\Main::$current_email_recipient;

			if ( ! empty( $recipient['subscriber'] ) || ! empty( $recipient['email'] ) ) {
				$subscriber = noptin_get_subscriber( $recipient['subscriber'] ?? $recipient['email'] );

				if ( $subscriber->exists() ) {
					return $subscriber;
				}
			}
		}

		return null;
	}

	/**
	 * Unsubscribes a user
	 *
	 * @since 3.0.0
	 */
	public static function unsubscribe_user() {

		$recipient = \Hizzle\Noptin\Emails\Main::$current_email_recipient;

		// Fetch the campaign id.
		$campaign    = \Hizzle\Noptin\Emails\Main::$current_email;
		$campaign_id = $campaign ? $campaign->id : 0;

		// Fetch the subscriber.
		$subscriber = self::get_subscriber();
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
	 * @since 3.0.0
	 */
	public static function resubscribe_user() {

		// Fetch the subscriber.
		$subscriber = self::get_subscriber();

		// Abort if the subscriber is already subscribed or does not exist.
		if ( ! $subscriber || ! $subscriber->exists() || $subscriber->is_active() ) {
			return;
		}

		// Resubscribe the subscriber.
		$subscriber->set_status( 'subscribed' );
		$subscriber->save();

		// Process campaigns.
		if ( ! empty( \Hizzle\Noptin\Emails\Main::$current_email ) ) {
			decrease_noptin_campaign_stat( \Hizzle\Noptin\Emails\Main::$current_email->id, '_noptin_unsubscribed' );
		}
	}

	/**
	 * Handles the confirm action.
	 *
	 * @since 3.0.0
	 */
	public static function handle_confirm() {

		// Confirm the subscriber.
		confirm_noptin_subscriber_email( self::get_subscriber() );
	}

	/**
	 * Displays the manage preferences form.
	 *
	 * @since 3.0.0
	 */
	public static function display_manage_preferences_form() {
		printf(
			'<h1>%s</h1>',
			esc_html( get_bloginfo( 'name' ) )
		);

		Manage_Preferences::display_form();
	}
}
