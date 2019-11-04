<div id="noptin-automations-popup">
	<h2>Select Automation Type</h2>

	<ul>
		<?php

			$counter = 1;

			foreach( $triggers as $trigger => $args ) {
				$disabled = empty( $args['setup_cb'] );
				$outer_tag= $disabled ? 'div' : 'a';
				$class	  = $disabled ? 'disabled' : 'enabled';
				$style    = "--noptin-automation-select-order: $counter;";
				$counter++;
		?>
			<li style="<?php echo $style; ?>">
				<<?php echo $outer_tag; ?> href="#" class="noptin-automation-type-select <?php echo $class; ?>">
					<h3><?php echo $args['title'];?></h3>
					<div style="flex: 1 0 0;"><?php echo $args['description'];?></div>

					<?php
						if( $disabled ) {

							$string   = __( "Install Addon", 'newsletter-optin-box' );
							$disabled = __( "Disabled", 'newsletter-optin-box' );
							echo "<div>
									<span class='button button-disabled'>$disabled</span>
									<a class='button button-link' href='https://noptin.com/product/ultimate-addons-pack/'>$string</a>
								</div>";

						} else {
							?>

							<div><span class="button button-primary"><?php _e( "Set Up", 'newsletter-optin-box' );?></span></div>
							<div class='noptin-automation-type-setup-form' style="display:none">
							<form class="noptin-automation-setup-form noptin-fields">

								<h3 style="margin: 6px 0;"><?php echo $args['title'];?></h3>
								<div><?php echo $args['description'];?></div>
								<?php wp_nonce_field( 'noptin_campaign' );?>
								<input type="hidden" name="automation_type" value="<?php echo esc_attr( $trigger );?>" />
								<table class="form-table noptin-create-new-automation-campaign">
								<?php

									/**
    								 * Runs before displaying automation settings
    								 *
    								 */
									do_action( 'noptin_before_display_automation_settings', $trigger, $args );

									$automation_name = esc_attr( $args['title'] );
									?>
									<tr>

										<th>
											<label><b><?php _e( 'Automation Name', 'newsletter-optin-box' ); ?></b></label>
										</th>

										<td>
											<input type="text" name="automation_name" class="noptin-campaign-input" value="<?php echo $automation_name; ?>">
											<p class="description">This name helps you identify this automation and is not visible to your subscribers.</p>
										</td>

									</tr>

									<?php

										if( is_callable( $args['setup_cb'] ) ) {
											call_user_func( $args['setup_cb'], $trigger, $args );
										}

									if(! empty( $args['support_delay'] ) ) {

										$label = __( 'Delay', 'newsletter-optin-box' );
										if( is_string( $args['support_delay'] ) ) {
											$label = __( 'Sends', 'newsletter-optin-box' );
										}

										?>
										<tr>

											<th>
												<label><b><?php echo $label; ?></b></label>
											</th>

											<td>
												<input style="width:100px" type="number" name="automation_delay"  value="0">

												<select class="noptin-max-w-200" name="automation_delay_unit">
													<option value="MINUTE" selected="selected">Minute(s)</option>
													<option value="HOUR">Hour(s)</option>
													<option value="DAY">Day(s)</option>
												</select>

												<?php

													if( is_string( $args['support_delay'] ) ) {
														echo "<span class='description'>{$args['support_delay']}</span>";
													}

											echo "</td></tr>";

									}

									/**
    								 * Runs after displaying automation settings
    								 *
    								 */
									do_action( 'noptin_after_display_automation_settings', $trigger, $args );
								?>
							</table>
						</div>

						</form>

			  		<?php	} ?>

				</<?php echo $outer_tag; ?>>
			</li>
		<?php } ?>
	</ul>
</div>
