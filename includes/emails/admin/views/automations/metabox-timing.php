<?php

defined( 'ABSPATH' ) || exit;

/**
 * @var Noptin_Automated_Email $campaign
 */

$senders = get_noptin_email_senders();
$unit    = $campaign->get_sends_after_unit();
?>

<p>
	<label>
		<strong class="noptin-label-span"><?php esc_html_e( 'Timing', 'newsletter-optin-box' ); ?></strong>
		<select name="noptin_email[when_to_run]" id="noptin-automated-email-when-to-run" class="widefat">
			<option value="immediately" <?php selected( true, $campaign->sends_immediately() ); ?>><?php esc_html_e( 'Sends immediately', 'newsletter-optin-box' ); ?></option>
			<option value="delayed" <?php selected( false, $campaign->sends_immediately() ); ?>><?php esc_html_e( 'Sends after a delay', 'newsletter-optin-box' ); ?></option>
		</select>
	</label>
</p>

<p class="noptin-automation-delay-wrapper" style="display: <?php echo $campaign->sends_immediately() ? 'none' : 'block'; ?>;">
	<label>
		<strong class="noptin-label-span"><?php esc_html_e( 'Length of the delay', 'newsletter-optin-box' ); ?></strong>
		<input type="number" style="width: 80px;" name="noptin_email[sends_after]" value="<?php echo esc_attr( $campaign->get_sends_after() ); ?>" min="0">
		<select name="noptin_email[sends_after_unit]">
			<?php foreach ( get_noptin_email_delay_units() as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $unit ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</label>
</p>
