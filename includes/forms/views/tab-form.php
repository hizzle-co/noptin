<?php
/**
 * Displays the settings tab in the form editor.
 *
 * @var Noptin_Form $form
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
// TODO: Make fields required, before fields content, after fields content.
$all_settings = $form->settings;
$subscribe    = empty( $form->settings['submit'] ) ? __( 'Subscribe', 'newsletter-optin-box' ) : $form->settings['submit'];
$all_fields   = get_noptin_custom_fields();
$form_fields  = current( wp_list_filter( $all_fields, array( 'merge_tag' => 'email' ) ) );

// Prepare form fields.
if ( $form_fields ) {

	$form_fields = array(
		array(
			'type'  => 'email',
			'label' => $form_fields['label'],
		)
	);

} else {

	$form_fields = array(
		array(
			'type'  => 'email',
			'label' => __( 'Email Address', 'newsletter-optin-box' ),
		)
	);

}

$form_fields     = isset( $all_settings['fields'] ) ? $all_settings['fields'] : $form_fields;
$privacy_policy  = get_privacy_policy_url();
$privacy_policy  = empty( $privacy_policy ) ? home_url() : $privacy_policy;
$default_consent = sprintf(
	__( 'I have read and agree to the Terms and conditions and the %1$sPrivacy policy%2$s', 'newsletter-optin-box' ),
	'<a href="' . esc_url( $privacy_policy ) . '" target="_blank">',
	'</a>'
);
?>

<h2 class="screen-reader-text"><?php esc_html_e( 'Form Fields', 'newsletter-optin-box' ); ?></h2>

<div class="noptin-form-editor-fields-layout-wrap">
	<fieldset id="noptin-form-fields-panel-fields" class="noptin-settings-panel">
		<button
			aria-expanded="true"
			aria-controls="noptin-form-fields-panel-fields-content"
			type="button"
			class="noptin-accordion-trigger"
			><span class="title"><?php esc_html_e( 'Form Fields', 'newsletter-optin-box' ); ?></span>
			<span class="icon"></span>
		</button>

		<div class="noptin-settings-panel__content" id="noptin-form-fields-panel-fields-content">

			<div class="form-fields">

				<p class="description"><?php esc_html_e( 'Which fields would you like to display on this newsletter sign-up form? Click on a field to edit it.', 'newsletter-optin-box' ); ?></p>

				<div class="form-fields-inner">
					<?php foreach ( $form_fields as $field ) : ?>

						<fieldset id="noptin-form-fields-panel-fields-<?php echo esc_attr( $field['type'] ); ?>" class="noptin-settings-panel draggable-source noptin-settings-panel__hidden">
							<button
								aria-expanded="false"
								aria-controls="noptin-form-fields-panel-fields-<?php echo esc_attr( $field['type'] ); ?>-content"
								type="button"
								class="noptin-accordion-trigger"
								><span class="dashicons dashicons-move"></span>
								<span class="title"><?php echo esc_html( $field['label'] ); ?></span>
								<code class="badge"><?php echo esc_html( $field['type'] ); ?></code>
								<span class="icon"></span>
							</button>
							<div class="noptin-settings-panel__content" id="noptin-form-fields-panel-fields-<?php echo esc_attr( $field['type'] ); ?>-content">

								<input type="hidden" name="noptin_form[settings][fields][][type]" value="<?php echo esc_attr( $field['type'] ); ?>" />
								<input type="hidden" name="noptin_form[settings][fields][][merge_tag]" value="<?php echo esc_attr( $field['type'] ); ?>" />

								<?php if ( 'GDPR_consent' !== $field['type'] ): ?>
									<p>
										<label><?php esc_html_e( 'Field Label', 'newsletter-optin-box' ); ?>
											<input type="text" name="noptin_form[settings][fields][][label]" class="widefat noptin-form-field-label" value="<?php echo esc_attr( $field['label'] ); ?>" />
										</label>
									</p>

									<?php do_action( 'noptin_form_edit_field', $field ); ?>

									<?php if ( 'email' !== $field['type'] ): ?>
										<p>
											<label>
												<input type="checkbox" name="noptin_form[settings][fields][][required]" value="1" <?php checked( ! empty( $field['required'] ) ); ?>/>
												<span class="description"><?php esc_html_e( 'Is this field required?', 'newsletter-optin-box' ); ?></span>
											</label>
										</p>
									<?php endif; ?>
								<?php else: ?>
									<input type="hidden" class="noptin-form-field-label" name="noptin_form[settings][fields][][label]" value="<?php esc_attr_e( 'Agree to terms', 'newsletter-optin-box' ); ?>" />

									<p>
										<label><?php esc_html_e( 'Consent Text', 'newsletter-optin-box' ); ?>
											<textarea rows="5" name="noptin_form[settings][fields][][text]" class="widefat"><?php echo esc_textarea( $field['text'] ); ?></textarea>
										</label>
										<p class="description"><?php esc_html_e( 'HTML is allowed', 'newsletter-optin-box' ); ?></p>
									</p>

								<?php endif; ?>

								<a href="#" class="noptin-field-editor-delete"><?php esc_html_e( 'Delete Field', 'newsletter-optin-box' ); ?></a>
							</div>
						</fieldset>

					<?php endforeach; ?>
				</div>

				<fieldset id="noptin-form-fields-panel-fields-subscribe-button" class="noptin-settings-panel noptin-settings-panel__hidden">
					<button
						aria-expanded="false"
						aria-controls="noptin-form-fields-panel-fields-subscribe-button-content"
						type="button"
						class="noptin-accordion-trigger"
					>
						<span class="title"><?php echo esc_html( $subscribe ); ?></span>
						<code class="badge"><?php echo esc_html_e( 'Subscribe button', 'newsletter-optin-box' ); ?></code>
						<span class="icon"></span>
					</button>
					<div class="noptin-settings-panel__content" id="noptin-form-fields-panel-fields-subscribe-button-content">

						<div class="noptin-text-wrapper">
							<label><?php esc_html_e( 'Subscribe Text', 'newsletter-optin-box' ); ?> 
								<input type="text" name="noptin_form[settings][submit]" class="widefat noptin-form-field-label" value="<?php echo esc_attr( $subscribe ); ?>" />
							</label>
							<p class="description"><?php esc_html_e( 'Set the text of the subscribe button.', 'newsletter-optin-box' ); ?></p>
						</div>

					</div>
				</fieldset>

				<p><button type="button" class="button noptin-button-standout noptin-button-add-field"><?php esc_html_e( 'Add Field', 'newsletter-optin-box' ); ?></button></p>

			</div>

		</div>

	</fieldset>

	<!-- Basic appearance settings -->
	<fieldset id="noptin-form-appearance-panel-layout" class="noptin-settings-panel">
		<button
			aria-expanded="true"
			aria-controls="noptin-form-appearance-panel-layout-content"
			type="button"
			class="noptin-accordion-trigger"
			><span class="title"><?php esc_html_e( 'Form Layout', 'newsletter-optin-box' ); ?></span>
			<span class="icon"></span>
		</button>

		<div class="noptin-settings-panel__content" id="noptin-form-appearance-panel-layout-content">
			<table class="form-table noptin-form-settings">

				<p>
					<?php noptin_hidden_field( 'noptin_form[settings][template]', 'normal' ); ?>
					<label>
						<input type="checkbox" id="noptin-form-template" name="noptin_form[settings][template]" value="condensed" <?php checked( ! empty( $form->settings['template'] ) && 'condensed' === $form->settings['template'] ); ?>/>
						<span class="description"><?php esc_html_e( 'Display all fields on a single line.', 'newsletter-optin-box' ); ?></span>
					</label>
				</p>

				<p>
					<?php noptin_hidden_field( 'noptin_form[settings][labels]', 'hide' ); ?>
					<label>
						<input type="checkbox" id="noptin-form-display-labels" name="noptin_form[settings][labels]" value="show" <?php checked( ! empty( $form->settings['labels'] ) && 'show' === $form->settings['labels'] ); ?>/>
						<span class="description"><?php esc_html_e( 'Display field labels above each field.', 'newsletter-optin-box' ); ?></span>
					</label>
				</p>

				<p>
					<?php noptin_hidden_field( 'noptin_form[settings][styles]', 'inherit' ); ?>
					<label>
						<input type="checkbox" id="noptin-form-form-styles" name="noptin_form[settings][styles]" value="basic" <?php checked( empty( $all_settings['styles'] ) || 'basic' === $all_settings['styles'] ); ?>/>
						<span class="description"><?php esc_html_e( 'Add basic CSS styles to the form (Recommended).', 'newsletter-optin-box' ); ?></span>
					</label>
				</p>

				<p>
					<?php noptin_hidden_field( 'noptin_form[settings][wrap]', 'div' ); ?>
					<label>
						<input type="checkbox" id="noptin-form-form-wrap" name="noptin_form[settings][wrap]" value="p" <?php checked( ! empty( $all_settings['wrap'] ) && 'p' === $all_settings['wrap'] ); ?>/>
						<span class="description"><?php esc_html_e( 'Wrap fields inside <p> elements.', 'newsletter-optin-box' ); ?></span>
					</label>
				</p>

			</table>
		</div>

	</fieldset>
</div>

<!-- Field setting templates. -->
<div id="noptin-form-fields-panel-field-templates" style="display: none;">

	<?php foreach ( $all_fields as $field ) : ?>

		<div id="noptin-form-fields-panel-<?php echo esc_attr( $field['merge_tag'] ); ?>-template">
			<input type="hidden" name="noptin_form[settings][fields][][type]" value="<?php echo esc_attr( $field['merge_tag'] ); ?>" />

			<p>
				<label><?php esc_html_e( 'Field Label', 'newsletter-optin-box' ); ?>
					<input type="text" name="noptin_form[settings][fields][][label]" class="widefat noptin-form-field-label" value="<?php echo esc_attr( $field['label'] ); ?>" />
				</label>
			</p>

			<?php do_action( 'noptin_form_edit_field_template', $field ); ?>

			<?php if ( 'email' !== $field['merge_tag'] ): ?>
				<p>
					<label>
						<input type="checkbox" name="noptin_form[settings][fields][][required]" value="1" <?php checked( ! empty( $field['required'] ) ); ?>/>
						<span class="description"><?php esc_html_e( 'Is this field required?', 'newsletter-optin-box' ); ?></span>
					</label>
				</p>
			<?php endif; ?>

			<a href="#" class="noptin-field-editor-delete"><?php esc_html_e( 'Delete Field', 'newsletter-optin-box' ); ?></a>
		</div>

	<?php endforeach; ?>

	<div id="noptin-form-fields-panel-GDPR_consent-template">
		<input type="hidden" name="noptin_form[settings][fields][][type]" value="GDPR_consent" />
		<input type="hidden" class="noptin-form-field-label" name="noptin_form[settings][fields][][label]" value="<?php esc_attr_e( 'Agree to terms', 'newsletter-optin-box' ); ?>" />

		<p>
			<label><?php esc_html_e( 'Consent Text', 'newsletter-optin-box' ); ?>
				<textarea rows="5" name="noptin_form[settings][fields][][text]" class="widefat"><?php echo esc_textarea( $default_consent ); ?></textarea>
			</label>
			<p class="description"><?php esc_html_e( 'HTML is allowed', 'newsletter-optin-box' ); ?></p>
		</p>

		<a href="#" class="noptin-field-editor-delete"><?php esc_html_e( 'Delete Field', 'newsletter-optin-box' ); ?></a>
	</div>
</div>

<!-- New field template. -->
<div id="noptin-form-fields-panel-new-field-template" style="display: none;">

	<fieldset id="noptin-form-fields-panel-fields-{{type}}" class="noptin-settings-panel draggable-source">

		<button
			aria-expanded="true"
			aria-controls="noptin-form-fields-panel-fields-{{type}}-content"
			type="button"
			class="noptin-accordion-trigger"
			><span class="dashicons dashicons-move"></span>
			<span class="title"><?php esc_html_e( 'New Field', 'newsletter-optin-box' ); ?></span>
			<code class="badge" style="display: none;"></code>
			<span class="icon"></span>
		</button>

		<div class="noptin-settings-panel__content" id="noptin-form-fields-panel-fields-{{type}}-content">
			<select class="noptin-form-settings-field-type">
				<option value="-1" selected="selected" disabled><?php esc_html_e( 'Select field', 'newsletter-optin-box' ); ?></option>
				<?php foreach ( $all_fields as $field ) : ?>
					<option value="<?php echo esc_attr( $field['merge_tag'] ); ?>"><?php echo esc_html( $field['label'] ); ?></option>
				<?php endforeach; ?>
				<option value="GDPR_consent"><?php esc_html_e( 'Agree to terms', 'newsletter-optin-box' ); ?></option>
			</select>

			<p class="description"><?php
				printf(
					__( 'If the field you want to add does not appear above, consider %1$screating additional fields%2$s.', 'newsletter-optin-box' ),
					'<a target="_blank" href="' . esc_url_raw( admin_url( 'admin.php?page=noptin-settings&tab=fields' ) ) . '">',
					'</a>'
				);
			?></p>

			<a href="#" class="noptin-field-editor-delete"><?php esc_html_e( 'Delete Field', 'newsletter-optin-box' ); ?></a>
		</div>

	</fieldset>

</div>
