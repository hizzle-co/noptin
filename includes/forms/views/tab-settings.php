<?php
/**
 * Displays the settings tab in the form editor.
 *
 * @var Noptin_Form $form
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$all_settings = $form->settings; //TODO: Form status.
$places = array_merge(
	array(
		'frontpage'  => __( 'Front page', 'newsletter-optin-box' ),
		'blogpage'   => __( 'Blog page', 'newsletter-optin-box' ),
		'searchpage' => __( 'Search page', 'newsletter-optin-box' ),
		'archives'   => __( 'Archive pages', 'newsletter-optin-box' ),
	),
	noptin_get_post_types()
);
$hide   = empty( $form->settings['hide'] ) ? array() : $form->settings['hide'];
$inject = empty( $form->settings['inject'] ) ? '' : $form->settings['inject'];
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

			<tr valign="top" class="form-field-row form-field-row-status">
				<th scope="row">
					<label for="noptin-form-status"><?php esc_html_e( 'Form Status', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>
					<select class="regular-text" id="noptin-form-status" name="noptin_form[status]">
						<option value="publish" <?php selected( 'draft' != $form->status ); ?>><?php esc_html_e( 'Published', 'newsletter-optin-box' ); ?></option>
						<option value="draft" <?php selected( 'draft' == $form->status ); ?>><?php esc_html_e( 'Draft', 'newsletter-optin-box' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Set to draft if you want to hide the form from website visitors.', 'newsletter-optin-box' ); ?></p>
				</td>
			</tr>

			<tr valign="top" class="form-field-row form-field-row-inject">
				<th scope="row">
					<label for="noptin-form-inject"><?php esc_html_e( 'Append to blog posts', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>
					<select class="regular-text" id="noptin-form-status" name="noptin_form[settings][inject]">
						<option value="" <?php selected( $inject, '' ); ?>><?php esc_html_e( 'Do not append', 'newsletter-optin-box' ); ?></option>
						<option value="before" <?php selected( $inject, 'before' ); ?>><?php esc_html_e( 'Before post content', 'newsletter-optin-box' ); ?></option>
						<option value="after" <?php selected( $inject, 'after' ); ?>><?php esc_html_e( 'After post content', 'newsletter-optin-box' ); ?></option>
						<option value="both" <?php selected( $inject, 'both' ); ?>><?php esc_html_e( 'Before and after post content', 'newsletter-optin-box' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Automatically display this form after blog posts and other content.', 'newsletter-optin-box' ); ?></p>
				</td>
			</tr>

			<tr valign="top" class="form-field-row form-field-row-redirect-url">
				<th scope="row">
					<label for="noptin-form-redirect-url"><?php esc_html_e( 'Redirect URL', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>
					<input type="text" class="regular-text" id="noptin-form-redirect-url" name="noptin_form[settings][redirect]" value="<?php echo isset( $all_settings['redirect'] ) ? esc_attr( $all_settings['redirect'] ) : ''; ?>" placeholder="<?php echo sprintf( esc_attr__( 'Example: %s', 'newsletter-optin-box' ), esc_attr( site_url( '/thank-you/' ) ) ); ?>" />
					<p class="description"><?php esc_html_e( 'Optional. Enter a URL to redirect users after they sign-up via this form or leave blank to disable redirects.', 'newsletter-optin-box' ); ?></p>
				</td>
			</tr>

			<tr valign="top" class="form-field-row form-field-row-update-subscribers">
				<th scope="row">
					<label for="noptin-form-update-subscribers"><?php esc_html_e( 'Update existing subscribers?', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>
					<?php noptin_hidden_field( 'noptin_form[settings][update_existing]', 0 ); ?>
					<label>
						<input type="checkbox" id="noptin-form-update-subscribers" name="noptin_form[settings][update_existing]" value="1" <?php checked( ! empty( $all_settings['update_existing'] ) ); ?>/>
						<span class="description"><?php esc_html_e( 'Should we update existing subscribers if they match the submitted email address?', 'newsletter-optin-box' ); ?></span>
					</label>
				</td>
			</tr>

			<?php do_action( 'noptin_form_settings_editor', $form ); ?>
		</table>
	</div>

</fieldset>

<fieldset id="noptin-form-settings-panel-targeting" class="noptin-settings-panel noptin-settings-panel__hidden">
	<button
		aria-expanded="false"
		aria-controls="noptin-form-settings-panel-targeting-content"
		type="button"
		class="noptin-accordion-trigger"
		><span class="title"><?php esc_html_e( 'Conditional Display', 'newsletter-optin-box' ); ?></span>
		<span class="icon"></span>
	</button>

	<div class="noptin-settings-panel__content" id="noptin-form-settings-panel-targeting-content">
		<table class="form-table noptin-form-settings">

			<tr valign="top" class="form-field-row form-field-row-hide-on">
				<th scope="row">
					<label><?php esc_html_e( 'Hide on', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>
					<div class="noptin-checkbox-list-wrap">
						<ul>
							<?php foreach ( $places as $key => $place ) : ?>
								<li>
									<label>
										<input type="checkbox" name="noptin_form[settings][hide][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $hide ) ); ?>/>
										<span class="description"><?php echo esc_html( $place ); ?></span>
									</label>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
					<p class="description"><?php esc_html_e( 'Optional. Select the places where this form should be hidden.', 'newsletter-optin-box' ); ?></p>
				</td>
			</tr>

			<tr valign="top" class="form-field-row form-field-row-only-show-on">
				<th scope="row">
					<label for="noptin-form-only-show-on"><?php esc_html_e( 'Only show on:', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>
					<input type="text" class="regular-text" id="noptin-form-only-show-on" name="noptin_form[settings][only_show]" value="<?php echo isset( $all_settings['only_show'] ) ? esc_attr( $all_settings['only_show'] ) : ''; ?>" placeholder="<?php echo sprintf( esc_attr__( 'Example: %s', 'newsletter-optin-box' ), '3,14,5,' . esc_attr( noptin_clean_url( home_url( 'newsletter' ) ) ) ); ?>" />
					<p class="description"><?php esc_html_e( 'Optional. Enter a comma separated list of URLs or post ids. If set, the form will only show if a user is viewing those pages.', 'newsletter-optin-box' ); ?></p>
				</td>
			</tr>

			<?php do_action( 'noptin_form_conditional_display_editor', $form ); ?>
		</table>
	</div>

</fieldset>
