<?php
/**
 * Handles numbers.
 *
 * @since 1.0.0
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles numbers.
 *
 * @since 1.5.5
 */
class Noptin_Custom_Field_Number extends Noptin_Custom_Field_Text {

	/**
	 * Retreives the input type.
	 *
	 * @since 1.5.5
	 * @return string
	 */
	public function get_input_type() {
		return 'number';
	}

	/**
	 * Filters the database schema.
	 *
	 * @since 1.13.0
	 * @param array $schema
	 * @param array $field
	 */
	public function filter_db_schema( $schema, $custom_field ) {
		$schema[ $this->get_column_name( $custom_field ) ] = array(
			'type'        => 'DECIMAL',
			'length'      => '26,8',
			'label'       => wp_strip_all_tags( $custom_field['label'] ),
			'description' => wp_strip_all_tags( $custom_field['label'] ),
		);

		return $schema;
	}
}
