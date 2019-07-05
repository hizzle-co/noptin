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
        $noptin = noptin();
        $this->admin_path  = plugin_dir_path(__FILE__);
        $this->admin_url   = plugins_url('/', __FILE__);
        $this->assets_url  = $noptin->plugin_url . 'includes/assets/';
        $this->assets_path = $noptin->plugin_path . 'includes/assets/';
        

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

        // Include the rating hooks
        require_once $this->admin_path . 'ratings.php';

        //Editor
        require_once $this->admin_path . 'forms-editor.php';

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

        //Runs when saving a new opt-in form
        add_action('wp_ajax_noptin_save_optin_form', array($this, 'save_optin_form'));

        //Runs when saving a form as a template
        add_action('wp_ajax_noptin_save_optin_form_as_template', array($this, 'save_optin_form_as_template'));

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
        global $pagenow;

        //Only enque on our pages
        if( 'admin.php' != $pagenow || false === stripos( $_GET['page'], 'noptin') ){
            return;
        }

        $version = filemtime( $this->assets_path . 'css/admin.css' );
        wp_enqueue_style('noptin', $this->assets_url . 'css/admin.css', array( 'select2','wp-color-picker' ), $version);
        wp_enqueue_script('select2', $this->assets_url . 'js/select2.js', array( 'jquery' ), '4.0.7');
        wp_enqueue_style('select2', $this->assets_url . 'css/select2.css', array(), '4.0.7');
        wp_enqueue_media();
        wp_enqueue_script('vue', $this->assets_url . 'js/vue.js', array( 'wp-color-picker' ), '2.6.10');
        $version = filemtime( $this->assets_path . 'js/admin.js' );
        wp_register_script('noptin', $this->assets_url . 'js/admin.js', array('select2','vue'), $version, true);

        // Pass variables to our js file, e.g url etc
        $params = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'api_url' => get_home_url( null, 'wp-json/wp/v2/'),
            'nonce'   => wp_create_nonce('noptin_admin_nonce'),
        );

        //Codemirror
        wp_enqueue_code_editor(
			array(
                'type'       => 'css',
                'codemirror' => array(
                    'indentUnit' => 2,
                    'tabSize'    => 2,
                ),
            ),
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

        //Add the optin forms page
        add_submenu_page(
            'noptin',
            esc_html__('Newsletter opt-in forms', 'noptin'),
            esc_html__('Newsletter Forms', 'noptin'),
            'manage_options',
            'noptin-forms',
            array($this, 'render_forms_page')
        );

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

        $logo_url        = $this->assets_url . 'images/logo.png';
        $screenshot_url  = $this->assets_url . 'images/screenshot1.png';
        $screenshot2_url = $this->assets_url . 'images/screenshot2.png';
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
        $logo_url    = $this->assets_url . 'images/logo.png';
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
     * Renders forms page
     *
     * @access      public
     * @since       1.0.4
     * @return      self::$instance
     */
    public function render_forms_page() {

        if (!current_user_can('manage_options')) {
            return;
        }

        /**
         * Runs before displaying the forms page.
         *
         * @param array $this The admin instance
         */
        do_action('noptin_before_admin_forms_page', $this);

        //The optin form currently being edited
        $form = false;

        //Is the user creating a new optin form?
        if( isset( $_GET['action'] ) && 'new' == $_GET['action'] ){
            $form   = noptin_create_optin_form();
        }

        //Is the user trying to edit a new optin form?
        if( isset( $_GET['form_id'] ) ){
            $form   = absint( $_GET['form_id'] );
        }

        //Is the user deleting an optin form?
        if( isset( $_GET['action'] ) && 'delete' == $_GET['action'] ){
            noptin_delete_optin_form( $_GET['delete'] );
        }

        //Is the user duplicating an optin form?
        if( isset( $_GET['action'] ) && 'duplicate' == $_GET['action'] ){
            $form   = noptin_duplicate_optin_form( $_GET['duplicate'] );
        }

        if( $form ){
            $editor = new Noptin_Form_Editor( $form, true );
            $editor->output();
        } else {

            //Fetch forms
            $forms = noptin_get_optin_forms();

            //No forms?
            if(! $forms ){

                //Ask the user to add some
                include $this->admin_path . 'templates/forms-empty.php';

            } else {

                //Show them to the user
                include $this->admin_path . 'templates/forms-list.php';

            }

            
        }
        

        /**
         * Runs after displaying the forms page.
         *
         * @param array $this The admin instance
         */
        do_action('noptin_after_admin_forms_page', $this);
    }

    /**
     * Downloads subscribers
     *
     * @access      public
     * @since       1.0.0
     * @return      self::$instance
     */
    public function save_optin_form() {

        if (!current_user_can('manage_options')) {
            return;
        }

        //Check nonce
        check_ajax_referer( 'noptin_admin_nonce' );

        /**
         * Runs before saving a form
         *
         * @param array $this The admin instance
         */
        do_action('noptin_before_save_form', $this);

        //Prepare the args
        $ID        = trim( $_POST['state']['id'] );
        $state     = $_POST['state'];
        $postarr   = array(
            'post_title'        => $state['optinName'],
            'ID'                => $ID,
            'post_content'      => $_POST['html'],
            'post_status'       => $state['optinStatus'],
        );

        if(! wp_update_post( $postarr ) ) {
            status_header(400);
            die();
        }

        update_post_meta( $ID, '_noptin_state', $_POST['state'] );

        /**
         * Runs after saving a form
         *
         * @param array $this The admin instance
         */
        do_action('noptin_after_save_form', $this);

    exit; //This is important
}

    /**
     * Saves an optin form as a template
     *
     * @access      public
     * @since       1.0.0
     * @return      self::$instance
     */
    public function save_optin_form_as_template() {

        if (!current_user_can('manage_options')) {
            return;
        }

        //Check nonce
        check_ajax_referer( 'noptin_admin_nonce' );

        /**
         * Runs before saving a form as a template
         *
         * @param array $this The admin instance
         */
        do_action('noptin_before_save_form_as_template', $this);

        $templates = get_option( 'noptin_templates' );

        if(! is_array( $templates ) ) {
            $templates = array();
        }

        $fields = 'CSS hideNote note noteColor hideOnNoteClick hideDescription description descriptionColor hideTitle title titleColor noptinButtonBg noptinButtonColor noptinButtonLabel noptinFormBg noptinFormBorderColor noptinFormBorderRound formRadius hideCloseButton closeButtonPos singleLine buttonPosition showNameField requireNameField firstLastName image imagePos';
        $fields = explode(' ', $fields);
        $data   = array();

        foreach( $fields as $field ){
            if( isset( $_POST['state'][$field] ) ) {

                $value = $_POST['state'][$field];

                if( 'false' == $value ) {
                    $data[$field] = false;
                    continue;
                }

                if( 'true' == $value ) {
                    $data[$field] = true;
                    continue;
                }

                $data[$field] = $value;
            }
        }

        $title = trim( $_POST['state']['optinName'] . ' ' . date( DATE_COOKIE ) );
        $templates[ $title ] = $data;

        update_option( 'noptin_templates', $templates );

        /**
         * Runs after saving a form as a template
         *
         * @param array $this The admin instance
         */
        do_action('noptin_after_save_form_as_template', $this);

    exit; //This is important
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
