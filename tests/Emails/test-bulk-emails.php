<?php

namespace Hizzle\Noptin\Tests\Bulk_Emails;

use Hizzle\Noptin\Emails\Bulk\Main;
use Hizzle\Noptin\Emails\Email;

/**
 * Test Main bulk email sender class.
 */
class Test_Main extends \WP_UnitTestCase {

	/**
	 * @var Email Test campaign
	 */
	protected $campaign;

	/**
	 * @var \Hizzle\Noptin\Bulk_Emails\Email_Sender Mock sender for testing
	 */
	protected $mock_sender;

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
				'email_sender'   => 'mock',
				'email_type'     => 'normal',
				'template'       => 'default',
				'recipients'     => 'test@example.com',
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

		// Create mock sender
		$this->mock_sender = new class {
			public $sent_emails = [];
			public $recipients = ['test1@example.com', 'test2@example.com'];

			public function get_recipients($campaign) {
				return $this->recipients;
			}

			public function send($campaign, $recipient) {
				$this->sent_emails[] = $recipient;
				$campaign->send_to( $recipient );

				return true;
			}

			public function done_sending($campaign) {
				// Clean up after sending
			}
		};

		// Init email senders
		Main::$senders['mock'] = $this->mock_sender;

		add_filter(
			'noptin_email_senders',
			function($senders) {
				$senders['mock'] = array(
					'label' => 'Mock Sender',
					'description' => 'Mock sender for testing',
					'image' => array(
						'icon' => 'email',
						'url' => 'https://example.com/mock-sender-image.png',
					),
					'is_installed' => true,
					'is_local'     => true,
				);
				return $senders;
			}
		);

		// Create a test campaign
		$this->campaign = $this->create_test_campaign();
		$this->campaign->save();
	}

	/**
	 * Test email sender registration.
	 */
	public function test_init_email_senders() {
		$this->assertArrayHasKey('mock', Main::$senders);
		$this->assertSame($this->mock_sender, Main::$senders['mock']);
	}

	/**
	 * Test has_sender method.
	 */
	public function test_has_sender() {
		$this->assertEquals($this->mock_sender, Main::has_sender('mock'));
		$this->assertFalse(Main::has_sender('nonexistent'));
		$this->assertEquals('mock', $this->campaign->get_sender());
	}

	/**
	 * Test sending newsletter campaign.
	 */
	public function test_send_newsletter_campaign() {

		// Check that the campaign can be sent
		$can_send = $this->campaign->can_send( true );
		$this->assertNotWPError( $can_send, is_wp_error( $can_send ) ? $can_send->get_error_message() : '' );

		// Check if the sender is available
		$this->assertEquals($this->mock_sender, Main::has_sender('mock'));

		// Check if we have a pending campaing.
		$pending_campaign = Main::prepare_pending_campaign();
		$error            = get_post_meta( $this->campaign->id, '_bulk_email_last_error', true );

		$this->assertIsObject( $pending_campaign, is_array( $error ) ? $error['message'] : '' );

		// Send the campaign
		Main::send_newsletter_campaign($this->campaign);

		// Verify that the sending task was scheduled
		$this->assertIsNumeric(
			next_scheduled_noptin_background_action( Main::TASK_HOOK ),
			'Sending task should be scheduled'
		);

		// Verify that the health check task was scheduled
		$this->assertIsNumeric(
			next_scheduled_noptin_background_action( Main::HEALTH_CHECK_HOOK ),
			'Health check task should be scheduled'
		);

		// Verify that the campaign was queued for sending
		$this->assertNotEmpty(
			get_post_meta( $this->campaign->id, '_noptin_last_activity', true )
		);
	}

	/**
	 * Test hourly email limit tracking.
	 */
	public function test_hourly_email_limit() {
		// Test initial count
		noptin()->db()->delete_all( 'email_logs' );
		$this->assertEquals(0, noptin_emails_sent_this_period());

		// Simulate sending emails by calling the static send_campaign method via reflection
		$reflection = new \ReflectionClass('Hizzle\Noptin\Emails\Bulk\Main');
		$method = $reflection->getMethod('send_campaign');
		$method->setAccessible(true);

		// Send a few test emails
		for ($i = 0; $i < 3; $i++) {
			$recipient = 'test' . $i . '@example.com';
			$result = $method->invoke(null, $recipient, $this->campaign);
			$this->assertNotFalse($result, "Failed to send email to {$recipient}");
		}

		// Check updated count
		$this->assertEquals(3, noptin_emails_sent_this_period());

		// Test exceeding limit
		add_filter('noptin_max_emails_per_period', function() {
			return 2;
		});

		$this->assertTrue(noptin_email_sending_limit_reached());
	}

	/**
	 * Test handling unexpected shutdown.
	 */
	public function test_handle_unexpected_shutdown() {
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
	public function test_batch_limits() {
		// Clear any existing locks
		delete_option(Main::LOCK_KEY);

		// Run batch process
		Main::run();

		// Verify lock was released after processing
		$lock = get_option(Main::LOCK_KEY);
		$this->assertFalse($lock, 'Lock should be released after processing');

		// Verify that the campaign sending was attempted
		$last_activity = get_post_meta($this->campaign->id, '_noptin_last_activity', true);
		$this->assertNotEmpty($last_activity, 'Campaign status should be set');
	}

	/**
	 * Clean up after tests.
	 */
	public function tear_down() {
		// Delete test campaign
		if ($this->campaign && $this->campaign->exists()) {
			$this->campaign->delete();
		}

		// Clean up any options or transients
		delete_transient(Main::LOCK_KEY);

		parent::tear_down();
	}
}
