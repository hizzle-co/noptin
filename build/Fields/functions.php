<?php
/**
 * Fields API: Field functions
 *
 * Contains functions for manipulating Noptin fields
 *
 * @since             1.6.0
 * @package           Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns an array of available custom field types.
 *
 * @since 1.5.5
 * @see \Hizzle\Noptin\Fields\Main::get_custom_field_types
 * @return array
 */
function get_noptin_custom_field_types() {

	$field_types = apply_filters(
		'noptin_custom_field_types',
		array(
			'email'          => array(
				'predefined' => true,
				'merge_tag'  => 'email',
				'label'      => __( 'Email Address', 'newsletter-optin-box' ),
				'class'      => '\\Hizzle\\Noptin\\Fields\\Types\\Email',
			),
			'first_name'     => array(
				'predefined' => true,
				'merge_tag'  => 'first_name',
				'label'      => __( 'First Name', 'newsletter-optin-box' ),
				'class'      => '\\Hizzle\\Noptin\\Fields\\Types\\Text',
			),
			'last_name'      => array(
				'predefined' => true,
				'merge_tag'  => 'last_name',
				'label'      => __( 'Last Name', 'newsletter-optin-box' ),
				'class'      => '\\Hizzle\\Noptin\\Fields\\Types\\Text',
			),
			'language'       => array(
				'predefined' => true,
				'merge_tag'  => 'language',
				'label'      => __( 'Language', 'newsletter-optin-box' ),
				'class'      => '\\Hizzle\\Noptin\\Fields\\Types\\Language',
			),
			'text'           => array(
				'predefined' => false,
				'label'      => __( 'Text Input', 'newsletter-optin-box' ),
				'class'      => '\\Hizzle\\Noptin\\Fields\\Types\\Text',
			),
			'textarea'       => array(
				'predefined' => false,
				'label'      => __( 'Textarea Input', 'newsletter-optin-box' ),
				'class'      => '\\Hizzle\\Noptin\\Fields\\Types\\Textarea',
			),
			'number'         => array(
				'predefined' => false,
				'label'      => __( 'Number Input', 'newsletter-optin-box' ),
				'class'      => '\\Hizzle\\Noptin\\Fields\\Types\\Number',
			),
			'radio'          => array(
				'predefined'       => false,
				'supports_options' => true,
				'label'            => __( 'Radio Buttons', 'newsletter-optin-box' ),
				'class'            => '\\Hizzle\\Noptin\\Fields\\Types\\Radio',
			),
			'dropdown'       => array(
				'predefined'       => false,
				'supports_options' => true,
				'label'            => __( 'Dropdown', 'newsletter-optin-box' ),
				'class'            => '\\Hizzle\\Noptin\\Fields\\Types\\Dropdown',
			),
			'date'           => array(
				'predefined' => false,
				'label'      => __( 'Date', 'newsletter-optin-box' ),
				'class'      => '\\Hizzle\\Noptin\\Fields\\Types\\Date',
			),
			'date_time'      => array(
				'predefined' => false,
				'label'      => __( 'Date & Time', 'newsletter-optin-box' ),
				'class'      => '\\Hizzle\\Noptin\\Fields\\Types\\Date_Time',
			),
			'checkbox'       => array(
				'predefined' => false,
				'label'      => __( 'Checkbox', 'newsletter-optin-box' ),
				'class'      => '\\Hizzle\\Noptin\\Fields\\Types\\Checkbox',
			),
			'multi_checkbox' => array(
				'predefined'       => false,
				'supports_options' => true,
				'label'            => __( 'Multiple checkboxes', 'newsletter-optin-box' ),
				'class'            => '\\Hizzle\\Noptin\\Fields\\Types\\MultiCheckbox',
			),
		)
	);

	if ( ! noptin_is_multilingual() && isset( $field_types['language'] ) ) {
		unset( $field_types['language'] );
	}

	return $field_types;
}

/**
 * Returns an array of predefined custom field keys.
 *
 * @since 1.6.0
 * @return array
 */
function get_noptin_predefined_custom_fields() {
	$field_types       = get_noptin_custom_field_types();
	$predefined_fields = array();

	foreach ( $field_types as $key => $field ) {
		if ( ! empty( $field['predefined'] ) ) {
			$predefined_fields[] = $key;
		}
	}

	return $predefined_fields;
}

/**
 * Returns an array of custom field keys that support options.
 *
 * @since 1.6.0
 * @return array
 */
function get_noptin_option_supported_fields() {
	$field_types   = get_noptin_custom_field_types();
	$option_fields = array();

	foreach ( $field_types as $key => $field ) {
		if ( ! empty( $field['supports_options'] ) ) {
			$option_fields[] = $key;
		}
	}

	return $option_fields;
}

/**
 * Displays a custom field input.
 *
 * @since 1.5.5
 * @see Noptin_Custom_Field_Type::output
 * @param array $custom_field
 * @param false|\Hizzle\Noptin\Subscribers\Subscriber $subscriber
 */
function display_noptin_custom_field_input( $custom_field, $subscriber = false ) {
	$custom_field['name'] = empty( $custom_field['wrap_name'] ) ? $custom_field['merge_tag'] : 'noptin_fields[' . $custom_field['merge_tag'] . ']';

	if ( ! isset( $custom_field['value'] ) ) {
		$custom_field['value'] = '';
	}

	if ( empty( $custom_field['id'] ) ) {
		$custom_field['id'] = empty( $custom_field['show_id'] ) ? uniqid( sanitize_html_class( $custom_field['merge_tag'] ) . '_' ) : 'noptin_field_' . sanitize_html_class( $custom_field['merge_tag'] );
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( ( '' === $custom_field['value'] || array() === $custom_field['value'] ) && ! empty( $_POST ) ) {

		// Below is cleaned on output.
		if ( isset( $_POST['noptin_fields'][ $custom_field['merge_tag'] ] ) ) {
			$custom_field['value'] = $_POST['noptin_fields'][ $custom_field['merge_tag'] ];
		} elseif ( isset( $_POST[ $custom_field['merge_tag'] ] ) ) {
			$custom_field['value'] = $_POST[ $custom_field['merge_tag'] ];
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	do_action( 'noptin_display_custom_field_input', $custom_field, $subscriber );
	do_action( "noptin_display_{$custom_field['type']}_input", $custom_field, $subscriber );
}

/**
 * Converts a custom field to schema.
 *
 * @param array $custom_field
 * @since 2.0.0
 * @return array
 */
function noptin_convert_custom_field_to_schema( $custom_field ) {
	$field_type = $custom_field['type'];
	return apply_filters( "noptin_filter_{$field_type}_schema", array(), $custom_field );
}
