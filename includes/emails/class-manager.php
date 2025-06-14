<?php
/**
 * Emails API: Emails Manager.
 *
 * Contains the main class for Noptin emails
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main class for Noptin emails.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
#[AllowDynamicProperties]
class Noptin_Email_Manager {

	/** @var Noptin_Email_Tags */
	public $tags;

	/** @var Noptin_Automated_Email_Types */
	public $automated_email_types;

	/**
	 * Class constructor.
	 *
	 */
	public function __construct() {

		// Load files.
		$this->load_files();

		// Init class properties.
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		// Add hooks.
		add_action( 'after_setup_theme', array( $this, 'add_hooks' ) );
	}

	/**
	 * Loads required files.
	 */
	public function load_files() {

		require_once plugin_dir_path( __FILE__ ) . 'class-generator.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-html-to-text.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-email-tags.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-email-type.php';
		require_once plugin_dir_path( __FILE__ ) . 'automated-email-types/class-type.php';
		require_once plugin_dir_path( __FILE__ ) . 'automated-email-types/class-type-automation-rule.php';
		require_once plugin_dir_path( __FILE__ ) . 'automated-email-types/class-types.php';
	}

	/**
	 * Init class properties.
	 */
	public function init() {
		$this->tags                  = new Noptin_Email_Tags();
		$this->automated_email_types = new Noptin_Automated_Email_Types();

		do_action( 'noptin_email_manager_init', $this );
	}

	/**
	 * Add hooks
	 *
	 */
	public function add_hooks() {
		$this->tags->add_hooks();
		$this->automated_email_types->add_hooks();
	}
}
