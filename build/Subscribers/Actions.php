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
		noptin()->actions_page->add_confirmable_action(
			'unsubscribe',
			__( 'Unsubscribe', 'newsletter-optin-box' )
		);

		// User resubscribe.
		add_action( 'noptin_actions_handle_resubscribe', array( __CLASS__, 'resubscribe_user' ) );
		noptin()->actions_page->add_confirmable_action(
			'resubscribe',
			__( 'Resubscribe', 'newsletter-optin-box' )
		);

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
			// This will create a new unsubscribed subscriber.
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

			if ( ! empty( \Hizzle\Noptin\Emails\Main::$current_email_recipient['email'] ) ) {
				\Hizzle\Noptin\Emails\Logs\Main::create(
					'resubscribe',
					\Hizzle\Noptin\Emails\Main::$current_email->id,
					\Hizzle\Noptin\Emails\Main::$current_email_recipient['email']
				);
			}
		}
	}

	/**
	 * Handles the confirm action.
	 *
	 * @since 3.0.0
	 */
	public static function handle_confirm( $page = null ) {
		if ( ! $page || ! method_exists( $page, 'get_request_value' ) ) {
			return;
		}

		// Identify the subscriber from the confirmation link itself. The actions
		// page falls back to the current visitor when the link has no valid value.
		$value      = trim( $page->get_request_value(), '/' );
		$recipient  = json_decode( noptin_decrypt( $value ), true );
		$subscriber = null;

		if ( is_array( $recipient ) && ! empty( $recipient['email'] ) && is_email( $recipient['email'] ) ) {
			$subscriber = noptin_get_subscriber( $recipient['email'] );
		} elseif ( ! empty( $value ) ) {
			// Continue to support older confirmation links containing a subscriber key.
			$subscriber_id = get_noptin_subscriber_id_by_confirm_key( $value );
			$subscriber    = $subscriber_id ? noptin_get_subscriber( $subscriber_id ) : null;
		}

		if ( ! $subscriber || ! $subscriber->exists() ) {
			return;
		}

		confirm_noptin_subscriber_email( $subscriber );

		// Check the saved record before granting this browser the subscriber key.
		$subscriber = noptin_get_subscriber( $subscriber->get_id() );
		if ( ! $subscriber->exists() || ! $subscriber->get_confirmed() || ! $subscriber->is_active() ) {
			return;
		}

		if ( headers_sent() || apply_filters( 'noptin_disable_cookies', false ) ) {
			return;
		}

		setcookie( 'noptin_email_subscribed', $subscriber->get_confirm_key(), time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );

		$cookie = get_noptin_option( 'subscribers_cookie' );
		if ( ! empty( $cookie ) && is_string( $cookie ) ) {
			setcookie( $cookie, '1', time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
		}
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
