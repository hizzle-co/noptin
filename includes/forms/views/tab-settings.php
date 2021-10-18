<?php
/**
 * Displays the settings tab in the form editor.
 *
 * @var Noptin_Form $form
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$all_settings = $form->settings;
?>

<h2 class="screen-reader-text"><?php esc_html_e( 'Form Settings', 'newsletter-optin-box' ); ?></h2>

<p class="description"><?php esc_html_e( 'Use this tab to update form settings.', 'newsletter-optin-box' ); ?></p>

<fieldset id="noptin-form-settings-panel-basic" class="noptin-settings-panel">
	<button
		aria-expanded="true"
		aria-controls="noptin-form-settings-panel-basic-content"
		type="button"
		class="noptin-accordion-trigger"
		><span class="title"><?php esc_html_e( 'Form Settings', 'newsletter-optin-box' ); ?></span>
		<span class="icon"></span>
	</button>

	<div class="noptin-settings-panel__content" id="noptin-form-settings-panel-basic-content">
		<table class="form-table noptin-form-settings">

			<tr valign="top" class="form-field-row form-field-row-update-subscribers">
				<th scope="row">
					<label for="noptin-form-update-subscribers"><?php esc_html_e( 'Update existing subscribers?', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>
					<?php noptin_hidden_field( 'noptin_form[settings][update_existing]', 0 ); ?>
					<label>
						<input type="checkbox" id="noptin-form-update-subscribers" name="noptin_form[settings][update_existing]" value="1" <?php checked( empty( $all_settings['update_existing'] ) ); ?>/>
						<span class="description"><?php esc_html_e( 'Should we update existing subscribers if they match the submitted email address?', 'newsletter-optin-box' ); ?></span>
					</label>
				</td>
			</tr>

			<tr valign="top" class="form-field-row form-field-row-redirect-url">
				<th scope="row">
					<label for="noptin-form-redirect-url"><?php esc_html_e( 'Redirect URL', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>
					<input type="text" class="regular-text" id="noptin-form-redirect-url" name="noptin_form[settings][redirect]" value="<?php echo isset( $all_settings['redirect'] ) ? esc_url( $all_settings['redirect'] ) : ''; ?>" placeholder="<?php echo sprintf( esc_attr__( 'Example: %s', 'newsletter-optin-box' ), esc_attr( site_url( '/thank-you/' ) ) ); ?>" />
					<p class="description"><?php esc_html_e( 'Optional. Enter a URL to redirect users after they sign-up via this form or leave blank to disable redirects.', 'newsletter-optin-box' ); ?></p>
				</td>
			</tr>

		</table>
	</div>

</fieldset>
