<?php
/**
 * class Noptin_Background_Sync class.
 *
 * @extends Noptin_Background_Process
 */

defined( 'ABSPATH' ) || exit;

class Noptin_Background_Sync extends Noptin_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'noptin_bg_sync';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param array $item
	 *
	 * @return mixed
	 */
	public function task( $item ) {

		// Item can either be a subscriber or a WordPress user.

		if( 'subscriber' == $item ) {

			// Fetch the next subscriber to sync.
			$subscriber = $this->get_unsynced_subscriber();

			// If all subscribers are synced, return false to stop the process.
			if ( empty( $subscriber ) ) {
				delete_option( 'noptin_subscribers_syncing' );
				return false;
			}

			// Else, sync the subscriber and move on to the next user on the list.
			sync_noptin_subscribers_to_users( $subscriber );
			return $item;

		} else if ( 'wp_user' == $item ) {

			// Fetch the next user to sync.
			$user = $this->get_unsynced_user();

			// If all users are synced, return false to stop the process.
			if ( empty( $user ) ) {
				delete_option( 'noptin_users_bg_sync' );
				return false;
			}

			// Else, sync the user and move on to the next user on the list.
			sync_users_to_noptin_subscribers( $user );
			return $item;

		}

	}

	/**
	 * Fetches an unsynced subscriber.
	 *
	 * @return string|null;
	 */
	public function get_unsynced_subscriber() {

		$query = new Noptin_Subscriber_Query(
			array(
				'meta_key'	    => 'wp_user_id',
				'meta_compare'  => 'NOT EXISTS',
				'number'        => 1,
				'fields'        => 'id',
				'count_total'   => false,
			)
		);

		$subscriber = $query->get_results();

		return empty( $subscriber ) ? 0 : $subscriber[0];

	}

	/**
	 * Hides inactive subscribers from the sync.
	 *
	 * @param WP_User_Query $query The user query.
	 */
	public function filter_user_query( &$query ) {
		global $wpdb;

		$query->query_where .= " AND $wpdb->users.user_status = 0 ";

		if ( is_multisite() ) {
			$query->query_where .= " AND $wpdb->users.deleted = 0 AND $wpdb->users.spam = 0 ";
		}

	}

	/**
	 * Fetches an unsynced WordPress User.
	 *
	 * @return int;
	 */
	public function get_unsynced_user() {

		// For buddypress and co, do not sync inactive users.
		add_action( 'pre_user_query', array( $this, 'filter_user_query' ) );

		$user = get_users(
			array(
				'meta_key'	    => 'noptin_subscriber_id',
				'meta_compare'  => 'NOT EXISTS',
				'number'        => 1,
				'fields'        => 'ID',
			)
		);

		// For buddypress and co, do not sync inactive users.
		remove_action( 'pre_user_query', array( $this, 'filter_user_query' ) );

		if( empty( $user ) ) {
			return 0;
		}

		return $user[0];

	}

}
