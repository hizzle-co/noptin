<?php defined( 'ABSPATH' ) || exit; ?>
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

		<!-- footer -->
		<?php if ( ! empty( $footer ) ) : ?>
			<table class="footer-wrap" cellpadding="0" cellspacing="0" border="0" align="center">
				<tbody>
					<tr>
						<td></td>
						<td class="container">
							<!-- content -->
								<div class="content">
									<table>
										<tbody>
											<tr>
												<td align="center" valign="top">
													<?php echo wp_kses_post( $footer ); ?>
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
		<?php endif; ?>

		<!-- end footer -->

		</td>
    	</tr>
    </tbody></table>

</body>
</html>
