<?php

namespace Hizzle\Noptin\Tests\Bulk_Emails;

use Hizzle\Noptin\Bulk_Emails\Main;
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
				'email_type'     => 'visual',
				'template'       => 'default',
				'recipients'     => 'test@example.com',
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
		$this->bulk_emails = noptin()->bulk_emails();

		// Create mock sender
		$this->mock_sender = new class {
			public $sent_emails = [];
			public $recipients = ['test1@example.com', 'test2@example.com'];

			public function get_recipients($campaign) {
				return $this->recipients;
			}

			public function send($campaign, $recipient) {
				$this->sent_emails[] = $recipient;
				return true;
			}

			public function done_sending($campaign) {
				// Clean up after sending
			}
		};

		// Init email senders
		$this->bulk_emails->senders['mock'] = $this->mock_sender;

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

		// Send the campaign
		$this->bulk_emails->send_newsletter_campaign($this->campaign);

		// Verify that the campaign was queued for sending
		$this->assertTrue(
			has_action('wp_ajax_noptin_send_bulk_emails') ||
			has_action('wp_ajax_nopriv_noptin_send_bulk_emails')
		);
	}

	/**
	 * Test hourly email limit tracking.
	 */
	public function test_hourly_email_limit() {
		// Test initial count
		$this->assertEquals(0, Main::emails_sent_this_hour());

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
		$this->assertEquals(3, Main::emails_sent_this_hour());

		// Test exceeding limit
		add_filter('noptin_max_emails_per_period', function() {
			return 2;
		});

		$this->assertTrue(Main::exceeded_hourly_limit());
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

		// Mock error_get_last() using namespace function override
		$mock_error = [
			'type'    => E_ERROR,
			'message' => 'Test error message',
			'file'    => __FILE__,
			'line'    => __LINE__
		];

		// Create a namespace function override
		namespace_function_include('error_get_last', function() use ($mock_error) {
			return $mock_error;
		});

		$this->bulk_emails->handle_unexpected_shutdown();

		// Restore original error_get_last function
		namespace_function_restore('error_get_last');

		// Verify campaign was paused
		$this->assertEquals(
			$mock_error['message'],
			get_post_meta($this->campaign->id, 'paused_reason', true)
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
		delete_option('noptin_emails_sent_' . Main::current_hour());
		delete_transient($this->bulk_emails->cron_hook . '_process_lock');

		parent::tear_down();
	}
}
