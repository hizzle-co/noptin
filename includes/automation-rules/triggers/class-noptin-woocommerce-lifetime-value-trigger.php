<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when a customer reaches a lifetime value.
 *
 * @since 1.3.3
 */
class Noptin_WooCommerce_Lifetime_Value_Trigger extends Noptin_WooCommerce_Trigger {

	/**
	 * Deprecated.
	 */
	public $depricated = true;

	/**
	 * Constructor.
	 *
	 * @since 1.3.3
	 */
	public function __construct() {
		add_action( 'woocommerce_order_status_completed', array( $this, 'init_trigger' ) );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'woocommerce_lifetime_value';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'WooCommerce Lifetime Value', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'When a customer reaches a given lifetime value', 'newsletter-optin-box' );
	}

	/**
     * Returns an array of known smart tags.
     *
     * @since 1.10.1
     * @return array
     */
    public function get_known_smart_tags() {

		return array_merge(
			parent::get_known_smart_tags(),
			$this->get_customer_smart_tags()
		);
    }

	/**
	 * @inheritdoc
	 */
	public function get_keywords() {
		return array(
			'woocommerce',
			'lifetime value',
			'customer',
		);
	}

	/**
	 * @inheritdoc
	 */
	public function settings_to_conditional_logic( $settings ) {

		// We have no conditional logic here.
		if ( ! is_array( $settings ) || ! isset( $settings['lifetime_value'] ) ) {
			return false;
		}

		$conditions = array();

		// Lifetime value.
		if ( ! empty( $settings['lifetime_value'] ) ) {
			$conditions[] = array(
				'type'      => 'customer.total_spent',
				'condition' => 'is',
				'value'     => (int) $settings['lifetime_value'],
			);
		}

		unset( $settings['lifetime_value'] );

		return array(
			'conditional_logic' => $conditions,
			'settings'          => $settings,
		);
	}

	/**
	 * Inits the trigger.
	 *
	 * @param int|WC_Order $order_id The order being acted on.
	 * @since 1.10.1
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

		$args = $this->before_trigger_wc( false, $customer );

		$args['customer'] = $customer;

		$this->trigger( $customer, $args );

		$this->after_trigger_wc( $args );
	}

}
