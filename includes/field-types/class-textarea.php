<?php
/**
 * Handles textarea inputs.
 *
 * @since 1.0.0
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles textarea inputs.
 *
 * @since 1.5.5
 */
class Noptin_Custom_Field_Textarea extends Noptin_Custom_Field_Type {

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
			<textarea
				name="<?php echo esc_attr( $args['name'] ); ?>"
				id="<?php echo esc_attr( $args['id'] ); ?>"
				class="noptin-text noptin-form-field"
				rows="4"
				<?php if ( empty( $args['vue'] ) ) : ?>
					placeholder="<?php echo esc_attr( $args['label'] ); ?>"
				<?php else: ?>
					:placeholder="field.type.label"
				<?php endif;?>
			><?php echo esc_textarea( $args['value'] ); ?></textarea>
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
		return sanitize_textarea_field( $value );
	}

}
