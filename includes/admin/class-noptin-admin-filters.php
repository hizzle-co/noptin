<?php
/**
 * Registers admin filters
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
}
