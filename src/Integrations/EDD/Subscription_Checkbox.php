<?php

namespace Hizzle\Noptin\Integrations\EDD;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Extends the Checkbox Integration to handle EDD subscriptions.
 *
 * @since 3.0.0
 */
class Subscription_Checkbox extends \Hizzle\Noptin\Integrations\Checkbox_Integration {

	/**
	 * Constructor
	 */
	public function __construct() {

		// Set the context, slug, name and source.
		$this->context = __( 'customers', 'newsletter-optin-box' );
		$this->slug    = 'edd';
		$this->name    = 'Easy Digital Downloads';
		$this->source  = 'edd_checkout';
		$this->url     = '/getting-email-subscribers/edd-checkout/';

		// Parent constructor.
		parent::__construct();
	}

	/**
	 * Setup hooks in case the integration is enabled.
	 *
	 * @since 1.2.6
	 */
	public function initialize() {

		parent::initialize();

		// Listen to checkout submission.
		add_action( 'edd_complete_purchase', array( $this, 'process_checkout' ), 1, 2 );
	}

	/**
	 * Hooks the display checkbox code.
	 *
	 * @since 1.2.6
	 * @param string $checkbox_position The checkbox position to display the checkbox.
	 */
	public function hook_show_checkbox_code( $checkbox_position ) {

		if ( $this->can_show_checkbox() ) {
			add_action( $checkbox_position, array( $this, 'output_checkbox' ), 1000 );
			add_filter( 'edd_payment_meta', array( $this, 'save_edd_checkout_checkbox_value' ) );
		}
	}

	/**
	 * Returns an array of subscription checkbox positions.
	 *
	 * @since 1.2.6
	 * @return array
	 */
	public function checkbox_positions() {
		return apply_filters(
			'noptin_edd_integration_subscription_checkbox_positions',
			array(
				'edd_purchase_form_after_email'      => __( 'After email field', 'newsletter-optin-box' ),
				'edd_purchase_form_user_info_fields' => __( 'After customer details', 'newsletter-optin-box' ),
				'edd_purchase_form_before_cc_form'   => __( 'Before payment details', 'newsletter-optin-box' ),
				'edd_purchase_form_after_cc_form'    => __( 'After payment details', 'newsletter-optin-box' ),
				'edd_purchase_form_before_submit'    => __( 'Before checkout submit', 'newsletter-optin-box' ),
				'edd_purchase_form_after_submit'     => __( 'After checkout submit', 'newsletter-optin-box' ),
			)
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
	 * Processes the checkout.
	 *
	 * @param int $order_id Order id.
	 * @param \EDD_Payment  $payment    EDD_Payment object containing all payment data.
	 */
	public function process_checkout( $order_id, $payment ) {

		// Fetch the payment.
		if ( empty( $payment ) || empty( $payment->email ) ) {
			return array();
		}

		// Should we process the order?
		if ( ! $this->triggered( $order_id ) ) {
			return null;
		}

		// Process the submission.
		$this->process_submission(
			array_merge(
				array(
					'source'     => $this->source,
					'email'      => $payment->email,
					'name'       => trim( $payment->first_name . ' ' . $payment->last_name ),
					'first_name' => $payment->first_name,
					'last_name'  => $payment->last_name,
					'ip_address' => $payment->ip,
				),
				$payment->address ?? array(),
			)
		);
	}

	/**
	 * @inheritdoc
	 */
	public function custom_fields() {
		return array(
			'name'       => __( 'Name', 'newsletter-optin-box' ),
			'first_name' => __( 'First Name', 'newsletter-optin-box' ),
			'last_name'  => __( 'Last Name', 'newsletter-optin-box' ),
			'line1'      => __( 'Billing Address 1', 'newsletter-optin-box' ),
			'line2'      => __( 'Billing Address 2', 'newsletter-optin-box' ),
			'zip'        => __( 'Billing Postcode', 'newsletter-optin-box' ),
			'city'       => __( 'Billing City', 'newsletter-optin-box' ),
			'state'      => __( 'Billing State', 'newsletter-optin-box' ),
			'country'    => __( 'Billing Country', 'newsletter-optin-box' ),
		);
	}
}
