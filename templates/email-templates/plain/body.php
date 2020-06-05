		<!-- body -->
		
		<?php echo $tracker; ?>

		<table class="body-wrap">
            <tbody>
				<tr>
            		<td></td>
            		<td class="container" bgcolor="#FFFFFF" valign="top">
						<!-- content -->
						<div class="content">
                			<table>
                				<tbody>
									<tr>
										<td>
										<?php if ( ! empty( $hero_text ) ) { ?>
	
											<!-- start hero -->
												<h1><?php echo $hero_text; ?></h1>
											<!-- end hero -->

										<?php } ?>

										<!-- start copy -->
											<?php echo wpautop( $email_body ); ?>
										<!-- end copy -->

										<?php if ( ! empty( $cta_url ) ) { ?>

											<!-- start button -->
											<table>
												<tr>
													<td align="left" bgcolor="#ffffff">
														<table border="0" cellpadding="0" cellspacing="0" width="100%">
															<tr>
																<td align="center" bgcolor="#ffffff" style="padding: 12px;">
																	<table border="0" cellpadding="0" cellspacing="0">
																		<tr>
																			<td align="center" style="border-radius: 6px;">
																				<div style='text-align: center; padding: 20px;' align='center'>
																					<a href="<?php echo $cta_url; ?>" target="_blank" style="background: #1a82e2; display: inline-block; padding: 16px 36px; font-size: 16px; color: #ffffff; text-decoration: none; border-radius: 6px;"><?php echo $cta_text; ?></a>
																				</div>
																			</td>
																		</tr>
																	</table>
																</td>
															</tr>
														</table>
													</td>
												</tr>
											</table>
											<!-- end button -->

										<?php } ?>

										<?php if ( ! empty( $after_cta_text ) ) { ?>
											<?php echo wpautop( $after_cta_text ); ?>
										<?php } ?>

										<?php if ( ! empty( $after_cta_text2 ) ) { ?>
											<?php echo wpautop( $after_cta_text2 ); ?>
										<?php } ?>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<!-- /content -->
					</td>
					<td></td>
				</tr>
			</tbody>
		</table>
		<!-- /body -->
