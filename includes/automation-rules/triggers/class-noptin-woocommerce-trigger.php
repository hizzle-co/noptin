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

	/**
	 * @inheritdoc
	 */
	public function get_settings() {

		return array(

			'new_customer' => array(
				'type'        => 'checkbox_alt',
				'el'          => 'input',
				'label'       => __( 'New customers', 'newsletter-optin-box' ),
				'description' => __( 'Only fire for first time buyers?', 'newsletter-optin-box' ),
			),

		);

	}

	/**
     * Checks if this rule is valid for the above parameters.
     *
     * @since 1.2.8
     * @param Noptin_Automation_Rule $rule The rule to check for.
     * @param mixed $args Extra args for the action.
     * @param mixed $subject The subject that this rule was triggered for.
     * @param Noptin_Abstract_Action $action The action to run.
     * @return bool
     */
	public function is_rule_valid_for_args( $rule, $args, $subject, $action ) {
		$settings = $rule->trigger_settings;

		// Are we firering for new customers only?
		if ( ! empty( $settings['new_customer'] ) ) {

			// Fetch the user associated with the order.
			$user = $args['bridge']->get_order_customer_user_id( $args['order_id'] );
			if ( empty( $user ) ) {
				$user = $args['bridge']->get_order_customer_email( $args['order_id'] );
			}

			return $args['bridge']->get_order_count( $user ) === 1;
		}

		return true;

	}

}
// TODO: Add a conditional rule for number of previous purchases.
// TODO: Add a conditional rule for customers who checked the marketing checkbox.
