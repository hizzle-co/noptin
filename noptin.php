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
 * Version:         1.0.2
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

            public $version = '1.0.2';

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
                
                $this->admin       = Noptin_Admin::instance();
				
				// Confirm current db version
				$this->db_version = get_option('noptin_db_version', '0.0.0');				
				if( $this->db_version == '0.0.0' ){
					$this->create_tables();
					update_option('noptin_db_version', $this->version);
					$this->db_version = get_option('noptin_db_version', '1.0.0');
                }
                
				
				//initialize hooks
				$this->init_hooks();
				
				do_action('noptin_loaded');
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
                require_once $this->plugin_path . 'admin/admin.php';
                
                do_action('noptin_after_includes');
            }

            /**
             * Run action and filter hooks
             *
             * @access      private
             * @since       1.0.0
             * @return      void
             */
            private function init_hooks() {
												
                do_action('noptin_before_init_hooks');
                
                add_action( 'init', array( $this, 'register_blocks') );
                add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts') );
                add_action( 'wp_ajax_noptin_new_user', array( $this, 'add_ajax_subscriber') );
		        add_action( 'wp_ajax_nopriv_noptin_new_user', array( $this, 'add_ajax_subscriber') );
            
                do_action('noptin_after_init_hooks');
            }

            /**
             * Handles ajax requests to add new email subscribers
             *
             * @access      public
             * @since       1.0.0
             * @return      void
             */
            public function add_ajax_subscriber() {
                global $wpdb;

                // Check nonce
                $nonce = $_POST['noptin_subscribe'];
                if ( ! wp_verify_nonce( $nonce, 'noptin-subscribe-nonce' )) {
                    echo wp_json_encode( array(
                       'result' => '0',
                       'msg'    => esc_html__('Error: Please reload the page and try again.', 'noptin'),
                    ));
                    exit;
                }

                //Check email address
                $email = sanitize_email($_POST['email']);
                if ( empty($email) || !is_email($email)) {
                    echo wp_json_encode( array(
                       'result' => '0',
                       'msg'    => esc_html__('Error: Please provide a valid email address.', 'noptin'),
                    ));
                    exit;
                }

                do_action('noptin_before_add_ajax_subscriber');

                //Add the user to the database 
                $table = $wpdb->prefix . 'noptin_subscribers';
                $key   = $wpdb->prepare("(%s)", md5($email));
                $email = $wpdb->prepare("(%s)", $email);
                $wpdb->query("INSERT IGNORE INTO $table (email, confirm_key)
                VALUES ($email, $key)");

                do_action('noptin_after_after_ajax_subscriber');

                //We made it
                echo wp_json_encode( array(
                    'result' => '1',
                    'msg'    => esc_html__('Success!', 'noptin'),
                 ));

                 exit;
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
                    $this->plugin_url . 'assets/backend.js',
                    array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'underscore' ),
                    filemtime( $this->plugin_path . 'assets/backend.js' )
                );

                wp_register_style(
                    'noptin_admin',
                    $this->plugin_url . 'assets/backend.css',
                    array(),
                    filemtime( $this->plugin_path . 'assets/backend.css' )
                );

                wp_register_script(
                    'noptin_front',
                    $this->plugin_url . 'assets/frontend.js',
                    array( 'jquery' ),
                    filemtime( $this->plugin_path . 'assets/frontend.js' ),
                    true
                );

                $params = array(
                    'ajaxurl'               => admin_url( 'admin-ajax.php' ),
                    'noptin_subscribe'		=> wp_create_nonce('noptin-subscribe-nonce'),
                );
                wp_localize_script( 'noptin_front', 'noptin', $params );

                wp_register_style(
                    'noptin_front',
                    $this->plugin_url . 'assets/frontend.css',
                    array(),
                    filemtime( $this->plugin_path . 'assets/frontend.css' )
                );
            
                register_block_type( 'noptin/email-optin', array(
                    //'style'          => 'noptin_front',
                    'editor_script'  => 'noptin_admin',
                    'script'         => 'noptin_front',
                    //'editor_style'   => 'noptin_admin',
                ) );
            }

            /**
             * Creates the necessary db tables
             *
             * @access      public
             * @since       1.0.0
             * @return      void
             */
            public function create_tables() {
				global $wpdb;
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				
				$charset_collate_bin_column = '';
				$charset_collate = '';

				if (!empty($wpdb->charset)) {
    				$charset_collate_bin_column = "CHARACTER SET $wpdb->charset";
					$charset_collate = "DEFAULT $charset_collate_bin_column";
				}
				
				if (strpos($wpdb->collate, "_") > 0) {
    				$charset_collate_bin_column .= " COLLATE " . substr($wpdb->collate, 0, strpos($wpdb->collate, '_')) . "_bin";
    				$charset_collate .= " COLLATE $wpdb->collate";
				} else {
					
    				if ($wpdb->collate == '' && $wpdb->charset == "utf8") {
	    				$charset_collate_bin_column .= " COLLATE utf8_bin";
					}
					
				}
				
                //Create the subscribers table
                $table = $wpdb->prefix . 'noptin_subscribers';
				$sql = "CREATE TABLE IF NOT EXISTS $table (id bigint(9) NOT NULL AUTO_INCREMENT, 
					first_name varchar(200),
                    second_name varchar(200),
					email varchar(50) NOT NULL UNIQUE,
                    source varchar(50) DEFAULT 'unknown',
                    confirm_key varchar(50) NOT NULL,
					confirmed INT(2) NOT NULL DEFAULT '0',
					time timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					UNIQUE KEY id (id)) $charset_collate;";
				
                dbDelta($sql);
            }
        }

    function noptin() {
        return Noptin::instance();
    }
    
    noptin();

