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
class Noptin_Custom_Field_Radio extends Noptin_Custom_Field_Type {

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

			<label class="noptin-label"><?php echo empty( $args['vue'] ) ? wp_kses_post( $args['label'] ) : '{{field.type.label}}'; ?></label>

			<?php foreach ( explode( "\n", $args['options'] ) as $option ) : ?>
				<label style="display: block; margin-bottom: 6px;">
					<input
						type="radio"
						value="<?php echo esc_attr( $option ); ?>"
						name="<?php echo esc_attr( $args['name'] ); ?>"
						<?php checked( esc_attr( $option ), esc_attr( $args['value'] ) ); ?>
						<?php echo empty( $args['required'] ) ? '' : 'required'; ?>
					/>
					<strong><?php echo esc_html( $option ); ?></strong>
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
