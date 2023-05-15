<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when a product is purchase.
 *
 * @since       1.2.8
 */
class Noptin_WooCommerce_Product_Purchase_Trigger extends Noptin_WooCommerce_Trigger {

	/**
	 * @var bool
	 */
	public $depricated = true;

	/**
	 * Constructor.
	 *
	 * @since 1.3.0
	 */
	public function __construct() {
		add_action( 'woocommerce_order_refunded', array( $this, 'init_refund_trigger' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'init_buy_trigger' ) );
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
		return __( 'When a WooCommerce Product is bought or refunded', 'newsletter-optin-box' );
	}

	/**
	 * Retrieve the trigger's rule table description.
	 *
	 * @since 1.11.9
	 * @param Noptin_Automation_Rule $rule
	 * @return array
	 */
	public function get_rule_table_description( $rule ) {
		$settings = $rule->trigger_settings;

		// Ensure we have a product.
		if ( empty( $settings['product_id'] ) ) {
			return sprintf(
				'<span class="noptin-rule-error">%s</span>',
				esc_html__( 'Error: Product not specified', 'newsletter-optin-box' )
			);
		}

		$product = wc_get_product( $settings['product_id'] );

		if ( ! $product ) {
			return sprintf(
				'<span class="noptin-rule-error">%s</span>',
				esc_html__( 'Error: Product not found', 'newsletter-optin-box' )
			);
		}

		$meta  = array(
			esc_html__( 'Product', 'newsletter-optin-box' ) => $product->get_name(),
			esc_html__( 'Action', 'newsletter-optin-box' ) => 'buy' === $settings['action'] ? esc_html__( 'Buy', 'newsletter-optin-box' ) : esc_html__( 'Refund', 'newsletter-optin-box' ),
		);

		return $this->rule_trigger_meta( $meta, $rule ) . parent::get_rule_table_description( $rule );
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {

		$products = wc_get_products(
			array(
				'limit'  => -1,
				'status' => 'publish',
				'parent' => 0,
			)
		);

		$prepared = array();

		foreach ( $products as $product ) {
			$prepared[ $product->get_id() ] = $product->get_name();
		}

		return array(

			'product_id' => array(
				'el'          => 'select',
				'options'     => $prepared,
				'label'       => __( 'Product', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select a WooCommerce product', 'newsletter-optin-box' ),
				'default'     => current( array_keys( $prepared ) ),
			),

			'action'     => array(
				'el'          => 'select',
				'options'     => array(
					'buy'    => __( 'The product is bought', 'newsletter-optin-box' ),
					'refund' => __( 'The product is refunded', 'newsletter-optin-box' ),
				),
				'label'       => __( 'Action', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select the product action', 'newsletter-optin-box' ),
				'default'     => 'buy',
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
		if ( empty( $settings['product_id'] ) || (int) $settings['product_id'] !== (int) $args['product_id'] ) {
			return false;
		}

		return true;

	}

	/**
     * Returns an array of known smart tags.
     *
     * @since 1.9.0
     * @return array
     */
    public function get_known_smart_tags() {

		return array_merge(
			parent::get_known_smart_tags(),
			$this->get_product_smart_tags(),
			$this->get_order_smart_tags(),
			$this->get_customer_smart_tags()
		);
    }

	/**
	 * Fires when a product is refunded.
	 *
	 * @param int|WC_Order $order_id The order being acted on.
	 * @since 1.9.0
	 */
	public function init_refund_trigger( $order_id ) {
		$this->maybe_trigger( $order_id, 'refund' );
	}

	/**
	 * Fires when a product is bought.
	 *
	 * @param int|WC_Order $order_id The order being acted on.
	 * @since 1.9.0
	 */
	public function init_buy_trigger( $order_id ) {
		$this->maybe_trigger( $order_id, 'buy' );
	}

	/**
	 * Fires when a product is bought or refunded.
	 *
	 * @param int|WC_Order $order_id The order being acted on.
	 * @param string       $action   The action being performed.
	 * @since 1.9.0
	 */
	public function maybe_trigger( $order_id, $action ) {

		if ( is_numeric( $order_id ) ) {
			$order = wc_get_order( $order_id );
		} else {
			$order = $order_id;
		}

		// Ensure we have an order.
		if ( empty( $order ) || ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		// Prepare the order customer.
		if ( $order->get_customer_id() ) {
			$customer = new WC_Customer( $order->get_customer_id() );
		} else {
			$customer = WC()->customer;
		}

		// Loop through the order items.
		foreach ( $order->get_items() as $item ) {

			// Ensure we have a product.
			/** @var WC_Order_Item_Product $item */
			$product = $item->get_product();
			if ( empty( $product ) ) {
				continue;
			}

			// Ensure we have a product id.
			$product_id = $product->get_id();
			if ( empty( $product_id ) ) {
				continue;
			}

			// Attach WC hooks.
			$args = array_merge(
				$this->before_trigger_wc( $order, $customer, $product ),
				array(
					'order_id'    => $order->get_id(),
					'product_id'  => $product_id,
					'product_sku' => $product->get_sku(),
					'product_qty' => $item->get_quantity(),
					'action'      => $action,
				)
			);

			// Trigger the event.
			$this->trigger( $customer, $args );

			// Detach WC hooks.
			$this->after_trigger_wc( $args );
		}
	}

	/**
	 * Prepares email test data.
	 *
	 * @since 1.11.0
	 * @param Noptin_Automation_Rule $rule
	 * @return Noptin_Automation_Rules_Smart_Tags
	 * @throws Exception
	 */
	public function get_test_smart_tags( $rule ) {

		/** @var Noptin_WooCommerce_Automated_Email_Type[] $email_types */
		$email_types = noptin()->emails->automated_email_types->types;

		$email_types['woocommerce_product_purchase']->_prepare_test_data();

		$args = array(
			'email'  => $email_types['woocommerce_product_purchase']->order->get_billing_email(),
			'action' => 'buy',
		);

		$args = $this->prepare_trigger_args( $email_types['woocommerce_product_purchase']->customer, $args );

		return $args['smart_tags'];
	}

	/**
	 * Serializes the trigger args.
	 *
	 * @since 1.11.1
	 * @param array $args The args.
	 * @return false|array
	 */
	public function serialize_trigger_args( $args ) {
		return array(
			'order_id'   => $args['order_id'],
			'product_id' => $args['product_id'],
		);
	}

	/**
	 * Unserializes the trigger args.
	 *
	 * @since 1.11.1
	 * @param array $args The args.
	 * @return array|false
	 */
	public function unserialize_trigger_args( $args ) {

		$order = wc_get_order( $args['order_id'] );

		if ( empty( $order ) || ! is_a( $order, 'WC_Order' ) ) {
			throw new Exception( 'The order no longer exists' );
		}

		$customer = new WC_Customer( $order->get_customer_id() );

		if ( ! $customer->get_id() ) {
			$customer->set_email( $order->get_billing_email() );
			$customer->set_billing_email( $order->get_billing_email() );
		}

		// Check the status.
		if ( $order->is_paid() ) {
			$action = 'buy';
		} elseif ( $order->has_status( 'refunded' ) ) {
			$action = 'refund';
		} else {
			throw new Exception( 'The order status is not valid' );
		}

		// Loop through the order items.
		foreach ( $order->get_items() as $item ) {

			// Ensure we have a product.
			/** @var WC_Order_Item_Product $item */
			$product = $item->get_product();
			if ( empty( $product ) ) {
				continue;
			}

			// Ensure we have a product id.
			$product_id = $product->get_id();
			if ( empty( $product_id ) ) {
				continue;
			}

			if ( absint( $product_id ) !== absint( $args['product_id'] ) ) {
				continue;
			}

			// Attach WC hooks.
			$args = array_merge(
				$this->before_trigger_wc( $order, $customer, $product ),
				array(
					'order_id'    => $order->get_id(),
					'product_id'  => $product_id,
					'product_sku' => $product->get_sku(),
					'product_qty' => $item->get_quantity(),
					'action'      => $action,
				)
			);

			// Prepare the trigger args.
			return $this->prepare_trigger_args( $customer, $args );
		}

		throw new Exception( 'The order item no longer exists' );
	}
}
