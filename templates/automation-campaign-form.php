<div id="content">
	<form method="post" action="<?php echo get_noptin_automation_campaign_url( $campaign_id ); ?>" class="noptin-automation-campaign-form noptin-fields">

		<input type="hidden" name="noptin-action" value="save-automation-campaign"/>
		<input type="hidden" name="id" value="<?php echo esc_attr( $campaign_id ); ?>"/>
		<input type="hidden" name="campaign_type" value="automation"/>
		<?php wp_nonce_field( 'noptin_campaign', 'noptin_campaign_nonce' ); ?>

		<div id="poststuff">
			<div>

				<h3><?php _e( 'Edit Automation Campaign', 'newsletter-optin-box' ); ?> &mdash; <?php echo sanitize_text_field( $campaign->post_title ); ?></h3>
				<hr/>

				<div>
					<table class="form-table" id="noptin-edit-automation-campaign">

					<?php
						/**
						 * Fires before printing the first row in the automation campaign editor
						 *
						 * @param object $campaign current campaign object
						 * @param string $automation_type the automation type
						 */
						do_action( 'noptin_before_automation_editor_fields', $campaign, $automation_type );
					?>
<!--
					<?php if ( $supports_filter ) { ?>

						<tr>
							<th>
								<label><b><?php _e( 'Sends To:', 'newsletter-optin-box' ); ?></b></label>
							</th>
							<td>
								<?php $text = __( 'All Subscribers', 'newsletter-optin-box' ); ?>
								<p class="description"><?php echo $text; ?> &mdash; <a href="#" class="noptin-filter-recipients"><?php _e( 'Filter recipients', 'newsletter-optin-box' ); ?></a></p>
							</td>
						</tr>

					<?php } ?>
-->
					<tr>
						<th>
							<label for="noptin-email-subject"><b><?php _e( 'Email Subject:', 'newsletter-optin-box' ); ?></b></label>
						</th>
						<td>
							<input style="max-width: 100%;" type="text" name="subject" id="noptin-email-subject" class="noptin-campaign-input" value="<?php echo esc_attr( $subject ); ?>" placeholder="<?php esc_attr_e( "Enter your email's subject", 'newsletter-optin-box' ); ?>">
						</td>
					</tr>

					<tr>
						<th>
							<label for="noptin-email-preview">
								<b><?php _e( 'Preview Text:', 'newsletter-optin-box' ); ?></b>
								<span title="<?php esc_attr_e( 'Some email clients display this text next to the subject.', 'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info"></span>
							</label>
						</th>
						<td>
							<input style="max-width: 100%;" type="text" name="preview_text" id="noptin-email-preview" class="noptin-campaign-input" value="<?php echo esc_attr( $preview_text ); ?>" placeholder="<?php esc_attr_e( 'Enter the text shown next to the subject', 'newsletter-optin-box' ); ?>" >
						</td>
					</tr>

					<tr>
						<th>
							<label for="noptin-email-body"><b><?php _e( 'Email Body:', 'newsletter-optin-box' ); ?></b></label>
						</th>
						<td>
							<?php

									wp_editor(
										$email_body,
										'noptinautomationemailbody',
										array(
											'media_buttons' => true,
											'drag_drop_upload' => true,
											'textarea_rows' => 15,
											'textarea_name' => 'email_body',
											'tabindex' => 4,
											'tinymce'  => array(
												'theme_advanced_buttons1' => 'bold,italic,underline,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,|,formatselect,styleselect',
											),
										)
									);

									?>
						</td>
					</tr>

					<?php
					if ( isset( $automations[ $automation_type ]['setup_cb'] ) && is_callable( $automations[ $automation_type ]['setup_cb'] ) ) {
						call_user_func( $automations[ $automation_type ]['setup_cb'], $campaign );
					}
					?>

					<?php
						/**
						 * Fires after printing the last row in the automation campaign editor
						 *
						 * @param object $campaign current campaign object
						 * @param string $automation_type the automation type
						 */
						do_action( 'noptin_after_automation_editor_fields', $campaign, $automation_type );
					?>

					<tr>
						<th></th>
						<td>
							<input type="submit" name="publish" class="button-primary" value="<?php echo 'publish' === $campaign->post_status ? 'Save Changes' : 'Publish'; ?>"/>
							<input type="submit" name="draft" class="button-link" value="<?php echo 'publish' === $campaign->post_status ? 'Switch to Draft' : 'Save as draft'; ?>"/>
						</td>
					</tr>

					</table>
				</div>
			</div>
		</div>
	</form>
</div>
