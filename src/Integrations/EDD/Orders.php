<?php

namespace Hizzle\Noptin\Integrations\EDD;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Container for EDD orders.
 *
 * @since 2.2.0
 */
class Orders extends \Hizzle\Noptin\Objects\Collection {

	/**
	 * @var string the record class.
	 */
	public $record_class = __NAMESPACE__ . '\Order';

	/**
	 * @var string type.
	 */
	public $type = 'edd_order';

	/**
	 * @var string prefix.
	 */
	public $smart_tags_prefix = 'order';

	/**
	 * @var string label.
	 */
	public $label = 'EDD Orders';

	/**
	 * @var string label.
	 */
	public $singular_label = 'EDD Order';

	/**
	 * @var string integration.
	 */
	public $integration = 'edd';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->show_tab = true;

		parent::__construct();
		add_action( 'edd_transition_order_status', array( $this, 'on_change_status' ), 10, 3 );
		add_action( 'edd_order_added', array( $this, 'on_create_order' ), 10, 2 );
	}

	/**
	 * Retrieves several items by email.
	 *
	 */
	public function get_all_by_email( $email_address, $limit = 25 ) {
		return edd_get_orders(
			array(
				'number' => $limit,
				'email'  => $email_address,
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
	 * Returns a list of available triggers.
	 *
	 * @return array $triggers The triggers.
	 */
	public function get_triggers() {
		$triggers = array(
			$this->type . '_created' => array(
				'label'       => sprintf(
					/* translators: %s: Object type label. */
					__( '%s > Created', 'newsletter-optin-box' ),
					$this->singular_label
				),
				'description' => __( 'When an EDD order is created', 'newsletter-optin-box' ),
				'subject'     => 'edd_customer',
				'provides'    => array( 'user' ),
			),
		);

		// Add a new trigger for each payment status.
		foreach ( edd_get_payment_statuses() as $status => $label ) {
			$triggers[ 'edd_' . sanitize_key( $status ) ] = array(
				'label'       => $this->singular_label . ' > ' . $label,
				'description' => sprintf(
					// translators: %s is the payment action label, e.g. "Complete" or "Refunded".
					__( 'When an EDD order is %s', 'newsletter-optin-box' ),
					strtolower( $label )
				),
				'subject'     => 'edd_customer',
				'extra_args'  => array(
					'previous_status' => array(
						'description' => __( 'The previous order status.', 'newsletter-optin-box' ),
						'type'        => 'string',
						'options'     => array_merge(
							array( 'new' => __( 'New', 'newsletter-optin-box' ) ),
							edd_get_payment_statuses()
						),
					),
				),
				'provides'    => array( 'user' ),
			);
		}

		return $triggers;
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {

		return array(
			'id'                => array(
				'label' => __( 'ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'parent'            => array(
				'label' => __( 'Parent ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'number'            => array(
				'label' => __( 'Number', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'key'               => array(
				'label' => __( 'Key', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'transaction_id'    => array(
				'label' => __( 'Transaction ID', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'gateway'           => array(
				'label'   => __( 'Gateway', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => wp_list_pluck(
					edd_get_payment_gateways(),
					'admin_label'
				),
			),
			'currency'          => array(
				'label'   => __( 'Currency', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => edd_get_currencies(),
			),
			'subtotal'          => array(
				'label' => __( 'Subtotal', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'fees_total'        => array(
				'label' => __( 'Fees Total', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'tax'               => array(
				'label' => __( 'Tax', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'discounted_amount' => array(
				'label' => __( 'Discounted Amount', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'total'             => array(
				'label' => __( 'Total', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'discounts'         => array(
				'label' => __( 'Discounts', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'tax_rate'          => array(
				'label' => __( 'Tax Rate', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'date'              => array(
				'label' => __( 'Created Date', 'newsletter-optin-box' ),
				'type'  => 'date',
			),
			'completed_date'    => array(
				'label' => __( 'Completed Date', 'newsletter-optin-box' ),
				'type'  => 'date',
			),
			'date_refundable'   => array(
				'label' => __( 'Refundable Date', 'newsletter-optin-box' ),
				'type'  => 'date',
			),
			'is_complete'       => array(
				'label' => __( 'Is Complete', 'newsletter-optin-box' ),
				'type'  => 'boolean',
			),
			'is_recoverable'    => array(
				'label' => __( 'Is Recoverable', 'newsletter-optin-box' ),
				'type'  => 'boolean',
			),
			'status'            => array(
				'label'   => __( 'Status', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => edd_get_payment_statuses(),
			),
			'status_nicename'   => array(
				'label' => __( 'Status Nicename', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'recovery_url'      => array(
				'label' => __( 'Recovery URL', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'admin_url'         => array(
				'label' => __( 'Admin URL', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'first_name'        => array(
				'label' => __( 'First name', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'last_name'         => array(
				'label' => __( 'Last name', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'email'             => array(
				'label' => __( 'Email', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'billing_address'   => array(
				'label'          => __( 'Full Address', 'newsletter-optin-box' ),
				'type'           => 'string',
				'skip_smart_tag' => true,
			),
			'address.line1'     => array(
				'label' => __( 'Address 1', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'address.line2'     => array(
				'label' => __( 'Address 2', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'address.city'      => array(
				'label' => __( 'City', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'address.state'     => array(
				'label' => __( 'State', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'address.country'   => array(
				'label' => __( 'Country', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'address.zip'       => array(
				'label' => __( 'ZIP', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'customer_id'       => array(
				'label' => __( 'Customer ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'user_id'           => array(
				'label' => __( 'User ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'ip'                => array(
				'label' => __( 'Customer IP', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'mode'              => array(
				'label'   => __( 'Mode', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => array(
					'live' => __( 'Live', 'newsletter-optin-box' ),
					'test' => __( 'Test', 'newsletter-optin-box' ),
				),
			),
			'meta'              => array(
				'label'   => __( 'Meta Value', 'newsletter-optin-box' ),
				'type'    => 'string',
				'example' => 'key="my_key"',
			),
			'download_list'     => array(
				'label'          => __( 'Download Links', 'newsletter-optin-box' ),
				'type'           => 'string',
				'skip_smart_tag' => true,
			),
		);
	}

	/**
	 * Inits post type triggers.
	 */
	public function on_change_status( $old_status, $new_status, $order_id ) {

		if ( $new_status === $old_status ) {
			return;
		}

		$order = edd_get_order( $order_id );

		if ( empty( $order ) || ! is_a( $order, '\EDD\Orders\Order' ) ) {
			return;
		}

		$customer = edd_get_customer( $order->customer_id );

		if ( empty( $customer ) ) {
			return;
		}

		$referring_campaign = edd_get_order_meta( $order->id, '_noptin_referring_campaign', true );

		if ( empty( $referring_campaign ) ) {
			$referring_campaign = noptin_get_referring_email_id();

			if ( ! empty( $referring_campaign ) ) {
				edd_update_order_meta( $order->id, '_noptin_referring_campaign', $referring_campaign );
			}
		}

		if ( ! empty( $referring_campaign ) && 'complete' === $new_status ) {
			noptin_record_ecommerce_purchase( $order->total, $referring_campaign );
		}

		$this->trigger(
			'edd_' . sanitize_key( $new_status ),
			array(
				'email'       => $order->email,
				'object_id'   => $order->id,
				'subject_id'  => $order->customer_id,
				'extra_args'  => array(
					'order.previous_status' => $old_status,
				),
				'provides'    => array(
					'user' => $order->user_id,
				),
				'unserialize' => array(
					'order.status' => $new_status,
				),
				'url'         => admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $order->ID ),
				'activity'    => edd_currency_filter( edd_format_amount( $order->total, true, $order->currency ), $order->currency ),
			)
		);
	}

	/**
	 * Fired after an order is created.
	 *
	 */
	public function on_create_order( $order_id, $args ) {

		if ( ! empty( $args['email'] ) && ! empty( $args['customer_id'] ) ) {
			$this->trigger(
				$this->type . '_created',
				array(
					'email'      => $args['email'],
					'object_id'  => $order_id,
					'subject_id' => $args['customer_id'],
					'provides'   => array(
						'user' => $args['user_id'],
					),
				)
			);
		}
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

		if ( false !== strpos( $rule->get_trigger_id(), 'edd_' ) && 'edd_created' !== $rule->get_trigger_id() ) {
			$args = array(
				'status__in' => array( substr( $rule->get_trigger_id(), 4 ) ),
			);
		} else {
			$args = array(
				'status__in' => edd_get_payment_status_keys(),
			);
		}

		$order = self::get_test_order( $args );
		return array(
			'email'      => $order->email,
			'object_id'  => $order->id,
			'subject_id' => $order->customer_id,
			'extra_args' => array(
				'order.previous_status' => 'new',
			),
			'provides'   => array(
				'user' => $order->user_id,
			),
		);
	}

	/**
	 * Retrieves a test order args.
	 *
	 * @since 2.2.0
	 * @param array $args
	 * @throws \Exception
	 * @return \EDD\Orders\Order
	 */
	public static function get_test_order( $args = array() ) {

		// Fetch latest order.
		$order = current(
			edd_get_orders(
				array_merge(
					array(
						'number'     => 1,
						'status__in' => array( 'complete' ),
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
