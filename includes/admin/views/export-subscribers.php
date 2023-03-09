<?php defined( 'ABSPATH' ) || exit; ?>

<div class="wrap noptin-export-subscribers-page" id="noptin-wrapper">

	<h1 class="title"><?php esc_html_e( 'Export Subscribers', 'newsletter-optin-box' ); ?></h1>

	<?php require plugin_dir_path( __FILE__ ) . 'subscriber-tabs.php'; ?>

	<form name="noptin-export-subscribers" method="POST" action="<?php echo esc_url_raw( add_query_arg( array() ) ); ?>">
		<input type="hidden" name="noptin_admin_action" value="noptin_export_subscribers">
		<?php wp_nonce_field( 'noptin-export-subscribers', 'noptin-export-subscribers' ); ?>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="postbox-container-2" class="postbox-container">
					<table class="form-table">
    					<tbody>

							<tr class="form-field-row-type">
								<th scope="row"><label for="file_name"><?php esc_html_e( 'Export File', 'newsletter-optin-box' ); ?></label></th>
								<td>
									<div>
										<input style="width: 20em;" name='file_name' id="file_name" type="text" value="noptin-subscribers" placeholder="noptin-subscribers"/>
										<select name='file_type'>
											<option value='csv'><?php esc_html_e( 'CSV', 'newsletter-optin-box' ); ?></option>
											<option value='json'><?php esc_html_e( 'JSON', 'newsletter-optin-box' ); ?></option>
											<option value='xml'><?php esc_html_e( 'XML', 'newsletter-optin-box' ); ?></option>
										</select>
									</div>
								</td>
							</tr>

							<tr class="form-field-row-status">
								<th scope="row"><label for="subscriber_status"><?php esc_html_e( 'Subscriber Status', 'newsletter-optin-box' ); ?></label></th>
								<td>
									<div>
										<select name='subscriber_status' class="regular-text" id="subscriber_status">
											<option value='active'><?php esc_html_e( 'Subscribed', 'newsletter-optin-box' ); ?></option>
											<option value='inactive'><?php esc_html_e( 'Pending', 'newsletter-optin-box' ); ?></option>
											<option value='all' selected="selected"><?php esc_html_e( 'Any', 'newsletter-optin-box' ); ?></option>
										</select>
									</div>
								</td>
							</tr>

							<tr class="form-field-row-confirmation">
								<th scope="row"><label for="email_status"><?php esc_html_e( 'Email Status', 'newsletter-optin-box' ); ?></label></th>
								<td>
									<div>
										<select name='email_status' class="regular-text" id="email_status">
											<option value='confirmed'><?php esc_html_e( 'Confirmed', 'newsletter-optin-box' ); ?></option>
											<option value='unconfirmed'><?php esc_html_e( 'Not Confirmed', 'newsletter-optin-box' ); ?></option>
											<option value='any' selected="selected"><?php esc_html_e( 'Any', 'newsletter-optin-box' ); ?></option>
										</select>
									</div>
								</td>
							</tr>

							<tr class="form-field-row-search">
								<th scope="row"><label for="field_search"><?php esc_html_e( 'Search Term', 'newsletter-optin-box' ); ?></label></th>
								<td>
									<div>
										<input name='search' id="field_search" class="regular-text" type="text" placeholder="gmail.com"/>
										<p class="description"><?php esc_html_e( 'Specify a search term if you want to limit the subscribers by the searched text', 'newsletter-optin-box' ); ?></p>
									</div>
								</td>
							</tr>

							<tr class="form-field-row-date">
								<th scope="row"><label for="field_date"><?php esc_html_e( 'Subscription Date', 'newsletter-optin-box' ); ?></label></th>
								<td>
									<div>
										<select name='date_type' style='margin-bottom: 4px;'>
											<option value='on'><?php esc_html_e( 'Subscribed On', 'newsletter-optin-box' ); ?></option>
											<option value='before'><?php esc_html_e( 'Subscribed Before', 'newsletter-optin-box' ); ?></option>
											<option value='after' selected="selected"><?php esc_html_e( 'Subscribed After', 'newsletter-optin-box' ); ?></option>
										</select>
										<input name='date' id="field_date" type="text" placeholder="2020/06/20"/>
										<p class="description"><?php esc_html_e( 'Specify a date if you only want to limit the subscribers by date', 'newsletter-optin-box' ); ?></p>
									</div>
								</td>
							</tr>

							<tr class="form-field-row-order">
								<th scope="row"><label for="field_order"><?php esc_html_e( 'Order', 'newsletter-optin-box' ); ?></label></th>
								<td>
									<div>
										<select name='order' style='margin-bottom: 4px;'>
											<option value='ASC' selected="selected"><?php esc_html_e( 'Ascending', 'newsletter-optin-box' ); ?></option>
											<option value='DESC'><?php esc_html_e( 'Descending', 'newsletter-optin-box' ); ?></option>
										</select>
										<select id="field_order" name='order_by' style='margin-bottom: 4px;'>
											<option value='id' selected="selected"><?php esc_html_e( 'Subscriber Id', 'newsletter-optin-box' ); ?></option>
											<option value='first_name'><?php esc_html_e( 'First Name', 'newsletter-optin-box' ); ?></option>
											<option value='second_name'><?php esc_html_e( 'Last Name', 'newsletter-optin-box' ); ?></option>
											<option value='email'><?php esc_html_e( 'Email Address', 'newsletter-optin-box' ); ?></option>
											<option value='date_created'><?php esc_html_e( 'Subscription Date', 'newsletter-optin-box' ); ?></option>
										</select>
									</div>
								</td>
							</tr>

							<tr class="form-field-row-fields">
								<th scope="row"><?php esc_html_e( 'Fields', 'newsletter-optin-box' ); ?></th>
								<td>
									<fieldset>
										<p class="description"><?php esc_html_e( 'What fields do you want to export?', 'newsletter-optin-box' ); ?></p>
										<?php

											foreach ( get_noptin_custom_fields() as $custom_field ) {
												printf(
													'<label><input type="checkbox" name="fields[]" value="%s" %s><span>%s</span></label><br>',
													esc_attr( $custom_field['merge_tag'] ),
													checked( ! empty( $custom_field['subs_table'] ), true, false ),
													esc_html( $custom_field['label'] )
												);
											}

										?>
										<label><input type='checkbox' name='fields[]' value='active'><span><?php esc_html_e( 'Active', 'newsletter-optin-box' ); ?></span></label><br>
										<label><input type='checkbox' name='fields[]' value='confirm_key'><span><?php esc_html_e( 'Confirm Key', 'newsletter-optin-box' ); ?></span></label><br>
										<label><input type='checkbox' name='fields[]' value='confirmed'><span><?php esc_html_e( 'Email Confirmed', 'newsletter-optin-box' ); ?></span></label><br>
										<label><input type='checkbox' name='fields[]' value='date_created'><span><?php esc_html_e( 'Subscription Date', 'newsletter-optin-box' ); ?></span></label><br>
										<label><input type='checkbox' name='fields[]' value='GDPR_consent'><span><?php esc_html_e( 'GDPR Consent', 'newsletter-optin-box' ); ?></span></label><br>
										<label><input type='checkbox' name='fields[]' value='ip_address'><span><?php esc_html_e( 'IP Address', 'newsletter-optin-box' ); ?></span></label><br>
									</fieldset>
								</td>
							</tr>

							<tr class="form-field-row-submit">
								<th scope="row"><?php submit_button( __( 'Download Subscribers', 'newsletter-optin-box' ) ); ?></th>
								<td><a href="<?php echo esc_attr( noptin_get_upsell_url( 'guide/email-subscribers/exporting-subscribers/', 'export', 'subscribers' ) ); ?>"><?php esc_html_e( 'Need Help?', 'newsletter-optin-box' ); ?></a></td>
							</tr>

						</tbody>
					</table>
				</div>
			</div>
		</div>
	</form>
</div>
