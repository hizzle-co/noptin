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
		add_action( 'noptin_admin_delete_all_subscribers', 'Noptin_Subscribers_Admin::delete_all_subscribers' );
		add_action( 'admin_init', 'Noptin_Subscribers_Admin::maybe_redirect_to_newsletter' );
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
