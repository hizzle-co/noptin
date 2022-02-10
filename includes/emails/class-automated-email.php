<?php
/**
 * Email API: Automated Email.
 *
 * Contains the main automated email class
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Represents a single automated email.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_Automated_Email {

	/** @var bool */
	public $is_legacy = false;

	/** @var int */
	public $id = 0;

	/** @var string */
	public $status = 'draft'; // Or publish.

	/** @var string */
	public $created;

	/** @var string */
	public $name = ''; // Name of this automation.

	/** @var string */
	public $type; // Type of this automation.

	/** @var array */
	public $options = array();

	/**
	 * Class constructor.
	 *
	 * @param int|string|array $args
	 */
	public function __construct( $args ) {

		// Creating a new campaign.
		if ( is_string( $args ) && ! is_numeric( $args ) ) {
			$this->type   = $args;
			$this->status = 'publish';
			return;
		}

		// Loading a saved campaign.
		if ( is_numeric( $args ) ) {
			$post = get_post( $args );

			// Abort if the post does not exist.
			if ( empty( $post ) || 'noptin-campaign' !== $post->post_type || 'automation' !== get_post_meta( $post->ID, 'campaign_type', true ) ) {
				return;
			}

			// Prepare campaign data.
			$data = json_decode( $post->post_content, true );
			//noptin_dump( wp_unslash( $post->post_content ) ); exit;
			// Check if we're dealing with a legacy campaign.
			if ( ! is_array( $data ) ) {
				$this->is_legacy = true;
			} else {
				$this->options = wp_unslash( $data );
			}

			$this->id      = $post->ID;
			$this->status  = $post->post_status;
			$this->name    = $post->post_title;
			$this->created = $post->post_date;
			$this->type    = get_post_meta( $post->ID, 'automation_type', true );
		}

		// Data array.
		if ( is_array( $args ) ) {
			$this->type   = $args['automation_type'];
			$this->status = $args['status'];
			$this->name   = $args['title'];

			if ( ! empty( $args['id'] ) ) {
				$this->id = (int) $args['id'];
				unset( $args['id'] );
			}

			unset( $args['automation_type'], $args['status'], $args['title'] );
			$this->options = $args;
		}

	}

	/**
	 * Checks if the automated email exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return ! empty( $this->id );
	}

	/**
	 * Retrieves a given setting
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get( $key ) {

		// Fetch value.
		if ( 'name' === $key ) {
			$value = $this->name;
		} else if ( $this->is_legacy ) {
			$value = $this->exists() ? '' : get_post_meta( $this->id, $key, true );
		} else {
			$value = isset( $this->options[ $key ] ) ? $this->options[ $key ] : '';
		}

		// General filter.
		$value = apply_filters( 'noptin_get_automated_email_prop', $value, $key, $this );

		// Prop specific filtter.
		return apply_filters( "noptin_get_automated_email_$key", $value, $this );

	}

	/**
	 * Checks if the automated email is published.
	 *
	 * @return bool
	 */
	public function is_published() {

		$is_published = 'publish' === $this->status;
		return apply_filters( 'noptin_automation_is_published', $is_published, $this->status, $this );
	}

	/**
	 * Checks if the automated email is active.
	 *
	 * @return bool
	 */
	public function can_send() {

		$can_send = $this->is_published() && $this->exists();
		return apply_filters( 'noptin_automation_can_send', $can_send, $this );
	}

	/**
	 * Checks if this is a mass mail.
	 *
	 * @return bool
	 */
	public function is_mass_mail() {

		$is_mass_mail = in_array( $this->type, array( 'post_digest', 'post_notifications' ) );
		return apply_filters( 'noptin_automation_is_mass_mail', $is_mass_mail, $this->type, $this );
	}

	/**
	 * Checks if this email supports timing.
	 *
	 * @return bool
	 */
	public function supports_timing() {

		$supports_timing = ! in_array( $this->type, array( 'post_digest' ) );
		return apply_filters( 'noptin_automated_email_supports_timing', $supports_timing, $this->type, $this );
	}

	/**
	 * Returns the sender for this email.
	 *
	 * @return bool
	 */
	public function get_sender() {

		$sender = $this->get( 'email_sender' );
		$sender = in_array( $sender, array_keys( get_noptin_email_senders() ) ) ? $sender : 'noptin';
		return apply_filters( 'noptin_automated_email_sender', $sender, $this );
	}

	/**
	 * Returns the email type for this automated email.
	 *
	 * @return bool
	 */
	public function get_email_type() {

		// Abort if this is a legacy email type.
		if ( $this->is_legacy ) {
			return 'normal';
		}

		$email_type = $this->get( 'email_type' );
		return in_array( $email_type, array_keys( get_noptin_email_types() ) ) ? $email_type : 'normal';
	}

	/**
	 * Returns the email template for this automated email.
	 *
	 * @return bool
	 */
	public function get_template() {

		// Read from campaign options.
		if ( ! $this->is_legacy ) {
			$template = $this->get( 'template' );
		}

		// Read from settings.
		if ( empty( $template ) ) {
			$template = get_noptin_option( 'email_template',  'plain' );
		}

		// Default to the plain template.
		if ( empty( $template ) ) {
			$template = 'plain';
		}

		// Filter and return.
		return apply_filters( 'noptin_automated_email_template', $template, $this );

	}

	/**
	 * Returns the subject for this automation.
	 *
	 * @return string
	 */
	public function get_subject() {
		return $this->get( 'subject' );
	}

	/**
	 * Returns the recipients for this automation.
	 *
	 * @return string
	 */
	public function get_recipients() {

		// Abort for mass mail.
		if ( $this->is_mass_mail() ) {
			return '';
		}

		// Prepare recipient.
		$recipient = $this->is_legacy ? '' : $this->get( 'recipients' );

		// If no recipient, use the default recipient.
		if ( empty( $recipient ) ) {
			return apply_filters( "noptin_default_automated_email_{$this->type}_recipient", '', $this );
		}

		return $recipient;
	}

	/**
	 * Returns the placeholder for email recipients.
	 *
	 */
	public function get_placeholder_recipient() {
		$emails = apply_filters( "noptin_default_automated_email_{$this->type}_recipient", '', $this );
		$emails = trim( $emails . ', ' . get_option( 'admin_email' ) . ' --notracking' );
		$emails = trim( $emails, ',' );

		if ( empty( $emails ) ) {
			return '';
		}

		return sprintf( __( 'For example, %s', 'newsletter-optin-box' ), $emails );
	}

	/**
	 * Returns the content for this automation.
	 *
	 * @return string
	 */
	public function get_content( $email_type = 'normal' ) {

		// Abort if this is a legacy email type.
		if ( $this->is_legacy ) {

			if ( ! $this->exists() || $email_type !== 'normal' ) {
				return '';
			}

			$post = get_post( $this->id );
			return empty( $post ) ? '' : wp_unslash( $post->post_content );

		}

		return $this->get( 'content_' . $email_type );
	}

	/**
	 * Checks whether the campaign sends immediately.
	 *
	 * @return bool
	 */
	public function sends_immediately() {

		if ( 'immediately' === $this->get( 'when_to_run' ) ) {
			return true;
		}

		return 1 > $this->get_sends_after();
	}

	/**
	 * Returns the delay interval for this automated email.
	 *
	 * @return int
	 */
	public function get_sends_after() {

		if ( $this->is_legacy ) {
			return (int) get_post_meta( $this->id, 'noptin_sends_after', true );
		}

		return (int) $this->get( 'sends_after' );
	}

	/**
	 * Returns the delay unit for this automated email.
	 *
	 * @param bool $label
	 * @return string
	 */
	public function get_sends_after_unit( $label = false ) {

		$units = get_noptin_email_delay_units();

		if ( $this->is_legacy ) {
			$unit = get_post_meta( $this->id, 'noptin_sends_after_unit', true );
		} else {
			$unit = $this->get( 'sends_after_unit' );
		}

		if ( empty( $unit ) || ! isset( $units[ $unit ] ) ) {
			$unit = 'hours';
		}

		return $label ? $units[ $unit ] : $unit;
	}

	/**
	 * Sends a test email
	 *
	 * @param string $recipient
	 * @return bool|WP_Error
	 */
	public function send_test( $recipient ) {

		// Ensure we have a subject.
		if ( empty( $this->options['subject'] ) ) {
			return new WP_Error( 'missing_subject', __( 'You need to provide a subject for your email.', 'newsletter-optin-box' ) );
		}

		// Ensure we have content.
		$content = $this->get_content( $this->get_email_type() );
		if ( empty( $content ) ) {
			return new WP_Error( 'missing_content', __( 'The email body cannot be empty.', 'newsletter-optin-box' ) );
		}

		// Is the email type supported?
		if ( ! isset( noptin()->emails->automated_email_types->types[ $this->type ] ) ) {
			return new WP_Error( 'unsupported_automation_type', __( 'Invalid or unsupported automation type.', 'newsletter-optin-box' ) );
		}

		// Try sending the test email.
		try {
			return noptin()->emails->automated_email_types->types[ $this->type ]->send_test( $this, $recipient );
		} catch ( Exception $e ) {
			return new WP_Error( 'exception', $e->getMessage() );
		}

	}

	/**
	 * Saves the automated email.
	 *
	 * @return bool|WP_Error
	 */
	public function save() {

		// Prepare post args.
		$args = array(
			'post_type'    => 'noptin-campaign',
			'post_title'   => $this->name,
			'post_status'  => $this->status,
			'post_content' => wp_json_encode( $this->options ),
			'meta_input'   => array(
				'automation_type' => $this->type,
				'campaign_type'   => 'automation',
			)
		);

		// Slash data.
		// WP expects all data to be slashed and will unslash it (fixes '\' character issues).
		$args = wp_slash( $args );

		// Only remove taggeted link rel if it was hooked.
		$has_filter = false !== has_filter( 'content_save_pre', 'wp_targeted_link_rel' );

		if ( $has_filter ) {
			wp_remove_targeted_link_rel_filters();
		}

		// Update the email if it exists.
		if ( $this->exists() ) {
			$args['ID'] = $this->id;
			return wp_update_post( $args, true );

			if ( $has_filter ) {
				wp_init_targeted_link_rel_filters();
			}

		}

		// Create a new automated email.
		$result = wp_insert_post( $args, true );

		if ( $has_filter ) {
			wp_init_targeted_link_rel_filters();
		}

		if ( is_int( $result ) ) {
			$this->id = $result;
			return true;
		}

		return $result;
	}

}