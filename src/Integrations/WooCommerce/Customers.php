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
	 * @var string the record class.
	 */
	public $record_class = '\Hizzle\Noptin\Integrations\WooCommerce\Customer';

	/**
	 * @var string type.
	 */
	public $type = 'wc_customer';

	/**
	 * @var string prefix.
	 */
	public $smart_tags_prefix = 'customer';

	/**
	 * @var string label.
	 */
	public $label = 'WooCommerce Customers';

	/**
	 * @var string label.
	 */
	public $singular_label = 'WooCommerce Customer';

	/**
	 * @var string integration.
	 */
	public $integration = 'woocommerce';

	/**
	 * Retrieves a single person from a WordPress user.
	 *
	 * @param \WP_User $user The user.
	 * @return Customer $person The person.
	 */
	public function get_from_user( $user ) {
		return new Customer( $user ? $user->ID : 0 );
	}

	/**
	 * Retrieves a single person from an email address.
	 *
	 * @param string $email The email address.
	 * @return Customer $person The person.
	 */
	public function get_from_email( $email ) {
		return $this->get_from_user( get_user_by( 'email', $email ) );
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {
		return array(
			'id'               => array(
				'label' => __( 'ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'order_count'      => array(
				'label' => __( 'Number of orders', 'newsletter-optin-box' ),
				'type'  => 'number',
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
				'type'  => 'number',
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
		);
	}

	/**
	 * Retrieves fields that can be calculated from an email address.
	 */
	public function provides() {
		return array( 'order_count', 'total_spent' );
	}
}
