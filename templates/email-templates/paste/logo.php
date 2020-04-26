<?php if ( ! empty( $logo_url ) ) { ?>
	<!-- start logo -->
	<tr>
		<td align="center" bgcolor="#e9ecef">
		<!--[if (gte mso 9)|(IE)]>
		<table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
		<tr>
		<td align="center" valign="top" width="600">
		<![endif]-->
		<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
			<tr>
				<td align="center" valign="top" style="padding: 24px 0;">
					<a href="[[home_url]]" target="_blank" style="display: inline-block;"><img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'This email looks way better when viewed with images.', 'newsletter-optin-box' ); ?>" border="0" style="display: block; height: auto; max-height: 200px; width: auto; max-width: 100%; min-width: 48px;"></a>
				</td>
			</tr>
		</table>

		<!--[if (gte mso 9)|(IE)]>
		</td>
		</tr>
		</table>
		<![endif]-->
		</td>
	</tr>
	<!-- end logo -->
<?php } ?>
