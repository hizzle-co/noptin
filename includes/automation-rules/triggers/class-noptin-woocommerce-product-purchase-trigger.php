<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Fires when a product is purchase.
 *
 * @since       1.2.8
 */
class Noptin_WooCommerce_Product_Purchase_Trigger extends Noptin_Abstract_Trigger {

    
    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'woocommerce_product_purchase';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return __( 'WooCommerce Product Purchase', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __( 'Fired when a WooCommerce Product is bought', 'newsletter-optin-box' );
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
        return array(); // product_id, leave blank to fire for any product, separate several ids by comma.
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
