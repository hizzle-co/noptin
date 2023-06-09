<?php
/**
 * Manages the subscribers admin page.
 *
 * @since             1.5.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manage subscribers page.
 *
 * @since 1.5.5
 */
class Noptin_Subscribers_Admin {

	/**
	 * Inits relevant hooks.
	 *
	 * @since 1.5.5
	 */
	public static function init_hooks() {
		add_action( 'add_meta_boxes_noptin_subscribers', 'Noptin_Subscribers_Admin::register_metaboxes' );
		add_action( 'noptin_admin_delete_all_subscribers', 'Noptin_Subscribers_Admin::delete_all_subscribers' );
		add_action( 'noptin_delete_email_subscriber', 'Noptin_Subscribers_Admin::delete_subscriber' );
		add_action( 'admin_init', 'Noptin_Subscribers_Admin::maybe_redirect_to_newsletter' );
	}

	/**
	 * Registers metaboxes.
	 *
	 * @param Noptin_Subscriber $subscriber
	 * @since 1.5.5
	 */
	public static function register_metaboxes( $subscriber ) {

		add_meta_box(
			'noptin_subscriber_details',
			__( 'Subscriber Details', 'newsletter-optin-box' ),
			'Noptin_Subscribers_Admin::metabox_callback',
			'noptin_page_noptin-subscribers',
			'normal',
			'default',
			'details'
		);

		add_meta_box(
			'noptin_subscriber_activity',
			__( 'Activity Feed', 'newsletter-optin-box' ),
			'Noptin_Subscribers_Admin::metabox_callback',
			'noptin_page_noptin-subscribers',
			'advanced',
			'default',
			'activity'
		);

		add_meta_box(
			'noptin_subscriber_save',
			__( 'Save Changes', 'newsletter-optin-box' ),
			'Noptin_Subscribers_Admin::metabox_callback',
			'noptin_page_noptin-subscribers',
			'side',
			'default',
			'save'
		);

		if ( apply_filters( 'noptin_enable_geolocation', true ) ) {
			$ip_address = $subscriber->ip_address;
			if ( ! empty( $ip_address ) && '::1' !== $ip_address && noptin_locate_ip_address( $ip_address ) ) {

				add_meta_box(
					'noptin_subscriber_location',
					__( 'GeoLocation', 'newsletter-optin-box' ),
					'Noptin_Subscribers_Admin::metabox_callback',
					'noptin_page_noptin-subscribers',
					'side',
					'default',
					'geolocation'
				);

			}
		}

	}

	/**
	 * Displays default metaboxes.
	 *
	 * @param Noptin_Subscriber $subscriber.
	 * @param array $metabox.
	 * @since 1.5.5
	 */
	public static function metabox_callback( $subscriber, $metabox ) {

		$file = trim( $metabox['args'] );
		$file = plugin_dir_path( __FILE__ ) . "views/single-subscriber/$file.php";

		if ( file_exists( $file ) ) {
			include $file;
		}

	}

	/**
	 * Displays the subscribers admin page.
	 *
	 * @since       1.5.5
	 */
	public static function output() {

		// Only admins should access this page.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		do_action( 'noptin_before_admin_subscribers_page', noptin()->admin );

		$is_component_page = false;

		// Either render the appropriate component...
		foreach ( self::get_components() as $component => $details ) {

			if ( isset( $_GET[ $component ] ) ) {
				call_user_func( $details['callback'] );
				$is_component_page = true;
				break;
			}
		}

		// Or the subscriber's overview page.
		if ( ! $is_component_page ) {
			self::render_subscribers_overview_page();
		}

		do_action( 'noptin_after_admin_subscribers_page', noptin()->admin );
	}

	/**
	 * Returns the sub-pages.
	 *
	 * @since       1.5.5
	 */
	public static function get_components() {

		return apply_filters(
			'noptin_admin_subscribers_page_components',
			array(
				'custom_fields' => array(
					'callback'     => 'Noptin_Subscribers_Admin::render_custom_fields_page',
					'show_on_tabs' => true,
					'label'        => __( 'Custom Fields', 'newsletter-optin-box' ),
				),
			)
		);

	}

	/**
	 * Renders subscribers overview page
	 *
	 * @since       1.5.5
	 */
	public static function render_subscribers_overview_page() {
		do_action( 'noptin_before_subscribers_overview_page', noptin()->admin );
		include plugin_dir_path( __FILE__ ) . 'views/view-subscribers.php';
		do_action( 'noptin_after_subscribers_overview_page', noptin()->admin );
	}

	/**
	 * Deletes all subscribers.
	 *
	 * @since       1.5.5
	 */
	public static function delete_all_subscribers() {
		global $wpdb;

		// Only admins should be able to add subscribers.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['_wpnonce'] ) ) {
			return;
		}

		// Verify nonces to prevent CSRF attacks.
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'noptin-delete-subscribers' ) ) {
			return;
		}

		// Truncate subscriber tables.
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}noptin_subscribers" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}noptin_subscriber_meta" );

		$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'noptin_subscriber_id' ), '%s' );

		do_action( 'noptin_delete_all_subscribers' );

		noptin()->admin->show_info( __( 'Successfully deleted all subscribers.', 'newsletter-optin-box' ) );
		wp_safe_redirect( remove_query_arg( array( 'noptin_admin_action', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * Deletes a single subscriber.
	 *
	 * @since       1.7.0
	 */
	public static function delete_subscriber() {

		// Only admins should be able to add subscribers.
		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['noptin_nonce'] ) ) {
			return;
		}

		// Verify nonces to prevent CSRF attacks.
		if ( ! wp_verify_nonce( $_GET['noptin_nonce'], 'noptin_delete_subscriber' ) ) {
			return;
		}

		// Delete the subscriber.
		delete_noptin_subscriber( (int) $_GET['subscriber_id'] );

		// Show success then redirect the user.
		noptin()->admin->show_info( __( 'Successfully deleted the subscriber.', 'newsletter-optin-box' ) );
		wp_safe_redirect( remove_query_arg( array( 'noptin_admin_action', '_wpnonce', 'subscriber_id' ) ) );
		exit;
	}

	/**
	 * Redirect to the newsletter page when user selects the send email bulk action.
	 *
	 * @since 1.10.1
	 */
	public static function maybe_redirect_to_newsletter() {

		// Ensure we're on the correct screen.
		if ( ! ( is_admin() && isset( $_GET['page'] ) && 'noptin-subscribers' === $_GET['page'] ) ) {
			return;
		}

		// Check nonce.
		if ( empty( $_POST['id'] ) || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bulk-ids' ) ) {
			return;
		}

		if ( empty( $_POST['filter_action'] ) && isset( $_POST['action'] ) && 'send_email' === $_POST['action'] ) {
			wp_safe_redirect( get_noptin_email_recipients_url( $_POST['id'], 'noptin' ) );
			exit;
		}
	}
}
