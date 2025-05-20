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
	 * Constructor
	 */
	public function __construct() {

		// Load files.
		$this->load_files();

		// Init class properties.
		$this->tags  = new Noptin_Form_Tags();
	}

	/**
	 * Loads required files.
	 */
	public function load_files() {

		require_once plugin_dir_path( __FILE__ ) . 'class-form-tags.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-form.php'; // Container for a single form.
		require_once plugin_dir_path( __FILE__ ) . 'class-form-legacy.php'; // Container for a single legacy form.
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
