<?php

// Exit if accessed directly.

defined( 'ABSPATH' ) || exit;

/**
 * Helper class for EDD triggers.
 *
 * @since 1.9.0
 */
abstract class Noptin_EDD_Trigger extends Noptin_Abstract_Trigger {

	/**
	 * @var EDD_Payment $payment
	 */
	protected $payment;

	/**
	 * @var EDD_Customer $customer
	 */
	protected $customer;

	/**
	 * @var EDD_Download $download
	 */
	protected $download;

	/**
	 * @var string
	 */
	public $category = 'EDD';

	/**
	 * @var string
	 */
	public $integration = 'edd';

	/**
	 * Returns payment smart tags.
	 *
	 * @return array
	 */
	public function get_payment_smart_tags() {

		return array(

			'order.id'                => array(
				'description'       => __( 'Order ID', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.id',
				'conditional_logic' => 'number',
			),

			'order.new'               => array(
				'description'       => __( 'Order is new', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.new',
				'conditional_logic' => 'string',
				'options'           => array(
					'yes' => __( 'Yes', 'newsletter-optin-box' ),
					'no'  => __( 'No', 'newsletter-optin-box' ),
				),
			),

			'order.number'            => array(
				'description'       => __( 'Order number', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.number',
				'conditional_logic' => 'string',
			),

			'order.key'               => array(
				'description'       => __( 'Order key', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.key',
				'conditional_logic' => 'string',
			),

			'order.subtotal'          => array(
				'description'       => __( 'Order subtotal', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.subtotal',
				'conditional_logic' => 'number',
			),

			'order.tax'               => array(
				'description'       => __( 'Order tax', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.tax',
				'conditional_logic' => 'number',
			),

			'order.discounted_amount' => array(
				'description'       => __( 'Order discount', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.discounted_amount',
				'conditional_logic' => 'number',
			),

			'order.fees_total'        => array(
				'description'       => __( 'Order fees total', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.fees_total',
				'conditional_logic' => 'number',
			),

			'order.total'             => array(
				'description'       => __( 'Order total', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.total',
				'conditional_logic' => 'number',
			),

			'order.tax_rate'          => array(
				'description'       => __( 'Order tax rate', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.tax_rate',
				'conditional_logic' => 'number',
			),

			'order.date'              => array(
				'description'       => __( 'Order date', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.date',
				'conditional_logic' => 'date',
			),

			'order.completed_date'    => array(
				'description'       => __( 'Order completed date', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.completed_date',
				'conditional_logic' => 'date',
			),

			'order.status'            => array(
				'description' => __( 'The order status.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.status',
			),

			'order.status_nicename'   => array(
				'description' => __( 'The status label.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.status_nicename',
			),

			'order.gateway'           => array(
				'description' => __( 'The payment gateway.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.gateway',
			),

			'order.currency'          => array(
				'description' => __( 'The payment currency.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.currency',
			),

			'order.transaction_id'    => array(
				'description'       => __( 'Transaction id', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.transaction_id',
				'conditional_logic' => 'string',
			),

			'order.first_name'        => array(
				'description'       => __( 'The billing first name', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.first_name',
				'conditional_logic' => 'string',
			),

			'order.last_name'         => array(
				'description'       => __( 'The billing last name', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.last_name',
				'conditional_logic' => 'string',
			),

			'order.email'             => array(
				'description'       => __( 'The billing email', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.email',
				'conditional_logic' => 'string',
			),

			'order.recovery_url'      => array(
				'description' => __( 'The URL to pay for the order', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.recovery_url',
			),

			'order.admin_url'         => array(
				'description' => __( 'The admin URL of the order', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.admin_url',
			),

			'order.meta'              => array(
				'description' => __( 'The value of an order custom field. Format is optional.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => "order.meta key='xyz' format='date'",
			),

			'order.ip'                => array(
				'description'       => __( "The customer's IP address.", 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.ip',
				'conditional_logic' => 'string',
			),

			'order.download_list'     => array(
				'description' => __( 'A list of download links for each download purchased.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.download_list',
			),

		);
	}

	/**
	 * Order field value of the current order.
	 *
	 * @param array $args
	 * @param string $field
	 * @return string
	 */
	public function get_order_field( $args = array(), $field = 'order.id' ) {
		$default = isset( $args['default'] ) ? $args['default'] : '';

		// Abort if no payment.
		if ( empty( $this->payment ) ) {
			return esc_html( $default );
		}

		$field = str_replace( 'order.', '', $field );

		// Process order fields.
		switch ( $field ) {

			case 'admin_url':
				return admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $this->payment->ID );

			case 'recovery_url':
				return $this->payment->get_recovery_url();

			case 'download_list':
				return edd_email_tag_download_list( $this->payment->ID );

			case 'billing_address':
				return edd_email_tag_billing_address( $this->payment->ID );

			default:
				$value = $this->payment->__get( $field );

				if ( 'meta' === $field && isset( $args['key'] ) ) {
					$value = $this->payment->get_meta( $args['key'] );
				}

				if ( is_array( $value ) ) {
					$value = implode( ', ', $value );
				}

				if ( is_bool( $value ) ) {

					if ( isset( $args['format'] ) && 'label' === $args['format'] ) {
						$value = $value ? __( 'Yes', 'newsletter-optin-box' ) : __( 'No', 'newsletter-optin-box' );
					} else {
						$value = $value ? 'yes' : 'no';
					}
				}

				$value = wp_kses_post( (string) $value );

				// Abort if no formating.
				if ( empty( $args['format'] ) ) {
					return $value;
				}

				// Format amounts.
				if ( 'price' === $args['format'] ) {
					return edd_currency_filter( edd_format_amount( floatval( $value ), true, $this->payment->currency ), $this->payment->currency );
				}

				// Format dates.
				if ( 'date' === $args['format'] ) {
					return date_i18n( get_option( 'date_format' ), strtotime( $value ) );
				}

				// Format times.
				if ( 'time' === $args['format'] ) {
					return date_i18n( get_option( 'time_format' ), strtotime( $value ) );
				}

				// Format datetimes.
				if ( 'datetime' === $args['format'] ) {
					return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $value ) );
				}

				// Format as a list.
				if ( 'list' === $args['format'] ) {
					$items = noptin_parse_list( esc_html( $value ), true );
					return '<ul><li>' . implode( '</li><li>', $items ) . '</li></ul>';
				}

				// Format as a link.
				if ( 'link' === $args['format'] ) {
					$text = empty( $args['text'] ) ? $value : $args['text'];
					return sprintf( '<a href="%s">%s</a>', esc_url( $value ), esc_html( $text ) );
				}

				return apply_filters( 'noptin_edd_smart_tag_value_format_as_' . $args['format'], $value, $field, $args, $this );
		}

		return esc_html( $default );
	}

	/**
	 * Returns customer smart tags.
	 *
	 * @return array
	 */
	public function get_customer_smart_tags() {

		return array(

			'customer.user_id'        => array(
				'description'       => __( 'User ID', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => 'customer.user_id',
				'conditional_logic' => 'number',
			),

			'customer.id'             => array(
				'description'       => __( 'Customer ID', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => 'customer.id',
				'conditional_logic' => 'number',
			),

			'customer.avatar_url'     => array(
				'description' => __( "Return the customer's avatar.", 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_customer_field' ),
				'example'     => 'customer.avatar_url',
			),

			'customer.purchase_count' => array(
				'description'       => __( 'The number of orders the customer has', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => 'customer.purchase_count',
				'conditional_logic' => 'number',
			),

			'customer.purchase_value' => array(
				'description'       => __( 'Lifetime Value', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => "customer.purchase_value format='price'",
				'conditional_logic' => 'number',
			),

			'customer.email'          => array(
				'description'       => __( "The customer's email", 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => 'customer.email',
				'conditional_logic' => 'string',
			),

			'customer.status'         => array(
				'description'       => __( "The customer's status", 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => 'customer.status',
				'conditional_logic' => 'string',
			),

			'customer.name'           => array(
				'description'       => __( "The customer's display name", 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => 'customer.name',
				'conditional_logic' => 'string',
			),

			'customer.newsletter'     => array(
				'description'       => __( "The customer's newsletter subscription status", 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => 'customer.newsletter',
				'conditional_logic' => 'string',
				'options'           => array(
					'yes' => __( 'subscribed', 'newsletter-optin-box' ),
					'no'  => __( 'unsubscribed', 'newsletter-optin-box' ),
				),
			),

			'customer.date_created'   => array(
				'description'       => __( "The customer's creation date", 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => 'customer.date_created',
				'conditional_logic' => 'date',
			),
		);
	}

	/**
	 * Customer field value of the current customer.
	 *
	 * @param array $args
	 * @param string $field
	 * @return string
	 */
	public function get_customer_field( $args = array(), $field = 'customer.id' ) {
		$default = isset( $args['default'] ) ? $args['default'] : '';

		// Abort if no customer.
		if ( empty( $this->customer ) ) {
			return esc_html( $default );
		}

		$field = str_replace( 'customer.', '', $field );

		// Process customer fields.
		switch ( $field ) {

			case 'newsletter':
				$email      = $this->customer->email;
				$subscriber = noptin_get_subscriber( $email );

				if ( $subscriber->is_active() && ! noptin_is_email_unsubscribed( $email ) ) {
					return 'yes';
				}

				return 'no';

			case 'avatar_url':
				return esc_url( get_avatar_url( $this->customer->email ) );

			default:
				$value = $this->customer->__get( $field );

				if ( 'meta' === $field && isset( $args['key'] ) ) {
					$value = $this->payment->__get( $args['key'] );
				}

				if ( is_array( $value ) ) {
					$value = implode( ', ', $value );
				}

				if ( is_bool( $value ) ) {

					if ( isset( $args['format'] ) && 'label' === $args['format'] ) {
						$value = $value ? __( 'Yes', 'newsletter-optin-box' ) : __( 'No', 'newsletter-optin-box' );
					} else {
						$value = $value ? 'yes' : 'no';
					}
				}

				$value = wp_kses_post( (string) $value );

				// Abort if no formating.
				if ( empty( $args['format'] ) ) {
					return $value;
				}

				// Format amounts.
				if ( 'price' === $args['format'] ) {
					return edd_currency_filter( edd_format_amount( floatval( $value ) ) );
				}

				// Format dates.
				if ( 'date' === $args['format'] ) {
					return date_i18n( get_option( 'date_format' ), strtotime( $value ) );
				}

				// Format times.
				if ( 'time' === $args['format'] ) {
					return date_i18n( get_option( 'time_format' ), strtotime( $value ) );
				}

				// Format datetimes.
				if ( 'datetime' === $args['format'] ) {
					return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $value ) );
				}

				// Format as a list.
				if ( 'list' === $args['format'] ) {
					$items = noptin_parse_list( esc_html( $value ), true );
					return '<ul><li>' . implode( '</li><li>', $items ) . '</li></ul>';
				}

				// Format as a link.
				if ( 'link' === $args['format'] ) {
					$text = empty( $args['text'] ) ? $value : $args['text'];
					return sprintf( '<a href="%s">%s</a>', esc_url( $value ), esc_html( $text ) );
				}

				return apply_filters( 'noptin_edd_smart_tag_value_format_as_' . $args['format'], $value, $field, $args, $this );
		}

		return esc_html( $default );
	}

}
