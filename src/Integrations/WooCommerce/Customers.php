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
		$this->icon           = array(
			'icon' => 'admin-users',
			'fill' => '#674399',
		);

		parent::__construct();

		add_action( 'woocommerce_payment_complete', array( $this, 'payment_complete' ), 100 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'payment_complete' ), 100 );
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

		return new Customer( $user->ID );
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
	 * Retrieves the manual recipients.
	 */
	public function get_manual_recipients() {
		return array(
			$this->field_to_merge_tag( 'email' ) => $this->singular_label,
		);
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
				'label'   => __( 'Locale', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => noptin_get_available_languages(),
				'default' => get_locale(),
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
	 * Returns a list of available triggers.
	 *
	 * @return array $triggers The triggers.
	 */
	public function get_triggers() {
		return array_merge(
			parent::get_triggers(),
			array(
				'woocommerce_lifetime_value' => array(
					'label'          => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Lifetime Value', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description'    => sprintf(
						/* translators: %s: Object type label. */
						__( 'When a %s\'s lifetime value surpasses a certain amount', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'subject'        => 'customer',
					'extra_settings' => array(
						'lifetime_value' => array(
							'el'          => 'input',
							'type'        => 'number',
							'label'       => __( 'Lifetime Value', 'noptin-woocommerce' ),
							'description' => __( 'Enter the amount of money the customer has spent in total.', 'noptin-woocommerce' ),
							'placeholder' => '1000',
							'default'     => '1000',
							'required'    => true,
							'prefix'      => get_woocommerce_currency_symbol(),
						),
					),
				),
			)
		);
	}

	/**
	 * Called when a payment is complete.
	 *
	 * @param int $order_id The order ID.
	 */
	public function payment_complete( $order_id ) {
		if ( ! Orders::is_complete() ) {
			return;
		}

		/** @var \Hizzle\Noptin\Automation_Rules\Automation_Rule[] $rules */
		$rules = noptin_get_automation_rules(
			array(
				'trigger_id' => array( 'woocommerce_lifetime_value', 'woocommerce_lifetime_orders' ),
				'status'     => true,
			)
		);

		if ( empty( $rules ) ) {
			return;
		}

		if ( is_numeric( $order_id ) ) {
			$order = wc_get_order( $order_id );
		} else {
			$order = $order_id;
		}

		$customer_id = Orders::get_order_customer( $order );
		/** @var Customer $customer */
		$customer        = $this->get( $customer_id );
		$lifetime_spent  = null;
		$previous_spent  = null;
		$lifetime_orders = null;
		$previous_orders = null;

		// Loop through rules.
		foreach ( $rules as $rule ) {

			// Lifetime value.
			if ( 'woocommerce_lifetime_value' === $rule->get_trigger_id() ) {
				$needed_spent = (float) $rule->get_trigger_setting( 'lifetime_value' );

				if ( empty( $needed_spent ) ) {
					continue;
				}

				if ( is_null( $lifetime_spent ) ) {
					$lifetime_spent = Main::calculate_customer_lifetime_value( $customer->get_email() );
					$previous_spent = $lifetime_spent - $order->get_total();
				}

				// Ensure the lifetime spend is = or > than needed spent and the previous spent is less than the needed spent.
				if ( $lifetime_spent < $needed_spent || $previous_spent > $needed_spent ) {
					continue;
				}
			}

			// Lifetime orders.
			if ( 'woocommerce_lifetime_orders' === $rule->get_trigger_id() ) {
				$needed_orders = (int) $rule->get_trigger_setting( 'orders' );

				if ( empty( $needed_orders ) ) {
					continue;
				}

				if ( is_null( $lifetime_orders ) ) {
					$lifetime_orders = Main::count_customer_orders( $customer->get_email() );
					$previous_orders = $lifetime_orders - 1;
				}

				// Ensure the lifetime orders is = or > than needed orders and the previous orders is less than the needed orders.
				if ( $lifetime_orders < $needed_orders || $previous_orders > $needed_orders ) {
					continue;
				}
			}

			$this->trigger(
				$rule->get_trigger_id(),
				array(
					'email'      => $customer->get_email(),
					'object_id'  => $customer_id,
					'subject_id' => $customer_id,
					'rule_id'    => $rule->get_id(),
				)
			);
		}
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
		return get_current_user_id();
	}
}
