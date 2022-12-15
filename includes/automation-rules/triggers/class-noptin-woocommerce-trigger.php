<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Helper class for WooCommerce triggers.
 *
 * @since 1.9.0
 */
abstract class Noptin_WooCommerce_Trigger extends Noptin_Abstract_Trigger {

	/**
	 * @inheritdoc
	 */
	public function get_image() {
		return 'https://cdn.noptin.com/templates/images/woocommerce-icon.png';
	}

	/**
	 * @inheritdoc
	 */
	public function get_keywords() {
		return array(
			'woocommerce',
			'order',
			'ecommerce',
			'product',
		);
	}

	/**
	 * Fired before triggering WC events.
	 *
	 * @param WC_Order $order The WooCommerce order.
	 * @param WC_Customer $customer The WooCommerce customer.
	 * @param WC_Product $product The WooCommerce product.
	 * @return array An array of args to pass to the action.
	 */
	public function before_trigger_wc( $order, $customer = false, $product = false ) {

		/** @var Noptin_WooCommerce_Automated_Email_Type[] $email_types */
		$email_types = noptin()->emails->automated_email_types->types;

		$args = array();

		// Set order.
		if ( ! empty( $order ) && isset( $email_types['woocommerce_new_order'] ) ) {
			$args['email']                               = $order->get_billing_email();
			$args['previous_order']                      = $email_types['woocommerce_new_order']->order;
			$email_types['woocommerce_new_order']->order = $order;
		}

		// Set customer.
		if ( ! empty( $customer ) && isset( $email_types['woocommerce_new_order'] ) ) {
			$args['email']                                  = $customer->get_email();
			$args['previous_customer']                      = $email_types['woocommerce_new_order']->customer;
			$email_types['woocommerce_new_order']->customer = $customer;
		}

		// Set product.
		if ( ! empty( $product ) && isset( $email_types['woocommerce_product_purchase'] ) ) {
			$args['previous_product']                             = $email_types['woocommerce_product_purchase']->product;
			$email_types['woocommerce_product_purchase']->product = $product;
		}

		return $args;
	}

	/**
	 * Fired after triggering WC events.
	 *
	 * @param array $args An array of args passed to the action.
	 */
	public function after_trigger_wc( $args ) {

		/** @var Noptin_WooCommerce_Automated_Email_Type[] $email_types */
		$email_types = noptin()->emails->automated_email_types->types;

		if ( isset( $args['previous_order'] ) && isset( $email_types['woocommerce_new_order'] ) ) {
			$email_types['woocommerce_new_order']->order = $args['previous_order'];
		}

		if ( isset( $args['previous_customer'] ) && isset( $email_types['woocommerce_new_order'] ) ) {
			$email_types['woocommerce_new_order']->customer = $args['previous_customer'];
		}

		if ( isset( $args['previous_product'] ) && isset( $email_types['woocommerce_product_purchase'] ) ) {
			$email_types['woocommerce_product_purchase']->product = $args['previous_product'];
		}

	}

	/**
	 * Returns order smart tags.
	 *
	 * @return array
	 */
	public function get_order_smart_tags() {

		$email_types = noptin()->emails->automated_email_types->types;

		if ( isset( $email_types['woocommerce_new_order'] ) ) {
			/** @var Noptin_WooCommerce_New_Order_Email[] $email_types */
			return $email_types['woocommerce_new_order']->get_order_merge_tags();
		}

		return false;
	}

	/**
	 * Returns customer smart tags.
	 *
	 * @return array
	 */
	public function get_customer_smart_tags() {

		$email_types = noptin()->emails->automated_email_types->types;

		if ( isset( $email_types['woocommerce_new_order'] ) ) {
			/** @var Noptin_WooCommerce_New_Order_Email[] $email_types */
			return $email_types['woocommerce_new_order']->get_customer_merge_tags();
		}

		return false;
	}

	/**
	 * Returns product smart tags.
	 *
	 * @return array
	 */
	public function get_product_smart_tags() {

		$email_types = noptin()->emails->automated_email_types->types;

		if ( isset( $email_types['woocommerce_product_purchase'] ) ) {
			/** @var Noptin_WooCommerce_Product_Purchase_Email[] $email_types */
			return $email_types['woocommerce_product_purchase']->get_product_merge_tags();
		}

		return false;
	}

}
