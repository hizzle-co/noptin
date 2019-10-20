<div id="content">
	<form method="post" action="<?php echo get_noptin_automation_campaign_url( $id ); ?>" class="noptin-newsletter-campaign-form noptin-fields">

		<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>"/>

		<?php wp_nonce_field( 'noptin_campaign' );  ?>

		<div id="poststuff">
			<div> <!-- <div class="postbox" > -->

                <h3><?php _e( 'Edit Automation:', 'newsletter-optin-box'); ?></h3>
				<hr/>

				<div>
					<table class="form-table" id="noptin-addedit-automation-campaign">

					<?php
						/**
        				 * Fires before printing the first row in the automation campaign editor
        				 *
        				 * @param object $campaign current campaign object
        				 */
        				do_action('noptin_before_automation_editor_fields', $campaign );
					?>

					<tr>
						<th>
							<label><b><?php _e( 'Send To:', 'newsletter-optin-box' ); ?></b></label>
						</th>
						<td>
							<?php

								//Load thickbox https://codex.wordpress.org/Javascript_Reference/ThickBox
								add_thickbox();

								//Filter subscribers here
								$text = __( 'All Subscribers', 'newsletter-optin-box' );
							?>
							<p class="description"><?php echo $text; ?> &mdash; <a href="#TB_inline?&width=600&height=550&inlineId=noptin-recipients-filter" class="thickbox">Filter recipients</a></p>
						</td>
					</tr>

					<tr>
						<th>
							<label for="noptin-email-subject"><b><?php _e( 'Email Subject:', 'newsletter-optin-box' ); ?></b></label>
						</th>
						<td>
							<?php $subject = is_object( $campaign ) ? $campaign->post_title : ''; ?>
							<input type="text" name="email_subject" id="noptin-email-subject" class="noptin-campaign-input" value="<?php echo esc_attr( $subject ); ?>">
						</td>
					</tr>

					<tr>
						<th>
							<label for="noptin-email-body"><b><?php _e( 'Email Body:', 'newsletter-optin-box' ); ?></b></label>
						</th>
						<td>
							<?php
								$body = is_object( $campaign ) ? stripslashes( $campaign->post_content ) : '';

									wp_editor(
										$body,
										'noptin-email-body',
										array(
											'media_buttons' => true,
											'textarea_rows' => 15,
											'tabindex' => 4,
											'tinymce'  => array(
												'theme_advanced_buttons1' => 'bold,italic,underline,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,|,formatselect,styleselect',
											),
										)
									);

							?>
						</td>
					</tr>

					<tr>
						<th></th>
						<td>
							<input type="submit" name="publish" class="button-primary" value="<?php echo 'publish' == $campaign->post_status ? 'Save Changes' : 'Publish' ?>"/>
							<input type="submit" name="draft" class="button-secondary" value="<?php echo 'publish' == $campaign->post_status ? 'Switch to Draft' : 'Save Changes' ?>"/>
						</td>
					</tr>

					<?php
						/**
        				 * Fires after printing the last row in the automation campaign editor
        				 *
        				 * @param object $campaign current campaign object
        				 */
        				do_action('noptin_after_automation_editor_fields', $campaign) ;
					?>

					</table>
				</div>
			</div>
		</div>

				<div id="noptin-recipients-filter" style="display:none;">
     				<p>
						<?php

							$filters = array(
								'all'		=> __( 'All subscribers', 'newsletter-optin-box'),
								'only'		=> __( 'Signed up via', 'newsletter-optin-box'),
								'except'	=> __( 'Did not sign Up via', 'newsletter-optin-box'),
							);

							/**
        					 * Fires when printing the campaign recipients filter
        					 *
        					 * @param object $campaign current campaign object
        					 */
        					do_action('noptin_automation_campaign_recipients', $campaign) ;
						?>
     				</p>
				</div>
			</form>
		</div>
