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

/**
 * Returns a reference to the main Noptin instance
 */
function noptin() {
    return Noptin::instance();
}

/**
 * Returns an optin form
 */
function noptin_get_optin_form( $id ){
    return new Noptin_Form( $id );
}

/**
 * Creates an optin form
 */
function noptin_create_optin_form( $data = false ){
    $form    = new Noptin_Form( $data );
    $created = $form->save();

    if( is_wp_error( $created ) ) {
        return $created;
    }

    return $form->id;
}


/**
 * Deletes an optin form
 */
function noptin_delete_optin_form( $id ){
    return wp_delete_post( $id, true );
}

/**
 * Duplicates an optin form
 */
function noptin_duplicate_optin_form( $id ){
    $form = noptin_get_optin_form( $id );
    $form->duplicate();
    return $form->id;
}

/**
 * Returns all optin forms
 */
function noptin_get_optin_forms( $meta_key = '', $meta_value = '', $compare = '='){
    $args   = array(
        'numberposts'      => -1,
        'post_type'        => 'noptin-form',
        'post_status'      => array( 'draft', 'publish' )
    );

    if( $meta_key ) {
        $args['meta_query'] = array(
                array(
                    'key'       => $meta_key,
                    'value'     => $meta_value,
                    'compare'   => $compare,
                )
            );

    }
    return get_posts( $args );
}

/**
 * Returns optin post type details
 */
function noptin_get_optin_form_post_type_details(){
	return apply_filters(
		'noptin_optin_form_post_type_details',
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
        if( is_scalar( $val) ) {
            $val     = esc_attr($val);
            $return .= ' ' . $attr . '="' . $val . '"';
        }
    }
    return $return;

}

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
    unset( $return['attachment'] );

    return $return;

}

/**
 * Returns color themess
 */
function noptin_get_color_themes(){
    return apply_filters(
		'noptin_form_color_themes',
		array(
            'Red'           => '#e51c23 #fafafa #c62828', //Base color, Secondary color, border color
            'Pink'          => '#e91e63 #fafafa #ad1457',
            'Purple'        => '#9c27b0 #fafafa #6a1b9a',
            'Deep Purple'   => '#673ab7 #fafafa #4527a0',
            'Purple'        => '#9c27b0 #fafafa #4527a0',
            'Indigo'        => '#3f51b5 #fafafa #283593',
            'Blue'          => '#2196F3 #fafafa #1565c0',
            'Light Blue'    => '#03a9f4 #fafafa #0277bd',
            'Cyan'          => '#00bcd4 #fafafa #00838f',
            'Teal'          => '#009688 #fafafa #00695c',
            'Green'         => '#4CAF50 #fafafa #2e7d32',
            'Light Green'   => '#8bc34a #191919 #558b2f',
            'Lime'          => '#cddc39 #191919 #9e9d24',
            'Yellow'        => '#ffeb3b #191919 #f9a825',
            'Amber'         => '#ffc107 #191919 #ff6f00',
            'Orange'        => '#ff9800 #fafafa #e65100',
            'Deep Orange'   => '#ff5722 #fafafa #bf360c',
            'Brown'         => '#795548 #fafafa #3e2723',
            'Blue Grey'     => '#607d8b #fafafa #263238',
            'Black'         => '#313131 #fafafa #607d8b',
            'White'         => '#ffffff #191919 #191919',
            'Grey'          => '#aaaaaa #191919 #191919',
        ));

}

/**
 * Returns optin templates
 */
function noptin_get_optin_templates(){
    $templates = get_option( 'noptin_templates' );

    if(! is_array( $templates ) ) {
        $templates = array();
    }

    return apply_filters( 'noptin_form_templates', $templates );

}

/**
 * Returns color themess
 */
function noptin_get_form_design_props(){
    return apply_filters(
		'noptin_form_design_props',
		array(
			'hideCloseButton', 'closeButtonPos', 'singleLine', 'formRadius', 'formWidth',
			'formHeight', 'noptinFormBg', 'fields', 'imageMain',
            'noptinFormBorderColor', 'image', 'imagePos', 'noptinButtonLabel', 'buttonPosition',
            'noptinButtonBg', 'noptinButtonColor', 'hideTitle', 'title', 'titleColor',
            'hideDescription', 'description', 'descriptionColor', 'hideNote', 'hideOnNoteClick',
            'note', 'noteColor', 'CSS', 'optinType'
        ));

}

/**
 * Function noptin editor localize
 */
function noptin_localize_optin_editor( $state ){
	$props   = noptin_get_form_design_props();
	$props[] = 'DisplayOncePerSession';
	$props[] = 'timeDelayDuration';
	$props[] = 'scrollDepthPercentage';
	$props[] = 'cssClassOfClick';
	$props[] = 'triggerPopup';

    $params = array(
        'ajaxurl'      => admin_url('admin-ajax.php'),
        'api_url'      => get_home_url( null, 'wp-json/wp/v2/'),
        'nonce'        => wp_create_nonce('noptin_admin_nonce'),
        'data'         => $state,
        'templates'    => noptin_get_optin_templates(),
        'color_themes' => noptin_get_color_themes(),
        'design_props' => $props,
    );
    wp_localize_script('noptin', 'noptinEditor', $params);
}

/**
 * Function noptin editor localize
 */
function noptin_form_template_form_props(){

    $style = array(
		'width: formWidth',
		'minHeight: formHeight',
	);

	$style = '"{' . implode( ',', $style ) . '}"';
	$class = "singleLine ? 'noptin-form-single-line' : 'noptin-form-new-line'";

	return " @submit.prevent :class=\"$class\" :style=$style";
}

/**
 * Function noptin editor localize
 */
function noptin_form_template_wrapper_props(){

    $props = array(
		':data-trigger="triggerPopup"',
		':data-after-click="cssClassOfClick"',
		':data-on-scroll="scrollDepthPercentage"',
		':data-after-delay="timeDelayDuration"',
		'class="noptin-optin-form-wrapper"',
		':data-once-per-session="DisplayOncePerSession"',
		':style="{borderColor: noptinFormBorderColor,  backgroundColor: noptinFormBg, borderRadius: formRadius}"'
	);

	return implode( ' ', $props );
}
