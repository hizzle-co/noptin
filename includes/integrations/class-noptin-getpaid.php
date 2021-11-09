<?php
/**
 * Contains the GetPaid and Noptin integration details.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The GetPaid and Noptin integration class.
 *
 * @since       1.4.1
 */
class Noptin_GetPaid extends Noptin_Abstract_Ecommerce_Integration {

	/**
	 * @var string Slug, used as a unique identifier for this integration.
	 * @since 1.4.1
	 */
	public $slug = 'getpaid';

	/**
	 * @var string The product's post type in case this integration saves products as custom post types.
	 * @since 1.4.1
	 */
	public $product_post_type = array( 'wpi_item' );

	/**
	 * @var string Name of this integration.
	 * @since 1.4.1
	 */
	public $name = 'GetPaid';

	/**
	 * Setup hooks in case the integration is enabled.
	 *
	 * @since 1.4.1
	 */
	public function initialize() {

		parent::initialize();

		// Invoice.
		add_action( 'getpaid_checkout_invoice_updated', array( $this, 'add_order_subscriber' ), $this->priority );
		add_action( 'getpaid_checkout_invoice_updated', array( $this, 'checkout_processed' ), $this->priority );
		add_action( 'getpaid_invoice_status_publish', array( $this, 'order_completed' ), $this->priority );
		add_action( 'getpaid_invoice_status_publish', array( $this, 'order_paid' ), $this->priority );
		add_action( 'getpaid_invoice_status_wpi-refunded', array( $this, 'order_refunded' ), $this->priority );
		add_action( 'getpaid_new_invoice', array( $this, 'order_created' ), $this->priority );
		add_action( 'getpaid_update_invoice', array( $this, 'order_updated' ), $this->priority );
		add_action( 'getpaid_invoice_status_wpi-pending', array( $this, 'order_pending' ), $this->priority );
		add_action( 'getpaid_invoice_status_wpi-processing', array( $this, 'order_processing' ), $this->priority );
		add_action( 'getpaid_invoice_status_wpi-onhold', array( $this, 'order_held' ), $this->priority );
		add_action( 'getpaid_invoice_status_wpi-cancelled', array( $this, 'order_cancelled' ), $this->priority );
		add_action( 'getpaid_invoice_status_wpi-failed', array( $this, 'order_failed' ), $this->priority );

		// Items.
		// add_action( 'getpaid_update_item', array( $this, 'product_updated' ), $this->priority );
		// add_action( 'getpaid_new_item', array( $this, 'product_updated' ), $this->priority );
		remove_action( 'save_post', array( $this, 'product_updated' ), $this->priority );

		// Automation rules.
		add_action( 'noptin_automation_rules_load', array( $this, 'register_automation_rules' ), $this->priority );

	}

	/**
	 * Registers GetPaid automation rules.
	 *
	 * @since 1.4.1
	 * @param Noptin_Automation_Rules $rules The automation rules class.
	 */
	public function register_automation_rules( $rules ) {
		$rules->add_trigger( new Noptin_GetPaid_New_Invoice_Trigger( $this ) );
		$rules->add_trigger( new Noptin_GetPaid_Item_Purchase_Trigger( $this ) );
		$rules->add_trigger( new Noptin_GetPaid_Lifetime_Value_Trigger( $this ) );
	}

	/**
	 * Hooks the display checkbox code.
	 *
	 * @since 1.4.1
	 * @param string $checkbox_position The checkbox position to display the checkbox.
	 */
	public function hook_show_checkbox_code( $checkbox_position ) {
		add_action( $checkbox_position, array( $this, 'output_checkbox' ), 20 );
		add_action( 'getpaid_checkout_invoice_updated', array( $this, 'save_getpaid_checkout_checkbox_value' ), 0 );
	}

	/**
	 * Returns an array of subscription checkbox positions.
	 *
	 * @since 1.2.6
	 * @return array
	 */
	public function checkbox_positions() {
		return array(
			'getpaid_after_payment_form_billing_email'   => __( 'After email field', 'newsletter-optin-box' ),
			'getpaid_before_payment_form_pay_button'     => __( 'Before payment button', 'newsletter-optin-box' ),
			'getpaid_after_payment_form_pay_button'      => __( 'After payment button', 'newsletter-optin-box' ),
			'getpaid_after_payment_form_billing_fields'  => __( 'After billing fields', 'newsletter-optin-box' ),
			'getpaid_after_payment_form_shipping_fields' => __( 'After shipping fields', 'newsletter-optin-box' ),
			'getpaid_before_payment_form_gateway_select' => __( 'Before gateways', 'newsletter-optin-box' ),
			'getpaid_after_payment_form_gateway_select'  => __( 'After gateways', 'newsletter-optin-box' ),
		);
	}

	/**
	 * Checks if the user checked the GetPaid email subscription checkbox?
	 *
	 * @since 1.4.1
	 * @param WPInv_Invoice $invoice
	 */
	public function save_getpaid_checkout_checkbox_value( $invoice ) {
		if ( $this->checkbox_was_checked() ) {
			update_post_meta( $invoice->get_id(), 'noptin_opted_in', 1 );
		}
	}

	/**
	 * Checks if the GetPaid integration was triggered for the given invoice id.
	 *
	 * @param int $invoice_id Invoice id being checked.
	 * @since 1.4.1
	 * @return bool
	 */
	public function triggered( $invoice_id = null ) {
		$invoice_id = $this->prepare_order_id( $invoice_id );
		$checked    = get_post_meta( $invoice_id, 'noptin_opted_in', true );
		return $this->auto_subscribe() || ! empty( $checked );
	}

	/**
	 * Returns the subscription checkbox markup.
	 *
	 * @param array $html_attrs An array of HTML attributes.
	 * @since 1.4.1
	 * @return string
	 */
	public function get_checkbox_markup( array $html_attrs = array() ) {

		// Abort if we're not displaying a checkbox.
		if ( ! $this->can_show_checkbox() ) {
			return '';
		}

		ob_start();

		// Checkbox opening wrapper.
		echo '<!-- Noptin Newsletters - https://noptin.com/ -->';
		do_action( 'noptin_integration_before_subscription_checkbox_wrapper', $this );
		do_action( 'noptin_integration_' . $this->slug . '_before_subscription_checkbox_wrapper', $this );

		echo aui()->input(
			array(
				'type'       => 'checkbox',
				'name'       => 'noptin-subscribe',
				'id'         => esc_attr( 'noptin' ) . uniqid( '_' ),
				'class'      => sprintf( ' noptin-integration-subscription-checkbox noptin-integration-subscription-checkbox-%s', $this->slug ),
				'required'   => false,
				'label'      => $this->get_label_text(),
				'value'      => 1,
			)
		);

		// Checkbox closing wrapper.
		do_action( 'noptin_integration_after_subscription_checkbox_wrapper', $this );
		do_action( 'noptin_integration_' . $this->slug . '_after_subscription_checkbox_wrapper', $this );
		echo '<!-- / Noptin Newsletters -->';

		return ob_get_clean();

	}

	/**
	 * Returns the checkbox message default value.
	 *
	 * @since 1.4.1
	 */
	public function get_checkbox_message_integration_default_value() {
		return __( 'Add me to your newsletter and keep me updated whenever you publish new products', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_order_customer_email( $invoice_id ) {

		$invoice = new WPInv_Invoice( $invoice_id );
		if ( ! $invoice->exists() ) {
			return 0;
		}

		return $invoice->get_customer_email();

	}

	/**
	 * @inheritdoc
	 */
	public function get_order_customer_user_id( $invoice_id ) {

		$invoice = new WPInv_Invoice( $invoice_id );
		if ( ! $invoice->exists() ) {
			return 0;
		}

		return $invoice->get_customer_id();

	}

	/**
	 * @inheritdoc
	 */
	public function get_order_customer_details( $invoice_id, $existing_subscriber = false ) {

		// Fetch the invoice.
		$invoice = new WPInv_Invoice( $invoice_id );
		if ( ! $invoice->exists() ) {
			return array();
		}

		$noptin_fields = array();

		if ( ! $existing_subscriber ) {
			$noptin_fields = array(
				'_subscriber_via' => 'getpaid_checkout',
				'invoice_id'      => $invoice_id,
			);
		}

		$noptin_fields['email']             = $invoice->get_email();
		$noptin_fields['name']              = $invoice->get_full_name();
		$noptin_fields['phone']             = $invoice->get_phone();
		$noptin_fields['company']           = $invoice->get_company();
		$noptin_fields['address_1']         = $invoice->get_address();
		$noptin_fields['postcode']          = $invoice->get_zip();
		$noptin_fields['city']              = $invoice->get_city();
		$noptin_fields['state']             = $invoice->get_state();
		$noptin_fields['country']           = $invoice->get_country();
		$noptin_fields['wp_user_id']        = $invoice->get_customer_id();
		$noptin_fields['ip_address']        = $invoice->get_user_ip();

		if ( ! empty( $noptin_fields['country'] ) ) {
			$noptin_fields['country_short'] = $noptin_fields['country'];
			$noptin_fields['country']       = wpinv_country_name( $noptin_fields['country'] );
		}

		return array_filter( $noptin_fields );
	}

	/**
	 * @inheritdoc
	 */
	public function available_customer_fields() {
		return array(
			'phone'         => __( 'Billing Phone', 'newsletter-optin-box' ),
			'company'       => __( 'Billing Company', 'newsletter-optin-box' ),
			'address_1'     => __( 'Billing Address 1', 'newsletter-optin-box' ),
			'postcode'      => __( 'Billing Postcode', 'newsletter-optin-box' ),
			'city'          => __( 'Billing City', 'newsletter-optin-box' ),
			'state'         => __( 'Billing State', 'newsletter-optin-box' ),
			'country'       => __( 'Billing Country', 'newsletter-optin-box' ),
			'country_short' => __( 'Billing Country Code', 'newsletter-optin-box' ),
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_order_details( $invoice_id ) {

		// Fetch the invoice.
		$invoice = new WPInv_Invoice( $invoice_id );
		if ( ! $invoice->exists() ) {
			return parent::get_order_details( $invoice_id );
		}

		return array(
			'id'       => $invoice->get_id(),
			'order_id' => $invoice->get_id(),
			'total'    => $invoice->get_total(),
			'tax'      => $invoice->get_total_tax(),
			'fees'     => $invoice->get_total_fees(),
			'currency' => $invoice->get_currency(),
			'discount' => $invoice->get_total_discount(),
			'edit_url' => get_edit_post_link( $invoice->get_id() ),
			'view_url' => $invoice->get_view_url(),
			'pay_url'  => $invoice->get_checkout_payment_url(),
			'status'   => str_replace( 'wpi-', '', $invoice->get_status() ),

			'title'    => sprintf(
				esc_html__( 'Invoice #%s from %s', 'newsletter-optin-box' ),
				$invoice->get_number(),
				$invoice->get_email()
			),

			'items'    => array_map(
				array( $this, 'get_order_item_details' ),
				$invoice->get_items()
			),
			'date_created' => $invoice->get_date_created(),
			'date_paid'    => $invoice->get_date_completed(),
		);

	}

	/**
	 * Returns an array of GetPaid invoice item details.
	 *
	 * @param GetPaid_Form_Item $item The item id.
	 * @since 1.4.1
	 * @return array
	 */
	protected function get_order_item_details( $item ) {

		return array(
			'item_id'      => $item->get_id(),
			'product_id'   => $item->get_id(),
			'variation_id' => $item->get_id(),
			'name'         => $item->get_name(),
			'price'        => $item->get_sub_total(),
			'quantity'     => $item->get_quantity(),
		);

	}

	/**
	 * @inheritdoc
	 */
	public function get_order_count( $customer_id_or_email ) {

		$customer_id = $this->get_customer_id_from_email( $customer_id_or_email );
		if ( empty( $customer_id ) ) {
			return 0;
		}

		$args = array(
			'data'             => array(

				'ID'           => array(
					'type'     => 'post_data',
					'function' => 'COUNT',
					'name'     => 'count',
					'distinct' => true,
				),

			),
			'where'            => array(

				'author'       => array(
					'type'     => 'post_data',
					'value'    => absint( $customer_id ),
					'key'      => 'posts.post_author',
					'operator' => '=',
				),

			),
			'query_type'     => 'get_var',
			'invoice_status' => array( 'publish', 'wpi-processing', 'wpi-onhold', 'wpi-refunded', 'wpi-renewal' ),
		);

		return (int) GetPaid_Reports_Helper::get_invoice_report_data( $args );

	}

	/**
	 * @inheritdoc
	 */
	public function get_total_spent( $customer_id_or_email ) {

		$customer_id = $this->get_customer_id_from_email( $customer_id_or_email );
		if ( empty( $customer_id ) ) {
			return 0;
		}

		$args = array(
			'data'             => array(

				'total'        => array(
					'type'     => 'invoice_data',
					'function' => 'SUM',
					'name'     => 'total_sales',
				)

			),
			'where'            => array(

				'author'       => array(
					'type'     => 'post_data',
					'value'    => absint( $customer_id ),
					'key'      => 'posts.post_author',
					'operator' => '=',
				),

			),
			'query_type'     => 'get_var',
			'invoice_status' => array( 'wpi-renewal', 'wpi-processing', 'publish' ),
		);

		return (float) GetPaid_Reports_Helper::get_invoice_report_data( $args );

	}

	/**
	 * @inheritdoc
	 */
	public function get_product_purchase_count( $customer_id_or_email = null, $product_id = 0 ) {

		$customer_id = $this->get_customer_id_from_email( $customer_id_or_email );
		if ( false === $customer_id ) {
			return 0;
		}

		$args = array(
			'data'             => array(
				'quantity'     => array(
					'type'     => 'invoice_item',
					'function' => 'SUM',
					'name'     => 'invoice_item_count',
				),
			),
			'where'         => array(

				'item_id'      => array(
					'type'     => 'invoice_item',
					'value'    => absint( $product_id ),
					'key'      => 'invoice_items.item_id',
					'operator' => '=',
				),

			),
			'query_type'     => 'get_var',
			'invoice_status' => array( 'wpi-renewal', 'wpi-processing', 'publish' ),
		);

		if ( is_numeric( $customer_id ) ) {

			$args['where']['author'] = array(
				'type'     => 'post_data',
				'value'    => absint( $customer_id ),
				'key'      => 'posts.post_author',
				'operator' => '=',
			);

		}

		return (int) GetPaid_Reports_Helper::get_invoice_report_data( $args );

	}

	/**
	 * Retrieves the customer id from the customer email.
	 *
	 * @since 1.4.1
	 * @return int|null|false
	 */
	public function get_customer_id_from_email( $customer_id_or_email = null ) {

		if ( is_email( $customer_id_or_email ) ) {
			$customer_id_or_email = get_user_by( 'email', $customer_id_or_email );

			if ( empty( $customer_id_or_email ) ) {
				return false;
			}

			return $customer_id_or_email->ID;
		}

		return $customer_id_or_email;

	}

	/**
	 * @inheritdoc
	 */
	public function get_products() {
		$products = get_posts(
			array(
				'numberposts' => -1,
				'fields'      => 'ids',
				'status'      => 'publish',
				'post_type'   => 'wpi_item'
			)
		);

		return array_filter(
			array_map( array( $this, 'get_product_details' ), $products )
		);

	}

	/**
	 * @inheritdoc
	 */
	public function get_product_details( $product_id ) {
		$item = new WPInv_Item( $product_id );

		if ( ! $item->exists() ) {
			return array();
		}

		$details = array(
			'id'                 => $item->get_id(),
			'name'               => $item->get_name(),
			'description'        => $item->get_description(),
			'url'                => get_permalink( $item->get_id() ),
			'price'              => $item->get_price(),
			'type'               => $item->get_type(),
			'sku'                => $item->get_id(),
			'images'             => array( get_the_post_thumbnail_url( $item->get_id() ) ),
		);

		$quantity = get_post_meta( $item->get_id(), '_stock', true );

		if ( false === $quantity || '' === $quantity ) {
			$quantity = null;
		} else {
			$quantity = (int) $quantity;
		}

		$details['inventory_quantity'] = $quantity;

		return $details;

	}

}
