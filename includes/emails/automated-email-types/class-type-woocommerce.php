<?php
/**
 * Emails API: WooCommerce Automated Email.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Helper class for WooCommerce automated emails.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
abstract class Noptin_WooCommerce_Automated_Email_Type extends Noptin_Automated_Email_Type {

	/**
	 * @var string
	 */
	public $category = '';

	/**
	 * @var WC_Order
	 */
	public $order = null;

	/**
	 * @var WC_Customer
	 */
	public $customer = null;

	/**
	 * @var WC_Product
	 */
	public $product = null;

	/**
	 * @var WC_Order_Item_Product
	 */
	public $order_item = null;

	/**
	 * Retrieves the automated email type image.
	 *
	 */
	public function the_image() {
		echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 503.81 299.89"><path fill="#7f54b3" d="M46.75,0H456.84a46.94,46.94,0,0,1,47,47V203.5a46.94,46.94,0,0,1-47,47H309.78L330,299.89l-88.78-49.43H47a46.94,46.94,0,0,1-47-47V47A46.77,46.77,0,0,1,46.76,0Z"/><path fill="#fff" d="M28.69,42.8c2.86-3.89,7.16-5.94,12.9-6.35Q57.25,35.24,59.41,51.2,68.94,115.4,80.09,160l44.85-85.4q6.15-11.67,15.36-12.29c9-.61,14.54,5.12,16.8,17.2,5.12,27.24,11.67,50.38,19.45,70q8-78,27-112.64c3.07-5.73,7.57-8.6,13.51-9A17.8,17.8,0,0,1,230,32a16,16,0,0,1,6.35,11.67,17.79,17.79,0,0,1-2,9.83c-8,14.75-14.55,39.53-19.87,73.93-5.12,33.39-7,59.4-5.73,78a24.29,24.29,0,0,1-2.46,13.52c-2.46,4.51-6.15,7-10.86,7.37-5.32.41-10.85-2.05-16.17-7.57Q150.64,189.54,134,131.48q-20,39.32-29.49,59c-12.09,23.14-22.33,35-30.93,35.64C68,226.51,63.3,221.8,59.2,212Q43.54,171.72,25.41,56.52A17.44,17.44,0,0,1,28.69,42.8ZM468.81,75C461.43,62.05,450.58,54.27,436,51.2A53.72,53.72,0,0,0,425,50c-19.66,0-35.63,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.23,20.69,32.77,23.76A53.64,53.64,0,0,0,414.54,204c19.86,0,35.83-10.24,48.12-30.72a109.73,109.73,0,0,0,16-58C478.84,99.33,475.36,86,468.81,75ZM443,131.69c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.92-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95,66.29,66.29,0,0,1,10.86-24.37c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31a52,52,0,0,1,3.69,18.64A71.47,71.47,0,0,1,443,131.69ZM340.6,75c-7.37-12.91-18.43-20.69-32.76-23.76A53.79,53.79,0,0,0,296.78,50c-19.66,0-35.64,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.22,20.69,32.76,23.76A53.72,53.72,0,0,0,286.33,204c19.87,0,35.84-10.24,48.13-30.72a109.72,109.72,0,0,0,16-58C350.43,99.33,347.16,86,340.6,75Zm-26,56.73c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.91-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95A66.29,66.29,0,0,1,279,97.28c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31A52,52,0,0,1,316,116.53a60.45,60.45,0,0,1-1.44,15.16Z"/></svg>';
	}

	/**
	 * Returns the default template.
	 *
	 */
	public function default_template() {
		return 'woocommerce';
	}

	/**
	 * Returns the default recipient.
	 *
	 */
	public function get_default_recipient() {
		return '[[customer.email]]';
	}

	/**
	 * Formats a datetime merge tag.
	 *
	 * Dates should be passed in the site's timezone.
	 * WC_DateTime objects will maintain their specified timezone.
	 *
	 * @param WC_DateTime|DateTime|string $input
	 * @param array                        $parameters [modify, format]
	 * @param bool                         $is_gmt
	 *
	 * @return string|false
	 */
	protected function format_datetime( $input, $default ) {

		if ( ! $input ) {
			return $default;
		}

		if ( $input instanceof WC_DateTime ) {
			return $input->date_i18n( get_option( 'date_format' ) );
		}

		if ( $input instanceof DateTime ) {
			return date_i18n( get_option( 'date_format' ), $input->getTimestamp() );
		}

		if ( is_numeric( $input ) ) {
			return date_i18n( get_option( 'date_format' ), $input );
		}

		if ( is_string( $input ) ) {
			return date_i18n( get_option( 'date_format' ), strtotime( $input ) );
		}

		return $default;

	}

	/**
	 * Formats a Price.
	 *
	 * @param string $amount
	 * @param string $currency
	 * @param string $format
	 * @return string
	 */
	protected function format_amount( $amount, $currency = null, $format = 'price' ) {

		if ( 'decimal' === $format ) {
			return wc_format_localized_price( floatval( $amount ) );
		}

		return wc_price( $amount, array( 'currency' => $currency ) );

	}

	/**
	 * Retrieves an array of order merge tags.
	 *
	 * @return array
	 */
	public function get_order_merge_tags() {

		return array(

			'order.id'                   => array(
				'description'       => __( 'Order ID', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.id',
				'conditional_logic' => 'number',
			),

			'order.number'               => array(
				'description'       => __( 'Order Number', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.number',
				'conditional_logic' => 'string',
			),

			'order.transaction_id'       => array(
				'description'       => __( 'Transaction id', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.transaction_id',
				'conditional_logic' => 'string',
			),

			'order.status'               => array(
				'description' => __( 'The order status.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.status',
			),

			'order.coupon_code'          => array(
				'description'       => __( 'Coupon code', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.coupon_code',
				'conditional_logic' => 'string',
			),

			'order.date_created'         => array(
				'description'       => __( 'The date the order was created.', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.date_created',
				'conditional_logic' => 'date',
			),

			'order.date_paid'            => array(
				'description' => __( 'The date the order was paid.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.date_paid',
			),

			'order.date_completed'       => array(
				'description' => __( 'The date the order was completed.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.date_completed',
			),

			'order.subtotal'             => array(
				'description'       => __( 'The subtotal for the order.', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => "order.subtotal format='price'",
				'conditional_logic' => 'number',
			),

			'order.total_tax'            => array(
				'description'       => __( 'The total tax for the order.', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => "order.total_tax format='price'",
				'conditional_logic' => 'number',
			),

			'order.shipping_total'       => array(
				'description'       => __( 'The shipping cost for the order.', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => "order.shipping_total format='price'",
				'conditional_logic' => 'number',
			),

			'order.discount_total'       => array(
				'description'       => __( 'The total discount for the order.', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => "order.discount_total format='price'",
				'conditional_logic' => 'number',
			),

			'order.total'                => array(
				'description'       => __( 'The total cost of the order.', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => "order.total format='price'",
				'conditional_logic' => 'number',
			),

			'order.item_count'           => array(
				'description'       => __( 'The number of items in the order.', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.item_count',
				'conditional_logic' => 'number',
			),

			'order.billing_address'      => array(
				'description' => __( 'The formatted billing address for the order', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.billing_address',
			),

			'order.billing_first_name'   => array(
				'description'       => __( 'The billing first name', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.billing_first_name',
				'conditional_logic' => 'string',
			),

			'order.billing_last_name'    => array(
				'description'       => __( 'The billing last name', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.billing_last_name',
				'conditional_logic' => 'string',
			),

			'order.billing_company'      => array(
				'description'       => __( 'The billing company', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.billing_company',
				'conditional_logic' => 'string',
			),

			'order.billing_address_1'    => array(
				'description'       => __( 'The billing address 1', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.billing_address_1',
				'conditional_logic' => 'string',
			),

			'order.billing_address_2'    => array(
				'description'       => __( 'The billing address 2', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.billing_address_2',
				'conditional_logic' => 'string',
			),

			'order.billing_city'         => array(
				'description'       => __( 'The billing city', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.billing_city',
				'conditional_logic' => 'string',
			),

			'order.billing_state'        => array(
				'description'       => __( 'The billing state', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.billing_state',
				'conditional_logic' => 'string',
			),

			'order.billing_postcode'     => array(
				'description'       => __( 'The billing post code', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.billing_postcode',
				'conditional_logic' => 'string',
			),

			'order.billing_country'      => array(
				'description'       => __( 'The billing country', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.billing_country',
				'conditional_logic' => 'string',
				'options'           => WC()->countries->get_countries(),
			),

			'order.billing_email'        => array(
				'description'       => __( 'The billing email', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.billing_email',
				'conditional_logic' => 'string',
			),

			'order.billing_phone'        => array(
				'description'       => __( 'The billing phone number for the order', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.billing_phone',
				'conditional_logic' => 'string',
			),

			'order.shipping_method'      => array(
				'description' => __( 'The formatted shipping method for the order', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.shipping_method',
			),

			'order.shipping_address'     => array(
				'description' => __( 'The formatted shipping address for the order', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.shipping_address',
			),

			'order.shipping_first_name'  => array(
				'description'       => __( 'The shipping first name', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.shipping_first_name',
				'conditional_logic' => 'string',
			),

			'order.shipping_last_name'   => array(
				'description'       => __( 'The shipping last name', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.shipping_last_name',
				'conditional_logic' => 'string',
			),

			'order.shipping_company'     => array(
				'description'       => __( 'The shipping company', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.shipping_company',
				'conditional_logic' => 'string',
			),

			'order.shipping_address_1'   => array(
				'description'       => __( 'The shipping address 1', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.shipping_address_1',
				'conditional_logic' => 'string',
			),

			'order.shipping_address_2'   => array(
				'description'       => __( 'The shipping address 2', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.shipping_address_2',
				'conditional_logic' => 'string',
			),

			'order.shipping_city'        => array(
				'description'       => __( 'The shipping city', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.shipping_city',
				'conditional_logic' => 'string',
			),

			'order.shipping_state'       => array(
				'description'       => __( 'The shipping state', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.shipping_state',
				'conditional_logic' => 'string',
			),

			'order.shipping_postcode'    => array(
				'description'       => __( 'The shipping postcode', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.shipping_postcode',
				'conditional_logic' => 'string',
			),

			'order.shipping_country'     => array(
				'description'       => __( 'The shipping country', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.shipping_country',
				'conditional_logic' => 'string',
				'options'           => WC()->countries->get_countries(),
			),

			'order.shipping_phone'       => array(
				'description'       => __( 'The shipping phone number for the order', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.shipping_phone',
				'conditional_logic' => 'string',
			),

			'order.payment_method'       => array(
				'description'       => __( 'The payment method', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.payment_method',
				'conditional_logic' => 'string',
				'options'           => wp_list_pluck( WC_Payment_Gateways::instance()->payment_gateways(), 'title', 'id' ),
			),

			'order.payment_method_title' => array(
				'description' => __( 'The payment method title', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.payment_method_title',
			),

			'order.payment_url'          => array(
				'description' => __( 'The URL to pay for the order', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.payment_url',
			),

			'order.view_url'             => array(
				'description' => __( 'The URL to view the order', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.view_url',
			),

			'order.admin_url'            => array(
				'description' => __( 'The admin URL of the order', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.admin_url',
			),

			'order.customer_note'        => array(
				'description' => __( 'The customer provided note for the order.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.customer_note',
			),

			'order.meta'                 => array(
				'description' => __( 'The value of an order custom field. Format is optional.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => "order.meta key='xyz' format='date'",
			),

			'order.customer_ip_address'  => array(
				'description'       => __( "The customer's IP address.", 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.customer_ip_address',
				'conditional_logic' => 'string',
			),

			'order.customer_user_agent'  => array(
				'description'       => __( "The customer's user agent.", 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_field' ),
				'example'           => 'order.customer_user_agent',
				'conditional_logic' => 'string',
			),

			'order.items'                => array(
				'description' => __( 'Displays the order items. Style can be grid or list.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => "order.items style='grid'",
			),

			'order.cross_sells'          => array(
				'description' => __( 'Displays cross sells based on the order items. Style can be grid or list.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => "order.cross_sells limit='6' style='grid'",
			),

			'order.upsells'              => array(
				'description' => __( 'Displays upsells based on the order items. Style can be grid or list.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => "order.upsells limit='6' style='grid'",
			),

			'order.details'              => array(
				'description' => __( 'Displays important details about the order.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_field' ),
				'example'     => 'order.details',
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

		// Abort if no order.
		if ( empty( $this->order ) ) {
			return esc_html( $default );
		}

		// Process order fields.
		switch ( $field ) {

			case 'order.number':
				return $this->order->get_order_number();

			case 'order.admin_url':
				return $this->order->get_edit_order_url();

			case 'order.billing_address':
				return $this->order->get_formatted_billing_address();

			case 'order.shipping_address':
				return $this->order->get_formatted_shipping_address();

			case 'order.subtotal':
				$format = isset( $args['format'] ) ? $args['format'] : 'price';
				return $this->format_amount( $this->order->get_subtotal(), $this->order->get_currency(), $format );

			case 'order.coupon_code':
				$coupon_code = current( $this->order->get_coupon_codes() );
				return $coupon_code ? $coupon_code : $default;

			case 'order.discount_total':
				$format = isset( $args['format'] ) ? $args['format'] : 'price';
				return $this->format_amount( $this->order->get_total_discount(), $this->order->get_currency(), $format );

			case 'order.shipping_total':
				$format = isset( $args['format'] ) ? $args['format'] : 'price';
				return $this->format_amount( $this->order->get_shipping_total(), $this->order->get_currency(), $format );

			case 'order.total_tax':
				$format = isset( $args['format'] ) ? $args['format'] : 'price';
				return $this->format_amount( $this->order->get_total_tax(), $this->order->get_currency(), $format );

			case 'order.total':
				$format = isset( $args['format'] ) ? $args['format'] : 'price';
				return $this->format_amount( $this->order->get_total(), $this->order->get_currency(), $format );

			case 'order.date_completed':
				return $this->format_datetime( $this->order->get_date_completed(), $default );

			case 'order.date_paid':
				return $this->format_datetime( $this->order->get_date_paid(), $default );

			case 'order.date_created':
				return $this->format_datetime( $this->order->get_date_created(), $default );

			case 'order.item_count':
				return $this->order->get_item_count();

			case 'order.view_url':
				return $this->order->get_view_order_url();

			case 'order.payment_url':
				return $this->order->get_checkout_payment_url();

			case 'order.cross_sells':
				$template    = isset( $args['style'] ) ? $args['style'] : 'grid';
				$limit       = isset( $args['limit'] ) ? absint( $args['limit'] ) : 6;
				$cross_sells = $this->get_order_cross_sells( $this->order );

				if ( empty( $cross_sells ) ) {
					return $default;
				}

				$products = wc_get_products(
					array(
						'include'    => $cross_sells,
						'limit'      => $limit,
						'status'     => 'publish',
						'visibility' => 'catalog',
					)
				);

				return $this->get_products_html( $template, $products );

			case 'order.upsells':
				$template    = isset( $args['style'] ) ? $args['style'] : 'grid';
				$limit       = isset( $args['limit'] ) ? absint( $args['limit'] ) : 6;
				$cross_sells = $this->get_order_upsells( $this->order );

				if ( empty( $cross_sells ) ) {
					return $default;
				}

				$products = wc_get_products(
					array(
						'include'    => $cross_sells,
						'limit'      => $limit,
						'status'     => 'publish',
						'visibility' => 'catalog',
					)
				);

				return $this->get_products_html( $template, $products );

			case 'order.items':
				$template = isset( $args['style'] ) ? $args['style'] : 'grid';
				$products = array();

				foreach ( $this->order->get_items() as $item ) {
					/** @var WC_Order_Item_Product $item */
					$products[] = $item->get_product();
				}

				return $this->get_products_html( $template, $products );

			case 'order.meta':
				// Abort if no meta key.
				if ( empty( $args['key'] ) ) {
					return esc_html( $default );
				}

				// Retrieve the value.
				$meta = $this->order->get_meta( trim( $args['key'] ) );

				if ( '' === $meta ) {
					return $default;
				}

				// Optionally format the value.
				if ( empty( $args['format'] ) ) {
					return wp_kses_post( (string) $meta );
				}

				// Format as date.
				if ( 'date' === $args['format'] ) {
					return $this->format_datetime( $meta, $default );
				}

				// Format as price.
				if ( 'price' === $args['format'] ) {
					return $this->format_amount( $meta, $this->order->get_currency() );
				}

				if ( 'price_decimal' === $args['format'] ) {
					return $this->format_amount( $meta, $this->order->get_currency(), 'decimal' );
				}

				// Unsupported format.
				return wp_kses_post( (string) $meta );

			case 'order.details':
				WC()->mailer();
				ob_start();
				do_action( 'woocommerce_email_order_details', $this->order, false, false, '' );
				return ob_get_clean();

			default:
				$method = 'get_' . str_replace( 'order.', '', $field );

				if ( is_callable( array( $this->order, $method ) ) ) {
					return wp_kses_post( (string) $this->order->$method() );
				}
		}

		return esc_html( $default );
	}

	/**
	 * Retrieves an array of customer merge tags.
	 *
	 * @return array
	 */
	public function get_customer_merge_tags() {

		return array(

			'customer.id'               => array(
				'description'       => __( 'Customer ID', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => 'customer.id',
				'conditional_logic' => 'number',
			),

			'customer.details'          => array(
				'description' => __( "Return the customer's details.", 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_customer_field' ),
				'example'     => 'customer.details',
			),

			'customer.avatar_url'       => array(
				'description' => __( "Return the customer's avatar.", 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_customer_field' ),
				'example'     => 'customer.avatar_url',
			),

			'customer.order_count'      => array(
				'description'       => __( 'The number of orders the customer has', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => 'customer.order_count',
				'conditional_logic' => 'number',
			),

			'customer.first_order_date' => array(
				'description'       => __( 'The date of the customer\'s first order', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => "customer.first_order_date format='date'",
				'conditional_logic' => 'date',
			),

			'customer.total_spent'      => array(
				'description'       => __( 'Lifetime Value', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => "customer.total_spent format='price'",
				'conditional_logic' => 'number',
			),

			'customer.username'         => array(
				'description'       => __( "The customer's username", 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => 'customer.username',
				'conditional_logic' => 'string',
			),

			'customer.email'            => array(
				'description'       => __( "The customer's email", 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => 'customer.email',
				'conditional_logic' => 'string',
			),

			'customer.first_name'       => array(
				'description'       => __( "The customer's first name", 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => 'customer.first_name',
				'conditional_logic' => 'string',
			),

			'customer.last_name'        => array(
				'description'       => __( "The customer's last name", 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => 'customer.last_name',
				'conditional_logic' => 'string',
			),

			'customer.display_name'     => array(
				'description'       => __( "The customer's display name", 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => 'customer.display_name',
				'conditional_logic' => 'string',
			),

			'customer.locale'           => array(
				'description'       => __( "The customer's preferred locale", 'newsletter-optin-box' ),
				'conditional_logic' => 'string',
				'example'           => 'customer.locale default="en_US"',
				'options'           => noptin_get_available_languages(),
			),

			'customer.newsletter'       => array(
				'description'       => __( "The customer's newsletter subscription status", 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_customer_field' ),
				'example'           => 'customer.newsletter',
				'conditional_logic' => 'string',
				'options'           => array(
					'yes' => __( 'subscribed', 'newsletter-optin-box' ),
					'no'  => __( 'unsubscribed', 'newsletter-optin-box' ),
				),
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

		// Process customer fields.
		switch ( $field ) {

			case 'custom.newsletter':
				$email      = $this->customer->get_email();
				$subscriber = noptin_get_subscriber( $email );

				if ( $subscriber->is_active() && ! noptin_is_email_unsubscribed( $email ) ) {
					return 'yes';
				}

				return 'no';

			case 'customer.locale':
				$locale = $this->customer->get_meta( 'locale' );

				if ( empty( $locale ) ) {

					if ( $this->customer->get_id() ) {
						$locale = get_user_locale( $this->customer->get_id() );
					} else {
						$locale = $default;
					}
				}

				if ( empty( $locale ) ) {
					$locale = get_locale();
				}

				return $locale;

			case 'customer.details':
				$order = empty( $this->order ) ? $this->customer->get_last_order() : $this->order;

				if ( ! empty( $order ) ) {

					WC()->mailer();
					ob_start();
					do_action( 'woocommerce_email_customer_details', $order, false, false, '' );
					return ob_get_clean();

				}
				break;

			case 'customer.total_spent':
				$format = isset( $args['format'] ) ? $args['format'] : 'decimal';
				return $this->format_amount( $this->customer->get_total_spent(), null, $format );

			case 'customer.first_order_date':
				$format      = isset( $args['format'] ) ? $args['format'] : 'date';
				$first_order = wc_get_orders(
					array(
						'customer' => $this->customer->get_id(),
						'limit'    => 1,
						'orderby'  => 'date',
						'order'    => 'ASC',
					)
				);

				if ( empty( $first_order ) ) {
					return esc_html( $default );
				}

				$date = $first_order[0]->get_date_created();
				if ( 'date' === $format ) {
					return $date->date_i18n( wc_date_format() );
				}

				return $date->date( 'Y-m-d' );

			default:
				$method = 'get_' . str_replace( 'customer.', '', $field );

				if ( is_callable( array( $this->customer, $method ) ) ) {
					return wp_kses_post( (string) $this->customer->$method() );
				}
		}

		return esc_html( $default );
	}

	/**
	 * Retrieves an array of product merge tags.
	 *
	 * @return array
	 */
	public function get_product_merge_tags() {

		return array(

			'product.id'                => array(
				'description'       => __( 'Product ID', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_product_field' ),
				'example'           => 'product.id',
				'conditional_logic' => 'number',
			),

			'product.name'              => array(
				'description'       => __( 'Product name', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_product_field' ),
				'example'           => 'product.name',
				'conditional_logic' => 'string',
			),

			'product.sku'               => array(
				'description'       => __( 'Product sku', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_product_field' ),
				'example'           => 'product.sku',
				'conditional_logic' => 'string',
			),

			'product.type'              => array(
				'description'       => __( 'Product type', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_product_field' ),
				'example'           => 'product.type',
				'conditional_logic' => 'string',
				'options'           => wc_get_product_types(),
			),

			'product.short_description' => array(
				'description' => __( 'Product short description', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_product_field' ),
				'example'     => 'product.short_description',
			),

			'product.description'       => array(
				'description' => __( 'Product description', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_product_field' ),
				'example'     => 'product.description',
			),

			'product.url'               => array(
				'description' => __( 'Product URL', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_product_field' ),
				'example'     => 'product.url',
			),

			'product.price'             => array(
				'description'       => __( 'Product price', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_product_field' ),
				'example'           => "product.price format='price'",
				'conditional_logic' => 'number',
			),

			'product.image'             => array(
				'description' => __( 'Product image', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_product_field' ),
				'example'     => "product.image format='woocommerce_thumbnail'",
			),

			'product.add_to_cart_url'   => array(
				'description' => __( 'Add to cart URL', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_product_field' ),
				'example'     => 'product.add_to_cart_url',
			),

			'product.related'           => array(
				'description' => __( 'Displays related products. Style can be grid or list.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_product_field' ),
				'example'     => "product.related limit='6' style='grid'",
			),

		);

	}

	/**
	 * Custom field value of the current product.
	 *
	 * @param array $args
	 * @param string $field
	 * @return string
	 */
	public function get_product_field( $args = array(), $field = 'product.id' ) {
		$default = isset( $args['default'] ) ? $args['default'] : '';

		// Abort if no product.
		if ( empty( $this->product ) ) {
			return esc_html( $default );
		}

		// Process product fields.
		switch ( $field ) {

			case 'product.url':
				return $this->product->get_permalink();

			case 'product.price':
				$format = isset( $args['format'] ) ? $args['format'] : 'price';
				return $this->format_amount( $this->product->get_price(), null, $format );

			case 'product.image':
				$size = isset( $args['size'] ) ? $args['size'] : 'woocommerce_thumbnail';
				return $this->product->get_image( $size );

			case 'product.add_to_cart_url':
				return $this->product->add_to_cart_url();

			case 'product.related':
				$template = isset( $args['style'] ) ? $args['style'] : 'grid';
				$limit    = isset( $args['limit'] ) ? absint( $args['limit'] ) : 6;
				$related  = array_filter( array_map( 'wc_get_product', wc_get_related_products( $this->product->get_id(), $limit, $this->product->get_upsell_ids() ) ), 'wc_products_array_filter_visible' );

				if ( empty( $related ) ) {
					return $default;
				}

				return $this->get_products_html( $template, $related );

			default:
				$method = 'get_' . str_replace( 'product.', '', $field );

				if ( is_callable( array( $this->product, $method ) ) ) {
					return wp_kses_post( (string) $this->product->$method() );
				}
		}

		return esc_html( $default );
	}

	/**
	 * Retrieves an array of order item merge tags.
	 *
	 * @return array
	 */
	public function get_order_item_merge_tags() {

		return array(

			'order_item.id'        => array(
				'description'       => __( 'Ordered Item ID', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_item_field' ),
				'example'           => 'order_item.id',
				'conditional_logic' => 'number',
			),

			'order_item.name'      => array(
				'description'       => __( 'Ordered Item Name', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_item_field' ),
				'example'           => 'order_item.name',
				'conditional_logic' => 'string',
			),

			'order_item.quantity'  => array(
				'description'       => __( 'Ordered Item Quantity', 'newsletter-optin-box' ),
				'callback'          => array( $this, 'get_order_item_field' ),
				'example'           => 'order_item.quantity',
				'conditional_logic' => 'number',
			),

			'order_item.attribute' => array(
				'description' => __( 'Displays a given attribute for the product.', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_item_field' ),
				'example'     => "order_item.attribute key='xyz'",
			),

			'order_item.meta'      => array(
				'description' => __( 'Displays the value of an order item meta field', 'newsletter-optin-box' ),
				'callback'    => array( $this, 'get_order_item_field' ),
				'example'     => "order_item.meta key='xyz'",
			),

		);

	}

	/**
	 * Custom field value of the current order item.
	 *
	 * @param array $args
	 * @param string $field
	 * @return string
	 */
	public function get_order_item_field( $args = array(), $field = 'order_item.id' ) {
		$default = isset( $args['default'] ) ? $args['default'] : '';

		// Abort if no order item.
		if ( empty( $this->order_item ) ) {
			return esc_html( $default );
		}

		// Process product's order items.
		switch ( $field ) {

			case 'order_item.attribute':
				// Abort if no key provided.
				if ( empty( $args['key'] ) ) {
					return esc_html( $default );
				}

				$product   = $this->order_item->get_product();
				$attribute = empty( $product ) ? $default : $product->get_attribute( trim( $args['key'] ) );

				return esc_html( $attribute );

			case 'order_item.meta':
				// Abort if no key provided.
				if ( empty( $args['key'] ) ) {
					return esc_html( $default );
				}

				return wp_kses_post( (string) wc_get_order_item_meta( $this->order_item, trim( $args['key'] ) ) );

			default:
				$method = 'get_' . str_replace( 'order_item.', '', $field );

				if ( is_callable( array( $this->order_item, $method ) ) ) {
					return wp_kses_post( (string) $this->order_item->$method() );
				}
		}

		return esc_html( $default );
	}

	/**
	 * Get order cross sells.
	 *
	 * @param WC_Order $order
	 * @return int[]
	 */
	protected function get_order_cross_sells( $order ) {
		$cross_sells = array();
		$in_order    = array();

		$items = $order->get_items();

		foreach ( $items as $item ) {
			/** @var WC_Order_Item_Product $item */
			$product = $item->get_product();

			if ( $product ) {
				$in_order[]  = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
				$cross_sells = array_merge( $product->get_cross_sell_ids(), $cross_sells );
			}
		}

		return array_diff( $cross_sells, $in_order );
	}

	/**
	 * Get order upsells.
	 *
	 * @param WC_Order $order
	 * @return int[]
	 */
	protected function get_order_upsells( $order ) {
		$upsells  = array();
		$in_order = array();

		foreach ( $order->get_items() as $item ) {
			/** @var WC_Order_Item_Product $item */
			$product = $item->get_product();

			if ( $product ) {
				$in_order[] = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
				$upsells    = array_merge( $product->get_upsell_ids(), $upsells );
			}
		}

		return array_diff( $upsells, $in_order );
	}

	/**
	 * Get product html to display.
	 *
	 * @param string $template
	 * @param WC_Product[] $products
	 *
	 * @return string
	 */
	public function get_products_html( $template = 'grid', $products = array() ) {
		ob_start();
		get_noptin_template( 'woocommerce/email-products-' . $template . '.php', compact( 'products' ) );
		return ob_get_clean();
	}

	/**
	 * Retrieves the main product image.
	 *
	 * @param WC_Product $product
	 * @param string $size
	 * @return string
	 */
	public static function get_product_image( $product, $size = 'woocommerce_thumbnail' ) {

		$image_id = $product->get_image_id();
		if ( ! empty( $image_id ) ) {
			$image_url = wp_get_attachment_image_url( $image_id, $size );

			$image = '<img src="' . esc_url( $image_url ) . '" class="noptin-wc-product-image" alt="' . esc_attr( $product->get_name() ) . '">';

			return apply_filters( 'noptin_woocommere_email_product_image', $image, $size, $product );
		} else {
			return apply_filters( 'noptin_woocommere_email_placeholder_image', wc_placeholder_img( $size ), $size, $product );
		}

	}

	/**
	 * Sends a notification.
	 *
	 * @param Noptin_Automated_Email $campaign
	 * @param string $key
	 */
	protected function prepare_and_send( $campaign, $key ) {

		// Generate customer email.
		$email = '';

		// If we have a customer, set-up their info.
		if ( ! empty( $this->customer ) ) {
			$email = $this->customer->get_email();
			$this->maybe_set_subscriber_and_user_from_customer( $this->customer );
		} elseif ( ! empty( $this->order ) ) {
			$email = $this->order->get_billing_email();
			$this->maybe_set_subscriber_and_user( $email );
		}

		$this->send( $campaign, $key, $this->get_recipients( $campaign, array( '[[customer.email]]' => $email ) ) );

	}

	/**
	 * Sets subscriber and user for the customer.
	 *
	 * @param \WC_Customer $customer
	 */
	protected function maybe_set_subscriber_and_user_from_customer( $customer ) {

		if ( empty( $customer ) ) {
			return;
		}

		$email = $customer->get_email();

		if ( ! empty( $email ) ) {
			$this->maybe_set_subscriber_and_user( $email );
		}

		$user_id = $customer->get_id();

		if ( $user_id ) {
			$this->user = get_user_by( 'id', $user_id );
		}
	}

	/**
	 * Fired after sending a campaign.
	 *
	 * @param Noptin_Automated_Email $campaign
	 */
	protected function after_send( $campaign ) {

		// Remove temp variables.
		$this->customer   = null;
		$this->order      = null;
		$this->order_item = null;
		$this->product    = null;

		parent::after_send( $campaign );
	}

	/**
	 * Sends a test email.
	 *
	 * @param Noptin_Automated_Email $campaign
	 * @param string $recipient
	 * @return bool Whether or not the test email was sent
	 */
	public function send_test( $campaign, $recipient ) {

		$this->prepare_test_data( $campaign );

		// Maybe set related subscriber.
		$this->maybe_set_subscriber_and_user_from_customer( $this->customer );

		return $this->send( $campaign, 'test', array( sanitize_email( $recipient ) => false ) );

	}

	/**
	 * Prepares test data.
	 *
	 * @param Noptin_Automated_Email $campaign
	 */
	public function prepare_test_data( $campaign ) {

		// Prepare user and subscriber.
		parent::prepare_test_data( $campaign );

		// Prepare WC data.
		$this->_prepare_test_data();

	}

	/**
	 * Prepares test data.
	 *
	 * @param int $offset
	 */
	public function _prepare_test_data( $offset = 0 ) {

		// Do not run more than 10 times.
		if ( $offset > 10 ) {
			throw new Exception( __( 'Could not find an order for this preview.', 'newsletter-optin-box' ) );
		}

		// Get the next order.
		$orders = wc_get_orders(
			array(
				'type'   => 'shop_order',
				'limit'  => 1,
				'offset' => $offset,
				'return' => 'ids',
			)
		);

		// If no order found, abort.
		if ( ! $orders ) {
			throw new Exception( __( 'Could not find an order for this preview.', 'newsletter-optin-box' ) );
		}

		// Retrieve the order object.
		$order = wc_get_order( $orders[0] );

		// Continue if this is not a guest order and has products.
		if ( $order && $order->get_customer_id() && $order->get_item_count() > 0 ) {
			$this->order    = $order;
			$this->user     = $order->get_user();
			$this->customer = new WC_Customer( $order->get_customer_id() );

			/**@var WC_Order_Item_Product $item */
			foreach ( $order->get_items() as $item ) {
				$this->order_item = $item;
				$this->product    = $item->get_product();
				break;
			}

			return;
		}

		return $this->_prepare_test_data( $offset + 1 );

	}

}
