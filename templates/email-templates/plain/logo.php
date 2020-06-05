<?php if ( ! empty( $logo_url ) ) { ?>
	<!-- start logo -->
		<table class="body-wrap">
			<tbody>
				<tr>
					<td></td>
					<td class="container" bgcolor="#FFFFFF" valign="top" style="padding-bottom: 0;">
						<div class="content">
							<table>
								<tbody>
									<tr>
										<td>
											<p style="text-align: center;"><a href="[[home_url]]" target="_blank" style="display: inline-block;"><img src="<?php echo esc_url( $logo_url ); ?>" border="0" style="display: block; height: auto; max-height: 200px; width: auto; max-width: 100%; min-width: 48px;"></a></p>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	<!-- end logo -->
<?php } ?>
