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
	 * @var Noptin_Form_Tags
	 */
	public $tags;

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
		$this->tags  = new Noptin_Form_Tags();
		$this->admin = new Noptin_Form_Admin();

		add_action( 'plugins_loaded', array( $this, 'add_hooks' ), 5 );
	}

	/**
	 * Loads required files.
	 */
	public function load_files() {

		require_once plugin_dir_path( __FILE__ ) . 'class-form-element.php'; // Displays opt-in forms.
		require_once plugin_dir_path( __FILE__ ) . 'class-form-tags.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-form-admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-form.php'; // Container for a single form.
		require_once plugin_dir_path( __FILE__ ) . 'class-form-legacy.php'; // Container for a single legacy form.
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

		// Init modules.
		$this->tags->add_hooks();
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
	 * Displays a subscription form.
	 *
	 * @param int|array $form_id_or_configuration An id of a saved form or an array of arguments with which to generate a form on the fly.
	 * @param bool $display Whether to display the form or return its HTML.
	 * @see Noptin_Form_Output_Manager::shortcode()
	 * @see show_noptin_form()
	 * @return string
	 */
	public function show_form( $form_id_or_configuration = array(), $display = true ) {
		return show_noptin_form( $form_id_or_configuration, $display );
	}

	/**
	 * Return all tags
	 *
	 * @return array
	 */
	public function get_tags() {
		return $this->tags->all();
	}
}
