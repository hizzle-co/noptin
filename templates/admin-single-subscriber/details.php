<table class="form-table">
	<tbody>
		<?php foreach ( get_noptin_custom_fields() as $custom_field ) : ?>
			<tr class="noptin-form-field-row form-field-row-<?php echo sanitize_html_class( $custom_field['merge_tag'] ); ?>">
				<th scope="row"><label for="noptin_field_<?php echo sanitize_html_class( $custom_field['merge_tag'] ); ?>"><?php echo 'checkbox' === $custom_field['type'] ? "&nbsp;" : esc_html( $custom_field['label'] ); ?></label></th>
				<td>
					<div>

						<?php if ( 'email' === $custom_field['type'] ) : ?>
							<input type="email" class="regular-text" name="<?php echo esc_attr( $custom_field['merge_tag'] ); ?>" id="noptin_field_<?php echo sanitize_html_class( $custom_field['merge_tag'] ); ?>" value="<?php echo esc_attr( $subscriber->get( $custom_field['merge_tag'] ) ); ?>">
						<?php endif; ?>

						<?php if ( 'first_name' === $custom_field['type'] || 'last_name' === $custom_field['type'] ) : ?>
							<input type="text" class="regular-text" name="<?php echo esc_attr( $custom_field['merge_tag'] ); ?>" id="noptin_field_<?php echo sanitize_html_class( $custom_field['merge_tag'] ); ?>" value="<?php echo esc_attr( $subscriber->get( $custom_field['merge_tag'] ) ); ?>">
						<?php endif; ?>

						<?php if ( 'text' === $custom_field['type'] ) : ?>
							<input type="text" class="regular-text" name="noptin_custom_field[<?php echo esc_attr( $custom_field['merge_tag'] ); ?>]" id="noptin_field_<?php echo sanitize_html_class( $custom_field['merge_tag'] ); ?>" value="<?php echo esc_attr( $subscriber->get( $custom_field['merge_tag'] ) ); ?>">
						<?php endif; ?>

						<?php if ( 'number' === $custom_field['type'] ) : ?>
							<input type="number" class="regular-text" name="noptin_custom_field[<?php echo esc_attr( $custom_field['merge_tag'] ); ?>]" id="noptin_field_<?php echo sanitize_html_class( $custom_field['merge_tag'] ); ?>" value="<?php echo (float) $subscriber->get( $custom_field['merge_tag'] ); ?>">
						<?php endif; ?>

						<?php if ( 'date' === $custom_field['type'] ) : ?>
							<input type="date" class="regular-text" name="noptin_custom_field[<?php echo esc_attr( $custom_field['merge_tag'] ); ?>]" id="noptin_field_<?php echo sanitize_html_class( $custom_field['merge_tag'] ); ?>" value="<?php echo esc_attr( $subscriber->get( $custom_field['merge_tag'] ) ); ?>">
						<?php endif; ?>

						<?php if ( 'checkbox' === $custom_field['type'] ) : ?>
							<label>
								<input type="checkbox" value="1" name="noptin_custom_field[<?php echo esc_attr( $custom_field['merge_tag'] ); ?>]" id="noptin_field_<?php echo sanitize_html_class( $custom_field['merge_tag'] ); ?>" <?php checked( '1', $subscriber->get( $custom_field['merge_tag'] ) ); ?> >
								<strong><?php echo esc_html( $custom_field['label'] ); ?></strong>
							</label>
						<?php endif; ?>

						<?php if ( 'radio' === $custom_field['type'] && ! empty( $custom_field['options'] ) ) : ?>
							<?php foreach ( explode( "\n", $custom_field['options'] ) as $option ) : ?>
								<label style="display: block; margin-bottom: 6px;">
									<input type="radio" value="<?php echo esc_attr( $option ); ?>" name="noptin_custom_field[<?php echo esc_attr( $custom_field['merge_tag'] ); ?>]" <?php checked( esc_attr( $option ), $subscriber->get( $custom_field['merge_tag'] ) ); ?> >
									<strong><?php echo esc_html( $option ); ?></strong>
								</label>
							<?php endforeach; ?>
						<?php endif; ?>

						<?php if ( 'dropdown' === $custom_field['type'] && ! empty( $custom_field['options'] ) ) : ?>
							<select class="regular-text" name="noptin_custom_field[<?php echo esc_attr( $custom_field['merge_tag'] ); ?>]" id="noptin_field_<?php echo sanitize_html_class( $custom_field['merge_tag'] );?>">
								<option value="" <?php selected( $subscriber->get( $custom_field['merge_tag'] ) === '' ); ?>><?php echo esc_html_e( 'Select An Option', 'newsletter-optin-box' ); ?></option>
								<?php foreach ( explode( "\n", $custom_field['options'] ) as $option ) : ?>
									<option value="<?php echo esc_attr( $option ); ?>" <?php selected( esc_attr( $option ), $subscriber->get( $custom_field['merge_tag'] ) ); ?>><?php echo esc_html( $option ); ?></option>
								<?php endforeach; ?>
							</select>
						<?php endif; ?>

						<?php if ( 'birthday' === $custom_field['type'] ) : ?>
							<input type="text" placeholder="MM/DD" class="regular-text" name="noptin_custom_field[<?php echo esc_attr( $custom_field['merge_tag'] ); ?>]" id="noptin_field_<?php echo sanitize_html_class( $custom_field['merge_tag'] ); ?>" value="<?php echo esc_attr( $subscriber->get( $custom_field['merge_tag'] ) ); ?>">
						<?php endif; ?>

						<?php do_action( 'noptin_admin_single_subscriber_display_custom_field', $custom_field ); ?>
					</div>
				</td>
			</tr>
		<?php endforeach; ?>
		<tr class="form-field-row-status">
			<th scope="row"><label for="field_status"><?php _e( 'Subscription Status', 'newsletter-optin-box' ); ?></label></th>
			<td>
				<div>
					<select name="status" id="field_status" style="min-width: 25em;">
						<option <?php selected( 0 === (int) $subscriber->active ) ?> value="0"><?php _e( 'Subscribed', 'newsletter-optin-box' ); ?></option>
						<option <?php selected( 0 !== (int) $subscriber->active ) ?> value="1"><?php _e( 'Pending', 'newsletter-optin-box' ); ?></option>
					</select>
				</div>
			</td>
		</tr>

		<tr class="form-field-row-email-status">
			<th scope="row"><label for="field_status"><?php _e( 'Email Status', 'newsletter-optin-box' ); ?></label></th>
			<td>
				<div>
					<select name="confirmed" id="field_email_status" style="min-width: 25em;">
						<option <?php selected( 1 === (int) $subscriber->confirmed ) ?> value="1"><?php _e( 'Confirmed', 'newsletter-optin-box' ); ?></option>
						<option <?php selected( 1 !== (int) $subscriber->confirmed ) ?> value="0"><?php _e( 'Not Confirmed', 'newsletter-optin-box' ); ?></option>
					</select>
				</div>
			</td>
		</tr>

		<tr class="form-field-row-key">
			<th scope="row"><label for="field_status"><?php _e( 'Confirmation Key', 'newsletter-optin-box' ); ?></label></th>
			<td>
				<div>
					<input type="text" class="regular-text" name="confirm_key" id="field_confirm_key" value="<?php echo esc_attr( $subscriber->confirm_key ); ?>" disabled="disabled">
				</div>
			</td>
		</tr>
	</tbody>
</table>

<p class="description"><a target="_blank" href="<?php echo esc_url_raw( admin_url( 'admin.php?page=noptin-settings&tab=fields' ) ); ?>"><?php _e( 'Manage available subscriber fields', 'newsletter-optin-box' ); ?></a></p>
