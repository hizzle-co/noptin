<?php

namespace Hizzle\Noptin\Tests\Bulk_Emails;

use Hizzle\Noptin\Emails\Bulk\Main;
use Hizzle\Noptin\Emails\Email;

/**
 * Test Main bulk email sender class.
 */
class Test_Main extends \WP_UnitTestCase {

	/**
	 * @var Main
	 */
	protected $bulk_emails;

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
		$this->bulk_emails = Main::instance();

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
		$this->bulk_emails->senders['mock'] = $this->mock_sender;

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
	 * Test singleton instance.
	 */
	public function test_instance() {
		$this->assertInstanceOf(Main::class, Main::instance());
		$this->assertSame(Main::instance(), Main::instance());
	}

	/**
	 * Test email sender registration.
	 */
	public function test_init_email_senders() {
		$this->assertArrayHasKey('mock', $this->bulk_emails->senders);
		$this->assertSame($this->mock_sender, $this->bulk_emails->senders['mock']);
	}

	/**
	 * Test has_sender method.
	 */
	public function test_has_sender() {
		$this->assertTrue($this->bulk_emails->has_sender('mock'));
		$this->assertFalse($this->bulk_emails->has_sender('nonexistent'));
		$this->assertEquals('mock', $this->campaign->get_sender());
	}

	/**
	 * Test sending newsletter campaign.
	 */
	public function test_send_newsletter_campaign() {

		// Check that the campaign can be sent
		$can_send = $this->campaign->can_send( true );
		$this->assertNotWPError( $can_send, is_wp_error( $can_send ) ? $can_send->get_error_message() : '' );

		// Send the campaign
		$this->bulk_emails->send_newsletter_campaign($this->campaign);

		// Verify that the campaign was queued for sending
		$this->assertNotEmpty(
			did_filter( 'noptin_send_bulk_emails_ajax_query_args' )
		);
	}

	/**
	 * Test hourly email limit tracking.
	 */
	public function test_hourly_email_limit() {
		// Test initial count
		noptin()->db()->delete_all( 'email_logs' );
		$this->assertEquals(0, noptin_emails_sent_this_period());

		// Use reflection to access protected process_task method
		$reflection = new \ReflectionClass($this->bulk_emails);
		$method = $reflection->getMethod('process_task');
		$method->setAccessible(true);

		// Set current campaign
		$campaign_property = $reflection->getProperty('current_campaign');
		$campaign_property->setAccessible(true);
		$campaign_property->setValue($this->bulk_emails, $this->campaign);

		// Simulate sending emails
		for ($i = 0; $i < 3; $i++) {
			$this->assertNotFalse($method->invoke($this->bulk_emails, 'test' . $i . '@example.com'));
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
		// Set current campaign
		$reflection = new \ReflectionClass($this->bulk_emails);
		$property = $reflection->getProperty('current_campaign');
		$property->setAccessible(true);
		$property->setValue($this->bulk_emails, $this->campaign);

		// Create mock error
		$mock_error = array(
			'type'    => E_ERROR,
			'message' => 'Test error message',
			'file'    => __FILE__,
			'line'    => __LINE__
		);

		// Call the method with our mock error
		$this->bulk_emails->handle_unexpected_shutdown($mock_error);

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
		// Set current campaign
		$reflection = new \ReflectionClass($this->bulk_emails);
		$property = $reflection->getProperty('current_campaign');
		$property->setAccessible(true);
		$property->setValue($this->bulk_emails, $this->campaign);

		// Run batch process
		$this->bulk_emails->run();

		// Verify processed tasks
		$this->assertGreaterThan(0, $this->bulk_emails->processed_tasks);
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
		delete_transient($this->bulk_emails->cron_hook . '_process_lock');

		parent::tear_down();
	}
}
