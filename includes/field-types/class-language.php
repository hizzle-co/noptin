<?php

/**
 * Handles language dropdowns.
 *
 * @since 1.0.0
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles language dropdowns.
 *
 * @since 1.8.0
 */
class Noptin_Custom_Field_Language extends Noptin_Custom_Field_Dropdown {

	/**
	 * Retrieves the list of available languages.
	 *
	 * @since 1.8.0
	 * @return array
	 */
	public function get_languages() {
		$languages = noptin_get_available_languages();
		return is_array( $languages ) ? $languages : array();
	}

	/**
	 * Fetches available field options.
	 *
	 * @since 1.13.0
	 * @param array $custom_field
	 * @return array
	 */
	public function get_field_options( $custom_field ) {
		return $this->get_languages();
	}

	/**
	 * Displays the actual markup for this field.
	 *
	 * @since 1.8.0
	 * @param array $args Field args
	 * @param false|Noptin_Subscriber $subscriber
	 */
	public function output( $args, $subscriber ) {
		$args['options'] = $this->get_languages();
		parent::output( $args, $subscriber );
	}

	/**
	 * Sanitizes the submitted value.
	 *
	 * @since 1.8.0
	 * @param mixed $value Submitted value
	 * @param false|Noptin_Subscriber $subscriber
	 */
	public function sanitize_value( $value, $subscriber ) {
		return array_key_exists( $value, $this->get_languages() ) ? $value : get_locale();
	}

	/**
	 * Formats a value for display.
	 *
	 * @since 1.8.0
	 * @param mixed $value Sanitized value
	 * @param Noptin_Subscriber $subscriber
	 */
	public function format_value( $value, $subscriber ) {

		$languages = $this->get_languages();
		if ( $value && array_key_exists( $value, $languages ) ) {
			return esc_html( $languages[ $value ] );
		}

		return esc_html__( 'Not Set', 'newsletter-optin-box' );
	}

}
