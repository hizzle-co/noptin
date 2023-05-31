<?php
/**
 * Emails API: Sender.
 *
 * Sends an email.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sends an email.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_Email_Sender {

	/**
	 * True when email is being sent.
	 *
	 * @var bool
	 */
	public $sending = false;

	/**
	 * Whether or not we should disable template plugins.
	 */
	public $disable_template_plugins = true;

	/**
	 * Array or comma-separated list of email recipients.
	 *
	 * @var string|string[]
	 */
	public $recipients = '';

	/**
	 * Email subject.
	 *
	 * @var string
	 */
	public $subject = '';

	/**
	 * Email content.
	 *
	 * @var string
	 */
	public $message = '';

	/**
	 * Additional headers.
	 *
	 * @var string[]
	 */
	public $headers = array();

	/**
	 * Paths to files to attach.
	 *
	 * @var string[]
	 */
	public $attachments = array();

	/**
	 * Reply-to email address.
	 *
	 * @var string
	 */
	public $reply_to;

	/**
	 * Email address of the sender.
	 *
	 * @var string
	 */
	public $from_email;

	/**
	 * Name of the sender.
	 *
	 * @var string
	 */
	public $from_name;

	/**
	 * Content type of the email.
	 *
	 * @var string
	 */
	public $content_type = '';

	/**
	 * URL to unsubscribe from further emails.
	 *
	 * @var string
	 */
	public $unsubscribe_url = '';

	/**
	 * Register relevant hooks.
	 */
	public function add_hooks() {
		add_action( 'noptin_send_bg_email', array( $this, 'handle_background_send' ) );
	}

	/**
	 * Sends a background email.
	 */
	public function handle_background_send( $key ) {
		$data = get_transient( $key );
		delete_transient( $key );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return;
		}

		call_user_func( array( $this, 'send' ), wp_unslash( $data ) );
	}

	/**
	 * Sends an email in the background.
	 *
	 * @param array $args An array of arguments, with keys similar to properties of this class.
	 * @see noptin_send_email()
	 * @return void
	 */
	public function bg_send( $args ) {

		$key  = 'noptin_send_' . md5( wp_json_encode( $args ) );
		set_transient( $key, $args );

		if ( ! empty( $args['delay'] ) ) {
			schedule_noptin_background_action( strtotime( $args['delay'] ), 'noptin_send_bg_email', $key );
		} else {
			do_noptin_background_action( 'noptin_send_bg_email', $key );
		}

	}

	/**
	 * Sends an email.
	 *
	 * A true return value does not automatically mean that the user received the
	 * email successfully. It just only means that the method used was able to
	 * process the request without any errors.
	 *
	 * @param array $args An array of arguments, with keys similar to properties of this class.
	 * @see noptin_send_email()
	 * @return bool Whether the email was sent successfully.
	 */
	public function send( $args ) {

		// Indicate that we're sending an email.
		$this->sending = true;

		// Prepare args.
		foreach ( $args as $key => $value ) {
			$this->$key = $value;
		}

		// Fires before an email is sent.
		do_action( 'noptin_before_sending_email', $this );

		// Attach our own hooks.
		$this->before_sending();

		// Prepare the sending function.
		$sending_function = apply_filters( 'noptin_email_sending_function', 'wp_mail', $this );

		// Send the actual email.
		$result = call_user_func(
			$sending_function,
			$this->recipients,
			html_entity_decode( $this->subject, ENT_QUOTES, get_bloginfo( 'charset' ) ),
			$this->message,
			$this->prepare_headers( $this->headers ),
			$this->attachments
		);

		// If the email was not sent, log the error.
		if ( empty( $result ) ) {
			log_noptin_message(
				sprintf(
					/* Translators: %1$s Email address, %2$s Email subject & error. */
					__( 'Failed sending an email to %1$s with the subject %2$s', 'newsletter-optin-box' ),
					is_array( $this->recipients ) ? implode( ', ', array_map( 'sanitize_email', $this->recipients ) ) : sanitize_email( $this->recipients ),
					wp_specialchars_decode( $this->subject ) . '<code>' . esc_html( $this->get_phpmailer_last_error() ) . '</code>'
				)
			);
		}

		// Fetch the matching subscriber.
		if ( ! empty( $args['campaign_id'] ) ) {
			foreach ( wp_parse_list( $this->recipients ) as $recipient ) {
				$subscriber = noptin_get_subscriber( $recipient );

				if ( $subscriber->exists() ) {
					$subscriber->record_sent_campaign( $args['campaign_id'] );
				}
			}
		}

		// Remove our hooks.
		$this->after_sending();

		// Hooks after an email is sent.
		do_action( 'noptin_after_sending_email', $this );

		// Reset class properties.
		$this->reset();

		// Return the result.
		return $result;
	}

	/**
	 * Prepares email headers.
	 */
	public function prepare_headers( $headers ) {

		if ( ! is_array( $headers ) ) {
			$headers = array();
		}

		$name     = $this->get_from_name();
		$reply_to = $this->get_reply_to();
		$content  = $this->get_content_type( 'text/plain' );
		$headers  = array();

		if ( ! empty( $reply_to ) && ! empty( $name ) ) {
			$headers = array( "Reply-To:$name <$reply_to>" );
		}

		$headers[]  = "Content-Type:$content";

		if ( ! empty( $this->unsubscribe_url ) ) {
			$url       = $this->unsubscribe_url;
			$headers[] = "List-Unsubscribe:<$url>";
		}

		$headers = implode( "\r\n", $headers );
		return apply_filters( 'noptin_sender_email_headers', $headers, $this );

	}

	/**
	 * Add filters/actions before the email is sent.
	 *
	 * @since 1.7.0
	 */
	public function before_sending() {

		add_filter( 'wp_mail_from', array( $this, 'get_from_email' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ), 1000 );
		add_filter( 'wp_mail', array( $this, 'ensure_email_content' ), 1000000 );

	}

	/**
	 * Get the email reply-to.
	 *
	 * @since 1.7.0
	 *
	 * @return string The email reply-to address.
	 */
	public function get_reply_to() {

		// Check if a custom from email was set.
		if ( ! empty( $this->reply_to ) ) {
			return $this->reply_to;
		}

		$reply_to = get_noptin_option( 'reply_to', get_option( 'admin_email' ) );

		if ( ! is_email( $reply_to ) ) {
			$reply_to = get_option( 'admin_email' );
		}

		return apply_filters( 'noptin_sender_reply_to', $reply_to, $this );
	}

	/**
	 * Get the "from" email address address.
	 *
	 * @since 1.7.0
	 *
	 * @return string The "from" email address address.
	 */
	public function get_from_email( $email = '' ) {

		// Check if a custom from email was set.
		if ( ! empty( $this->from_email ) ) {
			$email = $this->from_email;
		} else {

			// Only overide if there is an email set in the settings.
			$from_email = get_noptin_option( 'from_email' );

			if ( is_email( $from_email ) ) {
				$email = $from_email;
			}
		}

		// Fix for wordpress@localhost
		if ( ! is_email( $email ) ) {
			$email = get_option( 'admin_email' );
		}

		return apply_filters( 'noptin_sender_from_email', sanitize_email( $email ), $this );

	}

	/**
	 * Get the "from" name.
	 *
	 * @since 1.7.0
	 *
	 * @return string The "from" name.
	 */
	public function get_from_name() {

		// Check if a custom from name was set.
		if ( ! empty( $this->from_name ) ) {
			$from_name = $this->from_name;
		} else {

			// Retrieve from the settings.
			$from_name = get_noptin_option( 'from_name', get_option( 'blogname' ) );

			if ( empty( $from_name ) ) {
				$from_name = get_option( 'blogname' );
			}
		}

		$from_name = wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES );
		return apply_filters( 'noptin_sender_from_name', $from_name, $this );
	}

	/**
	 * Get the email content type.
	 *
	 * @since 1.7.0
	 *
	 * @return string The email content type.
	 */
	public function get_content_type( $content_type = '' ) {

		// Abort if no custom content type is set.
		if ( empty( $this->content_type ) ) {
			return $content_type;
		}

		switch ( strtolower( $this->content_type ) ) {

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
	public function ensure_email_content( $args ) {

		if ( $this->disable_template_plugins ) {
			$args['message'] = $this->message;
		}

		return $args;
	}

	/**
	 * Remove filters/actions after the email is sent.
	 *
	 * @since 1.7.0
	 */
	public function after_sending() {
		remove_filter( 'wp_mail_from', array( $this, 'get_from_email' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ), 1000 );
		remove_filter( 'wp_mail', array( $this, 'ensure_email_content' ), 1000000 );
	}

	/**
	 * Gets the most recent PHPMailer error message.
	 *
	 * @since 1.7.0
	 *
	 * @return string
	 */
	protected function get_phpmailer_last_error() {
		global $phpmailer;

		/** @var PHPMailer\PHPMailer\PHPMailer $phpmailer */
		if ( $phpmailer && ! empty( $phpmailer->ErrorInfo ) ) {
			return $phpmailer->ErrorInfo;
		}

		return __( 'The mail function returned false.', 'newsletter-optin-box' );
	}

	/**
	 * Resets the class properties.
	 *
	 * @since 1.7.0
	 *
	 * @return string
	 */
	public function reset() {
		$this->sending                  = false;
		$this->recipients               = '';
		$this->subject                  = '';
		$this->message                  = '';
		$this->reply_to                 = '';
		$this->from_email               = '';
		$this->from_name                = '';
		$this->content_type             = '';
		$this->unsubscribe_url          = '';
		$this->headers                  = array();
		$this->attachments              = array();
		$this->disable_template_plugins = true;
	}

}
