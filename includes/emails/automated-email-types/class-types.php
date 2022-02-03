<?php
/**
 * Emails API: Automated Email Types.
 *
 * Container for automated email types.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Container for automated email types.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_Automated_Email_Types {

	/**
	 * @var Noptin_Automated_Email_Type[]
	 */
	public $types;

	/**
	 * Registers relevant hooks.
	 *
	 */
	public function add_hooks() {

		// Register automated email types.
		$this->register_automated_email_types();

		// Init each email type separately.
		foreach ( $this->types as $type ) {
			$type->add_hooks();
		}

	}

	/**
	 * Registers known automated email types.
	 *
	 */
	protected function register_automated_email_types() {

		// Loop through each class.
		foreach ( $this->get_automated_email_types() as $type => $class ) {

			// If the class does not exist, try loading it.
			if ( ! class_exists( $class ) ) {

				if ( file_exists( plugin_dir_path( __FILE__ ) . "class-$type.php" ) ) {
					require_once plugin_dir_path( __FILE__ ) . "class-$type.php";
				} else {
					continue;
				}

			}

			// Register the automated email type.
			$this->types[ $type ] = new $class;
		}

	}

	/**
	 * Returns an array of known automated email types.
	 *
	 * @return array
	 */
	protected function get_automated_email_types() {

		require_once plugin_dir_path( __FILE__ ) . 'class-type.php';

		// Prepare an array of key and class.
		$known_types = array(
			'post_notifications'           => 'Noptin_New_Post_Notification',
			'post_digest'                  => 'Noptin_Post_Digest',
			'woocommerce_new_order'        => 'Noptin_WooCommerce_New_Order_Email',
			'woocommerce_product_purchase' => 'Noptin_WooCommerce_Product_Purchase_Email',
			'woocommerce_lifetime_value'   => 'Noptin_WooCommerce_Lifetime_Value_Email',
		);

		// Ensure WooCommerce exists.
		if ( ! class_exists( 'WooCommerce' ) ) {
			unset( $known_types['woocommerce_new_order'], $known_types['woocommerce_product_purchase'], $known_types['woocommerce_lifetime_value'] );
		}

		// Filter and return.
		return apply_filters( 'noptin_automated_email_types', $known_types );

	}

	/**
	 * Generates then sends a test email.
	 *
	 * @param array $data
	 * @param string $recipient
	 */
	public function send_test_email( $data, $recipient ) {

		// Abort if there is not email type.
		if ( empty( $data['automation_type'] ) || empty( $this->types[ $data['automation_type'] ] ) ) {
			wp_send_json_error( __( 'Unsupported automation type.', 'newsletter-optin-box' ) );
		}

		// Prepare automated email.
		$email = new Noptin_Automated_Email( $data );

		// Send test email.
		$result = $email->send_test( $recipient );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Successfuly sent the email.
		if ( $result ) {
			wp_send_json_success( __( 'Your test email has been sent', 'newsletter-optin-box' ) );
		}

		// Failed sending the email.
		wp_send_json_error( __( 'Could not send the test email', 'newsletter-optin-box' ) );

	}

}
