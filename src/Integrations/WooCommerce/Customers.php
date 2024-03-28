<?php

namespace Hizzle\Noptin\Integrations\WooCommerce;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for WooCommerce customers.
 *
 * @since 3.0.0
 */
class Customers extends \Hizzle\Noptin\Objects\People {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->integration    = 'woocommerce';
		$this->type           = 'customer';
		$this->label          = __( 'Customers', 'newsletter-optin-box' );
		$this->singular_label = __( 'Customer', 'newsletter-optin-box' );
		$this->email_sender   = 'woocommerce_customers';
		$this->record_class   = __NAMESPACE__ . '\Customer';
		$this->is_stand_alone = false;
		$this->can_list       = true;

		parent::__construct();
	}

	/**
	 * Retrieves a single person from a WordPress user.
	 *
	 * @param \WP_User $user The user.
	 * @return Customer $person The person.
	 */
	public function get_from_user( $user ) {

		// Abort if no user.
		if ( ! $user ) {
			return new Customer( 0 );
		}

		$external = new \WC_Customer( $user->ID );

		if ( $external->get_id() > 0 ) {
			return new Customer( $external );
		}

		// Fetch for order by email.
		return $this->get_from_email( $user->user_email );
	}

	/**
	 * Retrieves a single person from an email address.
	 *
	 * @param string $email The email address.
	 * @return Customer $person The person.
	 */
	public function get_from_email( $email ) {

		// Fetch for order by email.
		$order = wc_get_orders(
			array(
				'limit'    => 1,
				'return'   => 'objects',
				'orderby'  => 'none',
				'customer' => $email,
			)
		);

		if ( $order ) {
			$user_id = $order[0]->get_user_id() > 0 ? $order[0]->get_user_id() : 0 - $order[0]->get_id();
			return new Customer( $user_id );
		}

		return new Customer( 0 );
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {
		$fields = array(
			'id'               => array(
				'label' => __( 'ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'order_count'      => array(
				'label'      => __( 'Number of orders', 'newsletter-optin-box' ),
				'type'       => 'number',
				'deprecated' => 'woocommerce_orders',
			),
			'total_spent'      => array(
				'label'   => __( 'Lifetime value', 'newsletter-optin-box' ),
				'type'    => 'number',
				'example' => "format='price'",
			),
			'first_order_date' => array(
				'label' => __( 'First order date', 'newsletter-optin-box' ),
				'type'  => 'date',
			),
			'username'         => array(
				'label' => __( 'Username', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'email'            => array(
				'label' => __( 'Email', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'first_name'       => array(
				'label' => __( 'First name', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'last_name'        => array(
				'label' => __( 'Last name', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'display_name'     => array(
				'label' => __( 'Display name', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'locale'           => array(
				'label' => __( 'Locale', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'billing_address'  => array(
				'label' => __( 'Billing address', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'billing_city'     => array(
				'label' => __( 'Billing city', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'billing_state'    => array(
				'label' => __( 'Billing state', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'billing_postcode' => array(
				'label' => __( 'Billing postcode', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'billing_country'  => array(
				'label' => __( 'Billing country', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'billing_phone'    => array(
				'label' => __( 'Billing phone', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
		);

		// Add provided fields.
		$fields = $this->add_provided( $fields );

		return $fields;
	}

	/**
	 * Retrieves fields that can be calculated from an email address.
	 */
	public function provides() {
		return array( 'order_count', 'total_spent' );
	}

	/**
	 * Retrieves a test ID.
	 *
	 */
	public function get_test_id() {
		return get_current_user_id();
	}
}
