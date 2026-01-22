<?php
/**
 * Tests for bulk email campaign pausing and resuming.
 *
 * @package Noptin
 */

namespace Hizzle\Noptin\Tests\Bulk_Emails;

use Hizzle\Noptin\Emails\Bulk\Main;
use Hizzle\Noptin\Emails\Email;

/**
 * Test bulk email pausing.
 */
class Test_Bulk_Email_Pausing extends \WP_UnitTestCase {

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

		// Release any existing lock.
		delete_option( Main::release_lock() );

		// Create 5 subscribers.
		$this->create_test_subscribers( 20 );

		// Set sending limit.
		update_noptin_option( 'per_hour', 1 );
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

		// Delete test subscribers.
		noptin()->db()->delete_all( 'subscribers' );

		// Release any existing lock.
		delete_option( Main::release_lock() );
	}

	/**
	 * Test campaign pause.
	 */
	public function test_campaign_resume_without_increasing_limits() {
		// Start sending the campaign.
		$this->campaign->save();

		// Pause the campaign.
		noptin_pause_email_campaign( $this->campaign->id, 'Test pause reason' );

		// Verify pause meta.
		$this->assertEquals( 1, get_post_meta( $this->campaign->id, 'paused', true ) );

		$error = get_post_meta( $this->campaign->id, '_bulk_email_last_error', true );
		$this->assertEquals( 'Test pause reason', $error['message'] ?? '' );

		// Check that a task to resume the campaign is scheduled.
		$this->assertNotEmpty( next_scheduled_noptin_background_action( 'noptin_resume_email_campaign' ) );

		// Resume the campaign.
		noptin_resume_email_campaign( $this->campaign->id );

		// Verify pause meta cleared.
		$this->assertEmpty( get_post_meta( $this->campaign->id, 'paused', true ) );

		$this->assertEmpty( get_post_meta( $this->campaign->id, '_bulk_email_last_error', true ) );

		// Check that no resume task is scheduled.
		$this->assertEmpty( next_scheduled_noptin_background_action( 'noptin_resume_email_campaign' ) );

		// Check that no sending tasks are scheduled since we have passed the limit.
		$this->assertEmpty( next_scheduled_noptin_background_action( Main::TASK_HOOK ) );

		// Check that sending health task is scheduled.
		$this->assertNotEmpty( next_scheduled_noptin_background_action( Main::HEALTH_CHECK_HOOK ) );

		// Check that we have sent 1 email.
		$this->assertEquals( 1, (int) get_post_meta( $this->campaign->id, '_noptin_sends', true ) );
	}

	/**
	 * Test campaign pause.
	 */
	public function test_campaign_resume_after_increasing_limits() {
		// Start sending the campaign.
		$this->campaign->save();

		// Pause the campaign.
		noptin_pause_email_campaign( $this->campaign->id, 'Test pause reason' );

		// Check that a task to resume the campaign is scheduled.
		$this->assertNotEmpty( next_scheduled_noptin_background_action( 'noptin_resume_email_campaign' ) );

		// Increase sending limits.
		update_noptin_option( 'per_hour', 10 );

		// Resume the campaign.
		noptin_resume_email_campaign( $this->campaign->id );

		// Check that no resume task is scheduled.
		$this->assertEmpty( next_scheduled_noptin_background_action( 'noptin_resume_email_campaign' ) );

		// Check that sending tasks are scheduled since we have passed the limit.
		$this->assertNotEmpty( next_scheduled_noptin_background_action( Main::TASK_HOOK ) );

		// Check that sending health task is scheduled.
		$this->assertNotEmpty( next_scheduled_noptin_background_action( Main::HEALTH_CHECK_HOOK ) );

		// Check that we have sent 10 emails.
		$this->assertEquals( 10, (int) get_post_meta( $this->campaign->id, '_noptin_sends', true ) );
	}

	/**
	 * Test paused campaigns excluded from processing.
	 */
	public function test_paused_campaigns_excluded() {
		$this->campaign->save();

		// Pause campaign1.
		noptin_pause_email_campaign( $this->campaign->id, 'Paused' );

		// We should have no sendable campaigns.
		$this->assertEmpty( Main::prepare_pending_campaign() );
	}

	/**
	 * Test automatic pause on error.
	 */
	public function test_automatic_pause_on_error() {
		// Filter wp_mail to simulate error.
		add_filter( 'wp_mail', '__return_false' );

		// Send the campaign.
		$this->campaign->save();

		// Check that the campaign is paused due to error.
		$this->assertNotEmpty( get_post_meta( $this->campaign->id, 'paused', true ) );

		// We should have a logged error message.
		$this->assertIsArray( get_post_meta( $this->campaign->id, '_bulk_email_last_error', true ) );

		// There should be no sending tasks scheduled.
		$this->assertEmpty( next_scheduled_noptin_background_action( Main::TASK_HOOK ) );
		$this->assertEmpty( next_scheduled_noptin_background_action( Main::HEALTH_CHECK_HOOK ) );

		// There shuold be a resume task scheduled.
		$this->assertNotEmpty( next_scheduled_noptin_background_action( 'noptin_resume_email_campaign' ) );
	}

	/**
	 * Test resume continues from last position.
	 */
	public function test_resume_continues_from_last_position() {
		$this->campaign->save();

		// Set progress before pause.
		update_post_meta( $this->campaign->id, 'subscriber_offset', 150 );
		noptin_pause_email_campaign( $this->campaign->id, 'Mid-campaign pause' );

		// Resume.
		noptin_resume_email_campaign( $this->campaign->id );

		// Verify offset is maintained.
		$offset = get_post_meta( $this->campaign->id, 'subscriber_offset', true );
		$this->assertEquals( 150, $offset );
	}

	/**
	 * Test pause reason logging.
	 */
	public function test_pause_reason_logging() {
		$this->campaign->save();

		$reasons = array(
			'Rate limit exceeded',
			'SMTP authentication failed',
			'Invalid sender email',
			'Unsupported email sender',
		);

		foreach ( $reasons as $index => $reason ) {
			update_noptin_option( 'per_hour', 2 + $index );
			noptin_pause_email_campaign( $this->campaign->id, $reason );
			$saved_reason = get_post_meta( $this->campaign->id, '_bulk_email_last_error', true );
			$this->assertEquals( $reason, $saved_reason['message'] ?? '' );

			// Resume for next test.
			noptin_resume_email_campaign( $this->campaign->id );
		}
	}

	/**
	 * Test pause during batch processing.
	 */
	public function test_pause_during_batch_processing() {
		$this->campaign->save();

		// Simulate processing first batch.
		update_post_meta( $this->campaign->id, 'subscriber_offset', 100 );
		update_post_meta( $this->campaign->id, 'subscriber_to_send', array( 101, 102, 103 ) );

		// Pause mid-batch.
		noptin_pause_email_campaign( $this->campaign->id, 'Mid-batch pause' );

		// Verify pause.
		$paused = get_post_meta( $this->campaign->id, 'paused', true );
		$this->assertEquals( 1, $paused );

		// Current batch should be preserved.
		$batch = get_post_meta( $this->campaign->id, 'subscriber_to_send', true );
		$this->assertNotEmpty( $batch );
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
