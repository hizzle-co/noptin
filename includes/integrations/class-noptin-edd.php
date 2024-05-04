<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with EDD
 *
 * @since       1.2.6
 */
class Noptin_EDD extends Noptin_Abstract_Ecommerce_Integration {

	/**
	 * @var string source of subscriber.
	 * @since 1.7.0
	 */
	public $subscriber_via = 'edd_checkout';

	/**
	 * Init variables.
	 *
	 * @since       1.2.6
	 */
	public function before_initialize() {
		$this->order_label = __( 'Payments', 'newsletter-optin-box' );
		$this->slug        = 'edd';
		$this->name        = 'Easy Digital Downloads';
	}

	/**
	 * Setup hooks in case the integration is enabled.
	 *
	 * @since 1.2.6
	 */
	public function initialize() {
		add_action( 'edd_payment_saved', array( $this, 'add_order_subscriber' ), 50 );
	}

	/**
	 * Hooks the display checkbox code.
	 *
	 * @since 1.2.6
	 * @param string $checkbox_position The checkbox position to display the checkbox.
	 */
	public function hook_show_checkbox_code( $checkbox_position ) {
		add_action( $checkbox_position, array( $this, 'output_checkbox' ), 1000 );
		add_filter( 'edd_payment_meta', array( $this, 'save_edd_checkout_checkbox_value' ) );
	}

	/**
	 * Returns an array of subscription checkbox positions.
	 *
	 * @since 1.2.6
	 * @return array
	 */
	public function checkbox_positions() {
		return array(
			'edd_purchase_form_after_email'      => __( 'After email field', 'newsletter-optin-box' ),
			'edd_purchase_form_user_info_fields' => __( 'After customer details', 'newsletter-optin-box' ),
			'edd_purchase_form_before_cc_form'   => __( 'Before payment details', 'newsletter-optin-box' ),
			'edd_purchase_form_after_cc_form'    => __( 'After payment details', 'newsletter-optin-box' ),
			'edd_purchase_form_before_submit'    => __( 'Before checkout submit', 'newsletter-optin-box' ),
			'edd_purchase_form_after_submit'     => __( 'After checkout submit', 'newsletter-optin-box' ),
		);
	}

	/**
	 * Did the user check the email subscription checkbox?
	 *
	 * @param array $payment_meta An array of payment meta.
	 * @since 1.2.6
	 * @return array
	 */
	public function save_edd_checkout_checkbox_value( $payment_meta ) {

		if ( $this->checkbox_was_checked() ) {
			$payment_meta['_noptin_optin'] = 1;
		}

		return $payment_meta;
	}

	/**
	 * Was integration triggered?
	 *
	 * @param int $payment_id Payment being executed.
	 * @since 1.2.6
	 * @return bool
	 */
	public function triggered( $payment_id = null ) {
		$meta    = edd_get_payment_meta( $payment_id );
		$checked = is_array( $meta ) && isset( $meta['_noptin_optin'] ) && $meta['_noptin_optin'];
		return $this->auto_subscribe() || $checked;
	}

	/**
	 * Returns the checkbox message default value.
	 *
	 * @since 1.2.6
	 * @return string
	 */
	public function get_checkbox_message_integration_default_value() {
		return __( 'Add me to your newsletter and keep me updated whenever you publish new downloads', 'newsletter-optin-box' );
	}

	/**
	 * Prints the checkbox wrapper.
	 *
	 */
	public function before_checkbox_wrapper() {

		$checkbox_position = $this->get_checkbox_position();
		$style             = 'edd_purchase_form_after_email' !== $checkbox_position && 'edd_purchase_form_user_info_fields' !== $checkbox_position;

		echo '<p id="noptin_edd_optin_checkbox"' . ( $style ? ' style="padding-left: 0;"' : '' ) . '>';
	}

	/**
	 * Prints the checkbox closing wrapper.
	 *
	 */
	public function after_checkbox_wrapper() {
		echo '</p>';
	}

	/**
	 * Returns the email address of the customer associated with an order.
	 *
	 * @param int $payment_id The order id.
	 * @since 1.2.6
	 * @return string
	 */
	public function get_order_customer_email( $payment_id ) {
		return edd_get_payment_user_email( $payment_id );
	}

	/**
	 * Returns the id of the customer associated with an order.
	 *
	 * @param int $payment_id The order id.
	 * @since 1.2.6
	 * @return int
	 */
	public function get_order_customer_user_id( $payment_id ) {
		return edd_get_payment_user_id( $payment_id );
	}

	/**
	 * Returns order customer details
	 *
	 * @param int $payment_id The order id.
	 * @param bool $existing_subscriber Whether this is an existing subscriber or not.
	 * @since 1.2.6
	 * @return array
	 */
	public function get_order_customer_details( $payment_id, $existing_subscriber = false ) {

		// Fetch the payment.
		$payment = new EDD_Payment( $payment_id );
		if ( empty( $payment ) || empty( $payment->email ) ) {
			return array();
		}

		// Prepare subscriber details.
		$noptin_fields = array(
			'source'     => 'edd_checkout',
			'payment_id' => $payment_id,
			'email'      => $payment->email,
			'address_1'  => $payment->address['line1'],
			'address_2'  => $payment->address['line2'],
			'postcode'   => $payment->address['zip'],
			'city'       => $payment->address['city'],
			'state'      => $payment->address['state'],
			'country'    => $payment->address['country'],
			'wp_user_id' => $payment->user_id,
			'first_name' => $payment->first_name,
			'last_name'  => $payment->last_name,
		);

		if ( $existing_subscriber ) {
			unset( $noptin_fields['source'] );
			unset( $noptin_fields['payment_id'] );
		}

		return array_filter( $noptin_fields );
	}

	/**
	 * @inheritdoc
	 */
	public function available_customer_fields() {
		return array(
			'address_1' => __( 'Billing Address 1', 'newsletter-optin-box' ),
			'address_2' => __( 'Billing Address 2', 'newsletter-optin-box' ),
			'postcode'  => __( 'Billing Postcode', 'newsletter-optin-box' ),
			'city'      => __( 'Billing City', 'newsletter-optin-box' ),
			'state'     => __( 'Billing State', 'newsletter-optin-box' ),
			'country'   => __( 'Billing Country', 'newsletter-optin-box' ),
		);
	}

	/**
	 * Fired when an order is refunded.
	 *
	 * @param EDD_Payment $payment The Payment Object.
	 * @since 1.2.6
	 */
	public function order_refunded( $payment ) {
		parent::order_refunded( $payment->ID );
	}
}
