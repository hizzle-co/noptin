<?php

namespace Hizzle\Noptin\Integrations\EDD;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for an EDD customer.
 *
 * @since 2.2.0
 */
class Customer extends \Hizzle\Noptin\Objects\Person {

	/**
	 * @var \EDD_Customer The external object.
	 */
	public $external;

	/**
	 * @var \EDD\Orders\Order_Address The associated address.
	 */
	public $address;

	/**
	 * Class constructor.
	 *
	 * @param mixed $external The external object.
	 */
	public function __construct( $external ) {
		if ( is_numeric( $external ) ) {
			$external = edd_get_customer( $external );
		}

		$this->external = $external;

		if ( $this->exists() ) {
			$this->address = $this->external->get_address();
		}
	}

	/**
	 * Checks if the customer exists.
	 * @return bool
	 */
	public function exists() {
		if ( ! is_a( $this->external, 'EDD_Customer' ) ) {
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
	public function get( $field_key, $args = array() ) {

		if ( ! $this->exists() ) {
			return null;
		}

		// Meta.
		if ( 'meta' === $field_key ) {
			if ( ! empty( $args['key'] ) ) {
				return edd_get_customer_meta( $this->external->id, $args['key'], true );
			}

			return '';
		}

		// Check if this is an address field.
		if ( 0 === strpos( $field_key, 'address.' ) ) {
			$field_key = str_replace( 'address.', '', $field_key );
			return ! empty( $this->address ) && isset( $this->address->{$field_key} ) ? $this->address->{$field_key} : '';
		}

		return $this->external->__get( $field_key );
	}

	/**
	 * Formats a given field's value.
	 *
	 * @param mixed $raw_value The raw value.
	 * @param array $format The format.
	 * @return mixed $value The formatted value.
	 */
	public function format( $raw_value, $format ) {

		// Format amounts.
		if ( 'price' === $format['format'] ) {
			return edd_currency_filter( edd_format_amount( floatval( $raw_value ) ) );
		}

		return parent::format( $raw_value, $format );
	}
}
