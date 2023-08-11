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
abstract class Noptin_Automated_Email_Type extends Noptin_Email_Type {

	/**
	 * @var string
	 */
	public $category = 'General';

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
	 * @deprecated 1.11.0
	 */
	public function the_image(){}

	/**
	 * Returns the image URL or dashicon for the automated email type.
	 *
	 * @return string|array
	 */
	public function get_image() {
		return 'email-alt';
	}

	/**
	 * Registers relevant hooks.
	 *
	 */
	public function add_hooks() {

		parent::add_hooks();

		add_filter( "noptin_default_automated_email_{$this->type}_recipient", array( $this, 'get_default_recipient' ) );

		if ( is_callable( array( $this, 'render_metabox' ) ) ) {
			add_action( "noptin_automated_email_{$this->type}_options", array( $this, 'render_metabox' ) );
		}

		if ( is_callable( array( $this, 'about_automation' ) ) ) {
			add_filter( "noptin_automation_table_about_{$this->type}", array( $this, 'about_automation' ), 10, 2 );
		}

		if ( ! empty( $this->notification_hook ) && is_callable( array( $this, 'maybe_send_notification' ) ) ) {
			add_action( $this->notification_hook, array( $this, 'maybe_send_notification' ), 10, 2 );
		}

		if ( is_callable( array( $this, 'on_save_campaign' ) ) ) {
			add_action( "noptin_{$this->type}_campaign_saved", array( $this, 'on_save_campaign' ) );
		}

		if ( is_callable( array( $this, 'on_delete_campaign' ) ) ) {
			add_action( "noptin_{$this->type}_campaign_before_delete", array( $this, 'on_delete_campaign' ) );
		}

	}

	/**
	 * Returns the default recipient.
	 *
	 */
	public function default_name() {
		return $this->get_name();
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
		return add_query_arg( 'campaign', rawurlencode( $this->type ), admin_url( 'admin.php?page=noptin-email-campaigns&section=automations&sub_section=edit_campaign' ) );
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

		foreach ( get_posts( $args ) as $post ) {
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

		$timestamp        = strtotime( "+ $sends_after $sends_after_unit", time() );
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

			$track     = false === strpos( $recipient, '--notracking' );
			$recipient = trim( str_replace( array_keys( $merge_tags ), array_values( $merge_tags ), $recipient ) );

			if ( ! empty( $recipient ) ) {
				$recipients[ $recipient ] = $track;
			}
		}

		return $recipients;
	}

}
