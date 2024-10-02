<?php

namespace Hizzle\Noptin\Emails;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for a Noptin emails.
 *
 * @since 3.0.0
 */
class Records extends \Hizzle\Noptin\Objects\Collection {

	/**
	 * @var string the record class.
	 */
	public $record_class = '\Hizzle\Noptin\Emails\Record';

	/**
	 * @var string integration.
	 */
	public $integration = 'noptin';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function __construct() {
		$this->label             = __( 'Email Campaigns', 'newsletter-optin-box' );
		$this->singular_label    = __( 'Email Campaign', 'newsletter-optin-box' );
		$this->type              = 'noptin-campaign';
		$this->smart_tags_prefix = 'campaign';
		$this->can_list          = false;
		$this->icon              = array(
			'icon' => 'email',
			'fill' => '#008000',
		);

		parent::__construct();
		add_filter( 'noptin_automation_rules_email_conditional_logic_skip_tags', array( $this, 'conditional_logic_skip_tags' ), 10, 2 );
		add_action( 'noptin_automation_rules_email_prepare_skipped_rules', array( $this, 'prepare_skipped_rules' ), 10, 2 );
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {

		return array(
			'id'                  => array(
				'label' => __( 'ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'name'                => array(
				'label'      => __( 'Name', 'newsletter-optin-box' ),
				'type'       => 'string',
				'deprecated' => 'campaign_title',
			),
			'subject'             => array(
				'label' => __( 'Subject', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'preview_url'         => array(
				'label' => __( 'Preview URL', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'edit_url'            => array(
				'label' => __( 'Edit URL', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'view_in_browser_url' => array(
				'label' => __( 'View in browser', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'send_count'          => array(
				'label' => __( 'Sends', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'open_count'          => array(
				'label' => __( 'Opens', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'click_count'         => array(
				'label' => __( 'Clicks', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'unsubscribe_count'   => array(
				'label' => __( 'Unsubscribed', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'created'             => array(
				'label' => __( 'Created', 'newsletter-optin-box' ),
				'type'  => 'date',
			),
		);
	}

	/**
	 * Returns a list of available (actions).
	 *
	 * @return array $actions The actions.
	 */
	public function get_actions() {
		return array_merge(
			parent::get_actions(),
			array(
				'email' => array(
					'id'            => 'email',
					'label'         => __( 'Send Email', 'newsletter-optin-box' ),
					'description'   => __( 'Send Email', 'newsletter-optin-box' ),
					'callback'      => __CLASS__ . '::run_send_email_action',
					'can_run'       => __CLASS__ . '::can_run_send_email_action',
					// translators: %s is a list of conditions.
					'run_if'        => __( 'Sends if %s', 'newsletter-optin-box' ),
					// translators: %s is a list of conditions.
					'skip_if'       => __( 'Does not send if %s', 'newsletter-optin-box' ),
					'callback_args' => array( 'rule', 'args', 'smart_tags' ),
				),
			)
		);
	}

	/**
	 * Sends an email.
	 *
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule — The automation rule.
	 */
	public static function run_send_email_action( $rule, $args, $smart_tags ) {
		if ( ! empty( $args['post_meta'] ) ) {
			update_post_meta( (int) $args['post_meta']['id'], $args['post_meta']['key'], array( (int) $args['post_meta']['id'], (int) $rule->get_action_setting( 'automated_email_id' ) ) );
		}

		return noptin_send_email_campaign(
			$rule->get_action_setting( 'automated_email_id' ),
			$smart_tags
		);
	}

	/**
	 * Checks if we can send an email.
	 *
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule — The automation rule.
	 */
	public static function can_run_send_email_action( $subject, $rule, $args ) {
		global $noptin_subscribers_batch_action;

		// Abort if we do not have a campaign.
		$automated_email_id = $rule->get_action_setting( 'automated_email_id' );
		if ( empty( $automated_email_id ) ) {
			throw new \Exception( 'No email campaign found for automation rule:- ' . esc_html( $rule->get_id() ) );
		}

		// ... or if we're importing subscribers.
		if ( 'import' === $noptin_subscribers_batch_action && 'noptin_subscribers_import_item' !== $rule->get_trigger_id() ) {
			throw new \Exception( 'Cannot send email campaign while importing subscribers' );
		}

		$campaign = noptin_get_email_campaign_object( $automated_email_id );
		$can_send = $campaign->can_send();

		if ( is_wp_error( $can_send ) ) {
			throw new \Exception( esc_html( $can_send->get_error_message() ) );
		}

		if ( ! $can_send ) {
			throw new \Exception( 'Email campaign cannot be sent:- ' . esc_html( $campaign->get( 'name' ) ) );
		}

		if ( absint( $campaign->get( 'automation_rule' ) ) !== absint( $rule->get_id() ) ) {
			throw new \Exception( 'Email campaign does not belong to the automation rule:- ' . esc_html( $campaign->get( 'name' ) ) );
		}

		if ( ! empty( $args['post_meta'] ) ) {
			$sent_notification = get_post_meta( $args['post_meta']['id'], $args['post_meta']['key'], true );
			if ( ! is_array( $sent_notification ) || (int) $args['post_meta']['id'] !== (int) $sent_notification[0] ) {
				return true;
			}

			throw new \Exception( 'Email campaign has already been sent:- ' . esc_html( $campaign->get( 'name' ) ) );
		}

		return true;
	}

	/**
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule — The automation rule.
	 */
	public function conditional_logic_skip_tags( $tags, $rule ) {
		// Abort if we do not have a campaign.
		$automated_email_id = $rule->get_action_setting( 'automated_email_id' );
		if ( empty( $automated_email_id ) ) {
			return $tags;
		}

		$campaign = noptin_get_email_campaign_object( $automated_email_id );

		if ( ! $campaign->is_mass_mail() || ! noptin_has_active_license_key() ) {
			return $tags;
		}

		$collection = apply_filters( 'noptin_' . $campaign->get_sender() . '_email_sender_collection_object', false );

		if ( ! is_object( $collection ) ) {
			return $tags;
		}

		$collection_tags = new \Hizzle\Noptin\Objects\Tags( $collection->type );
		return array_merge(
			$tags,
			array_keys( $collection_tags->tags )
		);
	}

	/**
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule — The automation rule.
	 */
	public function prepare_skipped_rules( $conditional_logic, $rule ) {
		if ( ! noptin_has_active_license_key() ) {
			return;
		}

		$automated_email_id = $rule->get_action_setting( 'automated_email_id' );

		$GLOBALS['noptin_email_' . $automated_email_id . '_extra_conditional_logic'] = $conditional_logic;
	}
}
