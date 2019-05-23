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
 * Version:         1.0.4
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
            public $version = '1.0.4';

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

                //Init the plugin after WP inits
                add_action( 'init', array( $this, 'init'), 5 );       
				
            }

            /**
             * Init the plugin
             *
             * @access      public
             * @since       1.0.5
             * @return      void
             */
            public function init() {
												
                do_action('before_noptin_init');

                //Set global variables
				$this->plugin_path = plugin_dir_path( __FILE__ );
                $this->plugin_url  = plugins_url( '/', __FILE__ );

                //Ensure the db is up to date
                $this->maybe_upgrade_db();

                // Include core files
                $this->includes();

                //Init the admin
                $this->admin  = Noptin_Admin::instance();

                //Register blocks
                $this->register_blocks();

                //Load css and js
                add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts') );
                
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
												
				// Admin page
                require_once $this->plugin_path . 'includes/admin/admin.php';

                //Functions
                require_once $this->plugin_path . 'includes/functions.php';

                //Ajax
                require_once $this->plugin_path . 'includes/ajax.php';
                
                do_action('noptin_after_includes');
            }

            /**
             * Registers front end scripts
             *
             * @access      public
             * @since       1.0.2
             * @return      void
             */
            public function enqueue_scripts() {
                wp_enqueue_script( 'noptin_front' );
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

                do_action('noptin_before_register_blocks');

                wp_register_script(
                    'noptin_admin',
                    $this->plugin_url . 'includes/assets/js/blocks.js',
                    array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'underscore' ),
                    filemtime( $this->plugin_path . 'includes/assets/js/blocks.js' )
                );

                wp_register_style(
                    'noptin_admin',
                    $this->plugin_url . 'includes/assets/css/blocks.css',
                    array(),
                    filemtime( $this->plugin_path . 'includes/assets/css/blocks.css' )
                );

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

                wp_register_style(
                    'noptin_front',
                    $this->plugin_url . 'includes/assets/css/frontend.css',
                    array(),
                    filemtime( $this->plugin_path . 'includes/assets/css/frontend.css' )
                );
            
                register_block_type( 'noptin/email-optin', array(
                    //'style'          => 'noptin_front',
                    'editor_script'  => 'noptin_admin',
                    'script'         => 'noptin_front',
                    //'editor_style'   => 'noptin_admin',
                ) );
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

                //Upgrade db if installed version of Ralas is lower than current version
                if( $installed_version < $this->db_version ){
                    require $this->plugin_path . 'includes/install.php';
                    new Noptin_Install( $installed_version );
                    update_option( 'noptin_db_version', $this->db_version );
                }

	        }
        }

//Kickstart everything
Noptin::instance();
