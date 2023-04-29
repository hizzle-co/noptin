<?php
/**
 * Admin section
 *
 * Simple WordPress optin form
 *
 * @since             1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
	 * @access public
	 * @since  1.0.0
	 * @var    string|null
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
	 * Admin menus.
	 *
	 * @var Noptin_Admin_Menus
	 * @access public
	 * @since  1.9.0
	 */
	public $admin_menus;

	/**
	 * Assets URL.
	 *
	 * @var string
	 */
	public $assets_url;

	/**
	 * Assets path.
	 *
	 * @var string
	 */
	public $assets_path;

	/**
	 * Admin filters.
	 *
	 * @var Noptin_Admin_Filters
	 */
	public $filters;

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
		$this->admin_path  = plugin_dir_path( __FILE__ );
		$this->admin_url   = plugins_url( '/', __FILE__ );
		$this->assets_url  = plugin_dir_url( Noptin::$file ) . 'includes/assets/';
		$this->assets_path = plugin_dir_path( Noptin::$file ) . 'includes/assets/';

		$this->admin_menus = new Noptin_Admin_Menus();

		$this->filters = new Noptin_Admin_Filters();

		// initialize hooks.
		Noptin_Subscribers_Admin::init_hooks();
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 0 );

		// (maybe) do an action.
		add_action( 'admin_init', array( $this, 'maybe_do_action' ) );
		add_action( 'noptin_created_new_custom_fields', array( $this, 'noptin_created_new_custom_fields' ) );

		// Runs when saving a new opt-in form.
		add_action( 'wp_ajax_noptin_save_optin_form', array( $this, 'save_optin_form' ) );

		// Display notices.
		add_action( 'admin_notices', array( $this, 'show_notices' ) );

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
	public function enqueue_scripts() {
		global $current_screen;

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

		// Codemirror for editor css.
		if ( 'noptin-form' === $page ) {

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

		}

		// Optin forms editor.
		$editing_new_form    = isset( $_GET['post'] ) && ! is_legacy_noptin_form( (int) $_GET['post'] );
		$editing_legacy_form = isset( $_GET['post'] ) && is_legacy_noptin_form( (int) $_GET['post'] );
		if ( 'noptin-form' === $page ) {

			if ( ! $editing_new_form && ( ! is_using_new_noptin_forms() || $editing_legacy_form ) ) {
				wp_enqueue_style( 'noptin-modules', $this->assets_url . 'js/dist/modules.css', array(), noptin()->version );
			}
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

			$params = array(
				'ajaxurl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'noptin_subscribers' ),
				'reloading'         => __( 'Reloading the page', 'newsletter-optin-box' ),
				'close'             => __( 'Close', 'newsletter-optin-box' ),
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

			if ( ! empty( $_GET['import'] ) ) {
				$version = filemtime( $this->assets_path . 'js/dist/subscribers-import.js' );
				wp_enqueue_script( 'noptin-import-subscribers', $this->assets_url . 'js/dist/subscribers-import.js', array(), $version, true );
			}
		}

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
		$state  = map_deep( wp_unslash( $_POST['state'] ), 'noptin_sanitize_booleans' );
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
			wp_die( esc_html( $post->get_error_message() ) );
		}

		if ( empty( $state['showPostTypes'] ) ) {
			$state['showPostTypes'] = array();
		}

		update_post_meta( $id, '_noptin_state', $state );
		update_post_meta( $id, '_noptin_optin_type', $state['optinType'] );

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

		delete_transient( 'noptin_subscription_sources' );

		exit; // This is important.
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
				wp_safe_redirect( add_query_arg( array( 'page' => 'noptin-settings' ), admin_url( 'admin.php' ) ) );
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

		if ( empty( $notices[ $type ] ) || ! is_array( $notices[ $type ] ) ) {
			$notices[ $type ] = array();
		}

		$notices[ $type ][] = $message;

		update_option( 'noptin_notices', $notices );
	}

	/**
	 * Displays a success notice
	 *
	 * @param       string $msg The message to queue.
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
	 * @param       string $msg The message to queue.
	 * @since       1.1.2
	 */
	public function show_error( $msg ) {
		$this->save_notice( 'error', $msg );
	}

	/**
	 * Displays a warning notice
	 *
	 * @access      public
	 * @param       string $msg The message to queue.
	 * @since       1.1.2
	 */
	public function show_warning( $msg ) {
		$this->save_notice( 'warning', $msg );
	}

	/**
	 * Displays a info notice
	 *
	 * @access      public
	 * @param       string $msg The message to queue.
	 * @since       1.1.2
	 */
	public function show_info( $msg ) {
		$this->save_notice( 'info', $msg );
	}

	/**
	 * Hide the custom fields notice
	 *
	 * @access      public
	 * @since       1.5.5
	 */
	public function noptin_created_new_custom_fields() {

		if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'noptin_created_new_custom_fields' ) ) {
			return;
		}

		update_option( 'noptin_created_new_custom_fields', 1 );
		wp_safe_redirect( remove_query_arg( array( '_wpnonce', 'noptin_admin_action' ) ) );
		exit;
	}

	/**
	 * Show notices
	 *
	 * @access      public
	 * @since       1.1.2
	 */
	public function show_notices() {

		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		$custom_fields = get_noptin_option( 'custom_fields' );

		if ( empty( $custom_fields ) && ! get_option( 'noptin_created_new_custom_fields' ) && ( empty( $_GET['page'] ) || 'noptin-settings' !== $_GET['page'] ) ) {

			$message = sprintf(
				'%s<br><br><a class="button button-primary" href="%s">%s</a> <a class="button button-secondary" href="%s">%s</a>',
				__( 'Noptin has changed the way it handles custom fields to give you more control.', 'newsletter-optin-box' ),
				esc_url_raw( admin_url( 'admin.php?page=noptin-settings&tab=fields' ) ),
				__( 'Set Up Custom Fields', 'newsletter-optin-box' ),
				wp_nonce_url( add_query_arg( 'noptin_admin_action', 'noptin_created_new_custom_fields' ), 'noptin_created_new_custom_fields' ),
				__( 'Dismiss this notice forever', 'newsletter-optin-box' )
			);

			$this->print_notice( 'info', $message );
		}

		$notices = $this->get_notices();

		// Abort if we do not have any notices.
		if ( empty( $notices ) || ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		$this->clear_notices();

		foreach ( $notices as $type => $messages ) {

			if ( ! is_array( $messages ) ) {
				continue;
			}

			foreach ( $messages as $message ) {
				$this->print_notice( $type, $message );
			}
		}
	}

	/**
	 * Prints a single notice.
	 *
	 * @param string $type
	 * @param string $message
	 * @since       1.5.5
	 */
	public function print_notice( $type, $message ) {

		printf(
			'<div class="notice notice-%s noptin-notice is-dismissible"><p>%s</p></div>',
			esc_attr( sanitize_html_class( $type ) ),
			wp_kses_post( $message )
		);

	}

}
