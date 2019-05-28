<?php
/**
 * Admin section
 *
 * Simple WordPress optin form
 *
 * @since             1.0.0
 *
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    die;
}

function noptin() {
    return Noptin::instance();
}

/**
 * Renders a single field in the opt-in editor sidebar
 */
function noptin_render_editor_field( $id, $field ){
    if(!empty($field['el'])){
        do_action( 'noptin_render_editor_' . $field['el'], $id, $field );
    }
}

/**
 * Returns all popup forms
 */
function noptin_get_popup_forms(){
    $args   = array(
        'numberposts'      => -1,
        'post_type'        => 'noptin-popup',
        'post_status'      => array( 'draft', 'publish' )
    );
    return get_posts( $args );
}

/**
 * Creates a popup form
 */
function noptin_create_popup_form( $title = false ){

    //Set the title
    if(! $title ){
        $title = __( 'New Form', 'noptin');
    }

    //Prepare the args
    $postarr   = array(
        'post_title'       => $title ,
        'post_type'        => 'noptin-popup',
    );

    return wp_insert_post( $postarr, true );
}

/**
 * Deletes a popup form
 */
function noptin_delete_popup_form( $id ){
    return wp_delete_post( $id, true );
}


/**
 * Returns popup post type details
 */
function noptin_get_popup_post_type_details(){
	return apply_filters(
		'noptin_popup_post_type_details',
		array(
			'labels'              => array(),
			'description'         => '',
			'public'              => false,
			'show_ui'             => false,
			'map_meta_cap'        => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'hierarchical'        => false,
			'query_var'           => false,
			'supports'            => array(),
			'has_archive'         => false,
			'show_in_nav_menus'   => false,
			'show_in_rest'        => false,
			'menu_icon'   		  => ''
		));
}

/**
 * Converts an array into a string of html attributes
 */
function noptin_array_to_attrs( $array ){

    $return = '';
    foreach( $array as $attr=>$val ){
        $val     = esc_attr($val);
        $return .= ' ' . $attr . '="' . $val . '"';
    }
    return $return;

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

        default:
            echo "<label class='$class' $restrict><span class='noptin-label'>$label</span> <input $attrs /></label>";
            break;
    }

}
add_action( 'noptin_render_editor_input', 'noptin_render_editor_input', 10, 2 );

/**
 * Renders a select input field in the opt-in editor sidebar
 */
function noptin_render_editor_select( $id, $field ){
    $label          = empty($field['label']) ? '' : $field['label'];
    $restrict       = empty($field['restrict']) ? '' : ' v-if="' . $field['restrict'] . '" ';
    $multiselect    = 'multiselect' == $field['el'] ? ' multiple="multiple" ' : '';
    $ajax           = is_string($field['options']) ? " ajax='{$field['options']}' " : 'ajax="0"';

    echo "<div class='noptin-select-wrapper' $restrict>$label<noptinselect2 $ajax $multiselect v-model='$id'>";

    if(is_array($field['options'])) {
        foreach( $field['options'] as $val => $label ){
            echo "<option value='$val'>$label</option>";
        }
    }

    echo "</noptinselect2></div>";
}
add_action( 'noptin_render_editor_select', 'noptin_render_editor_select', 10, 2 );
add_action( 'noptin_render_editor_multiselect', 'noptin_render_editor_select', 10, 2 );

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
    $restrict       = empty($field['restrict']) ? '' : ' v-if="' . $field['restrict'] . '" ';

    echo "<div class='noptin-radio-button-wrapper' $restrict><span>$label</span><div class='noptin-buttons'>";

    if(is_array($field['options'])) {
        foreach( $field['options'] as $val => $label ){
            echo "<label><input type='radio' v-model='$id' value='$val' class='screen-reader-text'><span>$label</span></label>";
        }
    }

    echo "</div></div>";
}
add_action( 'noptin_render_editor_radio_button', 'noptin_render_editor_radio_button', 10, 2 );

/**
 * Renders a panel
 */
function noptin_render_editor_panel( $id, $panel ){
    if(!empty($panel['children'])){
        $restrict   = empty($field['restrict']) ? '' : ' v-if="' . $field['restrict'] . '" ';
        $panel_name = "{$panel['id']}Open";
        printf(
            '
            <div %3$s class="noptin-popup-editor-panel">
                <span v-if="%1$s"  @click="%1$s=!%1$s" class="dashicons dashicons-arrow-up-alt2 noptin-popup-editor-panel-toggle"></span>
                <span v-if="!%1$s"  @click="%1$s=!%1$s" class="dashicons dashicons-arrow-down-alt2 noptin-popup-editor-panel-toggle"></span>
                <h2  @click="%1$s=!%1$s" class="noptin-popup-editor-panel-title">%2$s</h2>
                <div class="noptin-popup-editor-panel-body" v-if="%1$s">',
            $panel_name,
            $panel['title'],
            $restrict
        );
        
        foreach( $panel['children'] as $id=>$field ){
            noptin_render_editor_field( $id, $field );
        }
        echo "</div></div>";
    }
}
add_action( 'noptin_render_editor_panel', 'noptin_render_editor_panel', 10, 2 );

/**
 * Returns post types
 */
function noptin_get_post_types(){
    $return = array();
    $args   = array(
        'public'    => true,
        'show_ui'   => true
    );
    $post_types = get_post_types( $args, 'objects' );

    foreach( $post_types as $obj ){
        $return[$obj->name] = $obj->label;
    }
    return $return;

}