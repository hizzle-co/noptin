<?php

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Class for integrating with WooCommerce Blocks
 */
class Noptin_WooCommerce_Checkout_Block_Integration implements IntegrationInterface {

	/**
	 * WooCommerce integration bridget.
	 *
	 * @var Noptin_WooCommerce
	 */
	protected $wc;

	/**
	 * Constructor.
	 *
	 * @param Noptin_WooCommerce $wc WooCommerce integration bridget.
	 */
	public function __construct( $wc ) {
		$this->wc = $wc;
	}

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'noptin';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {

		$this->register_frontend_scripts();
		$this->register_editor_scripts();
		$this->register_editor_blocks();
		$this->extend_store_api();

	}

	/**
	 * Register frontend scripts.
	 */
	public function register_frontend_scripts() {
		$script_url  = noptin()->plugin_url . 'includes/assets/js/dist/blocks-woocommerce-frontend.js';
		$script_data = include noptin()->plugin_path . 'includes/assets/js/dist/blocks-woocommerce-frontend.asset.php';

		wp_register_script(
			'wc-blocks-noptin-integration',
			$script_url,
			$script_data['dependencies'],
			$script_data['version'],
			true
		);

		wp_set_script_translations(
			'wc-blocks-noptin-integration',
			'newsletter-optin-box',
			plugin_basename( dirname( Noptin::$file ) ) . '/languages/'
		);
	}

	/**
	 * Register editor scripts.
	 */
	public function register_editor_scripts() {
		$script_url  = noptin()->plugin_url . 'includes/assets/js/dist/blocks-woocommerce-backend.js';
		$script_data = include noptin()->plugin_path . 'includes/assets/js/dist/blocks-woocommerce-backend.asset.php';

		wp_register_script(
			'wc-blocks-noptin-integration-editor',
			$script_url,
			$script_data['dependencies'],
			$script_data['version'],
			true
		);

		wp_set_script_translations(
			'wc-blocks-noptin-integration-editor',
			'newsletter-optin-box',
			plugin_basename( dirname( Noptin::$file ) ) . '/languages/'
		);
	}

	/**
	 * Register blocks.
	 */
	public function register_editor_blocks() {
		register_block_type(
			noptin()->plugin_path . 'includes/assets/js/dist/woocommerce-block.json',
			array(
				'editor_script' => 'wc-blocks-noptin-integration-editor',
			)
		);
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'wc-blocks-noptin-integration' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'wc-blocks-noptin-integration-editor' );
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {

		return array(
			'defaultText'   => $this->wc->get_label_text(),
			'position'      => $this->get_checkbox_position(),
			'defaultStatus' => (bool) get_noptin_option( $this->wc->get_autotick_checkbox_option_name() ),
			'optinEnabled'  => $this->wc->is_enabled() && ! $this->wc->auto_subscribe(),
			'adminUrl'      => admin_url(),
		);

	}

	/**
	 * Returns the checkbox position.
	 *
	 * @return string
	 */
	public function get_checkbox_position() {

		$position  = $this->wc->get_checkbox_position();
		$positions = array(
			'after_email_field'                           => 'woocommerce/checkout-contact-information-block',
			'woocommerce_checkout_billing'                => 'woocommerce/checkout-billing-address-block',
			'woocommerce_checkout_shipping'               => 'woocommerce/checkout-shipping-methods-block',
			'woocommerce_checkout_after_customer_details' => 'woocommerce/checkout-contact-information-block',
			'woocommerce_review_order_before_payment'     => 'woocommerce/checkout-payment-methods-block',
			'woocommerce_review_order_before_submit'      => 'woocommerce/checkout-totals-block',
			'woocommerce_after_order_notes'               => 'woocommerce/checkout-fields-block',
		);

		return isset( $positions[ $position ] ) ? $positions[ $position ] : 'woocommerce/checkout-contact-information-block';
	}

	/**
	 * Add schema Store API to support posted data.
	 */
	public function extend_store_api() {

		if ( function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			woocommerce_store_api_register_endpoint_data(
				array(
					'endpoint'        => 'checkout',
					'namespace'       => $this->get_name(),
					'schema_callback' => function() {
						return array(
							'optin' => array(
								'description' => __( 'Subscribe to marketing opt-in.', 'newsletter-optin-box' ),
								'type'        => 'boolean',
								'context'     => array(),
								'arg_options' => array(
									'validate_callback' => '__return_true',
								),
							),
						);
					},
				)
			);
		}

	}

}
