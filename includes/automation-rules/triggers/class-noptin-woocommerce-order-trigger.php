<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when an order's status changes.
 *
 * @since 1.8.1
 */
class Noptin_WooCommerce_Order_Trigger extends Noptin_WooCommerce_Trigger {

	/**
	 * @var string The trigger's order action.
	 */
	protected $order_action;

	/**
	 * @var string The trigger's order action label.
	 */
	protected $order_action_label;

	/**
	 * Constructor.
	 *
	 * @since 1.8.1
	 * @param string $order_action The trigger's order status.
	 * @param string $order_action_label The trigger's order action label.
	 */
	public function __construct( $order_action, $order_action_label ) {
		$this->order_action       = $order_action;
		$this->order_action_label = $order_action_label;

		$status_hooks = array(
			'checkout_order_processed' => 'woocommerce_checkout_order_processed',
			'payment_complete'         => 'woocommerce_payment_complete',
			'order_refunded'           => 'woocommerce_order_refunded',
			'new_order'                => 'woocommerce_new_order',
			'update_order'             => 'woocommerce_update_order',
		);

		if ( in_array( $order_action, array_keys( $status_hooks ), true ) ) {
			add_action( $status_hooks[ $order_action ], array( $this, 'init_trigger' ), 10000, 1 );
		} else {
			add_action( 'woocommerce_order_status_' . $order_action, array( $this, 'init_trigger' ), 10000, 1 );
		}

	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'wc_' . sanitize_key( $this->order_action );
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		// translators: %s is the order action label, e.g. "Created" or "Refunded".
		return sprintf( __( 'WooCommerce Order > %s', 'newsletter-optin-box' ), $this->order_action_label );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		// translators: %s is the order action label, e.g. "Created" or "Refunded".
		return sprintf( __( 'When a WooCommerce order is %s', 'newsletter-optin-box' ), wc_strtolower( $this->order_action_label ) );
	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_description( $rule ) {
		$settings = $rule->trigger_settings;

		if ( ! empty( $settings['new_customer'] ) ) {
			// translators: %s is the order action label, e.g. "Created" or "Refunded".
			return sprintf( __( "When a first-time customer's WooCommerce order is %s", 'newsletter-optin-box' ), wc_strtolower( $this->order_action_label ) );
		}

		return $this->get_description();

	}

	/**
     * Returns an array of known smart tags.
     *
     * @since 1.8.1
     * @return array
     */
    public function get_known_smart_tags() {

		return array_merge(
			parent::get_known_smart_tags(),
			$this->get_order_smart_tags(),
			$this->get_customer_smart_tags()
		);
    }

	/**
	 * Inits the trigger.
	 *
	 * @param int|WC_Order $order_id The order being acted on.
	 * @since 1.8.1
	 */
	public function init_trigger( $order_id ) {

		if ( is_numeric( $order_id ) ) {
			$order = wc_get_order( $order_id );
		} else {
			$order = $order_id;
		}

		if ( empty( $order ) || ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		if ( $order->get_customer_id() ) {
			$customer = new WC_Customer( $order->get_customer_id() );
		} else {
			$customer = WC()->customer;
		}

		$args = $this->before_trigger_wc( $order, $customer );

		$args['order']    = $order;
		$args['customer'] = $customer;

		$this->trigger( $customer, $args );

		$this->after_trigger_wc( $args );
	}

}
