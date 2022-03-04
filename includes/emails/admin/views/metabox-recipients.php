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
				<?php do_action( "noptin_sender_options_$_sender", $campaign ); ?>
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
