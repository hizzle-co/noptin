<?php
/**
 * Handles radio buttons.
 *
 * @since 1.0.0
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles radio buttons.
 *
 * @since 1.5.5
 */
class Noptin_Custom_Field_Radio extends Noptin_Custom_Field_Dropdown {

	/**
	 * Displays the actual markup for this field.
	 *
	 * @since 1.5.5
	 * @param array $args Field args
	 * @param false|Noptin_Subscriber $subscriber
	 */
	public function output( $args, $subscriber ) {

		?>

			<label class="noptin-label"><?php echo empty( $args['vue'] ) ? wp_kses_post( $args['label'] ) : '{{field.type.label}}'; ?></label>

			<?php foreach ( $this->get_field_options( $args ) as $value => $label ) : ?>
				<label style="display: block; margin-bottom: 6px;">
					<input
						type="radio"
						value="<?php echo esc_attr( $value ); ?>"
						name="<?php echo esc_attr( $args['name'] ); ?>"
						<?php checked( esc_attr( $value ), esc_attr( $args['value'] ) ); ?>
						<?php echo empty( $args['required'] ) ? '' : 'required'; ?>
					/>
					<strong><?php echo esc_html( $label ); ?></strong>
				</label>
			<?php endforeach; ?>

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
