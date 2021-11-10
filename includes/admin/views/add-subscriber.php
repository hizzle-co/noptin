<div class="wrap noptin-add-subscriber-page" id="noptin-wrapper">

	<h1 class="title"><?php esc_html_e( 'Add Subscriber','newsletter-optin-box' ); ?></h1>

	<style>

		.noptin-add-subscriber-form .noptin-label {
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

		.noptin-add-subscriber-form .noptin-text,
		.noptin-add-subscriber-form .noptin-birthday-div {
			width: 100%;
			max-width: 25em !important;
		}
	</style>

	<form class="noptin-add-subscriber-form" method="POST" action="<?php echo esc_url_raw( add_query_arg( array() ) ); ?>">
		<input type="hidden" name="noptin_admin_action" value="noptin_admin_add_subscriber">
		<?php wp_nonce_field( 'noptin-admin-add-subscriber', 'noptin-admin-add-subscriber' ); ?>

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
									display_noptin_custom_field_input( $custom_field, false );
								?>
							</td>
						</tr>
					<?php endforeach; ?>

					<tr class="form-field-row-status">
						<th scope="row"><label for="field_status"><?php _e( 'Subscription Status', 'newsletter-optin-box' ); ?></label></th>
						<td>
							<div>
								<select name="noptin_fields[active]" id="field_status" class="noptin-text">
									<option <?php selected( ! (bool) get_noptin_option( 'double_optin', false ) ) ?> value="0"><?php _e( 'Subscribed', 'newsletter-optin-box' ); ?></option>
									<option <?php selected( (bool) get_noptin_option( 'double_optin', false ) ) ?> value="1"><?php _e( 'Pending', 'newsletter-optin-box' ); ?></option>
								</select>
							</div>
						</td>
					</tr>

					<tr class="form-field-row-email-status">
						<th scope="row"><label for="field_email_status"><?php _e( 'Email Status', 'newsletter-optin-box' ); ?></label></th>
						<td>
							<div>
								<select name="noptin_fields[confirmed]" id="field_email_status" class="noptin-text">
									<option value="1"><?php _e( 'Confirmed', 'newsletter-optin-box' ); ?></option>
									<option selected="selected" value="0"><?php _e( 'Not Confirmed', 'newsletter-optin-box' ); ?></option>
								</select>
							</div>
						</td>
					</tr>

					<tr class="form-field-row-submit">
						<th scope="row"><?php submit_button( __( 'Add Subscriber', 'newsletter-optin-box' ) ); ?></th>
						<td><!--<a target="_blank" href="https://noptin.com/guide/"><?php _e( 'Need Help?', 'newsletter-optin-box' ); ?></a>!--></td>
					</tr>

				</tbody>
			</table>

	</form>

	<p class="description"><?php
		printf(
			__( 'Store more information about your subscribers by %1$screating custom fields%2$s.', 'newsletter-optin-box' ),
			'<a href="' . esc_url_raw( admin_url( 'admin.php?page=noptin-settings&tab=fields' ) ) . '">',
			'</a>'
		);
	?></p>

</div>
