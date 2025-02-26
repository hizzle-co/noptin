<?php

namespace Hizzle\Noptin\Emails;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles email actions.
 *
 * @since 3.0.0
 */
class Actions {

	/**
	 * Initializes the actions.
	 *
	 * @param array $recipient The recipient.
	 * @since 3.0.0
	 */
	public static function init( $recipient ) {

		// Set the current email recipient.
		self::set_current_email_recipient( $recipient );

		// Email open.
		add_action( 'noptin_actions_handle_email_open', array( __CLASS__, 'handle_email_open' ) );
		add_filter( 'noptin_auto_submit_actions_page_for_email_open', '__return_false' );

		// Email click.
		add_action( 'noptin_actions_handle_email_click', array( __CLASS__, 'handle_email_click' ) );
		add_filter( 'noptin_auto_submit_actions_page_for_email_click', '__return_false' );
	}

	/**
	 * Sets the current email recipient.
	 *
	 * @param array $recipient The recipient.
	 * @since 3.0.0
	 */
	public static function set_current_email_recipient( $recipient ) {
		$campaign = $recipient['cid'] ?? 0;

		// Set the campaign.
		if ( ! empty( $campaign ) ) {
			$campaign = new Email( $campaign );

			if ( ! $campaign->exists() ) {
				$campaign = null;
			}
		} else {
			$campaign = null;
		}

		// Set the subscriber.
		if ( isset( $recipient['email'] ) && empty( $recipient['subscriber'] ) && function_exists( 'get_noptin_subscriber_id_by_email' ) ) {
			$subscriber = get_noptin_subscriber_id_by_email( $recipient['email'] );

			if ( $subscriber ) {
				$recipient['subscriber'] = $subscriber;
			}
		}

		// Set the current email recipient.
		Main::init_current_email_recipient( $recipient, $campaign );

		// Set the current email.
		Main::$current_email = $campaign;

		// Register the temporary merge tags.
		do_action( 'noptin_register_temporary_merge_tags' );
	}

	/**
	 * Handles the email open action.
	 *
	 * @since 3.0.0
	 */
	public static function handle_email_open() {
		// Log the action.
		$email = Main::$current_email;
		if ( $email ) {
			$recipient = Main::$current_email_recipient;

			if ( is_array( $recipient ) ) {
				if ( ! empty( $recipient['subscriber'] ) ) {
					log_noptin_subscriber_campaign_open( $recipient['subscriber'], $email->id );
				} elseif ( ! empty( $recipient['email'] ) ) {
					$opens = get_post_meta( $email->id, '_noptin_opens_emails', true );

					if ( ! is_array( $opens ) ) {
						$opens = array();
					}

					if ( ! in_array( $recipient['email'], $opens, true ) ) {
						$opens[] = $recipient['email'];
						update_post_meta( $email->id, '_noptin_opens_emails', $opens );
						increment_noptin_campaign_stat( $email->id, '_noptin_opens' );
					}
				}
			}
		}

		// Display 1x1 pixel transparent gif.
		nocache_headers();
		header( 'Content-type: image/gif' );
		header( 'Content-Length: 42' );
		echo esc_html( base64_decode( 'R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEA' ) );
		exit;
	}

	/**
	 * Logs email clicks
	 *
	 * @access      public
	 * @since       1.2.0
	 * @return      array
	 */
	public static function handle_email_click() {

		// Fetch recipient.
		$recipient = Main::$current_email_recipient;

		// Add cookie.
		if ( ! empty( Main::$current_email ) ) {
			Revenue::record_email_click( Main::$current_email->id );
		}

		// Abort if no destination.
		if ( ! is_array( $recipient ) || empty( $recipient['to'] ) || '#' === $recipient['to'] ) {
			wp_safe_redirect( get_home_url() );
			exit;
		}

		$destination = str_replace( array( '#038;', '&#38;', '&amp;' ), '&', rawurldecode( $recipient['to'] ) );

		// Ensure we have a campaign.
		if ( ! empty( Main::$current_email ) ) {
			if ( is_array( $recipient ) ) {
				if ( ! empty( $recipient['subscriber'] ) ) {
					log_noptin_subscriber_campaign_click( $recipient['subscriber'], Main::$current_email->id, $destination );
				} elseif ( ! empty( $recipient['email'] ) ) {
					$clicks = get_post_meta( Main::$current_email->id, '_noptin_clicks_emails', true );

					if ( ! is_array( $clicks ) ) {
						$clicks = array();
					}

					if ( ! in_array( $recipient['email'], $clicks, true ) ) {
						$clicks[] = $recipient['email'];
						update_post_meta( Main::$current_email->id, '_noptin_clicks_emails', $clicks );
						increment_noptin_campaign_stat( Main::$current_email->id, '_noptin_clicks' );
					}
				}
			}
		}

		wp_redirect( $destination );
		exit;
	}
}
