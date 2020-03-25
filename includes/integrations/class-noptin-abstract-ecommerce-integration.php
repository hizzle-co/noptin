<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Base E-Commerce integration
 *
 * @since       1.2.6
 */
abstract class Noptin_Abstract_Ecommerce_Integration extends Noptin_Abstract_Integration {

	/**
	 * @var string The context for subscribers.
	 * @since 1.2.6
	 */
	public $context = 'customers';

	/**
	 * @var string The label for order.
	 * @since 1.2.6
	 */
	public $order_label = '';

	/**
	 * @var int The priority for hooks.
	 * @since 1.2.6
	 */
	public $priority = 40;

	/**
	 * @var string type of integration.
	 * @since 1.2.6
	 */
	public $integration_type = 'ecommerce';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->context     = __( 'customers', 'newsletter-optin-box' );
		$this->order_label = __( 'Orders', 'newsletter-optin-box' );
		parent::__construct();
	}

	/**
	 * Adds/Updates an order subscriber.
	 * 
	 * @param int $order_id The order id.
	 * @since 1.2.6
	 * @return int|null The subscriber id.
	 */
	public function add_order_subscriber( $order_id ) {

		// Fetch the subscriber id and order customer details.
		$subscriber_id      = $this->get_order_subscriber( $order_id );
		$subscriber_details = $this->get_order_customer_details( $order_id, empty( $subscriber_id ) );

		// Either create a new subscriber...
		if ( empty( $subscriber_id ) ) {

			// Should we process the subsriber?
			if ( ! $this->triggered( $order_id ) ) {
				return null;
			}

			return $this->add_subscriber( $subscriber_details, $order_id );
		}

		// Or update an existing one.
		return $this->update_subscriber( $subscriber_id, $subscriber_details, $order_id );

	}

	/**
	 * Returns an array of all complete orders.
	 *
	 * @since 1.2.6
	 * @return array
	 */
	public function get_orders() {
		return array();
	}

	/**
	 * Returns an array of order details.
	 *
	 * @param int $order_id The order id.
	 * @since 1.2.6
	 * @return array
	 */
	public function get_order_details( $order_id ) {
		return array(
			'total'        => 0,
			'tax'          => 0,
			'fees'         => 0,
			'discount'     => 0,
			'url'          => '',
			'id'           => '',
			'title'        => '',
			'date_created' => '',
			'date_paid'    => '',
			'items'        => array(
				array(
					'id'       => '',
					'name'     => '',
					'price'    => '',
					'quantity' => '',
				)
			)

		);
	}

	/**
	 * Returns an array of customer details.
	 *
	 * @param int $order_id The order id.
	 * @param bool $existing_subscriber Whether this is an existing subscriber or not.
	 * @since 1.2.6
	 * @return array
	 */
	public function get_order_customer_details( $order_id, $existing_subscriber = false ) {
		return array();
	}

	/**
	 * Returns the email address of the customer associated with an order.
	 *
	 * @param int $order_id The order id.
	 * @since 1.2.6
	 * @return string
	 */
	public function get_order_customer_email( $order_id ) {
		return '';
	}

	/**
	 * Returns the id of the customer associated with an order.
	 *
	 * @param int $order_id The order id.
	 * @since 1.2.6
	 * @return int
	 */
	public function get_order_customer_user_id( $order_id ) {
		return 0;
	}

	/**
	 * Returns a subcriber id responsible for a payment.
	 * 
	 * @param int $order_id The order id.
	 * @since 1.2.6
	 * @return int|null The subscriber id.
	 */
	public function get_order_subscriber( $order_id ) {

		// Fetch the payment.
		$email = $this->get_order_customer_email( $order_id );
		if ( empty( $email ) ) {
			return null;
		}

		return get_noptin_subscriber_id_by_email( $email );

	}

	/**
	 * Fires a specific hook based on an order status.
	 * 
	 * @param string $action The order action.
	 * @param int $order_id The order id.
	 */
	public function fire_order_hook( $action, $order_id ) {

		$subscriber_id = $this->get_order_subscriber( $order_id );

		// Only fired when there is actually a subcsriber.
		if ( $subscriber_id ) {
			do_action( "noptin_integration_order", $action, $order_id, $subscriber_id, $this );
			do_action( "noptin_integration_order_$action", $order_id, $subscriber_id, $this );
			do_action( "noptin_{$this->slug}_integration_order_$action", $order_id, $subscriber_id, $this );
			do_action( "noptin_{$this->slug}_integration_order", $action, $order_id, $subscriber_id, $this );
		}

	}

	/**
	 * Fired when an order is processed.
	 * 
	 * @param int $order_id The order id.
	 * @since 1.2.6
	 */
	public function checkout_processed( $order_id ) {
		$this->fire_order_hook( 'processed', $order_id );
	}

	/**
	 * Fired when an order is created.
	 * 
	 * @param int $order_id The order id.
	 * @since 1.2.6
	 */
	public function order_created( $order_id ) {
		$this->fire_order_hook( 'created', $order_id );
	}

	/**
	 * Fired when an order is updated.
	 * 
	 * @param int $order_id The order id.
	 * @since 1.2.6
	 */
	public function order_updated( $order_id ) {
		$this->fire_order_hook( 'updated', $order_id );
	}

	/**
	 * Fired when an order is deleted.
	 * 
	 * @param int $order_id The order id.
	 * @since 1.2.6
	 */
	public function order_deleted( $order_id ) {
		$this->fire_order_hook( 'deleted', $order_id );
	}

	/**
	 * Fired when an order is completed.
	 * 
	 * @param int $order_id The order id.
	 * @since 1.2.6
	 */
	public function order_completed( $order_id ) {
		$this->fire_order_hook( 'completed', $order_id );
	}

	/**
	 * Fired when an order is refunded.
	 * 
	 * @param int $order_id The order id.
	 * @since 1.2.6
	 */
	public function order_refunded( $order_id ) {
		$this->fire_order_hook( 'refunded', $order_id );
	}

	/**
	 * Fired when an order is paid for.
	 * 
	 * @param int $order_id The order id.
	 * @since 1.2.6
	 */
	public function order_paid( $order_id ) {
		$this->fire_order_hook( 'paid', $order_id );
	}

	/**
	 * Returns an array of order events.
	 * 
	 * @since 1.2.6
	 */
	public function get_order_events() {
		return array(
			'created'   => __( 'An order is completed', 'newsletter-optin-box' ),
			'updated'   => __( 'An order is updated', 'newsletter-optin-box' ),
			'paid'      => __( 'An order is paid', 'newsletter-optin-box' ),
			'refunded'  => __( 'An order is refunded', 'newsletter-optin-box' ),
			'completed' => __( 'An order is completed', 'newsletter-optin-box' ),
			'deleted'   => __( 'An order is deleted', 'newsletter-optin-box' ),
		);
	}

	/**
	 * Returns an array of all published products.
	 *
	 * @since 1.2.6
	 * @return array
	 */
	public function get_products() {
		return array();
	}

	/**
	 * Returns an array of product details.
	 *
	 * @param int $product_id The product id.
	 * @since 1.2.6
	 * @return array
	 */
	public function get_product_details( $product_id ) {
		return array(
			'id'       => '',
			'name'     => '',
			'price'    => '',
		);
	}

	/**
	 * Fires a specific hook based on a product event.
	 * 
	 * @param string $action The product action.
	 * @param int $product_id The product id.
	 * @since 1.2.6
	 */
	public function fire_product_hook( $action, $product_id ) {

		$product = $this->get_product_details( $product_id );

		// Only fired when there is actually a product.
		if ( ! empty( $product['id'] ) ) {
			do_action( "noptin_integration_product", $action, $product_id, $product, $this );
			do_action( "noptin_integration_product_$action", $product_id, $product, $this );
			do_action( "noptin_{$this->slug}_integration_product_$action", $product_id, $product, $this );
			do_action( "noptin_{$this->slug}_integration_product", $action, $product_id, $product, $this );
		}

	}

	/**
	 * Fired when a product is created.
	 * 
	 * @param int $product_id The product id.
	 * @since 1.2.6
	 */
	public function product_created( $product_id ) {
		$this->fire_product_hook( 'created', $product_id );
	}

	/**
	 * Fired when a product is updated.
	 * 
	 * @param int $product_id The product id.
	 * @since 1.2.6
	 */
	public function product_updated( $product_id ) {
		$this->fire_product_hook( 'updated', $product_id );
	}

	/**
	 * Fired when a product is deleted.
	 * 
	 * @param int $product_id The product id.
	 * @since 1.2.6
	 */
	public function product_deleted( $product_id ) {
		$this->fire_product_hook( 'deleted', $product_id );
	}

}
