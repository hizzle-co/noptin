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
	 * @var string The product's post type in case this integration saves products as custom post types.
	 * @since 1.3.0
	 */
	public $product_post_type = array( 'product', 'product_variation' );

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

		parent::initialize();

		// Orders.
		add_action( 'woocommerce_new_order', array( $this, 'add_order_subscriber' ), 1 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'checkout_processed' ), $this->priority );
		add_action( 'woocommerce_order_status_completed', array( $this, 'order_completed' ), $this->priority );
		add_action( 'woocommerce_payment_complete', array( $this, 'order_paid' ), $this->priority );
		add_action( 'woocommerce_order_refunded', array( $this, 'order_refunded' ), $this->priority );
		add_action( 'woocommerce_new_order', array( $this, 'order_created' ), $this->priority );
		add_action( 'woocommerce_update_order', array( $this, 'order_updated' ), $this->priority );
		add_action( 'woocommerce_order_status_pending', array( $this, 'order_pending' ), $this->priority );
		add_action( 'woocommerce_order_status_processing', array( $this, 'order_processing' ), $this->priority );
		add_action( 'woocommerce_order_status_on-hold', array( $this, 'order_held' ), $this->priority );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'order_cancelled' ), $this->priority );
		add_action( 'woocommerce_order_status_failed', array( $this, 'order_failed' ), $this->priority );

		// Products.
		add_action( 'woocommerce_update_product', array( $this, 'product_updated' ), $this->priority );
		add_action( 'woocommerce_update_product_variation', array( $this, 'product_updated' ), $this->priority );
		add_action( 'woocommerce_new_product_variation', array( $this, 'product_updated' ), $this->priority );
		add_action( 'woocommerce_new_product', array( $this, 'product_updated' ), $this->priority );
		remove_action( 'save_post', array( $this, 'product_updated' ), $this->priority );

		// Automation rules.
		add_action( 'noptin_automation_rules_load', array( $this, 'register_automation_rules' ), $this->priority );

	}

	/**
	 * Registers automation rules.
	 *
	 * @since 1.3.0
	 * @param Noptin_Automation_Rules $rules The automation rules class.
	 */
	public function register_automation_rules( $rules ) {
		$rules->add_trigger( new Noptin_WooCommerce_New_Order_Trigger( $this ) );
		$rules->add_trigger( new Noptin_WooCommerce_Product_Purchase_Trigger( $this ) );
		$rules->add_trigger( new Noptin_WooCommerce_Lifetime_Value_Trigger( $this ) );
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

		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_woocommerce_checkout_checkbox_value' ) );
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
			'woocommerce_review_order_before_payment'     => __( 'After order review', 'newsletter-optin-box' ),
			'woocommerce_review_order_before_submit'      => __( 'Before submit button', 'newsletter-optin-box' ),
			'woocommerce_after_order_notes'               => __( 'After order notes', 'newsletter-optin-box' ),
		);
	}

	/**
	 * Did the user check the email subscription checkbox?
	 *
	 * @param WC_Order $order
	 */
	public function save_woocommerce_checkout_checkbox_value( $order ) {
		if ( $this->checkbox_was_checked() ) {
			$order->update_meta_data( 'noptin_opted_in', 1 );
		}
	}

	/**
	 * Was integration triggered?
	 *
	 * @param int $order_id Order id being executed.
	 * @return bool
	 */
	public function triggered( $order_id = null ) {
		$checked = get_post_meta( $order_id, 'noptin_opted_in', true );
		return $this->auto_subscribe() || ! empty( $checked );
	}

	/**
	 * Adds the checkbox after an email field.
	 *
	 * @return bool
	 */
	public function add_checkbox_after_email_field( $field, $key ) {
		if ( $key !== 'billing_email' ) {
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
		return __( 'Add me to your newsletter and keep me updated whenever you publish new products', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
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
	 * @inheritdoc
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
	 * @inheritdoc
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
				$noptin_fields['country_short'] = $noptin_fields['country'];
				$noptin_fields['country']       = isset( $countries[ $noptin_fields['country'] ] ) ? $countries[ $noptin_fields['country'] ] : $noptin_fields['country'];
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

	/**
	 * @inheritdoc
	 */
	public function available_customer_fields() {
		return array(
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

	/**
	 * @inheritdoc
	 */
	public function get_order_details( $order_id ) {

		// Fetch the order.
		$order = wc_get_order( $order_id );
		if ( empty( $order ) ) {
			return parent::get_order_details( $order_id );
		}

		$details = array(
			'id'       => $order->get_id(),
			'order_id' => $order->get_id(),
			'total'    => $order->get_total(),
			'tax'      => $order->get_total_tax(),
			'fees'     => $order->get_total_fees(),
			'currency' => $order->get_currency(),
			'discount' => $order->get_total_discount(),
			'edit_url' => $order->get_edit_order_url(),
			'view_url' => $order->get_view_order_url(),
			'pay_url'  => $order->get_checkout_payment_url(),
			'status'   => str_replace( 'wc-', '', $order->get_status() ),

			'title'    => sprintf(
				esc_html__( 'Order #%d from %s', 'newsletter-optin-box' ),
				$order->get_id(),
				$order->get_billing_email()
			),

			'items'    => array_map(
				array( $this, 'get_order_item_details' ),
				$order->get_items()
			)
		);

		// Date the order was created.
		$details['date_created'] = $order->get_date_created();
		if ( ! empty( $details['date_created'] ) ) {
			$details['date_created'] = $details['date_created']->__toString();
		}

		// Date it was paid.
		$details['date_paid'] = $order->get_date_completed();
		if ( ! empty( $details['date_paid'] ) ) {
			$details['date_paid'] = $details['date_paid']->__toString();
		}

		return $details;

	}

	/**
	 * Returns an array of order item details.
	 *
	 * @param WC_Order_Item_Product $item The item id.
	 * @since 1.3.0
	 * @return array
	 */
	protected function get_order_item_details( $item ) {

		$product_id   = $item->get_product_id();
        $variation_id = $item->get_variation_id();

        if ( empty( $variation_id ) ) {
            $variation_id = $item->get_product_id();
		}

		return array(
			'item_id'      => $item->get_id(),
			'product_id'   => $product_id,
			'variation_id' => $variation_id,
			'name'         => $item->get_name(),
			'price'        => $item->get_total(),
			'quantity'     => $item->get_quantity(),
		);

	}

	/**
	 * @inheritdoc
	 */
	public function get_order_count( $customer_id_or_email ) {

		if ( empty( $customer_id_or_email ) ) {
			return 0;
		}

		if ( is_numeric( $customer_id_or_email ) ) {
			return (int) wc_get_customer_order_count( $customer_id_or_email );
		}

		$orders = wc_get_orders(
			array(
				'limit'         => -1,
				'billing_email' => $customer_id_or_email,
				'return'        => 'ids',
				'status'        => array_keys( wc_get_order_statuses() ),
			)
		);

		return count( $orders );

	}

	/**
	 * @inheritdoc
	 */
	public function get_total_spent( $customer_id_or_email ) {

		if ( empty( $customer_id_or_email ) ) {
			return 0;
		}

		if ( is_numeric( $customer_id_or_email ) ) {
			return (float) wc_get_customer_total_spent( $customer_id_or_email );
		}

		// Fetch all customer orders.
		$orders = wc_get_orders(
			array(
				'limit'         => -1,
				'billing_email' => $customer_id_or_email,
				'status'        => wc_get_is_paid_statuses(),
			)
		);

		$total = 0;

		// Get the sum of order totals.
		foreach( $orders as $order ) {
			$total += $order->get_total();
		}

		return $total;

	}

	/**
	 * @inheritdoc
	 */
	public function get_product_purchase_count( $customer_id_or_email = null, $product_id = 0 ) {

		$orders = wc_get_orders(
			array(
				'limit'    => -1,
				'customer' => $customer_id_or_email,
				'status'   => array( 'wc-completed', 'wc-processing', 'wc-refunded' ),
			)
		);

		$count = 0;

		// Loop through each order.
   		foreach ( $orders as $order ) {

			// Fetch the items.
			$items = $order->get_items();
			  
			// Compare each product to our product.
      		foreach ( $items as $item ) {
				$item = $this->get_order_item_details( $item );

        		if ( $product_id == $item['product_id'] ) {
            		$count += 1;
         		} else if( $product_id == $item['variation_id'] ) {
					$count += 1;
				}
    		}
   		}

		return $count;

	}

	/**
	 * @inheritdoc
	 */
	public function get_products() {
		$products = wc_get_products(
			array(
				'limit'  => -1,
				'return' => 'ids',
				'status' => 'publish',
				'parent' => 0,
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
		$product = wc_get_product( $product_id );

		if ( empty( $product ) || ! $product->is_purchasable() ) {
			return array();
		}

		$details = array(
			'id'                 => $product->get_id(),
			'name'               => $product->get_name(),
			'description'        => $product->get_short_description(),
			'url'                => $product->get_permalink(),
			'price'              => $product->get_price(),
			'type'               => $product->get_type(),
			'sku'                => $product->get_sku(),
			'inventory_quantity' => 0,
			'variations'         => array(),
		);

		// Gallery images.
		$images = $product->get_gallery_image_ids();

		// Add featured image to the beginning.
		array_unshift( $images, $product->get_image_id() );

		// Remove duplicate and empty values.
		$images = array_unique( array_filter( $images ) );

		// Convert image ids to urls.
		$details['images'] = array_filter( array_map( 'wp_get_attachment_url', $images ) );

		// Variations.
		$variations = $product->get_children();

		foreach ( $variations as $variation ) {
			$variation = $this->get_product_details( $variation );

			if ( empty( $variation ) ) {
				continue;
			}

			$details['variations'][] = $variation;

		}

		if ( empty( $details['variations'] ) ) {
			unset( $details['variations'] );
			$details['inventory_quantity'] = $product->get_stock_quantity();
		}

		return $details;

	}

}
