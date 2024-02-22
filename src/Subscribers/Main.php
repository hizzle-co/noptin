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

		$triggers[] = array(
			'id'         => 'unsubscribe',
			'trigger_id' => 'unsubscribe',
			'callback'   => function ( &$automation_rule ) {

				/** @var \Hizzle\Noptin\DB\Automation_Rule $automation_rule */
				$automation_rule->set_trigger_id( 'noptin_subscriber_status_set_to_unsubscribed' );
			},
		);

		$triggers[] = array(
			'id'         => 'import_subscriber',
			'trigger_id' => 'import_subscriber',
			'callback'   => function ( &$automation_rule ) {

				/** @var \Hizzle\Noptin\DB\Automation_Rule $automation_rule */
				$automation_rule->set_trigger_id( 'noptin_subscribers_import_item' );
			},
		);

		return $triggers;
	}

	/**
	 * Counts a customer's orders.
	 *
	 * @since 3.0.0
	 */
	public static function count_customer_orders( $customer_id_or_email ) {
		if ( empty( $customer_id_or_email ) ) {
			return 0;
		}

		if ( is_email( $customer_id_or_email ) && email_exists( $customer_id_or_email ) ) {
			$customer_id_or_email = email_exists( $customer_id_or_email );
		}

		if ( is_numeric( $customer_id_or_email ) ) {
			return (int) wc_get_customer_order_count( $customer_id_or_email );
		}

		$orders = wc_get_orders(
			array(
				'limit'         => -1,
				'billing_email' => $customer_id_or_email,
				'return'        => 'ids',
				'type'          => 'shop_order',
			)
		);

		return count( $orders );
	}

	/**
	 * Calculates a customer's lifetime value.
	 *
	 * @since 3.0.0
	 */
	public static function calculate_customer_lifetime_value( $customer_id_or_email ) {
		if ( empty( $customer_id_or_email ) ) {
			return 0;
		}

		if ( is_email( $customer_id_or_email ) && email_exists( $customer_id_or_email ) ) {
			$customer_id_or_email = email_exists( $customer_id_or_email );
		}

		if ( is_numeric( $customer_id_or_email ) ) {
			return (float) wc_get_customer_total_spent( $customer_id_or_email );
		}

		// Fetch all customer orders.
		$orders = wc_get_orders(
			array(
				'limit'         => -1,
				'billing_email' => $customer_id_or_email,
				'status'        => wc_get_is_paid_statuses(),
			)
		);

		$total = 0;

		// Get the sum of order totals.
		foreach ( $orders as $order ) {
			$total += $order->get_total();
		}

		return $total;
	}
}
