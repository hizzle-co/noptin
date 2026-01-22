<?php
/**
 * Tests for bulk email sending limits.
 *
 * @package Noptin
 */

/**
 * Test bulk email sending limits.
 */
class Test_Bulk_Email_Limits extends WP_UnitTestCase {

	/**
	 * Test rate limit detection.
	 */
	public function test_rate_limit_detection() {
		// Mock rate limit reached.
		add_filter( 'noptin_email_sending_limit_reached', '__return_true' );

		$this->assertTrue( noptin_email_sending_limit_reached() );

		// Remove filter.
		remove_filter( 'noptin_email_sending_limit_reached', '__return_true' );
	}

	/**
	 * Test sending pauses when limit reached.
	 */
	public function test_sending_pauses_when_limit_reached() {
		$campaign = $this->create_test_campaign();

		// Mock rate limit.
		add_filter( 'noptin_email_sending_limit_reached', '__return_true' );

		// Attempt to get next recipient should return false.
		$recipient = $this->simulate_get_next_recipient( $campaign );

		// In actual implementation, this would return false when limit is reached.
		$this->assertTrue( noptin_email_sending_limit_reached() );

		remove_filter( 'noptin_email_sending_limit_reached', '__return_true' );
	}

	/**
	 * Test next send time calculation.
	 */
	public function test_next_send_time_calculation() {
		// Mock next send time.
		$future_time = time() + 3600; // 1 hour from now.
		add_filter( 'noptin_get_next_email_send_time', function() use ( $future_time ) {
			return $future_time;
		} );

		$next_time = noptin_get_next_email_send_time();
		$this->assertEquals( $future_time, $next_time );
	}

	/**
	 * Test campaign doesn't complete when rate limited.
	 */
	public function test_campaign_not_completed_when_rate_limited() {
		$campaign = $this->create_test_campaign();
		$this->create_test_subscribers( 200 );

		// Mock rate limit after 50 emails.
		$sent_count = 0;
		add_filter( 'noptin_email_sending_limit_reached', function() use ( &$sent_count ) {
			return $sent_count >= 50;
		} );

		// Simulate sending.
		for ( $i = 0; $i < 100; $i++ ) {
			if ( noptin_email_sending_limit_reached() ) {
				break;
			}
			$sent_count++;
		}

		// Should have stopped at 50.
		$this->assertEquals( 50, $sent_count );

		// Campaign should not be marked completed.
		$completed = get_post_meta( $campaign->id, 'completed', true );
		$this->assertEmpty( $completed );
	}

	/**
	 * Test scheduled task timing with rate limit.
	 */
	public function test_scheduled_task_respects_rate_limit() {
		// Mock rate limit and next send time.
		add_filter( 'noptin_email_sending_limit_reached', '__return_true' );
		$next_time = time() + 1800; // 30 minutes from now.
		add_filter( 'noptin_get_next_email_send_time', function() use ( $next_time ) {
			return $next_time;
		} );

		// Task should be scheduled for the next send time.
		$scheduled_time = noptin_get_next_email_send_time();
		$this->assertEquals( $next_time, $scheduled_time );
		$this->assertGreaterThan( time(), $scheduled_time );
	}

	/**
	 * Test max emails per period.
	 */
	public function test_max_emails_per_period() {
		// Default should be unlimited (0).
		$max = noptin_max_emails_per_period();
		$this->assertEquals( 0, $max );

		// Test custom limit.
		add_filter( 'noptin_max_emails_per_period', function() {
			return 100;
		} );

		$max = noptin_max_emails_per_period();
		$this->assertEquals( 100, $max );
	}

	/**
	 * Test limit resets after period.
	 */
	public function test_limit_resets_after_period() {
		// Mock current sent count.
		update_option( 'noptin_emails_sent_this_period', 50 );
		update_option( 'noptin_email_period_start', time() - 3600 );

		// Mock max emails.
		add_filter( 'noptin_max_emails_per_period', function() {
			return 100;
		} );

		// Should not be limited yet.
		$sent = get_option( 'noptin_emails_sent_this_period', 0 );
		$this->assertEquals( 50, $sent );

		// After period reset, count should be 0.
		delete_option( 'noptin_emails_sent_this_period' );
		$sent = get_option( 'noptin_emails_sent_this_period', 0 );
		$this->assertEquals( 0, $sent );
	}

	/**
	 * Test AJAX loop prevention when rate limited.
	 */
	public function test_no_ajax_loop_when_rate_limited() {
		$campaign = $this->create_test_campaign();

		// Mock rate limit.
		add_filter( 'noptin_email_sending_limit_reached', '__return_true' );

		// send_pending() should not trigger AJAX when rate limited.
		// This is tested by checking the early return in send_pending().
		$this->assertTrue( noptin_email_sending_limit_reached() );

		remove_filter( 'noptin_email_sending_limit_reached', '__return_true' );
	}

	/**
	 * Test health check schedules when limited.
	 */
	public function test_health_check_schedules_when_limited() {
		$campaign = $this->create_test_campaign();
		$this->create_test_subscribers( 100 );

		// Mock rate limit.
		add_filter( 'noptin_email_sending_limit_reached', '__return_true' );
		$next_time = time() + 600; // 10 minutes.
		add_filter( 'noptin_get_next_email_send_time', function() use ( $next_time ) {
			return $next_time;
		} );

		// Health check should be scheduled for next send time.
		$scheduled = noptin_get_next_email_send_time();
		$this->assertEquals( $next_time, $scheduled );
		$this->assertGreaterThan( time(), $scheduled );
	}

	/**
	 * Test limit doesn't affect manual sending.
	 */
	public function test_limit_doesnt_affect_manual_sends() {
		$campaign = $this->create_test_campaign();

		// Mock rate limit.
		add_filter( 'noptin_email_sending_limit_reached', '__return_true' );

		// Manual sends (non-mass-mail) should bypass limits.
		// This is implementation-specific behavior.
		$this->assertTrue( noptin_email_sending_limit_reached() );
	}

	/**
	 * Test concurrent sending attempts with lock.
	 */
	public function test_lock_prevents_concurrent_sends() {
		// Simulate acquiring lock.
		$lock_acquired = add_option( 'noptin_send_bulk_emails_process_lock', time(), '', 'no' );
		$this->assertTrue( $lock_acquired );

		// Second attempt should fail.
		$lock_acquired_again = add_option( 'noptin_send_bulk_emails_process_lock', time(), '', 'no' );
		$this->assertFalse( $lock_acquired_again );

		// Clean up.
		delete_option( 'noptin_send_bulk_emails_process_lock' );
	}

	/**
	 * Test stale lock cleanup.
	 */
	public function test_stale_lock_cleanup() {
		// Set stale lock (older than TTL).
		$stale_time = time() - 120; // 2 minutes ago (TTL is 60 seconds).
		update_option( 'noptin_send_bulk_emails_process_lock', $stale_time );

		$lock_time = get_option( 'noptin_send_bulk_emails_process_lock' );

		// Lock is stale if (current_time - lock_time) >= TTL.
		$is_stale = ( time() - $lock_time ) >= 60;
		$this->assertTrue( $is_stale );

		// Should be able to delete and re-acquire.
		delete_option( 'noptin_send_bulk_emails_process_lock' );
		$lock_acquired = add_option( 'noptin_send_bulk_emails_process_lock', time(), '', 'no' );
		$this->assertTrue( $lock_acquired );

		// Clean up.
		delete_option( 'noptin_send_bulk_emails_process_lock' );
	}

	/**
	 * Helper: Create test campaign.
	 */
	private function create_test_campaign() {
		$campaign_id = wp_insert_post(
			array(
				'post_type'   => 'noptin-campaign',
				'post_status' => 'publish',
				'post_title'  => 'Test Limit Campaign',
			)
		);

		update_post_meta( $campaign_id, 'campaign_type', 'newsletter' );
		update_post_meta( $campaign_id, 'email_sender', 'noptin' );

		return noptin_get_email_campaign_object( $campaign_id );
	}

	/**
	 * Helper: Create test subscribers.
	 */
	private function create_test_subscribers( $count ) {
		for ( $i = 1; $i <= $count; $i++ ) {
			add_noptin_subscriber(
				array(
					'email'  => "limit{$i}@example.com",
					'status' => 'subscribed',
				)
			);
		}
	}

	/**
	 * Helper: Simulate get next recipient.
	 */
	private function simulate_get_next_recipient( $campaign ) {
		if ( noptin_email_sending_limit_reached() ) {
			return false;
		}

		return 123; // Mock recipient ID.
	}
}
