<?php
/**
 * Email API: Newsletter Email.
 *
 * Contains the main newsletter email class
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Represents a single newsletter email.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_Newsletter_Email {

	/** @var bool */
	public $is_legacy = false;

	/** @var int */
	public $id = 0;

	/** @var int */
	public $parent_id = 0;

	/** @var string */
	public $status = 'draft'; // Or publish / future.

	/** @var string */
	public $created;

	/** @var string */
	public $subject = '';

	/** @var array */
	public $options = array();

	/** @var string */
	public $type = 'newsletter';

	/**
	 * Class constructor.
	 *
	 * @param int|string|array $args
	 */
	public function __construct( $args ) {

		// Creating a new campaign.
		if ( empty( $args ) ) {
			return;
		}

		// Loading a saved campaign.
		if ( is_numeric( $args ) ) {
			$post = get_post( $args );

			// Abort if the post does not exist.
			if ( empty( $post ) || 'noptin-campaign' !== $post->post_type || 'newsletter' !== get_post_meta( $post->ID, 'campaign_type', true ) ) {
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

			$this->id        = $post->ID;
			$this->parent_id = $post->post_parent;
			$this->status    = $post->post_status;
			$this->subject   = $post->post_title;
			$this->created   = $post->post_date;
		}

		// Data array.
		if ( is_array( $args ) ) {
			$this->status  = $args['status'];
			$this->subject = $args['subject'];

			// Optional email ID.
			if ( ! empty( $args['id'] ) ) {
				$this->id = (int) $args['id'];
				unset( $args['id'] );
			}

			// Optional parent ID.
			if ( ! empty( $args['parent_id'] ) ) {
				$this->parent_id = (int) $args['parent_id'];
				unset( $args['parent_id'] );
			}

			// Optional created date.
			if ( ! empty( $args['created'] ) ) {
				$this->created = gmdate( 'Y-m-d H:i:s', strtotime( $args['created'] ) );
				unset( $args['created'] );
			}

			unset( $args['status'], $args['subject'] );
			$this->options = $args;
		}

	}

	/**
	 * Checks if the email exists.
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

		if ( 'name' === $key ) {
			$key = 'subject';
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
		return apply_filters( "noptin_get_email_$key", $value, $this );

	}

	/**
	 * Checks if the email is published.
	 *
	 * @return bool
	 */
	public function is_published() {

		$is_published = 'publish' === $this->status;
		return apply_filters( 'noptin_email_is_published', $is_published, $this->status, $this );
	}

	/**
	 * Checks if the email is can send.
	 *
	 * @return bool
	 */
	public function can_send() {

		$can_send = $this->is_published() && $this->exists();

		// Check if the campaign is already sent.
		if ( $can_send && '' !== get_post_meta( $this->id, 'completed', true ) ) {
			$can_send = false;
		}

		return apply_filters( 'noptin_email_can_send', $can_send, $this );
	}

	/**
	 * Checks if this is a mass mail.
	 *
	 * @return bool
	 */
	public function is_mass_mail() {
		return true;
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
	 * Returns the sender for this email.
	 *
	 * @return bool
	 */
	public function get_sender() {

		$sender = $this->get( 'email_sender' );
		$sender = in_array( $sender, array_keys( get_noptin_email_senders() ), true ) ? $sender : 'noptin';
		return apply_filters( 'noptin_email_sender', $sender, $this );
	}

	/**
	 * Returns the email type.
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
	 * Returns the email template for this email.
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
		return apply_filters( 'noptin_email_template', $template, $this );

	}

	/**
	 * Returns the subject for this email.
	 *
	 * @return string
	 */
	public function get_subject() {
		return $this->subject;
	}

	/**
	 * Returns the content for this email.
	 *
	 * @return string
	 */
	public function get_content( $email_type = null ) {

		if ( empty( $email_type ) ) {
			$email_type = $this->get_email_type();
		}

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
	 * Returns a link to edit the campaign.
	 *
	 * @since 1.7.0
	 * @return string.
	 */
	public function get_edit_url() {

		$param = array(
			'page'        => 'noptin-email-campaigns',
			'section'     => 'newsletters',
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
		return noptin()->emails->newsletter->generate_preview( $this );
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
			'section'             => 'newsletters',
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
			'section'             => 'newsletters',
			'sub_section'         => false,
			'noptin_admin_action' => 'noptin_delete_email_campaign',
			'campaign'            => $this->id,
		);
		return wp_nonce_url( add_query_arg( $param, admin_url( '/admin.php' ) ), 'noptin_delete_campaign', 'noptin_nonce' );

	}

	/**
	 * Saves the newsletter email.
	 *
	 * @return bool|WP_Error
	 */
	public function save() {

		// Prepare post args.
		$args = array(
			'post_type'     => 'noptin-campaign',
			'post_parent'   => $this->parent_id,
			'post_title'    => $this->subject,
			'edit_date'     => true,
			'post_date'     => current_time( 'mysql' ),
			'post_date_gmt' => current_time( 'mysql', true ),
			'post_status'   => $this->status,
			'post_content'  => $this->get_content( $this->get_email_type() ),
			'meta_input'    => array(
				'campaign_type' => 'newsletter',
				'campaign_data' => $this->options,
			),
		);

		// Are we scheduling the campaign?
		if ( 'publish' === $this->status && ! empty( $_POST['schedule-date'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

			$datetime = date_create( sanitize_text_field( $_POST['schedule-date'] ), wp_timezone() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( false !== $datetime ) {

				$this->status          = 'future';
				$args['post_status']   = 'future';
				$args['post_date']     = $datetime->format( 'Y-m-d H:i:s' );
				$args['post_date_gmt'] = get_gmt_from_date( $datetime->format( 'Y-m-d H:i:s' ) );

			}
		}

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

		if ( is_int( $result ) && $result ) {
			$this->id = $result;

			$post = get_post( $this->id );

			if ( 'future' === $post->post_status ) {
				wp_clear_scheduled_hook( 'publish_future_post', array( $post->ID ) );
				wp_schedule_single_event( strtotime( get_gmt_from_date( $post->post_date ) . ' GMT' ), 'publish_future_post', array( $post->ID ) );
			}

			do_action( 'noptin_' . $this->type . '_campaign_saved', $this );
			return true;
		}

		return $result;
	}

}
