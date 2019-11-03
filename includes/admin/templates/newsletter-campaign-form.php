<div id="content">
	<form method="post" action="<?php echo get_noptin_new_newsletter_campaign_url(); ?>" class="noptin-newsletter-campaign-form noptin-fields">

		<input type="hidden" name="noptin-action" value="save-newsletter-campaign"/>

		<?php if(! empty( $id ) ) { ?>
			<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>"/>
		<?php } ?>

		<?php

			wp_nonce_field( 'noptin_campaign', 'noptin_campaign_nonce' );

			$display_message   = __( 'Add New Email Campaign:', 'newsletter-optin-box');
			if ( ! empty( $id ) ) {
				$display_message   = __( 'Edit Email Campaign:', 'newsletter-optin-box');
			}
		?>

		<div id="poststuff">
			<div> <!-- <div class="postbox" > -->

                <h3><?php echo $display_message; ?></h3>
				<hr/>

				<div>
					<table class="form-table" id="noptin-addedit-newsletter-campaign">

					<?php
						/**
        				 * Fires before printing the first row in the newsletter campaign editor
        				 *
        				 * @param object $campaign current campaign object
        				 */
        				do_action('noptin_before_newsletter_editor_fields', $campaign );
					?>

					<tr>
						<th>
							<label><b><?php _e( 'Send To:', 'newsletter-optin-box' ); ?></b></label>
						</th>
						<td>
							<?php $text = __( 'All Subscribers', 'newsletter-optin-box' ); ?>
							<p class="description"><?php echo $text; ?> &mdash; <a href="#" class="noptin-filter-recipients">Filter recipients</a></p>
						</td>
					</tr>

					<tr>
						<th>
							<label for="noptin-email-subject"><b><?php _e( 'Email Subject:', 'newsletter-optin-box' ); ?></b></label>
						</th>
						<td>
							<?php $subject = is_object( $campaign ) ? $campaign->post_title : get_noptin_default_newsletter_subject(); ?>
							<input style="max-width: 100%;" type="text" name="email_subject" id="noptin-email-subject" class="noptin-campaign-input" value="<?php echo esc_attr( $subject ); ?>" placeholder="<?php esc_attr_e( "Enter your email's subject", 'newsletter-optin-box' ); ?>">
						</td>
					</tr>

					<tr>
						<th>
							<label for="noptin-email-preview">
								<b><?php _e( 'Preview Text:', 'newsletter-optin-box' ); ?></b>
								<span title="<?php esc_attr_e( 'Some email clients display this text next to the subject.',  'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info"></span>
							</label>
						</th>
						<td>
							<?php $preview = is_object( $campaign ) ? get_post_meta( $campaign->ID, 'preview_text', true ) : get_noptin_default_newsletter_preview_text(); ?>
							<input style="max-width: 100%;" type="text" name="preview_text" id="noptin-email-preview" class="noptin-campaign-input" value="<?php echo esc_attr( $preview ); ?>" placeholder="<?php esc_attr_e( "Enter the text shown next to the subject", 'newsletter-optin-box' );?>" >
						</td>
					</tr>

					<tr>
						<th>
							<label for="noptin-email-body"><b><?php _e( 'Email Body:', 'newsletter-optin-box' ); ?></b></label>
						</th>
						<td>
							<?php
								$body = is_object( $campaign ) ? stripslashes( $campaign->post_content ) : get_noptin_default_newsletter_body();

									wp_editor(
										$body,
										'noptinemailbody',
										array(
											'media_buttons'    => true,
											'drag_drop_upload' => true,
											'textarea_rows'    => 15,
											'textarea_name'	   => 'email_body',
											'quicktags'		   => false,
											'tabindex'         => 4,
											'tinymce'          => array(
												'theme_advanced_buttons1' => 'bold,italic,underline,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,|,formatselect,styleselect',
											),
										)
									);

							?>
						</td>
					</tr>

					<tr>
						<th>
							<label for="noptin-email-schedule">
								<b><?php _e( 'Send This Campaign', 'newsletter-optin-box' ); ?></b>
								<span title="<?php esc_attr_e( 'Enter zero to send this campaign as soon as it is published.',  'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info"></span>
							</label>
						</th>
						<td>
							<?php $schedule = empty( $id ) ? '0' : get_post_meta( $id, 'noptin_sends_after', true ); ?>
							<input style="width:100px" min="0" type="number" name="noptin-email-schedule" id="noptin-email-schedule"  value="<?php echo esc_attr( $schedule );?>">

							<select class="noptin-max-w-200" name="noptin-email-schedule-unit">
								<?php
									$unit  = empty( $id ) ? 'minutes' : get_post_meta( $id, 'noptin_sends_after_unit', true );
									$units = array(
										'minutes' => 'Minute(s)',
										'hours'   => 'Hour(s)',
										'days'    => 'Day(s)',
									);

									foreach ( $units as $key => $value ) {
										printf(
											"<option %s value='%s'>%s</option>\n",
											selected( $key, $unit, false ),
											esc_attr( $key ),
											esc_html( $value ),
										);
									}
								?>
							</select>
							<span class="description"><?php _e( 'after it is published.', 'newsletter-optin-box' ); ?></span>
						</td>
					</tr>

					<?php
						/**
        				 * Fires after printing the last row in the newsletter campaign editor
        				 *
        				 * @param object $campaign current campaign object
        				 */
        				do_action('noptin_after_newsletter_editor_fields', $campaign) ;
					?>

					<tr>
						<th></th>
						<td>
							<input type="submit" name="publish" class="button-primary" value="<?php echo is_object($campaign) && 'draft' != $campaign->post_status ? 'Save Changes' : 'Publish' ?>"/>
							<input type="submit" name="draft" class="button-link" value="<?php echo is_object($campaign) && 'draft' != $campaign->post_status ? 'Switch to Draft' : 'Save as draft' ?>"/>
						</td>
					</tr>

					</table>
				</div>
			</div>
		</div>
	</form>
</div>
