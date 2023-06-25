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
	 * Whether or not this field type supports multiple values.
	 *
	 * @var bool
	 */
	protected $is_multiple = false;

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

		printf(
			'<label class="noptin-label" for="%s">%s</label>',
			esc_attr( $args['id'] ),
			empty( $args['vue'] ) ? wp_kses_post( $args['label'] ) : '{{field.type.label}}'
		);

		if ( ! empty( $this->is_multiple ) ) {
			$this->display_multiple( $args );
		} else {
			$this->display_single( $args );
		}
	}

	/**
	 * Displays the single select field.
	 *
	 * @since 1.13.0
	 * @param array $args Field args
	 */
	protected function display_single( $args ) {

		?>

		<select
			name="<?php echo esc_attr( $args['name'] ); ?>"
			id="<?php echo esc_attr( $args['id'] ); ?>"
			class="noptin-text noptin-form-field"
			<?php echo empty( $args['required'] ) ? '' : 'required'; ?>
		>
			<option <?php selected( empty( $args['value'] ) ); ?> disabled><?php echo empty( $args['vue'] ) ? esc_html( wp_strip_all_tags( $args['label'] ) ) : '{{field.type.label}}'; ?></option>
			<?php foreach ( $this->get_field_options( $args ) as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( esc_attr( $value ), esc_attr( $args['value'] ) ); ?>><?php echo esc_html( wp_strip_all_tags( $label ) ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php

	}

	/**
	 * Displays the multiple select field.
	 *
	 * @since 1.13.0
	 * @param array $args Field args
	 */
	protected function display_multiple( $args ) {

		?>

			<?php foreach ( $this->get_field_options( $args ) as $value => $label ) : ?>
				<label style="display: block; margin-bottom: 6px;">
					<input
						name="<?php echo esc_attr( $args['name'] ); ?>[]"
						type="checkbox"
						value="<?php echo esc_attr( $value ); ?>"
						class='noptin-checkbox-form-field'
						<?php checked( is_array( $args['value'] ) && in_array( esc_attr( $value ), $args['value'], true ) ); ?>
					/>
					<span><?php echo wp_kses_post( $label ); ?></span>
				</label>
			<?php endforeach; ?>
		<?php

	}

	/**
	 * Filters the database schema.
	 *
	 * @since 1.13.0
	 * @param array $schema
	 * @param array $field
	 */
	public function filter_db_schema( $schema, $custom_field ) {
		$field_schema = array(
			'type'              => 'VARCHAR',
			'label'             => wp_strip_all_tags( $custom_field['label'] ),
			'description'       => wp_strip_all_tags( $custom_field['label'] ),
			'sanitize_callback' => 'noptin_clean',
		);

		if ( is_callable( array( $this, 'sanitize_value' ) ) ) {
			$field_schema['sanitize_callback'] = array( $this, 'sanitize_value' );
		}

		if ( $this->is_multiple ) {
			$field_schema['is_meta_key']          = true;
			$field_schema['is_meta_key_multiple'] = true;
		} else {
			$field_schema['length'] = 255;
		}

		$available_options = $this->get_field_options( $custom_field );

		if ( ! empty( $available_options ) ) {
			$max_length = 0;

			foreach ( array_keys( $available_options ) as $option ) {
				$option_length = strlen( (string) $option );

				if ( $option_length > $max_length ) {
					$max_length = $option_length;
				}
			}

			if ( ! $this->is_multiple ) {
				$field_schema['length'] = $max_length + 1;
			}

			$field_schema['enum']   = $available_options;
		}

		$schema[ $this->get_column_name( $custom_field ) ] = $field_schema;

		return $schema;
	}
}
