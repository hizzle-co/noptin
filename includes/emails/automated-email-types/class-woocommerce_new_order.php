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
class Noptin_WooCommerce_New_Order_Email extends Noptin_Automated_Email_Type {

	/**
	 * @var string
	 */
	public $type = 'woocommerce_new_order';

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

		// Fetch order statuses.
		$statuses = array(
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

		// Prepare selected status.
		$status = $campaign->get( 'order_status' );

		if ( empty( $status ) || ! isset( $statuses[ $status ] ) ) {
			$status = 'paid';
		}

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

}
