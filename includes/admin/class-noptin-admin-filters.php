<?php
/**
 * Registers admin filters and actions
 *
 * @since             1.2.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Main Class
 *
 * @since       1.2.4
 */
class Noptin_Admin_Filters {

	/**
	 * Class constructor.
	 * @since       1.2.4
	 */
	public function __construct() {
		
		add_filter( 'noptin_admin_tools_page_title', array( $this, 'filter_tools_page_titles' ) );
		do_action( 'delete_user', array( $this, 'delete_user_subscriber_link' ) );
		do_action( 'delete_noptin_subscriber', array( $this, 'delete_subscriber_user_link' ) );
	}

	/**
	 * Filters tools page titles.
	 * @since       1.2.4
	 */
	public function filter_tools_page_titles( $title ) {
		
		$titles = array(
			'debug_log'	   => __( 'Debug Log', 'newsletter-optin-box' ),
			'system_info'  => __( 'System Information', 'newsletter-optin-box' ),
		);

		if ( isset( $_GET['tool'] ) && isset( $titles[ $_GET['tool'] ] ) ) {
			return $titles[ $_GET['tool'] ];
		}

		return $title;

	}

	/**
	 * Deletes a user > subscriber connection.
	 * @since       1.2.4
	 * @param int $user_id The id of the user being deleted
	 */
	public function delete_user_subscriber_link( $user_id ) {
		$subscriber_id = get_user_meta ( $user_id, 'noptin_subscriber_id', true );

		if ( ! empty( $subscriber_id ) ) {
			delete_noptin_subscriber_meta( $subscriber_id, 'wp_user_id' );
			delete_user_meta ( $user_id, 'noptin_subscriber_id' );
		}

	}

	/**
	 * Deletes a subscriber > user connection.
	 * @since       1.2.4
	 * @param int $subscriber_id The id of the subscriber being deleted
	 */
	public function delete_subscriber_user_link( $subscriber_id ) {
		$user_id = get_noptin_subscriber_meta ( $subscriber_id, 'wp_user_id', true );

		if ( ! empty( $user_id ) ) {
			delete_noptin_subscriber_meta( $subscriber_id, 'wp_user_id' );
			delete_user_meta ( $user_id, 'noptin_subscriber_id' );
		}

	}

}
