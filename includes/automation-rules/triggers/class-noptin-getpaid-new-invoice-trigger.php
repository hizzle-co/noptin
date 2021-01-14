<?php
/**
 * Contains the GetPaid new invoice trigger.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when there is a new GetPaid invoice.
 *
 * @since       1.4.1
 */
class Noptin_GetPaid_New_Invoice_Trigger extends Noptin_Abstract_Trigger {

    /**
     * @var Noptin_GetPaid The Noptin and GetPaid integration bridge.
     */
    private $bridge = null;

    /**
     * The GetPaid new invoice trigger constructor.
     *
     * @since 1.4.1
     * @var Noptin_GetPaid $bridge The Noptin and GetPaid integration bridge.
     */
    public function __construct( $bridge ) {
        $this->bridge = $bridge;
        add_action( 'noptin_getpaid_integration_order', array( $this, 'init_trigger' ), 10000, 4 );
    }

    /**
     * @inheritdoc
     */
    public function get_id() {
        return 'getpaid_new_order';
    }

    /**
     * @inheritdoc
     */
    public function get_name() {
        return __( 'GetPaid New Invoice', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_description() {
        return __( 'Fired when there is a new GetPaid invoice', 'newsletter-optin-box' );
    }

    /**
     * @inheritdoc
     */
    public function get_rule_description( $rule ) {
        $settings = $rule->trigger_settings;
        $actions  = $this->get_invoice_actions();

        if ( empty( $settings['action'] ) || ! isset( $actions[ $settings['action'] ] ) ) {
            return __( 'When a subscriber receives a new GetPaid invoice', 'newsletter-optin-box' );
        }

        if ( ! empty( $settings['new_customer'] ) ) {
            return sprintf(
                __( "When a first-time customer's GetPaid invoice is %s", 'newsletter-optin-box' ),
                $actions[ $settings['action'] ]
            );
        }

        return sprintf(
            __( "When a subscriber's GetPaid invoice is %s", 'newsletter-optin-box' ),
            $actions[ $settings['action'] ]
        );

    }

    /**
     * @inheritdoc
     */
    public function get_keywords() {
        return array(
            'getpaid',
            'coupon',
            'ecommerce',
            'invoice'
        );
    }

    /**
     * Returns an array of GetPaid invoice actions.
     *
     * @return array
     */
    public function get_invoice_actions() {
        return array(
            'created'    => __( 'Created', 'newsletter-optin-box' ),
            'pending'    => __( 'Pending', 'newsletter-optin-box' ),
            'processing' => __( 'Processing', 'newsletter-optin-box' ),
            'held'       => __( 'Held', 'newsletter-optin-box' ),
            'paid'       => __( 'Paid', 'newsletter-optin-box' ),
            'completed'  => __( 'Completed', 'newsletter-optin-box' ),
            'refunded'   => __( 'Refunded', 'newsletter-optin-box' ),
            'cancelled'  => __( 'Cancelled', 'newsletter-optin-box' ),
            'failed'     => __( 'Failed', 'newsletter-optin-box' ),
            'deleted'    => __( 'Deleted', 'newsletter-optin-box' ),
        );
    }

    /**
     * @inheritdoc
     */
    public function get_settings() {
    
        return array(

            'action'          => array(
				'el'          => 'select',
				'label'       => __( 'Invoice status', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select invoice state', 'newsletter-optin-box' ),
				'options'     => $this->get_invoice_actions(),
				'description' => __( 'Select the invoice status for which this trigger should fire.', 'newsletter-optin-box' ),
            ),

            'new_customer'    => array(
                'type'        => 'checkbox_alt',
                'el'          => 'input',
                'label'       => __( 'New customers', 'newsletter-optin-box' ),
                'description' => __( 'Only fire for first time buyers?', 'newsletter-optin-box' ),
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

        // Are we firering for new customers only?
        if ( ! empty( $settings['new_customer'] ) ) {

            // Fetch the user associated with the order.
            $user = $this->bridge->get_order_customer_user_id( $args['order_id'] );
            if ( empty( $user ) ) {
                $user = $this->bridge->get_order_customer_email( $args['order_id'] );
            }

            return $this->bridge->get_order_count( $user ) === 1;
        }

        return true;

    }

    /**
     * Trigger the GetPaid new invoice automation rule whenever an invoice status changes.
     *
     * @param string $action The invoice state.
     * @param int $invoice_id The id of the invoice changing state.
     * @param int $subscriber_id The subscriber for the invoice.
     * @param Noptin_GetPaid $bridge The Noptin and GetPaid integration bridge.
     * @since 1.3.0
     */
    public function init_trigger( $action, $invoice_id, $subscriber_id, $bridge ) {
        $details           = $bridge->get_order_details( $invoice_id );
        $details['action'] = $action;
        $this->trigger( $subscriber_id, $details );
    }

}
