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
	 * Displays the single select field.
	 *
	 * @since 2.0.0
	 * @param array $args Field args
	 */
	protected function display_single( $args ) {

		?>

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
}
