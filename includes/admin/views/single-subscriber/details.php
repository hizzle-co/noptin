<style>

	#noptin_subscriber_details .noptin-label {
		border: 0;
		clip: rect(1px,1px,1px,1px);
		-webkit-clip-path: inset(50%);
		clip-path: inset(50%);
		height: 1px;
		margin: -1px;
		overflow: hidden;
		padding: 0;
		position: absolute;
		width: 1px;
		word-wrap: normal!important;
	}

	#noptin_subscriber_details .noptin-text,
	#noptin_subscriber_details .noptin-birthday-div {
		width: 100%;
		max-width: 25em !important;
	}
</style>

<table class="form-table">
	<tbody>

		<?php foreach ( get_noptin_custom_fields() as $custom_field ) : ?>
			<tr class="form-field-row form-field-row-<?php echo sanitize_html_class( $custom_field['merge_tag'] ); ?>">
				<th scope="row">
					<label for="noptin_field_<?php echo sanitize_html_class( $custom_field['merge_tag'] ); ?>"><?php echo 'checkbox' === $custom_field['type'] ? "&nbsp;" : esc_html( $custom_field['label'] ); ?></label>
				</th>
				<td>
					<?php
						// Display the field.
						$custom_field['wrap_name'] = true;
						$custom_field['show_id']   = true;
						display_noptin_custom_field_input( $custom_field, $subscriber );
					?>
				</td>
			</tr>
		<?php endforeach; ?>

		<tr class="form-field-row-status">
			<th scope="row"><label for="field_status"><?php _e( 'Subscription Status', 'newsletter-optin-box' ); ?></label></th>
			<td>
				<div>
					<select name="noptin_fields[active]" id="field_status" class="noptin-text">
						<option <?php selected( 0 === (int) $subscriber->active ) ?> value="0"><?php _e( 'Subscribed', 'newsletter-optin-box' ); ?></option>
						<option <?php selected( 0 !== (int) $subscriber->active ) ?> value="1"><?php _e( 'Pending', 'newsletter-optin-box' ); ?></option>
					</select>
				</div>
			</td>
		</tr>

		<tr class="form-field-row-email-status">
			<th scope="row"><label for="field_email_status"><?php _e( 'Email Status', 'newsletter-optin-box' ); ?></label></th>
			<td>
				<div>
					<select name="noptin_fields[confirmed]" id="field_email_status" class="noptin-text">
						<option <?php selected( 1 === (int) $subscriber->confirmed ) ?> value="1"><?php _e( 'Confirmed', 'newsletter-optin-box' ); ?></option>
						<option <?php selected( 1 !== (int) $subscriber->confirmed ) ?> value="0"><?php _e( 'Not Confirmed', 'newsletter-optin-box' ); ?></option>
					</select>
				</div>
			</td>
		</tr>

		<tr class="form-field-row-key">
			<th scope="row"><label for="field_confirm_key"><?php _e( 'Confirmation Key', 'newsletter-optin-box' ); ?></label></th>
			<td>
				<div>
					<input type="text" class="regular-text" id="field_confirm_key" value="<?php echo esc_attr( $subscriber->confirm_key ); ?>" readonly>
				</div>
			</td>
		</tr>
	</tbody>
</table>

<p class="description"><?php
	printf(
		__( 'Store more information about your subscribers by %1$screating custom fields%2$s.', 'newsletter-optin-box' ),
		'<a target="_blank" href="' . esc_url_raw( admin_url( 'admin.php?page=noptin-settings&tab=fields' ) ) . '">',
		'</a>'
	)
?></p>
