		<!-- start footer -->
		<tr>
			<td align="center" bgcolor="#e9ecef" style="padding: 24px;" class="footer">
				<!--[if (gte mso 9)|(IE)]>
					<table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
						<tr>
							<td align="center" valign="top" width="600">
				<![endif]-->
				<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">

					<!-- start permission -->
					<?php if ( ! empty( $permission_text ) ) { ?>
					<tr>
						<td align="center" bgcolor="#e9ecef" style="padding: 12px 24px; font-family: Helvetica, Arial, sans-serif; font-size: 14px; line-height: 20px; color: #666;">
							<?php echo $permission_text; ?>
						</td>
					</tr>
					<?php } ?>
					<!-- end permission -->

					<!-- start unsubscribe -->
					<?php if ( ! empty( $footer_text ) ) { ?>
					<tr>
						<td align="center" bgcolor="#e9ecef" style="padding: 12px 24px; font-family: Helvetica, Arial, sans-serif; font-size: 14px; line-height: 20px; color: #666;">
							<?php echo $footer_text; ?>
						</td>
					</tr>
					<?php } ?>
					<!-- end unsubscribe -->

				</table>
				<!--[if (gte mso 9)|(IE)]>
							</td>
						</tr>
					</table>
				<![endif]-->
			</td>
		</tr>
		<!-- end footer -->

	</table>
	<!-- end body -->

</body>
</html>
