<?php

defined( 'ABSPATH' ) || exit;

/**
 * @var Noptin_Automated_Email|Noptin_Newsletter_Email $campaign
 */

$recipient_ids = $campaign->get_manual_recipients_ids();
$senders       = get_noptin_email_senders();
$sender        = $campaign->get_sender();
?>

<?php if ( ! empty( $recipient_ids ) ) : ?>

	<input type="hidden" name="noptin_email[email_sender]" value="<?php echo esc_attr( $sender ); ?>">

	<div class="noptin-manual-email-recipients">
		<?php foreach ( $recipient_ids as $recipient_id ) : ?>
			<?php
				$recipient = get_noptin_email_recipient( $recipient_id, $sender );

				if ( empty( $recipient ) ) {
					continue;
				}

				$recipient_name = empty( $recipient['name'] ) ? $recipient['email'] : $recipient['name'] . ' <' . $recipient['email'] . '>';
			?>
			<div class="noptin-manual-email-recipient">
				<input type="hidden" name="noptin_email[manual_recipients_ids][]" value="<?php echo esc_attr( $recipient_id ); ?>">
				<?php echo wp_kses_post( get_avatar( $recipient['email'], 32 ) ); ?>
				<div class="noptin-manual-email-recipient-name-email">

					<?php if ( ! empty( $recipient['name'] ) ) : ?>
						<div class="noptin-manual-email-recipient-name"><?php echo esc_html( $recipient['name'] ); ?></div>
					<?php endif; ?>

					<div class="noptin-manual-email-recipient-email">
						<?php echo esc_html( $recipient['email'] ); ?>
					</div>
				</div>

				<?php if ( ! empty( $recipient['url'] ) ) : ?>
					<a href="<?php echo esc_url( $recipient['url'] ); ?>" target="_blank" class="noptin-manual-email-recipient-view dashicons dashicons-visibility"></a>
				<?php endif; ?>

				<a href="#" class="noptin-manual-email-recipient-remove dashicons dashicons-no-alt"></a>
			</div>
		<?php endforeach; ?>
	</div>

<?php elseif ( $campaign->is_mass_mail() ) : ?>

	<div class="noptin-select-email-sender senders-<?php echo count( $senders ); ?>">

		<label style="width:100%;" class="noptin-margin-y noptin-email-senders-label">
			<strong><?php esc_html_e( 'Send To', 'newsletter-optin-box' ); ?></strong>
			<select name="noptin_email[email_sender]" class="noptin-email_sender" style="display:block; width:100%;">
				<?php foreach ( $senders as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $campaign->get_sender() ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>

		<?php foreach ( array_keys( $senders ) as $_sender ) : ?>
			<div class="noptin-sender-options noptin-margin-y sender-<?php echo esc_attr( $_sender ); ?>" style="display:<?php echo $_sender === $campaign->get_sender() ? 'block' : 'none'; ?>;">
				<?php do_action( "noptin_sender_options_$_sender", $campaign ); ?>
			</div>
		<?php endforeach; ?>

	</div>

<?php else : ?>

	<label style="width:100%;" class="noptin-margin-y noptin-email-recipients-label">
		<strong><?php esc_html_e( 'Recipient(s)', 'newsletter-optin-box' ); ?></strong>
		<input type="text" id="noptin-automated-email-recipients" name="noptin_email[recipients]" value="<?php echo esc_attr( $campaign->get_recipients() ); ?>" placeholder="<?php echo esc_attr( $campaign->get_placeholder_recipient() ); ?>" class="noptin-admin-field-big" required>
	</label>
	<?php noptin_email_display_merge_tags_text( __( 'Enter recipients (comma-separated) for this email. <br> Add <b>--notracking</b> after an email to disable open and click tracking for that recipient.', 'newsletter-optin-box' ) . '<br>' ); ?>

<?php endif; ?>
