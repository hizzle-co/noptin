<?php
/**
 * Displays the settings tab in the form editor.
 *
 * @var Noptin_Form $form
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$all_settings = $form->settings;
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

$form_fields      = isset( $all_settings['fields'] ) ? $all_settings['fields'] : $form_fields;
$form_preview_url = add_query_arg(
	array(
		'noptin_preview_form' => $form->exists() ? $form->id : 'new',
	),
	site_url( '/', 'admin' )
);

?>

<h2 class="screen-reader-text"><?php esc_html_e( 'Form Fields and Preview', 'newsletter-optin-box' ); ?></h2>

<fieldset id="noptin-form-fields-panel-fields" class="noptin-settings-panel">
	<button
		aria-expanded="true"
		aria-controls="noptin-form-fields-panel-fields-content"
		type="button"
		class="noptin-accordion-trigger"
		><span class="title"><?php esc_html_e( 'Form Fields and Preview', 'newsletter-optin-box' ); ?></span>
		<span class="icon"></span>
	</button>

	<div class="noptin-settings-panel__content" id="noptin-form-fields-panel-fields-content">

		<div class="form-fields">

			<h3>
				<?php esc_html_e( 'Form Fields', 'newsletter-optin-box' ); ?>
				<span title="<?php esc_attr_e( 'Click on the "Add Field" button to add other fields to your newsletter subscription form the click on the "Save Changes" button to save your changes.', 'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info"></span>
			</h3>

			<div class="form-fields-inner">
				<?php foreach ( $form_fields as $field ) : ?>

					<fieldset id="noptin-form-fields-panel-fields-<?php echo esc_attr( $field['type'] ); ?>" class="noptin-settings-panel noptin-settings-panel__hidden">
						<button
							aria-expanded="false"
							aria-controls="noptin-form-fields-panel-fields-<?php echo esc_attr( $field['type'] ); ?>-content"
							type="button"
							class="noptin-accordion-trigger"
							><span class="title"><?php echo esc_html( $field['label'] ); ?></span>
							<span class="icon"></span>
						</button>
						<div class="noptin-settings-panel__content" id="noptin-form-fields-panel-fields-<?php echo esc_attr( $field['type'] ); ?>-content">

							<input type="hidden" name="noptin_form[settings][][type]" value="<?php echo esc_attr( $field['type'] ); ?>" />

							<div class="noptin-text-wrapper">
								<label><?php esc_html_e( 'Field Label', 'newsletter-optin-box' ); ?>
									<input type="text" name="noptin_form[settings][][label]" class="widefat noptin-form-field-label" value="<?php echo esc_attr( $field['label'] ); ?>" />
								</label>
							</div>

							<div class="noptin-text-wrapper">
								<label><?php esc_html_e( 'Field Label', 'newsletter-optin-box' ); ?>
									<input type="text" name="noptin_form[settings][][label]" class="widefat noptin-form-field-label" value="<?php echo esc_attr( $field['label'] ); ?>" />
								</label>
							</div>

							<?php do_action( 'noptin_form_edit_field', $field ); ?>
							<a href="#" class="noptin-field-editor-delete"><?php esc_html_e( 'Delete Field', 'newsletter-optin-box' ); ?></a>
						</div>
					</fieldset>

				<?php endforeach; ?>
			</div>

			<p><button type="button" class="button noptin-button-standout noptin-button-add-field"><?php esc_html_e( 'Add Field', 'newsletter-optin-box' ); ?></button></p>

		</div>

		<div class="form-preview">
			<h3>
				<?php esc_html_e( 'Form Preview', 'newsletter-optin-box' ); ?>
				<span title="<?php esc_attr_e( 'The form may look slightly different than this when shown in a post, page or widget area.', 'newsletter-optin-box' ); ?>" class="noptin-tip dashicons dashicons-info"></span>
			</h3>
			<iframe id="noptin-form-preview" src="<?php echo esc_attr( $form_preview_url ); ?>"></iframe>
		</div>
	</div>

</fieldset>

<!-- Field setting templates. -->
<div id="noptin-form-fields-panel-field-templates" style="display: none;">

	<?php foreach ( $all_fields as $field ) : ?>

		<div id="noptin-form-fields-panel-<?php echo esc_attr( $field['merge_tag'] ); ?>-template">
			<input type="hidden" name="noptin_form[settings][][type]" value="<?php echo esc_attr( $field['merge_tag'] ); ?>" />

			<div class="noptin-text-wrapper">
				<label><?php esc_html_e( 'Field Label', 'newsletter-optin-box' ); ?>
					<input type="text" name="noptin_form[settings][][label]" class="widefat noptin-form-field-label" value="<?php echo esc_attr( $field['label'] ); ?>" />
				</label>
			</div>

			<div class="noptin-text-wrapper">
				<label><?php esc_html_e( 'Field Label', 'newsletter-optin-box' ); ?>
					<input type="text" name="noptin_form[settings][][label]" class="widefat noptin-form-field-label" value="<?php echo esc_attr( $field['label'] ); ?>" />
				</label>
			</div>

			<?php do_action( 'noptin_form_edit_field_template', $field ); ?>
			<a href="#" class="noptin-field-editor-delete"><?php esc_html_e( 'Delete Field', 'newsletter-optin-box' ); ?></a>
		</div>

	<?php endforeach; ?>

</div>

<!-- New field template. -->
<div id="noptin-form-fields-panel-new-field-template" style="display: none;">

	<fieldset id="noptin-form-fields-panel-fields-{{type}}" class="noptin-settings-panel ui-state-default">

		<button
			aria-expanded="true"
			aria-controls="noptin-form-fields-panel-fields-{{type}}-content"
			type="button"
			class="noptin-accordion-trigger"
			><span class="title"><?php esc_html_e( 'New Field', 'newsletter-optin-box' ); ?></span>
			<span class="icon"></span>
		</button>

		<div class="noptin-settings-panel__content" id="noptin-form-fields-panel-fields-{{type}}-content">
			<select class="noptin-form-settings-field-type">
				<option value="-1" selected="selected" disabled><?php esc_html_e( 'Select field', 'newsletter-optin-box' ); ?></option>
				<?php foreach ( $all_fields as $field ) : ?>
					<option value="<?php echo esc_attr( $field['merge_tag'] ); ?>"><?php echo esc_html( $field['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
			<a href="#" class="noptin-field-editor-delete"><?php esc_html_e( 'Delete Field', 'newsletter-optin-box' ); ?></a>
			<p class="description"><?php
				printf(
					__( 'If the field you want to add does not appear above, consider %1$screating additional fields%2$s.', 'newsletter-optin-box' ),
					'<a target="_blank" href="' . esc_url_raw( admin_url( 'admin.php?page=noptin-settings&tab=fields' ) ) . '">',
					'</a>'
				);
			?></p>
		</div>

	</fieldset>

</div>
