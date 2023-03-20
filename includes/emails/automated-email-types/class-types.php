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
	 * Registers an automated email type.
	 */
	public function register_automated_email_type( $type, $object ) {

		// Register the automated email type.
		$this->types[ $type ] = $object;

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
			$this->types[ $type ] = new $class( $type );
		}

	}

	/**
	 * Returns an array of known automated email types.
	 *
	 * @return array
	 */
	protected function get_automated_email_types() {

		// Prepare an array of key and class.
		$known_types = array(
			'post_notifications' => 'Noptin_New_Post_Notification',
			'post_digest'        => 'Noptin_Post_Digest',
		);

		// Check if WC exists.
		if ( class_exists( 'WooCommerce' ) ) {
			$known_types['woocommerce_new_order']        = 'Noptin_WooCommerce_New_Order_Email';
			$known_types['woocommerce_product_purchase'] = 'Noptin_WooCommerce_Product_Purchase_Email';
			$known_types['woocommerce_lifetime_value']   = 'Noptin_WooCommerce_Lifetime_Value_Email';
			require_once plugin_dir_path( __FILE__ ) . 'class-type-woocommerce.php';
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

		// Abort if there is no email type.
		if ( empty( $data['automation_type'] ) || empty( $this->types[ $data['automation_type'] ] ) ) {
			wp_send_json_error( __( 'Unsupported automation type.', 'newsletter-optin-box' ) );
		}

		// Prepare automated email.
		$email = new Noptin_Automated_Email( $data );

		// Ensure we have a subject.
		$subject = $email->get_subject();
		if ( empty( $subject ) ) {
			wp_send_json_error( __( 'You need to provide a subject for your email.', 'newsletter-optin-box' ) );
		}

		// Ensure we have content.
		$content = $email->get_content( $email->get_email_type() );
		if ( empty( $content ) ) {
			wp_send_json_error( __( 'The email body cannot be empty.', 'newsletter-optin-box' ) );
		}

		// Try sending the test email.
		try {
			$result = $this->types[ $email->type ]->send_test( $email, $recipient );
		} catch ( Exception $e ) {
			$result = new WP_Error( 'exception', $e->getMessage() );
		}

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

	/**
	 * Generates a preview email.
	 *
	 * @param Noptin_Automated_Email $email
	 * @return string
	 */
	public function generate_preview( $email ) {

		// Abort if there is no email type.
		if ( empty( $email->type ) || empty( $this->types[ $email->type ] ) ) {
			return __( 'Unsupported automation type.', 'newsletter-optin-box' );
		}

		// Try generating the preview email.
		try {
			return $this->types[ $email->type ]->generate_preview( $email );
		} catch ( Exception $e ) {
			return $e->getMessage();
		}

	}

}
