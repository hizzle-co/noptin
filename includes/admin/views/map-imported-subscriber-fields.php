<form class="noptin-import-subscribers-form-map-fields" method="POST">

	<header>
		<h2><?php _e( 'Map Fields', 'newsletter-optin-box' ); ?></h2>
		<p class="description"><?php _e( 'Next, map imported fields to Noptin fields.', 'newsletter-optin-box' ); ?></p>
	</header>

	<section>
		<table class="form-table">
			<tbody>

				<tr class="form-field-row form-field-row-confirmed">
					<th scope="row"><?php _e( 'Noptin Field', 'newsletter-optin-box' ); ?></th>
					<td><strong><?php _e( 'Map Field', 'newsletter-optin-box' ); ?></strong></td>
				</tr>

				<?php foreach ( get_noptin_custom_fields() as $custom_field ) : ?>
					<tr class="form-field-row form-field-row-<?php echo sanitize_html_class( $custom_field['merge_tag'] ); ?>">
						<th scope="row">
							<label for="noptin_map_field_<?php echo sanitize_html_class( $custom_field['merge_tag'] ); ?>"><?php echo esc_html( $custom_field['label'] ); ?></label>
						</th>
						<td>
							<select class="noptin-map-field noptin-field-can-have-custom-value regular-text" id="noptin_map_field_<?php echo sanitize_html_class( $custom_field['merge_tag'] ); ?>" data-maps="<?php echo esc_attr( $custom_field['merge_tag'] ); ?>">
								<option <?php selected( empty( $fields[ $custom_field['merge_tag'] ] ) ); ?> value="0"><?php esc_html_e( 'Map Field', 'newsletter-optin-box' ); ?></option>
								<option value="-1"><?php esc_html_e( 'Manually enter value', 'newsletter-optin-box' ); ?></option>
								<?php foreach ( $headers as $header ) : ?>
									<option <?php selected( isset( $fields[ $custom_field['merge_tag'] ] ) && $fields[ $custom_field['merge_tag'] ] === $header ); ?> value="<?php echo esc_attr( $header ); ?>"><?php echo esc_html( $header ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="noptin-custom-field-value hidden">
								<input class="regular-text" type="text" placeholder="<?php esc_attr_e( 'Enter field value') ?>" />
								<br>
								<span><?php esc_html_e( 'This value will be assigned to all imported subscribers', 'newsletter-optin-box' ); ?></span>
							</p>
						</td>
					</tr>
				<?php endforeach; ?>

				<tr class="form-field-row form-field-row-status">
					<th scope="row"><label for="noptin_map_field_status"><?php _e( 'Subscription Status', 'newsletter-optin-box' ); ?></label></th>
					<td>
						<select class="noptin-map-field regular-text" id="noptin_map_field_status" data-maps="active">
							<option value="1" selected="selected"><?php esc_html_e( 'Mark all as active', 'newsletter-optin-box' ); ?></option>
							<option value="2"><?php esc_html_e( 'Mark all as inactive', 'newsletter-optin-box' ); ?></option>
							<optgroup label="<?php esc_attr_e( 'Map Field', 'newsletter-optin-box' ); ?>">
								<?php foreach ( $headers as $header ) : ?>
									<option value="<?php echo esc_attr( $header ); ?>"><?php echo esc_html( $header ); ?></option>
								<?php endforeach; ?>
							</optgroup>
						</select>
						<p class="description"><?php esc_html_e( 'A subscriber can either be active or inactive', 'newsletter-optin-box' ); ?></p>
					</td>
				</tr>

				<tr class="form-field-row form-field-row-confirmed">
					<th scope="row"><label for="noptin_map_field_confirmed"><?php _e( 'Email Status', 'newsletter-optin-box' ); ?></label></th>
					<td>
						<select class="noptin-map-field regular-text" id="noptin_map_field_confirmed" data-maps="confirmed">
							<option value="1"><?php esc_html_e( 'Mark all as confirmed', 'newsletter-optin-box' ); ?></option>
							<option value="2" selected="selected"><?php esc_html_e( 'Mark all as unconfirmed', 'newsletter-optin-box' ); ?></option>
							<optgroup label="<?php esc_attr_e( 'Map Field', 'newsletter-optin-box' ); ?>">
								<?php foreach ( $headers as $header ) : ?>
									<option value="<?php echo esc_attr( $header ); ?>"><?php echo esc_html( $header ); ?></option>
								<?php endforeach; ?>
							</optgroup>
						</select>
						<p class="description"><?php esc_html_e( "A subscriber's email can either be confirmed or unconfirmed", 'newsletter-optin-box' ); ?></p>
					</td>
				</tr>

				<tr class="form-field-row form-field-row-source noptin-advanced hidden">
					<th scope="row"><label for="noptin_map_field_source"><?php _e( 'Subscribed Via', 'newsletter-optin-box' ); ?></label></th>
					<td>
						<select class="noptin-map-field regular-text" id="noptin_map_field_source" data-maps="_subscriber_via">
							<option value="-1"><?php esc_html_e( 'Mark all as imported', 'newsletter-optin-box' ); ?></option>
							<optgroup label="<?php esc_attr_e( 'Map Field', 'newsletter-optin-box' ); ?>">
								<?php foreach ( $headers as $header ) : ?>
									<option value="<?php echo esc_attr( $header ); ?>"><?php echo esc_html( $header ); ?></option>
								<?php endforeach; ?>
							</optgroup>
						</select>
						<p class="description"><?php esc_html_e( 'How did they join your newsletter?', 'newsletter-optin-box' ); ?></p>
					</td>
				</tr>

				<tr class="form-field-row form-field-row-ip_address noptin-advanced hidden">
					<th scope="row"><label for="noptin_map_field_ip_address"><?php _e( 'IP Address', 'newsletter-optin-box' ); ?></label></th>
					<td>
						<select class="noptin-map-field regular-text" id="noptin_map_field_ip_address" data-maps="ip_address">
							<option selected="selected" value="0"><?php esc_html_e( 'Map Field', 'newsletter-optin-box' ); ?></option>
							<?php foreach ( $headers as $header ) : ?>
								<option value="<?php echo esc_attr( $header ); ?>"><?php echo esc_html( $header ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( "Map the subscriber's IP address", 'newsletter-optin-box' ); ?></p>
					</td>
				</tr>

				<tr class="form-field-row form-field-row-conversion_page noptin-advanced hidden">
					<th scope="row"><label for="noptin_map_field_conversion_page"><?php _e( 'Conversion Page', 'newsletter-optin-box' ); ?></label></th>
					<td>
						<select class="noptin-map-field regular-text" id="noptin_map_field_conversion_page" data-maps="conversion_page">
							<option selected="selected" value="0"><?php esc_html_e( 'Map Field', 'newsletter-optin-box' ); ?></option>
							<?php foreach ( $headers as $header ) : ?>
								<option value="<?php echo esc_attr( $header ); ?>"><?php echo esc_html( $header ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( "Which URL did the subscriber convert on?", 'newsletter-optin-box' ); ?></p>
					</td>
				</tr>

			</table>
		</section>

		<footer>
			<button type="submit" class="button button-primary noptin-import-finish"><?php esc_html_e( 'Import', 'newsletter-optin-box'); ?></button>
			<button type="submit" class="button button-link" style="color: grey;" onclick="jQuery('.noptin-advanced').toggleClass('hidden'); return false;"><?php esc_html_e( 'Toggle Advanced Options', 'newsletter-optin-box'); ?></button>
		</footer>
		<p class="description"><?php
			printf(
				__( 'Import more information about your subscribers by %1$screating custom fields%2$s.', 'newsletter-optin-box' ),
				'<a target="_blank" href="' . esc_url_raw( admin_url( 'admin.php?page=noptin-settings&tab=fields' ) ) . '">',
				'</a>'
			);
		?></p>
	</form>

<div class="noptin-import-progress hidden">
	<h3 class="hidden noptin-import-complete" style="color: green;"><?php _e( 'Import Complete', 'newsletter-optin-box' );?></h3>
	<h3 class="noptin-importing"><?php _e( 'Importing Subscribers', 'newsletter-optin-box' );?><span class="spinner" style="float: none; visibility: visible;"></span></h3>
	<h4><?php printf( __( '%s Subscribers Imported', 'newsletter-optin-box' ), '<span class="noptin-imported">0</span>' ); ?></h4>
	<h4><?php printf( __( '%s Subscribers Updated', 'newsletter-optin-box' ), '<span class="noptin-updated">0</span>' ); ?></h4>
	<h4><?php printf( __( '%s Subscribers Skipped', 'newsletter-optin-box' ), '<span class="noptin-skipped">0</span>' ); ?></h4>
	<h4><?php printf( __( '%s Subscribers Failed', 'newsletter-optin-box' ), '<span class="noptin-failed">0</span>' ); ?></h4>
</div>
