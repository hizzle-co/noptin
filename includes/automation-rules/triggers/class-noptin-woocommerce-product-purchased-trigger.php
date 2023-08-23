<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when a product is purchased.
 *
 * @since 1.11.9
 */
class Noptin_WooCommerce_Product_Purchased_Trigger extends Noptin_WooCommerce_Trigger {

	/**
	 * Constructor.
	 *
	 * @since 1.3.0
	 */
	public function __construct() {
		add_action( 'woocommerce_order_status_completed', array( $this, 'maybe_trigger' ) );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'woocommerce_product_purchased';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'WooCommerce Product Purchased', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'When someone purchases a WooCommerce product', 'newsletter-optin-box' );
	}

	/**
     * Returns an array of known smart tags.
     *
     * @since 1.9.0
     * @return array
     */
    public function get_known_smart_tags() {

		return array_merge(
			parent::get_known_smart_tags(),
			$this->get_order_item_smart_tags(),
			$this->get_product_smart_tags(),
			$this->get_order_smart_tags(),
			$this->get_customer_smart_tags()
		);
    }

	/**
	 * Fires when a product is bought or refunded.
	 *
	 * @param int|WC_Order $order_id The order being acted on.
	 * @since 1.9.0
	 */
	public function maybe_trigger( $order_id ) {

		if ( is_numeric( $order_id ) ) {
			$order = wc_get_order( $order_id );
		} else {
			$order = $order_id;
		}

		// Ensure we have an order.
		if ( empty( $order ) || ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		// Prepare the order customer.
		$customer = $this->get_order_customer( $order );

		// Loop through the order items.
		foreach ( $order->get_items() as $item ) {

			// Ensure we have a product.
			/** @var WC_Order_Item_Product $item */
			$product = $item->get_product();
			if ( empty( $product ) ) {
				continue;
			}

			// Ensure we have a product id.
			$product_id = $product->get_id();
			if ( empty( $product_id ) ) {
				continue;
			}

			// Attach WC hooks.
			$args = array_merge(
				$this->before_trigger_wc( $order, $customer, $product, $item ),
				array(
					'order_id'      => $order->get_id(),
					'product_id'    => $product_id,
					'order_item_id' => $item->get_id(),
				)
			);

			// Trigger the event.
			$this->trigger( $customer, $args );

			// Detach WC hooks.
			$this->after_trigger_wc( $args );
		}
	}

	/**
	 * Prepares email test data.
	 *
	 * @since 1.11.0
	 * @param Noptin_Automation_Rule $rule
	 * @return Noptin_Automation_Rules_Smart_Tags
	 * @throws Exception
	 */
	public function get_test_smart_tags( $rule ) {

		/** @var Noptin_WooCommerce_Automated_Email_Type[] $email_types */
		$email_types = noptin()->emails->automated_email_types->types;

		$email_types['woocommerce_product_purchase']->_prepare_test_data();

		$order = $email_types['woocommerce_product_purchase']->order;
		$args  = array(
			'email' => $order->get_billing_email(),
		);

		$args = $this->prepare_trigger_args( $this->get_order_customer( $order ), $args );

		return $args['smart_tags'];
	}

	/**
	 * Serializes the trigger args.
	 *
	 * @since 1.11.1
	 * @param array $args The args.
	 * @return false|array
	 */
	public function serialize_trigger_args( $args ) {
		return array(
			'order_id'      => $args['order_id'],
			'product_id'    => $args['product_id'],
			'order_item_id' => $args['order_item_id'],
		);
	}

	/**
	 * Unserializes the trigger args.
	 *
	 * @since 1.11.1
	 * @param array $args The args.
	 * @return array|false
	 */
	public function unserialize_trigger_args( $args ) {

		$order = wc_get_order( $args['order_id'] );

		if ( empty( $order ) ) {
			throw new Exception( 'The order no longer exists' );
		}

		$customer = $this->get_order_customer( $order );

		// Check the status.
		$this->validate_order_status( $order );

		/** @var WC_Order_Item_Product $order_item */
		$order_item = $order->get_item( $args['order_item_id'] );

		if ( empty( $order_item ) ) {
			throw new Exception( 'The order item no longer exists' );
		}

		$product = $order_item->get_product();

		if ( empty( $product ) ) {
			throw new Exception( 'The product no longer exists' );
		}

		// Attach WC hooks.
		$args = array_merge(
			$this->before_trigger_wc( $order, $customer, $product, $order_item ),
			array(
				'order_id'      => $order->get_id(),
				'product_id'    => $product->get_id(),
				'order_item_id' => $order_item->get_id(),
			)
		);

		// Prepare the trigger args.
		return $this->prepare_trigger_args( $customer, $args );
	}

	/**
	 * Validates the order status.
	 *
	 * @param \WC_Order $order The order.
	 */
	public function validate_order_status( $order ) {
		if ( ! $order->is_paid() ) {
			throw new Exception( 'The order status is not valid' );
		}
	}
}
