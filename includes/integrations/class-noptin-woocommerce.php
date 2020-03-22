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
class Noptin_WooCommerce extends Noptin_Abstract_Integration {

	/**
	 * Init variables.
	 */
	public function before_initialize() {

		$this->slug = 'woocommerce';
		$this->name = 'WooCommerce';

	}

	/**
	 * Setup hooks.
	 */
	public function initialize() {

		// Display a subscription checkbox.
		$checkbox_position = get_noptin_option( 'noptin_woocommerce_integration_checkout_position' );
		if ( ! empty( $checkbox_position ) ) {

			if ( 'after_email_field' === $checkbox_position ) {
				add_filter( 'woocommerce_form_field_email', array( $this, 'add_checkbox_after_email_field' ), 100, 2 );
			} else {
				add_action( $checkbox_position, array( $this, 'output_checkbox' ), 20 );
			}

			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_woocommerce_checkout_checkbox_value' ) );

		}

		add_action( 'woocommerce_checkout_order_processed', array( $this, 'subscribe_from_woocommerce_checkout' ) );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'checkout_processed' ), 20 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'order_completed' ), 20 );
		add_action( 'woocommerce_payment_complete', array( $this, 'order_paid' ), 20 );
		add_action( 'woocommerce_order_refunded', array( $this, 'order_refunded' ), 20 );

	}

	/**
	 * Integration options.
	 */
	public function get_options( $options ) {

		$options = $this->add_enable_integration_option( $options );

		$options = $this->add_autosubscribe_integration_option( 
			$options,
			null,
			__( 'Check to display a subscription checkbox instead of automatically subscribing new customers.', 'newsletter-optin-box' ),
			'false'
		);

		$options['noptin_woocommerce_integration_checkout_position'] = array(
            'el'                    => 'select',
            'section'		        => 'integrations',
            'label'                 => __( 'Checkbox position', 'newsletter-optin-box' ),
			'description'           => __( 'Where should we add a newsletter subscription checkbox?', 'newsletter-optin-box' ),
			'restrict'              => 'noptin_enable_woocommerce_integration && noptin_woocommerce_integration_manual_subscription',
			'options'               => array(
				'after_email_field'                           => __( 'After email field', 'newsletter-optin-box' ),
				'woocommerce_checkout_billing'                => __( 'After billing details', 'newsletter-optin-box' ),
				'woocommerce_checkout_shipping'               => __( 'After shipping details', 'newsletter-optin-box' ),
				'woocommerce_checkout_after_customer_details' => __( 'After customer details', 'newsletter-optin-box' ),
				'woocommerce_review_order_before_submit'      => __( 'Before submit button', 'newsletter-optin-box' ),
				'woocommerce_after_order_notes'               => __( 'After order notes', 'newsletter-optin-box' ),
			),
			'placeholder'           => __( 'Do not subscribe new customers', 'newsletter-optin-box' ),
		);

		$options = $this->add_checkbox_message_integration_option( $options );
		$options[ $this->get_checkbox_message_integration_option_name() ]['restrict'] .= ' && noptin_woocommerce_integration_checkout_position';

		return $options;
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
	 * Get a string of attributes for the checkbox element.
	 *
	 * @return string
	 */
	protected function get_checkbox_attributes() {

		$attributes = array(
			'type'  => 'checkbox',
			'name'  => 'noptin-subscribe',
			'value' => '1',
			'class' => 'input-checkbox',
		);
		$attributes = (array) apply_filters( 'noptin_integration_subscription_checkbox_attributes', $attributes, $this );

		$attributes = (array) apply_filters( "noptin_{$this->slug}_integration_subscription_checkbox_attributes", $attributes, $this );

		$string = '';
		foreach ( $attributes as $key => $value ) {
			$string .= sprintf( '%s="%s"', $key, esc_attr( $value ) );
		}

		return $string;
	}

	/**
	 * Returns the checkbox message default value.
	 */
	public function get_checkbox_message_integration_default_value() {
		return __( 'Add me to your newsletter and keep me updated whenever your publish new products', 'newsletter-optin-box' );
	}

	/**
	 * Subscribes a new customer after their order is processed.
	 * 
	 * @param int $order_id The order id.
	 * @return boolean
	 */
	public function subscribe_from_woocommerce_checkout( $order_id ) {

		// Should we process the subsriber?
		if ( ! $this->triggered( $order_id ) ) {
			return false;
		}

		// Fetch the order.
		$order = wc_get_order( $order_id );
		if ( empty( $order ) ) {
			return false;
		}

		// Prepare subscriber details.
		$noptin_fields = array(
			'_subscriber_via' => 'woocommerce_checkout',
			'order_id'        => $order_id,
		);

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

		$this->add_subscriber( array_filter( $noptin_fields ), $order );

	}

	/**
	 * Returns a subcriber id responsible for an order.
	 * 
	 * @param int $order_id The order id.
	 * @return int|null The subscriber id.
	 */
	public function get_order_subscriber( $order_id ) {

		// Fetch the order.
		$order = wc_get_order( $order_id );
		if ( empty( $order ) ) {
			return null;
		}

		// (Maybe) Subscriber's email address.
		if ( method_exists( $order, 'get_billing_email' ) ) {
			$email = $order->get_billing_email();
		} else {
			$email = $order->billing_email;
		}

		return get_noptin_subscriber_id_by_email( $email );

	}

	/**
	 * Fires a specific hook based on an order status.
	 * 
	 * @param int $order_id The order id.
	 * @param int|null $subscriber_id The order subscriber.
	 */
	public function fire_order_hook( $status, $order_id, $subscriber_id ) {

		// Only fired when there is actually a subcsriber.
		if ( $subscriber_id ) {
			do_action( "noptin_woocommerce_integration_order_$status", $order_id, $subscriber_id );
			do_action( "noptin_woocommerce_integration_order", $status, $order_id, $subscriber_id );
		}

	}

	/**
	 * Fired when an order is processed.
	 * 
	 * @param int $order_id The order id.
	 */
	public function checkout_processed( $order_id ) {
		$subscriber_id = $this->get_order_subscriber( $order_id );
		$this->fire_order_hook( 'processed', $order_id, $subscriber_id );
	}

	/**
	 * Fired when an order is completed.
	 * 
	 * @param int $order_id The order id.
	 */
	public function order_completed( $order_id ) {
		$subscriber_id = $this->get_order_subscriber( $order_id );
		$this->fire_order_hook( 'completed', $order_id, $subscriber_id );
	}

	/**
	 * Fired when an order is paid for.
	 * 
	 * @param int $order_id The order id.
	 */
	public function order_paid( $order_id ) {
		$subscriber_id = $this->get_order_subscriber( $order_id );
		$this->fire_order_hook( 'paid', $order_id, $subscriber_id );
	}

	/**
	 * Fired when an order is refunded.
	 * 
	 * @param int $order_id The order id.
	 */
	public function order_refunded( $order_id ) {
		$subscriber_id = $this->get_order_subscriber( $order_id );
		$this->fire_order_hook( 'refunded', $order_id, $subscriber_id );
	}

}
