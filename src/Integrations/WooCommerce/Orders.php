<?php

namespace Hizzle\Noptin\Integrations\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Container for an order.
 */
class Orders extends \Hizzle\Noptin\Objects\Collection {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function __construct() {
		$this->record_class      = __NAMESPACE__ . '\Order';
		$this->integration       = 'woocommerce';
		$this->smart_tags_prefix = 'order';
		$this->label             = __( 'Orders', 'newsletter-optin-box' );
		$this->singular_label    = __( 'Order', 'newsletter-optin-box' );
		$this->type              = 'shop_order';
		$this->title_field       = 'number';
		$this->description_field = 'details';
		$this->url_field         = 'admin_url';
		$this->can_list          = true;
		$this->provides          = array( 'customer' );
		$this->show_tab          = true;
		$this->icon              = array(
			'icon' => 'money-alt',
			'fill' => '#674399',
		);

		parent::__construct();

		// State transition.
		foreach ( array_keys( $this->order_states() ) as $state ) {
			add_action( $state, array( $this, 'order_state_changed' ), 11, 3 );
		}
	}

	private function order_states() {
		$statuses = array(
			'woocommerce_new_order'                => __( 'Created', 'newsletter-optin-box' ),
			'woocommerce_checkout_order_processed' => __( 'Processed via checkout', 'newsletter-optin-box' ),
			'woocommerce_payment_complete'         => __( 'Paid', 'newsletter-optin-box' ),
			'woocommerce_order_refunded'           => __( 'Refunded', 'newsletter-optin-box' ),
			'woocommerce_before_delete_order'      => __( 'Deleted', 'newsletter-optin-box' ),
		);

		foreach ( wc_get_order_statuses() as $status => $label ) {
			$status = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;

			if ( ! in_array( $status, array( 'checkout-draft', 'refunded', 'draft' ), true ) ) {
				$statuses[ 'woocommerce_order_status_' . $status ] = $label;
			}
		}

		return $statuses;
	}

	public static function is_complete() {
		$current_hook = current_filter();

		// Check if the order was manually completed via the admin.
		if ( 'woocommerce_order_status_completed' === $current_hook ) {
			return true;
		}

		if ( 'woocommerce_payment_complete' === $current_hook && ! did_action( 'woocommerce_order_status_completed' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Retrieves available filters.
	 *
	 * @return array
	 */
	public function get_filters() {

		return array(
			'status'              => array(
				'label'    => __( 'Order status', 'newsletter-optin-box' ),
				'el'       => 'select',
				'multiple' => true,
				'options'  => wc_get_order_statuses(),
			),
			'parent'              => array(
				'label' => __( 'Parent order ID', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'number',
			),
			'parent_exclude'      => array(
				'label'       => __( 'Exclude parent order', 'newsletter-optin-box' ),
				'el'          => 'form_token',
				'placeholder' => __( 'Comma-separated list of parent order IDs to exclude.', 'newsletter-optin-box' ),
			),
			'exclude'             => array(
				'label'       => __( 'Exclude Order IDs', 'newsletter-optin-box' ),
				'el'          => 'form_token',
				'placeholder' => __( 'Comma-separated list of order IDs to exclude.', 'newsletter-optin-box' ),
			),
			'currency'            => array(
				'label' => __( 'Currency Code', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'payment_method'      => array(
				'label'   => __( 'Payment method', 'newsletter-optin-box' ),
				'el'      => 'select',
				'options' => wp_list_pluck( WC()->payment_gateways()->payment_gateways(), 'method_title', 'id' ),
			),
			'customer'            => array(
				'label' => __( 'Customer ID or email', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'billing_first_name'  => array(
				'label' => __( 'Billing first name', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'billing_last_name'   => array(
				'label' => __( 'Billing last name', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'billing_company'     => array(
				'label' => __( 'Billing company name', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'billing_address_1'   => array(
				'label' => __( 'Billing address line 1', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'billing_address_2'   => array(
				'label' => __( 'Billing address line 2', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'billing_city'        => array(
				'label' => __( 'Billing city', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'billing_state'       => array(
				'label' => __( 'Billing state', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'billing_postcode'    => array(
				'label' => __( 'Billing postcode', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'billing_country'     => array(
				'label'   => __( 'Billing country', 'newsletter-optin-box' ),
				'el'      => 'select',
				'options' => WC()->countries->get_countries(),
			),
			'billing_email'       => array(
				'label' => __( 'Billing email address', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'billing_phone'       => array(
				'label' => __( 'Billing phone number', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'shipping_first_name' => array(
				'label' => __( 'Shipping first name', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'shipping_last_name'  => array(
				'label' => __( 'Shipping last name', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'shipping_company'    => array(
				'label' => __( 'Shipping company name', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'shipping_address_1'  => array(
				'label' => __( 'Shipping address line 1', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'shipping_address_2'  => array(
				'label' => __( 'Shipping address line 2', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'shipping_city'       => array(
				'label' => __( 'Shipping city', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'shipping_state'      => array(
				'label' => __( 'Shipping state', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'shipping_postcode'   => array(
				'label' => __( 'Shipping postcode', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'shipping_country'    => array(
				'label'   => __( 'Shipping country', 'newsletter-optin-box' ),
				'el'      => 'select',
				'options' => WC()->countries->get_countries(),
			),
			'customer_ip_address' => array(
				'label' => __( 'Customer IP address', 'newsletter-optin-box' ),
				'el'    => 'input',
				'type'  => 'text',
			),
			'date_created'        => array(
				'label'       => __( 'Date created', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'string',
				'placeholder' => sprintf(
					'%s <br /> %s',
					__( 'Date order was created, in the site\'s timezone, with an optional comparison operator.', 'newsletter-optin-box' ),
					sprintf(
						'For example, %1$s, >%1$s, or %1$s...%2$s',
						gmdate( 'Y-m-d' ),
						gmdate( 'Y-m-d', strtotime( '+1 week' ) )
					)
				),
			),
			'date_modified'       => array(
				'label'       => __( 'Date modified', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'string',
				'placeholder' => sprintf(
					'%s <br /> %s',
					__( 'Date order was last modified, in the site\'s timezone, with an optional comparison operator.', 'newsletter-optin-box' ),
					sprintf(
						'For example, %1$s, >%1$s, or %1$s...%2$s',
						gmdate( 'Y-m-d' ),
						gmdate( 'Y-m-d', strtotime( '+1 week' ) )
					)
				),
			),
			'date_completed'      => array(
				'label'       => __( 'Date completed', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'string',
				'placeholder' => sprintf(
					'%s <br /> %s',
					__( 'Date order was completed, in the site\'s timezone, with an optional comparison operator.', 'newsletter-optin-box' ),
					sprintf(
						'For example, %1$s, >%1$s, or %1$s...%2$s',
						gmdate( 'Y-m-d' ),
						gmdate( 'Y-m-d', strtotime( '+1 week' ) )
					)
				),
			),
			'date_paid'           => array(
				'label'       => __( 'Date paid', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'string',
				'placeholder' => sprintf(
					'%s <br /> %s',
					__( 'Date order was paid, in the site\'s timezone, with an optional comparison operator.', 'newsletter-optin-box' ),
					sprintf(
						'For example, %1$s, >%1$s, or %1$s...%2$s',
						gmdate( 'Y-m-d' ),
						gmdate( 'Y-m-d', strtotime( '+1 week' ) )
					)
				),
			),
		);
	}

	/**
	 * Retrieves matching posts.
	 *
	 * @param array $filters The available filters.
	 * @return int[] $users The user IDs.
	 */
	public function get_all( $filters ) {

		$filters = array_merge(
			array(
				'type'    => 'shop_order',
				'number'  => 10,
				'order'   => 'DESC',
				'orderby' => 'date',
				'return'  => 'ids',
			),
			$filters
		);

		// If order by is title, use name instead.
		if ( 'title' === $filters['orderby'] ) {
			$filters['orderby'] = 'name';
		}

		// Convert number to numberposts.
		if ( isset( $filters['number'] ) ) {
			$filters['limit'] = $filters['number'];
			unset( $filters['number'] );
		}

		if ( ! empty( $filters['status'] ) && ! is_array( $filters['status'] ) ) {
			$filters['status'] = wp_parse_list( $filters['status'] );
		}

		// Ensure include and exclude are arrays.
		foreach ( array( 'parent_exclude', 'exclude' ) as $key ) {
			if ( isset( $filters[ $key ] ) && ! is_array( $filters[ $key ] ) ) {
				$filters[ $key ] = wp_parse_id_list( $filters[ $key ] );
			}
		}

		// Parse dates.
		foreach ( array( 'date_created', 'date_modified', 'date_completed', 'date_paid' ) as $key ) {
			if ( isset( $filters[ $key ] ) ) {
				$filters[ $key ] = self::parse_wc_date_query( $filters[ $key ] );
			}
		}

		return wc_get_orders( array_filter( $filters ) );
	}

	/**
	 * Retrieves several items by email.
	 *
	 */
	public function get_all_by_email( $email_address, $limit = 25 ) {
		return wc_get_orders(
			array(
				'limit'    => $limit,
				'customer' => $email_address,
			)
		);
	}

	/**
	 * Fetches the custom tab headers.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	protected function get_custom_tab_headers() {
		return array(
			array(
				'label'      => $this->singular_label,
				'name'       => 'order',
				'is_primary' => true,
				'url'        => 'url',
			),
			array(
				'label'   => __( 'Items', 'newsletter-optin-box' ),
				'name'    => 'items',
				'is_list' => true,
				'item'    => '%s &times; %s - %s',
				'args'    => array(
					'name',
					'quantity',
					'total',
				),
			),
			array(
				'label'    => __( 'Status', 'newsletter-optin-box' ),
				'name'     => 'status',
				'is_badge' => true,
			),
			array(
				'label'      => __( 'Discount', 'newsletter-optin-box' ),
				'name'       => 'discount',
				'is_numeric' => true,
			),
			array(
				'label'      => __( 'Total', 'newsletter-optin-box' ),
				'name'       => 'total',
				'is_numeric' => true,
			),
			array(
				'label' => __( 'Date Created', 'newsletter-optin-box' ),
				'name'  => 'date',
				'align' => 'right',
			),
		);
	}

	/**
	 * Parses a date query arg.
	 *
	 * @param string $date_query The date query.
	 * @return string $date_query The parsed date query.
	 */
	public static function parse_wc_date_query( $date_query ) {

		$date_query = trim( $date_query );

		// Multiple dates are separated by ....
		if ( false !== strpos( $date_query, '...' ) ) {
			$dates = explode( '...', $date_query );

			$first  = gmdate( 'Y-m-d', strtotime( trim( $dates[0] ) ) );
			$second = gmdate( 'Y-m-d', strtotime( trim( $dates[1] ) ) );

			return implode( '...', array( $first, $second ) );
		}

		// Optional comparison operator, >, <, >=, or <=.
		if ( preg_match( '/^(>|<|>=|<=)?(.*)$/', $date_query, $matches ) ) {
			$operator = isset( $matches[1] ) ? trim( $matches[1] ) : '';
			$date     = isset( $matches[2] ) ? trim( $matches[2] ) : '';

			if ( ! empty( $date ) ) {
				$date = gmdate( 'Y-m-d', strtotime( trim( $date ) ) );
			}

			return $operator . $date;
		}

		return $date_query;
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {

		$fields = array(
			'number'               => array(
				'label' => __( 'Number', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s Number', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'Displays the %s number.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'icon'        => 'heading',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'element'     => 'heading',
					'linksTo'     => $this->field_to_merge_tag( 'admin_url' ),
				),
			),
			'transaction_id'       => array(
				'label' => __( 'Transaction ID', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'coupon_code'          => array(
				'label' => __( 'Coupon Code', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'subtotal'             => array(
				'label' => __( 'Subtotal', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'total_tax'            => array(
				'label' => __( 'Total tax', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'shipping_total'       => array(
				'label' => __( 'Shipping total', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'discount_total'       => array(
				'label' => __( 'Discount total', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'total'                => array(
				'label' => __( 'Total', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'item_count'           => array(
				'label' => __( 'Item count', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'billing_address'      => array(
				'label' => __( 'Billing address', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Order Billing address', 'newsletter-optin-box' ),
					'description' => __( 'Displays the formatted billing address for the order', 'newsletter-optin-box' ),
					'icon'        => 'location',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
				),
			),
			'billing_first_name'   => array(
				'label' => __( 'Billing first name', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'billing_last_name'    => array(
				'label' => __( 'Billing last name', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'billing_company'      => array(
				'label' => __( 'Billing company', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'billing_address_1'    => array(
				'label' => __( 'Billing address 1', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'billing_address_2'    => array(
				'label' => __( 'Billing address 2', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'billing_city'         => array(
				'label' => __( 'Billing city', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'billing_state'        => array(
				'label' => __( 'Billing state', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'billing_postcode'     => array(
				'label' => __( 'Billing postcode', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'billing_country'      => array(
				'label'   => __( 'Billing country', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => WC()->countries->get_countries(),
			),
			'billing_email'        => array(
				'label' => __( 'Billing email', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'billing_phone'        => array(
				'label' => __( 'Billing phone', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'shipping_method'      => array(
				'label' => __( 'Shipping method', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Shipping method', 'newsletter-optin-box' ),
					'description' => __( 'Displays the formatted shipping method for the order', 'newsletter-optin-box' ),
					'icon'        => 'car',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
				),
			),
			'shipping_address'     => array(
				'label' => __( 'Shipping address', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Order Shipping address', 'newsletter-optin-box' ),
					'description' => __( 'Displays the formatted shipping address for the order', 'newsletter-optin-box' ),
					'icon'        => 'location-alt',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
				),
			),
			'shipping_first_name'  => array(
				'label' => __( 'Shipping first name', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'shipping_last_name'   => array(
				'label' => __( 'Shipping last name', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'shipping_company'     => array(
				'label' => __( 'Shipping company', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'shipping_address_1'   => array(
				'label' => __( 'Shipping address 1', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'shipping_address_2'   => array(
				'label' => __( 'Shipping address 2', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'shipping_city'        => array(
				'label' => __( 'Shipping city', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'shipping_state'       => array(
				'label' => __( 'Shipping state', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'shipping_postcode'    => array(
				'label' => __( 'Shipping postcode', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'shipping_country'     => array(
				'label'   => __( 'Shipping country', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => WC()->countries->get_countries(),
			),
			'shipping_phone'       => array(
				'label' => __( 'Shipping phone', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'payment_method'       => array(
				'label'   => __( 'Payment method', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => wp_list_pluck( WC()->payment_gateways()->payment_gateways(), 'method_title', 'id' ),
			),
			'payment_method_title' => array(
				'label' => __( 'Payment method title', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'payment_url'          => array(
				'description' => __( 'Payment URL', 'newsletter-optin-box' ),
				'type'        => 'string',
				'block'       => array(
					'title'       => __( 'Pay Order', 'newsletter-optin-box' ),
					'description' => __( 'Displays a button link to pay for the order.', 'newsletter-optin-box' ),
					'icon'        => 'money-alt',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'defaults'    => array(
						'text' => __( 'Pay Now', 'newsletter-optin-box' ),
						'url'  => $this->field_to_merge_tag( 'payment_url' ),
					),
					'element'     => 'button',
				),
			),
			'view_url'             => array(
				'description' => __( 'View URL', 'newsletter-optin-box' ),
				'type'        => 'string',
				'block'       => array(
					'title'       => __( 'View order', 'newsletter-optin-box' ),
					'description' => __( 'Displays a button link to view the order.', 'newsletter-optin-box' ),
					'icon'        => 'visibility',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'defaults'    => array(
						'text' => __( 'View order', 'newsletter-optin-box' ),
						'url'  => $this->field_to_merge_tag( 'view_url' ),
					),
					'element'     => 'button',
				),
			),
			'admin_url'            => array(
				'description' => __( 'Admin URL', 'newsletter-optin-box' ),
				'type'        => 'string',
				'block'       => array(
					'title'       => __( 'Edit order', 'newsletter-optin-box' ),
					'description' => __( 'Displays a button link to edit the order in the admin backend.', 'newsletter-optin-box' ),
					'icon'        => 'edit',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'defaults'    => array(
						'text' => __( 'Edit order', 'newsletter-optin-box' ),
						'url'  => $this->field_to_merge_tag( 'admin_url' ),
					),
					'element'     => 'button',
				),
			),
			'customer_note'        => array(
				'label' => __( 'Customer note', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Customer note', 'newsletter-optin-box' ),
					'description' => __( 'Displays the customer note for the order.', 'newsletter-optin-box' ),
					'icon'        => 'edit',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
				),
			),
			'customer_ip_address'  => array(
				'label' => __( 'Customer IP address', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'customer_user_agent'  => array(
				'label' => __( 'Customer user agent', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'items'                => array(
				'label' => __( 'Items', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Order Items', 'newsletter-optin-box' ),
					'description' => __( 'Displays the items in the order.', 'newsletter-optin-box' ),
					'icon'        => 'cart',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'element'     => 'order_item',
				),
			),
			'cross_sells'          => array(
				'label' => __( 'Cross sells', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Order Cross sells', 'newsletter-optin-box' ),
					'description' => __( 'Displays the cross sells for the order.', 'newsletter-optin-box' ),
					'icon'        => 'products',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'element'     => 'product',
				),
			),
			'upsells'              => array(
				'label' => __( 'Upsells', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Order Upsells', 'newsletter-optin-box' ),
					'description' => __( 'Displays the upsells for the order.', 'newsletter-optin-box' ),
					'icon'        => 'products',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'element'     => 'product',
				),
			),
			'details'              => array(
				'label' => __( 'Details', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Order Details', 'newsletter-optin-box' ),
					'description' => __( 'Displays the details for the order.', 'newsletter-optin-box' ),
					'icon'        => 'info',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'element'     => 'div',
				),
			),
			'customer_details'     => array(
				'label' => __( 'Customer details', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Customer Details', 'newsletter-optin-box' ),
					'description' => __( 'Displays the customer details for the order.', 'newsletter-optin-box' ),
					'icon'        => 'user',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'element'     => 'div',
				),
			),
			'id'                   => array(
				'label' => __( 'ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'parent_id'            => array(
				'label' => __( 'Parent ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'date_created'         => array(
				'description' => __( 'Date created', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'date_modified'        => array(
				'description' => __( 'Date modified', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'date_paid'            => array(
				'description' => __( 'Date paid', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'date_completed'       => array(
				'description' => __( 'Date completed', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'status'               => array(
				'description' => __( 'Status', 'newsletter-optin-box' ),
				'type'        => 'string',
				'options'     => $this->wc_order_statuses(),
			),
			'meta'                 => $this->meta_key_tag_config(),
		);

		return apply_filters( 'noptin_post_type_known_custom_fields', $fields, $this->type );
	}

	protected function wc_order_statuses() {
		$statuses = wc_get_order_statuses();
		$prepared = array();

		// Remove wc- prefix.
		foreach ( $statuses as $status => $label ) {
			$status              = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;
			$prepared[ $status ] = $label;
		}

		return $prepared;
	}

	/**
	 * Sanitize a hook name to a trigger id.
	 *
	 * @param string $hook The hook name.
	 * @return string $trigger_id The trigger id.
	 */
	private function sanitize_hook_to_trigger_id( $hook ) {
		// Remove woocommerce_ prefix.
		if ( false !== strpos( $hook, 'woocommerce_' ) ) {
			$hook = substr( $hook, 12 );
		}

		// Remove order_status_ prefix.
		if ( false !== strpos( $hook, 'order_status_' ) ) {
			$hook = substr( $hook, 13 );
		}

		return 'wc_' . sanitize_key( $hook );
	}

	/**
	 * Returns a list of available triggers.
	 *
	 * @return array $triggers The triggers.
	 */
	public function get_triggers() {
		$triggers = array();

		foreach ( $this->order_states() as $state => $label ) {

			$triggers[ $this->sanitize_hook_to_trigger_id( $state ) ] = array(
				'label'       => sprintf(
					/* translators: %s: Order status label. */
					__( 'Order > %s', 'newsletter-optin-box' ),
					$label
				),
				'description' => sprintf(
					/* translators: %s: Order status label. */
					__( 'When a WooCommerce order is %s', 'newsletter-optin-box' ),
					strtolower( $label )
				),
				'subject'     => 'customer',
				'extra_args'  => array(
					'previous_status' => array(
						'description' => __( 'The previous order status.', 'newsletter-optin-box' ),
						'type'        => 'string',
						'options'     => array_merge(
							array( 'new' => __( 'New', 'newsletter-optin-box' ) ),
							$this->wc_order_statuses()
						),
					),
				),
			);
		}

		return $triggers;
	}

	/**
	 * Fired when an order state changes.
	 *
	 * @param int|\WC_Order $order_id The order being acted on.
	 */
	public function order_state_changed( $order_id, $order = null, $transition = array() ) {

		// Abort if cleaning draft orders.
		if ( doing_action( 'woocommerce_cleanup_draft_orders' ) ) {
			return;
		}

		if ( is_numeric( $order_id ) ) {
			$order = wc_get_order( $order_id );
		} else {
			$order = $order_id;
		}

		if ( empty( $order ) || ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		// Abort if no billing email is set.
		if ( empty( $order->get_billing_email() ) || empty( $order->get_id() ) ) {
			return;
		}

		$referring_campaign = $order->get_meta( '_noptin_referring_campaign' );

		if ( empty( $referring_campaign ) ) {
			$referring_campaign = noptin_get_referring_email_id();

			if ( ! empty( $referring_campaign ) ) {
				$order->update_meta_data( '_noptin_referring_campaign', $referring_campaign );
				$order->save();
			}
		}

		if ( ! empty( $referring_campaign ) && self::is_complete() ) {
			noptin_record_ecommerce_purchase( $order->get_total(), $referring_campaign );
		}

		// Check that the current action is a valid trigger.
		$hook       = current_filter();
		$trigger_id = $this->sanitize_hook_to_trigger_id( $hook );

		if ( ! in_array( $trigger_id, array_keys( $this->get_triggers() ), true ) ) {
			return;
		}

		$args = array(
			'email'      => $order->get_billing_email(),
			'object_id'  => $order->get_id(),
			'subject_id' => self::get_order_customer( $order ),
			'extra_args' => array(
				'order.previous_status' => 'new',
			),
			'url'        => $order->get_edit_order_url(),
			'activity'   => sprintf(
				'#%1$s %2$s',
				$order->get_order_number(),
				$order->get_formatted_order_total()
			),
		);

		// Check if the hook name contains a status change.
		if ( false !== strpos( $hook, 'woocommerce_order_status_' ) ) {
			$args['unserialize'] = array(
				'order.status' => $order->get_status(),
			);

			if ( is_array( $transition ) && ! empty( $transition['from'] ) ) {
				$args['extra_args'] = array(
					'order.previous_status' => $transition['from'],
				);
			}
		}

		$this->trigger(
			$trigger_id,
			$args
		);
	}

	/**
	 * Retrieves the order customer.
	 *
	 * @param \WC_Order $order The order being acted on.
	 */
	public static function get_order_customer( $order ) {
		if ( is_callable( array( $order, 'get_customer_id' ) ) ) {
			$customer = new \WC_Customer( $order->get_customer_id() );

			if ( $customer->get_id() ) {
				return $customer->get_id();
			}
		}

		return 0 - $order->get_id();
	}

	/**
	 * Retrieves a test object args.
	 *
	 * @since 2.2.0
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule
	 * @throws \Exception
	 * @return array
	 */
	public function get_test_args( $rule ) {

		$args = array(
			'status' => array_keys( wc_get_order_statuses() ),
		);

		if ( 'wc_payment_complete' === $rule->get_trigger_id() ) {
			$args['status'] = array( 'wc-processing', 'wc-completed' );
		}

		if ( 'wc_order_refunded' === $rule->get_trigger_id() ) {
			$args['status'] = array( 'wc-refunded' );
		}

		foreach ( array_keys( $args['status'] ) as $status ) {
			$prepared_status = false !== strpos( $status, 'wc-' ) ? substr( $status, 3 ) : $status;
			if ( $rule->get_trigger_id() === 'wc_' . $prepared_status ) {
				$args['status'] = array( $status );
				break;
			}
		}

		$order = self::get_test_order( $args );
		return array(
			'email'      => $order->get_billing_email(),
			'object_id'  => $order->get_id(),
			'subject_id' => self::get_order_customer( $order ),
			'extra_args' => array(
				'order.previous_status' => 'new',
			),
		);
	}

	/**
	 * Retrieves a test order args.
	 *
	 * @since 2.2.0
	 * @param array $args
	 * @throws \Exception
	 * @return \WC_Order
	 */
	public static function get_test_order( $args = array() ) {

		// Fetch latest order.
		$order = current(
			wc_get_orders(
				array_merge(
					array(
						'limit'  => 1,
						'order'  => 'DESC',
						'type'   => 'shop_order',
						'status' => array( 'wc-processing', 'wc-completed' ),
					),
					$args
				)
			)
		);

		if ( empty( $order ) ) {
			throw new \Exception( 'No order found' );
		}

		return $order;
	}
}
