<?php
/**
 * Contains the GetPaid item purchase trigger.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when a specific GetPaid item is purchased.
 *
 * @since       1.4.1
 */
class Noptin_GetPaid_Item_Purchase_Trigger extends Noptin_Abstract_Trigger {

    /**
     * @var Noptin_GetPaid The Noptin and GetPaid integration bridge.
     */
    private $bridge = null;

    /**
     * The GetPaid item purchased automation rule trigger constructor.
     *
     * @since 1.4.1
     * @var Noptin_GetPaid $bridge The Noptin and GetPaid integration bridge.
     */
    public function __construct( $bridge ) {
        $this->bridge = $bridge;
        add_action( 'noptin_getpaid_integration_product_refund', array( $this, 'init_refund_trigger' ), 10000, 5 );
        add_action( 'noptin_getpaid_integration_product_buy', array( $this, 'init_buy_trigger' ), 10000, 5 );
    }

    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'getpaid_item_purchase';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return __( 'GetPaid Item Purchase', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __( 'Fired when a GetPaid Item is bought', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_rule_description( $rule ) {

        $settings = $rule->trigger_settings;

        // Are we filtering by subscription method?
        if ( empty( $settings['action'] ) || empty( $settings['product_id'] ) ) {
            return __( 'When a subscriber buys a GetPaid item', 'newsletter-optin-box' );
        }

        $product = new WPInv_Item( $settings['product_id'] );
		if ( ! $product->exists() ) {
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
    public function get_keywords() {
        return array(
            'getpaid',
            'item',
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
                'label'       => __( 'Item', 'newsletter-optin-box' ),
                'placeholder' => __( 'Select a GetPaid item', 'newsletter-optin-box' ),
            ),

            'action' => array(
                'el'          => 'select',
                'options'     => array(
                    'buy'     => __( 'The item is bought', 'newsletter-optin-box' ),
                    'refund'  => __( 'The item is refunded', 'newsletter-optin-box' ),
                ),
                'label'       => __( 'State', 'newsletter-optin-box' ),
                'placeholder' => __( 'Select the item state', 'newsletter-optin-box' ),
                'default'     => 'buy'
            ),

            'first_time'    => array(
                'type'        => 'checkbox_alt',
                'el'          => 'input',
                'label'       => __( 'New buyer', 'newsletter-optin-box' ),
                'description' => __( 'Only fire the first time someone buys this item?', 'newsletter-optin-box' ),
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

            // Fetch the user associated with the invoice.
            $user = $this->bridge->get_order_customer_user_id( $args['order_id'] );
            if ( empty( $user ) ) {
                $user = $this->bridge->get_order_customer_email( $args['order_id'] );
            }

            return $this->bridge->get_product_purchase_count( $user, $args['product_id'] ) === 1;

        }

        return true;

    }

    /**
     * Trigger the GetPaid item purchased automation rule whenever an item is refunded.
     *
     * @param string $item_id The id of the item being refunded.
     * @param array $item The invoice item being refunded.
     * @param int $invoice_id The associated invoice.
     * @param int $subscriber_id The subscriber for the invoice.
     * @param Noptin_GetPaid $bridge The Noptin and GetPaid integration bridge.
     * @since 1.4.1
     */
    public function init_refund_trigger( $item_id, $item, $invoice_id, $subscriber_id, $bridge ) {
        $details               = $bridge->get_order_details( $invoice_id );
        $details               = array_merge( $details, $item );
        $details['action']     = 'refund';
        $details['product_id'] = $item_id;
        $this->trigger( $subscriber_id, $details );
    }

    /**
     * Trigger the GetPaid item purchased automation rule whenever an item is paid for.
     *
     * @param string $item_id The id of the item being paid for.
     * @param array $item The invoice item being paid for.
     * @param int $invoice_id The associated invoice.
     * @param int $subscriber_id The subscriber for the invoice.
     * @param Noptin_GetPaid $bridge The Noptin and GetPaid integration bridge.
     * @since 1.4.1
     */
    public function init_buy_trigger( $item_id, $item, $invoice_id, $subscriber_id, $bridge ) {
        $details               = $bridge->get_order_details( $invoice_id );
        $details               = array_merge( $details, $item );
        $details['product_id'] = $item_id;
        $details['action']     = 'buy';
        $this->trigger( $subscriber_id, $details );
    }

}
