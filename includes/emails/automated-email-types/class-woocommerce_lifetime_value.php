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
	 * Retrieves the automated email type image.
	 *
	 */
	public function the_image() {
		echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 503.81 299.89"><path fill="#7f54b3" d="M46.75,0H456.84a46.94,46.94,0,0,1,47,47V203.5a46.94,46.94,0,0,1-47,47H309.78L330,299.89l-88.78-49.43H47a46.94,46.94,0,0,1-47-47V47A46.77,46.77,0,0,1,46.76,0Z"/><path fill="#fff" d="M28.69,42.8c2.86-3.89,7.16-5.94,12.9-6.35Q57.25,35.24,59.41,51.2,68.94,115.4,80.09,160l44.85-85.4q6.15-11.67,15.36-12.29c9-.61,14.54,5.12,16.8,17.2,5.12,27.24,11.67,50.38,19.45,70q8-78,27-112.64c3.07-5.73,7.57-8.6,13.51-9A17.8,17.8,0,0,1,230,32a16,16,0,0,1,6.35,11.67,17.79,17.79,0,0,1-2,9.83c-8,14.75-14.55,39.53-19.87,73.93-5.12,33.39-7,59.4-5.73,78a24.29,24.29,0,0,1-2.46,13.52c-2.46,4.51-6.15,7-10.86,7.37-5.32.41-10.85-2.05-16.17-7.57Q150.64,189.54,134,131.48q-20,39.32-29.49,59c-12.09,23.14-22.33,35-30.93,35.64C68,226.51,63.3,221.8,59.2,212Q43.54,171.72,25.41,56.52A17.44,17.44,0,0,1,28.69,42.8ZM468.81,75C461.43,62.05,450.58,54.27,436,51.2A53.72,53.72,0,0,0,425,50c-19.66,0-35.63,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.23,20.69,32.77,23.76A53.64,53.64,0,0,0,414.54,204c19.86,0,35.83-10.24,48.12-30.72a109.73,109.73,0,0,0,16-58C478.84,99.33,475.36,86,468.81,75ZM443,131.69c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.92-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95,66.29,66.29,0,0,1,10.86-24.37c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31a52,52,0,0,1,3.69,18.64A71.47,71.47,0,0,1,443,131.69ZM340.6,75c-7.37-12.91-18.43-20.69-32.76-23.76A53.79,53.79,0,0,0,296.78,50c-19.66,0-35.64,10.24-48.13,30.72a108.52,108.52,0,0,0-16,57.75q0,23.66,9.83,40.55c7.37,12.91,18.22,20.69,32.76,23.76A53.72,53.72,0,0,0,286.33,204c19.87,0,35.84-10.24,48.13-30.72a109.72,109.72,0,0,0,16-58C350.43,99.33,347.16,86,340.6,75Zm-26,56.73c-2.86,13.51-8,23.55-15.56,30.31-5.94,5.32-11.47,7.57-16.59,6.55-4.91-1-9-5.32-12.08-13.31a52,52,0,0,1-3.69-18.64,71.48,71.48,0,0,1,1.43-14.95A66.29,66.29,0,0,1,279,97.28c6.76-10,13.92-14.13,21.3-12.7,4.91,1,9,5.33,12.08,13.31A52,52,0,0,1,316,116.53a60.45,60.45,0,0,1-1.44,15.16Z"/></svg>';
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
	public function render_metabox( $campaign ) {

		?>
			<p>
				<label>
					<strong class="noptin-label-span">
						<?php esc_html_e( 'Lifetime Value', 'newsletter-optin-box' ); ?>
					</strong>
					<input class="widefat" type="number" name="noptin_email[lifetime_value]" value="<?php echo floatval( $campaign->get( 'lifetime_value' ) ); ?>" min="0" step="any">
				</label>
				<span class="noptin-help-text">
					<?php esc_html_e( "This email is automatically sent whenever a customer's lifetime value surpases the specified amount.", 'newsletter-optin-box' ); ?>
				</span>
			</p>
		<?php

	}

	/**
	 * Filters automation summary.
	 *
	 * @param string $about
	 * @param Noptin_Automated_Email $campaign
	 */
	public function about_automation( $about, $campaign ) {

		$lifetime_value = floatval( $campaign->get( 'lifetime_value' ) );

		if ( ! $campaign->sends_immediately() ) {

			return sprintf(
				// Translators: %1$s is the sending delay, %2$s is the lifetime value.
				__( 'Sends %1$s after a customer reaches a lifetime value of %2$s', 'newsletter-optin-box' ),
				(int) $campaign->get_sends_after() . ' ' . esc_html( $campaign->get_sends_after_unit( true ) ),
				wc_price( $lifetime_value )
			);

		}

		return sprintf(
			// Translators: %s is the lifetime value.
			__( 'Sends immediately a customer reaches a lifetime value of %s', 'newsletter-optin-box' ),
			wc_price( $lifetime_value )
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
