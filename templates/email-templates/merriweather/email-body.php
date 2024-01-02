<?php

	defined( 'ABSPATH' ) || exit;

	/**
	 * @var array $settings
	 * @var string $content
	 */
?>
	<!-- start copy block -->
	<tr>
	  	<td align="center" bgcolor="<?php echo esc_attr( $settings['background_color'] ); ?>">
			<!--[if (gte mso 9)|(IE)]>
				<table align="center" border="0" cellpadding="0" cellspacing="0" width="<?php echo esc_attr( $settings['width'] ); ?>">
					<tr>
						<td align="center" valign="top" width="<?php echo esc_attr( $settings['width'] ); ?>">
			<![endif]-->
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: <?php echo esc_attr( $settings['width'] ); ?>;">

		 		<!-- start copy -->
		  		<tr>
					<td align="left" bgcolor="<?php echo esc_attr( $settings['content_background'] ); ?>" class="margin-none" style="padding: 24px;">
						<?php echo $content; ?>
					</td>
		 		</tr>
		  		<!-- end copy -->

			</table>
			<!--[if (gte mso 9)|(IE)]>
						</td>
					</tr>
				</table>
			<![endif]-->
	  	</td>
	</tr>
	<!-- end copy block -->
