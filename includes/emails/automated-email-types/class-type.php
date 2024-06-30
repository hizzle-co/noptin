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
	 * @var string[]
	 */
	public $contexts = array();

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

		add_filter( 'noptin_automation_sub_types', array( $this, 'register_automation_type' ) );

		if ( is_callable( array( $this, 'campaign_options' ) ) ) {
			add_filter( "noptin_automation_{$this->type}_email_extra_settings", array( $this, 'campaign_options' ) );
		}

		add_filter( "noptin_automation_{$this->type}_merge_tags", array( $this, 'get_flattened_merge_tags' ), -1 );

		if ( is_callable( array( $this, 'about_automation' ) ) ) {
			add_filter( "noptin_automation_table_about_{$this->type}", array( $this, 'about_automation' ), 10, 2 );
		}

		if ( ! empty( $this->notification_hook ) && is_callable( array( $this, 'maybe_send_notification' ) ) ) {
			add_action( $this->notification_hook, array( $this, 'maybe_send_notification' ), 10, 2 );
		}

		$methods = array(
			'saved'       => 'on_save_campaign',
			'published'   => 'on_publish_campaign',
			'unpublished' => 'on_unpublish_campaign',
			'deleted'     => 'on_delete_campaign',
		);

		foreach ( $methods as $status => $method ) {
			if ( is_callable( array( $this, $method ) ) ) {
				add_action( "noptin_automation_{$this->type}_campaign_{$status}", array( $this, $method ) );
			}
		}
	}

	/**
	 * Registers the email sub types.
	 *
	 * @param array $types
	 * @return array
	 */
	public function register_automation_type( $types ) {
		return array_merge(
			$types,
			array(
				$this->type => array_merge(
					array(
						'label'                      => $this->get_name(),
						'description'                => $this->get_description(),
						'image'                      => $this->get_image(),
						'category'                   => $this->category,
						'is_mass_mail'               => 'Mass Mail' === $this->category,
						'supports_timing'            => 'Mass Mail' !== $this->category || 'post_notifications' === $this->type,
						'contexts'                   => $this->contexts,
						'supports_general_templates' => empty( $this->mail_config['defaults']['blocks'] ),
					),
					$this->mail_config
				),
			)
		);
	}

	/**
	 * Returns the default recipient.
	 *
	 */
	public function default_name() {

		if ( ! empty( $this->mail_config['label'] ) ) {
			return $this->mail_config['label'];
		}

		return $this->get_name();
	}

	/**
	 * Returns an array of all published automated emails.
	 *
	 * @return \Hizzle\Noptin\Emails\Email[]
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
			$emails[] = new \Hizzle\Noptin\Emails\Email( $post );
		}

		return $emails;
	}

	/**
	 * Schedules an automated email.
	 *
	 * @param int|string $object_id
	 * @param \Hizzle\Noptin\Emails\Email $automation
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
	 * @param \Hizzle\Noptin\Emails\Email $automation
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
