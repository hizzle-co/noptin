<?php

namespace Hizzle\Noptin\Integrations\EDD;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for a EDD order item.
 *
 * @since 3.0.0
 */
class Order_Item extends \Hizzle\Noptin\Objects\Record {

	/**
	 * @var \EDD\Orders\Order_Item The external object.
	 */
	public $external;

	/**
	 * Class constructor.
	 *
	 * @param mixed $external The external object.
	 */
	public function __construct( $external ) {

		if ( is_numeric( $external ) ) {
			$external = edd_get_order_item( $external );
		}

		$this->external = $external;
	}

	/**
	 * Checks if the post exists.
	 * @return bool
	 */
	public function exists() {
		if ( ! is_a( $this->external, '\EDD\Orders\Order_Item' ) ) {
			return false;
		}

		return $this->external->exists();
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
				return edd_get_order_item_meta( $this->external->id, $args['key'], true );
			}

			return '';
		}

		return $this->external->__get( $field );
	}

	/**
	 * Provides a related id.
	 *
	 * @param string $collection The collect.
	 */
	public function provide( $collection ) {
		if ( 'download' === $collection ) {
			return $this->external->product_id;
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
			$order    = edd_get_order( $this->external->order_id );
			$currency = $order ? $order->currency : edd_get_currency();
			return edd_currency_filter( edd_format_amount( $raw_value, true, $currency ), $currency );
		}

		return parent::format( $raw_value, $args );
	}
}
