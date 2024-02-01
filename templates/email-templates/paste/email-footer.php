<?php

	defined( 'ABSPATH' ) || exit;

	/**
	 * @var array $settings
	 * @var string $footer
	 */
?>
		<!-- start footer -->
		<tr>
			<td align="center" bgcolor="<?php echo esc_attr( $settings['background_color'] ); ?>" style="padding: 24px;" class="footer">
				<!--[if (gte mso 9)|(IE)]>
					<table align="center" border="0" cellpadding="0" cellspacing="0" width="<?php echo esc_attr( $settings['width'] ); ?>">
						<tr>
							<td align="center" valign="top" width="<?php echo esc_attr( $settings['width'] ); ?>">
				<![endif]-->
				<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: <?php echo esc_attr( $settings['width'] ); ?>;">

					<!-- start footer text -->
					<tr>
						<td align="center" bgcolor="<?php echo esc_attr( $settings['background_color'] ); ?>" style="padding: 12px 24px; font-size: 0.8em; line-height: 20px; color: <?php echo esc_attr( $settings['footer_text_color'] ); ?>;">
							<?php echo wp_kses_post( $footer ); ?>
						</td>
					</tr>
					<!-- end footer text -->

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
