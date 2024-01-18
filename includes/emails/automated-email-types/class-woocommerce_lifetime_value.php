<?php
/**
 * Emails API: WooCommerce Lifetime Value.
 *
 * Send an email to a customer when they reach a certain lifetime value.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Send an email to a customer when they reach a certain lifetime value.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_WooCommerce_Lifetime_Value_Email extends Noptin_WooCommerce_Automated_Email_Type {

	/**
	 * @var string
	 */
	public $type = 'woocommerce_lifetime_value';

	/**
	 * @var string
	 */
	public $notification_hook = 'noptin_woocommerce_lifetime_value_notify';

	/**
	 * Registers hooks.
	 *
	 */
	public function add_hooks() {
		parent::add_hooks();

		// Notify customers.
		add_action( 'noptin_woocommerce_order_paid', array( $this, 'maybe_schedule_notification' ), 100, 2 );
	}

	/**
	 * Retrieves the automated email type name.
	 *
	 */
	public function get_name() {
		return __( 'Lifetime Value', 'newsletter-optin-box' );
	}

	/**
	 * Retrieves the automated email type description.
	 *
	 */
	public function get_description() {
		return __( 'Send an email to your customers when they reach a given lifetime value.', 'newsletter-optin-box' );
	}

	/**
	 * Returns the default template.
	 *
	 */
	public function default_template() {
		return 'woocommerce';
	}

	/**
	 * Returns the default subject.
	 *
	 */
	public function default_subject() {
		return __( '[[customer.first_name]], thanks for being a loyal customer!', 'newsletter-optin-box' );
	}

	/**
	 * Returns the default content.
	 *
	 */
	public function default_content_normal() {
		ob_start();
		?>
		<p><?php esc_html_e( 'Hi [[customer.first_name]],', 'newsletter-optin-box' ); ?></p>
		<p><?php esc_html_e( 'To show you that we appreciate your loyalty, here is a coupon code for 20% off your next order.', 'newsletter-optin-box' ); ?></p>
		<p><h2 style="text-align: center;">20OFF</h2></p>
		<p><?php esc_html_e( 'Thanks for choosing [[blog_name]]!', 'newsletter-optin-box' ); ?></p>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns the default plain text content.
	 *
	 */
	public function default_content_plain_text() {
		return noptin_convert_html_to_text( $this->default_content_normal() );
	}

	/**
	 * Returns the default recipient.
	 *
	 */
	public function get_default_recipient() {
		return '[[customer.email]]';
	}

	/**
	 * Displays a metabox.
	 *
	 * @param Noptin_Automated_Email $campaign
	 */
	public function campaign_options( $options ) {
		return array_merge(
			$options,
			array(
				'lifetime_value' => array(
					'el'               => 'input',
					'type'             => 'number',
					'label'            => __( 'Lifetime Value', 'newsletter-optin-box' ),
					'description'      => __( 'This email is automatically sent whenever a customer\'s lifetime value surpases the specified amount.', 'newsletter-optin-box' ),
					'customAttributes' => array(
						'min'  => 0,
						'step' => 'any',
					),
				),
			)
		);
	}

	/**
	 * Retrieves an array of supported merge tags.
	 *
	 * @return array
	 */
	public function get_merge_tags() {

		return array(
			__( 'Order', 'newsletter-optin-box' )    => $this->get_order_merge_tags(),
			__( 'Customer', 'newsletter-optin-box' ) => $this->get_customer_merge_tags(),
		);
	}

	/**
	 * Notify customers when they make a new order.
	 *
     * @param int $order_id The order being acted on.
     * @param Noptin_WooCommerce $bridge The Noptin and WC integration bridge.
	 */
	public function maybe_schedule_notification( $order_id, $woocommerce ) {

		$order = wc_get_order( $order_id );

		// Ensure the order exists.
		if ( empty( $order ) ) {
			return;
		}

		// Are there any automations.
		$automations = $this->get_automations();
		if ( empty( $automations ) ) {
			return;
		}

		// Fetch the user associated with the order.
		$user = $woocommerce->get_order_customer_user_id( $order->get_id() );
		if ( empty( $user ) ) {
			$user = $woocommerce->get_order_customer_email( $order->get_id() );
		}

		// Calculate their lifetime value.
		$lifetime_value = $woocommerce->get_total_spent( $user );

		foreach ( $automations as $automation ) {

			// Check if the automation applies here.
			if ( $automation->can_send() && $this->is_automation_valid_for( $automation, $order, $lifetime_value ) ) {
				$this->schedule_notification( $order_id, $automation );
			}
		}
	}

	/**
	 * Checks if a given notification is valid for a given order
	 *
	 * @param Noptin_Automated_Email $automation
	 * @param WC_Order $order
	 * @param float $customer_lifetime_value The customers lifetime value.
	 */
	public function is_automation_valid_for( $automation, $order, $customer_lifetime_value ) {

		// Compare lifetime values.
		$automation_lifetime_value = floatval( $automation->get( 'lifetime_value' ) );
		$is_valid                  = $automation_lifetime_value <= $customer_lifetime_value;

		if ( $is_valid ) {

			// Ensure that the user reached this milestone in this specific order.
			$previous_total = $customer_lifetime_value - (float) $order->get_total();

			$is_valid = $previous_total < $automation_lifetime_value;

		}

		// Filter and return.
		return apply_filters( 'noptin_woocommerce_lifetime_value_notification_is_valid', $is_valid, $automation, $customer_lifetime_value, $order );
    }

	/**
	 * (Maybe) Send out a new order notification
	 *
	 * @param int $order_id
	 * @param int $campaign_id
	 * @param string $key
	 */
	public function maybe_send_notification( $order_id, $campaign_id ) {

		$order    = wc_get_order( $order_id );
		$campaign = new Noptin_Automated_Email( $campaign_id );
		$key      = $order_id . '_' . $campaign_id;

		// Ensure the order exists and the campaign is active.
		if ( empty( $order ) || ! $campaign->can_send() ) {
			return;
		}

		// Send the email.
		$this->order   = $order;
		$this->sending = true;

		// Set current customer.
		$customer_id = $order->get_customer_id();

		if ( $customer_id > 0 ) {
			$this->customer = new WC_Customer( $customer_id );
		}

		$this->prepare_and_send( $campaign, $key );
	}

	/**
	 * Prepares a customer.
	 *
	 * @param WC_Customer $customer
	 */
	public function prepare_customer( $customer ) {

		if ( empty( $customer ) ) {
			return;
		}

		// Set variables.
		$this->customer = $customer;
		$this->user     = get_user_by( 'id', $customer->get_id() );

		$this->maybe_set_subscriber_and_user_from_customer( $customer );

		// Prepare merge tags.
		foreach ( $this->get_customer_merge_tags() as $tag => $details ) {
			noptin()->emails->tags->add_tag( $tag, $details );
		}
	}

	/**
	 * Cleans the class.
	 *
	 */
	public function clean_customer() {

		// Set variables.
		$this->customer   = null;
		$this->user       = null;
		$this->subscriber = null;

		// Prepare merge tags.
		foreach ( array_keys( $this->get_customer_merge_tags() ) as $tag ) {
			noptin()->emails->tags->remove_tag( $tag );
		}
	}
}
