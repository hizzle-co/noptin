<?php
/**
 * Emails API: Automated Email Type.
 *
 * Container for a single automated email type.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Container for a single automated email type.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
abstract class Noptin_Automated_Email_Type {

	/**
	 * @var string
	 */
	public $type;

	/**
	 * True when email is being sent.
	 *
	 * @var bool
	 */
	public $sending = false;

	/**
	 * @var string
	 */
	public $notification_hook = '';

	/**
	 * @var string
	 */
	public $unsubscribe_url = '';

	/**
	 * @var Noptin_Subscriber
	 */
	public $subscriber;

	/**
	 * @var WP_User
	 */
	public $user;

	/**
	 * Retrieves the automated email type name.
	 *
	 */
	abstract public function get_name();

	/**
	 * Retrieves the automated email type description.
	 *
	 */
	abstract public function get_description();

	/**
	 * Retrieves the automated email type image.
	 *
	 */
	abstract public function the_image();

	/**
	 * Sends a test email.
	 *
	 * @param Noptin_Automated_Email $email
	 * @param string $recipients
	 * @return bool Whether or not the preview was sent
	 */
	abstract public function send_test( $email, $recipients );

	/**
	 * Registers relevant hooks.
	 *
	 */
	public function add_hooks() {

		add_filter( 'noptin_get_automated_email_prop', array( $this, 'maybe_set_default' ), 10, 3 );
		add_filter( "noptin_default_automated_email_{$this->type}_recipient", array( $this, 'get_default_recipient' ) );

		if ( is_callable( array( $this, 'render_metabox' ) ) ) {
			add_filter( "noptin_automated_email_{$this->type}_options", array( $this, 'render_metabox' ) );
		}

		if ( is_callable( array( $this, 'about_automation' ) ) ) {
			add_filter( "noptin_automation_table_about_{$this->type}", array( $this, 'about_automation' ), 10, 2 );
		}

		if ( ! empty( $this->notification_hook ) && is_callable( array( $this, 'maybe_send_notification' ) ) ) {
			add_action( $this->notification_hook, array( $this, 'maybe_send_notification' ), 10, 2 );
		}

	}

	/**
	 * Sets the default value for a given email type's value.
	 *
	 * @param mixed $value
	 * @param string $prop
	 * @param Noptin_Automated_Email $email
	 */
	public function maybe_set_default( $value, $prop, $email ) {

		// Abort if the email is saved or is not our type.
		if ( ! empty( $value ) || $email->exists() || $email->type !== $this->type ) {
			return $value;
		}

		// Set default template, permission and footer texts.
		switch ( $prop ) {

			case 'name':
				$value = $this->get_name();
				break;

			case 'footer_text':
				$value = get_noptin_footer_text();
				break;

			case 'permission_text':
				$value = get_noptin_permission_text();
				break;

			case 'template':
				$value = get_noptin_option( 'email_template',  'plain' );
				break;
		}

		// Is there a custom method to filter this prop?
		$method = sanitize_key( "default_$prop" );
		if ( is_callable( array( $this, $method ) ) ) {
			$value = $this->$method();
		}

		// Apply email type specific filter then return.
		return apply_filters( "noptin_{$this->type}_default_$prop", $value );

	}

	/**
	 * Returns the default recipient.
	 *
	 */
	public function get_default_recipient() {
		return '';
	}

	/**
	 * Returns the URL to create a new campaign.
	 *
	 */
	public function new_campaign_url() {
		return add_query_arg( 'campaign', urlencode( $this->type ), admin_url( 'admin.php?page=noptin-email-campaigns&section=automations&sub_section=edit_campaign' ) );
	}

	/**
	 * Returns an array of all published automated emails.
	 *
	 * @return Noptin_Automated_Email[]
	 */
	public function get_automations() {

		$emails = array();
		$args   = array(
			'numberposts'            => -1,
			'post_type'              => 'noptin-campaign',
			'orderby'                => 'menu_order',
			'order'                  => 'ASC',
			'suppress_filters'       => true, // DO NOT allow WPML to modify the query
			'cache_results'          => true,
			'update_post_term_cache' => false,
			'post_status'            => array( 'publish' ),
			'meta_query'             => array(
				array(
					'key'   => 'campaign_type',
					'value' => 'automation',
				),
				array(
					'key'   => 'automation_type',
					'value' => $this->type,
				),
			),
		);

		foreach ( get_posts( $args ) as $post ) {noptin_dump( $post );
			$emails[] = new Noptin_Automated_Email( $post->ID );
		}

		return $emails;

	}

	/**
	 * Schedules an automated email.
	 *
	 * @param int|string $object_id
	 * @param Noptin_Automated_Email $automation
	 */
	public function schedule_notification( $object_id, $automation ) {

		if ( ! $automation->supports_timing() || $automation->sends_immediately() ) {
			return do_noptin_background_action( $this->notification_hook, $object_id, $automation->id );
		}

		$sends_after      = (int) $automation->get_sends_after();
		$sends_after_unit = $automation->get_sends_after_unit();

		$timestamp        = strtotime( "+ $sends_after $sends_after_unit", current_time( 'timestamp', true ) );
		return schedule_noptin_background_action( $timestamp, $this->notification_hook, $object_id, $automation->id );

	}

	/**
	 * Returns an array of email recipients.
	 *
	 * @param Noptin_Automated_Email $automation
	 * @param array $merge_tags
	 * @return array
	 */
	public function get_recipients( $automation, $merge_tags = array() ) {

		$recipients = array();

		$merge_tags['--notracking'] = '';
		foreach ( explode( ',', $automation->get_recipients() ) as $recipient ) {

			$no_tracking = false !== strpos( $recipient, '--notracking' );
			$recipient   = trim( str_replace( array_keys( $merge_tags ), array_values( $merge_tags ), $recipient ) );

			$recipients[ $recipient ] = $no_tracking;

		}

		return $recipients;
	}

	/**
	 * Retrieves an array of subscriber merge tags.
	 *
	 * @return array
	 */
	public function get_subscriber_merge_tags() {

		$tags = array();
		foreach ( get_noptin_custom_fields() as $field ) {

			$merge_tag = sanitize_key( $field['merge_tag'] );

			$tags[ $merge_tag ] = array(
				'description' => strip_tags( $field['label'] ),
				'callback'    => array( $this, 'get_subscriber_field' ),
				'example'     => $merge_tag . " default=''",
			);

		}

		return $tags;

	}

	/**
	 * Custom field value of the current subscriber.
	 *
	 * @param array $args
	 * @param string $field
	 * @return string
	 */
	public function get_subscriber_field( $args = array(), $field = 'first_name' ) {
		$default = isset( $args['default'] ) ? $args['default'] : '';

		// Abort if no subscriber.
		if ( empty( $this->subscriber ) || ! $this->subscriber->has_prop( $field ) ) {
			return esc_html( $default );
		}

		$all_fields = wp_list_pluck( get_noptin_custom_fields(), 'type', 'merge_tag' );

		// Format field value.
		if ( isset( $all_fields[ $field ] ) ) {

			$value = $this->subscriber->get( $field );
			if ( 'checkbox' == $all_fields[ $field ] ) {
				return ! empty( $value ) ? __( 'Yes', 'newsletter-optin-box' ) : __( 'No', 'newsletter-optin-box' );
			}

			$value = wp_kses_post(
				format_noptin_custom_field_value(
					$this->subscriber->get( $field ),
					$all_fields[ $field ],
					$this->subscriber
				)
			);

			if ( "&mdash;" !== $value ) {
				return $value;
			}
		}

		return esc_html( $default );
	}

	/**
	 * Retrieves an array of supported merge tags.
	 *
	 * @return array
	 */
	public function get_merge_tags() {
		return array();
	}

	/**
	 * Retrieves flattened merge tags.
	 *
	 * @return array
	 */
	public function get_flattened_merge_tags() {
		$merge_tags = array();

		foreach ( $this->get_merge_tags() as $_merge_tags ) {
			array_merge( $merge_tags, $_merge_tags );
		}

		return $merge_tags;
	}

	/**
	 * Registers supported merge tags.
	 *
	 * @return array
	 */
	public function register_merge_tags() {

		// Register general merge tags.
		foreach ( $this->get_flattened_merge_tags() as $tag => $details ) {
			noptin()->emails->tags->add_tag( $tag, $details );
		}

		// Register subsriber merge tags.
		if ( ! empty( $this->subscriber ) ) {
			foreach ( $this->get_subscriber_merge_tags() as $tag => $details ) {
				noptin()->emails->tags->add_tag( $tag, $details );
			}
		}

		// Unsubscribe URL.
		if ( ! empty( $this->unsubscribe_url ) ) {
			noptin()->emails->tags->tags['unsubscribe_url']['replacement'] = $this->unsubscribe_url;
		}

	}

	/**
	 * Unregisters supported merge tags.
	 *
	 * @return array
	 */
	public function unregister_merge_tags() {

		// Unregister general merge tags.
		foreach ( array_keys( $this->get_flattened_merge_tags() ) as $tag ) {
			noptin()->emails->tags->remove_tag( $tag );
		}

		// Unregister subsriber merge tags.
		if ( ! empty( $this->subscriber ) ) {
			foreach ( array_keys( $this->get_subscriber_merge_tags() ) as $tag ) {
				noptin()->emails->tags->remove_tag( $tag );
			}
		}

		// Unsubscribe URL.
		if ( ! empty( $this->unsubscribe_url ) ) {
			noptin()->emails->tags->tags['unsubscribe_url']['replacement'] = '';
		}

	}

	/**
	 * Sends a notification.
	 *
	 * @param Noptin_Automated_Email $campaign
	 * @param string $key
	 * @param array $recipients
	 */
	protected function send( $campaign, $key, $recipients ) {

		// Prepare recipient.
		$recipient = array_filter(
			array(
				'cid' => $campaign->id,
				'uid' => empty( $this->user ) ? false : $this->user->ID,
				'sid' => empty( $this->subscriber ) ? false : $this->subscriber->id,
			)
		);

		// Generate unsubscribe url.
		$this->unsubscribe_url = get_noptin_action_url( 'unsubscribe', noptin_encrypt( wp_json_encode( $recipient ) ) );

		// Register merge tags.
		$this->register_merge_tags();

		// Indicate that we're sending an email.
		$this->sending = true;

		foreach ( $recipients as $email => $track ) {

			// Send the email.
			noptin_send_email(
				array(
					'recipients'               => $email,
					'subject'                  => noptin_parse_email_subject_tags( $campaign->get_subject() ),
					'message'                  => noptin_generate_automated_email_content( $campaign, $recipient, $track  ),
					'headers'                  => array(),
					'attachments'              => array(),
					'reply_to'                 => '',
					'from_email'               => '',
					'from_name'                => '',
					'content_type'             => $campaign->get_email_type() === 'plain_text' ? 'text' : 'html',
					'unsubscribe_url'          => $this->unsubscribe_url,
					'disable_template_plugins' => ! ( $campaign->get_email_type() === 'normal' && $campaign->get_template() === 'default' ),
				)
			);

		}

		// Indicate that we're nolonger sending an email.
		$this->sending = false;

		// Uregister merge tags.
		$this->unregister_merge_tags();

		$this->user       = null;
		$this->subscriber = null;

		// TODO:Register user and subscriber merge tags if the two are set
		// For post digests and new post notifications, generate email content with merge tags then set for future sending. Only subscriber / user merge tags will be applied at the time of sending.
		// Work on post digests and new post notifications.
	}

	/**
	 * Prepares test data.
	 *
	 * @param Noptin_Automated_Email $email
	 */
	public function prepare_test_data( $email ) {
		$this->user = wp_get_current_user();
		$subscriber = get_current_noptin_subscriber_id();

		if ( $subscriber ) {
			$this->subscriber = new Noptin_Subscriber( $subscriber );
		}

	}

}
