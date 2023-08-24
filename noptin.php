<?php

/**
 * Noptin
 *
 * Simple WordPress optin form
 *
 *
 * Plugin Name:     Noptin - WordPress Newsletter Plugin
 * Plugin URI:      https://noptin.com
 * Description:     A very fast and lightweight WordPress newsletter plugin
 * Author:          Noptin Newsletter
 * Author URI:      https://github.com/picocodes
 * Version:         2.0.3
 * Text Domain:     newsletter-optin-box
 * License:         GPLv3
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:     /languages
 *
 * @since           1.0.0
 * @author          Picocodes
 * @author          Kaz
 * @license         GNU General Public License, version 3
 * @copyright       Picocodes
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'NOPTIN_VERIFY_NONCE' ) ) {
	define( 'NOPTIN_VERIFY_NONCE', false );
}

define( 'MINIMUM_SUPPORTED_NOPTIN_ADDONS_PACK_VERSION', '2.0.0' );

/**
 * Plugin main class
 *
 * @since       1.0.0
 */
class Noptin {

	/**
	 * The current plugin version.
	 *
	 * @var         string Plugin version
	 * @since       1.0.0
	 */
	public $version = '2.0.3';

	/**
	 * The current database version.
	 *
	 * @var         int Plugin db version
	 * @since       1.0.0
	 */
	public $db_version = 5;

	/**
	 * Stores the main Noptin instance.
	 *
	 * @access      private
	 * @var         Noptin $instance The one true noptin
	 * @since       1.0.0
	 */
	private static $instance = null;

	/**
	 * The main plugin file.
	 *
	 * @access      public
	 * @var         string Main plugin file;
	 * @since       1.0.0
	 */
	public static $file = __FILE__;

	/**
	 * Local path to this plugins root directory
	 *
	 * @access      public
	 * @var         string|null the local plugin path.
	 * @since       1.0.0
	 */
	public $plugin_path = null;

	/**
	 * Web path to this plugins root directory.
	 *
	 * @var         string|null the plugin url path.
	 * @access      public
	 * @since       1.0.0
	 */
	public $plugin_url = null;

	/**
	 * White Label
	 *
	 * @var Noptin_White_Label
	 * @access      public
	 * @since       1.7.7
	 */
	public $white_label = null;

	/**
	 * New post notifications.
	 *
	 * @var Noptin_New_Post_Notify
	 * @access      public
	 * @since       1.2.3
	 */
	public $post_notifications = null;

	/**
	 * A state of the art email sender.
	 *
	 * @var Noptin_Mailer
	 * @since 1.2.8
	 */
	public $mailer = null;

	/**
	 * Automation Rules.
	 *
	 * @var Noptin_Automation_Rules
	 * @since       1.2.8
	 */
	public $automation_rules;

	/**
	 * The class responsible for registering various hooks and filters.
	 *
	 * @var Noptin_Hooks
	 * @since       1.2.9
	 */
	public $hooks;

	/**
	 * The main admin class..
	 *
	 * @var Noptin_Admin
	 * @since       1.2.9
	 */
	public $admin;

	/**
	 * The main custom fields class.
	 *
	 * @var Noptin_Custom_Fields
	 * @since       1.5.5
	 */
	public $custom_fields;

	/**
	 * The main forms class.
	 *
	 * @var Noptin_Form_Manager
	 * @since       1.6.2
	 */
	public $forms;

	/**
	 * The main emails class.
	 *
	 * @var Noptin_Email_Manager
	 * @since       1.7.0
	 */
	public $emails;

	/**
	 * The main integrations class.
	 *
	 * @var Noptin_Integrations
	 * @since       1.7.0
	 */
	public $integrations;

	/**
	 * Actions page.
	 *
	 * @var Noptin_Actions_Page
	 * @since       1.7.0
	 */
	public $actions_page;

	/**
	 * Post types.
	 *
	 * @var Noptin_Post_Types
	 */
	public $post_types;

	/**
	 * Popups class.
	 *
	 * @var Noptin_Popups
	 */
	public $popups;

	/**
	 * Inpost class.
	 *
	 * @var Noptin_Inpost
	 */
	public $inpost;

	/**
	 * The main ajax class.
	 *
	 * @var Noptin_Ajax
	 */
	public $ajax;

	/**
	 * Get active instance
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      Noptin The one true Noptin
	 */
	public static function instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Class Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		// Prevent double initialization.
		if ( function_exists( 'noptin' ) ) {
			return;
		}

		// Load files / register the autoloader.
		$this->load_files();

		// If autoloading failed.
		if ( ! class_exists( 'Noptin_Hooks' ) ) {
			return;
		}

		// Set up globals.
		$this->setup_globals();

		// Set up hooks.
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 5 );

	}

	/**
	 * Includes files.
	 *
	 * @access      public
	 * @since       1.2.3
	 * @return      void
	 */
	private function load_files() {

		$plugin_path = plugin_dir_path( __FILE__ );

		// Non-class files.
		require_once $plugin_path . 'vendor/autoload.php';
		require_once $plugin_path . 'includes/functions.php';
		require_once $plugin_path . 'includes/subscriber.php';
		require_once $plugin_path . 'includes/forms/forms.php';
		require_once $plugin_path . 'includes/forms/class-form-manager.php';
		require_once $plugin_path . 'includes/emails/class-manager.php';
		require_once $plugin_path . 'includes/libraries/noptin-com/class-noptin-com.php';

		// Register autoloader.
		try {
			spl_autoload_register( array( $this, 'autoload' ), true );
		} catch ( Exception $e ) {
			log_noptin_message( $e->getMessage() );
		}

	}

	/**
	 * Sets up globals.
	 *
	 * @access      public
	 * @since       1.2.3
	 * @return      void
	 */
	private function setup_globals() {

		// Set up globals;
		$this->plugin_path = plugin_dir_path( __FILE__ );
		$this->plugin_url  = plugins_url( '/', __FILE__ );

		// Form manager.
		$this->forms = new Noptin_Form_Manager();

		// Email manager.
		$this->emails = new Noptin_Email_Manager();

		// Hooks class.
		$this->hooks = new Noptin_Hooks();

	}

	/**
	 * Load integrations after plugins are loaded.
	 *
	 * @access public
	 * @since  1.3.3
	 */
	public function plugins_loaded() {

		// Remove addons pack if version is less than 2.0.0
		if ( defined( 'NOPTIN_ADDONS_PACK_VERSION' ) && version_compare( NOPTIN_ADDONS_PACK_VERSION, MINIMUM_SUPPORTED_NOPTIN_ADDONS_PACK_VERSION, '<' ) ) {
			remove_action( 'noptin_integrations_load', 'noptin_addons_pack_load' );
		}

		// Integrations.
		$this->integrations = new Noptin_Integrations();

		// Custom fields.
		$this->custom_fields = new Noptin_Custom_Fields();

		// DB.
		$this->db();

		// REST.
		$this->api();

		// Bulk email sender.
		$this->bulk_emails();

		if ( empty( $this->white_label ) ) {
			$this->white_label = new Noptin_White_Label();
		}

		// Init scripts.
		Noptin_Scripts::init();

		// Init the plugin after WP inits
		add_action( 'init', array( $this, 'init' ), 5 );

		// Set up localisation.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ), 1 );

		// (Maybe) upgrade the database;
		add_action( 'init', array( $this, 'maybe_upgrade_db' ), 0 );

		// css body class.
		add_filter( 'body_class', array( $this, 'body_class' ) );

		/**
		 * Fires after Noptin loads.
		 *
		 * @param Noptin $noptin The Noptin instance.
		 * @since 1.0.7
		 */
		do_action( 'noptin_load', $this );
	}

	/**
	 * Init the plugin after WordPress inits.
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      void
	 */
	public function init() {

		/**
		 * Fires after WordPress inits but before Noptin inits
		 *
		 * @param Noptin $noptin The Noptin instance.
		 * @since 1.0.0
		 */
		do_action( 'before_noptin_init', $this );

		// Init the admin.
		$this->admin 			  = Noptin_Admin::instance();
		$this->admin->init();

		// Actions page controller.
		$this->actions_page 	  = new Noptin_Page();

		// Post types controller.
		$this->post_types   	  = new Noptin_Post_Types();

		// Form types (legacy).
		$this->popups = new Noptin_Popups();
		$this->inpost = new Noptin_Inpost();

		// Ajax.
		$this->ajax 			  = new Noptin_Ajax();

		// Automation tasks.
		$this->automation_rules   = new Noptin_Automation_Rules();

		/**
		 * Fires after Noptin inits
		 *
		 * @param Noptin $noptin The Noptin instance.
		 * @since 1.0.0
		 */
		do_action( 'noptin_init', $this );
	}

	/**
	 * Class autoloader
	 *
	 * @param       string $class_name The name of the class to load.
	 * @access      public
	 * @since       1.2.3
	 * @return      void
	 */
	public function autoload( $class_name ) {

		// Normalize the class name...
		$class_name  = strtolower( $class_name );

		// ... and make sure it is our class.
		if ( false === strpos( $class_name, 'noptin' ) ) {
			return;
		}

		// Next, prepare the file name from the class.
		$file_name = 'class-' . str_replace( '_', '-', $class_name ) . '.php';

		// Base path of the classes.
		$plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );

		// And an array of possible locations in order of importance;
		$locations = array(
			"$plugin_path/includes",
			"$plugin_path/includes/admin",
			"$plugin_path/includes/integrations",
			"$plugin_path/includes/automation-rules",
			"$plugin_path/includes/automation-rules/actions",
			"$plugin_path/includes/automation-rules/triggers",
		);

		foreach ( apply_filters( 'noptin_autoload_locations', $locations ) as $location ) {

			if ( file_exists( trailingslashit( $location ) . $file_name ) ) {
				include trailingslashit( $location ) . $file_name;
				break;
			}
		}

	}

	/**
	 * Filters the body classes
	 *
	 * @access      public
	 * @param       array $classes Array of existing class names.
	 * @since       1.1.1
	 * @return      array
	 */
	public function body_class( $classes ) {
		$classes['noptin'] = 'noptin';
		return $classes;
	}

	/**
	 * Runs installation
	 *
	 * @since 1.0.5
	 * @access public
	 */
	public function maybe_upgrade_db() {

		$installed_version = absint( get_option( 'noptin_db_version', 0 ) );

		// Upgrade db if installed version of noptin is lower than current version
		if ( $installed_version < $this->db_version ) {
			new Noptin_Install( $installed_version, $this->db_version );
		}
	}

	/**
     * Returns the new DB manager.
     *
     * @return \Hizzle\Noptin\DB\Main
     */
	public function db() {
		return \Hizzle\Noptin\DB\Main::instance();
	}

	/**
     * Returns the new REST API manager.
     *
     * @return \Hizzle\Noptin\REST\Main
     */
	public function api() {
		return \Hizzle\Noptin\REST\Main::instance();
	}

	/**
	 * Bulk email sender.
	 *
	 * @return \Hizzle\Noptin\Bulk_Emails\Main
	 */
	public function bulk_emails() {
		return \Hizzle\Noptin\Bulk_Emails\Main::instance();
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/plugins/newsletter-optin-box-LOCALE.mo
	 *      - WP_PLUGIN_DIR/newsletter-optin-box/languages/newsletter-optin-box-LOCALE.mo
	 *
	 * @since 1.1.9
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'newsletter-optin-box',
			false,
			plugin_basename( dirname( __FILE__ ) ) . '/languages/'
		);

	}

}

// Kickstart everything.
Noptin::instance();
