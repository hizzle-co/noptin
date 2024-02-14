<?php

namespace Hizzle\Noptin\Integrations\WooCommerce;

use WC_Customer;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for an WooCommerce customer.
 *
 * @since 3.0.0
 */
class Customer extends \Hizzle\Noptin\Objects\Person {

	/**
	 * @var \WC_Customer The external object.
	 */
	public $external;

	/**
	 * Class constructor.
	 *
	 * @param mixed $external The external object.
	 */
	public function __construct( $external ) {
		if ( is_numeric( $external ) && ! empty( $external ) ) {

			if ( (int) $external < 1 ) {
				$order = wc_get_order( absint( $external ) );

				if ( $order ) {
					$external = new \WC_Customer( $order->get_user_id() );

					// Set customer data from order if customer is not found.
					if ( ! $external->get_id() ) {
						$external->set_email( $order->get_billing_email() );
						$external->set_billing_email( $order->get_billing_email() );
						$external->set_first_name( $order->get_billing_first_name() );
						$external->set_last_name( $order->get_billing_last_name() );
						$external->set_display_name( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

						// Set billing address.
						$external->set_billing_address_1( $order->get_billing_address_1() );
						$external->set_billing_city( $order->get_billing_city() );
						$external->set_billing_state( $order->get_billing_state() );
						$external->set_billing_postcode( $order->get_billing_postcode() );
						$external->set_billing_country( $order->get_billing_country() );
						$external->set_billing_phone( $order->get_billing_phone() );

						// Set shipping address.
						$external->set_shipping_address_1( $order->get_shipping_address_1() );
						$external->set_shipping_city( $order->get_shipping_city() );
						$external->set_shipping_state( $order->get_shipping_state() );
						$external->set_shipping_postcode( $order->get_shipping_postcode() );
						$external->set_shipping_country( $order->get_shipping_country() );
					}
				}
			} else {
				$external = new \WC_Customer( $external );
			}
		}

		$this->external = $external;
	}

	/**
	 * Checks if the customer exists.
	 * @return bool
	 */
	public function exists() {
		if ( ! is_a( $this->external, 'WC_Customer' ) ) {
			return false;
		}

		return ! ! $this->external->get_email();
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

		// Locale.
		if ( 'locale' === $field ) {
			$locale = $this->external->get_meta( 'locale' );

			if ( empty( $locale ) ) {

				if ( $this->external->get_id() ) {
					$locale = get_user_locale( $this->external->get_id() );
				} else {
					$locale = isset( $args['default'] ) ? $args['default'] : '';
				}
			}

			if ( empty( $locale ) ) {
				$locale = get_locale();
			}

			return $locale;
		}

		// First order date.
		if ( 'first_order_date' === $field ) {
			$first_order = wc_get_orders(
				array(
					'customer' => $this->external->get_id() > 0 ? $this->external->get_id() : $this->external->get_email(),
					'limit'    => 1,
					'orderby'  => 'date',
					'order'    => 'ASC',
				)
			);

			if ( empty( $first_order ) ) {
				return '';
			}

			$date = $first_order[0]->get_date_created();
			return $date ? $date->date_i18n( wc_date_format() ) : '';
		}

		// Order count.
		if ( 'order_count' === $field && $this->external->get_id() < 1 ) {
			return Main::count_customer_orders( $this->external->get_email() );
		}

		// Total spent.
		if ( 'total_spent' === $field && $this->external->get_id() < 1 ) {
			return Main::calculate_customer_lifetime_value( $this->external->get_email() );
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
	 * Formats a given field's value.
	 *
	 * @param mixed $raw_value The raw value.
	 * @param array $format The format.
	 * @return mixed $value The formatted value.
	 */
	public function format( $raw_value, $format ) {

		// Format amounts.
		if ( 'price' === $format['format'] ) {
			return wc_price( (float) $raw_value, $format );
		}

		// Format decimals.
		if ( 'decimal' === $format['format'] ) {
			return wc_format_localized_price( floatval( $raw_value ) );
		}

		return parent::format( $raw_value, $format );
	}

	/**
	 * Checks if a customer has bought any of the given products.
	 *
	 * @param array $product_ids
	 * @return bool
	 */
	public function has_bought( $product_ids ) {

		foreach ( wp_parse_id_list( $product_ids ) as $product_id ) {
			if ( wc_customer_bought_product( $this->external->get_billing_email(), $this->external->get_id(), $product_id ) ) {
				return true;
			}
		}

		return false;
	}
}
