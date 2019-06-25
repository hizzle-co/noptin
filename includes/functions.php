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
 * Returns all optin forms
 */
function noptin_get_optin_forms(){
    $args   = array(
        'numberposts'      => -1,
        'post_type'        => 'noptin-form',
        'post_status'      => array( 'draft', 'publish' )
    );
    return get_posts( $args );
}

/**
 * Creates a new optin form
 * 
 * @param string $title Optional. The name of the new form
 */
function noptin_create_optin_form( $title = false ){

    //Set the title
    $_title = __( 'New Form', 'noptin');
    if(! $title ){
        $_title = $title;
    }

    //Prepare the args...
    $postarr   = array(
        'post_title'       => $_title ,
        'post_type'        => 'noptin-form',
    );

    //... then create the form
    $id = wp_insert_post( $postarr, true );

    //If an error occured, return it
    if( is_wp_error($id) ) {
        return $id;
    }

    //Maybe give the form a better name
    if(! $title ){
        $postarr   = array(
            'post_title'        => sprintf( __( 'Form #%s', 'noptin') , $id ),
            'ID'                => $id,
        );
        return wp_update_post( $postarr, true );
    }
    
    return $id;
    
}

/**
 * Deletes an optin form
 */
function noptin_delete_optin_form( $id ){
    return wp_delete_post( $id, true );
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
    return $return;

}