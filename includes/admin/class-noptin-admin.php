<?php
/**
 * Admin section
 *
 * Simple WordPress optin form
 *
 * @since             1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
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
	 *
	 * @access      public
	 * @since       1.0.0
	 * @var         string|null
	 */
	public $admin_path = null;

	/**
	 * Web path to this plugins admin directory
	 *
	 * @access      public
	 * @since       1.0.0
	 * @var         string|null
	 */
	public $admin_url = null;

	/**
	 * The main admin class instance.
	 *
	 * @access      protected
	 * @var         Noptin_Admin
	 * @since       1.0.0
	 */
	protected static $_instance = null;

	/**
	 * Background Sync.
	 *
	 * @var Noptin_Background_Sync
	 * @access      public
	 * @since       1.2.3
	 */
	public $bg_sync = null;

	/**
	 * Get active instance
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      self::$instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Initializes the admin instance.
	 */
	public function init() {

		/**
		 * Runs right before Noptin Admin loads.
		 *
		 * @param Noptin_Admin $admin The admin instance
		 * @since 1.0.1
		 */
		do_action( 'noptin_before_admin_load', $this );

		// Set global variables.
		$noptin            = noptin();
		$this->admin_path  = plugin_dir_path( __FILE__ );
		$this->admin_url   = plugins_url( '/', __FILE__ );
		$this->assets_url  = $noptin->plugin_url . 'includes/assets/';
		$this->assets_path = $noptin->plugin_path . 'includes/assets/';

		$this->email_campaigns = new Noptin_Email_Campaigns_Admin();
		$this->bg_sync 		   = new Noptin_Background_Sync();
		$this->filters         = new Noptin_Admin_Filters();

		// initialize hooks.
		$this->init_hooks();

		/**
		 * Runs after Noptin Admin loads.
		 *
		 * @param Noptin_Admin $admin The admin instance
		 * @since 1.0.1
		 */
		do_action( 'noptin_admin_loaded', $this );
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
		do_action( 'noptin_before_admin_init_hooks', $this );

		// Admin scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqeue_scripts' ), 0 );

		// (maybe) do an action.
		add_action( 'admin_init', array( $this, 'maybe_do_action' ) );

		// Register new menu pages.
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_head', array( $this, 'remove_menus' ) );

		// Runs when saving a new opt-in form.
		add_action( 'wp_ajax_noptin_save_optin_form', array( $this, 'save_optin_form' ) );

		// Runs when saving a form as a template.
		add_action( 'wp_ajax_noptin_save_optin_form_as_template', array( $this, 'save_optin_form_as_template' ) );

		// Display notices.
		add_action( 'admin_notices', array( $this, 'show_notices' ) );

		// Admin save subscriber.
		add_action( 'noptin_update_admin_edited_subscriber', array( $this, 'update_edited_subscriber' ) );

		// Display subscribers per page option.
		add_action( 'load-noptin_page_noptin-subscribers', array( $this, 'add_subscribers_page_screen_options' ) );
		add_filter( 'set-screen-option', array( $this, 'save_subscribers_page_screen_options' ), 10, 3 );

		Noptin_Vue::init_hooks();

		/**
		 * Runs right after registering admin hooks.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_after_admin_init_hooks', $this );
	}


	/**
	 * Register admin scripts
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function enqeue_scripts() {
		global $pagenow, $current_screen;

		// Load our CSS styles on all pages.
		$version = filemtime( $this->assets_path . 'css/admin.css' );
		wp_enqueue_style( 'noptin', $this->assets_url . 'css/admin.css', array(), $version );

		// Only enque on our pages.
		$page = '';

		if ( isset( $_GET['page'] ) ) {
			$page = $_GET['page'];
		}

		if ( ! empty( $current_screen->post_type ) ) {
			$page = $current_screen->post_type;
		}

		if ( empty( $page ) || false === stripos( $page, 'noptin' ) ) {
			return;
		}

		// Remove AUI scripts as they break our pages.
		if ( class_exists( 'AyeCode_UI_Settings' ) && is_callable( 'AyeCode_UI_Settings::instance' ) ) {
			$aui = AyeCode_UI_Settings::instance();
			remove_action( 'admin_enqueue_scripts', array( $aui, 'enqueue_scripts' ), 1 );
			remove_action( 'admin_enqueue_scripts', array( $aui, 'enqueue_style' ), 1 );
		}

		// Sweetalert https://sweetalert2.github.io/.
		wp_enqueue_script( 'promise-polyfill', $this->assets_url . 'vendor/sweetalert/promise-polyfill.min.js', array(), '8.1.3' );
		wp_enqueue_script( 'sweetalert2', $this->assets_url . 'vendor/sweetalert/sweetalert2.all.min.js', array( 'promise-polyfill' ), '9.6.0', true );

		// Tooltips https://iamceege.github.io/tooltipster/.
		wp_enqueue_script( 'tooltipster', $this->assets_url . 'vendor/tooltipster/tooltipster.bundle.min.js', array( 'jquery' ), '4.2.7', true );
		wp_enqueue_style( 'tooltipster', $this->assets_url . 'vendor/tooltipster/tooltipster.bundle.min.css', array(), '4.2.7' );

		// Select 2 https://select2.org/.
		wp_enqueue_script( 'select2', $this->assets_url . 'vendor/select2/select2.full.min.js', array( 'jquery' ), '4.0.12', true );
		wp_enqueue_style( 'select2', $this->assets_url . 'vendor/select2/select2.min.css', array(), '4.0.12' );

		// Vue js.
		wp_register_script( 'vue', $this->assets_url . 'vendor/vue/vue.min.js', array(), '2.6.11', true );

		// Enque media for image uploads.
		wp_enqueue_media();

		// Codemirror for editor css.
		wp_enqueue_code_editor(
			array(
				'type'       => 'css',
				'codemirror' => array(
					'indentUnit'     => 1,
					'tabSize'        => 4,
					'indentWithTabs' => true,
					'lineNumbers'    => false,
				),
			)
		);

		// Custom admin scripts.
		$version = filemtime( $this->assets_path . 'js/dist/admin.js' );
		wp_register_script( 'noptin', $this->assets_url . 'js/dist/admin.js', array( 'tooltipster' ), $version, true );

		// Pass variables to our js file, e.g url etc.
		$current_user = wp_get_current_user();
		$params       = array(
			'ajaxurl'        => admin_url( 'admin-ajax.php' ),
			'api_url'        => get_home_url( null, 'wp-json/wp/v2/' ),
			'nonce'          => wp_create_nonce( 'noptin_admin_nonce' ),
			'icon'           => $this->assets_url . 'images/checkmark.png',
			'admin_email'    => sanitize_email( $current_user->user_email ),
			'donwload_forms' => add_query_arg(
				array(
					'action'      => 'noptin_download_forms',
					'admin_nonce' => wp_create_nonce( 'noptin_admin_nonce' ),
				),
				admin_url( 'admin-ajax.php' )
			),
		);

		// localize and enqueue the script with all of the variable inserted.
		wp_localize_script( 'noptin', 'noptin_params', $params );

		wp_enqueue_script( 'noptin' );

		// Settings page.
		if ( 'noptin-settings' === $page ) {
			$version = filemtime( $this->assets_path . 'js/dist/settings.js' );
			wp_enqueue_script( 'noptin-settings', $this->assets_url . 'js/dist/settings.js', array( 'vue', 'select2', 'sweetalert2', 'noptin' ), $version, true );
			wp_localize_script( 'noptin-settings', 'noptinSettings', Noptin_Settings::get_state() );
		}

		// Optin forms editor.
		if ( 'noptin-form' === $page ) {
			$version = filemtime( $this->assets_path . 'js/dist/modules.css' );
			wp_enqueue_style( 'noptin-modules', $this->assets_url . 'js/dist/modules.css', array(), $version );
			wp_enqueue_script( 'noptin-modules', $this->assets_url . 'js/dist/modules.js', array(), $version, true );
			$version = filemtime( $this->assets_path . 'js/dist/optin-editor.js' );
			wp_enqueue_script( 'noptin-optin-editor', $this->assets_url . 'js/dist/optin-editor.js', array( 'vue', 'select2', 'sweetalert2', 'noptin-modules' ), $version, true );
		}

		// Email campaigns page.
		if ( 'noptin-email-campaigns' === $page ) {
			$version = filemtime( $this->assets_path . 'js/dist/newsletter-editor.js' );
			wp_enqueue_script( 'flatpickr', $this->assets_url . 'vendor/flatpickr/flatpickr.js', array(), '4.6.3', true );
			wp_enqueue_style( 'flatpickr', $this->assets_url . 'vendor/flatpickr/flatpickr.min.css', array(), '4.6.3' );
			wp_enqueue_script( 'noptin-email-campaigns', $this->assets_url . 'js/dist/newsletter-editor.js', array( 'select2', 'sweetalert2', 'postbox' ), $version, true );
		}

		// Subscribers page.
		if ( 'noptin-subscribers' === $page ) {
			$version = filemtime( $this->assets_path . 'js/dist/subscribers.js' );
			wp_enqueue_script( 'noptin-subscribers', $this->assets_url . 'js/dist/subscribers.js', array( 'sweetalert2', 'postbox' ), $version, true );

			$params       = array(
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'noptin_subscribers' ),
				'add'           => __( 'Add Subscriber', 'newsletter-optin-box' ),
				'save'          => __( 'Save', 'newsletter-optin-box' ),
				'missing_email' => __( 'Enter an email address', 'newsletter-optin-box' ),
				'add_success'   => __( 'New subscriber added', 'newsletter-optin-box' ),
				'reloading'     => __( 'Reloading the page', 'newsletter-optin-box' ),
				'export'        => __( 'Export Subscribers', 'newsletter-optin-box' ),
				'exportbtn'     => __( 'Export', 'newsletter-optin-box' ),
				'file'          => __( 'Select file type', 'newsletter-optin-box' ),
				'download'      => __( 'Download', 'newsletter-optin-box' ),
				'done'          => __( 'Done!', 'newsletter-optin-box' ),
				'close'         => __( 'Close', 'newsletter-optin-box' ),
				'imported'      => __( 'Imported', 'newsletter-optin-box' ),
				'skipped'       => __( 'Skipped', 'newsletter-optin-box' ),
				'import_fail'   => __( 'Visit Noptin > Tools > Debug Log to see why your subscribers were not imported', 'newsletter-optin-box' ),
				'import_title'  => __( 'Select your CSV file', 'newsletter-optin-box' ),
				'import_footer' => __( 'Import subscribers from any system into Noptin', 'newsletter-optin-box' ),
				'import_label'  => __( 'select your import file', 'newsletter-optin-box' ),
				'import'        => __( 'Import', 'newsletter-optin-box' ),
				'delete_subscriber' => __( 'Delete subscriber', 'newsletter-optin-box' ),
				'delete_footer'     => __( 'This will delete the subscriber and all associated data', 'newsletter-optin-box' ),
				'delete'            => __( 'Delete', 'newsletter-optin-box' ),
				'double_optin'      => __( 'Send a new double opt-in confirmation email to', 'newsletter-optin-box' ),
				'send'              => __( 'Send', 'newsletter-optin-box' ),
				'success'           => __( 'Success', 'newsletter-optin-box' ),
				'error'             => __( 'Error!', 'newsletter-optin-box' ),
				'troubleshoot'      => __( 'How to troubleshoot this error.', 'newsletter-optin-box' ),
				'connect_error'     => __( 'Unable to connect', 'newsletter-optin-box' ),
				'connect_info'      => __( 'This might be a problem with your server or your internet connection', 'newsletter-optin-box' ),
				'delete_all'        => __( 'Are you sure you want to delete all subscribers?', 'newsletter-optin-box' ),
				'no_revert'         => __( "You won't be able to revert this!", 'newsletter-optin-box' ),
				'deleted'           => __( 'Deleted Subscribers', 'newsletter-optin-box' ),
				'no_delete'         => __( 'Could not delete subscribers', 'newsletter-optin-box' ),
				'cancel'            => __( 'Cancel', 'newsletter-optin-box' ),
			);

			// localize and enqueue the script with all of the variable inserted.
			wp_localize_script( 'noptin-subscribers', 'noptinSubscribers', $params );

		}

		// Automation's creation page.
		if ( 'noptin-automation-rules' === $page ) {
			$version = filemtime( $this->assets_path . 'js/dist/automation-rules.js' );
			wp_enqueue_script( 'noptin-automation-rules', $this->assets_url . 'js/dist/automation-rules.js', array( 'vue', 'ddslick' ), $version, true );
			wp_enqueue_script( 'ddslick', $this->assets_url . 'vendor/ddslick/ddslick.js', array( 'vue' ), false, true );

			$params = array(
				'ajaxurl'          => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'noptin_automation_rules' ),
				'trigger_settings' => new stdClass(),
				'action_settings'  => new stdClass(),
				'rule_id'          => 0,
				'error'            => __( 'Unable to save your changes.', 'newsletter-optin-box' ),
				'saved'            => __( 'Your automation rule has been saved.', 'newsletter-optin-box' ),
			);

			if ( ! empty( $_GET['edit'] ) && is_numeric( $_GET['edit'] ) ) {
				$rule = new Noptin_Automation_Rule( $_GET['edit'] );

				$params[ 'rule_id' ]          = $rule->id;
				$params[ 'trigger_settings' ] = (object) $rule->trigger_settings;
				$params[ 'action_settings' ]  = (object) $rule->action_settings;

			}

			// localize and enqueue the script with all of the variable inserted.
			wp_localize_script( 'noptin-automation-rules', 'noptinRules', $params );

		}

	}

	/**
	 * Register admin page
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function add_menu_page() {

		// The main admin page.
		add_menu_page(
			'Noptin',
			'Noptin',
			get_noptin_capability(),
			'noptin',
			array( $this, 'render_main_page' ),
			'dashicons-forms',
			23
		);

		// Add the newsletter page.
		add_submenu_page(
			'noptin',
			esc_html__( 'Email Forms', 'newsletter-optin-box' ),
			esc_html__( 'Email Forms', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'edit.php?post_type=noptin-form'
		);

		// Add the email campaigns page.
		add_submenu_page(
			'noptin',
			esc_html__( 'Email Campaigns', 'newsletter-optin-box' ),
			esc_html__( 'Email Campaigns', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-email-campaigns',
			array( $this, 'render_email_campaigns_page' )
		);

		// Add the subscribers page.
		$subscribers_page_title = apply_filters( 'noptin_admin_subscribers_page_title', __( 'Email Subscribers', 'newsletter-optin-box' ) );
		add_submenu_page(
			'noptin',
			$subscribers_page_title,
			esc_html__( 'Email Subscribers', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-subscribers',
			array( $this, 'render_subscribers_page' )
		);

		do_action( 'noptin_after_register_menus', $this );

		// Automation Rules.
		$automations_page_title = apply_filters( 'noptin_admin_automation_rules_page_title', __( 'Automation Rules', 'newsletter-optin-box' ) );
		add_submenu_page(
			'noptin',
			$automations_page_title,
			esc_html__( 'Automation Rules', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-automation-rules',
			array( $this, 'render_automation_rules_page' )
		);

		// Settings.
		add_submenu_page(
			'noptin',
			esc_html__( 'Settings', 'newsletter-optin-box' ),
			esc_html__( 'Settings', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-settings',
			array( $this, 'render_settings_page' )
		);

		// Tools.
		$tools_page_title = apply_filters( 'noptin_admin_tools_page_title', __( 'Noptin Tools', 'newsletter-optin-box' ) );
		add_submenu_page(
			'noptin',
			esc_html( $tools_page_title ),
			esc_html__( 'Tools', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-tools',
			array( $this, 'render_tools_page' )
		);

		// Link to documentation.
		add_submenu_page(
			'noptin',
			esc_html__( 'Documentation', 'newsletter-optin-box' ),
			esc_html__( 'Documentation', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-docs',
			array( $this, 'render_add_new_page' )
		);

		// Welcome page.
		add_dashboard_page(
			esc_html__( 'Noptin Welcome', 'newsletter-optin-box' ),
			esc_html__( 'Noptin Welcome', 'newsletter-optin-box' ),
			'read',
			'noptin-welcome',
			array( $this, 'welcome_screen_content' )
		);
	}

	/**
	 * Renders main admin page
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function remove_menus() {
		remove_submenu_page( 'index.php', 'noptin-welcome' );
	}

	/**
	 * Display the welcome page
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function welcome_screen_content() {
		include $this->admin_path . 'welcome-screen.php';
	}

	/**
	 * Renders main admin page
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function render_main_page() {

		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		/**
		 * Runs before displaying the main menu page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_before_admin_main_page', $this );

		$today_date              = date( 'Y-m-d', current_time( 'timestamp' ) );
		$forms_url               = esc_url( get_noptin_forms_overview_url() );
		$new_form_url            = esc_url( get_noptin_new_form_url() );
		$subscribers_url         = esc_url( get_noptin_subscribers_overview_url() );
		$subscribers_total       = get_noptin_subscribers_count();
		$subscribers_today_total = get_noptin_subscribers_count( "`date_created`='$today_date'" );
		$this_week               = date( 'Y-m-d', strtotime( 'last week sunday' ) );
		$subscribers_week_total  = get_noptin_subscribers_count( "`date_created`>'$this_week'" );

		$popups   = noptin_count_optin_forms( 'popup' );
		$inpost   = noptin_count_optin_forms( 'inpost' );
		$widget   = noptin_count_optin_forms( 'sidebar' );
		$slide_in = noptin_count_optin_forms( 'slide_in' );

		include $this->admin_path . 'welcome.php';

		/**
		 * Runs after displaying the main menu page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_after_admin_main_page', $this );
	}

	/**
	 * Renders view subscribers page
	 *
	 * @access      public
	 * @since       1.1.2
	 * @return      void
	 */
	public function render_email_campaigns_page() {

		// Only admins can access this page.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		/**
		 * Runs before displaying the email campaigns page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_before_email_campaigns_page', $this );

		$this->email_campaigns->render_campaigns_page();

		/**
		 * Runs after displaying the email campaigns page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_after_email_campaigns_page', $this );
	}

	/**
	 * Renders the automation rules page
	 *
	 * @access      public
	 * @since       1.2.8
	 * @return      void
	 */
	public function render_automation_rules_page() {

		// Only admins can access this page.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		/**
		 * Runs before displaying the automation rules page.
		 *
		 */
		do_action( 'noptin_before_automation_rules_page' );

		// Render the automation creation page.
		if ( ! empty( $_GET['create'] ) ) {
			$this->render_create_automation_rule_page();

		// Render the automation edit page.
		} else if( ! empty( $_GET['edit'] ) && is_numeric( $_GET['edit'] ) ) {
			$this->render_edit_automation_rule_page( $_GET['edit'] );

		// Render the automation overview page.
		} else {
			$this->render_automation_rules_overview_page();
		}

		/**
		 * Runs after displaying the automation rules page.
		 *
		 */
		do_action( 'noptin_after_automation_rules_page' );

	}

	/**
	 * Renders the automation rules overview page
	 *
	 * @access      public
	 * @since       1.2.8
	 * @return      void
	 */
	public function render_automation_rules_overview_page() {

		// Only admins can access this page.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		/**
		 * Runs before displaying the automation rules overview page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_before_automation_rules_overview_page', $this );

		$table = new Noptin_Automation_Rules_Table();
		$table->prepare_items();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo get_admin_page_title(); ?> <a href="<?php echo esc_url( add_query_arg( 'create', '1' ) ); ?>" class="page-title-action noptin-add-automation-rule"><?php _e( 'Add New', 'newsletter-optin-box' ); ?></a></h1>
			<?php $this->show_notices(); ?>
			<form id="noptin-automation-rules-table" method="POST">
				<?php $table->display(); ?>
			</form>
			<p class="description"><a href="https://noptin.com/guide/automation-rules" target="_blank"><?php _e( 'Learn more about automation rules', 'newsletter-optin-box' ); ?></a></p>
		</div>
		<?php

		/**
		 * Runs after displaying the automation rules overview page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_after_automation_rules_overview_page', $this );
	}

	/**
	 * Renders the automation rule creation page.
	 *
	 * @access      public
	 * @since       1.2.8
	 * @return      void
	 */
	public function render_create_automation_rule_page() {

		// Only admins can access this page.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		/**
		 * Runs before displaying the automation creation page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_before_automation_rules_create_page', $this );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Create an Automation Rule', 'newsletter-optin-box' ); ?></h1>
			<?php get_noptin_template( 'automation-rules/create.php' ); ?>
			<p class="description"><a href="https://noptin.com/guide/automation-rules" target="_blank"><?php _e( 'Learn more about automation rules', 'newsletter-optin-box' ); ?></a></p>
		</div>
		<?php

		/**
		 * Runs after displaying the automation creation page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_after_automation_rules_create_page', $this );
	}

	/**
	 * Renders the automation rule creation page.
	 *
	 * @access      public
	 * @since       1.2.8
	 * @return      void
	 */
	public function render_edit_automation_rule_page( $rule_id ) {

		// Only admins can access this page.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		/**
		 * Runs before displaying the automation edit page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_before_automation_rule_edit_page', $this );

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php _e( 'Edit Automation Rule', 'newsletter-optin-box' ); ?></h1>
			<?php get_noptin_template( 'automation-rules/edit.php', compact( 'rule_id' ) ); ?>
			<p class="description"><a href="https://noptin.com/guide/automation-rules" target="_blank"><?php _e( 'Learn more about automation rules', 'newsletter-optin-box' ); ?></a></p>
		</div>
		<?php

		/**
		 * Runs after displaying the automation edit page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_after_automation_rule_edit_page', $this );
	}

	/**
	 * Renders view subscribers page
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function render_subscribers_page() {

		// Only admins can access this page.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		/**
		 * Runs before displaying the suscribers page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_before_admin_subscribers_page', $this );

		// Are we viewing a single subscriber or all subscribers?
		if ( ! empty( $_GET['subscriber'] ) ) {
			$this->render_single_subscriber_page( $_GET['subscriber'] );
		} else if ( ! empty( $_GET['export'] ) ) {
			$this->render_export_subscribers_page();
		} else {
			$this->render_subscribers_overview_page();
		}

		/**
		 * Runs after displaying the subscribers page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_after_admin_subscribers_page', $this );
	}

	/**
	 * Renders subscribers overview page
	 *
	 * @access      public
	 * @since       1.1.1
	 * @return      void
	 */
	public function render_subscribers_overview_page() {

		/**
		 * Runs before displaying the suscribers overview page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_before_subscribers_overview_page', $this );

		// Do actions.
		if ( ! empty( $_POST['noptin_nonce'] ) && wp_verify_nonce( $_POST['noptin_nonce'], 'noptin' ) ) {

			// Delete.
			if ( ! empty( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
				if ( ! empty( $_POST['email'] ) && is_array( $_POST['email'] ) ) {

					foreach ( $_POST['email'] as $email ) {
						delete_noptin_subscriber( $email );
					}

				}
			}
		}

		$download_url = add_query_arg( 'export', 'true', admin_url( 'admin.php?page=noptin-subscribers' ) );
		$table        = new Noptin_Subscribers_Table();
		$table->prepare_items();


		$data = '';
		$data_array = apply_filters( 'noptin_subscribers_page_extra_ajax_data', $_GET );
		foreach( $data_array as $key => $value ) {

			if ( is_scalar( $value ) ) {
				$value = esc_attr( $value );
				$key   = esc_attr( $key );
				$data .= " data-$key='$value'";
			}

		}

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo get_admin_page_title(); ?> <a href="#" class="page-title-action noptin-add-subscriber"><?php _e( 'Add New', 'newsletter-optin-box' ); ?></a> <a href="#" class="page-title-action noptin-import-subscribers"><?php _e( 'Import', 'newsletter-optin-box' ); ?></a> <a href="<?php echo $download_url; ?>" class="page-title-action"><?php _e( 'Export', 'newsletter-optin-box' ); ?></a> <a href="#" class="button-secondary noptin-danger-button noptin-delete-subscribers"><?php _e( 'Delete All Subscribers', 'newsletter-optin-box' ); ?></a> </h1>
			<?php $this->show_notices(); ?>
			<form id="noptin-subscribers-table" method="POST" action="<?php echo $table->base_url; ?>">
				<?php $table->search_box( __( 'Search Subscribers', 'newsletter-optin-box' ), 'noptin_search_subscribers'); ?>
				<?php $table->display(); ?>
			</form>
			<div id='noptin-subscribers-page-data' <?php echo $data; ?>></div>
		</div>
		<?php

		/**
		 * Runs after displaying the subscribers overview page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_after_subscribers_overview_page', $this );
	}

	/**
	 * Displays a single subscriber.
	 *
	 * @param       int $subscriber The subscriber to display.
	 * @access      public
	 * @since       1.1.1
	 * @return      void
	 */
	public function render_single_subscriber_page( $subscriber = 0 ) {

		if ( empty( $_GET['return'] ) ) {
			$_GET['return'] = admin_url( 'admin.php?page=noptin-subscribers' );
		}

		$data = '';
		$data_array = apply_filters( 'noptin_subscribers_page_extra_ajax_data', $_GET );
		foreach( $data_array as $key => $value ) {

			if ( is_scalar( $value ) ) {
				$value = esc_attr( $value );
				$key   = esc_attr( $key );
				$data .= " data-$key='$value'";
			}

		}

		echo "<div id='noptin-subscribers-page-data' $data></div>";
		/**
		 * Runs before displaying the suscribers page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_before_admin_single_subscriber_page', $subscriber, $this );

		$subscriber = new Noptin_Subscriber( $subscriber );

		if ( $subscriber->exists() ) {

			do_action( 'add_meta_boxes_noptin_subscribers', $subscriber );
			do_action( 'add_meta_boxes', 'noptin_subscribers', $subscriber );
			get_noptin_template( 'admin-single-subscriber/single-subscriber.php', array( 'subscriber' => $subscriber ) );

		} else {
			get_noptin_template( 'admin-single-subscriber/404.php', array() );
		}


		/**
		 * Runs after displaying the subscribers page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_after_admin_single_subscriber_page', $subscriber, $this );
	}

	/**
	 * Displays the export subscribers.
	 *
	 * @access      public
	 * @since       1.3.1
	 * @return      void
	 */
	public function render_export_subscribers_page() {

		/**
		 * Runs before displaying the suscribers export page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_before_subscribers_export_page', $this );

		get_noptin_template( 'export-subscribers.php' );

		/**
		 * Runs after displaying the subscribers export page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_after_subscribers_export_page', $this );
	}

	/**
	 * Renders the settings page
	 *
	 * @access      public
	 * @since       1.0.6
	 * @return      void
	 */
	public function render_settings_page() {
		Noptin_Settings::output();
	}

	/**
	 * Renders the tools page
	 *
	 * @access      public
	 * @since       1.2.3
	 * @return      void
	 */
	public function render_tools_page() {
		$this->tools = new Noptin_Tools();
		Noptin_Tools::output();
	}

	/**
	 * Downloads subscribers
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function save_optin_form() {

		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		// Check nonce.
		check_ajax_referer( 'noptin_admin_nonce' );

		/**
		 * Runs before saving a form
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_before_save_form', $this );

		// Prepare the args.
		$id     = trim( $_POST['state']['id'] );
		$state  = $_POST['state'];
		$status = 'draft';

		if ( true === $state['optinStatus'] || 'true' === $state['optinStatus'] ) {
			$status = 'publish';
		}

		$postarr = array(
			'post_title'   => $state['optinName'],
			'ID'           => $id,
			'post_content' => '',
			'post_status'  => $status,
		);

		$post = wp_update_post( $postarr, true );
		if ( is_wp_error( $post ) ) {
			status_header( 400 );
			die( $post->get_error_message() );
		}

		if ( empty( $_POST['state']['showPostTypes'] ) ) {
			$_POST['state']['showPostTypes'] = array();
		}

		update_post_meta( $id, '_noptin_state', $_POST['state'] );
		update_post_meta( $id, '_noptin_optin_type', $_POST['state']['optinType'] );

		// Ensure impressions and subscriptions are set.
		// to prevent the form from being hidden when the user sorts by those fields.
		$sub_count  = get_post_meta( $id, '_noptin_subscribers_count', true );
		$form_views = get_post_meta( $id, '_noptin_form_views', true );

		if ( empty( $sub_count ) ) {
			update_post_meta( $id, '_noptin_subscribers_count', 0 );
		}

		if ( empty( $form_views ) ) {
			update_post_meta( $id, '_noptin_form_views', 0 );
		}

		/**
		 * Runs after saving a form
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_after_save_form', $this );

		exit; // This is important.
	}

	/**
	 * Saves an optin form as a template
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function save_optin_form_as_template() {

		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		// Check nonce.
		check_ajax_referer( 'noptin_admin_nonce' );

		/**
		 * Runs before saving a form as a template
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_before_save_form_as_template', $this );

		$templates = get_option( 'noptin_templates' );

		if ( ! is_array( $templates ) ) {
			$templates = array();
		}

		$fields = noptin_get_form_design_props();
		$data   = array();

		foreach ( $fields as $field ) {

			if ( 'optinType' === $field ) {
				continue;
			}

			if ( isset( $_POST['state'][ $field ] ) ) {

				$value = stripslashes_deep( $_POST['state'][ $field ] );

				if ( 'false' === $value ) {
					$data[ $field ] = false;
					continue;
				}

				if ( 'true' === $value ) {
					$data[ $field ] = true;
					continue;
				}

				$data[ $field ] = $value;
			}
		}

		$title             = sanitize_text_field( $_POST['state']['optinName'] );
		$key               = wp_generate_password( '4', false ) . time();
		$templates[ $key ] = array(
			'title' => $title,
			'data'  => $data,
		);

		update_option( 'noptin_templates', $templates );

		/**
		 * Runs after saving a form as a template
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_after_save_form_as_template', $this );

		exit; // This is important.
	}




	/**
	 * Retrieves the subscribers list,, limited to 100
	 *
	 * @param       int    $page The page to retrieve.
	 * @param       string $meta_key Filter subscribers by a meta key.
	 * @param       string $meta_value Filter subscribers by a meta value.
	 * @access      public
	 * @since       1.0.0
	 * @return      array|null
	 */
	public function get_subscribers( $page = 1, $meta_key = '_subscriber_via', $meta_value = false ) {
		global $wpdb;

		$table      = get_noptin_subscribers_table_name();
		$meta_table = get_noptin_subscribers_meta_table_name();
		$limit      = 10;
		$offset     = absint( $page - 1 ) * $limit;
		$extra_sql  = '';

		if ( false !== $meta_value ) {
			$extra_sql = "INNER JOIN $meta_table ON ( $table.id = $meta_table.noptin_subscriber_id ) WHERE ( $meta_table.meta_key = '%s' AND $meta_table.meta_value = '%s' )";
			$extra_sql = $wpdb->prepare( $extra_sql, $meta_key, $meta_value );
		}

		$sql = "SELECT *
                    FROM $table
					$extra_sql
                    ORDER BY date_created DESC, id DESC
					LIMIT $offset, $limit";

		return $wpdb->get_results( $sql );

	}

	/**
	 * Does an action
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      void
	 */
	public function maybe_do_action() {

		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		if ( ! empty( $_REQUEST['noptin_admin_action'] ) ) {
			do_action( trim( $_REQUEST['noptin_admin_action'] ), $this );
		}

		// Redirect to welcome page.
		if ( ! get_option( '_noptin_has_welcomed', false ) && ! wp_doing_ajax() ) {

			// Ensure were not activating from network, or bulk.
			if ( ! is_network_admin() && ! isset( $_GET['activate-multi'] ) ) {

				// Prevent further redirects.
				update_option( '_noptin_has_welcomed', '1' );

				// Redirect to the welcome page.
				wp_safe_redirect( add_query_arg( array( 'page' => 'noptin-welcome' ), admin_url( 'index.php' ) ) );
				exit;

			}
		}

		// Subscriber actions.
		if ( isset( $_GET['page'] ) && 'noptin-subscribers' === $_GET['page'] ) {

			// Maybe delete an email subscriber.
			if ( ! empty( $_GET['delete-subscriber'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'noptin-subscriber' ) ) {
				delete_noptin_subscriber( $_GET['delete-subscriber'] );
				$this->show_success( __( 'Subscriber successfully deleted', 'newsletter-optin-box' ) );
			}

		}

		// Campaign actions.
		if ( isset( $_GET['page'] ) && 'noptin-email-campaigns' === $_GET['page'] ) {

			// Duplicate campaign.
			if ( ! empty( $_GET['duplicate_campaign'] ) ) {

				$campaign = get_post( $_GET['duplicate_campaign'] );

				if ( ! empty( $campaign ) ) {

					$post = array(
						'post_status'   => 'draft',
						'post_type'     => 'noptin-campaign',
						'post_date'     => current_time( 'mysql' ),
						'post_date_gmt' => current_time( 'mysql', true ),
						'edit_date'     => true,
						'post_title'    => trim( $campaign->post_title ),
						'post_content'  => $campaign->post_content,
						'meta_input'    => array(
							'campaign_type'           => 'newsletter',
							'preview_text'            => get_post_meta( $campaign->ID, 'preview_text', 'true' ),
						),
					);

					$new_campaign = wp_insert_post( $post, true );

					if ( is_wp_error( $new_campaign ) ) {
						$this->show_error( $new_campaign->get_error_message() );
					} else {
						wp_redirect( get_noptin_newsletter_campaign_url( $new_campaign ) );
						exit;
					}

				}
				
			}

			// Delete multiple campaigns.
			if ( ! empty( $_GET['action'] ) && 'delete' === $_GET['action'] && wp_verify_nonce( $_GET['_wpnonce'], 'bulk-ids' ) ) {
				$ids = array();

				if ( isset( $_REQUEST['id'] ) && is_array( $_REQUEST['id'] ) ) {
					$ids = array_map( 'intval', $_REQUEST['id'] );
				}

				foreach ( $ids as $id ) {
					wp_delete_post( $id, true );
				}

				$this->show_success( __( 'The selected campaigns have been deleted.', 'newsletter-optin-box' ) );
			}
		}

		// Docs page.
		if ( isset( $_GET['page'] ) && 'noptin-docs' === trim( $_GET['page'] ) ) {
			$url = sprintf( 'https://noptin.com/guide/introduction/?utm_medium=plugin-dashboard&utm_campaign=documentation-link&utm_source=%s', urlencode( get_home_url() ) );
			wp_redirect( $url, 301 );
			exit;
		}

		// Tools.
		if ( isset( $_GET['page'] ) && 'noptin-tools' === $_GET['page'] && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'noptin_tool' ) ) {

			// Sync Users.
			if ( isset( $_GET['tool'] ) && 'sync_users' === trim( $_GET['tool'] ) ) {

				if ( get_option( 'noptin_users_bg_sync' ) ) {
					$this->show_error( __( 'Your WordPress users are already being added to the newsletter.', 'newsletter-optin-box' ) );
				} else {
					add_option( 'noptin_users_bg_sync', 1 );
					$this->bg_sync->push_to_queue( 'wp_user' );
					$this->bg_sync->save()->dispatch();
					$this->show_info( __( 'Your WordPress users are now syncing in the background.', 'newsletter-optin-box' ) );
				}
			}

			// Sync Subscribers.
			if ( isset( $_GET['tool'] ) && 'sync_subscribers' === trim( $_GET['tool'] ) ) {

				if ( get_option( 'noptin_subscribers_syncing' ) ) {
					$this->show_error( __( 'Your WordPress subscribers are already syncing.', 'newsletter-optin-box' ) );
				} else {
					add_option( 'noptin_subscribers_syncing', 1 );
					$this->bg_sync->push_to_queue( 'subscriber' );
					$this->bg_sync->save()->dispatch();
					$this->show_info( __( 'Your subscribers are now syncing in the background.', 'newsletter-optin-box' ) );
				}
			}

		}

	}

	/**
	 * Displays a success notice
	 *
	 * @param       string $msg The message to qeue.
	 * @access      public
	 * @since       1.1.2
	 */
	function update_edited_subscriber() {

		if ( ! current_user_can( get_noptin_capability() ) || empty( $_POST['noptin-admin-update-subscriber-nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['noptin-admin-update-subscriber-nonce'], 'noptin-admin-update-subscriber' ) ) {
			return;
		}

		$post = wp_unslash( $_POST );
		$data = array(
			'email'      => sanitize_text_field( $post['email'] ),
			'first_name' => sanitize_text_field( $post['first_name'] ),
			'last_name'  => sanitize_text_field( $post['last_name'] ),
			'active'     => intval( $post['status'] ),
			'confirmed'  => intval( $post['confirmed'] ),
		);

		$meta       = empty( $post['noptin_custom_field'] ) ? array() : $post['noptin_custom_field'];
		$data       = wp_parse_args( $meta, $data );
		$subscriber = (int) $post['subscriber_id'];

		if ( ! empty( $subscriber ) ) {

			// Subscriber activation/deactivation.
			if ( ! empty( $data['active'] ) ) {
				deactivate_noptin_subscriber( $subscriber );
			}

			// Subscriber email confirmation.
			if ( ! empty( $data['confirmed'] ) ) {
				confirm_noptin_subscriber_email( $subscriber );
			}

			update_noptin_subscriber( $subscriber, $data );
			$this->show_success( __( 'Subscriber successfully updated', 'newsletter-optin-box' ) );
		}

	}

	/**
	 * Returns an array of notices.
	 *
	 * @access      public
	 * @since       1.2.9
	 */
	public function get_notices() {
		$notices = get_option( 'noptin_notices' );

		if ( ! is_array( $notices ) ) {
			return array();
		}

		return $notices;
	}

	/**
	 * Clears all notices
	 *
	 * @access      public
	 * @since       1.2.9
	 */
	public function clear_notices() {
		delete_option( 'noptin_notices' );
	}

	/**
	 * Saves a new notice
	 *
	 * @access      public
	 * @since       1.2.9
	 */
	public function save_notice( $type, $message ) {
		$notices = $this->get_notices();

		if ( empty( $notices[ $type ] ) || ! is_array( $notices[ $type ]) ) {
			$notices[ $type ] = array();
		}

		$notices[ $type ][] = $message;

		update_option( 'noptin_notices', $notices );
	}

	/**
	 * Displays a success notice
	 *
	 * @param       string $msg The message to qeue.
	 * @access      public
	 * @since       1.1.2
	 */
	public function show_success( $msg ) {
		$this->save_notice( 'success', $msg );
	}

	/**
	 * Displays a error notice
	 *
	 * @access      public
	 * @param       string $msg The message to qeue.
	 * @since       1.1.2
	 */
	public function show_error( $msg ) {
		$this->save_notice( 'error', $msg );
	}

	/**
	 * Displays a warning notice
	 *
	 * @access      public
	 * @param       string $msg The message to qeue.
	 * @since       1.1.2
	 */
	public function show_warning( $msg ) {
		$this->save_notice( 'warning', $msg );
	}

	/**
	 * Displays a info notice
	 *
	 * @access      public
	 * @param       string $msg The message to qeue.
	 * @since       1.1.2
	 */
	public function show_info( $msg ) {
		$this->save_notice( 'info', $msg );
	}

	/**
	 * Show notices
	 *
	 * @access      public
	 * @since       1.1.2
	 */
	public function show_notices() {

		$notices = $this->get_notices();

		// Abort if we do not have any notices.
		if ( empty( $notices ) ) {
			return;
		}

		$this->clear_notices();

		foreach ( $notices as $type => $messages ) {

			if ( ! is_array( $messages ) ) {
				continue;
			}

			$class = esc_attr( "notice notice-$type is-dismissible" );
			foreach ( $messages as $message ) {
				echo "<div class='$class'><p>$message</p></div>";
			}
		}
	}

	/**
	 * Registers screen options for the subscribers page.
	 *
	 * @access      public
	 * @since       1.3.4
	 */
	public function add_subscribers_page_screen_options() {
 
		$args = array(
			'default' => 10,
			'option'  => 'noptin_subscribers_per_page'
		);
	
		add_screen_option( 'per_page', $args );

	}

	/**
	 * Saves subscribers page screen options.
	 *
	 * @access      public
	 * @since       1.3.4
	 */
	public function save_subscribers_page_screen_options( $skip, $option, $value ) {
		return 'noptin_subscribers_per_page' === $option ? $value : $skip;
	}

}
