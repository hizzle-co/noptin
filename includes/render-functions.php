<?php
/**
 * Render functions
 *
 * Simple WordPress optin form
 *
 * @since             1.0.5
 *
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    die;
}

/**
 * Renders an optin templates select button group
 */
function noptin_render_editor_optin_templates( $id, $field ){

    $templates      = noptin_get_optin_templates();
    $label          = empty($field['label']) ? '' : $field['label'];

    $restrict       = noptin_get_editor_restrict_markup( $field );

    unset( $field['restrict'] );
    unset( $field['label'] );
    unset( $field['tooltip'] );

    //Generate attrs html
	$attrs = noptin_array_to_attrs( $field );

	$tooltip = noptin_get_editor_tooltip_markup( $field );

    echo "<div class='noptin-templates-wrapper' $restrict><label>$label $tooltip</label><div class='noptin-templates'>";
    echo "<div class='noptin-templates-select'>";
    echo "<select class='ddslickTemplates'></select>";
    echo "<button @click.prevent=\"currentStep='step_3'\"  class='noptin-add-button'>Continue <span class='dashicons dashicons-arrow-right-alt'></span></button>";
    echo "</div>";
    echo "<div class='noptin-templates-preview'>";

    echo '<noptinForm v-bind="$data"></noptinForm>';

    echo "</div></div></div>";
}
add_action( 'noptin_render_editor_optin_templates', 'noptin_render_editor_optin_templates', 10, 2 );

/**
 * Renders an color_themes select button group
 */
function noptin_render_editor_color_themes( $id, $field ){

    $templates      = noptin_get_color_themes();
    $label          = empty($field['label']) ? '' : $field['label'];

    $restrict       = noptin_get_editor_restrict_markup( $field );

    unset( $field['restrict'] );
    unset( $field['label'] );
    unset( $field['tooltip'] );

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );
	$tooltip = noptin_get_editor_tooltip_markup( $field );

    echo "<div class='noptin-color-themes-wrapper' $restrict><label>$label $tooltip</label><div class='noptin-templates'>";
    echo "<div class='noptin-templates-select'>";
    echo "<select class='ddslickThemes'></select>";
    echo "<button @click.prevent=\"currentStep='step_4'\"  class='noptin-add-button'>Continue <span class='dashicons dashicons-arrow-right-alt'></span></button>";
    echo "</div>";
    echo "<div class='noptin-templates-preview'>";

    echo '<noptinForm v-bind="$data"></noptinForm>';

    echo "</div></div></div>";
}
add_action( 'noptin_render_editor_color_themes', 'noptin_render_editor_color_themes', 10, 2 );

/**
 * Renders an optin_data
 */
function noptin_render_editor_optin_data( $id, $field ){

    $label          = empty($field['label']) ? '' : $field['label'];


    $restrict       = noptin_get_editor_restrict_markup( $field );

    unset( $field['restrict'] );
    unset( $field['label'] );
    unset( $field['tooltip'] );

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );
	$tooltip = noptin_get_editor_tooltip_markup( $field );

    echo "<div class='noptin-optin_data-wrapper' $restrict><label>$label $tooltip</label><div class='noptin-templates'>";
    echo "<div class='noptin-templates-select'>";
    echo "<label>Title</label>";
    echo "<div class='noptin-title-editor'><quill-editor v-model=\"title\" :options=\"titleEditorOptions\"> </quill-editor></div>";
    echo "<label>Description</label>";
    echo "<div class='noptin-description-editor'><quill-editor v-model=\"description\" :options=\"descriptionEditorOptions\"> </quill-editor></div>";
    echo "<div v-if='!hideNote'><label>Note</label><div class='noptin-note-editor'><quill-editor v-model=\"note\" :options=\"descriptionEditorOptions\"> </quill-editor></div></div>";
    echo "<button @click.prevent=\"currentStep='step_5'\"  class='noptin-add-button'>Continue <span class='dashicons dashicons-arrow-right-alt'></span></button>";
    echo "</div>";
    echo "<div class='noptin-templates-preview'>";

    echo '<noptinForm v-bind="$data"></noptinForm>';

    echo "</div></div></div>";
}
add_action( 'noptin_render_editor_optin_data', 'noptin_render_editor_optin_data', 10, 2 );

/**
 * Renders an optin_image
 */
function noptin_render_editor_optin_image( $id, $field ){

    $label          = empty($field['label']) ? '' : $field['label'];


    $restrict       = noptin_get_editor_restrict_markup( $field );

    unset( $field['restrict'] );
    unset( $field['label'] );
    unset( $field['tooltip'] );

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );
	$tooltip = noptin_get_editor_tooltip_markup( $field );

    echo "<div class='noptin-optin_image-wrapper' $restrict><label>$label $tooltip </label><div class='noptin-templates'>";
    echo "<div class='noptin-templates-select'>";
    noptin_render_editor_field( 'image', array(
        'type'      => 'image',
        'el'        => 'input',
        'label'     => 'Image URL',
    ) );
    noptin_render_editor_field( 'imagePos', array(
        'el'        => 'radio_button',
        'options'       => array(
            'top'       => 'Top',
            'left'      => 'Left',
            'right'     => 'Right',
            'bottom'    => 'Bottom'
        ),
        'label'     => 'Image Position',
        'restrict'  => 'image',
    ) );
    echo "<button @click.prevent=\"currentStep='step_6'\"  class='noptin-add-button'>Continue <span class='dashicons dashicons-arrow-right-alt'></span></button>";
    echo "</div>";
    echo "<div class='noptin-templates-preview'>";

    echo '<noptinForm v-bind="$data"></noptinForm>';

    echo "</div></div></div>";
}
add_action( 'noptin_render_editor_optin_image', 'noptin_render_editor_optin_image', 10, 2 );

/**
 * Renders an optin_image
 */
function noptin_render_editor_optin_fields( $id, $field ){

    $label          = empty($field['label']) ? '' : $field['label'];


    $restrict       = noptin_get_editor_restrict_markup( $field );

    unset( $field['restrict'] );
    unset( $field['label'] );
    unset( $field['tooltip'] );

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );
	$tooltip = noptin_get_editor_tooltip_markup( $field );

    echo "<div class='noptin-optin_image-wrapper' $restrict><label>$label $tooltip</label><div class='noptin-templates'>";
    echo "<div class='noptin-templates-select'>";
	noptin_render_editor_field( 'fields', array(
		'el'        => 'form_fields',
	) );

	noptin_render_editor_field( 'singleLine', array(
        'type'      => 'checkbox',
        'el'        => 'input',
        'label'     => 'Show all fields in a single line',
    ) );

    echo "<button @click.prevent=\"finalize\"  class='noptin-add-button'>Continue <span class='dashicons dashicons-arrow-right-alt'></span></button>";
    echo "</div>";
    echo "<div class='noptin-templates-preview'>";

    echo '<noptinForm v-bind="$data"></noptinForm>';

    echo "</div></div></div>";
}
add_action( 'noptin_render_editor_optin_fields', 'noptin_render_editor_optin_fields', 10, 2 );

/**
 * Renders an optin_done
 */
function noptin_render_editor_optin_done( $id, $field ){

    $label          = empty($field['label']) ? '' : $field['label'];

    $restrict       = noptin_get_editor_restrict_markup( $field );

    unset( $field['restrict'] );
    unset( $field['label'] );
    unset( $field['tooltip'] );

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );
    $id    = trim( $_GET['form_id']);
    $url   = admin_url("admin.php?page=noptin-forms");
	$tooltip = noptin_get_editor_tooltip_markup( $field );

    echo "<div class='noptin-optin_image-wrapper' $restrict><label>$label $tooltip</label><div class='noptin-templates'>";
    echo "<div class='noptin-templates-select'>";
    echo "<p>That's all. <a href='$url'>View your forms</a></p>";
    echo "<a href='$url&form_id=$id'  class='noptin-add-button'>View Advanced Options <span class='dashicons dashicons-arrow-right-alt'></span></a>";
    echo "</div>";
    echo "<div class='noptin-templates-preview'>";

    echo '<noptinForm v-bind="$data"></noptinForm>';

    echo "</div></div></div>";
}
add_action( 'noptin_render_editor_optin_done', 'noptin_render_editor_optin_done', 10, 2 );

/**
 * Renders an optin type select button group
 */
function noptin_render_editor_optin_types( $id, $field ){

    $optin_types = array(
        'popup'     => array(
            'label' => 'Popup',
            'desc'  => 'The form will appear in a popup lightbox',
            'icon'  => 'grid-view'
        ),
        'inpost'    => array(
            'label' => 'InPost',
            'desc'  => 'You will embed the form in posts using a shortcode',
            'icon'  => 'archive'
        ),
        'sidebar'   => array(
            'label' => 'Sidebar',
            'desc'  => 'The form will appear on the sidebar and other widget areas',
            'icon'  => 'admin-page'
        ),
    );
    $label          = empty($field['label']) ? '' : $field['label'];


    $restrict       = noptin_get_editor_restrict_markup( $field );

    unset( $field['restrict'] );
    unset( $field['label'] );
    unset( $field['tooltip'] );

    //Generate attrs html
	$attrs = noptin_array_to_attrs( $field );
	$tooltip = noptin_get_editor_tooltip_markup( $field );

    echo "<div class='noptin-optin_types-wrapper' $restrict><label>$label $tooltip</label><div class='noptin-optin_types'>";

    foreach( $optin_types as $val => $args ){
        echo "<div title='{$args['desc']}' class='noptin-tip noptin-shadow noptin-optin_type noptin-$val' @click=\"optinType='$val'; currentStep='step_2'\">";
        echo "<span class='noptin-optin_type-icon dashicons dashicons-{$args['icon']}'></span>";
        echo "<h3>{$args['label']}</h3>";
        echo '</div>';
    }

    echo "</div></div>";
}
add_action( 'noptin_render_editor_optin_types', 'noptin_render_editor_optin_types', 10, 2 );
