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
	 * Displays the actual markup for this field.
	 *
	 * @since 1.5.5
	 * @param array $args Field args
	 * @param false|Noptin_Subscriber $subscriber
	 */
	public function output( $args, $subscriber ) {

		if ( empty( $args['options'] ) ) {
			$args['options'] = '';
		}

		?>

			<label class="noptin-label" for="<?php echo esc_attr( $args['id'] ); ?>"><?php echo empty( $args['vue'] ) ? wp_kses_post( $args['label'] ) : '{{field.type.label}}'; ?></label>

			<select
				name="<?php echo esc_attr( $args['name'] ); ?>"
				id="<?php echo esc_attr( $args['id'] ); ?>"
				class="noptin-text noptin-form-field"
				<?php echo empty( $args['required'] ) ? '' : 'required'; ?>
			>
				<option <?php selected( empty( $args['value'] ) ); ?> disabled><?php echo empty( $args['vue'] ) ? esc_html( wp_strip_all_tags( $args['label'] ) ) : '{{field.type.label}}'; ?></option>
				<?php foreach ( noptin_newslines_to_array( $args['options'] ) as $value => $label ) : ?>
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

}
