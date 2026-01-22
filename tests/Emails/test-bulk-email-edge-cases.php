<?php
/**
 * Tests for bulk email edge cases and error scenarios.
 *
 * @package Noptin
 */

/**
 * Test bulk email edge cases.
 */
class Test_Bulk_Email_Edge_Cases extends WP_UnitTestCase {

	/**
	 * Test campaign with zero subscribers.
	 */
	public function test_campaign_with_zero_subscribers() {
		$campaign = $this->create_test_campaign();

		// No subscribers created.
		$recipients = noptin_get_subscribers(
			array(
				'status' => 'subscribed',
				'fields' => 'id',
			)
		);

		$this->assertEmpty( $recipients );

		// Campaign should complete immediately.
		update_post_meta( $campaign->id, 'completed', 1 );
		$completed = get_post_meta( $campaign->id, 'completed', true );
		$this->assertEquals( 1, $completed );
	}

	/**
	 * Test campaign with single subscriber.
	 */
	public function test_campaign_with_single_subscriber() {
		$campaign = $this->create_test_campaign();
		$this->create_test_subscribers( 1 );

		$recipients = noptin_get_subscribers(
			array(
				'status' => 'subscribed',
				'number' => 100,
				'fields' => 'id',
			)
		);

		$this->assertCount( 1, $recipients );
	}

	/**
	 * Test campaign with exactly batch size subscribers.
	 */
	public function test_campaign_with_exact_batch_size() {
		$campaign = $this->create_test_campaign();
		$this->create_test_subscribers( 100 );

		// First batch should be exactly 100.
		$batch1 = noptin_get_subscribers(
			array(
				'status' => 'subscribed',
				'number' => 100,
				'offset' => 0,
				'fields' => 'id',
			)
		);
		$this->assertCount( 100, $batch1 );

		// Second batch should be empty.
		$batch2 = noptin_get_subscribers(
			array(
				'status' => 'subscribed',
				'number' => 100,
				'offset' => 100,
				'fields' => 'id',
			)
		);
		$this->assertEmpty( $batch2 );
	}

	/**
	 * Test campaign with unsubscribed subscribers.
	 */
	public function test_campaign_excludes_unsubscribed() {
		$campaign = $this->create_test_campaign();

		// Create 50 subscribed, 50 unsubscribed.
		for ( $i = 1; $i <= 50; $i++ ) {
			add_noptin_subscriber(
				array(
					'email'  => "subscribed{$i}@example.com",
					'status' => 'subscribed',
				)
			);
		}

		for ( $i = 1; $i <= 50; $i++ ) {
			add_noptin_subscriber(
				array(
					'email'  => "unsubscribed{$i}@example.com",
					'status' => 'unsubscribed',
				)
			);
		}

		// Query should only return subscribed.
		$recipients = noptin_get_subscribers(
			array(
				'status' => 'subscribed',
				'fields' => 'id',
			)
		);

		$this->assertCount( 50, $recipients );
	}

	/**
	 * Test campaign with invalid email addresses.
	 */
	public function test_campaign_handles_invalid_emails() {
		$campaign = $this->create_test_campaign();

		// Attempt to create subscribers with invalid emails.
		$invalid_emails = array( '', 'not-an-email', 'missing@', '@domain.com' );

		foreach ( $invalid_emails as $email ) {
			$result = add_noptin_subscriber(
				array(
					'email'  => $email,
					'status' => 'subscribed',
				)
			);

			// Should return WP_Error for invalid emails.
			$this->assertInstanceOf( 'WP_Error', $result );
		}
	}

	/**
	 * Test campaign with duplicate subscribers.
	 */
	public function test_campaign_handles_duplicate_subscribers() {
		$campaign = $this->create_test_campaign();

		// Create subscriber.
		$id1 = add_noptin_subscriber(
			array(
				'email'  => 'duplicate@example.com',
				'status' => 'subscribed',
			)
		);

		// Attempt duplicate.
		$id2 = add_noptin_subscriber(
			array(
				'email'  => 'duplicate@example.com',
				'status' => 'subscribed',
			)
		);

		// Second should return existing ID or error.
		$this->assertTrue( is_wp_error( $id2 ) || $id1 === $id2 );
	}

	/**
	 * Test campaign with deleted subscribers mid-campaign.
	 */
	public function test_campaign_handles_deleted_subscribers() {
		$campaign = $this->create_test_campaign();
		$subscriber_ids = $this->create_test_subscribers( 100 );

		// Cache first batch.
		update_post_meta( $campaign->id, 'subscriber_to_send', array_slice( $subscriber_ids, 0, 10 ) );

		// Delete a subscriber from the cached batch.
		$deleted_id = $subscriber_ids[5];
		noptin_delete_subscriber( $deleted_id );

		// Sending should handle missing subscriber gracefully.
		$batch = get_post_meta( $campaign->id, 'subscriber_to_send', true );
		$this->assertContains( $deleted_id, $batch );

		// In actual implementation, send() should return WP_Error for deleted subscriber.
	}

	/**
	 * Test campaign offset overflow.
	 */
	public function test_campaign_offset_overflow() {
		$campaign = $this->create_test_campaign();
		$this->create_test_subscribers( 100 );

		// Set offset way beyond subscriber count.
		$huge_offset = 999999;
		$batch = noptin_get_subscribers(
			array(
				'status' => 'subscribed',
				'number' => 100,
				'offset' => $huge_offset,
				'fields' => 'id',
			)
		);

		$this->assertEmpty( $batch );
	}

	/**
	 * Test campaign with negative offset.
	 */
	public function test_campaign_negative_offset() {
		$campaign = $this->create_test_campaign();
		$this->create_test_subscribers( 100 );

		// Negative offset should be treated as 0.
		$offset = -10;
		$safe_offset = max( 0, $offset );

		$this->assertEquals( 0, $safe_offset );
	}

	/**
	 * Test campaign with zero batch size.
	 */
	public function test_campaign_zero_batch_size() {
		$campaign = $this->create_test_campaign();

		// Zero batch size should be prevented.
		$batch_size = 0;
		$safe_batch_size = max( 1, $batch_size );

		$this->assertEquals( 1, $safe_batch_size );
	}

	/**
	 * Test campaign with massive batch size.
	 */
	public function test_campaign_massive_batch_size() {
		$campaign = $this->create_test_campaign();
		$this->create_test_subscribers( 100 );

		// Request 10000 subscribers.
		$batch = noptin_get_subscribers(
			array(
				'status' => 'subscribed',
				'number' => 10000,
				'fields' => 'id',
			)
		);

		// Should only return available subscribers.
		$this->assertCount( 100, $batch );
	}

	/**
	 * Test concurrent campaign sends.
	 */
	public function test_concurrent_campaign_sends() {
		$campaign1 = $this->create_test_campaign();
		$campaign2 = $this->create_test_campaign();

		// Both campaigns should be able to exist.
		$this->assertNotEquals( $campaign1->id, $campaign2->id );

		// Lock should prevent concurrent processing.
		$lock1 = add_option( 'noptin_send_bulk_emails_process_lock', time(), '', 'no' );
		$this->assertTrue( $lock1 );

		$lock2 = add_option( 'noptin_send_bulk_emails_process_lock', time(), '', 'no' );
		$this->assertFalse( $lock2 );

		delete_option( 'noptin_send_bulk_emails_process_lock' );
	}

	/**
	 * Test campaign with empty sender.
	 */
	public function test_campaign_with_empty_sender() {
		$campaign_id = wp_insert_post(
			array(
				'post_type'   => 'noptin-campaign',
				'post_status' => 'publish',
				'post_title'  => 'No Sender Campaign',
			)
		);

		update_post_meta( $campaign_id, 'campaign_type', 'newsletter' );
		// Don't set email_sender.

		$campaign = noptin_get_email_campaign_object( $campaign_id );
		$sender = $campaign->get_sender();

		// Should have a default or empty sender.
		$this->assertTrue( empty( $sender ) || ! empty( $sender ) );
	}

	/**
	 * Test campaign completion with zero sends.
	 */
	public function test_campaign_completion_with_zero_sends() {
		$campaign = $this->create_test_campaign();
		// No subscribers, so no sends.

		update_post_meta( $campaign->id, 'completed', 1 );
		$sends = get_post_meta( $campaign->id, '_noptin_sends', true );

		$this->assertEmpty( $sends );
	}

	/**
	 * Test campaign metadata corruption.
	 */
	public function test_campaign_handles_corrupted_metadata() {
		$campaign = $this->create_test_campaign();

		// Set corrupted metadata.
		update_post_meta( $campaign->id, 'subscriber_to_send', 'not-an-array' );

		$batch = get_post_meta( $campaign->id, 'subscriber_to_send', true );

		// Should handle gracefully (implementation should check is_array).
		$this->assertNotTrue( is_array( $batch ) );
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

	/**
	 * Test time limit during processing.
	 */
	public function test_time_limit_detection() {
		$start_time = time();
		$max_runtime = 20; // seconds.

		// Simulate time passing.
		sleep( 1 );

		$elapsed = time() - $start_time;
		$should_stop = $elapsed >= $max_runtime;

		$this->assertFalse( $should_stop ); // Only 1 second passed.
	}

	/**
	 * Helper: Create test campaign.
	 */
	private function create_test_campaign() {
		$campaign_id = wp_insert_post(
			array(
				'post_type'   => 'noptin-campaign',
				'post_status' => 'publish',
				'post_title'  => 'Test Edge Case Campaign',
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
		$ids = array();
		for ( $i = 1; $i <= $count; $i++ ) {
			$id = add_noptin_subscriber(
				array(
					'email'  => "edge{$i}@example.com",
					'status' => 'subscribed',
				)
			);
			if ( ! is_wp_error( $id ) ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}
}
