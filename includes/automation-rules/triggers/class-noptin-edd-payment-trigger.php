<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when an EDD payment's status changes.
 *
 * @since 1.10.3
 */
class Noptin_Edd_Payment_Trigger extends Noptin_EDD_Trigger {

	/**
	 * @var string The trigger's payment action.
	 */
	protected $payment_action;

	/**
	 * @var string The trigger's payment action label.
	 */
	protected $payment_action_label;

	/**
	 * Constructor.
	 *
	 * @since 1.10.3
	 * @param string $payment_action The trigger's payment status.
	 * @param string $payment_action_label The trigger's payment action label.
	 */
	public function __construct( $payment_action, $payment_action_label ) {
		$this->payment_action       = $payment_action;
		$this->payment_action_label = $payment_action_label;

		add_action( 'edd_update_payment_status', array( $this, 'init_trigger' ), 10000, 3 );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'edd_' . sanitize_key( $this->payment_action );
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		// translators: %s is the payment action label, e.g. "Complete" or "Refunded".
		return sprintf( __( 'EDD Payment > %s', 'newsletter-optin-box' ), $this->payment_action_label );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		// translators: %s is the payment action label, e.g. "Complete" or "Refunded".
		return sprintf( __( 'When an EDD payment is %s', 'newsletter-optin-box' ), strtolower( $this->payment_action_label ) );
	}

	/**
     * Returns an array of known smart tags.
     *
     * @since 1.9.0
     * @return array
     */
    public function get_known_smart_tags() {

		return array_merge(
			parent::get_known_smart_tags(),
			array(
				'previous_status' => array(
					'description' => __( 'The previous order status.', 'newsletter-optin-box' ),
					'example'     => 'previous_status',
				),
			),
			$this->get_payment_smart_tags(),
			$this->get_customer_smart_tags()
		);
    }

	/**
	 * Inits the trigger.
	 *
	 * @param $payment_id int EDD_Payment object ID
	 * @param $new_status str New payment status
	 * @param $old_status str Old payment status
	 * @since 1.9.0
	 */
	public function init_trigger( $payment_id, $new_status, $old_status ) {

		if ( $new_status === $old_status || $new_status !== $this->payment_action ) {
			return;
		}

		$payment = edd_get_payment( $payment_id );

		if ( empty( $payment ) || ! is_a( $payment, 'EDD_Payment' ) ) {
			return;
		}

		$this->payment  = $payment;
		$this->customer = edd_get_customer( $payment->customer_id );

		if ( empty( $this->customer ) ) {
			return;
		}

		// Record activity.
		noptin_record_subscriber_activity(
			$this->customer->email,
			sprintf(
				// translators: %1 is the payment number, %2 is the payment action label, e.g. "Complete" or "Refunded".
				__( 'EDD payment #%1$s %2$s', 'newsletter-optin-box' ),
				sprintf(
					'<a href="%s">%s</a> (%s)',
					esc_url( admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $payment->ID ) ),
					$payment->number,
					edd_currency_filter( edd_format_amount( $payment->total, true, $payment->currency ), $payment->currency )
				),
				strtolower( $this->payment_action_label )
			)
		);

		$args = array(
			'email'           => $payment->email,
			'previous_status' => $old_status,
			'payment_id'      => $payment->ID,
		);

		$this->trigger( $this->customer, $args );

		$this->payment  = false;
		$this->customer = false;
	}

	/**
	 * Serializes the trigger args.
	 *
	 * @since 1.11.1
	 * @param array $args The args.
	 * @return false|array
	 */
	public function serialize_trigger_args( $args ) {
		unset( $args['subject'] );
		unset( $args['smart_tags'] );
		return $args;
	}

	/**
	 * Unserializes the trigger args.
	 *
	 * @since 1.11.1
	 * @param array $args The args.
	 * @return array|false
	 */
	public function unserialize_trigger_args( $args ) {

		// Fetch the payment.
		$payment = edd_get_payment( $args['payment_id'] );

		if ( empty( $payment ) || ! is_a( $payment, 'EDD_Payment' ) ) {
			throw new Exception( 'The payment no longer exists' );
		}

		// Check the status.
		if ( $payment->status !== $this->payment_action ) {
			throw new Exception( 'The payment status is not valid' );
		}

		// Fetch the customer.
		$customer = edd_get_customer( $payment->customer_id );

		if ( empty( $customer ) ) {
			throw new Exception( 'The customer no longer exists' );
		}

		$this->payment  = $payment;
		$this->customer = $customer;

		// Prepare the trigger args.
		return $this->prepare_trigger_args( $this->customer, $args );
	}
}
