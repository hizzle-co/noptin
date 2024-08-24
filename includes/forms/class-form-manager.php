<?php
/**
 * Forms API: Form Manager.
 *
 * Contains the main class for managing Noptin forms
 *
 * @since   1.6.2
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * This class takes care of all form related functionality
 *
 * Do not interact with this class directly, use `noptin_get_optin_form` and related functions instead.
 *
 */
class Noptin_Form_Manager {

	/**
	 * @var Noptin_Form_Output_Manager
	 */
	public $output_manager;

	/**
	 * @var Noptin_Form_Tags
	 */
	public $tags;

	/**
	* @var Noptin_Form_Previewer
	*/
	public $previewer;

	/**
	 * @var Noptin_Form_Asset_Manager
	 */
	public $assets;

	/**
	 * @var Noptin_Form_Admin
	 */
	public $admin;

	/**
	 * Constructor
	 */
	public function __construct() {

		// Load files.
		$this->load_files();

		// Init class properties.
		$this->output_manager = new Noptin_Form_Output_Manager();
		$this->tags           = new Noptin_Form_Tags();
		$this->previewer      = new Noptin_Form_Previewer();
		$this->assets         = new Noptin_Form_Asset_Manager();
		$this->admin          = new Noptin_Form_Admin();

		add_action( 'plugins_loaded', array( $this, 'add_hooks' ), 5 );

	}

	/**
	 * Loads required files.
	 */
	public function load_files() {

		require_once plugin_dir_path( __FILE__ ) . 'class-form-element.php'; // Displays opt-in forms.
		require_once plugin_dir_path( __FILE__ ) . 'class-form-tags.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-form-previewer.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-form-admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-form.php'; // Container for a single form.
		require_once plugin_dir_path( __FILE__ ) . 'class-form-legacy.php'; // Container for a single legacy form.
		require_once plugin_dir_path( __FILE__ ) . 'class-asset-manager.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-output-manager.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-widget.php'; // Opt-in form widget.

	}

	/**
	 * Register relevant hooks.
	 */
	public function add_hooks() {

		/**
		 * Fires before the form manager inits.
		 *
		 * @param Noptin_Form_Manager $manager
		 * @since 1.6.2
		 */
		do_action( 'before_init_noptin_form_manager', $this );

		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'disable_block_editor_for_forms' ), 10, 2 );

		// Init modules.
		$this->output_manager->add_hooks();
		$this->assets->add_hooks();
		$this->tags->add_hooks();
		$this->previewer->add_hooks();
		$this->admin->add_hooks();

		/**
		 * Fires after the form manager inits.
		 *
		 * @param Noptin_Form_Manager $manager
		 * @since 1.6.2
		 */
		do_action( 'init_noptin_form_manager', $this );

	}

	/**
	 * Register our form post type.
	 */
	public function register_post_type() {

		if ( ! is_blog_installed() || post_type_exists( 'noptin-form' ) ) {
			return;
		}

		/**
		 * Fires before the newsletter form post type is registered.
		 *
		 * @param Noptin_Form_Manager $manager
		 * @since 1.6.2
		 */
		do_action( 'before_register_noptin_form_post_type', $this );

		// Register post type.
		register_post_type(
			'noptin-form',
			apply_filters(
				'noptin_optin_form_post_type_details',
				array(
					'labels'              => array(
						'name'               => _x( 'Subscription Forms', 'Post type general name', 'newsletter-optin-box' ),
						'singular_name'      => _x( 'Subscription Form', 'Post type singular name', 'newsletter-optin-box' ),
						'menu_name'          => _x( 'Subscription Forms', 'Admin Menu text', 'newsletter-optin-box' ),
						'name_admin_bar'     => _x( 'Subscription Form', 'Add New on Toolbar', 'newsletter-optin-box' ),
						'add_new'            => __( 'Add New', 'newsletter-optin-box' ),
						'add_new_item'       => __( 'Add New Form', 'newsletter-optin-box' ),
						'new_item'           => __( 'New Form', 'newsletter-optin-box' ),
						'edit_item'          => __( 'Edit Form', 'newsletter-optin-box' ),
						'view_item'          => __( 'View Form', 'newsletter-optin-box' ),
						'search_items'       => __( 'Search Forms', 'newsletter-optin-box' ),
						'parent_item_colon'  => __( 'Parent Forms:', 'newsletter-optin-box' ),
						'not_found'          => __( 'No forms found.', 'newsletter-optin-box' ),
						'not_found_in_trash' => __( 'No forms found in Trash.', 'newsletter-optin-box' ),
					),
					'label'               => __( 'Subscription Forms', 'newsletter-optin-box' ),
					'description'         => '',
					'public'              => false,
					'show_ui'             => true,
					'map_meta_cap'        => true,
					'publicly_queryable'  => false,
					'exclude_from_search' => true,
					'hierarchical'        => false,
					'query_var'           => false,
					'supports'            => array( 'title', 'custom-fields' ),
					'has_archive'         => false,
					'show_in_nav_menus'   => false,
					'show_in_rest'        => true,
					'show_in_menu'        => false,
					'menu_icon'           => '',
					'can_export'          => false,
				)
			)
		);

		/**
		 * Fires after the newsletter form post type is registered.
		 *
		 * @param Noptin_Form_Manager $manager
		 * @since 1.6.2
		 */
		do_action( 'register_noptin_form_post_type', $this );

	}

	/**
	 * Register our form widget.
	 */
	public function register_widget() {

		/**
		 * Fires before the newsletter sign-up widget is registered.
		 *
		 * @param Noptin_Form_Manager $manager
		 * @since 1.6.2
		 */
		do_action( 'before_register_noptin_form_widget', $this );

		// Displays a normal sign-up form.
		register_widget( 'Noptin_Sidebar' );

		// Allows users to create forms on the fly.
		if ( ! is_using_new_noptin_forms() ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-widget-legacy.php';
			register_widget( 'Noptin_Widget' );
		}

		/**
		 * Fires after the newsletter sign-up widget is registered.
		 *
		 * @param Noptin_Form_Manager $manager
		 * @since 1.6.2
		 */
		do_action( 'register_noptin_form_widget', $this );

	}

	/**
	 * Displays a subscription form.
	 *
	 * @param int|array $form_id_or_configuration An id of a saved form or an array of arguments with which to generate a form on the fly.
	 * @param bool $echo Whether to display the form or return its HTML.
	 * @see Noptin_Form_Output_Manager::shortcode()
	 * @see show_noptin_form()
	 * @return string
	 */
	public function show_form( $form_id_or_configuration = array(), $echo = true ) {

		// If a form id was passed, convert it into arguments.
		if ( is_numeric( $form_id_or_configuration ) ) {
			$form_id_or_configuration = array( 'form' => (int) $form_id_or_configuration );
		}

		// Ensure we have an array.
		if ( ! is_array( $form_id_or_configuration ) ) {
			$form_id_or_configuration = array();
		}

		// Generate the form markup.
		if ( ! $echo ) {
			return $this->output_manager->shortcode( $form_id_or_configuration );
		}

		$this->output_manager->display_form( $form_id_or_configuration );
	}

	/**
	 * Return all tags
	 *
	 * @return array
	 */
	public function get_tags() {
		return $this->tags->all();
	}

	/**
	 * Disables the block editor for forms.
	 *
	 * @param bool $use_block_editor Whether to use the block editor.
	 * @param string $post_type The post type being edited.
	 * @return bool
	 */
	public function disable_block_editor_for_forms( $use_block_editor, $post_type ) {
		if ( 'noptin-form' === $post_type ) {
			return false;
		}

		return $use_block_editor;
	}
}
