<?php
/**
 * Tests for bulk email edge cases and error scenarios.
 *
 * @package Noptin
 */

namespace Hizzle\Noptin\Tests\Bulk_Emails;

use Hizzle\Noptin\Emails\Bulk\Main;
use Hizzle\Noptin\Emails\Email;

require_once __DIR__ . '/base.php';

/**
 * Test bulk email edge cases.
 */
class Test_Bulk_Email_Edge_Cases extends Noptin_Emails_Test_Case {

	/**
	 * Test campaign with zero subscribers.
	 */
	public function test_campaign_with_zero_subscribers() {
		// No subscribers created.
		$recipients = noptin_get_subscribers(
			array(
				'status' => 'subscribed',
				'fields' => 'id',
			)
		);

		$this->assertEmpty( $recipients );

		// Send the campaign (should handle zero recipients).
		$this->campaign->save();

		// Campaign should complete immediately.
		$this->assertEquals( 1, get_post_meta( $this->campaign->id, 'completed', true ) );

		// Check that no sends were recorded.
		$this->assertEmpty( get_post_meta( $this->campaign->id, '_noptin_sends', true ) );
	}

	/**
	 * Test campaign with single subscriber.
	 */
	public function test_campaign_with_single_subscriber() {
		$this->create_test_subscribers( 1 );

		// No subscribers created.
		$recipients = noptin_get_subscribers(
			array(
				'status' => 'subscribed',
				'fields' => 'id',
			)
		);

		$this->assertCount( 1, $recipients );

		// Send the campaign.
		$this->campaign->save();

		// Check that one send was recorded.
		$this->assertEquals( 1, (int) get_post_meta( $this->campaign->id, '_noptin_sends', true ) );
	}

	/**
	 * Test campaign with exactly batch size subscribers.
	 */
	public function test_campaign_with_exact_batch_size() {
		$this->create_test_subscribers( 10 );

		/** @var \Hizzle\Noptin\Subscribers\Records $collection */
		$collection = \Hizzle\Noptin\Objects\Store::get( 'subscriber' );

		// First batch should be exactly 10.
		$batch1 = $collection->get_batched_newsletter_recipients(
			array(),
			$this->campaign,
			10,
			0
		);

		$this->assertCount( 10, $batch1 );

		// Second batch should be empty.
		$batch2 = $collection->get_batched_newsletter_recipients(
			array( 'status' => 'subscribed' ),
			$this->campaign,
			10,
			10
		);

		$this->assertEmpty( $batch2 );
	}

	/**
	 * Test campaign with unsubscribed subscribers.
	 */
	public function test_campaign_excludes_unsubscribed() {

		// Create 5 subscribed, 5 unsubscribed.
		for ( $i = 1; $i <= 10; $i++ ) {
			add_noptin_subscriber(
				array(
					'email'  => "subscribed{$i}@example.com",
					'status' => $i < 6 ? 'subscribed' : 'unsubscribed',
				)
			);
		}

		/** @var \Hizzle\Noptin\Subscribers\Records $collection */
		$collection = \Hizzle\Noptin\Objects\Store::get( 'subscriber' );

		// Query should only return subscribed.
		$recipients = $collection->get_batched_newsletter_recipients(
			array(),
			$this->campaign,
			10,
			0
		);

		$this->assertCount( 5, $recipients );
	}

	/**
	 * Test campaign with deleted subscribers mid-campaign.
	 */
	public function test_campaign_handles_deleted_subscribers() {
		// Set sending limits.
		update_noptin_option( 'per_hour', 1 );

		// Create 5 subscribers.
		$subscriber_ids = $this->create_test_subscribers( 5 );

		// Send the campaign.
		$this->campaign->save();

		// We should have sent email to 1 subscriber.
		$this->assertEquals( 1, (int) get_post_meta( $this->campaign->id, '_noptin_sends', true ) );

		// Check that the current batch contains subscriber[2].
		$batch = get_post_meta( $this->campaign->id, 'subscriber_to_send', true );
		$this->assertContains( $subscriber_ids[2], $batch );

		// Delete a subscriber mid-campaign.
		delete_noptin_subscriber( $subscriber_ids[2] );

		// Increase sending limit to allow next send.
		update_noptin_option( 'per_hour', 100 );

		// Send pending emails.
		Main::send_pending();

		// We should have sent emails to 4 subscribers.
		$this->assertEquals( 4, (int) get_post_meta( $this->campaign->id, '_noptin_sends', true ) );
	}

	/**
	 * Test campaign with empty sender.
	 */
	public function test_campaign_with_invalid_sender() {
		// Set sender to empty.
		$this->campaign->options['email_sender'] = '';

		// Should default to 'noptin' if no sender is set.
		$this->assertEquals( 'noptin', $this->campaign->get_sender() );

		// Set sender to invalid.
		$this->campaign->options['email_sender'] = 'invalid_sender';
		$this->assertEquals( 'invalid_sender', $this->campaign->get_sender() );

		// Sending should fail gracefully.
		$this->campaign->save();

		$this->assertWPError( $this->campaign->can_send( true ) );
	}

	/**
	 * Test campaign completion with zero sends.
	 */
	public function test_campaign_completion_with_zero_sends() {
		$this->campaign->save();

		// No subscribers, so no sends.
		$this->assertEmpty( get_post_meta( $this->campaign->id, '_noptin_sends', true ) );
		$this->assertNotEmpty( get_post_meta( $this->campaign->id, 'completed', true ) );
	}

	/**
	 * Test memory limit during processing.
	 */
	public function test_memory_limit_detection() {
		// Mock memory exceeded.
		add_filter( 'noptin_memory_exceeded', '__return_true' );

		$exceeded = noptin_memory_exceeded();
		$this->assertTrue( $exceeded );

		remove_filter( 'noptin_memory_exceeded', '__return_true' );
	}
}
