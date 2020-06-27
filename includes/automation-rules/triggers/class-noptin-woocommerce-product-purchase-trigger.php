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
     * @var Noptin_WooCommerce The Noptin and WooCommerce integration bridge.
     */
    private $bridge = null;

    /**
     * Constructor.
     *
     * @since 1.3.0
     * @var Noptin_WooCommerce $bridge The Noptin and WooCommerce integration bridge.
     */
    public function __construct( $bridge ) {
        $this->bridge = $bridge;
        add_action( 'noptin_woocommerce_integration_product_refund', array( $this, 'init_refund_trigger' ), 10000, 5 );
        add_action( 'noptin_woocommerce_integration_product_buy', array( $this, 'init_buy_trigger' ), 10000, 5 );
    }

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
    public function get_rule_description( $rule ) {

        $settings = $rule->trigger_settings;

        // Are we filtering by subscription method?
        if ( empty( $settings['action'] ) || empty( $settings['product_id'] ) ) {
            return __( 'When a subscriber buys a WooCommerce product', 'newsletter-optin-box' );
        }

        $product = wc_get_product( $settings['product_id'] );
		if ( empty( $product ) ) {
			$product = '<span style="color: red;">' . __( 'missing product', 'newsletter-optin-box' ) . '<span>';
        } else {
            $product = esc_html( $product->get_name() );
        }

        if ( 'refund' == $settings['action'] ) {

            if ( ! empty( $settings['first_time'] ) ) {
                return sprintf(
                    __( 'When a first time customer is refunded for %s', 'newsletter-optin-box' ),
                   "<code>$product</code>"
                );
            }

            return sprintf(
                __( 'When a customer is refunded for %s', 'newsletter-optin-box' ),
               "<code>$product</code>"
            );

        }
        
        if ( ! empty( $settings['first_time'] ) ) {
            return sprintf(
                __( 'The first time a subscriber buys %s', 'newsletter-optin-box' ),
               "<code>$product</code>"
            );
        }

        return sprintf(
            __( 'When a subscriber buys %s', 'newsletter-optin-box' ),
           "<code>$product</code>"
        );

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

        $products = $this->bridge->get_products();

        return array(

            'product_id' => array(
                'el'          => 'select',
                'options'     => wp_list_pluck( $products, 'name', 'id' ),
                'label'       => __( 'Product', 'newsletter-optin-box' ),
                'placeholder' => __( 'Select a WooCommerce product', 'newsletter-optin-box' ),
            ),

            'action' => array(
                'el'          => 'select',
                'options'     => array(
                    'buy'     => __( 'The product is bought', 'newsletter-optin-box' ),
                    'refund'  => __( 'The product is refunded', 'newsletter-optin-box' ),
                ),
                'label'       => __( 'State', 'newsletter-optin-box' ),
                'placeholder' => __( 'Select the product state', 'newsletter-optin-box' ),
                'default'     => 'buy'
            ),

            'first_time'    => array(
                'type'        => 'checkbox_alt',
                'el'          => 'input',
                'label'       => __( 'New buyer', 'newsletter-optin-box' ),
                'description' => __( 'Only fire the first time someone buys this product?', 'newsletter-optin-box' ),
            ),

        );
    }

    /**
     * @inheritdoc
     */
    public function is_rule_valid_for_args( $rule, $args, $subscriber, $action ) {
        $settings = $rule->trigger_settings;

        // Ensure that we have an action for this event.
        if ( empty( $settings['action'] ) || $settings['action'] !== $args['action'] ) {
            return false;
        }

        // Confirm the products match.
        if ( empty( $settings['product_id'] ) || $settings['product_id'] != $args['product_id'] ) {
            return false;
        }

        // Are we firering for new buyers only?
        if ( ! empty( $settings['first_time'] ) ) {

            // Fetch the user associated with the order.
            $user = $this->bridge->get_order_customer_user_id( $args['order_id'] );
            if ( empty( $user ) ) {
                $user = $this->bridge->get_order_customer_email( $args['order_id'] );
            }

            return $this->bridge->get_product_purchase_count( $user, $args['product_id'] ) === 1;

        }

        return true;

    }

    /**
     * Calls the trigger when a product is refunded.
     *
     * @param string $product_id The product being refunded.
     * @param array $item The order item being refunded.
     * @param int $order_id The order being acted on.
     * @param int $subscriber_id The subscriber for the order.
     * @param Noptin_WooCommerce $bridge The Noptin and WC integration bridge.
     * @since 1.3.0
     */
    public function init_refund_trigger( $product_id, $item, $order_id, $subscriber_id, $bridge ) {
        $details               = $bridge->get_order_details( $order_id );
        $details               = array_merge( $details, $item );
        $details['action']     = 'refund';
        $details['product_id'] = $product_id;
        $this->trigger( $subscriber_id, $details );
    }

    /**
     * Calls the trigger when a product is bought.
     *
     * @param string $product_id The product being bought.
     * @param array $item The order item being bought.
     * @param int $order_id The order being acted on.
     * @param int $subscriber_id The subscriber for the order.
     * @param Noptin_WooCommerce $bridge The Noptin and WC integration bridge.
     * @since 1.3.0
     */
    public function init_buy_trigger( $product_id, $item, $order_id, $subscriber_id, $bridge ) {
        $details               = $bridge->get_order_details( $order_id );
        $details               = array_merge( $details, $item );
        $details['product_id'] = $product_id;
        $details['action']     = 'buy';
        $this->trigger( $subscriber_id, $details );
    }

}
