<?php
/**
 * Contains the GetPaid lifetime value trigger.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when a GetPaid customer reaches a certain lifetime value.
 *
 * @since       1.4.1
 */
class Noptin_GetPaid_Lifetime_Value_Trigger extends Noptin_Abstract_Trigger {

    /**
     * @var Noptin_GetPaid The Noptin and GetPaid integration bridge.
     */
    private $bridge = null;

    /**
     * The GetPaid lifetime value trigger constructor.
     *
     * @since 1.4.1
     * @var Noptin_GetPaid $bridge The Noptin and GetPaid integration bridge.
     */
    public function __construct( $bridge ) {
        $this->bridge = $bridge;
        add_action( 'noptin_getpaid_integration_order_paid', array( $this, 'init_trigger' ), 10000, 2 );
    }

    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'getpaid_lifetime_value';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return __( 'GetPaid Lifetime Value', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __( 'Fired when a customer reaches a given lifetime value', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_rule_description( $rule ) {
        $settings = $rule->trigger_settings;

        if ( empty( $settings['lifetime_value'] ) ) {
            return __( 'When a subscriber reaches a GetPaid lifetime value', 'newsletter-optin-box' );
        }

        $value = wpinv_price( $settings['lifetime_value'] );
        return sprintf(
            __( 'When a subscriber reaches a GetPaid lifetime value of %s', 'newsletter-optin-box' ),
            $value
        );

    }

    /**
     * @inheritdoc
     */
    public function get_keywords() {
        return array(
            'getpaid',
            'lifetime value',
            'ecommerce',
            'invoicing'
        );
    }

    /**
     * @inheritdoc
     */
    public function get_settings() {
    
        return array(

            'lifetime_value'  => array(
                'el'          => 'input',
                'label'       => __( 'Lifetime Value', 'newsletter-optin-box' ),
                'description' => __( 'Enter the lifetime value without the currency symbol', 'newsletter-optin-box' ),
                'placeholder' => '100',
            ),

        );

    }

    /**
     * @inheritdoc
     */
    public function is_rule_valid_for_args( $rule, $args, $subscriber, $action ) {
        $settings = $rule->trigger_settings;

        // Ensure that we have a value.
        if ( empty( $settings['lifetime_value'] ) ) {
            return false;
        }

        // Fetch the user associated with the order.
        $user = $this->bridge->get_order_customer_user_id( $args['order_id'] );
        if ( empty( $user ) ) {
            $user = $this->bridge->get_order_customer_email( $args['order_id'] );
        }

        // Get the user's lifetime value.
        $user_value   = $this->bridge->get_total_spent( $user );
        $needed_value = (float) $settings['lifetime_value'];

        // Does the user meet the required lifetime value.
        if ( $user_value < $needed_value ) {
            return false;
        }

        // Ensure that the user reached this milestone in this specific order.
        $previous_total = $user_value - (float) $args['total'];

        return $previous_total < $needed_value;

    }

    /**
     * Trigger the GetPaid lifetime value automation rule whenever an invoice is paid for.
     *
     * @param int $invoice_id The invoice being paid.
     * @param int $subscriber_id The subscriber for the invoice.
     * @since 1.4.1
     */
    public function init_trigger( $invoice_id, $subscriber_id ) {
        $this->trigger( $subscriber_id, $this->bridge->get_order_details( $invoice_id ) );
    }

}
