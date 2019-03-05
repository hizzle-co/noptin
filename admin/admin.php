<?php
/**
 * Admin section
 *
 * Simple WordPress optin form
 *
 * @since             1.0.0
 *
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    die;
}

/**
 * Admin main class
 *
 * @since       1.0.0
 */

class Noptin_Admin {

    /**
     * Local path to this plugins admin directory
     * @access      public
     * @since       1.0.0
     */
    public $admin_path = null;

    /**
     * Web path to this plugins admin directory
     * @access      public
     * @since       1.0.0
     */
    public $admin_url = null;

    /**
     * @access      private
     * @var        obj $instance The one true noptin
     * @since       1.0.0
     */
    private static $instance = null;

    /**
     * Get active instance
     *
     * @access      public
     * @since       1.0.0
     * @return      self::$instance
     */
    public static function instance() {

        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Class Constructor.
     */
    public function __construct() {

        /**
         * Runs right before admin module loads.
         *
         * @param array $this The admin instance
         */
        do_action('noptin_before_admin_load', $this);

        //Set global variables
        $this->admin_path = plugin_dir_path(__FILE__);
        $this->admin_url = plugins_url('/', __FILE__);

        // Include core files
        $this->includes();

        //initialize hooks
        $this->init_hooks();

        /**
         * Runs right after admin module loads.
         *
         * @param array $this The admin instance
         */
        do_action('noptin_admin_loaded', $this);
    }

    /**
     * Include necessary files
     *
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    private function includes() {

        // Include admin files
        //require_once $this->plugin_path . 'admin/functions.php';

        /**
         * Runs right after including admin files.
         *
         * @param array $this The admin instance
         */
        do_action('noptin_after_admin_includes', $this);
    }

    /**
     * Run action and filter hooks
     *
     * @access      private
     * @since       1.0.0
     * @return      void
     */
    private function init_hooks() {

        /**
         * Runs right before registering admin hooks.
         *
         * @param array $this The admin instance
         */
        do_action('noptin_before_admin_init_hooks', $this);

        //Admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqeue_scripts'));

        //Register new menu pages
        add_action('admin_menu', array($this, 'add_menu_page'));

        //Runs when downloading subscribers
        add_action('wp_ajax_noptin_download_subscribers', array($this, 'noptin_download_subscribers'));

        /**
         * Runs right after registering admin hooks.
         *
         * @param array $this The admin instance
         */
        do_action('noptin_after_admin_init_hooks', $this);
    }

    /**
     * Register admin scripts
     *
     * @access      public
     * @since       1.0.0
     * @return      self::$instance
     */
    public function enqeue_scripts() {
        wp_enqueue_style('noptin', $this->admin_url . 'admin.css');
        wp_register_script('noptin', $this->admin_url . 'admin.js', array('jquery'), null, true);

        // Pass variables to our js file, e.g url etc
        $params = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'admin_nonce' => wp_create_nonce('noptin_admin_nonce'),
        );

        // localize and enqueue the script with all of the variable inserted
        wp_localize_script('noptin', 'noptin', $params);
        wp_enqueue_script('noptin');
    }

    /**
     * Register admin page
     *
     * @access      public
     * @since       1.0.0
     * @return      self::$instance
     */
    public function add_menu_page() {

        //The main admin page
        add_menu_page(
            'Noptin',
            'Noptin',
            'manage_options',
            'noptin',
            array($this, 'render_main_page'),
            'dashicons-forms',
            67);

        //Add the subscribers page
        add_submenu_page(
            'noptin',
            esc_html__('Subscribers', 'noptin'),
            esc_html__('Subscribers', 'noptin'),
            'manage_options',
            'noptin-subscribers',
            array($this, 'render_subscribers_page')
        );
    }

    /**
     * Renders main admin page
     *
     * @access      public
     * @since       1.0.0
     * @return      self::$instance
     */
    public function render_main_page() {

        if (!current_user_can('manage_options')) {
            return;
        }

        /**
         * Runs before displaying the main menu page.
         *
         * @param array $this The admin instance
         */
        do_action('noptin_before_admin_main_page', $this);

        $logo_url = $this->admin_url . 'logo.png';
        $screenshot_url = $this->admin_url . 'screenshot1.png';
        include $this->admin_path . 'welcome.php';

        /**
         * Runs after displaying the main menu page.
         *
         * @param array $this The admin instance
         */
        do_action('noptin_after_admin_main_page', $this);
    }

    /**
     * Renders view subscribers page
     *
     * @access      public
     * @since       1.0.0
     * @return      self::$instance
     */
    public function render_subscribers_page() {

        if (!current_user_can('manage_options')) {
            return;
        }

        /**
         * Runs before displaying the suscribers page.
         *
         * @param array $this The admin instance
         */
        do_action('noptin_before_admin_subscribers_page', $this);

        $download_url = add_query_arg(
            array(
                'action' => 'noptin_download_subscribers',
                'admin_nonce' => urlencode(wp_create_nonce('noptin_admin_nonce')),
            ),
            admin_url('admin-ajax.php')
        );
        $logo_url = $this->admin_url . 'logo.png';
        $subscribers = $this->get_subscribers();
        include $this->admin_path . 'subscribers.php';

        /**
         * Runs after displaying the subscribers page.
         *
         * @param array $this The admin instance
         */
        do_action('noptin_after_admin_subscribers_page', $this);
    }

/**
 * Downloads subscribers
 *
 * @access      public
 * @since       1.0.0
 * @return      self::$instance
 */
    public function noptin_download_subscribers() {
        global $wpdb;

        if (!current_user_can('manage_options')) {
            return;
        }

        //Check nonce
        $nonce = $_GET['admin_nonce'];
        if (!wp_verify_nonce($nonce, 'noptin_admin_nonce')) {
            echo 'Reload the page and try again.';
            exit;
        }

        /**
         * Runs before downloading subscribers.
         *
         * @param array $this The admin instance
         */
        do_action('noptin_before_download_subscribers', $this);

        $output  = fopen("php://output", 'w') or die("Unsupported server");
        $table   = $wpdb->prefix . 'noptin_subscribers';
        $results = $wpdb->get_col("SELECT `email` FROM $table");

        header("Content-Type:application/csv");
        header("Content-Disposition:attachment;filename=emails.csv");

        //create the csv
        fputcsv($output, array('Email Address'));
        foreach ($results as $result) {
            fputcsv($output, array($result));
        }
        fclose($output);

        /**
         * Runs after after downloading.
         *
         * @param array $this The admin instance
         */
        do_action('noptin_after_download_subscribers', $this);

        exit; //This is important
    }

    /**
     * Retrieves the subscribers list,, limited to 100
     *
     * @access      public
     * @since       1.0.0
     * @return      self::$instance
     */
    public function get_subscribers() {
        global $wpdb;

        $table = $wpdb->prefix . 'noptin_subscribers';
        $sql = "SELECT *
                    FROM $table
                    ORDER BY time DESC
                    LIMIT 100";
        return $wpdb->get_results($sql);

    }

}
