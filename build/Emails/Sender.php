<?php
/**
 * Emails API: Sender.
 *
 * Sends an email.
 *
 * @since   1.7.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Emails;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sends an email.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Sender {

	/**
	 * @var array $args The arguments for sending emails.
	 */
	private static $args = array(); // Should be private and reset after each send

	/**
	 * Sends an email.
	 *
	 * A true return value does not automatically mean that the user received the
	 * email successfully. It just only means that the method used was able to
	 * process the request without any errors.
	 *
	 * @param array $args An array of arguments.
	 * @see noptin_send_email()
	 * @return bool Whether the email was sent successfully.
	 */
	public static function send( $args ) {

		// Validate recipients before processing
		if ( empty( $args['recipients'] ) ) {
			return false;
		}

		// Ensure recipients are valid emails
		$recipients       = wp_parse_list( $args['recipients'] );
		$valid_recipients = array_filter( $recipients, 'is_email' );

		if ( empty( $valid_recipients ) ) {
			log_noptin_message( 'No valid email addresses provided for sending.' );
			return false;
		}

		$args['recipients'] = $valid_recipients;

		if ( empty( $args['unsubscribe_url'] ) ) {
			$args['unsubscribe_url'] = \Hizzle\Noptin\Emails\Main::get_current_unsubscribe_url();
		}

		// Store previous args to restore later
		$previous_args = self::$args;

		self::$args = wp_parse_args(
			$args,
			array(
				// Whether or not we should disable template plugins.
				'disable_template_plugins' => true,
				// Email subject.
				'subject'                  => '',
				// Email content.
				'message'                  => '',
				// Additional headers.
				'headers'                  => array(),
				// Paths to files to attach.
				'attachments'              => array(),
				// Reply-to email address.
				'reply_to'                 => '',
				// Email address of the sender.
				'from_email'               => '',
				// Name of the sender.
				'from_name'                => '',
				// Content type of the email.
				'content_type'             => '',
				// URL to unsubscribe from further emails.
				'unsubscribe_url'          => '',
				// The current campaign ID.
				'campaign_id'              => 0,
			)
		);

		try {
			// Attach our own hooks.
			self::before_sending();

			// Prepare the sending function.
			$sending_function = apply_filters( 'noptin_email_sending_function', 'wp_mail', self::$args );

			// Send the actual email.
			$result = call_user_func(
				$sending_function,
				self::$args['recipients'],
				html_entity_decode( self::$args['subject'], ENT_QUOTES, get_bloginfo( 'charset' ) ),
				self::$args['message'],
				self::prepare_headers( self::$args['headers'] ),
				self::$args['attachments']
			);
		} catch ( \Exception $e ) {
			log_noptin_message(
				sprintf(
					'Exception while sending email to %s: %s',
					implode( ', ', self::$args['recipients'] ),
					$e->getMessage()
				)
			);
			$result = false;
		} finally {
			// Always remove hooks, even if an exception occurs
			self::after_sending();
		}

		// If the email was not sent, log the error.
		if ( empty( $result ) ) {
			log_noptin_message(
				sprintf(
					/* Translators: %1$s Email address, %2$s Email subject & error. */
					__( 'Failed sending an email to %1$s with the subject %2$s', 'newsletter-optin-box' ),
					implode( ', ', self::$args['recipients'] ),
					wp_specialchars_decode( self::$args['subject'] ) . '<code>' . esc_html( \Hizzle\Noptin\Emails\Main::get_phpmailer_last_error() ) . '</code>'
				)
			);
		}

		// Fetch the matching subscriber.
		if ( ! empty( self::$args['campaign_id'] ) ) {
			foreach ( self::$args['recipients'] as $recipient ) {
				$subscriber = noptin_get_subscriber( $recipient );

				if ( $subscriber->exists() ) {
					if ( empty( $result ) ) {
						$subscriber->record_activity(
							sprintf(
								/* Translators: %1$s Email address, %2$s Email subject & error. */
								__( 'Failed sending an email to %1$s with the subject %2$s', 'newsletter-optin-box' ),
								sanitize_email( $recipient ),
								wp_specialchars_decode( self::$args['subject'] ) . '<code>' . esc_html( \Hizzle\Noptin\Emails\Main::get_phpmailer_last_error() ) . '</code>'
							)
						);
					} else {
						$subscriber->record_sent_campaign( self::$args['campaign_id'] );
					}
				}

				if ( apply_filters( 'noptin_log_email_send', true, $result, self::$args ) ) {
					increment_noptin_campaign_stat( self::$args['campaign_id'], '_noptin_sends' );
					\Hizzle\Noptin\Emails\Logs\Main::create( 'send', self::$args['campaign_id'], $recipient, (int) $result );
				}
			}
		}

		self::$args = $previous_args; // Restore previous args

		// Return the result of the sending function.
		return $result;
	}

	/**
	 * Prepares email headers.
	 */
	public static function prepare_headers( $headers ) {

		if ( ! is_array( $headers ) ) {
			$headers = array();
		}

		$name     = sanitize_text_field( self::get_from_name() );
    	$reply_to = sanitize_email( self::get_reply_to() );
		$content  = self::get_content_type( 'text/plain' );

		if ( ! empty( $reply_to ) && ! empty( $name ) ) {
			$headers[] = "Reply-To:$name <$reply_to>";
		}

		$headers[] = "Content-Type:$content";

		if ( ! empty( self::$args['unsubscribe_url'] ) ) {
			$url       = add_query_arg( 'noptin-autosubmit', '1', self::$args['unsubscribe_url'] );
			$headers[] = "List-Unsubscribe:<$url>";
		}

		$headers = implode( "\r\n", $headers );
		return apply_filters( 'noptin_sender_email_headers', $headers, self::$args );
	}

	/**
	 * Add filters/actions before the email is sent.
	 *
	 * @since 1.7.0
	 */
	public static function before_sending() {
		add_filter( 'wp_mail_from', array( __CLASS__, 'get_from_email' ) );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( __CLASS__, 'get_content_type' ), 1000 );
		add_filter( 'wp_mail', array( __CLASS__, 'ensure_email_content' ), 1000000 );
		do_action( 'noptin_email_sender_before_sending', self::$args );
	}

	/**
	 * Get the email reply-to.
	 *
	 * @since 1.7.0
	 *
	 * @return string The email reply-to address.
	 */
	public static function get_reply_to() {

		// Check if a custom from email was set.
		if ( ! empty( self::$args['reply_to'] ) ) {
			return self::$args['reply_to'];
		}

		$reply_to = get_noptin_option( 'reply_to', get_option( 'admin_email' ) );

		if ( ! is_email( $reply_to ) ) {
			$reply_to = get_option( 'admin_email' );
		}

		return apply_filters( 'noptin_sender_reply_to', $reply_to, self::$args );
	}

	/**
	 * Get the "from" email address address.
	 *
	 * @since 1.7.0
	 *
	 * @return string The "from" email address address.
	 */
	public static function get_from_email( $email = '' ) {

		// Check if a custom from email was set.
		if ( ! empty( self::$args['from_email'] ) ) {
			$email = self::$args['from_email'];
		} else {

			// Only overide if there is an email set in the settings.
			$from_email = get_noptin_option( 'from_email' );

			if ( ! empty( $from_email ) && is_email( $from_email ) ) {
				$email = $from_email;
			}
		}

		// Fix for wordpress@localhost
		if ( empty( $email ) || ! is_email( $email ) ) {
			$email = get_option( 'admin_email' );
		}

		return apply_filters( 'noptin_sender_from_email', sanitize_email( $email ), self::$args );
	}

	/**
	 * Get the "from" name.
	 *
	 * @since 1.7.0
	 *
	 * @return string The "from" name.
	 */
	public static function get_from_name() {

		// Check if a custom from name was set.
		if ( ! empty( self::$args['from_name'] ) ) {
			$from_name = self::$args['from_name'];
		} else {

			// Retrieve from the settings.
			$from_name = get_noptin_option( 'from_name', get_option( 'blogname' ) );

			if ( empty( $from_name ) ) {
				$from_name = get_option( 'blogname' );
			}
		}

		$from_name = wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES );
		return apply_filters( 'noptin_sender_from_name', $from_name, self::$args );
	}

	/**
	 * Get the email content type.
	 *
	 * @since 1.7.0
	 *
	 * @return string The email content type.
	 */
	public static function get_content_type( $content_type = '' ) {

		// Abort if no custom content type is set.
		if ( empty( self::$args['content_type'] ) ) {
			return $content_type;
		}

		switch ( strtolower( self::$args['content_type'] ) ) {

			case 'html':
				return 'text/html';

			case 'multipart':
				return 'multipart/alternative';

			default:
				return 'text/plain';

		}
	}

	/**
	 * Ensures that our email messages are not messed up by template plugins.
	 *
	 * @since 1.7.0
	 *
	 * @return array wp_mail_data.
	 */
	public static function ensure_email_content( $args ) {

		if ( self::$args['disable_template_plugins'] ) {
			$args['message'] = self::$args['message'];
		}

		return $args;
	}

	/**
	 * Remove filters/actions after the email is sent.
	 *
	 * @since 1.7.0
	 */
	public static function after_sending() {
		remove_filter( 'wp_mail_from', array( __CLASS__, 'get_from_email' ) );
		remove_filter( 'wp_mail_from_name', array( __CLASS__, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( __CLASS__, 'get_content_type' ), 1000 );
		remove_filter( 'wp_mail', array( __CLASS__, 'ensure_email_content' ), 1000000 );
		do_action( 'noptin_email_sender_after_sending', self::$args );
	}
}
