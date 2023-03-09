<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when a download is purchased.
 *
 * @since 1.10.3
 */
class Noptin_EDD_Download_Purchase_Trigger extends Noptin_EDD_Trigger {

	/**
	 * Constructor.
	 *
	 * @since 1.3.0
	 */
	public function __construct() {
		add_action( 'edd_update_payment_status', array( $this, 'init_trigger' ), 10000, 3 );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'edd_download_purchase';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'EDD Download Purchase', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'When an EDD Download is bought or refunded', 'newsletter-optin-box' );
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
			array(
				'action'        => array(
					'description'       => __( 'Action', 'newsletter-optin-box' ),
					'example'           => 'action',
					'conditional_logic' => 'string',
					'options'           => array(
						'buy'    => __( 'Buy', 'newsletter-optin-box' ),
						'refund' => __( 'Refund', 'newsletter-optin-box' ),
					),
				),
				'download_id'   => array(
					'description'       => __( 'Download ID', 'newsletter-optin-box' ),
					'example'           => 'download_id',
					'conditional_logic' => 'number',
				),
				'download_name' => array(
					'description'       => __( 'Download name', 'newsletter-optin-box' ),
					'example'           => 'download_name',
					'conditional_logic' => 'string',
				),
				'download_url'  => array(
					'description'       => __( 'Download URL', 'newsletter-optin-box' ),
					'example'           => 'download_url',
					'conditional_logic' => 'string',
				),
				'download_sku'  => array(
					'description'       => __( 'Download SKU', 'newsletter-optin-box' ),
					'example'           => 'download_sku',
					'conditional_logic' => 'string',
				),
				'quantity'      => array(
					'description'       => __( 'Quantity', 'newsletter-optin-box' ),
					'example'           => 'quantity',
					'conditional_logic' => 'number',
				),
				'price_id'      => array(
					'description'       => __( 'Price ID', 'newsletter-optin-box' ),
					'example'           => 'price_id',
					'conditional_logic' => 'number',
				),
				'price'         => array(
					'description'       => __( 'Price', 'newsletter-optin-box' ),
					'example'           => 'price',
					'conditional_logic' => 'number',
				),
			),
			$this->get_payment_smart_tags(),
			$this->get_customer_smart_tags()
		);

    }

	/**
	 * Inits the trigger.
	 *
	 * @param $payment_id int EDD_Payment object ID
	 * @param $new_status str New payment status
	 * @param $old_status str Old payment status
	 * @since 1.9.0
	 */
	public function init_trigger( $payment_id, $new_status, $old_status ) {

		if ( $new_status === $old_status ) {
			return;
		}

		if ( 'complete' === $new_status ) {
			$action = 'buy';
		} elseif ( 'refunded' === $new_status || 'partially_refunded' === $new_status ) {
			$action = 'refund';
		} else {
			return;
		}

		$payment = edd_get_payment( $payment_id );

		if ( empty( $payment ) || ! is_a( $payment, 'EDD_Payment' ) ) {
			return;
		}

		$this->payment  = $payment;
		$this->customer = edd_get_customer( $payment->customer_id );

		if ( empty( $this->customer ) ) {
			return;
		}

		// Loop through the download items.
		foreach ( $payment->downloads as $order_item ) {

			$args = array(
				'action'      => $action,
				'email'       => $this->payment->email,
				'payment_id'  => $this->payment->ID,
				'download_id' => $order_item['id'],
				'quantity'    => $order_item['quantity'],
				'price_id'    => $order_item['options']['price_id'],
				'price'       => edd_get_price_option_amount( $order_item['id'], $order_item['options']['price_id'] ),
			);

			$download = edd_get_download( $order_item['id'] );

			if ( ! empty( $download ) ) {
				$args['download_sku']  = $download->get_sku();
				$args['download_url']  = get_permalink( $download->ID );
				$args['download_name'] = $download->post_title;

				if ( ! $download->has_variable_prices() ) {
					$args['price'] = $download->get_price();
				}
			}

			// Trigger the event.
			$this->trigger( $this->customer, $args );
		}

		$this->payment  = null;
		$this->customer = null;
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
			'payment_id'  => $args['payment_id'],
			'download_id' => $args['download_id'],
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

		// Fetch the payment.
		$payment = edd_get_payment( $args['payment_id'] );

		if ( empty( $payment ) || ! is_a( $payment, 'EDD_Payment' ) ) {
			throw new Exception( 'The payment no longer exists' );
		}

		// Check the status.
		if ( 'complete' === $payment->status ) {
			$action = 'buy';
		} elseif ( 'refunded' === $payment->status || 'partially_refunded' === $payment->status ) {
			$action = 'refund';
		} else {
			throw new Exception( 'The payment status is not valid' );
		}

		// Fetch the customer.
		$customer = edd_get_customer( $payment->customer_id );

		if ( empty( $customer ) ) {
			throw new Exception( 'The customer no longer exists' );
		}

		$this->payment  = $payment;
		$this->customer = $customer;

		// Loop through the download items.
		foreach ( $payment->downloads as $order_item ) {

			if ( absint( $order_item['id'] ) !== absint( $args['download_id'] ) ) {
				continue;
			}

			$args = array(
				'action'      => $action,
				'email'       => $this->payment->email,
				'payment_id'  => $this->payment->ID,
				'download_id' => $order_item['id'],
				'quantity'    => $order_item['quantity'],
				'price_id'    => $order_item['options']['price_id'],
				'price'       => edd_get_price_option_amount( $order_item['id'], $order_item['options']['price_id'] ),
			);

			$download = edd_get_download( $order_item['id'] );

			if ( ! empty( $download ) ) {
				$args['download_sku']  = $download->get_sku();
				$args['download_url']  = get_permalink( $download->ID );
				$args['download_name'] = $download->post_title;

				if ( ! $download->has_variable_prices() ) {
					$args['price'] = $download->get_price();
				}
			}

			// Prepare the trigger args.
			return $this->prepare_trigger_args( $this->customer, $args );
		}

		throw new Exception( 'The download no longer exists' );
	}
}
