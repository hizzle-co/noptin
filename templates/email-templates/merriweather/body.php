	<?php echo $tracker; ?>

	<?php if ( ! empty( $hero_text ) ) { ?>
	
		<!-- start hero -->
		<tr>
			<td align="center" bgcolor="#D2C7BA">
				<!--[if (gte mso 9)|(IE)]>
				<table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
				<tr>
				<td align="center" valign="top" width="600">
				<![endif]-->
				<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">
					<tr>
						<td align="left" bgcolor="#ffffff" style="padding: 36px 24px 0; font-family: 'Merriweather Bold', serif; border-top: 5px solid #69BCB1;">
							<h1 style="margin: 0; font-size: 32px; font-weight: 700; letter-spacing: -1px; line-height: 48px;"><?php echo $hero_text; ?></h1>
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
		<!-- end hero -->

	<?php } ?>

	<!-- start copy block -->
	<tr>
	  	<td align="center" bgcolor="#D2C7BA">
			<!--[if (gte mso 9)|(IE)]>
				<table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
					<tr>
						<td align="center" valign="top" width="600">
			<![endif]-->
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;">

		 		<!-- start copy -->
		  		<tr>
					<td align="left" bgcolor="#ffffff" class="margin-none" style="padding: 24px; font-family: 'Merriweather', serif; font-size: 16px; line-height: 24px; <?php if ( empty( $hero_text )) { echo 'border-top: 5px solid #69BCB1;'; } ?>">
						<?php echo wpautop( $email_body ); ?>
					</td>
		 		 </tr>
		  		<!-- end copy -->

				<?php if ( ! empty( $cta_url ) ) { ?>

					<!-- start button -->
						<tr>
							<td align="left" bgcolor="#ffffff">
								<table border="0" cellpadding="0" cellspacing="0" width="100%">
									<tr>
										<td align="center" bgcolor="#ffffff" style="padding: 12px;">
											<table border="0" cellpadding="0" cellspacing="0">
												<tr>
													<td align="center" bgcolor="#CC7953" style="border-radius: 6px;">
														<div style='text-align: left; padding: 20px;' align='left'>
															<a class="cta-link" href="<?php echo $cta_url; ?>" target="_blank" style="padding: 16px 36px; font-family: 'Merriweather', serif; font-size: 16px; color: #ffffff; text-decoration: none; border-radius: 6px; display: inline-block;"><?php echo $cta_text; ?></a>
														</div>
													</td>
												</tr>
											</table>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					<!-- end button -->

				<?php } ?>

				<?php if ( ! empty( $after_cta_text ) ) { ?>

					<!-- start copy -->
		  				<tr>
							<td align="left" bgcolor="#ffffff" style="padding: 0 24px; font-family: 'Merriweather', serif; font-size: 16px; line-height: 24px;">
								<?php echo wpautop( $after_cta_text ); ?>
							</td>
						</tr>
					<!-- end copy -->

				<?php } ?>

				<?php if ( ! empty( $after_cta_text2 ) ) { ?>

					<!-- start copy -->
						<tr>
							<td align="left" bgcolor="#ffffff" style="padding: 0 24px; font-family: 'Merriweather', serif; font-size: 16px; line-height: 24px; border-bottom: 5px solid #69BCB1">
								<?php echo wpautop( $after_cta_text2 ); ?>
							</td>
						</tr>
					<!-- end copy -->

				<?php } ?>

			</table>
			<!--[if (gte mso 9)|(IE)]>
						</td>
					</tr>
				</table>
			<![endif]-->
	  	</td>
	</tr>
	<!-- end copy block -->
