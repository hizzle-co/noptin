<div class="wrap" style="max-width: 420px;">
	<div>
		<span style="float:left"><?php echo  0 ==  $data->active ? 'Active' : 'Inactive' ?></span>
		<span style="float:right"><?php echo 1 ==  $data->confirmed ? 'Confirmed' : 'Not Confirmed' ?></span>
	</div>
	<div style="display: flex;justify-content: center;">
		<img style="margin-top: 40px;" src="<?php echo esc_url( get_avatar_url( $data->email ) ); ?>" />
	</div>

	<h2><?php _e( 'Subscriber Details', 'newsletter-optin-box'); ?></h2>

	<table class="wp-list-table widefat fixed striped posts">

		<tbody>
			<tr>
				<td><strong><?php _e( 'Subscriber Id', 'newsletter-optin-box'); ?></strong></td>
				<td><?php esc_html_e( $data->id ); ?></td>
			</tr>
			<tr>
				<td><strong><?php _e( 'Email Address', 'newsletter-optin-box'); ?></strong></td>
				<td><?php esc_html_e( $data->email ); ?></td>
			</tr>
			<?php if(! empty( $data->first_name ) ) { ?>
			<tr>
				<td><strong><?php _e( 'Subscriber Name', 'newsletter-optin-box'); ?></strong></td>
				<td><?php esc_html_e( $data->first_name . ' ' . $data->second_name ); ?></td>
			</tr>
			<?php } ?>
			<tr>
				<td><strong><?php _e( 'Subscribed On', 'newsletter-optin-box'); ?></strong></td>
				<td><?php esc_html_e( $data->date_created ); ?></td>
			</tr>
			<?php
			foreach( $meta as $key => $value ) {
				$value = $value[0];

				//Join arrays into a string
				if( is_array( $value ) ) {
					$value = implode( ', ', $value );
				}

				//Skip anything else that is not a scalar
				if(! is_scalar( $value ) ) {
					continue;
				}

				//Rename some fields
				if( '_subscriber_via' == $key ) {

					if( is_numeric( $value ) ) {
						$form  = noptin_get_optin_form( $value );
						$url   = get_noptin_edit_form_url( $value );
						$value = sprintf(
							'<a href="%s">%s</a>',
							esc_url( $url ),
							esc_html( $form->optinName )
						);
					}
					$key = __( 'Subscribed Via', 'newsletter-optin-box' );
				} else {
					$value = esc_html( $value );
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
		printf(
			__('%sGo back to the subscribers overview page.%s',  'newsletter-optin-box'),
			'<a style="margin-top: 16px;display: block;" href="' . esc_url( get_noptin_subscribers_overview_url() ) . '">',
			'</a>'
		);
	?>

</div>
<?php
