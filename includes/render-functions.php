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

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );

    //Container class
    $class = "noptin-{$field['type']}-wrapper";

    switch ( $field['type'] ) {

        //Color picker
        case 'color':
            echo "<div class='$class' $restrict><span class='noptin-label'>$label</span> <noptincolor v-model='$id' type='text' /> </div>";
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
            echo "<div class='$class' $restrict><span class='noptin-label'>$label</span> <div class='image-uploader'><input type='text' $attrs /> <input @click=\"upload_image('$id')\" type='button' class='button button-primary' value='Upload Image' /></div></div>";
            break;

        default:
            echo "<label class='$class' $restrict><span class='noptin-label'>$label</span> <input $attrs /></label>";
            break;
    }

}
add_action( 'noptin_render_editor_input', 'noptin_render_editor_input', 10, 2 );

/**
 * Renders a select input field in the opt-in editor sidebar
 */
function noptin_render_editor_select( $id, $field, $panel ){
    $label          = empty($field['label']) ? '' : $field['label'];
    $restrict       = noptin_get_editor_restrict_markup( $field );
    $multiselect    = 'multiselect' == $field['el'] ? ' multiple="multiple" ' : '';
    $ajax           = is_string($field['options']) ? " ajax='{$field['options']}' " : 'ajax="0"';

    unset( $field['restrict'] );
    unset( $field['label'] );

    //Generate attrs html
    $attrs = noptin_array_to_attrs( $field );

    echo "<div class='noptin-select-wrapper' $restrict><label>$label</label><noptinselect2 $attrs $ajax $multiselect v-model='$id'>";

    if(is_array($field['options'])) {
        foreach( $field['options'] as $val => $label ){
            echo "<option value='$val'>$label</option>";
        }
    }

    echo "</noptinselect2></div>";
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

    echo "<div class='noptin-radio-button-wrapper' $restrict><span>$label</span><div class='noptin-buttons'>";

    if(is_array($field['options'])) {
        foreach( $field['options'] as $val => $label ){
            echo "<label><input $attrs type='radio' v-model='$id' value='$val' class='screen-reader-text'><span>$label</span></label>";
        }
    }

    echo "</div></div>";
}
add_action( 'noptin_render_editor_radio_button', 'noptin_render_editor_radio_button', 10, 2 );

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
