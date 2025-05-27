<?php

namespace Hizzle\Noptin\Integrations\WooCommerce;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Extends the Checkbox Integration to handle WooCommerce subscriptions.
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
		$this->slug    = 'woocommerce';
		$this->name    = 'WooCommerce';
		$this->source  = 'woocommerce_checkout';

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
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'process_checkout' ), 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'process_checkout' ), 1 );
	}

	/**
	 * Hooks the display checkbox code.
	 *
	 * @since 1.2.6
	 * @param string $checkbox_position The checkbox position to display the checkbox.
	 */
	public function hook_show_checkbox_code( $checkbox_position ) {

		if ( $this->can_show_checkbox() ) {
			if ( 'after_email_field' === $checkbox_position ) {
				add_filter( 'woocommerce_form_field_email', array( $this, 'add_checkbox_after_email_field' ), 100, 2 );
			} else {
				add_action( $checkbox_position, array( $this, 'output_checkbox' ), 20 );
			}

			// hooks for when using WooCommerce Checkout Block.
			add_action( 'woocommerce_init', array( $this, 'add_checkout_block_field' ) );

			if ( (bool) get_noptin_option( $this->get_autotick_checkbox_option_name() ) ) {
				add_filter(
					'woocommerce_get_default_value_for_noptin/optin',
					function ( $value ) {
						return '1';
					}
				);
			}

			if ( ! is_admin() ) {
				add_action(
					'woocommerce_set_additional_field_value',
					function ( $key, $value, $group, $wc_object ) {
						if ( 'noptin/optin' === $key ) {
							$wc_object->update_meta_data( 'noptin_opted_in', $value, true );
						}
					},
					10,
					4
				);
			}
		}

		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_woocommerce_checkout_checkbox_value' ) );
	}

	/**
	 * Returns an array of subscription checkbox positions.
	 *
	 * @since 1.2.6
	 * @return array
	 */
	public function checkbox_positions() {
		return apply_filters(
			'noptin_woocommerce_integration_subscription_checkbox_positions',
			array(
				'after_email_field'                       => __( 'After email field', 'newsletter-optin-box' ),
				'woocommerce_checkout_billing'            => __( 'After billing details', 'newsletter-optin-box' ),
				'woocommerce_checkout_shipping'           => __( 'After shipping details', 'newsletter-optin-box' ),
				'woocommerce_checkout_after_customer_details' => __( 'After customer details', 'newsletter-optin-box' ),
				'woocommerce_review_order_before_payment' => __( 'After order review', 'newsletter-optin-box' ),
				'woocommerce_review_order_before_submit'  => __( 'Before submit button', 'newsletter-optin-box' ),
				'woocommerce_after_order_notes'           => __( 'After order notes', 'newsletter-optin-box' ),
			)
		);
	}

	public function add_checkout_block_field() {
		// for compatibility with older WooCommerce versions
		// check if function exists before calling
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		// get the location from the legacy method
		switch ( $this->get_checkbox_position() ) {
			case 'woocommerce_review_order_before_payment':
			case 'woocommerce_review_order_before_submit':
			case 'woocommerce_after_order_notes':
				$location = 'order';
				break;
			case 'woocommerce_checkout_billing':
			case 'woocommerce_checkout_shipping':
				$location = 'address';
				break;
			default:
				$location = 'contact';
				break;
		}

		try {
			woocommerce_register_additional_checkout_field(
				array(
					'id'            => 'noptin/optin',
					'location'      => $location,
					'type'          => 'checkbox',
					'label'         => $this->get_label_text(),
					'optionalLabel' => $this->get_label_text(),
				)
			);
		} catch ( \Exception $e ) {
			noptin_error_log( 'Error registering WooCommerce checkout field: ' . $e->getMessage() );
		}
	}

	/**
	 * Adds the checkbox after an email field.
	 *
	 * @return bool
	 */
	public function add_checkbox_after_email_field( $field, $key ) {
		if ( 'billing_email' !== $key ) {
			return $field;
		}

		return $this->append_checkbox( $field );
	}

	/**
	 * Prints the checkbox wrapper.
	 *
	 */
	public function before_checkbox_wrapper() {

		if ( 'woocommerce_checkout_after_customer_details' !== $this->get_checkbox_position() ) {
			echo '<p class="form-row form-row-wide" id="noptin_woocommerce_optin_checkbox">';
		}
	}

	/**
	 * Prints the checkbox closing wrapper.
	 *
	 */
	public function after_checkbox_wrapper() {

		if ( 'woocommerce_checkout_after_customer_details' !== $this->get_checkbox_position() ) {
			echo '</p>';
		}
	}

	/**
	 * Get a string of attributes for the checkbox element.
	 *
	 * @return array
	 * @since 1.2.6
	 */
	protected function get_checkbox_attributes() {

		$attributes = parent::get_checkbox_attributes();

		$attributes['class'] = 'input-checkbox' . ( ! empty( $attributes['class'] ) ? ' ' . $attributes['class'] : '' );

		return $attributes;
	}

	/**
	 * Did the user check the email subscription checkbox?
	 *
	 * @param \WC_Order $order
	 */
	public function save_woocommerce_checkout_checkbox_value( $order ) {
		if ( $this->checkbox_was_checked() ) {
			$order->update_meta_data( 'noptin_opted_in', 1 );
		} else {
			$order->delete_meta_data( 'noptin_opted_in' );
		}
	}

	/**
	 * Was integration triggered?
	 *
	 * @param \WC_Order $order Order id or object.
	 * @return bool
	 */
	public function triggered( $order = null ) {

		if ( empty( $order ) ) {
			return false;
		}

		// This is processed later.
		if ( 'checkout-draft' === $order->get_status() && ! doing_action( 'woocommerce_store_api_checkout_order_processed' ) ) {
			return false;
		}

		if ( $this->auto_subscribe() ) {
			return true;
		}

		// Ensure checkbox was checked.
		$checked = $order->get_meta( 'noptin_opted_in', true );

		return ! empty( $checked );
	}

	/**
	 * Processes the checkout.
	 *
	 * @param \WC_Order $order Order id or object.
	 */
	public function process_checkout( $order ) {

		// If the order is not an object, try to fetch it.
		$order = is_object( $order ) ? $order : wc_get_order( $order );
		if ( ! $order ) {
			return;
		}

		// Should we process the order?
		if ( ! $this->triggered( $order ) ) {
			return null;
		}

		// Prepare order data.
		$order_data = array(
			'user_id'           => $order->get_user_id(),
			'ip_address'        => $order->get_customer_ip_address(),
			'phone'             => $order->get_billing_phone(),
			'company'           => $order->get_billing_company(),
			'address_1'         => $order->get_billing_address_1(),
			'address_2'         => $order->get_billing_address_2(),
			'postcode'          => $order->get_billing_postcode(),
			'city'              => $order->get_billing_city(),
			'state'             => $order->get_billing_state(),
			'country'           => $order->get_billing_country(),
			'country_short'     => $order->get_billing_country(),
			'formatted_address' => $order->get_formatted_billing_address(),
		);

		if ( ! empty( $order_data['country'] ) ) {
			$countries             = WC()->countries->get_countries();
			$order_data['country'] = isset( $countries[ $order_data['country'] ] ) ? $countries[ $order_data['country'] ] : $order_data['country'];
		}

		// Process the submission.
		$this->process_submission(
			array_merge(
				array(
					'source'     => $this->source,
					'email'      => $order->get_billing_email(),
					'name'       => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
					'first_name' => $order->get_billing_first_name(),
					'last_name'  => $order->get_billing_last_name(),
					'ip_address' => $order->get_customer_ip_address(),
					'language'   => apply_filters( 'noptin_woocommerce_order_locale', get_locale(), $order->get_id() ),
				),
				$this->map_custom_fields( $order_data )
			)
		);
	}

	/**
	 * @inheritdoc
	 */
	public function custom_fields() {
		return array(
			'user_id'           => __( 'User ID', 'newsletter-optin-box' ),
			'phone'             => __( 'Billing Phone', 'newsletter-optin-box' ),
			'company'           => __( 'Billing Company', 'newsletter-optin-box' ),
			'address_1'         => __( 'Billing Address 1', 'newsletter-optin-box' ),
			'address_2'         => __( 'Billing Address 2', 'newsletter-optin-box' ),
			'postcode'          => __( 'Billing Postcode', 'newsletter-optin-box' ),
			'city'              => __( 'Billing City', 'newsletter-optin-box' ),
			'state'             => __( 'Billing State', 'newsletter-optin-box' ),
			'country'           => __( 'Billing Country', 'newsletter-optin-box' ),
			'country_short'     => __( 'Billing Country Code', 'newsletter-optin-box' ),
			'formatted_address' => __( 'Formatted Billing Address', 'newsletter-optin-box' ),
		);
	}
}
