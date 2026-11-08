<?php
/**
 * Regression coverage for transport failures in persisted recipient batches.
 *
 * @package Noptin
 */

namespace Hizzle\Noptin\Tests\Bulk_Emails;

use Hizzle\Noptin\Emails\Bulk\Main;
use Hizzle\Noptin\Emails\Email;

require_once __DIR__ . '/base.php';

class Test_Bulk_Email_Retry extends Noptin_Emails_Test_Case {

	private $subscriber_ids;
	private $attempts = array();
	private $failures_remaining = 0;
	private $failure_type = 'false';

	public function set_up() {
		parent::set_up();
		$this->attempts           = array();
		$this->failures_remaining = 0;
		$this->failure_type       = 'false';
		$this->subscriber_ids     = $this->create_test_subscribers( 3 );
		$this->campaign->options['manual_recipients_ids'] = $this->subscriber_ids;

		// Drive each batch explicitly; never use a real email transport.
		remove_action( 'noptin_newsletter_campaign_published', array( Main::class, 'send_newsletter_campaign' ) );
		remove_action( 'noptin_email_campaign_resumed', array( Main::class, 'send_newsletter_campaign' ), 1000 );
		add_filter( 'noptin_bulk_email_dispatch_next', '__return_false' );
		add_filter( 'noptin_email_sending_function', array( $this, 'transport' ), 10000 );
	}

	public function tear_down() {
		remove_filter( 'noptin_email_sending_function', array( $this, 'transport' ), 10000 );
		remove_filter( 'noptin_bulk_email_dispatch_next', '__return_false' );
		remove_filter( 'noptin_can_email_recipient_for_bulk_campaign', array( $this, 'skip_second' ) );
		parent::tear_down();
		add_action( 'noptin_newsletter_campaign_published', array( Main::class, 'send_newsletter_campaign' ) );
		add_action( 'noptin_email_campaign_resumed', array( Main::class, 'send_newsletter_campaign' ), 1000 );
	}

	public function transport() {
		return array( $this, 'capture' );
	}

	public function capture( $recipients ) {
		$email = reset( $recipients );
		$this->attempts[] = $email;
		if ( 'test2@example.com' === $email && $this->failures_remaining > 0 ) {
			--$this->failures_remaining;
			if ( 'exception' === $this->failure_type ) {
				throw new \RuntimeException( 'Simulated transport failure' );
			}
			if ( 'error' === $this->failure_type ) {
				throw new \Error( 'Simulated transport error' );
			}
			return false;
		}
		return true;
	}

	private function assert_failed_recipient_pending() {
		$this->assertNotEmpty( get_post_meta( $this->campaign->id, 'paused', true ) );
		$this->assertEmpty( get_post_meta( $this->campaign->id, 'completed', true ) );
		$this->assertContains(
			(string) $this->subscriber_ids[1],
			array_map( 'strval', get_post_meta( $this->campaign->id, 'contacts_to_send', true ) )
		);
		$this->assertFalse( noptin_email_campaign_sent_to( $this->campaign->id, 'test2@example.com' ) );
		$this->assertEquals( 1, (int) get_post_meta( $this->campaign->id, '_noptin_sends', true ) );
	}

	private function resume() {
		// Reload the persisted campaign instead of retaining the old instance.
		$this->campaign = new Email( $this->campaign->id );
		noptin_resume_email_campaign( $this->campaign->id );
		Main::run();
	}

	private function assert_delivered_once() {
		$this->assertEquals( 1, (int) get_post_meta( $this->campaign->id, 'completed', true ) );
		$this->assertEquals( 3, (int) get_post_meta( $this->campaign->id, '_noptin_sends', true ) );
		$counts = array_count_values( $this->attempts );
		$this->assertEquals( 1, $counts['test1@example.com'] );
		$this->assertEquals( 1, $counts['test3@example.com'] );
		$this->assertTrue( noptin_email_campaign_sent_to( $this->campaign->id, 'test2@example.com' ) );
	}

	public function test_false_result_retries_without_duplicating_successful_recipients() {
		$this->failures_remaining = 1;
		$this->campaign->save();
		Main::run();
		$this->assert_failed_recipient_pending();
		$this->resume();
		$this->assert_delivered_once();
		$this->assertEquals( 2, array_count_values( $this->attempts )['test2@example.com'] );
	}

	public function test_repeated_failure_remains_pending_until_success() {
		$this->failures_remaining = 2;
		$this->campaign->save();
		Main::run();
		$this->assert_failed_recipient_pending();
		$this->resume();
		$this->assert_failed_recipient_pending();
		$this->resume();
		$this->assert_delivered_once();
		$this->assertEquals( 3, array_count_values( $this->attempts )['test2@example.com'] );
	}

	public function test_transport_exception_remains_retryable() {
		$this->failure_type       = 'exception';
		$this->failures_remaining = 1;
		$this->campaign->save();
		Main::run();
		$this->assert_failed_recipient_pending();
		$this->resume();
		$this->assert_delivered_once();
	}

	public function test_uncaught_transport_error_keeps_persisted_recipient() {
		$this->failure_type       = 'error';
		$this->failures_remaining = 1;
		$this->campaign->save();
		Main::run();
		$this->assert_failed_recipient_pending();
		$this->resume();
		$this->assert_delivered_once();
	}

	public function skip_second( $allowed, $email ) {
		return $allowed && 'test2@example.com' !== $email;
	}

	public function test_ineligible_recipient_advances_batch() {
		add_filter( 'noptin_can_email_recipient_for_bulk_campaign', array( $this, 'skip_second' ), 10, 2 );
		$this->campaign->save();
		Main::run();
		$this->assertSame( array( 'test1@example.com', 'test3@example.com' ), $this->attempts );
		$this->assertEquals( 1, (int) get_post_meta( $this->campaign->id, 'completed', true ) );
	}

	public function test_unsubscribe_after_selection_advances_batch() {
		$this->campaign->save();
		Main::$senders['noptin']->get_recipients( $this->campaign );
		unsubscribe_noptin_subscriber( $this->subscriber_ids[1] );
		Main::run();
		$this->assertSame( array( 'test1@example.com', 'test3@example.com' ), $this->attempts );
		$this->assertEquals( 1, (int) get_post_meta( $this->campaign->id, 'completed', true ) );
	}

	public function test_unsubscribed_failed_recipient_is_not_retried() {
		$this->failures_remaining = 1;
		$this->campaign->save();
		Main::run();
		$this->assert_failed_recipient_pending();
		unsubscribe_noptin_subscriber( $this->subscriber_ids[1] );
		$this->resume();
		$this->assertSame( array( 'test1@example.com', 'test2@example.com', 'test3@example.com' ), $this->attempts );
		$this->assertEquals( 2, (int) get_post_meta( $this->campaign->id, '_noptin_sends', true ) );
		$this->assertEquals( 1, (int) get_post_meta( $this->campaign->id, 'completed', true ) );
	}
}
