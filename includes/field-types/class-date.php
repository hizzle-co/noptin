<?php
/**
 * Handles dates.
 *
 * @since 1.0.0
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles dates.
 *
 * @since 1.5.5
 */
class Noptin_Custom_Field_Date extends Noptin_Custom_Field_Text {

	/**
	 * Retreives the input type.
	 *
	 * @since 1.5.5
	 * @return string
	 */
	public function get_input_type() {
		return 'date';
	}

	/**
	 * Sanitizes the submitted value.
	 *
	 * @since 1.5.5
	 * @param mixed $value Submitted value
	 * @param false|Noptin_Subscriber $subscriber
	 */
	public function sanitize_value( $value, $subscriber ) {
		return empty( $value ) ? '' : date( 'Y-m-d', strtotime( $value ) );
	}

	/**
	 * Formats a value for display.
	 *
	 * @since 1.5.5
	 * @param mixed $value Sanitized value
	 * @param Noptin_Subscriber $subscriber
	 */
	public function format_value( $value, $subscriber ) {

		$value = $this->sanitize_value( $value, $subscriber );

		if ( empty( $value ) ) {
			return "&mdash;";
		}

		return date_i18n( get_option( 'date_format' ), strtotime( $value ) );

	}

}
