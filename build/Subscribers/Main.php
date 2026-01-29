<?php

namespace Hizzle\Noptin\Subscribers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main subscribers class.
 *
 * @since 3.0.0
 */
class Main {

	private static $hook_suffix;

	/**
	 * Registers custom objects.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		add_action( 'noptin_init', __CLASS__ . '::register_objects' );
		add_filter( 'noptin_automation_rule_migrate_triggers', __CLASS__ . '::migrate_triggers' );
		add_filter( 'noptin_subscriber_should_fire_has_changes_hook', __CLASS__ . '::should_fire_has_changes_hook', 10, 2 );
		add_filter( 'hizzle_rest_noptin_subscribers_record_tabs', __CLASS__ . '::add_collection_subscriber_tabs' );
		add_action( 'noptin_pre_load_actions_page', __NAMESPACE__ . '\Actions::init' );
		add_action( 'noptin_subscribers_before_prepare_query', __CLASS__ . '::hide_blocked_subscribers' );
		add_action( 'noptin_recalculate_subscriber_engagement_rate', __CLASS__ . '::recalculate_subscriber_engagement_rate' );
		add_action( 'noptin_send_confirmation_email', array( __CLASS__, 'send_confirmation_email' ) );

		// Subscribers menu.
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'subscribers_menu' ), 33 );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
			add_filter( 'get_noptin_admin_tools', array( __CLASS__, 'filter_admin_tools' ) );
			add_action( 'noptin_send_confirmation_emails', array( __CLASS__, 'send_confirmation_emails' ) );
		}

		// Initialize the schema.
		Schema::init();

		// Initialize the privacy class.
		Privacy::init();

		// Initialize the manage preferences class.
		Manage_Preferences::init();

		// Initialize the bounce handler.
		Bounce_Handler::init();
	}

	/**
	 * Registers custom objects.
	 *
	 * @since 3.0.0
	 */
	public static function register_objects() {
		\Hizzle\Noptin\Objects\Store::add( new Records() );
	}

	/**
	 * Migrates triggers.
	 *
	 * @since 3.0.0
	 *
	 * @param array $triggers The triggers.
	 */
	public static function migrate_triggers( $triggers ) {

		$triggers[] = array(
			'id'         => 'new_subscriber',
			'trigger_id' => 'new_subscriber',
			'callback'   => function ( &$automation_rule ) {

				/** @var \Hizzle\Noptin\DB\Automation_Rule $automation_rule */
				if ( noptin_has_enabled_double_optin() && ! $automation_rule->get_trigger_setting( 'fire_after_confirmation' ) ) {
					$automation_rule->set_trigger_id( 'noptin_subscriber_status_set_to_pending' );
				} else {
					$automation_rule->set_trigger_id( 'noptin_subscriber_status_set_to_subscribed' );
				}

				// Update the conditional logic.
				$automation_rule->add_conditional_logic_rules(
					array(),
					array( 'fire_after_confirmation' )
				);
			},
		);

		return $triggers;
	}

	/**
	 * Should fire has changes hook.
	 *
	 * @since 3.0.0
	 *
	 * @param bool  $should_fire The should fire.
	 * @param array $changed_keys An array of changed keys.
	 */
	public static function should_fire_has_changes_hook( $should_fire, $changed_keys ) {

		if ( ! $should_fire ) {
			return $should_fire;
		}

		$ignore = array( 'activity', 'date_modified', 'date_created', 'confirm_key' );

		// Abort if all keys in the changes are in the ignore list.
		if ( empty( array_diff( $changed_keys, $ignore ) ) ) {
			return false;
		}

		return $should_fire;
	}

	/**
	 * Registers collection subscriber tabs.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public static function add_collection_subscriber_tabs( $tabs ) {
		$collections = \Hizzle\Noptin\Objects\Store::filtered( array( 'show_tab' => true ) );

		foreach ( $collections as $collection ) {
			$tabs[ $collection->type ] = array_merge(
				$collection->get_custom_tab_details(),
				array( 'callback' => __CLASS__ . '::process_collection_tab' )
			);
		}

		return $tabs;
	}

	/**
	 * Processes collection subscriber tabs.
	 *
	 * @param array $request
	 * @return array
	 */
	public static function process_collection_tab( $request ) {
		$subscriber = noptin_get_subscriber( $request['id'] );

		if ( empty( $subscriber->get_email() ) ) {
			return new \WP_Error( 'subscriber_not_found', 'Subscriber not found', array( 'status' => 400 ) );
		}

		if ( empty( $request['tab_id'] ) ) {
			return new \WP_Error( 'tab_id_not_provided', 'Tab not provided', array( 'status' => 400 ) );
		}

		$collection = \Hizzle\Noptin\Objects\Store::get( $request['tab_id'] );

		if ( empty( $collection ) ) {
			return new \WP_Error( 'collection_not_found', 'Collection not found', array( 'status' => 400 ) );
		}

		return $collection->process_custom_tab( $subscriber->get_email() );
	}

	/**
	 * New subscribers menu.
	 */
	public static function subscribers_menu() {

		self::$hook_suffix = add_submenu_page(
			'noptin',
			esc_html__( 'Email Subscribers', 'newsletter-optin-box' ),
			esc_html__( 'Subscribers', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-subscribers',
			'\Hizzle\WordPress\ScriptManager::render_collection'
		);

		\Hizzle\WordPress\ScriptManager::add_collection(
			self::$hook_suffix,
			'noptin',
			'subscribers'
		);
	}

	/**
	 * Enqueues scripts.
	 *
	 * @param string $hook The hook.
	 */
	public static function enqueue_scripts( $hook ) {
		if ( self::$hook_suffix === $hook ) {
			wp_set_script_translations( 'hizzlewp-store-ui', 'newsletter-optin-box', noptin()->plugin_path . 'languages' );
		}
	}

	/**
	 * Filters admin tools to add subscriber related tools.
	 *
	 * @param array $tools The tools.
	 * @return array
	 */
	public static function filter_admin_tools( $tools ) {
		$tools['send_confirmation_emails'] = array(
			'name'    => __( 'Send Confirmation Emails', 'newsletter-optin-box' ),
			'button'  => __( 'Send', 'newsletter-optin-box' ),
			'desc'    => __( 'Send confirmation emails to all unconfirmed subscribers.', 'newsletter-optin-box' ),
			'url'     => wp_nonce_url( add_query_arg( 'noptin_admin_action', 'noptin_send_confirmation_emails' ), 'noptin-send-confirmation-emails' ),
			'confirm' => __( 'Are you sure you want to send confirmation emails to all unconfirmed subscribers?', 'newsletter-optin-box' ),
		);

		return $tools;
	}

	/**
	 * Sends confirmation emails to unconfirmed subscribers.
	 */
	public static function send_confirmation_emails() {

		// Only admins should be able to do this.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'noptin-send-confirmation-emails' ) ) {
			return;
		}

		// Fetch unconfirmed subscribers.
		$subscribers = noptin_get_subscribers(
			array(
				'confirmed' => 0,
				'number'    => -1,
				'fields'    => 'id',
			)
		);

		if ( empty( $subscribers ) ) {
			noptin()->admin->show_success( 'No unconfirmed subscribers found.' );
			return;
		}

		// Schedule sending of confirmation emails.
		// We don't want to do this in a loop directly to avoid timeouts.
		foreach ( $subscribers as $subscriber ) {
			schedule_noptin_background_action(
				time(),
				'noptin_send_confirmation_email',
				$subscriber
			);
		}

		noptin()->admin->show_success(
			sprintf(
				'Scheduled sending of confirmation emails to %s unconfirmed subscribers.',
				count( $subscribers )
			)
		);
	}

	/**
	 * Sends a confirmation email to a subscriber.
	 *
	 * @param int $subscriber_id The subscriber ID.
	 */
	public static function send_confirmation_email( $subscriber_id ) {
		$subscriber = noptin_get_subscriber( $subscriber_id );

		if ( ! $subscriber->get_email() ) {
			return;
		}

		return $subscriber->do_send_confirmation_email();
	}

	/**
	 * Hides blocked subscribers.
	 *
	 * @param \Hizzle\Store\Query $query The query.
	 */
	public static function hide_blocked_subscribers( $query ) {
		$excluded = wp_parse_list( $query->query_vars['status_not'] ?? array() );
		$included = wp_parse_list( $query->query_vars['status'] ?? array() );

		// Abort if we're already excluding blocked subscribers.
		if ( in_array( 'blocked', $excluded, true ) ) {
			return;
		}

		// Abort if we're already including blocked subscribers.
		if ( in_array( 'blocked', $included, true ) ) {
			return;
		}

		$excluded[] = 'blocked';
		$query->set( 'status_not', $excluded );
	}

	/**
	 * Recalculates the subscriber engagement rate.
	 *
	 * @param int $subscriber_id The subscriber ID.
	 */
	public static function recalculate_subscriber_engagement_rate( $subscriber_id ) {
		global $wpdb;

		// Get the subscriber.
		$subscriber = noptin_get_subscriber( $subscriber_id );

		if ( ! $subscriber || ! $subscriber->get_email() ) {
			return;
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
        			activity,
        			COUNT(*) as total,
        			MAX(date_created) as last_date
				FROM {$wpdb->prefix}noptin_email_logs
    			WHERE email = %s AND activity IN ('send', 'open', 'click')
    			GROUP BY activity
				",
				$subscriber->get_email()
			),
			ARRAY_A
		);

		// Initialize metrics
		$metrics = array(
			'send'  => array(
				'count' => 0,
				'last'  => null,
			),
			'open'  => array(
				'count' => 0,
				'last'  => null,
			),
			'click' => array(
				'count' => 0,
				'last'  => null,
			),
		);

		// Map results
		foreach ( $results as $row ) {
			$metrics[ $row['activity'] ]['count'] = intval( $row['total'] );

			// Dates are stored in UTC time, but without a timezone.
			// Fix that.
			if ( ! empty( $row['last_date'] ) ) {
				$metrics[ $row['activity'] ]['last'] = new \Hizzle\Store\Date_Time( $row['last_date'], new \DateTimeZone( 'UTC' ) );
			}
		}

		// Set metrics
		$subscriber->set( 'total_emails_sent', $metrics['send']['count'] );
		$subscriber->set( 'last_email_sent_date', $metrics['send']['last'] );
		$subscriber->set( 'total_emails_opened', $metrics['open']['count'] );
		$subscriber->set( 'last_email_opened_date', $metrics['open']['last'] );
		$subscriber->set( 'total_links_clicked', $metrics['click']['count'] );
		$subscriber->set( 'last_email_clicked_date', $metrics['click']['last'] );
		$subscriber->set( 'email_engagement_score', $subscriber->calculate_engagement_score() );

		// Save
		$subscriber->save();
	}
}
