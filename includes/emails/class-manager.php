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
class Noptin_Email_Manager {

	/** @var Noptin_Email_Sender */
	public $sender;

	/** @var Noptin_Emails_Admin */
	public $admin;

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
		add_action( 'plugins_loaded', array( $this, 'add_hooks' ) );

	}

	/**
	 * Loads required files.
	 */
	public function load_files() {

		require_once plugin_dir_path( __FILE__ ) . 'emails.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-email-sender.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-generator.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-html-to-text.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-emails-admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-email-tags.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-automated-email.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-newsletter-email.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-email-type.php';
		require_once plugin_dir_path( __FILE__ ) . 'automated-email-types/class-type.php';
		require_once plugin_dir_path( __FILE__ ) . 'automated-email-types/class-types.php';

	}

	/**
	 * Init class properties.
	 */
	public function init() {
		$this->sender                = new Noptin_Email_Sender();
		$this->admin                 = new Noptin_Emails_Admin();
		$this->tags                  = new Noptin_Email_Tags();
		$this->automated_email_types = new Noptin_Automated_Email_Types();
	}

	/**
	 * Add hooks
	 *
	 */
	public function add_hooks() {

		add_action( 'delete_post', array( $this, 'delete_stats' ) );

		$this->sender->add_hooks();
		$this->admin->add_hooks();
		$this->tags->add_hooks();
		$this->automated_email_types->add_hooks();
	}

	/**
	 * Deletes campaign stats when the campaign is deleted.
	 *
	 * @param int $post_id the campaign whose stats should be deleted.
	 */
	public function delete_stats( $post_id ) {
		global $wpdb;

		$wpdb->delete(
			get_noptin_subscribers_meta_table_name(),
			array(
				'meta_key' => "_campaign_$post_id",
			)
		);

	}

}
