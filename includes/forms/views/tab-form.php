<?php
/**
 * Displays the settings tab in the form editor.
 *
 * @var Noptin_Form $form
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$subscribe    = empty( $form->settings['submit'] ) ? __( 'Subscribe', 'newsletter-optin-box' ) : $form->settings['submit'];
$form_fields  = empty( $form->settings['fields'] ) ? 'email' : $form->settings['fields'];
$form_fields  = noptin_parse_list( $form_fields );
$inject       = empty( $form->settings['inject'] ) ? '' : $form->settings['inject'];
$update       = empty( $form->settings['update_existing'] ) ? '' : $form->settings['update_existing'];
$tag_messages = array(
	'noResults' => __( 'Enter a comma separated list of tags to assign the subscriber', 'newsletter-optin-box' ),
);

// TODO: Replace form fields select box with a sortable list of checkboxes.
?>

<h2 class="screen-reader-text"><?php esc_html_e( 'Form Fields', 'newsletter-optin-box' ); ?></h2>

<div class="noptin-text-wrapper form-settings-fields">
	<label for="noptin-form-fields" class="noptin-field-label">
		<?php esc_html_e( 'Fields to display', 'newsletter-optin-box' ); ?>
	</label>
	<select id="noptin-form-fields" class="noptin-select2" name="noptin_form[settings][fields][]" multiple="multiple" style="width: 30em;">
		<?php foreach ( get_noptin_custom_fields( true ) as $field ) : ?>
			<option
				value="<?php echo esc_html( $field['merge_tag'] ); ?>"
				<?php selected( in_array( $field['merge_tag'], $form_fields, true ) ); ?>
			><?php echo esc_html( $field['label'] ); ?></option>
		<?php endforeach; ?>
	</select>
	<p class="description">
		<?php
			printf(
				// translators: %1 & 2, opening and closing link.
				esc_html__( 'If the field you want to add does not appear above then start by %1$screating additional fields%2$s.', 'newsletter-optin-box' ),
				'<a target="_blank" href="' . esc_url_raw( admin_url( 'admin.php?page=noptin-settings&tab=fields' ) ) . '">',
				'</a>'
			);
		?>
	</p>
</div>

<div class="noptin-text-wrapper form-settings-subscribe-button">
	<label for="noptin-form-subscribe-button" class="noptin-field-label">
		<?php esc_html_e( 'Button Text', 'newsletter-optin-box' ); ?>
		<span title="<?php esc_attr_e( 'Set the text of the subscribe button.', 'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info"></span>
	</label>
	<input type="text" name="noptin_form[settings][submit]" class="noptin-text" value="<?php echo esc_attr( $subscribe ); ?>" />
</div>

<div class="noptin-text-wrapper form-settings-row-terms-text">
	<label for="noptin-form-terms-text" class="noptin-field-label">
		<?php esc_html_e( 'Terms / Acceptance Text (Optional)', 'newsletter-optin-box' ); ?>
		<span title="<?php esc_attr_e( 'Leave blank if you do not want to display an acceptance checkbox on the form.', 'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info"></span>
	</label>
	<input type="text" name="noptin_form[settings][acceptance]" class="noptin-text" value="<?php echo isset( $form->settings['acceptance'] ) ? esc_attr( $form->settings['acceptance'] ) : ''; ?>" placeholder="<?php esc_attr_e( 'Example: I have read and agree to the terms and conditions.', 'newsletter-optin-box' ); ?>"/>
</div>

<div class="noptin-text-wrapper form-field-row-redirect-url">
	<label for="noptin-form-redirect-url" class="noptin-field-label">
		<?php esc_html_e( 'Redirect URL (Optional)', 'newsletter-optin-box' ); ?>
		<span title="<?php esc_attr_e( 'Leave blank if you do not want to redirect users after they sign-up via this form.', 'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info"></span>
	</label>
	<input type="text" class="noptin-text" id="noptin-form-redirect-url" name="noptin_form[settings][redirect]" value="<?php echo isset( $form->settings['redirect'] ) ? esc_attr( $form->settings['redirect'] ) : ''; ?>" placeholder="<?php echo sprintf( /* translators: Example URL */ esc_attr__( 'Example: %s', 'newsletter-optin-box' ), esc_attr( site_url( '/thank-you/' ) ) ); ?>" />
</div>

<div class="noptin-text-wrapper form-settings-tags">
	<label for="noptin-form-tags" class="noptin-field-label">
		<?php esc_html_e( 'Tags', 'newsletter-optin-box' ); ?>
		<span title="<?php esc_attr_e( 'Enter the tags to assign new subscribers.', 'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info"></span>
	</label>
	<input type="text" name="noptin_form[settings][tags]" class="noptin-text" value="<?php echo isset( $form->settings['tags'] ) ? esc_attr( $form->settings['tags'] ) : ''; ?>" placeholder="Example tag 1, tag 2, tag 3"/>
	<p class="description">
		<?php esc_html_e( 'Enter a comma separated list of tags to assign new subscribers.', 'newsletter-optin-box' ); ?>
	</p>
</div>

<?php foreach ( get_noptin_multicheck_custom_fields() as $field ) : ?>
	<?php

		// Skip if no options.
		if ( empty( $field['options'] ) ) {
			continue;
		}

		$default_value = ! isset( $field['default_value'] ) ? array() : $field['default_value'];
		$current_value = ! isset( $form->settings[ $field['merge_tag'] ] ) ? noptin_parse_list( $default_value, true ) : noptin_parse_list( $form->settings[ $field['merge_tag'] ], true );
	?>
	<div class="noptin-text-wrapper form-field-row__cf-<?php echo esc_attr( $field['merge_tag'] ); ?>">
		<label for="noptin-form__cf-<?php echo esc_attr( $field['label'] ); ?>" class="noptin-field-label">
			<?php echo esc_html( $field['label'] ); ?>
			<span title="
				<?php
					sprintf(
						/* translators: %s is the context, e.g hobbies, lists */
						__( 'Select the %s to add new subscribers who sign up via this form.', 'newsletter-optin-box' ),
						$field['label']
					);
				?>
				"
				class="noptin-tip dashicons dashicons-info">
			</span>
		</label>

		<input type="hidden" name="noptin_form[settings][<?php echo esc_attr( $field['merge_tag'] ); ?>][]" value="0" />
		<select id="noptin-form__cf-<?php echo esc_attr( $field['label'] ); ?>" class="noptin-select2" name="noptin_form[settings][<?php echo esc_attr( $field['merge_tag'] ); ?>][]" multiple="multiple" style="width: 30em;">
			<?php foreach ( noptin_newslines_to_array( $field['options'] ) as $key => $label ) : ?>
				<option
					value="<?php echo esc_html( $key ); ?>"
					<?php selected( in_array( (string) $key, $current_value, true ) ); ?>
				><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
<?php endforeach; ?>

<?php do_action( 'noptin_form_settings_editor', $form ); ?>

<div class="noptin-text-wrapper form-field-row-redirect-url">
	<label class="noptin-field-label"><?php esc_html_e( 'Other Options', 'newsletter-optin-box' ); ?></label></label>

	<p>
		<?php noptin_hidden_field( 'noptin_form[settings][inject]', 'none' ); ?>
		<label>
			<input type="checkbox" id="noptin-form-inject-form" name="noptin_form[settings][inject]" value="after" <?php checked( $inject, 'after' ); ?>/>
			<span class="description"><?php esc_html_e( 'Automatically display this form after blog posts and other content.', 'newsletter-optin-box' ); ?></span>
		</label>
	</p>

	<p>
		<?php noptin_hidden_field( 'noptin_form[settings][update_existing]', 0 ); ?>
		<label>
			<input type="checkbox" id="noptin-form-update-subscribers" name="noptin_form[settings][update_existing]" value="1" <?php checked( ! empty( $update ) ); ?>/>
			<span class="description"><?php esc_html_e( 'Update existing subscribers if they match the submitted email address.', 'newsletter-optin-box' ); ?></span>
		</label>
	</p>

	<p>
		<?php noptin_hidden_field( 'noptin_form[settings][labels]', 'hide' ); ?>
		<label>
			<input type="checkbox" id="noptin-form-display-labels" name="noptin_form[settings][labels]" value="show" <?php checked( empty( $form->settings['labels'] ) || 'show' === $form->settings['labels'] ); ?>/>
			<span class="description"><?php esc_html_e( 'Display field labels outside the fields.', 'newsletter-optin-box' ); ?></span>
		</label>
	</p>

	<p>
		<?php noptin_hidden_field( 'noptin_form[settings][template]', 'normal' ); ?>
		<label>
			<input type="checkbox" id="noptin-form-form-template" name="noptin_form[settings][template]" value="condensed" <?php checked( ! empty( $form->settings['template'] ) && 'condensed' === $form->settings['template'] ); ?>/>
			<span class="description"><?php esc_html_e( 'Display all fields on a single line.', 'newsletter-optin-box' ); ?></span>
		</label>
	</p>

	<p>
		<?php noptin_hidden_field( 'noptin_form[settings][styles]', 'inherit' ); ?>
		<label>
			<input type="checkbox" id="noptin-form-form-styles" name="noptin_form[settings][styles]" value="basic" <?php checked( empty( $form->settings['styles'] ) || 'basic' === $form->settings['styles'] ); ?>/>
			<span class="description"><?php esc_html_e( 'Add basic CSS styles to the form (Recommended).', 'newsletter-optin-box' ); ?></span>
		</label>
	</p>

</div>
