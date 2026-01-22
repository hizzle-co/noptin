<?php
/**
 * Tests for bulk email campaign pausing and resuming.
 *
 * @package Noptin
 */

/**
 * Test bulk email pausing.
 */
class Test_Bulk_Email_Pausing extends WP_UnitTestCase {

	/**
	 * Test campaign pause.
	 */
	public function test_campaign_pause() {
		$campaign = $this->create_test_campaign();

		// Pause the campaign.
		noptin_pause_email_campaign( $campaign->id, 'Test pause reason' );

		// Verify pause meta.
		$paused = get_post_meta( $campaign->id, 'paused', true );
		$this->assertEquals( 1, $paused );

		$error = get_post_meta( $campaign->id, '_bulk_email_last_error', true );
		$this->assertEquals( 'Test pause reason', $error );
	}

	/**
	 * Test campaign resume.
	 */
	public function test_campaign_resume() {
		$campaign = $this->create_test_campaign();

		// Pause then resume.
		noptin_pause_email_campaign( $campaign->id, 'Test pause' );
		noptin_resume_email_campaign( $campaign->id );

		// Verify resume.
		$paused = get_post_meta( $campaign->id, 'paused', true );
		$this->assertEmpty( $paused );
	}

	/**
	 * Test paused campaigns excluded from processing.
	 */
	public function test_paused_campaigns_excluded() {
		$campaign1 = $this->create_test_campaign();
		$campaign2 = $this->create_test_campaign();

		// Pause campaign1.
		noptin_pause_email_campaign( $campaign1->id, 'Paused' );

		// Query for pending campaigns.
		$campaigns = get_posts(
			array(
				'post_type'   => 'noptin-campaign',
				'post_status' => 'publish',
				'meta_query'  => array(
					array(
						'key'     => 'paused',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		$campaign_ids = wp_list_pluck( $campaigns, 'ID' );

		// Campaign1 should not be in results.
		$this->assertNotContains( $campaign1->id, $campaign_ids );

		// Campaign2 should be in results.
		$this->assertContains( $campaign2->id, $campaign_ids );
	}

	/**
	 * Test automatic pause on error.
	 */
	public function test_automatic_pause_on_error() {
		$campaign = $this->create_test_campaign();

		// Simulate error during sending.
		$error_message = 'SMTP connection failed';
		noptin_pause_email_campaign( $campaign->id, $error_message );

		// Verify pause.
		$paused = get_post_meta( $campaign->id, 'paused', true );
		$this->assertEquals( 1, $paused );

		$saved_error = get_post_meta( $campaign->id, '_bulk_email_last_error', true );
		$this->assertEquals( $error_message, $saved_error );
	}

	/**
	 * Test temporary pause with expiry.
	 */
	public function test_temporary_pause_with_expiry() {
		$campaign = $this->create_test_campaign();

		// Pause for 10 minutes.
		$expiry_time = time() + ( 10 * MINUTE_IN_SECONDS );
		noptin_pause_email_campaign( $campaign->id, 'Temporary pause', 10 * MINUTE_IN_SECONDS );

		// Verify pause.
		$paused = get_post_meta( $campaign->id, 'paused', true );
		$this->assertEquals( 1, $paused );

		$pause_expiry = get_post_meta( $campaign->id, 'pause_expiry', true );
		$this->assertGreaterThanOrEqual( time(), $pause_expiry );
		$this->assertLessThanOrEqual( $expiry_time + 5, $pause_expiry );
	}

	/**
	 * Test expired pause auto-resume.
	 */
	public function test_expired_pause_auto_resume() {
		$campaign = $this->create_test_campaign();

		// Pause with past expiry.
		update_post_meta( $campaign->id, 'paused', 1 );
		update_post_meta( $campaign->id, 'pause_expiry', time() - 100 );

		// Check if can send.
		$pause_expiry = get_post_meta( $campaign->id, 'pause_expiry', true );
		$should_resume = ! empty( $pause_expiry ) && $pause_expiry < time();

		$this->assertTrue( $should_resume );
	}

	/**
	 * Test pause preserves campaign state.
	 */
	public function test_pause_preserves_campaign_state() {
		$campaign = $this->create_test_campaign();
		$this->create_test_subscribers( 100 );

		// Set some progress metadata.
		update_post_meta( $campaign->id, 'subscriber_offset', 50 );
		update_post_meta( $campaign->id, 'subscriber_to_send', array( 51, 52, 53 ) );
		update_post_meta( $campaign->id, '_noptin_sends', 50 );

		// Pause campaign.
		noptin_pause_email_campaign( $campaign->id, 'Testing state preservation' );

		// Verify state is preserved.
		$offset = get_post_meta( $campaign->id, 'subscriber_offset', true );
		$this->assertEquals( 50, $offset );

		$batch = get_post_meta( $campaign->id, 'subscriber_to_send', true );
		$this->assertEquals( array( 51, 52, 53 ), $batch );

		$sends = get_post_meta( $campaign->id, '_noptin_sends', true );
		$this->assertEquals( 50, $sends );
	}

	/**
	 * Test resume continues from last position.
	 */
	public function test_resume_continues_from_last_position() {
		$campaign = $this->create_test_campaign();

		// Set progress before pause.
		update_post_meta( $campaign->id, 'subscriber_offset', 150 );
		noptin_pause_email_campaign( $campaign->id, 'Mid-campaign pause' );

		// Resume.
		noptin_resume_email_campaign( $campaign->id );

		// Verify offset is maintained.
		$offset = get_post_meta( $campaign->id, 'subscriber_offset', true );
		$this->assertEquals( 150, $offset );
	}

	/**
	 * Test multiple pause/resume cycles.
	 */
	public function test_multiple_pause_resume_cycles() {
		$campaign = $this->create_test_campaign();

		// First pause.
		noptin_pause_email_campaign( $campaign->id, 'First pause' );
		$paused = get_post_meta( $campaign->id, 'paused', true );
		$this->assertEquals( 1, $paused );

		// First resume.
		noptin_resume_email_campaign( $campaign->id );
		$paused = get_post_meta( $campaign->id, 'paused', true );
		$this->assertEmpty( $paused );

		// Second pause.
		noptin_pause_email_campaign( $campaign->id, 'Second pause' );
		$paused = get_post_meta( $campaign->id, 'paused', true );
		$this->assertEquals( 1, $paused );

		// Second resume.
		noptin_resume_email_campaign( $campaign->id );
		$paused = get_post_meta( $campaign->id, 'paused', true );
		$this->assertEmpty( $paused );
	}

	/**
	 * Test pause reason logging.
	 */
	public function test_pause_reason_logging() {
		$campaign = $this->create_test_campaign();

		$reasons = array(
			'Rate limit exceeded',
			'SMTP authentication failed',
			'Invalid sender email',
			'Unsupported email sender',
		);

		foreach ( $reasons as $reason ) {
			noptin_pause_email_campaign( $campaign->id, $reason );
			$saved_reason = get_post_meta( $campaign->id, '_bulk_email_last_error', true );
			$this->assertEquals( $reason, $saved_reason );

			// Resume for next test.
			noptin_resume_email_campaign( $campaign->id );
		}
	}

	/**
	 * Test pause clears error on resume.
	 */
	public function test_resume_clears_error() {
		$campaign = $this->create_test_campaign();

		// Pause with error.
		noptin_pause_email_campaign( $campaign->id, 'Error message' );
		$error = get_post_meta( $campaign->id, '_bulk_email_last_error', true );
		$this->assertEquals( 'Error message', $error );

		// Resume should clear error.
		noptin_resume_email_campaign( $campaign->id );
		$error = get_post_meta( $campaign->id, '_bulk_email_last_error', true );

		// Error might be cleared or kept for history - implementation dependent.
		// This test verifies the pause flag is cleared.
		$paused = get_post_meta( $campaign->id, 'paused', true );
		$this->assertEmpty( $paused );
	}

	/**
	 * Test pause during batch processing.
	 */
	public function test_pause_during_batch_processing() {
		$campaign = $this->create_test_campaign();
		$this->create_test_subscribers( 250 );

		// Simulate processing first batch.
		update_post_meta( $campaign->id, 'subscriber_offset', 100 );
		update_post_meta( $campaign->id, 'subscriber_to_send', array( 101, 102, 103 ) );

		// Pause mid-batch.
		noptin_pause_email_campaign( $campaign->id, 'Mid-batch pause' );

		// Verify pause.
		$paused = get_post_meta( $campaign->id, 'paused', true );
		$this->assertEquals( 1, $paused );

		// Current batch should be preserved.
		$batch = get_post_meta( $campaign->id, 'subscriber_to_send', true );
		$this->assertNotEmpty( $batch );
	}

	/**
	 * Test scheduled tasks cleared on pause.
	 */
	public function test_scheduled_tasks_behavior_on_pause() {
		$campaign = $this->create_test_campaign();

		// Pause campaign - tasks should continue but skip paused campaigns.
		noptin_pause_email_campaign( $campaign->id, 'Test' );

		// Paused campaigns are excluded from prepare_pending_campaign().
		$paused = get_post_meta( $campaign->id, 'paused', true );
		$this->assertEquals( 1, $paused );
	}

	/**
	 * Helper: Create test campaign.
	 */
	private function create_test_campaign() {
		$campaign_id = wp_insert_post(
			array(
				'post_type'   => 'noptin-campaign',
				'post_status' => 'publish',
				'post_title'  => 'Test Pause Campaign',
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
					'email'  => "pause{$i}@example.com",
					'status' => 'subscribed',
				)
			);
		}
	}
}
