<?php

namespace Hizzle\Noptin\Integrations\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Container for an order.
 */
class Orders extends \Hizzle\Noptin\Objects\Generic_Post_Type {

	/**
	 * @var string the record class.
	 */
	public $record_class = '\Hizzle\Noptin\Integrations\WooCommerce';

	/**
	 * @var string integration.
	 */
	public $integration = 'woocommerce';

	public $smart_tags_prefix = 'order';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function __construct() {
		parent::__construct( 'shop_order' );

		// State transition.
		foreach ( array_keys( $this->order_states() ) as $state ) {
			add_action( $state, array( $this, 'order_state_changed' ), 11 );
		}
	}

	private function order_states() {
		$statuses = array_merge(
			array(
				'woocommerce_new_order'                => __( 'Created', 'newsletter-optin-box' ),
				'woocommerce_update_order'             => __( 'Updated', 'newsletter-optin-box' ),
				'woocommerce_checkout_order_processed' => __( 'Processed via checkout', 'newsletter-optin-box' ),
				'woocommerce_payment_complete'         => __( 'Paid', 'newsletter-optin-box' ),
				'woocommerce_order_refunded'           => __( 'Refunded', 'newsletter-optin-box' ),
			)
		);

		foreach ( wc_get_order_statuses() as $status => $label ) {
			$status = 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;

			if ( 'refunded' !== $status && 'draft' !== $status ) {
				$statuses[ 'woocommerce_order_status_' . $status ] = $label;
			}
		}

		return $statuses;
	}

	/**
	 * Retrieves available filters.
	 *
	 * @return array
	 */
	public function get_filters() {

		$types = wc_get_order_types();

		return array(
			'status'              => array(
				'label'    => __( 'Order status', 'newsletter-optin-box' ),
				'el'       => 'select',
				'multiple' => true,
				'options'  => wc_get_order_statuses(),
			),
			'type'                => array(
				'label'   => __( 'Order type', 'newsletter-optin-box' ),
				'el'      => 'select',
				'options' => array_combine( $types, $types ),
				'default' => 'shop_order',
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
				'status'  => 'publish',
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

		// Ensure include and exclude are arrays.
		foreach ( array( 'status', 'parent_exclude', 'exclude' ) as $key ) {
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
			'number'                    => array(
				'label' => __( 'Number', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'transaction_id'                    => array(
				'label' => __( 'Transaction ID', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'coupon_code' 				  => array(
				'label' => __( 'Coupon Code', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'subtotal'                   => array(
				'label' => __( 'Subtotal', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'total_tax' 			   => array(
				'label' => __( 'Total tax', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'shipping_total' 		  => array(
				'label' => __( 'Shipping total', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'discount_total' 		  => array(
				'label' => __( 'Discount total', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'total' 				   => array(
				'label' => __( 'Total', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'item_count' 			   => array(
				'label' => __( 'Item count', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'billing_address' 		=> array(
				'label' => __( 'Billing address', 'newsletter-optin-box' ),
				'type'        => 'string',
				'block'       => array(
					'title'       => __( 'Billing address', 'newsletter-optin-box' ),
					'description' => __( 'Displays the formatted billing address for the order', 'newsletter-optin-box' ),
					'icon'        => 'location',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
				),
			),
			'billing_first_name' 		=> array(
				'label' => __( 'Billing first name', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'billing_last_name' 		=> array(
				'label' => __( 'Billing last name', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'billing_company' 			=> array(
				'label' => __( 'Billing company', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'billing_address_1' 		=> array(
				'label' => __( 'Billing address 1', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'billing_address_2' 		=> array(
				'label' => __( 'Billing address 2', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'billing_city' 				=> array(
				'label' => __( 'Billing city', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'billing_state' 			=> array(
				'label' => __( 'Billing state', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'billing_postcode' 			=> array(
				'label' => __( 'Billing postcode', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'billing_country' 			=> array(
				'label' => __( 'Billing country', 'newsletter-optin-box' ),
				'type'        => 'string',
				'options'    => WC()->countries->get_countries(),
			),
			'billing_email' 			=> array(
				'label' => __( 'Billing email', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'billing_phone' 			=> array(
				'label' => __( 'Billing phone', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'shipping_method' 			=> array(
				'label' => __( 'Shipping method', 'newsletter-optin-box' ),
				'type'        => 'string',
				'block' 	 => array(
					'title'       => __( 'Shipping method', 'newsletter-optin-box' ),
					'description' => __( 'Displays the formatted shipping method for the order', 'newsletter-optin-box' ),
					'icon'        => 'car',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
				),
			),
			'shipping_address' 			=> array(
				'label' => __( 'Shipping address', 'newsletter-optin-box' ),
				'type'        => 'string',
				'block'       => array(
					'title'       => __( 'Shipping address', 'newsletter-optin-box' ),
					'description' => __( 'Displays the formatted shipping address for the order', 'newsletter-optin-box' ),
					'icon'        => 'location-alt',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
				),
			),
			'shipping_first_name' 		=> array(
				'label' => __( 'Shipping first name', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'shipping_last_name' 		=> array(
				'label' => __( 'Shipping last name', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'shipping_company' 			=> array(
				'label' => __( 'Shipping company', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'shipping_address_1' 		=> array(
				'label' => __( 'Shipping address 1', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'shipping_address_2' 		=> array(
				'label' => __( 'Shipping address 2', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'shipping_city' 			=> array(
				'label' => __( 'Shipping city', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'shipping_state' 			=> array(
				'label' => __( 'Shipping state', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'shipping_postcode' 		=> array(
				'label' => __( 'Shipping postcode', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'shipping_country' 			=> array(
				'label' => __( 'Shipping country', 'newsletter-optin-box' ),
				'type'        => 'string',
				'options'    => WC()->countries->get_countries(),
			),
			'shipping_phone' 			=> array(
				'label' => __( 'Shipping phone', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'payment_method' 			=> array(
				'label' => __( 'Payment method', 'newsletter-optin-box' ),
				'type'        => 'string',
				'options'   => wp_list_pluck( WC()->payment_gateways()->payment_gateways(), 'method_title', 'id' ),
			),
			'payment_method_title' 		=> array(
				'label' => __( 'Payment method title', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'payment_url'                     => array(
				'description' => __( 'Payment URL', 'newsletter-optin-box' ),
				'type'        => 'string',
				'block'       => array(
					'title'       => __( 'Payment Button', 'newsletter-optin-box' ),
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
			'view_url' 				   => array(
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
			'admin_url' 			   => array(
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
			'customer_note' 			=> array(
				'label' => __( 'Customer note', 'newsletter-optin-box' ),
				'type'        => 'string',
				'block' 	 => array(
					'title'       => __( 'Customer note', 'newsletter-optin-box' ),
					'description' => __( 'Displays the customer note for the order.', 'newsletter-optin-box' ),
					'icon'        => 'edit',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
				),
			),
			'customer_ip_address' 		=> array(
				'label' => __( 'Customer IP address', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'customer_user_agent' 		=> array(
				'label' => __( 'Customer user agent', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'items' 				   => array(
				'label' => __( 'Items', 'newsletter-optin-box' ),
				'type'        => 'string',
				'block'       => array(
					'title'       => __( 'Items', 'newsletter-optin-box' ),
					'description' => __( 'Displays the items in the order.', 'newsletter-optin-box' ),
					'icon'        => 'cart',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'settings' => array(
						'style' => array(
							'label'       => __( 'Style', 'newsletter-optin-box' ),
							'description' => __( 'The style of the items.', 'newsletter-optin-box' ),
							'type'        => 'select',
							'options'     => array(
								'list' => __( 'List', 'newsletter-optin-box' ),
								'grid' => __( 'Grid', 'newsletter-optin-box' ),
							),
							'default'     => 'list',
						),
					),
				),
			),
			'id'                      => array(
				'label' => __( 'ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'parent_id'               => array(
				'label' => __( 'Parent ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'date_created'            => array(
				'description' => __( 'Date created', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'date_modified'           => array(
				'description' => __( 'Date modified', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'date_paid' 			 => array(
				'description' => __( 'Date paid', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'date_completed' 		=> array(
				'description' => __( 'Date completed', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'status'                  => array(
				'description' => __( 'Status', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'featured'                => array(
				'description' => __( 'Featured', 'newsletter-optin-box' ),
				'type'        => 'boolean',
			),
			'catalog_visibility'      => array(
				'description' => __( 'Catalog visibility', 'newsletter-optin-box' ),
				'type'        => 'string',
				'options'     => array(
					'visible' => __( 'Visible', 'newsletter-optin-box' ),
					'catalog' => __( 'Catalog', 'newsletter-optin-box' ),
					'search'  => __( 'Search', 'newsletter-optin-box' ),
					'hidden'  => __( 'Hidden', 'newsletter-optin-box' ),
				),
			),
			'description'             => array(
				'description' => __( 'Description', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'sku'                     => array(
				'description' => __( 'SKU', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'regular_price'           => array(
				'description' => __( 'Regular price', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'sale_price'              => array(
				'description' => __( 'Sale price', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'date_on_sale_from'       => array(
				'description' => __( 'Date on sale from', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'date_on_sale_to'         => array(
				'description' => __( 'Date on sale to', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'total_sales'             => array(
				'description' => __( 'Total sales', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'type'                    => array(
				'description' => __( 'Type', 'newsletter-optin-box' ),
				'type'        => 'string',
				'options'     => wc_get_product_types(),
			),
			'tax_status'              => array(
				'description' => __( 'Tax status', 'newsletter-optin-box' ),
				'type'        => 'string',
				'options'     => array(
					'taxable'  => __( 'Taxable', 'newsletter-optin-box' ),
					'shipping' => __( 'Shipping', 'newsletter-optin-box' ),
					'none'     => __( 'None', 'newsletter-optin-box' ),
				),
			),
			'tax_class'               => array(
				'description' => __( 'Tax class', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'manage_stock'            => array(
				'description' => __( 'Manage stock', 'newsletter-optin-box' ),
				'type'        => 'boolean',
			),
			'stock_quantity'          => array(
				'description' => __( 'Stock quantity', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'stock_status'            => array(
				'description' => __( 'Stock status', 'newsletter-optin-box' ),
				'type'        => 'string',
				'options'     => array(
					'instock'    => __( 'In stock', 'newsletter-optin-box' ),
					'outofstock' => __( 'Out of Stock', 'newsletter-optin-box' ),
				),
			),
			'backorders'              => array(
				'description' => __( 'Backorders', 'newsletter-optin-box' ),
				'type'        => 'string',
				'options'     => array(
					'no'     => __( 'No', 'newsletter-optin-box' ),
					'notify' => __( 'Notify', 'newsletter-optin-box' ),
					'yes'    => __( 'Yes', 'newsletter-optin-box' ),
				),
			),
			'low_stock_amount'        => array(
				'description' => __( 'Low stock amount', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'sold_individually'       => array(
				'description' => __( 'Sold individually', 'newsletter-optin-box' ),
				'type'        => 'boolean',
			),
			'dimensions'              => array(
				'description' => __( 'Dimensions', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'weight'                  => array(
				'description' => __( 'Weight', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'length'                  => array(
				'description' => __( 'Length', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'width'                   => array(
				'description' => __( 'Width', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'height'                  => array(
				'description' => __( 'Height', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'upsell_ids'              => array(
				'description' => __( 'Upsell IDs', 'newsletter-optin-box' ),
				'type'        => 'array',
			),
			'cross_sell_ids'          => array(
				'description' => __( 'Cross sell IDs', 'newsletter-optin-box' ),
				'type'        => 'array',
			),
			'reviews_allowed'         => array(
				'description' => __( 'Reviews allowed', 'newsletter-optin-box' ),
				'type'        => 'boolean',
			),
			'purchase_note'           => array(
				'description' => __( 'Purchase note', 'newsletter-optin-box' ),
				'type'        => 'string',
			),
			'downloadable'            => array(
				'description' => __( 'Downloadable', 'newsletter-optin-box' ),
				'type'        => 'boolean',
			),
			'virtual'                 => array(
				'description' => __( 'Virtual', 'newsletter-optin-box' ),
				'type'        => 'boolean',
			),
			'shipping_class_id'       => array(
				'description' => __( 'Shipping class ID', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'download_limit'          => array(
				'description' => __( 'Download limit', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'download_expiry'         => array(
				'description' => __( 'Download expiry', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'average_rating'          => array(
				'description' => __( 'Average rating', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'review_count'            => array(
				'description' => __( 'Review count', 'newsletter-optin-box' ),
				'type'        => 'number',
			),
			'meta'                    => $this->meta_key_tag_config(),
		);

		return apply_filters( 'noptin_post_type_known_custom_fields', $fields, $this->type );
	}

	/**
	 * Returns the template for the list shortcode.
	 */
	protected function get_list_shortcode_template() {
		return array(
			'button'      => \Hizzle\Noptin\Emails\Admin\Editor::merge_tag_to_block_name( $this->field_to_merge_tag( 'add_to_cart_url' ) ),
			'image'       => \Hizzle\Noptin\Emails\Admin\Editor::merge_tag_to_block_name( $this->field_to_merge_tag( 'image' ) ),
			'description' => $this->field_to_merge_tag( 'short_description' ),
			'heading'     => \Hizzle\Noptin\Emails\Admin\Editor::merge_tag_to_block_name( $this->field_to_merge_tag( 'name' ) ),
			'meta'        => $this->field_to_merge_tag( 'price_html' ),
		);
	}

	/**
	 * Returns a list of available triggers.
	 *
	 * @return array $triggers The triggers.
	 */
	public function get_triggers() {
		return array_merge(
			parent::get_triggers(),
			array(
				'woocommerce_' . $this->type . '_purchased' => array(
					'label'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Purchased', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'When a %s is purchased', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'subject'     => 'wc_customer',
				),
				'woocommerce_' . $this->type . '_refunded' => array(
					'label'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Refunded', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'When a %s is refunded', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'subject'     => 'wc_customer',
				),
			)
		);
	}

	/**
	 * Fired when a product is purchased.
	 *
	 * @param int|\WC_Order $order_id The order being acted on.
	 */
	public function on_purchase( $order_id ) {
		$this->on_purchase_or_refund( $order_id, 'purchased' );
	}

	/**
	 * Fired when a product is refunded.
	 *
	 * @param int|\WC_Order $order_id The order being acted on.
	 */
	public function on_refund( $order_id ) {
		$this->on_purchase_or_refund( $order_id, 'refunded' );
	}

	/**
	 * Fired when a product is purchased or refunded.
	 *
	 * @param int|\WC_Order $order_id The order being acted on.
	 * @param string        $action   The action being performed.
	 */
	public function on_purchase_or_refund( $order_id, $action ) {

		if ( is_numeric( $order_id ) ) {
			$order = wc_get_order( $order_id );
		} else {
			$order = $order_id;
		}

		// Ensure we have an order.
		if ( empty( $order ) || ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		// Prepare the order customer.
		$customer = $this->get_order_customer( $order );

		// Loop through the order items.
		foreach ( $order->get_items() as $item ) {

			// Ensure we have a product.
			/** @var \WC_Order_Item_Product $item */
			$product = $item->get_product();
			if ( empty( $product ) ) {
				continue;
			}

			// Ensure we have a product id.
			$product_id = $product->get_id();
			if ( empty( $product_id ) ) {
				continue;
			}

			// Trigger the event.
			$this->trigger(
				'woocommerce_' . $this->type . '_' . $action,
				array(
					'email'       => $order->get_billing_email(),
					'object_id'   => $product_id,
					'subject_id'  => $customer,
					'unserialize' => array(
						'order.status' => $order->get_status(),
					),
					'provides'    => array(
						'order'      => $order->get_id(),
						'order_item' => $item->get_id(),
					),
				)
			);
		}
	}

	/**
	 * Retrieves the order customer.
	 *
	 * @param \WC_Order $order The order being acted on.
	 */
	protected function get_order_customer( $order ) {
		$customer = new \WC_Customer( $order->get_customer_id() );

		if ( $customer->get_id() ) {
			return $customer->get_id();
		}

		return 0 - $order->get_id();
	}
}
