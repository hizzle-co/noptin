<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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

		// Map subscriber fields to customer fields.
		add_filter( 'noptin_get_custom_fields_map_settings', array( $this, 'add_field_map_settings' ), $this->priority );
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
		$subscriber         = array(
			'source' => $this->subscriber_via,
		);

		foreach ( array( 'email', 'name', 'wp_user_id', 'ip_address', 'first_name', 'last_name', 'source' ) as $key ) {
			if ( isset( $subscriber_details[ $key ] ) ) {
				$subscriber[ $key ] = $subscriber_details[ $key ];
			}
		}

		foreach ( get_noptin_custom_fields() as $custom_field ) {
			if ( ! empty( $custom_field[ $this->slug ] ) && isset( $subscriber_details[ $custom_field[ $this->slug ] ] ) ) {
				$subscriber[ $custom_field['merge_tag'] ] = $subscriber_details[ $custom_field[ $this->slug ] ];
			}
		}

		// Either create a new subscriber...
		if ( empty( $subscriber_id ) ) {

			// Should we process the subscriber?
			if ( ! $this->triggered( $order_id ) ) {
				return null;
			}

			return $this->add_subscriber( $subscriber, $subscriber_details );
		}

		// Or update an existing one.
		return $this->update_subscriber( $subscriber_id, $subscriber, $subscriber_details );
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
			'id'           => $order_id,
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
				),
			),

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
	 * Fired when an order is completed.
	 *
	 * @param int $order_id The order id.
	 * @since 1.2.6
	 */
	public function order_completed( $order_id ) {

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

		// Fire refunded hooks for individual products.
		$details = $this->get_order_details( $order_id );
		foreach ( $details['items'] as $item ) {
			$this->product_refunded( $item['product_id'], $item, $order_id );
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
			do_action( 'noptin_ecommerce_integration_product_buy', $product_id, $item, $order_id, $subscriber_id, $this );
			do_action( "noptin_{$this->slug}_integration_product_buy", $product_id, $item, $order_id, $subscriber_id, $this );
		}

		do_action( "noptin_{$this->slug}_product_buy", $product_id, $item, $order_id, $this );
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
			do_action( 'noptin_ecommerce_integration_product_refund', $product_id, $item, $order_id, $subscriber_id, $this );
			do_action( "noptin_{$this->slug}_integration_product_refund", $product_id, $item, $order_id, $subscriber_id, $this );
		}

		do_action( "noptin_{$this->slug}_product_refund", $product_id, $item, $order_id, $this );
	}

	/**
	 * Returns an array of available customer fields.
	 *
	 * @return array
	 * @since 1.5.5
	 */
	protected function available_customer_fields() {
		return array();
	}

	/**
	 * Registers integration options.
	 *
	 * @since 3.2.0
	 * @param array $settings Current Noptin settings.
	 * @return array
	 */
	public function add_field_map_settings( $settings ) {
		$customer_fields = $this->available_customer_fields();

		if ( ! empty( $customer_fields ) ) {
			$settings[ $this->slug ] = array(
				'el'          => 'select',
				'label'       => sprintf(
					// translators: %s is the integration name.
					__( '%s Equivalent', 'newsletter-optin-box' ),
					$this->name
				),
				'conditions'  => array(
					array(
						'key'      => 'type',
						'operator' => '!includes',
						'value'    => \Noptin_Custom_Fields::predefined_fields(),
					),
				),
				'options'     => $customer_fields,
				'placeholder' => __( 'Not Mapped', 'newsletter-optin-box' ),
			);
		}

		return $settings;
	}
}
