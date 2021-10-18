<?php
/**
 * Displays the appearance tab in the form editor.
 *
 * @var Noptin_Form $form
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
// TODO: template (normal, fields single line, fields and extra content single line, unstyled), preferred width, border, background color, 
$appearance_settings = $form->appearance;
$all_settings        = $form->settings;
$label_placements    = array(
	'top'    => __( 'Top', 'newsletteer-optin-box' ),
	'left'   => __( 'Left', 'newsletteer-optin-box' ),
	'right'  => __( 'Right', 'newsletteer-optin-box' ),
	'hidden' => __( 'Hidden', 'newsletteer-optin-box' ),
);
$label_placement     = empty( $all_settings['label_position'] ) ? 'top' : $all_settings['label_position'];

?>

<h2 class="screen-reader-text"><?php esc_html_e( 'Appearance Settings', 'newsletter-optin-box' ); ?></h2>

<p class="description"><?php esc_html_e( 'Use this tab to change the appearance of your form.', 'newsletter-optin-box' ); ?></p>

<fieldset id="noptin-form-appearance-settings-panel-layout" class="noptin-settings-panel">
	<button
		aria-expanded="true"
		aria-controls="noptin-form-appearance-settings-panel-layout-content"
		type="button"
		class="noptin-accordion-trigger"
		><span class="title"><?php esc_html_e( 'Form Layout', 'newsletter-optin-box' ); ?></span>
		<span class="icon"></span>
	</button>

	<div class="noptin-settings-panel__content" id="noptin-form-appearance-settings-panel-layout-content">
		<table class="form-table noptin-form-settings">

			<tr valign="top" class="form-field-row form-field-row-label-position">
				<th scope="row">
					<label for="noptin-form-appearance-label-position"><?php esc_html_e( 'Label Position', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>
					<select name="noptin_form[settings][label_position]" class="regular-text" id="noptin-form-appearance-label-position">
						<?php foreach ( $label_placements as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $label_placement ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>	
					</select>
					<p class="description">
						<?php esc_html_e( 'How should field labels be displayed relative to the field inputs?', 'newsletter-optin-box' ); ?>
					</p>
				</td>
			</tr>

			<tr valign="top" class="form-field-row form-field-row-label-position">
				<th scope="row">
					<label for="noptin-form-appearance-label-position"><?php esc_html_e( 'Label Position', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>
					<select name="noptin_form[settings][label_position]" class="regular-text" id="noptin-form-appearance-label-position">
						<?php foreach ( $label_placements as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $label_placement ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>	
					</select>
					<p class="description">
						<?php esc_html_e( 'How should field labels be displayed relative to the field inputs?', 'newsletter-optin-box' ); ?>
					</p>
				</td>
			</tr>

		</table>
	</div>

</fieldset>
