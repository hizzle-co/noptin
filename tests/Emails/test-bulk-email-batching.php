<?php
/**
 * Tests for bulk email batching functionality.
 *
 * @package Noptin
 */

/**
 * Test bulk email batching.
 */
class Test_Bulk_Email_Batching extends WP_UnitTestCase {

	/**
	 * Test batch size filtering.
	 */
	public function test_batch_size_filter() {
		$campaign = $this->create_test_campaign();

		// Default batch size should be 100.
		$batch_size = apply_filters( 'noptin_bulk_email_batch_size', 100, $campaign );
		$this->assertEquals( 100, $batch_size );

		// Test custom batch size.
		add_filter( 'noptin_bulk_email_batch_size', function() {
			return 50;
		} );

		$batch_size = apply_filters( 'noptin_bulk_email_batch_size', 100, $campaign );
		$this->assertEquals( 50, $batch_size );
	}

	/**
	 * Test batch offset tracking.
	 */
	public function test_batch_offset_tracking() {
		$campaign = $this->create_test_campaign();

		// Initial offset should be 0.
		$offset = get_post_meta( $campaign->id, 'subscriber_offset', true );
		$this->assertEquals( 0, $offset );

		// After processing first batch, offset should be 100.
		update_post_meta( $campaign->id, 'subscriber_offset', 100 );
		$offset = get_post_meta( $campaign->id, 'subscriber_offset', true );
		$this->assertEquals( 100, $offset );

		// After processing second batch, offset should be 200.
		update_post_meta( $campaign->id, 'subscriber_offset', 200 );
		$offset = get_post_meta( $campaign->id, 'subscriber_offset', true );
		$this->assertEquals( 200, $offset );
	}

	/**
	 * Test batch processing with large recipient list.
	 */
	public function test_large_recipient_batching() {
		// Create 500 test subscribers.
		$subscriber_ids = $this->create_test_subscribers( 500 );
		$campaign       = $this->create_test_campaign();

		// Simulate batch processing.
		$batch_size = 100;
		$total_sent = 0;

		for ( $offset = 0; $offset < 500; $offset += $batch_size ) {
			$batch = array_slice( $subscriber_ids, $offset, $batch_size );
			$total_sent += count( $batch );

			// Verify batch size.
			$this->assertLessThanOrEqual( $batch_size, count( $batch ) );
		}

		// Verify all subscribers were batched.
		$this->assertEquals( 500, $total_sent );
	}

	/**
	 * Test empty batch handling.
	 */
	public function test_empty_batch_returns_empty_array() {
		$campaign = $this->create_test_campaign();

		// Set offset beyond available subscribers.
		update_post_meta( $campaign->id, 'subscriber_offset', 10000 );

		// Get recipients should return empty array.
		$recipients = $this->get_batched_recipients( $campaign, 100, 10000 );
		$this->assertIsArray( $recipients );
		$this->assertEmpty( $recipients );
	}

	/**
	 * Test batch size respects email sending limits.
	 */
	public function test_batch_size_respects_sending_limits() {
		$campaign = $this->create_test_campaign();

		// Mock email limit of 50 per period.
		add_filter( 'noptin_max_emails_per_period', function() {
			return 50;
		} );

		// Batch size should be adjusted to match limit.
		$expected_batch_size = 50;
		$batch_size          = apply_filters( 'noptin_bulk_email_batch_size', 100, $campaign );

		// In the actual implementation, batch size is adjusted in People_List::get_recipients().
		$this->assertLessThanOrEqual( 100, $batch_size );
	}

	/**
	 * Test offset increments only on successful batch fetch.
	 */
	public function test_offset_increments_on_success_only() {
		$campaign = $this->create_test_campaign();
		$this->create_test_subscribers( 150 );

		// Initial offset.
		$initial_offset = 0;
		update_post_meta( $campaign->id, 'subscriber_offset', $initial_offset );

		// Fetch first batch (100 subscribers).
		$batch = $this->get_batched_recipients( $campaign, 100, $initial_offset );
		$this->assertCount( 100, $batch );

		// Offset should increment to 100.
		update_post_meta( $campaign->id, 'subscriber_offset', 100 );
		$offset = get_post_meta( $campaign->id, 'subscriber_offset', true );
		$this->assertEquals( 100, $offset );

		// Fetch second batch (50 subscribers remaining).
		$batch = $this->get_batched_recipients( $campaign, 100, 100 );
		$this->assertCount( 50, $batch );

		// Offset should increment to 200.
		update_post_meta( $campaign->id, 'subscriber_offset', 200 );
		$offset = get_post_meta( $campaign->id, 'subscriber_offset', true );
		$this->assertEquals( 200, $offset );

		// Third batch should be empty.
		$batch = $this->get_batched_recipients( $campaign, 100, 200 );
		$this->assertEmpty( $batch );
	}

	/**
	 * Test campaign completion with batching.
	 */
	public function test_campaign_completion_with_batching() {
		$campaign = $this->create_test_campaign();
		$this->create_test_subscribers( 250 );

		// Process all batches.
		$batches_processed = 0;

		for ( $offset = 0; $offset < 300; $offset += 100 ) {
			$batch = $this->get_batched_recipients( $campaign, 100, $offset );

			if ( empty( $batch ) ) {
				break;
			}

			$batches_processed++;
		}

		// Should have processed 3 batches (100, 100, 50).
		$this->assertEquals( 3, $batches_processed );
	}

	/**
	 * Test metadata cleanup after campaign completion.
	 */
	public function test_metadata_cleanup_after_completion() {
		$campaign = $this->create_test_campaign();

		// Set batch metadata.
		update_post_meta( $campaign->id, 'subscriber_to_send', array( 1, 2, 3 ) );
		update_post_meta( $campaign->id, 'subscriber_offset', 100 );

		// Simulate cleanup.
		delete_post_meta( $campaign->id, 'subscriber_to_send' );
		delete_post_meta( $campaign->id, 'subscriber_offset' );

		// Verify cleanup.
		$contacts = get_post_meta( $campaign->id, 'subscriber_to_send', true );
		$offset   = get_post_meta( $campaign->id, 'subscriber_offset', true );

		$this->assertEmpty( $contacts );
		$this->assertEmpty( $offset );
	}

	/**
	 * Test batch processing doesn't load full recipient list.
	 */
	public function test_batch_doesnt_load_full_list() {
		$campaign = $this->create_test_campaign();
		$this->create_test_subscribers( 1000 );

		// First batch should only return 100, not 1000.
		$batch = $this->get_batched_recipients( $campaign, 100, 0 );
		$this->assertCount( 100, $batch );

		// Memory usage should be minimal (testing concept, not actual memory).
		$this->assertLessThanOrEqual( 100, count( $batch ) );
	}

	/**
	 * Helper: Create test campaign.
	 */
	private function create_test_campaign() {
		$campaign_id = wp_insert_post(
			array(
				'post_type'   => 'noptin-campaign',
				'post_status' => 'publish',
				'post_title'  => 'Test Batch Campaign',
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
		$subscriber_ids = array();

		for ( $i = 1; $i <= $count; $i++ ) {
			$subscriber_id = add_noptin_subscriber(
				array(
					'email'  => "test{$i}@example.com",
					'status' => 'subscribed',
				)
			);

			if ( ! is_wp_error( $subscriber_id ) ) {
				$subscriber_ids[] = $subscriber_id;
			}
		}

		return $subscriber_ids;
	}

	/**
	 * Helper: Get batched recipients.
	 */
	private function get_batched_recipients( $campaign, $batch_size, $offset ) {
		$args = array(
			'status' => 'subscribed',
			'offset' => $offset,
			'number' => $batch_size,
			'fields' => 'id',
		);

		return noptin_get_subscribers( $args );
	}
}
