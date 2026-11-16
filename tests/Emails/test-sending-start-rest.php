<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** First-party regression coverage migrated from sending-start-rest.php. */
class Test_Sending_Start_REST extends Noptin_Regression_Test_Case {
	/** Verify sending start rest using the isolated WordPress test database. */
	public function test_sending_start_rest() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		remove_all_actions( 'noptin_newsletter_campaign_published' );
		remove_all_actions( 'noptin_send_newsletter' );
		add_filter( 'noptin_saved_task_background_run', '__return_false' );
		remove_action( 'shutdown', array( $GLOBALS['noptin_tasks'], 'run_overdue_tasks_on_shutdown' ), 1 );
		add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
		remove_all_actions( 'noptin_newsletter_campaign_published' );
		remove_all_actions( 'noptin_send_newsletter' );
		$assert = array( $this, 'assertTrue' );
		$id = wp_insert_post( array( 'post_type' => 'noptin-campaign', 'post_status' => 'draft', 'post_title' => 'Sending start fixture', 'post_content' => '<p>Fixture content.</p>' ), true );
		if ( is_wp_error( $id ) ) throw new RuntimeException( $id->get_error_message() );
		try {
			update_post_meta( $id, 'campaign_type', 'newsletter' );
			update_post_meta( $id, 'campaign_data', array( 'subject' => 'Fixture', 'email_sender' => 'manual_recipients', 'recipients' => 'nobody@example.test' ) );
			global $wpdb;
			$wpdb->update( $wpdb->posts, array( 'post_status' => 'publish' ), array( 'ID' => $id ) );
			clean_post_cache( $id );
			$read = static function () use ( $id ) {
				$request = new WP_REST_Request( 'GET', '/wp/v2/noptin-campaign/' . $id );
				$request->set_query_params( array( 'context' => 'edit', '_fields' => 'id,noptin_list' ) );
				return rest_do_request( $request )->get_data()['noptin_list'];
			};
			$assert( '' === $read()['sending_started_label'], 'Legacy start must not be invented for display.' );
			$start = time() - 7200;
			update_post_meta( $id, '_noptin_sending_started', $start );
			update_post_meta( $id, '_noptin_last_activity', time() - 3600 );
			$data = $read();
			$assert( 'sending' === $data['state'] && $start === $data['sending_started'] && '' !== $data['sending_started_label'] && '' !== $data['sending_started_at'], 'Sending time missing from REST response.' );
			$assert( $data['last_activity'] > $start && '' !== $data['last_activity_at'] && '' !== $data['last_activity_label'], 'Last sending activity missing from REST response.' );
			delete_post_meta( $id, '_noptin_last_activity' );
			$assert( '' === $read()['last_activity_label'] && '' !== $read()['sending_started_label'], 'No-activity campaigns must retain the start-time fallback.' );
			update_post_meta( $id, '_noptin_last_activity', time() - 3600 );
			update_post_meta( $id, 'paused', 1 );
			$assert( '' === $read()['sending_started_label'], 'Paused campaigns must hide elapsed sending time.' );
			$assert( '' === $read()['last_activity_label'], 'Paused campaigns must hide elapsed activity time.' );
			delete_post_meta( $id, 'paused' );
			update_post_meta( $id, 'completed', 1 );
			$assert( '' === $read()['sending_started_label'], 'Completed campaigns must hide elapsed sending time.' );
			$assert( '' === $read()['last_activity_label'], 'Completed campaigns must hide elapsed activity time.' );
			$queue_called = false;
			add_action( 'noptin_newsletter_campaign_published', static function ( $campaign ) use ( $id, $assert, &$queue_called ) {
				if ( $campaign->id !== $id ) return;
				$assert( '' === get_post_meta( $id, '_noptin_sending_started', true ) && '' === get_post_meta( $id, '_noptin_last_activity', true ), 'Resend did not reset the previous sending run.' );
				$queue_called = true;
			} );
			$request = new WP_REST_Request( 'POST', '/wp/v2/noptin-campaign/' . $id . '/list-action' );
			$request->set_body_params( array( 'action' => 'resend', 'recipients' => 'all' ) );
			$response = rest_do_request( $request );
			$assert( 200 === $response->get_status() && $queue_called, 'Resend failed to enter the queue hook.' );
		} finally {
			wp_delete_post( $id, true );
		}
	}
}
