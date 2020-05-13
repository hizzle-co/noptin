<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Fires when there is a new order.
 *
 * @since       1.2.8
 */
class Noptin_WooCommerce_New_Order_Trigger extends Noptin_Abstract_Trigger {

    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'woocommerce_new_order';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return __( 'WooCommerce New Order', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __( 'Fired when there is a new WooCommerce order', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_rule_description( $rule ) {
        return __( 'When a subscriber makes a new WooCommerce order', 'newsletter-optin-box' );
    }

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
            'coupon',
            'ecommerce'
        );
    }

    /**
     * @inheritdoc
     */
    public function get_settings() {

        return array(

            'order_status'    => array(
				'el'          => 'select',
				'label'       => __( 'Order Status', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select an order status', 'newsletter-optin-box' ),
				'options'     => wc_get_order_statuses(),
				'description' => __( 'Select the order status for which this trigger should fire.', 'newsletter-optin-box' ),
            ),

            'first_time_purchase'    => array(
                'type'        => 'checkbox_alt',
                'el'          => 'input',
                'label'       => __( 'New customers', 'newsletter-optin-box' ),
                'description' => __( 'Only fire for first time purchasers?', 'newsletter-optin-box' ),
            ),

        );

    }

    /**
     * @inheritdoc
     */
    public function can_run() {
        return class_exists( 'WooCommerce' );
    }

    /**
     * @inheritdoc
     */
    public function is_rule_valid_for_args( $rule, $args, $subscriber, $action ) {
        return true;
    }

}
