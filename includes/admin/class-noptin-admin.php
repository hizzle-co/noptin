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

		// (maybe) do an action.
		add_action( 'admin_init', array( $this, 'maybe_do_action' ) );

		// Display notices.
		add_action( 'admin_notices', array( $this, 'show_notices' ) );

		add_action( 'noptin_admin_reset_data', array( $this, 'reset_data' ) );

		Noptin_Vue::init_hooks();
		Noptin_Tools::add_hooks();

		/**
		 * Runs right after registering admin hooks.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_after_admin_init_hooks', $this );
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

		// Review nag.
		if ( isset( $_GET['noptin_review_nag'] ) && isset( $_GET['noptin-review-nag-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['noptin-review-nag-nonce'] ) ), 'noptin-review-nag' ) ) {
			update_option( 'noptin_review_nag', (int) $_GET['noptin_review_nag'] );
			wp_safe_redirect( remove_query_arg( array( 'noptin_review_nag', 'noptin-review-nag-nonce' ) ) );
			exit;
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

		if ( doing_action( 'admin_notices' ) ) {

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

			// If user has been using Noptin for a while, show them a notice to rate the plugin.
			$review_nag = get_option( 'noptin_review_nag', time() + WEEK_IN_SECONDS );
			if ( ! empty( $review_nag ) && (int) $review_nag < time() ) {
				$this->print_notice(
					'info',
					sprintf(
						'%s %s <br><br> %s %s %s',
						__( 'You have been using Noptin for a while now.', 'newsletter-optin-box' ),
						sprintf(
							/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
							esc_html__( 'Thousands of hours have gone into this plugin. If you love it, Consider %1$sgiving us a 5* rating on WordPress.org%2$s. It takes less than 5 minutes.', 'newsletter-optin-box' ),
							'<a href="https://wordpress.org/support/plugin/newsletter-optin-box/reviews/?filter=5#new-post" target="_blank">',
							'</a>'
						),
						sprintf(
							'<a href="https://wordpress.org/support/plugin/newsletter-optin-box/reviews/?filter=5#new-post" target="_blank" class="button button-primary">%s</a>',
							__( 'Rate Noptin', 'newsletter-optin-box' )
						),
						sprintf(
							'<a href="%s" class="button">%s</a>',
							wp_nonce_url( add_query_arg( 'noptin_review_nag', time() + YEAR_IN_SECONDS ), 'noptin-review-nag', 'noptin-review-nag-nonce' ),
							__( 'I already did!', 'newsletter-optin-box' )
						),
						sprintf(
							'<a href="%s" class="button button-link">%s</a>',
							wp_nonce_url( add_query_arg( 'noptin_review_nag', time() + WEEK_IN_SECONDS ), 'noptin-review-nag', 'noptin-review-nag-nonce' ),
							__( 'Not now', 'newsletter-optin-box' )
						)
					)
				);
			}
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
