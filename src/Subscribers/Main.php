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

	/**
	 * Stores the main subscribers instance.
	 *
	 * @access private
	 * @var    Main $instance The main db instance.
	 * @since  1.0.0
	 */
	private static $instance = null;

	/**
	 * Get active instance
	 *
	 * @access public
	 * @since  1.0.0
	 * @return Main The main subscribers instance.
	 */
	public static function instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Loads the class.
	 *
	 */
	private function __construct() {
		add_action( 'noptin_init', array( $this, 'init' ) );
		add_filter( 'noptin_automation_rule_migrate_triggers', array( $this, 'migrate_triggers' ) );
		add_filter( 'noptin_subscriber_should_fire_has_changes_hook', array( $this, 'should_fire_has_changes_hook' ), 10, 2 );
		add_filter( 'hizzle_rest_noptin_subscribers_record_tabs', __CLASS__ . '::add_collection_subscriber_tabs' );
	}

	/**
	 * Registers custom objects.
	 *
	 * @since 3.0.0
	 */
	public function init() {
		\Hizzle\Noptin\Objects\Store::add( new Records() );
	}

	/**
	 * Migrates triggers.
	 *
	 * @since 3.0.0
	 *
	 * @param array $triggers The triggers.
	 */
	public function migrate_triggers( $triggers ) {

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
}
