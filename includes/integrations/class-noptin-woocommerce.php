<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Handles integrations with WooCommerce
 *
 * @since       1.2.6
 */
class Noptin_WooCommerce extends Noptin_Abstract_Ecommerce_Integration {

	/**
	 * @var string Slug, used as an unique identifier for this integration.
	 * @since 1.2.6
	 */
	public $slug = 'woocommerce';

	/**
	 * @var string Name of this integration.
	 * @since 1.2.6
	 */
	public $name = 'WooCommerce';

	/**
	 * Setup hooks in case the integration is enabled.
	 *
	 * @since 1.2.6
	 */
	public function initialize() {

		add_action( 'woocommerce_checkout_order_processed', array( $this, 'add_order_subscriber' ) );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'checkout_processed' ), 20 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'order_completed' ), 20 );
		add_action( 'woocommerce_payment_complete', array( $this, 'order_paid' ), 20 );
		add_action( 'woocommerce_order_refunded', array( $this, 'order_refunded' ), 20 );

	}

	/**
	 * Hooks the display checkbox code.
	 *
	 * @since 1.2.6
	 * @param string $checkbox_position The checkbox position to display the checkbox.
	 */
	public function hook_show_checkbox_code( $checkbox_position ) {

		if ( 'after_email_field' === $checkbox_position ) {
			add_filter( 'woocommerce_form_field_email', array( $this, 'add_checkbox_after_email_field' ), 100, 2 );
		} else {
			add_action( $checkbox_position, array( $this, 'output_checkbox' ), 20 );
		}

		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_woocommerce_checkout_checkbox_value' ) );
		add_filter( 'noptin_woocommerce_integration_subscription_checkbox_attributes', array( $this, 'add_woocommerce_class_to_checkbox' ) );
	}

	/**
	 * Returns an array of subscription checkbox positions.
	 *
	 * @since 1.2.6
	 * @return array
	 */
	public function checkbox_positions() {
		return array(
			'after_email_field'                           => __( 'After email field', 'newsletter-optin-box' ),
			'woocommerce_checkout_billing'                => __( 'After billing details', 'newsletter-optin-box' ),
			'woocommerce_checkout_shipping'               => __( 'After shipping details', 'newsletter-optin-box' ),
			'woocommerce_checkout_after_customer_details' => __( 'After customer details', 'newsletter-optin-box' ),
			'woocommerce_review_order_before_submit'      => __( 'Before submit button', 'newsletter-optin-box' ),
			'woocommerce_after_order_notes'               => __( 'After order notes', 'newsletter-optin-box' ),
		);
	}

	/**
	 * Did the user check the email subscription checkbox?
	 *
	 * @param int $order_id
	 */
	public function save_woocommerce_checkout_checkbox_value( $order_id ) {
		if ( $this->checkbox_was_checked() ) {
			update_post_meta( $order_id, '_noptin_optin', 1 );
		}
	}

	/**
	 * Was integration triggered?
	 *
	 * @param int $order_id Order id being executed.
	 * @return bool
	 */
	public function triggered( $order_id = null ) {
		$checked = get_post_meta( $order_id, '_noptin_optin', true );
		return $this->auto_subscribe() || ! empty( $checked );
	}

	/**
	 * Adds the checkbox after an email field.
	 *
	 * @return bool
	 */
	function add_checkbox_after_email_field( $field, $key ) {
		if ( $key !== 'billing_email' ) {
			return $field;
		}

		return $field . PHP_EOL . $this->get_checkbox_markup();

	}

	/**
	 * Prints the checkbox wrapper.
	 *
	 */
	function before_checkbox_wrapper() {

		if ( 'after_email_field' === get_noptin_option( 'noptin_woocommerce_integration_checkout_position' ) ) {
			echo '<p class="form-row form-row-wide" id="noptin_woocommerce_optin_checkbox">';
		}

	}

	/**
	 * Prints the checkbox closing wrapper.
	 *
	 */
	function after_checkbox_wrapper() {

		if ( 'after_email_field' === get_noptin_option( 'noptin_woocommerce_integration_checkout_position' ) ) {
			echo '</p>';
		}

	}

	/**
	 * Adds the woocommerce checkbox class to the subscription checkbox.
	 *
	 * @param array $attributes An array of checkbox attributes.
	 * @since 1.2.6
	 * @return array
	 */
	public function add_woocommerce_class_to_checkbox( $attributes ) {
		$attributes['class'] = 'input-checkbox';
		return $attributes;
	}

	/**
	 * Returns the checkbox message default value.
	 */
	public function get_checkbox_message_integration_default_value() {
		return __( 'Add me to your newsletter and keep me updated whenever your publish new products', 'newsletter-optin-box' );
	}

	/**
	 * Returns the email address of the customer associated with an order.
	 *
	 * @param int $order_id The order id.
	 * @since 1.2.6
	 * @return string
	 */
	public function get_order_customer_email( $order_id ) {

		$order = wc_get_order( $order_id );
		if ( empty( $order ) ) {
			return '';
		}

		if ( method_exists( $order, 'get_billing_email' ) ) {
			return $order->get_billing_email();
		}

		return $order->billing_email;

	}

	/**
	 * Returns the id of the customer associated with an order.
	 *
	 * @param int $order_id The order id.
	 * @since 1.2.6
	 * @return int
	 */
	public function get_order_customer_user_id( $order_id ) {

		$order = wc_get_order( $order_id );
		if ( empty( $order ) ) {
			return 0;
		}

		if ( method_exists( $order, 'get_customer_id' ) ) {
			return $order->get_customer_id();
		}

		return $order->customer_id;

	}

	/**
	 * Returns order customer details
	 *
	 * @param int $order_id The order id.
	 * @param bool $existing_subscriber Whether this is an existing subscriber or not.
	 * @since 1.2.6
	 * @return array
	 */
	public function get_order_customer_details( $order_id, $existing_subscriber = false ) {

		// Fetch the order.
		$order = wc_get_order( $order_id );
		if ( empty( $order ) ) {
			return array();
		}

		$noptin_fields = array();

		if ( ! $existing_subscriber ) {
			$noptin_fields = array(
				'_subscriber_via' => 'woocommerce_checkout',
				'order_id'        => $order_id,
			);
		}
		

		if ( method_exists( $order, 'get_billing_email' ) ) {
			$noptin_fields['email']             = $order->get_billing_email();
			$noptin_fields['name']              = $order->get_formatted_billing_full_name();
			$noptin_fields['phone']             = $order->get_billing_phone();
			$noptin_fields['company']           = $order->get_billing_company();
			$noptin_fields['address_1']         = $order->get_billing_address_1();
			$noptin_fields['address_2']         = $order->get_billing_address_2();
			$noptin_fields['postcode']          = $order->get_billing_postcode();
			$noptin_fields['city']              = $order->get_billing_city();
			$noptin_fields['state']             = $order->get_billing_state();
			$noptin_fields['country']           = $order->get_billing_country();
			$noptin_fields['wp_user_id']        = $order->get_customer_id();
			$noptin_fields['ip_address']        = $order->get_customer_ip_address();
			$noptin_fields['user_agent']        = $order->get_customer_user_agent();
			$noptin_fields['formatted_address'] = $order->get_formatted_billing_address();
			
			if ( ! empty( $noptin_fields['country'] ) ) {
				$countries = WC()->countries->get_countries();
				$noptin_fields['country'] = isset( $countries[ $noptin_fields['country'] ] ) ? $countries[ $noptin_fields['country'] ] : $noptin_fields['country'];
			}

		} else {
			$noptin_fields['email']      = $order->billing_email;
			$noptin_fields['name']       = trim( "{$order->billing_first_name} {$order->billing_last_name}" );
			$noptin_fields['wp_user_id'] = $order->customer_id;
			$noptin_fields['phone']      = $order->billing_phone;
			$noptin_fields['company']    = $order->billing_company;
			$noptin_fields['address_1']  = $order->billing_address_1;
			$noptin_fields['address_2']  = $order->billing_address_2;
			$noptin_fields['postcode']   = $order->billing_postcode;
			$noptin_fields['city']       = $order->billing_city;
			$noptin_fields['state']      = $order->billing_state;
			$noptin_fields['country']    = $order->billing_country;
			$noptin_fields['ip_address'] = $order->customer_ip_address;
			$noptin_fields['user_agent'] = $order->customer_user_agent;
		}

		return array_filter( $noptin_fields );
	}

}
