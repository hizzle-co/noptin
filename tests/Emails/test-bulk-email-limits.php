<?php
/**
 * Tests for bulk email sending limits.
 *
 * @package Noptin
 */

namespace Hizzle\Noptin\Tests\Bulk_Emails;

use Hizzle\Noptin\Emails\Bulk\Main;
use Hizzle\Noptin\Emails\Email;

/**
 * Test bulk email sending limits.
 */
class Test_Bulk_Email_Limits extends \WP_UnitTestCase {

	/**
	 * @var Email Test campaign
	 */
	protected $campaign;

	/**
	 * Helper method to create a test email campaign
	 *
	 * @param array $args Optional. Campaign arguments.
	 * @return Email
	 */
	protected function create_test_campaign($args = array()) {
		$default_args = array(
			'type'      => 'newsletter',
			'status'    => 'publish',
			'name'      => 'Test Campaign',
			'subject'   => 'Test Subject',
			'content'   => 'Test Content',
			'options'   => array(
				'email_sender'   => 'noptin',
				'email_type'     => 'normal',
				'template'       => 'default',
				'content_normal' => 'Test Content',
				'template'       => 'paste',
			),
		);

		$args = wp_parse_args($args, $default_args);
		return new Email($args);
	}

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();

		// Init email senders
		if ( ! did_action( 'noptin_init' ) ) {
			Main::init_email_senders();
		}

		// Create a test campaign.
		$this->campaign = $this->create_test_campaign();

		// Delete test subscribers.
		noptin()->db()->delete_all( 'subscribers' );
	}

	/**
	 * After a test method runs, resets any state in WordPress the test method might have changed.
	 */
	public function tear_down() {
		parent::tear_down();

		// Delete the test campaign.
		if ( $this->campaign && $this->campaign->exists() ) {
			wp_delete_post( $this->campaign->id, true );
		}

		// Release any existing lock.
		delete_option( Main::release_lock() );
	}

	/**
	 * Check if rate limit is detected.
	 */
	public function test_max_emails_per_period() {
		// Initially, no limit should be reached.
		$this->assertEmpty( noptin_max_emails_per_period() );

		// Set a limit of 3 emails per period.
		$this->set_per_period_limit( 1 );

		// Now, the limit should be 1.
		$this->assertEquals( 1, noptin_max_emails_per_period() );
	}

	private function set_per_period_limit( $count ) {
		update_noptin_option( 'per_hour', $count );
	}

	/**
	 * Check the rolling period calculation.
	 */
	public function test_rolling_period_calculation() {
		// Default should be 1 hour.
		$this->assertEquals( 3600, noptin_get_email_sending_rolling_period() );

		// Set to 5 minutes.
		$this->set_rolling_period( '5minutes' );

		// Should be 300 seconds.
		$this->assertEquals( 300, noptin_get_email_sending_rolling_period() );
	}

	private function set_rolling_period( $period ) {
		update_noptin_option( 'email_sending_rolling_period', $period );
	}

	/**
	 * Test rate limit detection.
	 */
	public function test_rate_limit_detection() {
		// Set the limit to 2 emails per period.
		$this->set_per_period_limit( 2 );

		// Set the rolling period to 1 hour.
		$this->set_rolling_period( '1hour' );

		// Create 3 test subscribers.
		$this->create_test_subscribers( 3 );

		// Send the campaign.
		$start_time = time();
		$this->campaign->save();

		// Check if limit was reached.
		$this->assertTrue( noptin_email_sending_limit_reached() );

		// Check that only 2 emails were sent.
		$this->assertEquals( 2, noptin_emails_sent_this_period() );
		$this->assertEquals( 2, get_post_meta( $this->campaign->id, '_noptin_sends', true ) );

		/** @var \Hizzle\Noptin\Tasks\Task[] $tasks */
		$tasks = \Hizzle\Noptin\Tasks\Main::query(
			array(
				'hook'   => Main::HEALTH_CHECK_HOOK,
				'status' => 'pending',
			)
		);

		// We should have one scheduled task.
		$this->assertCount( 1, $tasks );

		// Check that the sending health task is scheduled to the next send time.
		$next_send_time = noptin_get_next_email_send_time();
		$this->assertEquals( $next_send_time, $tasks[0]->get_date_scheduled()->getTimestamp() );
		$this->assertGreaterThan( time(), $next_send_time );
		$this->assertGreaterThanOrEqual( $start_time + HOUR_IN_SECONDS, $next_send_time );

		// Check that the normal sending task is not scheduled.
		$this->assertEmpty( next_scheduled_noptin_background_action( Main::TASK_HOOK ) );

		// The campaign should not be marked as completed.
		$this->assertEmpty( get_post_meta( $this->campaign->id, 'completed', true ) );

		// The campaign should not be paused.
		$this->assertEmpty( get_post_meta( $this->campaign->id, 'paused', true ) );

		// There should only be 1 remaining recipient.
		$remaining_recipients = get_post_meta( $this->campaign->id, 'subscriber_to_send', true );
		$this->assertCount( 1, $remaining_recipients );
	}

	/**
	 * Test concurrent sending attempts with lock.
	 */
	public function test_lock_prevents_concurrent_sends() {
		// Simulate acquiring lock.
		$this->assertTrue( Main::acquire_lock() );

		// Second attempt should fail.
		$this->assertFalse( Main::acquire_lock() );

		// Release the lock.
		Main::release_lock();

		// Now we should be able to acquire the lock again.
		$this->assertTrue( Main::acquire_lock() );
	}

	/**
	 * Test stale lock cleanup.
	 */
	public function test_stale_lock_cleanup() {
		// Manually set a lock.
		add_option( Main::LOCK_KEY, time() - Main::LOCK_TTL - 1, '', 'no' );

		// We should be able to acquire the lock since it's stale.
		$this->assertTrue( Main::acquire_lock() );
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
}
