<?php

defined( 'ABSPATH' ) || exit;

/**
 * @var Noptin_Automated_Email $campaign
 */

$email_type = $campaign->get_email_type();
$template = $campaign->get_template();

?>

<table class="form-table" role="presentation">
	<tbody>

		<tr class="noptin-is-conditional noptin-show-if-email-is-normal">
			<th scope="row">
				<label for="noptin-automated-email-template"><?php _e( 'Email Template', 'newsletter-optin-box' ); ?></label>
			</th>
			<td>
				<select name="noptin_email[template]" id="noptin-automated-email-template" class="noptin-admin-field-big">
					<?php foreach ( get_noptin_email_templates() as $key => $label ) : ?>
						<option <?php selected( $key, $template ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		
		<tr>
			<th scope="row">
				<label for="noptin-automated-email-subject"><?php _e( 'Email Subject', 'newsletter-optin-box' ); ?></label>
			</th>
			<td>
				<input type="text" id="noptin-automated-email-subject" name="noptin_email[subject]" value="<?php echo esc_attr( $campaign->get_subject() ); ?>" class="noptin-admin-field-big" required>
			</td>
		</tr>
		
		<tr class="noptin-is-conditional noptin-show-if-email-is-normal">
			<th scope="row">
				<label for="noptin-automated-email-heading"><?php _e( 'Email Heading', 'newsletter-optin-box' ); ?></label>
			</th>
			<td>
				<input type="text" id="noptin-automated-email-heading" name="noptin_email[heading]" value="<?php echo esc_attr( $campaign->get( 'heading' ) ); ?>" class="noptin-admin-field-big">
				<span title="<?php esc_attr_e( 'This is shown at the top of the email', 'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info-outline" style="vertical-align: middle; color: #767676;"></span>
			</td>
		</tr>

		<tr class="noptin-is-conditional noptin-show-if-email-is-normal">
			<th scope="row">
				<label for="noptin-email-preview-text"><?php _e( 'Preview Text', 'newsletter-optin-box' ); ?></label>
			</th>
			<td>
				<input type="text" id="noptin-email-preview-text" name="noptin_email[preview_text]" value="<?php echo esc_attr( $campaign->get( 'preview_text' ) ); ?>" class="noptin-admin-field-big">
				<span title="<?php esc_attr_e( 'Some email clients display this text next to the email subject.', 'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info-outline" style="vertical-align: middle; color: #767676;"></span>
			</td>
		</tr>

		<tr class="noptin-is-conditional noptin-show-if-email-is-normal">
			<th scope="row">
				<label for="noptin-automated-email-permission-text"><?php _e( 'Footer Text', 'newsletter-optin-box' ); ?></label>
			</th>
			<td>
				<textarea id="noptin-automated-email-permission-text" name="noptin_email[footer_text]" class="noptin-admin-field-big" placeholder="<?php echo esc_attr( get_noptin_footer_text() ); ?>" rows="4"><?php echo esc_textarea( $campaign->get( 'footer_text' ) ); ?></textarea>
				<span title="<?php esc_attr_e( 'Appears at the bottom of your email template', 'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info-outline" style="color: #767676;"></span>
			</td>
		</tr>

	</tbody>
</table>