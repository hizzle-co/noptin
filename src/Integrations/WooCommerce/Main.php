<?php

namespace Hizzle\Noptin\Integrations\WooCommerce;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integration with WooCommerce
 *
 * @since 3.0.0
 */
class Main {

	/**
	 * @var Template Email template.
	 */
	public $email_template;

	/**
	 * @var Migrate Migrates deprecated emails.
	 */
	public $migrate;

	/**
	 * Class constructor.
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		add_action( 'noptin_register_post_type_objects', array( $this, 'register_custom_objects' ) );
		add_filter( 'noptin_automation_rule_migrate_triggers', array( $this, 'migrate_triggers' ) );
		add_filter( 'noptin_supports_ecommerce_tracking', '__return_true' );
		add_filter( 'noptin_format_price_callback', __CLASS__ . '::price_format_cb' );
		$this->email_template = new Template();
		$this->migrate        = new Migrate();
	}

	public static function price_format_cb() {
		return 'wc_price';
	}

	/**
	 * Registers custom objects.
	 *
	 * @since 3.0.0
	 */
	public function register_custom_objects() {
		\Hizzle\Noptin\Objects\Store::add( new Customers() );
		\Hizzle\Noptin\Objects\Store::add( new Products() );
		\Hizzle\Noptin\Objects\Store::add( new Order_Items() );
		\Hizzle\Noptin\Objects\Store::add( new Orders() );
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
			'id'         => 'woocommerce_new_order',
			'trigger_id' => 'woocommerce_new_order',
			'callback'   => function ( &$automation_rule ) {

				/** @var \Hizzle\Noptin\Automation_Rules\Automation_Rule $automation_rule */
				$action = $automation_rule->get_trigger_setting( 'action' );
				$map    = array(
					'created'    => 'wc_new_order',
					'pending'    => 'wc_pending',
					'processing' => 'wc_processing',
					'held'       => 'wc_on-hold',
					'paid'       => 'wc_payment_complete',
					'completed'  => 'wc_completed',
					'refunded'   => 'wc_order_refunded',
					'cancelled'  => 'wc_cancelled',
					'failed'     => 'wc_failed',
					'deleted'    => 'wc_before_delete_order',
				);

				// Set the new trigger id.
				if ( $action && isset( $map[ $action ] ) ) {
					$automation_rule->set_trigger_id( $map[ $action ] );
				} else {
					$automation_rule->set_trigger_id( 'wc_new_order' );
				}

				// Update the conditional logic.
				$automation_rule->add_conditional_logic_rules(
					$automation_rule->get_trigger_setting( 'new_customer' ) ? array(
						array(
							'type'      => 'customer.order_count',
							'condition' => 'is',
							'value'     => '1',
						),
					) : array(),
					array( 'new_customer', 'action' )
				);
			},
		);

		$triggers[] = array(
			'id'         => 'woocommerce_product_purchase',
			'trigger_id' => 'woocommerce_product_purchase',
			'callback'   => function ( &$automation_rule ) {

				/** @var \Hizzle\Noptin\Automation_Rules\Automation_Rule $automation_rule */
				$action = $automation_rule->get_trigger_setting( 'action' );

				// Set the new trigger id.
				if ( 'refund' === $action ) {
					$automation_rule->set_trigger_id( 'woocommerce_product_refunded' );
				} else {
					$automation_rule->set_trigger_id( 'woocommerce_product_purchased' );
				}

				// Update the conditional logic.
				$automation_rule->add_conditional_logic_rules(
					$automation_rule->get_trigger_setting( 'product_id' ) ? array(
						array(
							'type'      => 'product.id',
							'condition' => 'is',
							'value'     => $automation_rule->get_trigger_setting( 'product_id' ),
						),
					) : array(),
					array( 'product_id', 'action' )
				);
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
