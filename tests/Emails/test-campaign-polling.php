<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** First-party regression coverage migrated from campaign-polling.php. */
class Test_Campaign_Polling extends Noptin_Regression_Test_Case {
	/** Verify campaign polling using the isolated WordPress test database. */
	public function test_polling_selects_the_oldest_sendable_campaign() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		remove_all_actions( 'noptin_newsletter_campaign_published' );
		remove_all_actions( 'noptin_send_newsletter' );
		add_filter( 'noptin_saved_task_background_run', '__return_false' );
		remove_action( 'shutdown', array( $GLOBALS['noptin_tasks'], 'run_overdue_tasks_on_shutdown' ), 1 );
		$ids = array();
		$assert = array( $this, 'assertTrue' );
		// Keep recovery preparation and delivery disabled for this entire test process.
		add_filter('noptin_email_sending_limit_reached', '__return_true');
		add_filter('noptin_max_emails_per_period', static function () { return 1; });
		add_filter('pre_wp_mail', '__return_false');
		try {
		    $queue_head = new ReflectionMethod(Hizzle\Noptin\Emails\Admin\Browser::class, 'oldest_sending_campaign_id');
		    $queue_head->setAccessible( true );
		    $original_head = $queue_head->invoke(null);
		    global $wpdb;
		    for ($i = 0; $i < 3; $i++) {
		        $id = wp_insert_post(array('post_type'=>'noptin-campaign', 'post_status'=>'draft', 'post_title'=>'Codex polling queue fixture', 'post_content'=>'<p>Queue selection fixture.</p>', 'post_author'=>get_current_user_id()));
		        $ids[] = $id;
		        update_post_meta($id, 'campaign_type', 'newsletter');
		        update_post_meta($id, 'campaign_data', (object) array('subject'=>'Queue selection fixture', 'email_type'=>'normal', 'content_normal'=>'<p>Queue selection fixture.</p>', 'email_sender'=>$i ? 'noptin' : 'fixture-unsupported'));
		        $wpdb->update($wpdb->posts, array('post_status'=>'publish', 'post_date'=>'1970-01-02 00:00:00', 'post_date_gmt'=>'1970-01-02 00:00:00'), array('ID'=>$id));
		        clean_post_cache($id);
		    }
		    $assert($ids[1] === $queue_head->invoke(null), 'Polling must skip an invalid oldest campaign');
		    $assert('' === get_post_meta($ids[0], 'paused', true) && '' === get_post_meta($ids[0], '_bulk_email_last_error', true), 'Polling must not pause invalid campaigns or save errors');
		    $selection = Hizzle\Noptin\Emails\Bulk\Main::get_pending_campaign_selection();
		    $assert($ids[1] === $selection['campaign']->id && isset($selection['errors'][$ids[0]]), 'Shared selection must return the same campaign and pending preparation errors');
		    update_post_meta($ids[0], 'paused', 1);
		    $assert($ids[1] === $queue_head->invoke(null), 'Queue head must exclude paused campaigns and break date ties by ID');
		    // Authorized browser-state requests now dispatch sending; cover those with
		    // the isolated campaign-browser-poll fixture rather than altering live tasks.
		    update_post_meta($ids[1], 'completed', 1);
		    $assert($ids[2] === $queue_head->invoke(null), 'Polling must advance past completed campaigns');
		    update_post_meta($ids[2], 'paused', 1);
		    $assert($original_head === $queue_head->invoke(null), 'Inactive fixtures must not displace the actual queue head');
		    wp_set_current_user(0);
		    $assert(0 === $queue_head->invoke(null), 'Queue IDs must not be exposed to unauthorized visitors');
		    $response = rest_do_request(new WP_REST_Request('GET', '/wp/v2/noptin-campaign/browser'));
		    $assert(in_array($response->get_status(), array(401, 403), true), 'Anonymous browser polling must be denied');
		} finally {
		    foreach ($ids as $id) wp_delete_post($id, true);
		}
	}
}
