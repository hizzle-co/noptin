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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 0 );

		// (maybe) do an action.
		add_action( 'admin_init', array( $this, 'maybe_do_action' ) );

		// Runs when saving a new opt-in form.
		add_action( 'wp_ajax_noptin_save_optin_form', array( $this, 'save_optin_form' ) );

		// Display notices.
		add_action( 'admin_notices', array( $this, 'show_notices' ) );

		add_action( 'noptin_admin_reset_data', array( $this, 'reset_data' ) );

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
	 * @access public
	 * @since  1.0.0
	 * @return void
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
			wp_enqueue_script( 'flatpickr', $this->assets_url . 'vendor/flatpickr/flatpickr.js', array(), '4.6.13', true );
			wp_enqueue_style( 'flatpickr', $this->assets_url . 'vendor/flatpickr/flatpickr.min.css', array(), '4.6.13' );
			wp_enqueue_script( 'noptin-email-campaigns', $this->assets_url . 'js/dist/newsletter-editor.js', array( 'select2', 'sweetalert2', 'postbox' ), $version, true );
		}

	}

	/**
	 * Saves a subscription form.
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
	 * Show notices
	 *
	 * @access      public
	 * @since       1.1.2
	 */
	public function show_notices() {

		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		// Warn addons pack if version is less than 2.0.0
		if ( defined( 'NOPTIN_ADDONS_PACK_VERSION' ) && version_compare( NOPTIN_ADDONS_PACK_VERSION, MINIMUM_SUPPORTED_NOPTIN_ADDONS_PACK_VERSION, '<' ) ) {
			$this->print_notice(
				'error',
				sprintf(
					// translators: %s: Update URL.
					__( 'Your Addons Pack is outdated and nolonger works with this version of Noptin. Please update to the latest version to continue using it. <a href="%s">Update Now</a>', 'newsletter-optin-box' ),
					admin_url( 'update-core.php' )
				)
			);
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

	/**
	 * Resets data.
	 */
	public function reset_data() {

		// Only admins should be able to add subscribers.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['_wpnonce'] ) ) {
			return;
		}

		// Verify nonces to prevent CSRF attacks.
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'noptin-reset-data' ) ) {
			return;
		}

		// Set flag.
		define( 'NOPTIN_RESETING_DATA', true );

		// Clear all data.
		include noptin()->plugin_path . 'uninstall.php';

		// Clear cache.
		wp_cache_flush();
	}

}
