<?php
/**
 * Noptin
 *
 * Simple WordPress optin form
 *
 * @since             1.0.0
 *
 * Plugin Name:     Noptin - Simple Newsletter Subscription Forms
 * Plugin URI:      https://wordpress.org/plugins/noptin
 * Description:     Easily add a newsletter optin box in any post, page or custom post type
 * Author:          Picocodes
 * Author URI:      https://github.com/picocodes
 * Version:         1.0.5
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

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
    die;
}

    /**
     * Plugin main class
     *
     * @since       1.0.0
     */

    class Noptin{

    /**
     * @var       Plugin version
     * @since       1.0.0
     */
    public $version = '1.0.5';

    /**
     * @var       Plugin db version
     * @since       1.0.0
     */
    public $db_version = 1;

    /**
     * @access      private
     * @var        obj $instance The one true noptin
     * @since       1.0.0
     */
    private static $instance = null;

    /**
	 * Local path to this plugins root directory
     * @access      public
     * @since       1.0.0
     */
    public $plugin_path = null;
			
	/**
	 * Web path to this plugins root directory
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
				
        if ( is_null( self::$instance ) )
    	    self::$instance = new self();									
		
        return self::$instance;
    }

    /**
	 * Class Constructor.
	 */
	public function __construct() {

        //Set global variables
		$this->plugin_path = plugin_dir_path( __FILE__ );
        $this->plugin_url  = plugins_url( '/', __FILE__ );

        // Include core files
        $this->includes();

      	//Init the plugin after WP inits
        add_action( 'init', array( $this, 'init'), 5 );
        
        //Register our new widget
        add_action( 'widgets_init', array($this, 'register_widget'));
				
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
		 *
		 */
        do_action('before_noptin_init', $this);
        
        //Init the admin
        $this->admin  = Noptin_Admin::instance();

        //Ensure the db is up to date
        $this->maybe_upgrade_db();

        //Register post types
        $this->register_post_types();

        //Register blocks
        $this->register_blocks();

        //Load css and js
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts') );
		
		/**
		 * Fires after Noptin inits
		 *
		 * @since 1.0.0
		 *
		 */
        do_action('noptin_init');
    }

    /**
     * Include necessary files
     *
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    private function includes() {
												
		// The main admin class
        require_once $this->plugin_path . 'includes/admin/admin.php';
        
        //Form class 
        require_once $this->plugin_path . 'includes/class-noptin-form.php';
        require_once $this->plugin_path . 'includes/class-popups.php';
        require_once $this->plugin_path . 'includes/class-inpost.php';
        require_once $this->plugin_path . 'includes/class-sidebar.php';

    	//plugin functions
        require_once $this->plugin_path . 'includes/functions.php';
        require_once $this->plugin_path . 'includes/render-functions.php';

    	//Ajax handlers
        require_once $this->plugin_path . 'includes/ajax.php';
        
        // Include the widget class
        require_once $this->plugin_path . 'includes/admin/widget.php';
		
		/**
		 * Fires after all plugin files and dependancies have been loaded
		 *
		 * @since 1.0.0
		 *
		*/
    	do_action('noptin_files_loaded');
    }

    /**
     * Registers front end scripts
     *
     * @access      public
     * @since       1.0.5
     * @return      void
     */
    public function register_scripts() {

		//The JS used to render the block in the editor backend
        wp_register_script(
            'noptin_blocks',
            $this->plugin_url . 'includes/assets/js/blocks.js',
            array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'underscore' ),
            filemtime( $this->plugin_path . 'includes/assets/js/blocks.js' )
        );

		//The css used to style the block in the editor backend
        wp_register_style(
            'noptin_blocks',
            $this->plugin_url . 'includes/assets/css/blocks.css',
            array(),
            filemtime( $this->plugin_path . 'includes/assets/css/blocks.css' )
        );

		//The JS used on the frontend
        wp_register_script(
            'noptin_front',
            $this->plugin_url . 'includes/assets/js/frontend.js',
            array( 'jquery' ),
            filemtime( $this->plugin_path . 'includes/assets/js/frontend.js' ),
            true
        );

        $params = array(
            'ajaxurl'               => admin_url( 'admin-ajax.php' ),
            'noptin_subscribe'		=> wp_create_nonce('noptin-subscribe-nonce'),
        );
        wp_localize_script( 'noptin_front', 'noptin', $params );

		//The css used to style the frontend
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

		//Register the assets...
		$this->register_scripts();

		//... then enqueue them
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
		 *
		*/
        do_action('noptin_before_register_blocks');

        //Register  js scripts and css styles
        $this->register_scripts();
        
        //Register the blocks
        register_block_type( 'noptin/email-optin', array(
            'style'          => 'noptin_front',
            'editor_script'  => 'noptin_blocks',
            'script'         => 'noptin_front',
            'editor_style'   => 'noptin_blocks',
        ) );
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
	 * Runs installation
	 *
	 * @since 1.0.5
	 * @access public
	 *
	 */
	public function maybe_upgrade_db() {

        $installed_version = absint( get_option( 'noptin_db_version', 0 ));

        //Upgrade db if installed version of noptin is lower than current version
        if( $installed_version < $this->db_version ){
            require $this->plugin_path . 'includes/install.php';
            new Noptin_Install( $installed_version );
            update_option( 'noptin_db_version', $this->db_version );
        }

    }
            
            
	/**
	 * Load Localisation files.
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
		register_post_type( 'noptin-form'	, noptin_get_optin_form_post_type_details() );

		/**
		 * Fires after custom post types are registered
		 *
		 * @since 1.0.0
		 *
		*/
		do_action( 'noptin_after_register_post_type' );

	}
}

//Kickstart everything
Noptin::instance();
