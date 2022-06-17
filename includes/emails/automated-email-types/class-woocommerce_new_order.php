<?php
/**
 * Emails API: WooCommerce New Order.
 *
 * Send an email to a customer when they make a new order.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Send an email to a customer when they make a new order.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_WooCommerce_New_Order_Email extends Noptin_WooCommerce_Automated_Email_Type {

	/**
	 * @var string
	 */
	public $type = 'woocommerce_new_order';

	/**
	 * @var string
	 */
	public $notification_hook = 'noptin_woocommerce_new_order_notify';

	/**
	 * Registers hooks.
	 *
	 */
	public function add_hooks() {
		parent::add_hooks();

		// Notify customers.
		add_action( 'noptin_woocommerce_order', array( $this, 'maybe_schedule_notification' ), 100, 3 );

		// Filters the products template.
		add_filter( 'noptin_post_digest_html', array( $this, 'maybe_filter_products_digest_template' ), 10, 3 );

	}

	/**
	 * Retrieves the automated email type name.
	 *
	 */
	public function get_name() {
		return __( 'Order Action', 'newsletter-optin-box' );
	}

	/**
	 * Retrieves the automated email type description.
	 *
	 */
	public function get_description() {
		return __( 'Send an email to your customers (or first time customers) when they make a new order or their order status changes.', 'newsletter-optin-box' );
	}

	/**
	 * Returns the default subject.
	 *
	 */
	public function default_subject() {
		return __( '[[customer.first_name]], help us make your next order perfect!', 'newsletter-optin-box' );
	}

	/**
	 * Returns the default heading.
	 *
	 */
	public function default_heading() {
		return __( 'How was your experience?', 'newsletter-optin-box' );
	}

	/**
	 * Returns the default content.
	 *
	 */
	public function default_content_normal() {
		ob_start();
		?>
		<p><?php esc_html_e( 'Hi [[customer.first_name]],', 'newsletter-optin-box' ); ?></p>
		<p><?php esc_html_e( 'We value your opinion and want to make your shopping experience perfect - so your feedback is important to us!', 'newsletter-optin-box' ); ?></p>
		<p><?php esc_html_e( 'Please reply to this email with any suggestions that might help us improve.', 'newsletter-optin-box' ); ?></p>
		<p><?php esc_html_e( 'Thanks for your help!', 'newsletter-optin-box' ); ?></p>
		<p>[[blog_name]]</p>
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
	 * Retrieves allowed order statuses.
	 *
	 * @return array
	 */
	public function get_allowed_statuses() {

		return array(
            'created'    => __( 'Created', 'newsletter-optin-box' ),
            'pending'    => __( 'Pending', 'newsletter-optin-box' ),
            'processing' => __( 'Processing', 'newsletter-optin-box' ),
            'held'       => __( 'Held', 'newsletter-optin-box' ),
            'paid'       => __( 'Paid', 'newsletter-optin-box' ),
            'completed'  => __( 'Completed', 'newsletter-optin-box' ),
            'refunded'   => __( 'Refunded', 'newsletter-optin-box' ),
            'cancelled'  => __( 'Cancelled', 'newsletter-optin-box' ),
            'failed'     => __( 'Failed', 'newsletter-optin-box' ),
            'deleted'    => __( 'Deleted', 'newsletter-optin-box' ),
        );

	}

	/**
	 * Retrieves the set order status for a campaign
	 *
	 * @param Noptin_Automated_Email $campaign
	 * @param bool $label
	 */
	public function get_campaign_order_status( $campaign, $label = false ) {

		// Fetch order statuses.
		$statuses = $this->get_allowed_statuses();

		// Prepare selected status.
		$status = $campaign->get( 'order_status' );

		if ( empty( $status ) || ! isset( $statuses[ $status ] ) ) {
			$status = 'paid';
		}

		return $label ? $statuses[ $status ] : $status;
	}

	/**
	 * Displays a metabox.
	 *
	 * @param Noptin_Automated_Email $campaign
	 */
	public function render_metabox( $campaign ) {

		// Fetch order statuses.
		$statuses = $this->get_allowed_statuses();

		// Prepare selected status.
		$status = $this->get_campaign_order_status( $campaign );

		// Are we sending to new customers.
		$new_customer = $campaign->get( 'new_customer' );

		?>
			<p>
				<label>
					<strong class="noptin-label-span">
						<?php esc_html_e( 'Send this email whenever an order is:-', 'newsletter-optin-box' ); ?>
					</strong>
					<select name="noptin_email[order_status]" id="noptin-automated-email-order-status" class="widefat">
						<?php foreach ( $statuses as $key => $label ) : ?>
							<option <?php selected( $status, $key ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</p>

			<p>
				<label>
					<input type="checkbox" name="noptin_email[new_customer]" <?php echo checked( ! empty( $new_customer ) ); ?>" value="1">
					<strong><?php esc_html_e( 'Only send to new customers?', 'newsletter-optin-box' ); ?></strong>
				</label>
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

		if ( ! $campaign->sends_immediately() ) {

			$about = sprintf(
				// Translators: %s is the sending delay.
				__( 'Sends %s after', 'newsletter-optin-box' ),
				(int) $campaign->get_sends_after() . ' ' . esc_html( $campaign->get_sends_after_unit( true ) )
			);

		} else {

			$about = __( 'Sends immediately', 'newsletter-optin-box' );
		}

		// Are we sending to new customers.
		$new_customer = $campaign->get( 'new_customer' );

		if ( ! empty( $new_customer ) ) {
			$about .= ' ' . __( "a first-time customer's order is", 'newsletter-optin-box' );
		} else {
			$about .= ' ' . __( "a customer's order is", 'newsletter-optin-box' );
		}

		// Prepare selected status.
		$about .= ' <em style="color: #607D8B;">' . strtolower( $this->get_campaign_order_status( $campaign, true ) ) . '</em>';

		return $about;

	}

	/**
	 * Notify customers when they make a new order.
	 *
	 * @param string $action The order action.
     * @param int $order_id The order being acted on.
     * @param Noptin_WooCommerce $bridge The Noptin and WC integration bridge.
	 */
	public function maybe_schedule_notification( $action, $order_id, $woocommerce ) {

		$order = wc_get_order( $order_id );

		// Ensure the order exists.
		if ( empty( $order ) ) {
			return;
		}

		// Are there any new post automations.
		$automations = $this->get_automations();
		if ( empty( $automations ) ) {
			return;
		}

		foreach ( $automations as $automation ) {

			// Check if the automation applies here.
			if ( $automation->can_send() && $this->is_automation_valid_for( $automation, $order, $action, $woocommerce ) ) {
				$this->schedule_notification( $order_id, $automation );
			}
		}

	}

	/**
	 * Checks if a given notification is valid for a given order
	 *
	 * @param Noptin_Automated_Email $automation
	 * @param WC_Order $order
	 * @param string $action
	 * @param Noptin_WooCommerce
	 */
	public function is_automation_valid_for( $automation, $order, $action, $woocommerce ) {

		$is_valid = true;

		// Prepare selected status.
		$status = $this->get_campaign_order_status( $automation );

		if ( $action !== $status ) {
			$is_valid = false;
		}

		// Are we sending to new customers.
		$new_customer = $automation->get( 'new_customer' );

		if ( ! empty( $new_customer ) && $is_valid ) {

			// Fetch the user associated with the order.
			$user = $woocommerce->get_order_customer_user_id( $order->get_id() );
			if ( empty( $user ) ) {
				$user = $woocommerce->get_order_customer_email( $order->get_id() );
			}

			$is_valid = $woocommerce->get_order_count( $user ) === 1;

		}

		return apply_filters( 'noptin_woocommerce_new_order_notification_is_valid', $is_valid, $automation, $order, $action );

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
	 * Get posts html to display.
	 *
	 * @param string $template
	 * @param WP_Post[] $campaign_posts
	 *
	 * @return string
	 */
	public function maybe_filter_products_digest_template( $content, $template, $campaign_posts ) {

		if ( null !== $content || empty( $campaign_posts ) || ! in_array( $campaign_posts[0]->post_type, array( 'product', 'product_variation' ), true ) || ! in_array( $template, array( 'grid', 'list' ), true ) ) {
			return $content;
		}

		$products = array_filter( array_map( 'wc_get_product', $campaign_posts ), 'wc_products_array_filter_visible' );

		return $this->get_products_html( $template, $products );
	}

}
