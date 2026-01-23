<?php
/**
 * Tests for bulk email batching functionality.
 *
 * @package Noptin
 */

namespace Hizzle\Noptin\Tests\Bulk_Emails;

use Hizzle\Noptin\Emails\Bulk\Main;

require_once __DIR__ . '/base.php';

/**
 * Test bulk email batching.
 */
class Test_Bulk_Email_Batching extends Noptin_Emails_Test_Case {

	/**
	 * Set up test environment.
	 */
	public function set_up() {
		parent::set_up();

		// Create test subscribers.
		$this->create_test_subscribers( 10 );
	}

	public function return_small_batch_size() {
		return 2;
	}

	public function return_large_batch_size() {
		return 10000;
	}

	/**
	 * Test batch tracking.
	 */
	public function test_batch_tracking() {
		// Filter the batch size to a small number for testing.
		add_filter( 'noptin_bulk_email_batch_size', array( $this, 'return_small_batch_size' ) );

		// Limit sending to 3 emails per period.
		// This way we can test multiple batches.
		update_noptin_option( 'per_hour', 3 );

		// Send the campaign.
		$this->campaign->save();

		// If we're here, we have already sent 3 emails.
		$this->assertEquals( 3, (int) get_post_meta( $this->campaign->id, '_noptin_sends', true ) );

		// The offset should be 4, ( we have processed 2 batches of 2 each, total 4).
		$this->assertEquals( 4, (int) get_post_meta( $this->campaign->id, 'subscriber_offset', true ) );

		// Only one subscriber should remain in the second batch.
		$remaining_subscribers = get_post_meta( $this->campaign->id, 'subscriber_to_send', true );
		$this->assertCount( 1, $remaining_subscribers );
		$this->assertCount( 1, Main::$senders['noptin']->get_recipients( $this->campaign ) );

		// Increase the limit to allow sending remaining emails.
		update_noptin_option( 'per_hour', 4 );

		// Send pending emails.
		Main::send_pending();

		// Only 1 more email should have been sent.
		$this->assertEquals( 4, (int) get_post_meta( $this->campaign->id, '_noptin_sends', true ) );

		// The new offset should still be 4.
		// We hit the sending limit before processing the next batch.
		$this->assertEquals( 4, (int) get_post_meta( $this->campaign->id, 'subscriber_offset', true ) );

		// No subscribers should remain in the batch.
		$remaining_subscribers = get_post_meta( $this->campaign->id, 'subscriber_to_send', true );
		$this->assertEmpty( $remaining_subscribers );

		// However, if we manually fetch recipients, it should return the next batch.
		$this->assertCount( 2, Main::$senders['noptin']->get_recipients( $this->campaign ) );

		// Increase the limit to allow sending remaining emails.
		update_noptin_option( 'per_hour', 20 );

		// Send pending emails.
		Main::send_pending();

		// All 10 emails should have been sent.
		$this->assertEquals( 10, (int) get_post_meta( $this->campaign->id, '_noptin_sends', true ) );

		// The offset should be empty.
		$this->assertEmpty( get_post_meta( $this->campaign->id, 'subscriber_offset', true ) );

		// No subscribers should remain in the batch.
		$remaining_subscribers = get_post_meta( $this->campaign->id, 'subscriber_to_send', true );
		$this->assertEmpty( $remaining_subscribers );

		// Remove the batch size filter.
		remove_filter( 'noptin_bulk_email_batch_size', array( $this, 'return_small_batch_size' ) );
	}

	/**
	 * Test campaign with negative offset.
	 */
	public function test_campaign_negative_offset() {
		// Set sending limits.
		update_noptin_option( 'per_hour', 1 );

		// Start sending the campaign.
		$this->campaign->save();

		// Should return 1 recipient
		// Noptin uses the per_hour option if it is less than the batch size.
		$recipients = Main::$senders['noptin']->get_recipients( $this->campaign );

		$this->assertCount( 1, $recipients );

		// Remove per_hour limit.
		update_noptin_option( 'per_hour', 0 );

		// Set negative offset.
		update_post_meta( $this->campaign->id, 'subscriber_offset', '-1' );

		// Set subscriber_to_send to an invalid value.
		// This should force re-fetch instead of failing.
		update_post_meta( $this->campaign->id, 'subscriber_to_send', 'not-an-array' );

		// Should return 10 recipients (we have reset the offset).
		$recipients = Main::$senders['noptin']->get_recipients( $this->campaign );

		$this->assertCount( 10, $recipients );

		// Remove cached recipients to force re-fetch.
		delete_post_meta( $this->campaign->id, 'subscriber_to_send' );
		delete_post_meta( $this->campaign->id, 'subscriber_offset' );

		// Set batch size to zero (should be prevented).
		add_filter( 'noptin_bulk_email_batch_size', '__return_zero' );

		// Should return 1 recipient (batch size should default to at least 1).
		$recipients = Main::$senders['noptin']->get_recipients( $this->campaign );

		$this->assertCount( 1, $recipients );

		// New offset should be 1.
		$offset = get_post_meta( $this->campaign->id, 'subscriber_offset', true );
		$this->assertEquals( 1, $offset );

		remove_filter( 'noptin_bulk_email_batch_size', '__return_zero' );

		// Remove cached recipients to force re-fetch.
		delete_post_meta( $this->campaign->id, 'subscriber_to_send' );

		// Set offset to 11, more than available subscribers.
		update_post_meta( $this->campaign->id, 'subscriber_offset', '11' );

		// Should return 0 recipients.
		$recipients = Main::$senders['noptin']->get_recipients( $this->campaign );
		$this->assertCount( 0, $recipients );

		// Subscriber offset should remain 11.
		$offset = get_post_meta( $this->campaign->id, 'subscriber_offset', true );
		$this->assertEquals( 11, $offset );

		// subscriber_to_send meta should be empty.
		$cached = get_post_meta( $this->campaign->id, 'subscriber_to_send', true );
		$this->assertEmpty( $cached );

		// Test large batch size.
		add_filter( 'noptin_bulk_email_batch_size', array( $this, 'return_large_batch_size' ) );

		// Set offset to 1.
		delete_post_meta( $this->campaign->id, 'subscriber_offset' );

		// Should return 10 recipients since we've reset the offset.
		$recipients = Main::$senders['noptin']->get_recipients( $this->campaign );
		$this->assertCount( 10, $recipients );

		remove_filter( 'noptin_bulk_email_batch_size', array( $this, 'return_large_batch_size' ) );
	}
}
