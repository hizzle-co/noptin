<?php

/**
 * Handles dropdowns.
 *
 * @since 1.0.0
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles dropdowns.
 *
 * @since 1.5.5
 */
class Noptin_Custom_Field_Dropdown extends Noptin_Custom_Field_Type {

	/**
	 * Whether or not it supports storing values in subscribers table.
	 *
	 * @var bool
	 */
	public $store_in_subscribers_table = true;

	/**
	 * Fetches available field options.
	 *
	 * @since 1.13.0
	 * @param array $custom_field
	 * @return array
	 */
	public function get_field_options( $custom_field ) {
		$options = array();

		if ( ! empty( $custom_field['options'] ) ) {
			$options = noptin_newslines_to_array( $custom_field['options'] );
		}

		return $options;
	}

	/**
	 * Displays the actual markup for this field.
	 *
	 * @since 1.5.5
	 * @param array $args Field args
	 * @param false|Noptin_Subscriber $subscriber
	 */
	public function output( $args, $subscriber ) {

		?>

			<label class="noptin-label" for="<?php echo esc_attr( $args['id'] ); ?>"><?php echo empty( $args['vue'] ) ? wp_kses_post( $args['label'] ) : '{{field.type.label}}'; ?></label>

			<select
				name="<?php echo esc_attr( $args['name'] ); ?>"
				id="<?php echo esc_attr( $args['id'] ); ?>"
				class="noptin-text noptin-form-field"
				<?php echo empty( $args['required'] ) ? '' : 'required'; ?>
			>
				<option <?php selected( empty( $args['value'] ) ); ?> disabled><?php echo empty( $args['vue'] ) ? esc_html( wp_strip_all_tags( $args['label'] ) ) : '{{field.type.label}}'; ?></option>
				<?php foreach ( $this->get_field_options( $args ) as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( esc_attr( $value ), esc_attr( $args['value'] ) ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

		<?php

	}

	/**
	 * Sanitizes the submitted value.
	 *
	 * @since 1.5.5
	 * @param mixed $value Submitted value
	 * @param false|Noptin_Subscriber $subscriber
	 */
	public function sanitize_value( $value, $subscriber ) {
		return sanitize_text_field( $value );
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
			'type'        => 'VARCHAR',
			'length'      => 255,
			'description' => wp_strip_all_tags( $custom_field['label'] ),
		);

		$available_options = $this->get_field_options( $custom_field );

		if ( ! empty( $available_options ) ) {
			$max_length = 0;

			foreach ( array_keys( $available_options ) as $option ) {
				$option_length = strlen( (string) $option );

				if ( $option_length > $max_length ) {
					$max_length = $option_length;
				}
			}

			$schema[ $this->get_column_name( $custom_field ) ]['length'] = $max_length + 1;
			$schema[ $this->get_column_name( $custom_field ) ]['enum']   = $available_options;
		}

		return $schema;
	}
}
