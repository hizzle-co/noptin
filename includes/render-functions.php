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
 * Renders a single editor field
 *
 * @param $id string Required. Unique id of the rendered field
 * @param $field array Required. The args of field to render
 * @param $panel string Optional. The panel where this field will be rendered
 * @return void
 */
function noptin_render_editor_field( $id, $field, $panel = false ){

    //Ensure an element has been specified
    if(!empty($field['el'])){
        $element = $field['el'];

        /**
		 * Fires when rendering an editor field
		 *
		 * @since 1.0.0
		 *
		*/
        do_action( "noptin_render_editor_{$element}", $id, $field, $panel );
    }
}


/**
 * Renders a paragraph in the opt-in editor sidebar
 */
function noptin_render_editor_paragraph( $id, $field ){

    //Abort if there is no content
    if( empty($field['content']) ){
        return;
    }

    //Setup content
    $content   = $field['content'];
    unset( $field['content'] );

    //If there is a restrict field, handle it
    $restrict  = empty($field['restrict']) ? '' : ' v-if="' . $field['restrict'] . '" ';
    unset( $field['restrict'] );

    //Setup class if none exists
    if( empty($field['class']) ){
        $field['class'] = 'noptin-padded';
    }

    //Setup attributes
    $attrs     = noptin_array_to_attrs( $field );

    //Render the html
    echo "<p $restrict $attrs>$content</p>";
}
add_action( 'noptin_render_editor_paragraph', 'noptin_render_editor_paragraph', 10, 2 );

/**
 * Renders hero text
 */
function noptin_render_editor_hero( $id, $field ){

    //Abort if there is no content
    if( empty($field['content']) ){
        return;
    }

    //Setup content
    $content   = $field['content'];
    unset( $field['content'] );

    //If there is a restrict field, handle it
    $restrict  = empty($field['restrict']) ? '' : ' v-if="' . $field['restrict'] . '" ';
    unset( $field['restrict'] );

    //Setup attributes
    $attrs     = noptin_array_to_attrs( $field );

    //Render the html
    echo "<h2 $restrict $attrs class='noptin-hero'>$content</h2>";
}
add_action( 'noptin_render_editor_hero', 'noptin_render_editor_hero', 10, 2 );

/**
 * Renders a textarea in the opt-in editor sidebar
 */
function noptin_render_editor_textarea( $id, $field ){

    //If there is a restrict field, handle it
    $restrict  = empty($field['restrict']) ? '' : ' v-if="' . $field['restrict'] . '" ';
    unset( $field['restrict'] );

    //Setup label
    $label = empty($field['label']) ? '' : $field['label'];
    unset( $field['label'] );

    //Setup attributes
    $attrs     = noptin_array_to_attrs( $field );

    //Render the html
    echo "<div $restrict class='noptin-textarea-wrapper'><label>$label</label><textarea $attrs v-model='$id'></textarea> </div>";
}
add_action( 'noptin_render_editor_textarea', 'noptin_render_editor_textarea', 10, 2 );

/**
 * Renders a editor in the opt-in editor sidebar
 */
function noptin_render_editor_editor( $id, $field ){

    //If there is a restrict field, handle it
    $restrict  = empty($field['restrict']) ? '' : ' v-if="' . $field['restrict'] . '" ';
    unset( $field['restrict'] );

    //Setup label
    $label = empty($field['label']) ? '' : $field['label'];
    unset( $field['label'] );

    //Setup attributes
    $attrs     = noptin_array_to_attrs( $field );

    //Render the html
    echo "<div $restrict class='noptin-textarea-wrapper'><label>$label</label><noptineditor $attrs id='$id' v-model='$id'></noptineditor> </div>";
}
add_action( 'noptin_render_editor_editor', 'noptin_render_editor_editor', 10, 2 );

/**
 * Renders an input field in the opt-in editor sidebar
 */
function noptin_render_editor_input( $id, $field ){

    //Setup label
    $label = empty($field['label']) ? '' : $field['label'];
    unset( $field['label'] );

    //If there is a restrict field, handle it
    $restrict  = empty($field['restrict']) ? '' : ' v-if="' . $field['restrict'] . '" ';
    unset( $field['restrict'] );

    //If no input type is set, set it to text
    if( empty($field['type']) ){
        $field['type'] = 'text';
    }

    //Set the model
    $field['v-model'] = $id;

    //Tooltips
    if(! empty($field['tooltip']) ){
        $tooltip = esc_attr( trim( $field['tooltip'] ) );
        unset( $field['tooltip'] );
        $tooltip  = "<span class='dashicons dashicons-info noptin-tip' title='$tooltip'></span>";
        $label    = "$label $tooltip";
    }

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );

    //Container class
    $class = "noptin-{$field['type']}-wrapper";

    switch ( $field['type'] ) {

        //Color picker
        case 'color':
            echo "<div class='$class' $restrict><span class='noptin-label'>$label</span> <noptin-swatch colors='material-basic' show-fallback v-model='$id' popover-to='left'></noptin-swatch></div>";
            break;

        case 'switch':
            $on  = empty($field['on'])? ''  : '<span class="on">' . $field['on'] . '</span>';
            $off = empty($field['off'])? '' : '<span class="off">' . $field['off'] . '</span>';
            echo "<label class='$class' $restrict><input type='checkbox' v-model='$id' class='screen-reader-text'> <span class='noptin-switch-slider'><span> </span></span><span class='noptin-label'> $label</span></label>";
            break;

        case 'checkbox':
            echo "<label class='$class' $restrict><input $attrs class='screen-reader-text'/> <span class='noptin-checkmark'></span> <span class='noptin-label'>$label</span></label>";
            break;

        case 'image':
            $attrs = str_replace( 'type="image"', '', $attrs );
            echo "<div class='$class' $restrict><span class='noptin-label'>$label</span> <div class='image-uploader'><input type='text' $attrs /> <input @click=\"upload_image('$id')\" type='button' class='button button-secondary' value='Upload Image' /></div></div>";
            break;

        default:
            echo "<label class='$class' $restrict><span class='noptin-label'>$label</span> <input $attrs /></label>";
            break;
    }

}
add_action( 'noptin_render_editor_input', 'noptin_render_editor_input', 10, 2 );

/**
 * Renders a multi checkbox input field
 */
function noptin_render_editor_multi_checkbox( $id, $field ){

    //If there is a restrict field, handle it
    $restrict  = empty($field['restrict']) ? '' : ' v-if="' . $field['restrict'] . '" ';
    unset( $field['restrict'] );

    //Set the model
    $field['v-model'] = $id;

    //Tooltips
    if(! empty($field['tooltip']) ){
        $tooltip = esc_attr( trim( $field['tooltip'] ) );
        unset( $field['tooltip'] );
        $tooltip  = "<span class='dashicons dashicons-info noptin-tip' title='$tooltip'></span>";
        $label    = "$label $tooltip";
    }

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );

    //Container class
	foreach( $field['options'] as $name => $label ) {
		echo "<label class='noptin-checkbox-wrapper' $restrict><input value='$name' type='checkbox' $attrs class='screen-reader-text'/> <span class='noptin-checkmark'></span> <span class='noptin-label'>$label</span></label>";
	}

}
add_action( 'noptin_render_editor_multi_checkbox', 'noptin_render_editor_multi_checkbox', 10, 2 );

/**
 * Renders a select input field in the opt-in editor sidebar
 */
function noptin_render_editor_select( $id, $field, $panel ){
    $label          = empty($field['label']) ? '' : $field['label'];
    $restrict       = noptin_get_editor_restrict_markup( $field );
    $ajax           = !empty($field['data']) ? " ajax='{$field['data']}' " : 'ajax="0"';

    unset( $field['restrict'] );
    unset( $field['label'] );
    unset( $field['data'] );
    if(! isset( $field['placeholder'] ) ) {
        $field['placeholder'] = 'Select';
    }

    $extra = '';
    if( 'multiselect' == $field['el'] ) {
        $extra = 'multiselect';
    }

    if(! empty( $field['taggable'] ) ) {
        $extra .= ' taggable :create-option =" val => ({ label: val, val: val })"';
        unset( $field['taggable'] );
    }

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );
    if( empty($field['options']) ) {
        $field['options'] = array();
    }

    $options = array();
    foreach( $field['options'] as $val => $name ){
        $options[] = array(
            'val'   => esc_attr( $val ),
            'label' => esc_attr( $name ),
        );

    }

    $options = wp_json_encode( $options );
    echo "<div class='noptin-select-wrapper' $restrict><label>$label</label><noptin-select
     :reduce='option => option.val' :clearable='false' :searchable='false' :options='$options' $attrs $ajax $extra v-model='$id'>";


    echo "</noptin-select></div>";
}
add_action( 'noptin_render_editor_select', 'noptin_render_editor_select', 10, 3 );
add_action( 'noptin_render_editor_multiselect', 'noptin_render_editor_select', 10, 3 );

/**
 * Renders a radio input field in the opt-in editor sidebar
 */
function noptin_render_editor_radio( $id, $field ){
    $label          = empty($field['label']) ? '' : $field['label'];
    $restrict       = empty($field['restrict']) ? '' : ' v-if="' . $field['restrict'] . '" ';

    echo "<div class='noptin-radio-wrapper' $restrict><span>$label</span>";

    if(is_array($field['options'])) {
        foreach( $field['options'] as $val => $label ){
            echo "<label><input type='radio' v-model='$id' value='$val' class='screen-reader-text'> $label <span class='noptin-checkmark'></span> </label>";
        }
    }

    echo "</div>";
}
add_action( 'noptin_render_editor_radio', 'noptin_render_editor_radio', 10, 2 );

/**
 * Renders a radio button group
 */
function noptin_render_editor_radio_button( $id, $field ){
    $label          = empty($field['label']) ? '' : $field['label'];
    $restrict       = noptin_get_editor_restrict_markup( $field );

    unset( $field['restrict'] );
    unset( $field['label'] );

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );

    echo "<fieldset class='noptin-radio-button-wrapper' $restrict><legend>$label</legend><div class='noptin-buttons'>";

    if(is_array($field['options'])) {
        foreach( $field['options'] as $val => $label ){
            echo "<label><input $attrs type='radio' v-model='$id' value='$val' class='screen-reader-text'><span>$label</span></label>";
        }
    }

    echo "</div></fieldset>";
}
add_action( 'noptin_render_editor_radio_button', 'noptin_render_editor_radio_button', 10, 2 );

/**
 * Renders an optin templates select button group
 */
function noptin_render_editor_optin_templates( $id, $field ){

    $templates      = noptin_get_optin_templates();
    $label          = empty($field['label']) ? '' : $field['label'];

    //Tooltips
    if(! empty($field['tooltip']) ){
        $tooltip  = esc_attr( trim( $field['tooltip'] ) );
        unset( $field['tooltip'] );
        $tooltip  = "<span class='dashicons dashicons-info noptin-tip' title='$tooltip'></span>";
        $label    = "$label $tooltip";
    }

    $restrict       = noptin_get_editor_restrict_markup( $field );

    unset( $field['restrict'] );
    unset( $field['label'] );
    unset( $field['tooltip'] );

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );

    echo "<div class='noptin-templates-wrapper' $restrict><label>$label</label><div class='noptin-templates'>";
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

    //Tooltips
    if(! empty($field['tooltip']) ){
        $tooltip  = esc_attr( trim( $field['tooltip'] ) );
        unset( $field['tooltip'] );
        $tooltip  = "<span class='dashicons dashicons-info noptin-tip' title='$tooltip'></span>";
        $label    = "$label $tooltip";
    }

    $restrict       = noptin_get_editor_restrict_markup( $field );

    unset( $field['restrict'] );
    unset( $field['label'] );
    unset( $field['tooltip'] );

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );

    echo "<div class='noptin-color-themes-wrapper' $restrict><label>$label</label><div class='noptin-templates'>";
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

    //Tooltips
    if(! empty($field['tooltip']) ){
        $tooltip  = esc_attr( trim( $field['tooltip'] ) );
        unset( $field['tooltip'] );
        $tooltip  = "<span class='dashicons dashicons-info noptin-tip' title='$tooltip'></span>";
        $label    = "$label $tooltip";
    }

    $restrict       = noptin_get_editor_restrict_markup( $field );

    unset( $field['restrict'] );
    unset( $field['label'] );
    unset( $field['tooltip'] );

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );

    echo "<div class='noptin-optin_data-wrapper' $restrict><label>$label</label><div class='noptin-templates'>";
    echo "<div class='noptin-templates-select'>";
    echo "<label>Title</label>";
    echo "<div class='noptin-title-editor'><quill-editor v-model=\"title\" :options=\"titleEditorOptions\"> </quill-editor></div>";
    echo "<label>Description</label>";
    echo "<div class='noptin-description-editor'><quill-editor v-model=\"description\" :options=\"descriptionEditorOptions\"> </quill-editor></div>";
    echo "<label>Note</label><div class='noptin-note-editor'><quill-editor v-model=\"note\" :options=\"descriptionEditorOptions\"> </quill-editor></div>";
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

    //Tooltips
    if(! empty($field['tooltip']) ){
        $tooltip  = esc_attr( trim( $field['tooltip'] ) );
        unset( $field['tooltip'] );
        $tooltip  = "<span class='dashicons dashicons-info noptin-tip' title='$tooltip'></span>";
        $label    = "$label $tooltip";
    }

    $restrict       = noptin_get_editor_restrict_markup( $field );

    unset( $field['restrict'] );
    unset( $field['label'] );
    unset( $field['tooltip'] );

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );

    echo "<div class='noptin-optin_image-wrapper' $restrict><label>$label</label><div class='noptin-templates'>";
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

    //Tooltips
    if(! empty($field['tooltip']) ){
        $tooltip  = esc_attr( trim( $field['tooltip'] ) );
        unset( $field['tooltip'] );
        $tooltip  = "<span class='dashicons dashicons-info noptin-tip' title='$tooltip'></span>";
        $label    = "$label $tooltip";
    }

    $restrict       = noptin_get_editor_restrict_markup( $field );

    unset( $field['restrict'] );
    unset( $field['label'] );
    unset( $field['tooltip'] );

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );

    echo "<div class='noptin-optin_image-wrapper' $restrict><label>$label</label><div class='noptin-templates'>";
    echo "<div class='noptin-templates-select'>";
    noptin_render_editor_field( 'singleLine', array(
        'type'      => 'checkbox',
        'el'        => 'input',
        'label'     => 'Show all fields in a single line',
    ) );
    noptin_render_editor_field( 'showNameField', array(
        'type'      => 'checkbox',
        'el'        => 'input',
        'label'     => 'Display the name field',
    ) );
    noptin_render_editor_field( 'firstLastName', array(
        'type'      => 'checkbox',
        'el'        => 'input',
        'label'     => 'Request for both the first and last names',
        'restrict'  => 'showNameField',
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

    //Tooltips
    if(! empty($field['tooltip']) ){
        $tooltip  = esc_attr( trim( $field['tooltip'] ) );
        unset( $field['tooltip'] );
        $tooltip  = "<span class='dashicons dashicons-info noptin-tip' title='$tooltip'></span>";
        $label    = "$label $tooltip";
    }

    $restrict       = noptin_get_editor_restrict_markup( $field );

    unset( $field['restrict'] );
    unset( $field['label'] );
    unset( $field['tooltip'] );

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );
    $id    = trim( $_GET['form_id']);
    $url   = admin_url("admin.php?page=noptin-forms");

    echo "<div class='noptin-optin_image-wrapper' $restrict><label>$label</label><div class='noptin-templates'>";
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

    //Tooltips
    if(! empty($field['tooltip']) ){
        $tooltip  = esc_attr( trim( $field['tooltip'] ) );
        unset( $field['tooltip'] );
        $tooltip  = "<span class='dashicons dashicons-info noptin-tip' title='$tooltip'></span>";
        $label    = "$label $tooltip";
    }

    $restrict       = noptin_get_editor_restrict_markup( $field );

    unset( $field['restrict'] );
    unset( $field['label'] );
    unset( $field['tooltip'] );

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );

    echo "<div class='noptin-optin_types-wrapper' $restrict><label>$label</label><div class='noptin-optin_types'>";

    foreach( $optin_types as $val => $args ){
        echo "<div title='{$args['desc']}' class='noptin-tip noptin-shadow noptin-optin_type noptin-$val' @click=\"optinType='$val'; currentStep='step_2'\">";
        echo "<span class='noptin-optin_type-icon dashicons dashicons-{$args['icon']}'></span>";
        echo "<h3>{$args['label']}</h3>";
        echo '</div>';
    }

    echo "</div></div>";
}
add_action( 'noptin_render_editor_optin_types', 'noptin_render_editor_optin_types', 10, 2 );

/**
 * Renders a panel
 */
function noptin_render_editor_panel( $id, $panel ){

    //Maybe abort early
    if( empty( $panel['children'] ) ){
        return;
    }

    //Prepare the variables
    $restrict   = noptin_get_editor_restrict_markup( $panel );
    $panel_name = "{$panel['id']}Open";
    $id         = "noptinPanel$panel_name";

    //Display the panel
    printf(
            '
            <div %3$s id="%4$s" :class="%1$s ? \'noptin-popup-editor-panel-open\' : \'noptin-popup-editor-panel-closed\'" class="noptin-popup-editor-panel">
                <div class="noptin-popup-editor-panel-header" @click="togglePanel(\'%1$s\')">
                    <span class="dashicons dashicons-arrow-up-alt2 noptin-popup-editor-panel-toggle"></span>
                    <span class="dashicons dashicons-arrow-down-alt2 noptin-popup-editor-panel-toggle"></span>
                    <h2 class="noptin-popup-editor-panel-title">%2$s</h2>
                </div>
                <div class="noptin-popup-editor-panel-body">',
            $panel_name,
            $panel['title'],
            $restrict,
            $id
        );

    //Display the panel's content
    foreach( $panel['children'] as $id=>$field ){
        noptin_render_editor_field( $id, $field );
    }

    //End the output
    echo "</div></div>";

}
add_action( 'noptin_render_editor_panel', 'noptin_render_editor_panel', 10, 2 );

/**
 * Returns the HTML used to restrict a given field
 */
function noptin_get_editor_restrict_markup( $field ){
    return empty($field['restrict']) ? '' : ' v-if="' . $field['restrict'] . '" ';
}
