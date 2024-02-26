<?php

namespace Hizzle\Noptin\Integrations\WooCommerce;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for a WC order item.
 *
 * @since 3.0.0
 */
class Order_Item extends \Hizzle\Noptin\Objects\Record {

	/**
	 * @var \WC_Order_Item_Product The external object.
	 */
	public $external;

	/**
	 * Class constructor.
	 *
	 * @param mixed $external The external object.
	 */
	public function __construct( $external ) {
		$this->external = \WC_Order_Factory::get_order_item( $external );
	}

	/**
	 * Checks if the post exists.
	 * @return bool
	 */
	public function exists() {
		if ( ! is_a( $this->external, 'WC_Order_Item_Product' ) ) {
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

		// Attribute.
		if ( 'attribute' === strtolower( $field ) ) {
			if ( ! empty( $args['key'] ) ) {
				$product = $this->external->get_product();
				return $product ? $product->get_attribute( $args['key'] ) : '';
			}

			return '';
		}

		// ID.
		if ( 'id' === strtolower( $field ) ) {
			return $this->external->get_id();
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
	 * Get product html to display.
	 *
	 * @param array $args
	 * @param int[] $item_ids
	 *
	 * @return string
	 */
	public static function get_order_items_html( $item_ids, $args ) {
		$products = array();

		foreach ( $item_ids as $item_id ) {
			$item = \WC_Order_Factory::get_order_item( $item_id );

			if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
				continue;
			}

			/** @var \WC_Order_Item_Product $item */
			$products[] = $item->get_product();
		}

		$template = isset( $args['style'] ) ? $args['style'] : 'grid';
		$products = array_filter( $products );

		ob_start();
		get_noptin_template( 'woocommerce/email-products-' . $template . '.php', compact( 'products' ) );
		return ob_get_clean();
	}

	/**
	 * Provides a related id.
	 *
	 * @param string $collection The collect.
	 */
	public function provide( $collection ) {
		if ( 'product' === $collection ) {
			return $this->external->get_product_id();
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
			$order    = $this->external->get_order();
			$currency = $order ? $order->get_currency() : get_woocommerce_currency();
			return wc_price( $raw_value, array( 'currency' => $currency ) );
		}

		return parent::format( $raw_value, $args );
	}
}
