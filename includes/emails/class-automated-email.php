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

			// Fetch campaign data.
			$data = get_post_meta( $post->ID, 'campaign_data', true );

			// Check if we're dealing with a legacy campaign.
			if ( ! is_array( $data ) ) {
				$this->is_legacy = true;
			} else {
				$this->options = $data;
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
	 * Magic getter
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	 * Retrieves a given setting
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get( $key ) {

		if ( 'ID' === $key ) {
			$key = 'id';
		}

		// Fetch value.
		if ( isset( $this->$key ) ) {
			$value = $this->$key;
		} elseif ( $this->is_legacy ) {
			$value = $this->exists() ? '' : get_post_meta( $this->id, $key, true );
		} else {
			$value = isset( $this->options[ $key ] ) ? $this->options[ $key ] : '';
		}

		// General filter.
		$value = apply_filters( 'noptin_get_email_prop', $value, $key, $this );

		// Prop specific filtter.
		return apply_filters( "noptin_get_email_prop_$key", $value, $this );

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
	 * Checks if this is an automation rule email.
	 *
	 * @return bool
	 */
	public function is_automation_rule() {
		return 0 === strpos( $this->type, 'automation_rule_' );
	}

	/**
	 * Returns the trigger.
	 *
	 * @return bool|string
	 */
	public function get_trigger() {
		return $this->is_automation_rule() ? substr( $this->type, 16 ) : false;
	}

	/**
	 * Checks if this is a mass mail.
	 *
	 * @return bool
	 */
	public function is_mass_mail() {

		$is_mass_mail = in_array( $this->type, array( 'post_digest', 'post_notifications' ), true );
		return apply_filters( 'noptin_automation_is_mass_mail', $is_mass_mail, $this->type, $this );
	}

	/**
	 * Checks if this email supports timing.
	 *
	 * @return bool
	 */
	public function supports_timing() {

		$supports_timing = ! in_array( $this->type, array( 'post_digest' ), true );
		return apply_filters( 'noptin_automated_email_supports_timing', $supports_timing, $this->type, $this );
	}

	/**
	 * Returns the sender for this email.
	 *
	 * @return bool
	 */
	public function get_sender() {

		$sender = $this->get( 'email_sender' );
		$sender = in_array( $sender, array_keys( get_noptin_email_senders() ), true ) ? $sender : 'noptin';
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
		return in_array( $email_type, array_keys( get_noptin_email_types() ), true ) ? $email_type : 'normal';
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
			$template = get_noptin_option( 'email_template', 'paste' );
		}

		// Default to the paste template.
		if ( empty( $template ) ) {
			$template = 'paste';
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
	 * Returns the recipient ids for mass mail that are manually sent to selected recipients.
	 *
	 * @return array
	 */
	public function get_manual_recipients_ids() {
		$ids = $this->get( 'manual_recipients_ids' );
		return empty( $ids ) ? array() : array_unique( noptin_parse_int_list( $ids ) );
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

		// translators: %s: Placeholder for email recipients.
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

			if ( ! $this->exists() || 'normal' !== $email_type ) {
				return '';
			}

			$post = get_post( $this->id );
			return empty( $post ) ? '' : $post->post_content;

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

		$units = get_noptin_email_delay_units( $label && 1 === $this->get_sends_after() );

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
	 * Returns a link to edit the campaign.
	 *
	 * @since 1.7.0
	 * @return string.
	 */
	public function get_edit_url() {

		$param = array(
			'page'        => 'noptin-email-campaigns',
			'section'     => 'automations',
			'sub_section' => 'edit_campaign',
			'campaign'    => $this->id,
		);
		return add_query_arg( $param, admin_url( '/admin.php' ) );

	}

	/**
	 * Returns a link to preview the campaign.
	 *
	 * @since 1.7.0
	 * @return string.
	 */
	public function get_preview_url( $recipient_email = '' ) {
		return get_noptin_action_url(
			'view_in_browser',
			noptin_encrypt(
				wp_json_encode(
					array(
						'cid'   => $this->id,
						'email' => $recipient_email,
					)
				)
			),
			true
		);
	}

	/**
	 * Generates a browser preview content for this email.
	 *
	 * @return string
	 */
	public function get_browser_preview_content() {
		return noptin()->emails->automated_email_types->generate_preview( $this );
	}

	/**
	 * Returns a link to duplicate the campaign.
	 *
	 * @since 1.7.0
	 * @return string.
	 */
	public function get_duplication_url() {

		$param = array(
			'page'                => 'noptin-email-campaigns',
			'section'             => 'automations',
			'sub_section'         => false,
			'noptin_admin_action' => 'noptin_duplicate_email_campaign',
			'campaign'            => $this->id,
		);
		return wp_nonce_url( add_query_arg( $param, admin_url( '/admin.php' ) ), 'noptin_duplicate_campaign', 'noptin_nonce' );

	}

	/**
	 * Returns a link to delete the campaign.
	 *
	 * @since 1.7.0
	 * @return string.
	 */
	public function get_delete_url() {

		$param = array(
			'page'                => 'noptin-email-campaigns',
			'section'             => 'automations',
			'sub_section'         => false,
			'noptin_admin_action' => 'noptin_delete_email_campaign',
			'campaign'            => $this->id,
		);
		return wp_nonce_url( add_query_arg( $param, admin_url( '/admin.php' ) ), 'noptin_delete_campaign', 'noptin_nonce' );

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
			'post_content' => $this->get_content( $this->get_email_type() ),
			'meta_input'   => array(
				'automation_type' => $this->type,
				'campaign_type'   => 'automation',
				'campaign_data'   => $this->options,
			),
		);

		// Slash data.
		// WP expects all data to be slashed and will unslash it (fixes '\' character issues).
		$args = wp_slash( $args );

		// Only remove taggeted link rel if it was hooked.
		$has_filter = false !== has_filter( 'content_save_pre', 'wp_targeted_link_rel' );

		if ( $has_filter ) {
			wp_remove_targeted_link_rel_filters();
		}

		// Create or update the email.
		if ( $this->exists() ) {
			$args['ID'] = $this->id;
			$result     = wp_update_post( $args, true );
		} else {
			$result = wp_insert_post( $args, true );
		}

		if ( $has_filter ) {
			wp_init_targeted_link_rel_filters();
		}

		if ( is_numeric( $result ) && $result ) {
			$this->id = $result;
			do_action( 'noptin_' . $this->type . '_campaign_saved', $this );
			return true;
		}

		return $result;
	}

}
