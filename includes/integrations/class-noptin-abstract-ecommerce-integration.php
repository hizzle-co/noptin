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
	 * @var string The product's post type in case this integration saves products as custom post types.
	 * @since 1.3.0
	 */
	public $product_post_type = array( 'product', 'product_variation' );

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
		parent::__construct();
		$this->context     = __( 'customers', 'newsletter-optin-box' );
		$this->order_label = __( 'Orders', 'newsletter-optin-box' );
	}

	/**
	 * Setup hooks in case the integration is enabled.
	 *
	 * @since 1.3.0
	 */
	public function initialize() {

		if ( empty( $this->product_post_type ) ) {
			return;
		}

		// Product actions.
		add_action( 'wp_trash_post', array( $this, 'product_trashed' ), $this->priority );
		add_action( 'untrashed_post', array( $this, 'product_untrashed' ), $this->priority );
		add_action( 'before_delete_post', array( $this, 'product_deleted' ), $this->priority );
		add_action( 'save_post', array( $this, 'product_updated' ), $this->priority );

	}

	/**
	 * Returns the order id given an order object/id.
	 * 
	 * @param int|object $order_id_or_object The order id or object.
	 * @since 1.4.1
	 * @return int The order id.
	 */
	public function prepare_order_id( $order_id_or_object ) {

		if ( is_numeric( $order_id_or_object ) ) {
			return (int) $order_id_or_object;
		}

		if ( is_object( $order_id_or_object ) && is_callable( array( $order_id_or_object, 'get_id' ) ) ) {
			return $order_id_or_object->get_id();
		}

		return 0;
	}

	/**
	 * Adds/Updates an order subscriber.
	 * 
	 * @param int $order_id The order id or object.
	 * @since 1.2.6
	 * @since 1.4.1 $order_id now accepts order object.
	 * @return int|null The subscriber id.
	 */
	public function add_order_subscriber( $order_id ) {

		// Fetch the subscriber id and order customer details.
		$order_id           = $this->prepare_order_id( $order_id );
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
	 * Returns a given customer's order count.
	 *
	 * @param string|int $customer_id_or_email The customer's id or email.
	 * @since 1.3.0
	 * @return int
	 */
	public function get_order_count( $customer_id_or_email ) {
		return 0;
	}

	/**
	 * Returns a given customer's total spent.
	 *
	 * @param string|int $customer_id_or_email The customer's id or email.
	 * @since 1.3.3
	 * @return int
	 */
	public function get_total_spent( $customer_id_or_email ) {
		return 0;
	}

	/**
	 * Returns an array of order details.
	 *
	 * @param int $order_id The order id or object.
	 * @since 1.4.1 $order_id now accepts order object.
	 * @since 1.2.6
	 * @return array
	 */
	public function get_order_details( $order_id ) {
		$order_id          = $this->prepare_order_id( $order_id );
		return array(
			'id'		   => $order_id,
			'total'        => 0,
			'tax'          => 0,
			'fees'         => 0,
			'currency'     => 'USD',
			'discount'     => 0,
			'edit_url'     => '',
			'view_url'     => '',
			'pay_url'      => '',
			'title'        => '',
			'date_created' => '',
			'date_paid'    => '',
			'status'       => 'pending',
			'items'        => array(
				array(
					'item_id'      => '',
					'product_id'   => '',
					'variation_id' => '',
					'name'         => '',
					'price'        => '',
					'quantity'     => '',
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
	 * @since 1.3.1
	 * @since 1.4.1 $order_id Now accepts the order object.
	 * @param string $action The order action.
	 * @param int|object $order_id The order id or object.
	 */
	public function fire_order_hook( $action, $order_id ) {

		$order_id      = $this->prepare_order_id( $order_id );
		$subscriber_id = $this->get_order_subscriber( $order_id );

		// Only fired when there is actually a subcsriber.
		if ( $subscriber_id ) {
			do_action( "noptin_integration_order", $action, $order_id, $subscriber_id, $this );
			do_action( "noptin_integration_order_$action", $order_id, $subscriber_id, $this );
			do_action( "noptin_ecommerce_integration_order_$action", $order_id, $subscriber_id, $this );
			do_action( "noptin_ecommerce_integration_order", $action, $order_id, $subscriber_id, $this );
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
		$this->fire_order_hook( 'checkout_processed', $order_id );
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

		// Fire bought hooks for individual products.
		$details = $this->get_order_details( $order_id );
		foreach ( $details['items'] as $item ) {
			$this->product_bought( $item['product_id'], $item, $order_id );
		}
	}

	/**
	 * Fired when an order is refunded.
	 * 
	 * @param int $order_id The order id.
	 * @since 1.2.6
	 */
	public function order_refunded( $order_id ) {
		$this->fire_order_hook( 'refunded', $order_id );

		// Fire refunded hooks for individual products.
		$details = $this->get_order_details( $order_id );
		foreach ( $details['items'] as $item ) {
			$this->product_refunded( $item['product_id'], $item, $order_id );
		}
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
	 * Fired when an order is fails payment.
	 * 
	 * @param int $order_id The order id.
	 * @since 1.3.0
	 */
	public function order_failed( $order_id ) {
		$this->fire_order_hook( 'failed', $order_id );
	}

	/**
	 * Fired when an order is cancelled.
	 * 
	 * @param int $order_id The order id.
	 * @since 1.3.0
	 */
	public function order_cancelled( $order_id ) {
		$this->fire_order_hook( 'cancelled', $order_id );
	}

	/**
	 * Fired when an order is held.
	 * 
	 * @param int $order_id The order id.
	 * @since 1.3.0
	 */
	public function order_held( $order_id ) {
		$this->fire_order_hook( 'held', $order_id );
	}

	/**
	 * Fired when an order is marked as processing.
	 * 
	 * @param int $order_id The order id.
	 * @since 1.3.0
	 */
	public function order_processing( $order_id ) {
		$this->fire_order_hook( 'processing', $order_id );
	}

	/**
	 * Fired when an order is marked as pending.
	 * 
	 * @param int $order_id The order id.
	 * @since 1.3.0
	 */
	public function order_pending( $order_id ) {
		$this->fire_order_hook( 'pending', $order_id );
	}

	/**
	 * Returns an array of all published products.
	 *
	 * @since 1.2.6
	 * @return array
	 */
	public function get_products() {
		// Return self::get_product_details[]
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
			'id'                 => '',
			'name'               => '',
			'description'        => '',
			'url'                => '',
			'price'              => '',
			'type'               => '',
			'sku'                => '',
			'inventory_quantity' => '',
			'images'             => array(), // array of urls.
			'variations'         => array(), // array of variations, should look similar to the parent array minus the variations key.
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
			do_action( "noptin_ecommerce_integration_product_$action", $product_id, $product, $this );
			do_action( "noptin_ecommerce_integration_product", $action, $product_id, $product, $this );
			do_action( "noptin_{$this->slug}_integration_product_$action", $product_id, $product, $this );
			do_action( "noptin_{$this->slug}_integration_product", $action, $product_id, $product, $this );
		}

	}

	/**
	 * Fired when a product is bought.
	 * 
	 * @param int $product_id The product id.
	 * @param array $item the item details
	 * @param int $order_id the purchase order id
	 * @since 1.3.0
	 */
	public function product_bought( $product_id, $item, $order_id ) {
		$subscriber_id = $this->get_order_subscriber( $order_id );

		if ( $subscriber_id ) {
			do_action( "noptin_ecommerce_integration_product_buy", $product_id, $item, $order_id, $subscriber_id, $this );
			do_action( "noptin_{$this->slug}_integration_product_buy", $product_id, $item, $order_id, $subscriber_id, $this );
		}

	}

	/**
	 * Fired when a product is refunded.
	 * 
	 * @param int $product_id The product id.
	 * @param array $item the item details
	 * @param int $order_id the purchase order id
	 * @since 1.3.0
	 */
	public function product_refunded( $product_id, $item, $order_id ) {
		$subscriber_id = $this->get_order_subscriber( $order_id );

		if ( $subscriber_id ) {
			do_action( "noptin_ecommerce_integration_product_refund", $product_id, $item, $order_id, $subscriber_id, $this );
			do_action( "noptin_{$this->slug}_integration_product_refund", $product_id, $item, $order_id, $subscriber_id, $this );
		}
	}

	/**
	 * Checks the post type of a product.
	 * 
	 * @param int $product_id The product id.
	 * @since 1.3.0
	 * @return bool
	 */
	public function is_product_post_type( $product_id ) {

		if ( empty( $product_id ) || empty( $this->product_post_type ) ) {
			return false;
		}

		if ( is_array( $this->product_post_type ) ) {
			return in_array( get_post_type( $product_id ), $this->product_post_type );
		}

		return get_post_type( $product_id ) == $this->product_post_type;

	}

	/**
	 * Fired when a product is updated.
	 * 
	 * @param int $product_id The product id.
	 * @since 1.2.6
	 */
	public function product_updated( $product_id ) {
		if ( $this->is_product_post_type( $product_id ) ) {
			$this->fire_product_hook( 'updated', $product_id );
		}
	}

	/**
	 * Fired when a product is deleted.
	 * 
	 * @param int $product_id The product id.
	 * @since 1.2.6
	 */
	public function product_deleted( $product_id ) {
		if ( $this->is_product_post_type( $product_id ) ) {
			$this->fire_product_hook( 'deleted', $product_id );
		}
	}

	/**
	 * Fired when a product is added to the trash.
	 * 
	 * @param int $product_id The product id.
	 * @since 1.3.0
	 */
	public function product_trashed( $product_id ) {
		if ( $this->is_product_post_type( $product_id ) ) {
			$this->fire_product_hook( 'trashed', $product_id );
		}
	}

	/**
	 * Fired when a product is added removed from the trash.
	 * 
	 * @param int $product_id The product id.
	 * @since 1.3.0
	 */
	public function product_untrashed( $product_id ) {
		if ( $this->is_product_post_type( $product_id ) ) {
			$this->fire_product_hook( 'untrashed', $product_id );
		}
	}

}
