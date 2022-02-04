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
	 * Object this email is for, for example a customer, product, or subscriber.
	 *
	 * @var object|bool
	 */
	public $object;

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
	public function get_recipients( $automation, $merge_tags ) {

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
		foreach ( $this->get_flattened_merge_tags() as $tag => $details ) {
			noptin()->emails->tags->add_tag( $tag, $details );
		}
	}

	/**
	 * Unregisters supported merge tags.
	 *
	 * @return array
	 */
	public function unregister_merge_tags() {
		foreach ( array_keys( $this->get_flattened_merge_tags() ) as $tag ) {
			noptin()->emails->tags->remove_tag( $tag );
		}
	}

}
