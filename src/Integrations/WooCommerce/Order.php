<?php

namespace Hizzle\Noptin\Integrations\WooCommerce;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for a WC order.
 *
 * @since 2.2.0
 */
class Order extends \Hizzle\Noptin\Objects\Record {

	/**
	 * @var \WC_Order The external object.
	 */
	public $external;

	/**
	 * Class constructor.
	 *
	 * @param mixed $external The external object.
	 */
	public function __construct( $external ) {
		$this->external = wc_get_order( $external );
	}

	/**
	 * Checks if the post exists.
	 * @return bool
	 */
	public function exists() {
		if ( ! is_a( $this->external, 'WC_Abstract_Order' ) ) {
			return false;
		}

		return $this->external->get_id() > 0;
	}

	/**
	 * Retrieves a given field's value.
	 *
	 * @param string $field The field.
	 * @param array  $args  The arguments.
	 * @return mixed $value The value.
	 */
	public function get( $field, $args = array() ) {

		if ( ! $this->exists() ) {
			return null;
		}

		// Meta.
		if ( 'meta' === $field ) {
			if ( ! empty( $args['key'] ) ) {
				return $this->external->get_meta( $args['key'], true );
			}

			return '';
		}

		// ID.
		if ( 'id' === strtolower( $field ) ) {
			return $this->external->get_id();
		}

		// Cross sells.
		if ( 'cross_sells' === strtolower( $field ) ) {
			return $this->get_record_ids_or_html(
				$this->get_order_cross_sells( $this->external ),
				$args,
				__NAMESPACE__ . '\Product::get_products_html'
			);
		}

		// Upsells.
		if ( 'upsells' === strtolower( $field ) ) {
			return $this->get_record_ids_or_html(
				$this->get_order_cross_sells( $this->external, 'get_upsell_ids' ),
				$args,
				__NAMESPACE__ . '\Product::get_products_html'
			);
		}

		// Order items.
		if ( 'items' === strtolower( $field ) ) {
			$items = array();

			foreach ( $this->external->get_items() as $item ) {
				/** @var \WC_Order_Item_Product $item */
				$items[] = $item->get_id();
			}

			return $this->get_record_ids_or_html(
				$items,
				$args,
				__NAMESPACE__ . '\Order_Item::get_order_items_html'
			);
		}

		// Order details.
		if ( 'details' === strtolower( $field ) ) {
			WC()->mailer();
			ob_start();
			do_action( 'woocommerce_email_order_details', $this->external, false, false, '' );
			return ob_get_clean();
		}

		// Customer details.
		if ( 'customer_details' === strtolower( $field ) ) {
			WC()->mailer();
			ob_start();
			do_action( 'woocommerce_email_customer_details', $this->external, false, false, '' );
			return ob_get_clean();
		}

		// Map fields.
		$map = array(
			'billing_address'  => 'formatted_billing_address',
			'shipping_address' => 'formatted_shipping_address',
			'admin_url'        => 'edit_order_url',
			'coupon_code'      => 'coupon_codes',
			'discount_total'   => 'total_discount',
			'view_url'         => 'view_order_url',
			'payment_url'      => 'checkout_payment_url',
		);

		if ( isset( $map[ $field ] ) ) {
			$field = $map[ $field ];
		}

		// Check if we have a method get_$field.
		$method = 'get_' . $field;
		if ( method_exists( $this->external, $method ) ) {
			$value = $this->external->{$method}();

			if ( is_a( $value, 'WC_DateTime' ) ) {
				return wc_format_datetime( $value );
			}

			return $value;
		}

		if ( method_exists( $this->external, $field ) ) {
			return $this->external->{$field}();
		}

		return null;
	}

	/**
	 * Get order cross sells.
	 *
	 * @param \WC_Order $order
	 * @return int[]
	 */
	protected function get_order_cross_sells( $order, $cb = 'get_cross_sell_ids' ) {
		$cross_sells = array();
		$in_order    = array();

		$items = $order->get_items();

		foreach ( $items as $item ) {
			/** @var \WC_Order_Item_Product $item */
			$product = $item->get_product();

			if ( $product ) {
				$in_order[]  = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
				$cross_sells = array_merge( $product->$cb(), $cross_sells );
			}
		}

		return array_diff( $cross_sells, $in_order );
	}

	/**
	 * Provides a related id.
	 *
	 * @param string $collection The collect.
	 */
	public function provide( $collection ) {
		if ( 'customer' === $collection ) {
			return $this->external->get_customer_id() > 0 ? $this->external->get_customer_id() : 0 - $this->external->get_id();
		}

		return parent::provide( $collection );
	}

	/**
	 * Formats a given field's value.
	 *
	 * @param mixed $raw_value The raw value.
	 * @param array $args The args.
	 * @return mixed $value The formatted value.
	 */
	public function format( $raw_value, $args ) {

		// Format prices.
		if ( 'price' === $args['format'] ) {
			return wc_price( $raw_value, array( 'currency' => $this->external->get_currency() ) );
		}

		return parent::format( $raw_value, $args );
	}
}
