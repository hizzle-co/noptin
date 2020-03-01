<div class="wrap" style="max-width: 420px;">
	<div>
		<span style="float:left"><?php echo  0 === (int) $data->active ? 'Active' : 'Inactive'; ?></span>
		<span style="float:right"><?php echo 1 === (int) $data->confirmed ? 'Confirmed' : 'Not Confirmed'; ?></span>
	</div>
	<div style="display: flex;justify-content: center;">
		<img style="margin-top: 40px;" src="<?php echo esc_url( get_avatar_url( $data->email ) ); ?>" />
	</div>

	<h2><?php _e( 'Subscriber Details', 'newsletter-optin-box' ); ?></h2>

	<table class="wp-list-table widefat fixed striped posts">

		<tbody>
			<tr>
				<td><strong><?php _e( 'Subscriber Id', 'newsletter-optin-box' ); ?></strong></td>
				<td><?php echo esc_html( $data->id ); ?></td>
			</tr>
			<tr>
				<td><strong><?php _e( 'Email Address', 'newsletter-optin-box' ); ?></strong></td>
				<td><?php echo esc_html( $data->email ); ?></td>
			</tr>
			<?php if ( ! empty( $data->first_name ) ) { ?>
			<tr>
				<td><strong><?php _e( 'Subscriber Name', 'newsletter-optin-box' ); ?></strong></td>
				<td><?php echo esc_html( $data->first_name . ' ' . $data->second_name ); ?></td>
			</tr>
			<?php } ?>
			<tr>
				<td><strong><?php _e( 'Subscribed On', 'newsletter-optin-box' ); ?></strong></td>
				<td><?php echo esc_html( $data->date_created ); ?></td>
			</tr>
			<?php
			foreach ( $meta as $key => $value ) {

				if ( has_filter( "noptin_format_subscriber_{$key}" ) ) {

					$value = apply_filters( "noptin_format_subscriber_{$key}", $value, $data, $meta );

				} else {
					$value = maybe_unserialize( $value[0] );

					// Skip anything else that is not a scalar.
					if ( ! is_scalar( $value ) ) {
						continue;
					}

					if ( 'country_flag' === $key ) {
						$value = esc_url( $value );
						$value = "<img src='$value'  width='36' />";
					} else {
						$value = esc_html( $value );
					}
				}



				// Rename some fields.
				if ( '_subscriber_via' === $key ) {

					if ( is_numeric( $value ) ) {
						$form  = noptin_get_optin_form( $value );
						$url   = get_noptin_edit_form_url( $value );
						$value = sprintf(
							'<a href="%s">%s</a>',
							esc_url( $url ),
							esc_html( $form->optinName )
						);
					}
					$key = __( 'Subscribed Via', 'newsletter-optin-box' );
				}

				$key = apply_filters( "noptin_subscriber_{$key}_label", $key );

				if ( 0 === stripos( $key, '_' ) ) {
					continue;
				}

				?>
			<tr>
				<td><strong><?php echo esc_html( $key ); ?></strong></td>
				<td><?php echo $value; ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>

	<?php

		do_action( 'noptin_single_subscriber', $data, $meta );

		printf(
			__( '%1$sGo back to the subscribers overview page.%2$s', 'newsletter-optin-box' ),
			'<a style="margin-top: 16px;display: block;" href="' . esc_url( get_noptin_subscribers_overview_url() ) . '">',
			'</a>'
		);
		?>

</div>
<?php
