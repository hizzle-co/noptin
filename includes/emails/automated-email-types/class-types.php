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
	public function register_automated_email_type( $type, $data ) {

		// Register the automated email type.
		$this->types[ $type ] = $data;
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
			'periodic'           => '\Hizzle\Noptin\Emails\Types\Recurring',
			'post_notifications' => 'Noptin_New_Post_Notification',
			'post_digest'        => 'Noptin_Post_Digest',
		);

		// Filter and return.
		return apply_filters( 'noptin_automated_email_types', $known_types );
	}
}
