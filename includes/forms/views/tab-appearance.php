<?php
/**
 * Displays the appearance tab in the form editor.
 *
 * @var Noptin_Form $form
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
// TODO: template (normal, fields single line, fields and extra content single line, unstyled), max-width can-not exceed 600px if not single line. html_class
$appearance_settings = $form->appearance;
$all_settings        = $form->settings;
$bg_color            = isset( $appearance_settings['bg_color'] ) ? $appearance_settings['bg_color'] : '';
$text_color          = isset( $appearance_settings['text_color'] ) ? $appearance_settings['text_color'] : '';
$border_color        = isset( $appearance_settings['border_color'] ) ? $appearance_settings['border_color'] : '';
$border_style        = isset( $appearance_settings['border_style'] ) ? $appearance_settings['border_style'] : 'none';
$button_color        = isset( $appearance_settings['button_color'] ) ? $appearance_settings['button_color'] : '';
$button_text_color   = isset( $appearance_settings['button_text_color'] ) ? $appearance_settings['button_text_color'] : '';
$button_style        = isset( $appearance_settings['button_style'] ) ? $appearance_settings['button_style'] : '';

?>

<h2 class="screen-reader-text"><?php esc_html_e( 'Appearance Settings', 'newsletter-optin-box' ); ?></h2>
<p class="description" style="margin-bottom: 16px;"><?php esc_html_e( 'Do not fill this section if you would like to inherit the appearance of your theme.', 'newsletter-optin-box' ); ?></p>

<fieldset id="noptin-form-settings-panel-layout" class="noptin-settings-panel">
	<button
		aria-expanded="true"
		aria-controls="noptin-form-settings-panel-layout-content"
		type="button"
		class="noptin-accordion-trigger"
		><span class="title"><?php esc_html_e( 'Form Layout', 'newsletter-optin-box' ); ?></span>
		<span class="icon"></span>
	</button>

	<div class="noptin-settings-panel__content" id="noptin-form-settings-panel-layout-content">
		<table class="form-table noptin-form-settings">

			<tr valign="top" class="form-field-row form-field-row-display-labels">
				<th scope="row">
					<label for="noptin-form-display-labels"><?php esc_html_e( 'Display Input Labels', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>
					<?php noptin_hidden_field( 'noptin_form[settings][labels]', 'hide' ); ?>
					<label>
						<input type="checkbox" id="noptin-form-display-labels" name="noptin_form[settings][labels]" value="show" <?php checked( ! empty( $all_settings['labels'] ) && 'show' === $all_settings['labels'] ); ?>/>
						<span class="description"><?php esc_html_e( 'Display input labels above the input fields.', 'newsletter-optin-box' ); ?></span>
					</label>
				</td>
			</tr>

			<tr valign="top" class="form-field-row form-field-row-form-styles">
				<th scope="row">
					<label for="noptin-form-form-styles"><?php esc_html_e( 'Form Styles', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>
					<?php noptin_hidden_field( 'noptin_form[settings][styles]', 'inherit' ); ?>
					<label>
						<input type="checkbox" id="noptin-form-form-styles" name="noptin_form[settings][styles]" value="basic" <?php checked( empty( $all_settings['styles'] ) || 'basic' === $all_settings['styles'] ); ?>/>
						<span class="description"><?php esc_html_e( 'Add basic CSS styles to the form (Recommended).', 'newsletter-optin-box' ); ?></span>
					</label>
				</td>
			</tr>

		</table>
	</div>

</fieldset>

<!-- Form settings -->
<fieldset id="noptin-form-appearance-panel-basic" class="noptin-settings-panel">
	<button
		aria-expanded="true"
		aria-controls="noptin-form-appearance-panel-basic-content"
		type="button"
		class="noptin-accordion-trigger"
		><span class="title"><?php esc_html_e( 'Form Appearance', 'newsletter-optin-box' ); ?></span>
		<span class="icon"></span>
	</button>

	<div class="noptin-settings-panel__content" id="noptin-form-appearance-panel-basic-content">

		<table class="form-table noptin-form-settings">

			<tr valign="top" class="form-field-row form-field-row-form-bg">
				<th scope="row">
					<label for="noptin-form-bg"><?php esc_html_e( 'Background Color', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>

					<?php
						printf(
							'<input %s />',
							noptin_attr(
								'color-picker',
								array(
									'type'               => 'text',
									'class'              => 'noptin-color-picker',
									'id'                 => 'noptin-form-bg',
									'name'               => 'noptin_form[appearance][bg_color]',
									'value'              => $bg_color,
									'type'               => 'text',
									'data-default-color' => '',
								)
							)
						);
					?>

				</td>
			</tr>

			<tr valign="top" class="form-field-row form-field-row-form-color">
				<th scope="row">
					<label for="noptin-form-color"><?php esc_html_e( 'Text Color', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>

					<?php
						printf(
							'<input %s />',
							noptin_attr(
								'color-picker',
								array(
									'type'               => 'text',
									'class'              => 'noptin-color-picker',
									'id'                 => 'noptin-form-color',
									'name'               => 'noptin_form[appearance][text_color]',
									'value'              => $text_color,
									'type'               => 'text',
									'data-default-color' => '',
								)
							)
						);
					?>

				</td>
			</tr>

			<tr valign="top" class="form-field-row form-field-row-form-border-style">
				<th scope="row">
					<label for="noptin-form-border-style"><?php esc_html_e( 'Border Style', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>
					<select id="noptin-form-border-style" name="noptin_form[appearance][border_style]">
						<option value="none" <?php selected( 'none', $border_style ); ?>><?php esc_html_e( 'No Border', 'newsletter-optin-box' ); ?></option>
						<option value="solid" <?php selected( 'solid', $border_style ); ?>><?php esc_html_e( 'Solid', 'newsletter-optin-box' ); ?></option>
						<option value="dashed" <?php selected( 'dashed', $border_style ); ?>><?php esc_html_e( 'Dashed', 'newsletter-optin-box' ); ?></option>
						<option value="dotted" <?php selected( 'dotted', $border_style ); ?>><?php esc_html_e( 'Dotted', 'newsletter-optin-box' ); ?></option>
						<option value="double" <?php selected( 'double', $border_style ); ?>><?php esc_html_e( 'Double', 'newsletter-optin-box' ); ?></option>
						<option value="groove" <?php selected( 'groove', $border_style ); ?>><?php esc_html_e( 'Groove', 'newsletter-optin-box' ); ?></option>
						<option value="inset" <?php selected( 'inset', $border_style ); ?>><?php esc_html_e( 'Inset', 'newsletter-optin-box' ); ?></option>
						<option value="outset" <?php selected( 'outset', $border_style ); ?>><?php esc_html_e( 'Outset', 'newsletter-optin-box' ); ?></option>
						<option value="ridge" <?php selected( 'ridge', $border_style ); ?>><?php esc_html_e( 'Ridge', 'newsletter-optin-box' ); ?></option>
					</select>
				</td>
			</tr>

			<tr valign="top" class="form-field-row form-field-row-form-border">
				<th scope="row">
					<label for="noptin-form-border"><?php esc_html_e( 'Border Color', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>

					<?php
						printf(
							'<input %s />',
							noptin_attr(
								'color-picker',
								array(
									'type'               => 'text',
									'class'              => 'noptin-color-picker',
									'id'                 => 'noptin-form-border',
									'name'               => 'noptin_form[appearance][border_color]',
									'value'              => $border_color,
									'type'               => 'text',
									'data-default-color' => '',
								)
							)
						);
					?>

				</td>
			</tr>

		</table>

	</div>

</fieldset>

<!-- button settings -->
<fieldset id="noptin-form-appearance-panel-button" class="noptin-settings-panel">
	<button
		aria-expanded="true"
		aria-controls="noptin-form-appearance-panel-button-content"
		type="button"
		class="noptin-accordion-trigger"
		><span class="title"><?php esc_html_e( 'Button Appearance', 'newsletter-optin-box' ); ?></span>
		<span class="icon"></span>
	</button>

	<div class="noptin-settings-panel__content" id="noptin-form-appearance-panel-button-content">

		<table class="form-table noptin-form-settings">

			<tr valign="top" class="form-field-row form-field-row-form-button-color">
				<th scope="row">
					<label for="noptin-form-button-color"><?php esc_html_e( 'Button Color', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>

					<?php
						printf(
							'<input %s />',
							noptin_attr(
								'color-picker',
								array(
									'type'               => 'text',
									'class'              => 'noptin-color-picker',
									'id'                 => 'noptin-form-button-color',
									'name'               => 'noptin_form[appearance][button_color]',
									'value'              => $button_color,
									'type'               => 'text',
									'data-default-color' => '',
								)
							)
						);
					?>

				</td>
			</tr>

			<tr valign="top" class="form-field-row form-field-row-button-text-color">
				<th scope="row">
					<label for="noptin-form-button-text-color"><?php esc_html_e( 'Text Color', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>

					<?php
						printf(
							'<input %s />',
							noptin_attr(
								'color-picker',
								array(
									'type'               => 'text',
									'class'              => 'noptin-color-picker',
									'id'                 => 'noptin-form-button-text-color',
									'name'               => 'noptin_form[appearance][button_text_color]',
									'value'              => $button_text_color,
									'type'               => 'text',
									'data-default-color' => '',
								)
							)
						);
					?>

				</td>
			</tr>

			<tr valign="top" class="form-field-row form-field-row-form-button-style">
				<th scope="row">
					<label for="noptin-form-button-style"><?php esc_html_e( 'Button Style', 'newsletter-optin-box' ); ?></label>
				</th>
				<td>
					<select id="noptin-form-button-style" name="noptin_form[appearance][button_style]">
						<option value="" <?php selected( '', $button_style ); ?>><?php esc_html_e( 'Default', 'newsletter-optin-box' ); ?></option>
						<option value="block" <?php selected( 'block', $button_style ); ?>><?php esc_html_e( 'Full Width', 'newsletter-optin-box' ); ?></option>
						<option value="left" <?php selected( 'left', $border_style ); ?>><?php esc_html_e( 'Left Aligned', 'newsletter-optin-box' ); ?></option>
						<option value="right" <?php selected( 'right', $border_style ); ?>><?php esc_html_e( 'Right Aligned', 'newsletter-optin-box' ); ?></option>
					</select>
				</td>
			</tr>

		</table>

	</div>

</fieldset>
