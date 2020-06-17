<div class="wrap noptin-export-subscribers-page">

	<?php
		printf(
			'<h1 class="title">%s<a class="page-title-action" href="%s">&nbsp;%s</a></h1>',
			esc_html__( 'Export Subscribers','newsletter-optin-box' ),
			esc_url( admin_url( 'admin.php?page=noptin-subscribers' ) ),
			esc_html__( 'Go Back','newsletter-optin-box' )
		);

		$file_name = 'noptin-subscribers-' . time();
	?>

	<form name="noptin-export-subscribers" method="post">
		<input type="hidden" name="noptin_admin_action" value="noptin_export_subscribers">
		<?php wp_nonce_field( 'noptin-export-subscribers', 'noptin-export-subscribers' ); ?>		

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="postbox-container-2" class="postbox-container">
					<table class="form-table">
    					<tbody>

							<tr class="form-field-row-type">
								<th scope="row"><label for="file_name"><?php _e( 'Export File', 'newsletter-optin-box' ); ?></label></th>
								<td>
									<div>
										<input style="width: 20em;" name='file_name' id="file_name" type="text" value="<?php echo $file_name ?>" placeholder="<?php echo $file_name ?>"/>
										<select name='file_type'>
											<option value='csv'><?php _e( 'CSV', 'newsletter-optin-box' ); ?></option>
											<option value='json'><?php _e( 'JSON', 'newsletter-optin-box' ); ?></option>
											<option value='xml'><?php _e( 'XML', 'newsletter-optin-box' ); ?></option>
										</select>
									</div>
								</td>
							</tr>

							<tr class="form-field-row-status">
								<th scope="row"><label for="subscriber_status"><?php _e( 'Subscriber Status', 'newsletter-optin-box' ); ?></label></th>
								<td>
									<div>
										<select name='subscriber_status' class="regular-text" id="subscriber_status">
											<option value='active'><?php _e( 'Active', 'newsletter-optin-box' ); ?></option>
											<option value='inactive'><?php _e( 'In-Active', 'newsletter-optin-box' ); ?></option>
											<option value='all' selected="selected"><?php _e( 'Any', 'newsletter-optin-box' ); ?></option>
										</select>
									</div>
								</td>
							</tr>

							<tr class="form-field-row-confirmation">
								<th scope="row"><label for="email_status"><?php _e( 'Email Status', 'newsletter-optin-box' ); ?></label></th>
								<td>
									<div>
										<select name='email_status' class="regular-text" id="email_status">
											<option value='confirmed'><?php _e( 'Confirmed', 'newsletter-optin-box' ); ?></option>
											<option value='unconfirmed'><?php _e( 'Not Confirmed', 'newsletter-optin-box' ); ?></option>
											<option value='any' selected="selected"><?php _e( 'Any', 'newsletter-optin-box' ); ?></option>
										</select>
									</div>
								</td>
							</tr>

							<tr class="form-field-row-search">
								<th scope="row"><label for="field_search"><?php _e( 'Search Term', 'newsletter-optin-box' ); ?></label></th>
								<td>
									<div>
										<input name='search' id="field_search" class="regular-text" type="text" placeholder="gmail.com"/>
										<p class="description"><?php _e( 'Specify a search term if you want to limit the subscribers by the searched text', 'newsletter-optin-box' ); ?></p>
									</div>
								</td>
							</tr>

							<tr class="form-field-row-date">
								<th scope="row"><label for="field_date"><?php _e( 'Subscription Date', 'newsletter-optin-box' ); ?></label></th>
								<td>
									<div>
										<select name='date_type' style='margin-bottom: 4px;'>
											<option value='on'><?php _e( 'Subscribed On', 'newsletter-optin-box' ); ?></option>
											<option value='before'><?php _e( 'Subscribed Before', 'newsletter-optin-box' ); ?></option>
											<option value='after' selected="selected"><?php _e( 'Subscribed After', 'newsletter-optin-box' ); ?></option>
										</select>
										<input name='date' id="field_date" type="text" placeholder="2020/06/20"/>
										<p class="description"><?php _e( 'Specify a date if you only want to limit the subscribers by date', 'newsletter-optin-box' ); ?></p>
									</div>
								</td>
							</tr>

							<tr class="form-field-row-order">
								<th scope="row"><label for="field_order"><?php _e( 'Order', 'newsletter-optin-box' ); ?></label></th>
								<td>
									<div>
										<select name='order' style='margin-bottom: 4px;'>
											<option value='ASC' selected="selected"><?php _e( 'Ascending', 'newsletter-optin-box' ); ?></option>
											<option value='DESC'><?php _e( 'Descending', 'newsletter-optin-box' ); ?></option>
										</select>
										<select id="field_order" name='order_by' style='margin-bottom: 4px;'>
											<option value='id' selected="selected"><?php _e( 'Subscriber Id', 'newsletter-optin-box' ); ?></option>
											<option value='first_name'><?php _e( 'First Name', 'newsletter-optin-box' ); ?></option>
											<option value='second_name'><?php _e( 'Last Name', 'newsletter-optin-box' ); ?></option>
											<option value='email'><?php _e( 'Email Address', 'newsletter-optin-box' ); ?></option>
											<option value='date_created'><?php _e( 'Subscription Date', 'newsletter-optin-box' ); ?></option>
										</select>
									</div>
								</td>
							</tr>

							<tr class="form-field-row-fields">
								<th scope="row"><?php _e( 'Fields', 'newsletter-optin-box' ); ?></th>
								<td>
									<fieldset>
										<p class="description"><?php _e( 'What fields do you want to export?', 'newsletter-optin-box' ); ?></p>
										<?php
											$fields = get_noptin_subscriber_fields();
											$fields = apply_filters( 'noptin_subscriber_export_fields', $fields );
											foreach ( $fields as $name => $label ) {
												$name    = esc_attr( $name );
												$label   = sanitize_text_field( $label );
												$checked = checked( 'email', $name, false );
												echo "<label><input type='checkbox' name='fields[]' value='$name' $checked><span></span>$label</label><br>";
											}
										?>
									</fieldset>
								</td>
							</tr>

							<tr class="form-field-row-submit">
								<th scope="row"><?php submit_button( __( 'Download Subscribers', 'newsletter-optin-box' ) ); ?></th>
								<td><a href="https://noptin.com/guide/email-subscribers/exporting-subscribers/"><?php _e( 'Learn More', 'newsletter-optin-box' ); ?></a></td>
							</tr>

						</tbody>
					</table>
				</div>
			</div>
		</div>
	</form>
</div>
