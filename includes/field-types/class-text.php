<?php
/**
 * Handles text inputs.
 *
 * @since 1.0.0
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles text inputs.
 *
 * @since 1.5.5
 */
class Noptin_Custom_Field_Text extends Noptin_Custom_Field_Type {

	/**
	 * Retreives the input type.
	 *
	 * @since 1.5.5
	 * @return string
	 */
	public function get_input_type() {
		return 'text';
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
			<input
				name="<?php echo esc_attr( $args['name'] ); ?>"
				id="<?php echo esc_attr( $args['id'] ); ?>"
				type="<?php echo esc_attr( $this->get_input_type() ); ?>"
				value="<?php echo esc_attr( $args['value'] ); ?>"
				class="noptin-text noptin-form-field"
				<?php if ( empty( $args['vue'] ) ) : ?>
					placeholder="<?php echo esc_attr( $args['label'] ); ?>"
				<?php else: ?>
					:placeholder="field.type.label"
				<?php endif;?>
				<?php echo empty( $args['required'] ) ? '' : 'required'; ?>
			/>

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
