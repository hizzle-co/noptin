<?php
/**
 * Handles checkboxes.
 *
 * @since 1.0.0
 *
 */

namespace Hizzle\Noptin\Fields\Types;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles checkboxes.
 *
 * @since 1.5.5
 */
class Checkbox extends Base {

	/**
	 * @inheritdoc
	 */
	public function output( $args ) {

		?>
			<input type="hidden" name="<?php echo esc_attr( $args['name'] ); ?>" value="0" />
			<label>
				<input
					name="<?php echo esc_attr( $args['name'] ); ?>"
					id="<?php echo esc_attr( $args['id'] ); ?>"
					type='checkbox'
					value='1'
					class='noptin-checkbox-form-field'
					<?php echo empty( $args['required'] ) ? '' : 'required'; ?>
					<?php checked( ! empty( $args['value'] ) ); ?>
				/><span><?php echo empty( $args['react'] ) ? wp_kses_post( $args['label'] ) : '{{field.type.label}}'; ?></span>
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
