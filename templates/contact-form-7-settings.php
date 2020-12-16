<?php

	/**
	 * Returns an array of form tags.
	 *
	 * @param WPCF7_ContactForm $contact_form The contact form being edited.
	 * @param string $field_type The field type being mapped.
	 * @return array
	 */
	function noptin_get_contact_form_7_form_tags( $contact_form, $field_type = null ) {

		return array_reduce( $contact_form->scan_form_tags(), function ( $carry, $item ) use ( $field_type ) {

			if ( ! empty( $item->name ) ) {
				if ( ! empty( $field_type ) && $item->basetype != $field_type ) {
					return $carry;
				}

				$carry[ $item->name ] = $item->name;
			}

			return $carry;
		} );
	}

	$mapped_fields     = isset( $settings['custom_fields'] ) ? $settings['custom_fields'] : array();
	$conditional_field = isset( $settings['conditional'] ) ? $settings['conditional'] : '';
	$cf7_form_tags     = noptin_get_contact_form_7_form_tags( $contact_form, 'acceptance' );

?>

<?php do_action( 'noptin_before_contact_form_7_settings', $custom_fields, $settings, $contact_form, $mapped_fields ); ?>

<style>
	#noptin .form-table th {
        width: 200px;
    }
</style>

<table class="form-table">
	<tbody>
		<tr>
			<th scope="row">
				<label><?php _e( 'Conditional Sign-up', 'newsletter-optin-box' ); ?></label>
			</th>

			<td>
				<select name="noptin_settings[conditional]" id="noptin_conditional_checkbox" style="width: 25em;">
					<option <?php selected( $conditional_field, '' ) ?> value=""><?php esc_html_e( 'Disable Conditional', 'newsletter-optin-box' ) ?></option>
					<?php foreach ( $cf7_form_tags as $key => $value): ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $conditional_field, $value ) ?>><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php _e( 'Optional. If you select a checkbox, users will only be added to your newsletter if they check it.', 'newsletter-optin-box' ); ?></p>
			</td>
		</tr>
		<?php do_action( 'noptin_contact_form_7_settings', $custom_fields, $settings, $contact_form ); ?>
	</tbody>
</table>

<h3><?php esc_html_e('Map Fields', 'newsletter-optin-box') ?></h3>

<table class="form-table">
	<tbody>

		<?php
			foreach( $custom_fields as $field ) :

				// Retrieve form tags.
				$type = null;
				if ( ! empty( $field['type'] ) ) {
					$type = $field['type'];
				}

				$cf7_form_tags = noptin_get_contact_form_7_form_tags( $contact_form, $type );

				$mapped_field = isset( $mapped_fields[ $field['name'] ] ) ? $mapped_fields[ $field['name'] ] : '';

		?>

			<tr>
				<th scope="row">
					<label><?php echo esc_html( $field['label'] ) ?></label>
				</th>

				<td>
					<select name="noptin_settings[custom_fields][<?php echo esc_attr( $field['name'] ) ?>]" id="noptin_map_field_<?php echo esc_attr( $field['name'] ) ?>" style="width: 25em;">
						<option <?php selected( $mapped_field, '' ) ?> disabled><?php esc_html_e( 'Map Field', 'newsletter-optin-box' ) ?></option>
						<?php foreach ( $cf7_form_tags as $key => $value): ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $mapped_field, $value ) ?>><?php echo esc_html( $value ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>

			<?php endforeach; ?>

	</tbody>
</table>

<?php do_action( 'noptin_after_contact_form_7_settings', $custom_fields, $settings, $contact_form ); ?>
