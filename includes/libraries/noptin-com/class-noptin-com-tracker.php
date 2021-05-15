<?php
/**
 * Noptin.com Tracker.
 *
 * The tracker adds functionality to track Noptin usage based on if the customer opted in.
 * No personal information is tracked, only server versions and subscriber counts and admin email for discount code.
 *
 * @package Noptin\noptin.com
 * @since   1.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Noptin_COM_Tracker Class
 *
 * Main class for usage tracking.
 * @ignore
 */
class Noptin_COM_Tracker {

	/**
	 * Hook into cron event.
	 */
	public static function init() {
		add_action( 'noptin_com_tracker_send_event', array( __CLASS__, 'send_tracking_data' ) );
		add_action( 'noptin_daily_maintenance', array( __CLASS__, 'send_tracking_data' ) );
	}

	/**
	 * Decide whether to send tracking data or not.
	 *
	 * @param boolean $override Should override?.
	 */
	public static function send_tracking_data( $override = false ) {

		// Don't trigger this on AJAX Requests.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( ! apply_filters( 'noptin_com_tracker_send_override', $override ) ) {
			// Send a maximum of once per week by default.
			$last_send = self::get_last_send_time();
			if ( $last_send && $last_send > apply_filters( 'noptin_com_tracker_last_send_interval', strtotime( '-1 week' ) ) ) {
				return;
			}
		} else {
			// Make sure there is at least a 1 hour delay between override sends, we don't want duplicate calls due to double clicking links.
			$last_send = self::get_last_send_time();
			if ( $last_send && $last_send > strtotime( '-1 hours' ) ) {
				return;
			}
		}

		// Update time first before sending to ensure it is set.
		update_option( 'noptin_com_tracker_last_send', time() );

		Noptin_COM_API_Client::post(
			'nopcom/1/stats',
			array(
				'body' => self::get_tracking_data(),
			)
		);

	}

	/**
	 * Get the last time tracking data was sent.
	 *
	 * @return int|bool
	 */
	private static function get_last_send_time() {
		return apply_filters( 'noptin_com_tracker_last_send_time', get_option( 'noptin_com_tracker_last_send', false ) );
	}

	/**
	 * Get all the tracking data.
	 *
	 * @return array
	 */
	private static function get_tracking_data() {
		global $wpdb;

		$table = get_noptin_subscribers_table_name();
		$data  = array();

		// General site info.
		$data['website']      = home_url();
		$data['admin_email']  = apply_filters( 'noptin_com_tracker_admin_email', get_option( 'admin_email' ) );
		$data['active_theme'] = wp_get_theme()->Name;

		// WordPress Info.
		$data['wordpress_version'] = self::standardize_version( get_bloginfo( 'version' ) );
		$data['is_multisite']      = (int) is_multisite();
		$data['site_language']     = get_locale();

		// Server Info.
		$data['php_version']     = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
		$data['server_software'] = ! empty( $_SERVER['SERVER_SOFTWARE'] ) ? strtok( $_SERVER['SERVER_SOFTWARE'], '/' ) : '';
		$data['mysql_version']   = self::get_mysql_version();
		$data['app_version']     = noptin()->version;

		// Store count info.
		$data['subscriber_count']      = get_noptin_subscribers_count();
		$data['installation_date']     = self::guess_installation_date();
		$data['last_subscriber_date']  = $wpdb->get_var( "SELECT `date_created` FROM $table ORDER BY `date_created` DESC LIMIT 1;" );
		$data['first_subscriber_date'] = $wpdb->get_var( "SELECT `date_created` FROM $table ORDER BY `date_created` ASC LIMIT 1;" );
		
		if ( empty( $data['last_subscriber_date'] ) ) {
			unset( $data['last_subscriber_date'] );
		}

		if ( empty( $data['first_subscriber_date'] ) ) {
			unset( $data['first_subscriber_date'] );
		}

		if ( empty( $data['installation_date'] ) ) {
			unset( $data['installation_date'] );
		}

		return apply_filters( 'noptin_com_tracker_data', $data );
	}

	/**
	 * Run the SQL version check.
	 *
	 * @since 1.5.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @return string
	 */
	private static function get_mysql_version() {
		global $wpdb;

		if ( method_exists( $wpdb, 'db_version' ) ) {
			$mysql_version = $wpdb->db_version();

			if ( ! empty( $mysql_version ) ) {
				return self::standardize_version( $mysql_version );
			}

		}

		$mysql_version = $wpdb->get_var( 'SELECT VERSION()' );

		if ( ! empty( $mysql_version ) ) {
			return self::standardize_version( $mysql_version );
		}

		return '';

	}

	/**
	 * Guess the website installation date.
	 *
	 * @since 1.5.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @return string
	 */
	private static function guess_installation_date() {
		global $wpdb;

		// Date of first registered user.
		$registered = get_user_option( 'user_registered', 1 );

		if ( ! empty( $registered ) ) {
			return $registered;
		}

		return $wpdb->get_var( "SELECT `post_date` FROM {$wpdb->posts} ORDER BY `post_date` ASC LIMIT 1;" );

	}

	/**
	 * Standardizes a version number.
	 *
	 * @since 1.5.0
	 *
	 * @param string $version_number
	 * @return string
	 */
	private static function standardize_version( $version_number ) {
		$version_number = explode( '.', $version_number );
		$major          = $version_number[0];
		$minor          = empty( $version_number[1] ) ? '0' : $version_number[1];

		return "$major.$minor";
	}

}

Noptin_COM_Tracker::init();
