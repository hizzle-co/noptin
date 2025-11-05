<?php
/**
 * Handles date and times.
 *
 * @since 1.0.0
 *
 */

namespace Hizzle\Noptin\Fields\Types;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles dates.
 *
 * @since 1.5.5
 */
class Date_Time extends Text {

	/**
	 * Retreives the input type.
	 *
	 * @since 1.5.5
	 * @return string
	 */
	public function get_input_type() {
		return 'datetime-local';
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
				'type' => 'DATETIME',
			)
		);

		// Remove the length.
		unset( $schema[ $column ]['length'] );

		return $schema;
	}

	/**
	 * @inheritdoc
	 */
	public function output( $args ) {

		if ( ( $args['value'] ?? '' ) instanceof \DateTime ) {
			$args['value'] = gmdate( 'Y-m-d\TH:i', $args['value']->getTimestamp() + $args['value']->getOffset() );
		}

		parent::output( $args );
	}
}
