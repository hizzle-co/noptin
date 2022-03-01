<?php

defined( 'ABSPATH' ) || exit;

/**
 * @var Noptin_Automated_Email|Noptin_Newsletter_Email $campaign
 */

$senders = get_noptin_email_senders();
?>

<?php if ( $campaign->is_mass_mail() ) : ?>
	<div class="noptin-select-email-sender senders-<?php echo count( $senders ); ?>">

		<label style="width:100%;" class="noptin-margin-y noptin-email-senders-label">
			<strong><?php _e( 'Send To', 'newsletter-optin-box' ); ?></strong>
			<select name="noptin_email[email_sender]" class="noptin-email_sender" style="display:block; width:100%;">
				<?php foreach ( $senders as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $campaign->get_sender() ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>

		<?php foreach ( array_keys( $senders ) as $_sender ) : ?>
			<div class="noptin-sender-options noptin-margin-y sender-<?php echo esc_attr( $_sender ); ?>" style="display:<?php echo $_sender == $campaign->get_sender() ? 'block' : 'none'; ?>;">
				<?php

					// Allow otherr plugins to register their options.
					do_action( "noptin_sender_options_$_sender", $campaign );

					// Filter recipients by custom fields.
					if ( 'noptin' === $_sender ) {

						foreach ( get_noptin_custom_fields() as $custom_field ) {

							if ( empty( $custom_field['options'] ) ) {
								$custom_field['options'] = '';
							}

							// Checkbox
							if ( 'checkbox' === $custom_field['type'] ) {

								$options = array(
									''  => __( 'Any', 'newsletter-optin-box' ),
									'1' => __( 'Yes', 'newsletter-optin-box' ),
									'0' => __( 'No', 'newsletter-optin-box' ),
								);

							}

							// Select.
							else if ( 'dropdown' === $custom_field['type'] ) {

								$options = array(
									'' => __( 'Any', 'newsletter-optin-box' ),
								);

								foreach ( explode( "\n", $custom_field['options'] ) as $option ) {
									$options[ $option ] = $option;
								}

							}

							// Radio.
							else if ( 'radio' === $custom_field['type'] ) {

								$options = array(
									'' => __( 'Any', 'newsletter-optin-box' ),
								);

								foreach ( explode( "\n", $custom_field['options'] ) as $option ) {
									$options[ $option ] = $option;
								}

							}

							// Abort.
							else {
								continue;
							}

							?>

								<label style="width:100%;" class="noptin-margin-y">
									<strong><?php echo wp_kses_post( $custom_field['label'] ); ?></strong>
									<select name="noptin_email[noptin_custom_field_<?php echo esc_attr( $custom_field['merge_tag'] ); ?>]" style="display:block; width:100%;">
										<?php foreach ( $options as $key => $label ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $campaign->get( 'noptin_custom_field_' . $custom_field['merge_tag'] ) ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</label>

							<?php
						}

					}

				?>
			</div>
		<?php endforeach; ?>

	</div>
<?php else: ?>

	<label style="width:100%;" class="noptin-margin-y noptin-email-recipients-label">
		<strong><?php _e( 'Recipient(s)', 'newsletter-optin-box' ); ?></strong>
		<input type="text" id="noptin-automated-email-recipients" name="noptin_email[recipients]" value="<?php echo esc_attr( $campaign->get_recipients() ); ?>" placeholder="<?php echo esc_attr( $campaign->get_placeholder_recipient() ); ?>" class="noptin-admin-field-big" required>
	</label>
	<p class="description"><?php _e( "Enter recipients (comma-separated) for this email. <br> Add <b>--notracking</b> after an email to disable open and click tracking for that recipient.", 'newsletter-optin-box' ); ?></p>

<?php endif; ?>
