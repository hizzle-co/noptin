<?php

defined( 'ABSPATH' ) || exit;

/**
 * @var Noptin_Automated_Email|Noptin_Newsletter_Email $campaign
 */

$email_type = $campaign->get_email_type();
?>

<table class="form-table" role="presentation" style="margin-top: 1.5em;">
	<tbody>

		<tr>
			<th scope="row">
				<label for="noptin-email-type"><?php esc_html_e( 'Email Type', 'newsletter-optin-box' ); ?></label>
			</th>
			<td>
				<select name="noptin_email[email_type]" id="noptin-email-type" class="widefat">
					<?php foreach ( get_noptin_email_types() as $key => $type ) : ?>
						<option <?php selected( $key, $email_type ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $type['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php foreach ( get_noptin_email_types() as $key => $type ) : ?>
					<p class="description noptin-is-conditional noptin-show-if-email-is-<?php echo esc_attr( $key ); ?>"><strong><?php echo esc_html( $type['label'] ); ?>:</strong> <?php echo wp_kses_post( $type['description'] ); ?></p>
				<?php endforeach; ?>
			</td>
		</tr>

		<tr class="noptin-is-conditional noptin-show-if-email-is-plain_text">
			<th scope="row">
				<label for="noptin-email-content-plain_text"><?php esc_html_e( 'Email Content', 'newsletter-optin-box' ); ?></label>
			</th>
			<td>
				<p><textarea name="noptin_email[content_plain_text]" id="noptin-email-content-plain_text" rows="15" class="widefat"><?php echo esc_textarea( $campaign->get_content( 'plain_text' ) ); ?></textarea></p>
				<p class="description">
					<?php
						printf(
							/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
							__( 'You can use %1$semail tags%2$s to personalize your email.', 'newsletter-optin-box' ),
							'<a href="https://noptin.com/guide/sending-emails/email-tags/">',
							'</a>'
						);
					?>
				</p>
			</td>
		</tr>

		<tr class="noptin-is-conditional noptin-show-if-email-is-raw_html">
			<th scope="row">
				<label for="noptin-email-content-raw_html"><?php esc_html_e( 'Email Content', 'newsletter-optin-box' ); ?></label>
			</th>
			<td>
				<p><textarea name="noptin_email[content_raw_html]" id="noptin-email-content-raw_html" rows="15" class="widefat"><?php echo esc_textarea( $campaign->get_content( 'raw_html' ) ); ?></textarea></p>
				<p class="description">
					<?php
						printf(
							/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
							__( 'You can use %1$semail tags%2$s to personalize your email.', 'newsletter-optin-box' ),
							'<a href="https://noptin.com/guide/sending-emails/email-tags/">',
							'</a>'
						);
					?>
				</p>
			</td>
		</tr>

		<tr class="noptin-is-conditional noptin-show-if-email-is-normal">
			<th scope="row">
				<label for="noptin-email-content"><?php esc_html_e( 'Email Content', 'newsletter-optin-box' ); ?></label>
			</th>
			<td>
				<?php
					wp_editor(
						wp_kses_post( $campaign->get_content( 'normal' ) ),
						'noptin-email-content',
						array(
							'media_buttons'    => true,
							'drag_drop_upload' => true,
							'textarea_rows'    => 15,
							'textarea_name'    => 'noptin_email[content_normal]',
							'tabindex'         => 4,
							'tinymce'          => array(
								'theme_advanced_buttons' => 'bold,italic,underline,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,|,formatselect,styleselect',
							),
						)
					);
				?>
				<p class="description">
					<?php
						printf(
							/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
							__( 'You can use %1$semail tags%2$s to personalize your email.', 'newsletter-optin-box' ),
							'<a href="https://noptin.com/guide/sending-emails/email-tags/">',
							'</a>'
						);
					?>
				</p>
			</td>
		</tr>

	</tbody>
</table>
