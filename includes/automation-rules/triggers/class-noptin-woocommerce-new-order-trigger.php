<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when there is a new order.
 *
 * @since       1.2.8
 */
class Noptin_WooCommerce_New_Order_Trigger extends Noptin_Abstract_Trigger {

	/**
	 * @var Noptin_WooCommerce The Noptin and WooCommerce integration bridge.
	 */
	private $bridge = null;

	/**
	 * Deprecated.
	 */
	public $depricated = true;

	/**
	 * @var string
	 */
	public $category = 'WooCommerce';

	/**
	 * @var string
	 */
	public $integration = 'woocommerce';

	/**
	 * Constructor.
	 *
	 * @since 1.3.0
	 * @var Noptin_WooCommerce $bridge The Noptin and WooCommerce integration bridge.
	 */
	public function __construct( $bridge ) {
		$this->bridge = $bridge;
		add_action( 'noptin_woocommerce_integration_order', array( $this, 'init_trigger' ), 10000, 4 );
	}

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
		return __( 'When there is a new WooCommerce order', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_description( $rule ) {
		$settings = $rule->trigger_settings;
		$actions  = $this->get_order_actions();

		if ( empty( $settings['action'] ) || ! isset( $actions[ $settings['action'] ] ) ) {
			return __( 'When a subscriber makes a new WooCommerce order', 'newsletter-optin-box' );
		}

		if ( ! empty( $settings['new_customer'] ) ) {
			return sprintf(
				// translators: %s is the order status.
				__( "When a first-time customer's WooCommerce order is %s", 'newsletter-optin-box' ),
				$actions[ $settings['action'] ]
			);
		}

		return sprintf(
			// translators: %s is the order status.
			__( "When a subscriber's WooCommerce order is %s", 'newsletter-optin-box' ),
			$actions[ $settings['action'] ]
		);

	}

	/**
	 * Returns an array of order actions.
	 *
	 * @return array
	 */
	public function get_order_actions() {
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

			'action'       => array(
				'el'          => 'select',
				'label'       => __( 'Order status', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select order state', 'newsletter-optin-box' ),
				'options'     => $this->get_order_actions(),
				'description' => __( 'Select the order status for which this trigger should fire.', 'newsletter-optin-box' ),
			),

			'new_customer' => array(
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
	 * Calls the trigger when an order state changes.
	 *
	 * @param string $action The order action.
	 * @param int $order_id The order being acted on.
	 * @param int $subscriber_id The subscriber for the order.
	 * @param Noptin_WooCommerce $bridge The Noptin and WC integration bridge.
	 * @since 1.3.0
	 */
	public function init_trigger( $action, $order_id, $subscriber_id, $bridge ) {
		$details           = $bridge->get_order_details( $order_id );
		$details['action'] = $action;
		$this->trigger( $subscriber_id, $details );
	}

}
