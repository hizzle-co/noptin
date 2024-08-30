<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

	/**
	 * Handles display of Vue Apps
	 *
	 * @since       1.0.8
	 */

class Noptin_Vue {

	/**
	 * registers action and filter hooks
	 */
	public static function init_hooks() {
		add_action( 'noptin_field_type_frontend_optin_markup', array( __CLASS__, 'print_frontend_markup' ), 10, 2 );
	}

	/**
	 * Renders a the frontend fields markup
	 */
	public static function print_frontend_markup( $field, $data ) {

		// Labels.
		$label = '';
		if ( ! empty( $field['type']['label'] ) ) {
			$label = esc_attr( $field['type']['label'] );
		}

		// Required fields.
		$required = '';
		if ( ! empty( $field['require'] ) && 'false' !== $field['require'] ) {
			$required = 'required';
		}

		// Text fields.
		if ( in_array( $field['type']['type'], array( 'name', 'text' ), true ) ) {
			printf(
				'<input name="%s" type="text" class="noptin-form-field" placeholder="%s" %s/>',
				esc_attr( $field['key'] ),
				esc_attr( $label ),
				esc_attr( $required )
			);
		}

		// Hidden.
		if ( 'hidden' === $field['type']['type'] ) {
			printf(
				'<input name="%s" type="hidden" value="%s"/>',
				esc_attr( $field['key'] ),
				esc_attr( $field['type']['value'] )
			);
		}

		// Checkbox.
		if ( 'checkbox' === $field['type']['type'] ) {
			printf(
				'<label><input name="%s" type="checkbox" value="1" class="noptin-checkbox-form-field" %s/><span>%s</span></label>',
				esc_attr( $field['key'] ),
				esc_attr( $field['type']['value'] ),
				esc_attr( $required ),
				wp_kses_post( $label )
			);
		}

		// Textarea.
		if ( 'textarea' === $field['type']['type'] ) {
			printf(
				'<textarea name="%s" class="noptin-checkbox-form-field" placeholder="%s" %s></textarea>',
				esc_attr( $field['key'] ),
				esc_attr( $label ),
				esc_attr( $required )
			);
		}

		// Select.
		if ( 'dropdown' === $field['type']['type'] ) {

			printf(
				'<select name="%s" class="noptin-form-field" %s>',
				esc_attr( $field['key'] ),
				esc_attr( $required )
			);

			printf(
				'<option value="" selected="selected">%s</option>',
				esc_html( $label )
			);

			foreach ( explode( ',', $field['type']['options'] ) as $option ) {

				if ( empty( $option ) ) {
					continue;
				}

				$option = explode( '|', $option );

				printf(
					'<option value="%s">%s</option>',
					esc_attr( isset( $option[1] ) ? $option[1] : $option[0] ),
					esc_html( $option[0] )
				);
			}

			echo '</select>';
		}
	}
}
