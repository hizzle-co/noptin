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
	}

	/**
	 * Retrieves the automated email type name.
	 *
	 */
	public function get_name() {
		return __( 'WooCommerce New Order', 'newsletter-optin-box' );
	}

	/**
	 * Retrieves the automated email type description.
	 *
	 */
	public function get_description() {
		return __( 'Send an email to your customers when they make a new order. Optionally limit the email to first-time customers.', 'newsletter-optin-box' );
	}

	/**
	 * Returns the default subject.
	 *
	 */
	public function default_subject() {
		return __( '[[customer.name]], help us make your next order perfect!', 'newsletter-optin-box' );
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
		<p><?php _e( 'Hi [[first_name]],', 'newsletter-optin-box' ); ?></p>
		<p><?php _e( 'We value your opinion and want to make your shopping experience perfect - so your feedback is important to us!', 'newsletter-optin-box' ); ?></p>
		<p><?php _e( 'Please reply to this email with any suggestions that might help us improve.', 'newsletter-optin-box' ); ?></p>
		<p><?php _e( 'Thanks for your help!', 'newsletter-optin-box' ); ?></p>
		<p>[[company]]</p>
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
						<?php _e( 'Send this email whenever an order is:-', 'newsletter-optin-box' ); ?>
					</strong>
					<select name="noptin_automation[order_status]" id="noptin-automated-email-order-status" class="widefat">
						<?php foreach ( $statuses as $key => $label ) : ?>
							<option <?php selected( $status, $key ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</p>

			<p>
				<label>
					<input type="checkbox" name="noptin_automation[new_customer]" <?php echo checked( ! empty( $new_customer ) ); ?>" value="1">
					<strong><?php _e( 'Only send to new customers?', 'newsletter-optin-box' ); ?></strong>
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
				__( 'Sends %s after', 'newsletter-opti-box' ),
				(int) $campaign->get_sends_after() . ' ' . esc_html( $campaign->get_sends_after_unit( true ) )
			);

		} else {

			$about = __( 'Sends immediately', 'newsletter-opti-box' );
		}

		// Are we sending to new customers.
		$new_customer = $campaign->get( 'new_customer' );

		if ( ! empty( $new_customer ) ) {
			$about .= ' ' . __( "a first-time customer's order is", 'newsletter-opti-box' );
		} else {
			$about .= ' ' . __( "a customer's order is", 'newsletter-opti-box' );
		}

		// Prepare selected status.
		$about .= ' ' . '<em style="color: #607D8B;">' . strtolower( $this->get_campaign_order_status( $campaign, true ) ) . '</em>';

		return $about;

	}

	/**
	 * Notify customers when they make a new order.
	 *
	 * @param string $action The order action.
     * @param int $order_id The order being acted on.
     * @param int $subscriber_id The subscriber for the order.
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
			if ( $this->is_automation_valid_for( $automation, $order, $action, $woocommerce ) ) {
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

		// Ensure the order exists and the campaign is active.
		if ( empty( $order ) || ! $campaign->can_send() ) {
			return;
		}

		if ( empty( $key ) ) {
			$key = $order_id . '_' . $campaign_id;
		}

		// Send the email.
		$this->order   = $order;
		$this->sending = true;

		$this->register_merge_tags();

		foreach ( $this->get_recipients( $campaign, array() ) as $recipient => $track ) {

			$content = noptin_generate_automated_email_content( $campaign, $recipient, $track  );
			noptin_send_email(
				array(
					'recipients' => $recipient,
					'message'    => noptin_generate_automated_email_content( $campaign, $recipient, $track  ),
					
				)
			);

			// $disable_template_plugins = true;
			// $subject = '';
			// $headers = array();
			// $attachments = array();
			// $reply_to = '';
			// $from_email = '';
			// $from_name = '';
			// $content_type = '';
			// $unsubscribe_url = '';

		}

		$this->unregister_merge_tags();
	}

	/**
	 * Retrieves an array of supported merge tags.
	 *
	 * @return array
	 */
	public function get_merge_tags() {

		return array(
			__( 'Order', 'noptin' )    => $this->get_order_merge_tags(),
			__( 'Customer', 'noptin' ) => $this->get_customer_merge_tags()
		);

	}

	/**
	 * Order field value of the current order.
	 *
	 * @param array $args
	 * @param string $field
	 * @return string
	 */
	public function get_order_field( $args = array(), $field = 'first_name' ) {
		$default = isset( $args['default'] ) ? $args['default'] : '';

		// Abort if no subscriber.
		if ( empty( $this->subscriber ) || ! $this->subscriber->has_prop( $field ) ) {
			return esc_html( $default );
		}

		$all_fields = wp_list_pluck( get_noptin_custom_fields(), 'type', 'merge_tag' );

		// Format field value.
		if ( isset( $all_fields[ $field ] ) ) {

			$value = $this->subscriber->get( $field );
			if ( 'checkbox' == $all_fields[ $field ] ) {
				return ! empty( $value ) ? __( 'Yes', 'newsletter-optin-box' ) : __( 'No', 'newsletter-optin-box' );
			}

			$value = wp_kses_post(
				format_noptin_custom_field_value(
					$this->subscriber->get( $field ),
					$all_fields[ $field ],
					$this->subscriber
				)
			);

			if ( "&mdash;" !== $value ) {
				return $value;
			}
		}

		return esc_html( $default );
	}

}
