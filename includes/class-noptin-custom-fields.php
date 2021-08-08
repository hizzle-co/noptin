<?php

/**
 * This class handles the display and management of custom fields.
 *
 * @since 1.2.9
 */
class Noptin_Custom_Fields {

	/**
	 * Returns the default subscriber fields.
	 *
	 * @return array $fields
	 */
	public static function default_fields() {

		return array(
			array(
				'type'       => 'email',
				'merge_tag'  => 'email',
				'label'      => __( 'Email Address', 'newsletter-optin-box' ),
				'visible'    => true,
				'subs_table' => true,
				'predefined' => true,
			),
			array(
				'type'       => 'first_name',
				'merge_tag'  => 'first_name',
				'label'      => __( 'First Name', 'newsletter-optin-box' ),
				'visible'    => true,
				'subs_table' => true,
				'predefined' => true,
			),
			array(
				'type'       => 'last_name',
				'merge_tag'  => 'last_name',
				'label'      => __( 'Last Name', 'newsletter-optin-box' ),
				'visible'    => true,
				'subs_table' => true,
				'predefined' => true,
			),
			array(
				'type'       => 'birthday',
				'merge_tag'  => 'birthday',
				'label'      => __( 'Birthday', 'newsletter-optin-box' ),
				'visible'    => true,
				'subs_table' => false,
				'predefined' => true,
			)
		);

	}

	/**
	 * Displays a custom field.
	 *
	 * @param string $custom_field
	 * @param false|Noptin_Subscriber
	 */
	public static function display_field( $custom_field, $subscriber ) {

		$custom_field['name']  = empty( $custom_field['wrap_name'] ) ? $custom_field['merge_tag'] : 'noptin_fields[' . $custom_field['merge_tag'] . ']';
		$custom_field['id']    = empty( $custom_field['show_id'] ) ? uniqid( sanitize_html_class( $custom_field['merge_tag'] ) ) : 'noptin_field_' . sanitize_html_class( $custom_field['merge_tag'] );
		$custom_field['value'] = empty( $subscriber ) ? '' : $subscriber->get( $custom_field['merge_tag'] );

		?>

		<p>

			<?php if ( 'email' === $custom_field['type'] ) : ?>
				<label class="noptin-label" for="<?php echo esc_attr( $custom_field['id'] ); ?>"><?php echo esc_attr( $custom_field['label'] ); ?></label>
				<input type="email" class="noptin-text" name="<?php echo esc_attr( $custom_field['name'] ); ?>" id="<?php echo esc_attr( $custom_field['id'] ); ?>" value="<?php echo esc_attr( $custom_field['value'] ); ?>" required>
			<?php endif; ?>

			<?php if ( 'first_name' === $custom_field['type'] || 'last_name' === $custom_field['type'] ) : ?>
				<label class="noptin-label" for="<?php echo esc_attr( $custom_field['id'] ); ?>"><?php echo esc_attr( $custom_field['label'] ); ?></label>
				<input type="text" class="noptin-text" name="<?php echo esc_attr( $custom_field['name'] ); ?>" id="<?php echo esc_attr( $custom_field['id'] ); ?>" value="<?php echo esc_attr( $custom_field['value'] ); ?>">
			<?php endif; ?>

			<?php if ( in_array( $custom_field['type'], array( 'text', 'number', 'date' ) ) ) : ?>
				<label class="noptin-label" for="<?php echo esc_attr( $custom_field['id'] ); ?>"><?php echo esc_attr( $custom_field['label'] ); ?></label>
				<input type="<?php echo esc_attr( $custom_field['type'] ); ?>" class="noptin-text" name="<?php echo esc_attr( $custom_field['name'] ); ?>" id="<?php echo esc_attr( $custom_field['id'] ); ?>" value="<?php echo esc_attr( $custom_field['value'] ); ?>">
			<?php endif; ?>

			<?php if ( 'checkbox' === $custom_field['type'] ) : ?>				
				<label class="noptin-checkbox-label">
					<input type="checkbox" value="1" name="<?php echo esc_attr( $custom_field['name'] ); ?>" id="<?php echo esc_attr( $custom_field['id'] ); ?>" <?php checked( '1', $custom_field['value'] ); ?> >
					<strong><?php echo esc_html( $custom_field['label'] ); ?></strong>
				</label>
			<?php endif; ?>

			<?php if ( 'radio' === $custom_field['type'] && ! empty( $custom_field['options'] ) ) : ?>
				<div class="noptin-radio-wrapper">
					<label class="noptin-label"><?php echo esc_html( $custom_field['label'] ); ?></label>
					<?php foreach ( explode( "\n", $custom_field['options'] ) as $option ) : ?>
						<label style="display: block; margin-bottom: 6px;">
							<input type="radio" value="<?php echo esc_attr( $option ); ?>" name="<?php echo esc_attr( $custom_field['name'] ); ?>" <?php checked( esc_attr( $option ), $custom_field['value'] ); ?> >
							<span><?php echo esc_html( $option ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( 'dropdown' === $custom_field['type'] && ! empty( $custom_field['options'] ) ) : ?>
				<label class="noptin-label" for="<?php echo esc_attr( $custom_field['id'] ); ?>"><?php echo esc_attr( $custom_field['label'] ); ?></label>
				<select class="noptin-text" name="<?php echo esc_attr( $custom_field['name'] ); ?>" id="<?php echo esc_attr( $custom_field['id'] ); ?>">
					<option value="" <?php selected( $custom_field['value'] === '' ); ?>><?php echo esc_html_e( 'Select An Option', 'newsletter-optin-box' ); ?></option>
					<?php foreach ( explode( "\n", $custom_field['options'] ) as $option ) : ?>
						<option value="<?php echo esc_attr( $option ); ?>" <?php selected( esc_attr( $option ), $custom_field['value'] ); ?>><?php echo esc_html( $option ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>

			<?php if ( 'birthday' === $custom_field['type'] ) : ?>
				<label class="noptin-label" for="<?php echo esc_attr( $custom_field['id'] ); ?>"><?php echo esc_attr( $custom_field['label'] ); ?></label>
				<input type="text" placeholder="MM/DD" class="noptin-text" name="<?php echo esc_attr( $custom_field['name'] ); ?>" id="<?php echo esc_attr( $custom_field['id'] ); ?>" value="<?php echo esc_attr( $custom_field['value'] ); ?>">
			<?php endif; ?>

			<?php do_action( 'noptin_display_custom_field_input', $custom_field ); ?>

		</p>
		<?php
	}

}
