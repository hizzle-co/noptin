<?php
/**
 * Handles email.
 *
 * @since 1.0.0
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles emails.
 *
 * @since 1.5.5
 */
class Noptin_Custom_Field_Email extends Noptin_Custom_Field_Text {

	/**
	 * Retreives the input type.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_input_type() {
		return 'email';
	}

	/**
	 * Sanitizes the submitted value.
	 *
	 * @since 1.5.5
	 * @param mixed $value Submitted value
	 */
	public function sanitize_value( $value ) {
		return '' === $value ? '' : sanitize_email( $value );
	}

}
