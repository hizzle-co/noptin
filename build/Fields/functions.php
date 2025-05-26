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
 * @see Noptin_Custom_Fields::get_custom_field_types
 * @return array
 */
function get_noptin_custom_field_types() {

	$field_types = apply_filters(
		'noptin_custom_field_types',		array(
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
