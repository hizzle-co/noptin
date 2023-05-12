<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when a product is refunded.
 *
 * @since 1.11.9
 */
class Noptin_WooCommerce_Product_Refunded_Trigger extends Noptin_WooCommerce_Product_Purchased_Trigger {

	/**
	 * Constructor.
	 *
	 * @since 1.3.0
	 */
	public function __construct() {
		add_action( 'woocommerce_order_refunded', array( $this, 'maybe_trigger' ) );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'woocommerce_product_refunded';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'WooCommerce Product Refunded', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'When a WooCommerce product is refunded', 'newsletter-optin-box' );
	}

	/**
	 * Validates the order status.
	 *
	 * @param \WC_Order $order The order.
	 */
	public function validate_order_status( $order ) {
		if ( ! $order->has_status( 'refunded' ) ) {
			throw new Exception( 'The order status is not valid' );
		}
	}
}
