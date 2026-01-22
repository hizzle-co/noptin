<?php

namespace Hizzle\Noptin\Tests\Bulk_Emails;

use Hizzle\Noptin\Emails\Bulk\Main;


require_once __DIR__ . '/base.php';

/**
 * Test Main bulk email sender class.
 */
class Test_Main extends Noptin_Emails_Test_Case {

	const TEST_SUBSCRIBER_COUNT = 5;

	/**
	 * @var \Hizzle\Noptin\Bulk_Emails\Email_Sender Mock sender for testing
	 */
	protected $mock_sender;

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();

		// Create test subscribers.
		$this->create_test_subscribers( self::TEST_SUBSCRIBER_COUNT );
	}

	/**
	 * Test sending newsletter campaign.
	 */
	public function test_send_newsletter_campaign() {

		// Check that the campaign can be sent
		$can_send = $this->campaign->can_send( true );
		$this->assertNotWPError( $can_send, is_wp_error( $can_send ) ? $can_send->get_error_message() : '' );

		// Check if the sender is available
		$this->assertNotEmpty(Main::has_sender($this->campaign->get_sender()));

		// Send the campaign to all subscribers.
		$this->campaign->save();

		// We should not have any pending campaigns.
		$pending_campaign = Main::prepare_pending_campaign();
		$this->assertFalse( $pending_campaign, 'There should be no pending campaigns. We have already sent this one.' );

		// Verify that the sending task is no longer scheduled.
		$this->assertFalse(
			next_scheduled_noptin_background_action( Main::TASK_HOOK ),
			'Sending task should not be scheduled'
		);

		// Verify that the health check task is no longer scheduled.
		$this->assertFalse(
			next_scheduled_noptin_background_action( Main::HEALTH_CHECK_HOOK ),
			'Health check task should not be scheduled'
		);

		// Verify that the resume task is no longer scheduled.
		$this->assertFalse(
			next_scheduled_noptin_background_action( 'noptin_resume_email_campaign', $this->campaign->id ),
			'Resume task should not be scheduled'
		);

		// Verify that the campaign sent emails to all recipients.
		$this->assertCount( self::TEST_SUBSCRIBER_COUNT, get_post_meta( $this->campaign->id, '_noptin_sends', true ) );

		for ( $i = 1; $i <= self::TEST_SUBSCRIBER_COUNT; $i++ ) {
			$this->assertTrue(
				noptin_email_campaign_sent_to( $this->campaign->id, "test{$i}@example.com" ),
				"Campaign should have sent email to test{$i}@example.com"
			);
		}

		// Check if there was activity logged.
		$this->assertNotEmpty(
			get_post_meta( $this->campaign->id, '_noptin_last_activity', true )
		);
	}

	/**
	 * Test handling unexpected shutdown.
	 */
	public function test_handle_unexpected_shutdown() {
		// Set a sending limit so that the campaign doesn't complete in one go.
		update_noptin_option( 'per_hour', 2 );

		// Send the campaign.
		$this->campaign->save();

		// Simulate an error by pausing the campaign
		noptin_pause_email_campaign(
			$this->campaign->id,
			'Test error message'
		);

		// Verify campaign was paused
		$last_error = get_post_meta($this->campaign->id, '_bulk_email_last_error', true);
		$this->assertEquals(
			'Test error message',
			is_array($last_error) ? ( $last_error['message'] ?? '' ) : ''
		);

		// Test that paused meta was set
		$this->assertEquals(1, (int) get_post_meta($this->campaign->id, 'paused', true));

		// Test that resume action was scheduled
		$this->assertIsNumeric(
			next_scheduled_noptin_background_action(
				'noptin_resume_email_campaign',
				$this->campaign->id
			)
		);
	}

	/**
	 * Test batch processing limits.
	 */
	public function test_sending_locks() {

		// Send the campaign.
		$this->campaign->save();

		// Verify lock was released after processing
		$this->assertFalse( Main::acquire_lock(), 'Lock should be released after processing');

		// Manually set a stale lock.
		add_option( Main::LOCK_KEY, time() - Main::LOCK_TTL - 1, '', 'no' );

		// We should be able to acquire the lock since it's stale.
		$this->assertTrue( Main::acquire_lock() );

		// We should not be able to acquire the lock again immediately.
		$this->assertFalse( Main::acquire_lock(), 'Lock should not be acquirable immediately after being acquired' );

		// Release the lock.
		Main::release_lock();

		// Now we should be able to acquire the lock again.
		$this->assertTrue( Main::acquire_lock() );
	}

	/**
	 * Test batch processing limits.
	 */
	public function test_existing_sending_locks() {

		// We should be able to acquire the lock since no one has it.
		$this->assertTrue( Main::acquire_lock() );

		// Send the campaign.
		$this->campaign->save();

		// It should not have been able to acquire the lock again since we already had it.
		$this->assertFalse( next_scheduled_noptin_background_action( Main::TASK_HOOK ), 'Sending task should not be scheduled since lock was held' );
		$this->assertEmpty( get_post_meta( $this->campaign->id, '_noptin_sends', true ), 'No emails should have been sent since lock was held' );
	}
}
