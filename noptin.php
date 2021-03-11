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
 * Version:         1.4.4
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
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! defined( 'NOPTIN_VERIFY_NONCE' ) ) {
	define( 'NOPTIN_VERIFY_NONCE', false );
}

/**
 * Plugin main class
 *
 * @property Noptin_Background_Sync bg_sync
 * @since       1.0.0
 */
class Noptin {

	/**
	 * The current plugin version.
	 *
	 * @var         string Plugin version
	 * @since       1.0.0
	 */
	public $version = '1.4.4';

	/**
	 * The current database version.
	 *
	 * @var         int Plugin db version
	 * @since       1.0.0
	 */
	public $db_version = 4;

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
	 * Background Mailer
	 *
	 * @var Noptin_Background_Mailer
	 * @access      public
	 * @since       1.2.3
	 */
	public $bg_mailer = null;

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

		// Load files / register the autoloader.
		$this->load_files();

		// If autoloading failed.
		if ( ! class_exists( 'Noptin_Hooks' ) ) {
			return;
		}

		// Set up globals.
		$this->setup_globals();

		// Set up hooks.
		$this->register_hooks();

		/**
		 * Fires after Noptin loads.
		 *
		 * @param Noptin $noptin The Noptin instance.
		 * @since 1.0.7
		 */
		do_action( 'noptin_load', $this );

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
		require_once $plugin_path . 'includes/libraries/action-scheduler/action-scheduler.php';

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
		global $wpdb;

		// Set up globals;
		$this->plugin_path = plugin_dir_path( __FILE__ );
		$this->plugin_url  = plugins_url( '/', __FILE__ );

		// Mailer.
		$this->mailer = new Noptin_Mailer(); 

		// Hooks class.
		$this->hooks = new Noptin_Hooks();

		// Register our custom meta table.
		$wpdb->noptin_subscribermeta = $wpdb->prefix . 'noptin_subscriber_meta';
	}

	/**
	 * Registers hooks.
	 *
	 * @access      public
	 * @since       1.2.3
	 * @return      void
	 */
	private function register_hooks() {

		// Init the plugin after WP inits
		add_action( 'init', array( $this, 'init' ), 5 );

		// Init integrations.
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 5 );

		// Register subscription block.
		add_action( 'init', array( $this, 'register_blocks' ) );

		// Set up localisation.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ), 1 );

		// (Maybe) upgrade the database;
		add_action( 'init', array( $this, 'maybe_upgrade_db' ) );

		// Load css and js.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// css body class.
		add_filter( 'body_class', array( $this, 'body_class' ) );

		// Register our new widget.
		add_action( 'widgets_init', array( $this, 'register_widget' ) );

	}

	/**
	 * Load integrations after plugins are loaded.
	 * 
	 * @access      public
	 * @since       1.3.3
	 */
	public function plugins_loaded () {
		$this->integrations = new Noptin_Integrations();
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

		// Bg processes.
		$this->bg_mailer          = new Noptin_Background_Mailer();
		$this->post_notifications = new Noptin_New_Post_Notify();
		$this->post_notifications->init();

		// Init the admin.
		$this->admin 			  = Noptin_Admin::instance();
		$this->admin->init();

		// Actions page controller.
		$this->actions_page 	  = new Noptin_Page();

		// Post types controller.
		$this->post_types   	  = new Noptin_Post_Types();

		// Form types.
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
	 * Registers front end scripts
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      void
	 */
	public function register_scripts() {

		// The JS used to render the block in the editor backend
		wp_register_script(
			'noptin_blocks',
			$this->plugin_url . 'includes/assets/js/dist/blocks.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'underscore' ),
			filemtime( $this->plugin_path . 'includes/assets/js/dist/blocks.js' )
		);

		// The css used to style the block in the editor backend
		wp_register_style(
			'noptin_blocks',
			$this->plugin_url . 'includes/assets/css/blocks.css',
			array(),
			filemtime( $this->plugin_path . 'includes/assets/css/blocks.css' )
		);

		// The JS used on the frontend
		wp_register_script(
			'noptin_front',
			$this->plugin_url . 'includes/assets/js/dist/frontend.js',
			array( 'jquery' ),
			filemtime( $this->plugin_path . 'includes/assets/js/dist/frontend.js' ),
			true
		);

		$params = array(
			'ajaxurl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'noptin' ),
			'cookie'      => get_noptin_option( 'subscribers_cookie' ),
			'cookie_path' => COOKIEPATH,
		);
		wp_localize_script( 'noptin_front', 'noptin', $params );

		// The css used to style the frontend
		wp_register_style(
			'noptin_front',
			$this->plugin_url . 'includes/assets/css/frontend.css',
			array(),
			filemtime( $this->plugin_path . 'includes/assets/css/frontend.css' )
		);
	}

	/**
	 * Registers front end scripts
	 *
	 * @access      public
	 * @since       1.0.2
	 * @return      void
	 */
	public function enqueue_scripts() {

		// Register the assets...
		$this->register_scripts();

		// ... then enqueue them
		wp_enqueue_script( 'noptin_front' );
		wp_enqueue_style( 'noptin_front' );
	}

	/**
	 * Registers the optin block
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function register_blocks() {

		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}

		/**
		 * Fires before editor blocks are registered
		 *
		 * @param Noptin $noptin The Noptin instance.
		 * @since 1.0.0
		*/
		do_action( 'noptin_before_register_blocks', $this );

		// Register  js scripts and css styles
		$this->register_scripts();

		// Register the blocks
		register_block_type(
			'noptin/email-optin',
			array(
				'style'         => 'noptin_front',
				'editor_script' => 'noptin_blocks',
				'script'        => 'noptin_front',
				'editor_style'  => 'noptin_blocks',
			)
		);
	}


	/**
	 * Registers a widget area
	 *
	 * @access      public
	 * @since       1.0.2
	 * @return      void
	 */
	public function register_widget() {
		register_widget( 'Noptin_Widget' );
		register_widget( 'Noptin_Sidebar' );
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
		$classes['noptin']  = 'noptin';
		$classes['noptinv'] = 'noptin-v' . sanitize_html_class( $this->version );
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
			new Noptin_Install( $installed_version );
			update_option( 'noptin_db_version', $this->db_version );
		}

		// Ensure all tables we successfully created.
		$tables = array( 'automation_rules', 'subscribers_meta', 'subscribers' );

		foreach ( $tables as $table ) {

			$option   = "noptin_{$table}_table_exists";
			$function = "noptin_{$table}_table_exists";

			// Do not run the query if the table is created.
			if ( ! get_option( $option ) ) {

				// Check if the table was created.
				if ( call_user_func( $function ) ) {
					update_option( $option, 1 );
				}

				// If not, create the table.
				else {
					new Noptin_Install( $table );
				}

			}

		}

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

// Kickstart everything
Noptin::instance();
