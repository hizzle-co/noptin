<?php

namespace Hizzle\Noptin\Integrations\EDD;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Containers for EDD customers.
 *
 * @since 2.2.0
 */
class Customers extends \Hizzle\Noptin\Objects\People {

	/**
	 * @var string the record class.
	 */
	public $record_class = __NAMESPACE__ . '\Customer';

	/**
	 * @var string type.
	 */
	public $type = 'edd_customer';

	/**
	 * @var string prefix.
	 */
	public $smart_tags_prefix = 'customer';

	/**
	 * @var string label.
	 */
	public $label = 'EDD Customers';

	/**
	 * @var string label.
	 */
	public $singular_label = 'EDD Customer';

	/**
	 * @var string integration.
	 */
	public $integration = 'edd';

	/**
	 * Retrieves several customers.
	 *
	 * @param array $filters The available filters.
	 * @return int[] $customers The customer IDs.
	 */
	public function get_all( $filters ) {
		return edd_get_customers(
			array(
				'number' => 9999999,
				'fields' => 'ids',
			)
		);
	}

	/**
	 * Retrieves a single person from a WordPress user.
	 *
	 * @param \WP_User $user The user.
	 * @return Customer $person The person.
	 */
	public function get_from_user( $user ) {
		return new Customer( edd_get_customer_by( 'user_id', $user->ID ) );
	}

	/**
	 * Retrieves a single person from an email address.
	 *
	 * @param string $email The email address.
	 * @return Customer $person The person.
	 */
	public function get_from_email( $email ) {
		return new Customer( edd_get_customer_by( 'email', $email ) );
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {
		return array(
			'id'                  => array(
				'label' => __( 'ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'user_id'             => array(
				'label' => __( 'User ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'purchase_count'      => array(
				'label' => __( 'Number of payments', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'purchase_value'      => array(
				'label'   => __( 'Lifetime value', 'newsletter-optin-box' ),
				'type'    => 'number',
			),
			'email'               => array(
				'label' => __( 'Primary email address', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'emails'              => array(
				'label' => __( 'Known email addresses', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'name'                => array(
				'label' => __( 'Name', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'address.address'     => array(
				'label' => __( 'Address 1', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'address.address2'    => array(
				'label' => __( 'Address 2', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'address.city'        => array(
				'label' => __( 'City', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'address.region'      => array(
				'label' => __( 'State', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'address.country'     => array(
				'label' => __( 'Country', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'address.postal_code' => array(
				'label' => __( 'ZIP', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'status'              => array(
				'label' => __( 'Status', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'date_created'        => array(
				'label'   => __( 'Registration date', 'newsletter-optin-box' ),
				'type'    => 'date',
			),
			'order_ids'           => array(
				'label' => __( 'Payment IDs', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'meta'                => array(
				'label'   => __( 'Meta Value', 'newsletter-optin-box' ),
				'type'    => 'string',
				'example' => 'key="my_key"',
			),
		);
	}

	/**
	 * Retrieves fields that can be calculated from an email address.
	 */
	public function provides() {
		return array(
			'purchase_count' => array(
				'label' => __( 'Number of payments', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'purchase_value' => array(
				'label'   => __( 'Lifetime value', 'newsletter-optin-box' ),
				'type'    => 'number',
			)
		);
	}

	/**
	 * Retrieves a test ID.
	 *
	 */
	public function get_test_id() {
		return current(
			edd_get_customers(
				array(
					'number' => 1,
					'fields' => 'ids',
				)
			)
		);
	}
}
