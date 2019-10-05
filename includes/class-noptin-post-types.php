<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
    die;
}

    /**
     * Handles registration of post types
     *
     * @since       1.1.1
     */

    class Noptin_Post_Types{

    /**
	 * Class Constructor.
	 */
	public function __construct() {

		//Register post types
		add_action( 'init', array( $this, 'register_post_types') );

		//Remove some meta boxes
		add_action( 'admin_menu', array( $this, 'remove_metaboxes') );

		//And some actions
		add_filter( 'post_row_actions', array( $this, 'remove_actions'), 10, 2 );

		//Register our special meta box
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes') );

    }

    /**
	 * Register post types
	 *
	 */
	public function register_post_types() {

		if ( ! is_blog_installed() || post_type_exists(  'noptin-form' ) ) {
			return;
		}

		/**
		 * Fires before custom post types are registered
		 *
		 * @since 1.0.0
		 *
		*/
		do_action( 'noptin_register_post_type' );

		//Optin forms
		register_post_type( 'noptin-form'	, $this->get_form_post_type_details() );

		/**
		 * Fires after custom post types are registered
		 *
		 * @since 1.0.0
		 *
		*/
		do_action( 'noptin_after_register_post_type' );

	}

    /**
     * Returns registration details for noptin-form post types
     *
     * @access      public
     * @since       1.1.1
     * @return      array
     */
    public function get_form_post_type_details() {

        return apply_filters(
			'noptin_optin_form_post_type_details',
			array(
				'labels'              => array(
					'name'                  => _x( 'Newsletter Forms', 'Post type general name', 'newsletter-optin-box' ),
					'singular_name'         => _x( 'Newsletter Form', 'Post type singular name', 'newsletter-optin-box' ),
					'menu_name'             => _x( 'Newsletter Forms', 'Admin Menu text', 'newsletter-optin-box' ),
					'name_admin_bar'        => _x( 'Newsletter Form', 'Add New on Toolbar', 'newsletter-optin-box' ),
					'add_new'               => __( 'Add New', 'newsletter-optin-box' ),
        			'add_new_item'          => __( 'Add New Form', 'newsletter-optin-box' ),
        			'new_item'              => __( 'New Form', 'newsletter-optin-box' ),
        			'edit_item'             => __( 'Edit Form', 'newsletter-optin-box' ),
        			'view_item'             => __( 'View Form', 'newsletter-optin-box' ),
        			'search_items'          => __( 'Search Forms', 'newsletter-optin-box' ),
					'parent_item_colon'     => __( 'Parent Forms:', 'newsletter-optin-box' ),
					'not_found'             => __( 'No forms found.', 'newsletter-optin-box' ),
        			'not_found_in_trash'    => __( 'No forms found in Trash.', 'newsletter-optin-box' ),
				),
				'label'               => __( 'Newsletter Forms', 'newsletter-optin-box' ),
				'description'         => '',
				'public'              => false,
				'show_ui'             => true,
				'map_meta_cap'        => true,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'hierarchical'        => false,
				'query_var'           => false,
				'supports'            => array( 'author' ),
				'has_archive'         => false,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'show_in_menu'        => 'noptin',
				'menu_icon'   		  => '',
				'can_export'		  => false,
			));

	}

	/**
	 * Removes unnecessary meta boxes from the post edit screen
	 *
	 */
	public function remove_metaboxes() {
		remove_meta_box('submitdiv', 'noptin-form', 'core');
	}

	/**
	 * Removes unnecessary actions
	 *
	 */
	public function remove_actions( $actions, $post ) {

		if( $post->post_type == 'noptin-form' ) {
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;

	}

	/**
	 * Registers the meta box required to render our form edit screen
	 *
	 */
	public function add_meta_boxes( $post_type ) {

		if( $post_type == 'noptin-form' ) {
			add_meta_box(
                'noptin_form_editor',
                __( 'Form Editor', 'textdomain' ),
                array( $this, 'render_form_editor' ),
                $post_type,
                'normal',
                'high'
            );
		}

	}

	/**
	 * Renders the form editor
	 *
	 */
	public function render_form_editor( $post ) {

		$form   = $post->ID;
		$editor = new Noptin_Form_Editor( $form, true );
		$editor->output();

	}

}

new Noptin_Post_Types();
