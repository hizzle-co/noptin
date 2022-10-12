<?php

	defined( 'ABSPATH' ) || exit;

	/**
	 * Returns an array of form tags.
	 *
	 * @param WPCF7_ContactForm $contact_form The contact form being edited.
	 * @param string $field_type The field type being mapped.
	 * @return array
	 */
	function noptin_get_contact_form_7_form_tags( $contact_form, $field_type = null ) {

		return array_reduce(
			$contact_form->scan_form_tags(),
			function ( $carry, $item ) use ( $field_type ) {

				if ( ! empty( $item->name ) ) {
					if ( ! empty( $field_type ) && $item->basetype !== $field_type ) {
						return $carry;
					}

					$carry[ $item->name ] = $item->name;
				}

				return $carry;
			}
		);
	}

	$mapped_fields     = isset( $settings['custom_fields'] ) ? $settings['custom_fields'] : array();

?>

<?php do_action( 'noptin_before_contact_form_7_settings', $custom_fields, $settings, $contact_form, $mapped_fields ); ?>

<style>
	#noptin .form-table th {
        width: 200px;
    }
</style>

<table class="form-table">
	<tbody>
		<?php do_action( 'noptin_contact_form_7_settings', $custom_fields, $settings, $contact_form ); ?>
	</tbody>
</table>

<h3><?php esc_html_e( 'Map Fields', 'newsletter-optin-box' ); ?></h3>

<table class="form-table">
	<tbody>

		<?php
			foreach ( $custom_fields as $field ) :

				// Retrieve form tags.
				$field_type = null;
				if ( ! empty( $field['type'] ) ) {
					$field_type = $field['type'];
				}

				$cf7_form_tags = noptin_get_contact_form_7_form_tags( $contact_form, $field_type );
				$cf7_form_tags = is_array( $cf7_form_tags ) ? $cf7_form_tags : array();
				$mapped_field  = isset( $mapped_fields[ $field['name'] ] ) ? $mapped_fields[ $field['name'] ] : '';

		?>

			<tr>
				<th scope="row">
					<label><?php echo esc_html( $field['label'] ); ?></label>
				</th>

				<td>
					<select name="noptin_settings[custom_fields][<?php echo esc_attr( $field['name'] ); ?>]" id="noptin_map_field_<?php echo esc_attr( $field['name'] ); ?>" style="width: 25em;">
						<option value="" <?php selected( $mapped_field, '' ); ?> ><?php esc_html_e( 'Not Mapped', 'newsletter-optin-box' ); ?></option>
						<?php foreach ( $cf7_form_tags as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $mapped_field, $value ); ?>><?php echo esc_html( $value ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ( 'GDPR_consent' === $field['name'] ) : ?>
						<p class="description"><?php esc_html_e( 'If mapped, only users who consent will join your newsletter.', 'newsletter-optin-box' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>

		<?php endforeach; ?>

	</tbody>
</table>

<?php do_action( 'noptin_after_contact_form_7_settings', $custom_fields, $settings, $contact_form ); ?>
