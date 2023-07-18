<?php
/**
 * Handles checkboxes.
 *
 * @since 1.0.0
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles checkboxes.
 *
 * @since 1.5.5
 */
class Noptin_Custom_Field_Checkbox extends Noptin_Custom_Field_Type {

	/**
	 * Displays the actual markup for this field.
	 *
	 * @since 1.5.5
	 * @param array $args Field args
	 * @param false|Noptin_Subscriber $subscriber
	 */
	public function output( $args, $subscriber ) {

		?>

			<label>
				<input
					name="<?php echo esc_attr( $args['name'] ); ?>"
					id="<?php echo esc_attr( $args['id'] ); ?>"
					type='checkbox'
					value='1'
					class='noptin-checkbox-form-field'
					<?php echo empty( $args['required'] ) ? '' : 'required'; ?>
					<?php checked( ! empty( $args['value'] ) ); ?>
				/><span><?php echo empty( $args['vue'] ) ? wp_kses_post( $args['label'] ) : '{{field.type.label}}'; ?></span>
			</label>

		<?php

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
				'type'     => 'TINYINT',
				'length'   => 1,
				'nullable' => false,
				'default'  => isset( $custom_field['default'] ) ? $custom_field['default'] : 0,
			)
		);

		if ( ! empty( $custom_field['default_value'] ) && 'no' !== $custom_field['default_value'] ) {
			$schema[ $column ]['default'] = 1;
		} else {
			$schema[ $column ]['default'] = 0;
		}

		return $schema;
	}
}
