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
			if( empty( $subscriber ) ) {
				return false;
			}

			// Else, sync the subscriber and move on to the next user on the list.
			sync_noptin_subscribers_to_users( $subscriber );
			return $item;

		} else {

			// Fetch the next user to sync.
			$user = $this->get_unsynced_user();
			
			// If all users are synced, return false to stop the process.
			if( empty( $user ) ) {
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
		global $wpdb;

		// Fetch unsynced subscribers.
		$meta_query = new WP_Meta_Query(
			array(
				'relation' => 'AND',
				array(
					'key'     => 'wp_user_id',
					'compare' => 'NOT EXISTS',
				),
			)
		);

		// Subscribers' table.
		$table = get_noptin_subscribers_table_name();

		// Retrieve join and where clauses.
		$clauses = $meta_query->get_sql( 'noptin_subscriber', $table, 'id' );

		$query = "SELECT $table.id FROM $table {$clauses['join']} WHERE 1=1 {$clauses['where']} LIMIT 1";

		return $wpdb->get_var( $query );

	}

	/**
	 * Fetches an unsynced WordPress User.
	 * 
	 * @return int;
	 */
	public function get_unsynced_user() {

		$user = get_users( array(
			'meta_key'	    => 'noptin_subscriber_id',
			'meta_compare'	=> 'NOT EXISTS',
			'number'		=> 1,
			'fields'		=> 'ID',
		));

		if( empty( $user ) ) {
			return 0;
		}
		return $user[0];

	}

	/**
	 * Complete.
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		delete_option( 'noptin_subscribers_syncing' );
		parent::complete();
	}

}
