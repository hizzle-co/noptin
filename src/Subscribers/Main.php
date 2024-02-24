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

}
