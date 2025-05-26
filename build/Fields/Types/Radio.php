<?php
/**
 * Handles radio buttons.
 *
 * @since 1.0.0
 *
 */

namespace Hizzle\Noptin\Fields\Types;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles radio buttons.
 *
 * @since 1.5.5
 */
class Radio extends Dropdown {

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
