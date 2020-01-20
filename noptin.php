<?php
/**
 * Noptin
 *
 * Simple WordPress optin form
 *
 * @since             1.0.0
 *
 * Plugin Name:     Noptin - Simple Newsletter Subscription Forms
 * Plugin URI:      https://noptin.com
 * Description:     Easily add a newsletter optin box onto post content, widget or popup
 * Author:          Picocodes
 * Author URI:      https://github.com/picocodes
 * Version:         1.2.2
 * Text Domain:     noptin
 * License:         GPL3+
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:     /languages
 *
 * @author          Picocodes
 * @author          Kaz
 * @license         GNU General Public License, version 3
 * @copyright       Picocodes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

	/**
	 * Plugin main class
	 *
	 * @since       1.0.0
	 */

class Noptin {

	/**
	 * @var       Plugin version
	 * @since       1.0.0
	 */
	public $version = '1.2.2';

	/**
	 * @var       Plugin db version
	 * @since       1.0.0
	 */
	public $db_version = 3;

	/**
	 * @access      private
	 * @var        obj $instance The one true noptin
	 * @since       1.0.0
	 */
	private static $instance = null;

	/**
	 * Local path to this plugins root directory
	 *
	 * @access      public
	 * @since       1.0.0
	 */
	public $plugin_path = null;

	/**
	 * Web path to this plugins root directory
	 *
	 * @access      public
	 * @since       1.0.0
	 */
	public $plugin_url = null;

	/**
	 * Get active instance
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      self::$instance The one true Noptin
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Class Constructor.
	 */
	public function __construct() {

		global $wpdb;

		// Set global variables
		$this->plugin_path = plugin_dir_path( __FILE__ );
		$this->plugin_url  = plugins_url( '/', __FILE__ );

		// Register our custom meta table
		$wpdb->noptin_subscribermeta = $wpdb->prefix . 'noptin_subscriber_meta';

		// Include core files
		$this->includes();

		// Init the plugin after WP inits
		add_action( 'init', array( $this, 'init' ), 5 );

		// Register our new widget
		add_action( 'widgets_init', array( $this, 'register_widget' ) );

		/**
		 * Fires after Noptin loads
		 *
		 * @since 1.0.7
		 */
		do_action( 'noptin_load' );

	}

	/**
	 * Init the plugin
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      void
	 */
	public function init() {

		/**
		 * Fires after WordPress inits but before Noptin inits
		 *
		 * @since 1.0.0
		 */
		do_action( 'before_noptin_init', $this );

		// Init the admin.
		$this->admin = Noptin_Admin::instance();

		// Init the bg mailer.
		$this->bg_mailer = new Noptin_Background_Mailer();

		// Actions page controller.
		$this->actions_page = new Noptin_Page();

		// Ensure the db is up to date
		$this->maybe_upgrade_db();

		// Register blocks
		$this->register_blocks();

		// Set up localisation.
		$this->load_plugin_textdomain();

		// Load css and js
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		 // css body class
		 add_filter( 'body_class', array( $this, 'body_class' ) );

		/**
		 * Fires after Noptin inits
		 *
		 * @since 1.0.0
		 */
		do_action( 'noptin_init' );
	}

	/**
	 * Include necessary files
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	private function includes() {

		// plugin functions
		require_once $this->plugin_path . 'includes/functions.php';

		// The main admin class
		require_once $this->plugin_path . 'includes/admin/admin.php';

		// Bg handlers
		require_once $this->plugin_path . 'includes/class-noptin-async-request.php';
		require_once $this->plugin_path . 'includes/class-noptin-background-process.php';
		require_once $this->plugin_path . 'includes/class-noptin-new-post-notify.php';
		require_once $this->plugin_path . 'includes/class-noptin-mailer.php';
		require_once $this->plugin_path . 'includes/class-noptin-background-mailer.php';

		require_once $this->plugin_path . 'includes/class-noptin-form.php';
		require_once $this->plugin_path . 'includes/class-noptin-post-types.php';
		require_once $this->plugin_path . 'includes/class-noptin-popups.php';
		require_once $this->plugin_path . 'includes/class-noptin-inpost.php';
		require_once $this->plugin_path . 'includes/class-noptin-sidebar.php';

		require_once $this->plugin_path . 'includes/class-noptin-page.php';
		require_once $this->plugin_path . 'includes/class-noptin-intergrations.php';
		require_once $this->plugin_path . 'includes/admin/class-noptin-vue.php';

		// Ajax handlers
		require_once $this->plugin_path . 'includes/class-noptin-ajax.php';

		// Include the widget class
		require_once $this->plugin_path . 'includes/admin/widget.php';

		/**
		 * Fires after all plugin files and dependancies have been loaded
		 *
		 * @since 1.0.0
		*/
		do_action( 'noptin_files_loaded' );
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
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'noptin' ),
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
		 * @since 1.0.0
		*/
		do_action( 'noptin_before_register_blocks' );

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
	 * @return      self::$instance
	 */
	public function register_widget() {
		register_widget( 'Noptin_Widget' );
		register_widget( 'Noptin_Sidebar' );
	}

	/**
	 * Filters the body classes
	 *
	 * @access      public
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

		require $this->plugin_path . 'includes/class-noptin-install.php';

		$installed_version = absint( get_option( 'noptin_db_version', 0 ) );

		// Upgrade db if installed version of noptin is lower than current version
		if ( $installed_version < $this->db_version ) {
			new Noptin_Install( $installed_version );
			update_option( 'noptin_db_version', $this->db_version );
		}

		// Force create the subscribers table
		if ( ! noptin_subscribers_table_exists() ) {
			new Noptin_Install( false );
		}

	}

	/**
	 * Loads the text domain
	 *
	 * @since 1.1.9
	 * @access public
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'noptin',
			false,
			$this->plugin_path . 'languages/'
		);

		/** Set our unique textdomain string */
		$textdomain = 'newsletter-optin-box';

		/** The 'plugin_locale' filter is also used by default in load_plugin_textdomain() */
		$locale = apply_filters( 'plugin_locale', get_locale(), $textdomain );

		/** Set filter for WordPress languages directory */
		$wp_lang_dir = apply_filters(
			'noptin_wp_lang_dir',
			WP_LANG_DIR . '/newsletter-optin-box/' . $textdomain . '-' . $locale . '.mo'
		);

		/** Translations: First, look in WordPress' "languages" folder = custom & update-secure! */
		load_textdomain( $textdomain, $wp_lang_dir );

		/** Translations: Secondly, look in plugin's "lang" folder = default */
		$plugin_dir = trailingslashit( $this->plugin_path );
		$lang_dir   = apply_filters( 'noptin_lang_dir', $plugin_dir . '/languages/' );
		load_plugin_textdomain( $textdomain, false, $lang_dir );

	}

}

// Kickstart everything
Noptin::instance();
