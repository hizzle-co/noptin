<?php

defined( 'ABSPATH' ) || exit;

/**
 * @var Noptin_Automated_Email $campaign
 */

$email_type = $campaign->get_email_type();
$template = $campaign->get_template();

?>

<style>
	#noptin_email_details .dashicons {
		width: 14px;
		height: 14px;
		font-size: 14px;
		vertical-align: middle;
		color: #767676;
	}

	.noptin-edit-email:not([data-type="normal"]) #noptin_email_details { display: none !important; }

</style>

<table class="form-table" role="presentation" style="margin-top: 1.5em;">
	<tbody>

		<tr>
			<th scope="row">
				<label for="noptin-automated-email-template"><?php _e( 'Email Template', 'newsletter-optin-box' ); ?></label>
			</th>
			<td>
				<select name="noptin_email[template]" id="noptin-automated-email-template" class="widefat">
					<?php foreach ( get_noptin_email_templates() as $key => $label ) : ?>
						<option <?php selected( $key, $template ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="noptin-automated-email-heading"><?php _e( 'Email Heading', 'newsletter-optin-box' ); ?></label>
				<span title="<?php esc_attr_e( 'This is shown at the top of the email', 'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info-outline"></span>
			</th>
			<td>
				<input type="text" id="noptin-automated-email-heading" name="noptin_email[heading]" value="<?php echo esc_attr( $campaign->get( 'heading' ) ); ?>" class="widefat">
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="noptin-email-preview-text"><?php _e( 'Preview Text', 'newsletter-optin-box' ); ?></label>
				<span title="<?php esc_attr_e( 'Some email clients display this text next to the email subject.', 'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info-outline"></span>
			</th>
			<td>
				<input type="text" id="noptin-email-preview-text" name="noptin_email[preview_text]" value="<?php echo esc_attr( $campaign->get( 'preview_text' ) ); ?>" class="widefat">
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="noptin-automated-email-permission-text"><?php _e( 'Footer Text', 'newsletter-optin-box' ); ?></label>
				<span title="<?php esc_attr_e( 'Appears at the bottom of your email template', 'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info-outline"></span>
			</th>
			<td>
				<textarea id="noptin-automated-email-permission-text" name="noptin_email[footer_text]" class="widefat" placeholder="<?php echo esc_attr( get_noptin_footer_text() ); ?>" rows="4"><?php echo esc_textarea( $campaign->get( 'footer_text' ) ); ?></textarea>
			</td>
		</tr>

	</tbody>
</table>
