<?php
/**
 * Displays the welcome email tab in the form editor.
 *
 * @var Noptin_Form $form
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
// TODO: Attachments (premium), double opt-in email.
$email_settings = $form->email;
?>

<h2 class="screen-reader-text"><?php esc_html_e( 'Welcome Email', 'newsletter-optin-box' ); ?></h2>

<p class="description" style="margin-bottom: 16px;"><?php esc_html_e( 'This email is sent to new subscribers who sign-up via this form. If double opt-in is enabled, then the email will be sent after a subscriber confirms their email address.', 'newsletter-optin-box' ); ?></p>

<table class="form-table noptin-form-settings">

	<tr valign="top" class="form-field-row form-field-row-enable-welcome-email">
		<th scope="row">
			<label for="noptin-form-enable-welcome-email"><?php esc_html_e( 'Enable Email', 'newsletter-optin-box' ); ?></label>
		</th>
		<td>
			<?php noptin_hidden_field( 'noptin_form[email][enable_email]', 0 ); ?>
			<label>
				<input type="checkbox" id="noptin-form-enable-welcome-email" name="noptin_form[email][enable_email]" value="1" <?php checked( ! empty( $email_settings['enable_email'] ) ); ?>/>
				<span class="description"><?php esc_html_e( 'Send new subscribers this welcome email.', 'newsletter-optin-box' ); ?></span>
			</label>
		</td>
	</tr>

	<tr valign="top" class="form-field-row form-field-row-welcome-email-subject">
		<th scope="row">
			<label for="noptin-form-welcome-email-subject"><?php esc_html_e( 'Email Subject', 'newsletter-optin-box' ); ?></label>
		</th>
		<td>
			<input type="text" class="widefat" id="noptin-form-welcome-email-subject" name="noptin_form[email][subject]" value="<?php echo isset( $email_settings['subject'] ) ? esc_url( $email_settings['subject'] ) : ''; ?>" placeholder="<?php esc_attr_e( 'Example: Thanks for subscribing', 'newsletter-optin-box' ); ?>" />
		</td>
	</tr>

	<tr valign="top" class="form-field-row form-field-row-welcome-email-content">
		<th scope="row">
			<label for="noptin-form-welcome-email-content"><?php esc_html_e( 'Email Content', 'newsletter-optin-box' ); ?></label>
		</th>
		<td>
			<?php

				wp_editor(
					isset( $email_settings['content'] ) ? wp_unslash( $email_settings['content'] ) : '',
					'noptin-form-welcome-email-content',
					array(
						'media_buttons'    => true,
						'drag_drop_upload' => true,
						'textarea_rows'    => 15,
						'textarea_name'    => 'noptin_form[email][content]',
						'tabindex'         => 4,
						'tinymce'          => array(
							'theme_advanced_buttons1' => 'bold,italic,underline,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,|,formatselect,styleselect',
						),
					)
				);

			?>
		</td>
	</tr>
</table>
