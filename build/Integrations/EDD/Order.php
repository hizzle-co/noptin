<?php

namespace Hizzle\Noptin\Integrations\EDD;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for an EDD order.
 *
 * @since 3.2.0
 */
class Order extends \Hizzle\Noptin\Objects\Record {

	/**
	 * @var \EDD\Orders\Order The external object.
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
			$external = edd_get_order( $external );
		}

		$this->external = $external;

		if ( $this->exists() ) {
			$this->address = $this->external->get_address();
		}
	}

	/**
	 * Checks if the payment exists.
	 * @return bool
	 */
	public function exists() {
		if ( ! is_a( $this->external, '\EDD\Orders\Order' ) ) {
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
				return edd_get_order_meta( $this->external->id, $args['key'], true );
			}

			return '';
		}

		if ( 'fees_total' === $field_key ) {
			return array_sum(
				wp_list_pluck(
					$this->external->get_fees(),
					'total'
				)
			);
		}

		if ( 'discounts' === $field_key ) {
			return wp_list_pluck(
				$this->external->get_discounts(),
				'description'
			);
		}

		if ( 'status_nicename' === $field_key ) {
			$all_payment_statuses = edd_get_payment_statuses();
			$status               = $this->external->status;
			return array_key_exists( $status, $all_payment_statuses ) ? $all_payment_statuses[ $status ] : ucfirst( $status );
		}

		if ( 'admin_url' === $field_key ) {
			return admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $this->external->id );
		}

		if ( 'download_list' === $field_key ) {
			return edd_email_tag_download_list( $this->external->id );
		}

		if ( 'billing_address' === $field_key ) {
			return edd_email_tag_billing_address( $this->external->id );
		}

		// Map fields.
		$map = array(
			'key'               => 'payment_key',
			'discounted_amount' => 'discount',
			'date'              => 'date_created',
			'completed_date'    => 'date_completed',
			'first_name'        => 'address.first_name',
			'last_name'         => 'address.last_name',
			'address.line1'     => 'address.address',
			'address.line2'     => 'address.address2',
			'address.state'     => 'address.region',
			'address.zip'       => 'address.postal_code',
		);

		if ( isset( $map[ $field_key ] ) ) {
			$field_key = $map[ $field_key ];
		}

		// Check if this is an address field.
		if ( 0 === strpos( $field_key, 'address.' ) ) {
			$field_key = str_replace( 'address.', '', $field_key );
			return ! empty( $this->address ) && isset( $this->address->{$field_key} ) ? $this->address->{$field_key} : '';
		}

		// Check if we have a method get_$field.
		$method = 'get_' . $field_key;
		if ( is_callable( array( $this->external, $method ) ) ) {
			return $this->external->{$method}();
		}

		return $this->external->__get( $field_key );
	}

	/**
	 * Provides a related id.
	 *
	 * @param string $collection The collect.
	 */
	public function provide( $collection ) {
		if ( 'edd_customer' === $collection ) {
			return edd_get_customer( $this->external->customer_id );
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
			return edd_currency_filter( edd_format_amount( $raw_value, true, $this->external->currency ), $this->external->currency );
		}

		return parent::format( $raw_value, $args );
	}

	/**
	 * Prepares custom tab content.
	 *
	 */
	public function prepare_custom_tab() {
		return array(
			'order'    => sprintf(
				'#%s',
				$this->external->get_number()
			),
			'url'      => admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $this->external->id ),
			'items'    => $this->prepare_order_items( $this->external->get_items() ),
			'discount' => edd_currency_filter( edd_format_amount( $this->external->discount, true, $this->external->currency ), $this->external->currency ),
			'total'    => edd_currency_filter( edd_format_amount( $this->external->total, true, $this->external->currency ), $this->external->currency ),
			'date'     => date_i18n( edd_get_date_format( 'datetime' ), strtotime( $this->external->date_created ) ),
			'status'   => $this->get( 'status_nicename' ),
		);
	}

	/**
	 * Returns an array of order item details.
	 *
	 * @param \EDD\Orders\Order_Item[] $items The items.
	 * @since 1.3.0
	 * @return array
	 */
	private function prepare_order_items( $items ) {
		$prepared = array();

		foreach ( $items as $item ) {
			$prepared[] = array(
				'name'     => $item->product_name,
				'total'    => edd_currency_filter( edd_format_amount( $item->total, true, $this->external->currency ), $this->external->currency ),
				'quantity' => $item->quantity,
			);
		}

		return $prepared;
	}
}
