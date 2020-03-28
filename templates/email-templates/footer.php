<tr>
	<td align="center" bgcolor="#e9ecef" style="padding: 24px;">
		<!--[if (gte mso 9)|(IE)]>
			<table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
				<tr>
					<td align="center" valign="top" width="600">
		<![endif]-->
		<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">

			<!-- start permission -->
			<tr>
				<td align="center" bgcolor="#e9ecef"
					style="padding: 12px 24px; font-family: 'Source Sans Pro', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 20px; color: #666;">
					<p style="margin: 0;"><?php _e( 'You received this email because you are subscribed to our email newsletter.', 'newsletter-optin-box' ); ?>
					</p>
				</td>
			</tr>
			<!-- end permission -->

			<!-- start unsubscribe -->
			<tr>
				<td align="center" bgcolor="#e9ecef"
					style="padding: 12px 24px; font-family: 'Source Sans Pro', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 20px; color: #666;">
					<p style="margin: 0;">
						<?php
							echo sprintf(
								/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
								__( 'To stop receiving these emails, you can %1$sunsubscribe%2$s at any time.', 'newsletter-optin-box' ),
								'<a href="[[unsubscribe_url]]" target="_blank">',
								'</a>'
							);
						?>
					</p>
					<p style="margin: 0;">[[noptin_company]] <br /> [[noptin_address]] <br /> [[noptin_city]],
						[[noptin_state]],
						[[noptin_country]]</p>
				</td>
			</tr>
			<!-- end unsubscribe -->

		</table>
		<!--[if (gte mso 9)|(IE)]>
					</td>
				</tr>
			</table>
		<![endif]-->
	</td>
</tr>
