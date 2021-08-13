<div class="wrap noptin-custom-fields-page">

	<h1 class="title"><?php esc_html_e( 'Custom Fields','newsletter-optin-box' ); ?></h1>

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

		.noptin-add-subscriber-form .noptin-text {
			width: 25em;
		}

	</style>
	<form class="noptin-add-subscriber-form" method="POST" action="<?php echo esc_url_raw( add_query_arg( array() ) ); ?>">
		<input type="hidden" name="noptin_admin_action" value="noptin_admin_add_subscriber">
		<?php wp_nonce_field( 'noptin-admin-add-subscriber', 'noptin-admin-add-subscriber' ); ?>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="postbox-container-2" class="postbox-container">
					<table class="form-table">
    					<tbody>

							<tr class="form-field-row form-field-row-status">
								<th scope="row"><label for="noptin_field_newsletter_status"><?php esc_html_e( 'Subscriber Status', 'newsletter-optin-box' ); ?></label></th>
								<td>
									<p>
										<label class="noptin-label" for="noptin_field_newsletter_status"><?php esc_html_e( 'Subscriber Status', 'newsletter-optin-box' ); ?></label>
										<select class="noptin-text" name="noptin_fields[active]" id="noptin_field_newsletter_status">
											<option value="0" selected="selected"><?php echo esc_html_e( 'Subscribed', 'newsletter-optin-box' ); ?></option>
											<option value="1"><?php echo esc_html_e( 'Pending', 'newsletter-optin-box' ); ?></option>
										</select>
									</p>
								</td>
							</tr>

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
											Noptin_Custom_Fields::display_field( $custom_field, false );
										?>
									</td>
								</tr>
							<?php endforeach; ?>

							<tr class="form-field-row-submit">
								<th scope="row"><?php submit_button( __( 'Add Subscriber', 'newsletter-optin-box' ) ); ?></th>
								<td><a href="https://noptin.com/guide/"><?php _e( 'Need Help?', 'newsletter-optin-box' ); ?></a></td>
							</tr>

						</tbody>
					</table>
				</div>
			</div>
		</div>

	</form>

	<p class="description"><?php
		printf(
			__( 'Store more information about your subscribers by %1$screating custom fields%2$s.', 'newsletter-optin-box' ),
			'<a href="' . esc_url_raw( admin_url( 'admin.php?page=noptin-settings&tab=fields' ) ) . '">',
			'</a>'
		);
	?></p>

</div>
