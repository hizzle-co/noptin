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
	 * Filters the database schema.
	 *
	 * @since 2.0.0
	 * @param array $schema
	 * @param array $field
	 */
	public function filter_db_schema( $schema, $custom_field ) {
		$schema = parent::filter_db_schema( $schema, $custom_field );
		$column = $this->get_column_name( $custom_field );

		$schema[ $column ] = array_merge(
			$schema[ $column ],
			array(
				'type' => 'DATE',
			)
		);

		// Remove the length.
		unset( $schema[ $column ]['length'] );

		return $schema;
	}
}
