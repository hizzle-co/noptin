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
	 * Sanitizes the submitted value.
	 *
	 * @since 1.5.5
	 * @param mixed $value Submitted value
	 * @param false|Noptin_Subscriber $subscriber
	 */
	public function sanitize_value( $value, $subscriber ) {
		return empty( $value ) ? 0 : 1;
	}

	/**
	 * Formats a value for display.
	 *
	 * @since 1.5.5
	 * @param mixed $value Sanitized value
	 * @param Noptin_Subscriber $subscriber
	 */
	public function format_value( $value, $subscriber ) {

		$value = $this->sanitize_value( $value, $subscriber );

		if ( ! empty( $value ) ) {
			return '<span class="dashicons dashicons-yes" style="color: green"></span>';
		}

		return '<span class="dashicons dashicons-no" style="color: red"></span>';

	}

}
