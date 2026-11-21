<?php
/** Persistent bulk-send reservation regressions. No external transport is used. */
namespace Hizzle\Noptin\Tests\Bulk_Emails;

use Hizzle\Noptin\Emails\Bulk\Main;
use Hizzle\Noptin\Emails\Bulk\Reservation;
use Hizzle\Noptin\Emails\Bulk\Reservation_Exception;

require_once __DIR__ . '/base.php';

class Test_Bulk_Email_Reservations extends Noptin_Emails_Test_Case {
	public function set_up() {
		parent::set_up();
		$this->campaign->status = 'draft';
		$this->campaign->save();
	}

	public function tear_down() {
		delete_option( Reservation::key( $this->campaign->id ) );
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	private function age_claim() {
		$record = json_decode( Reservation::read( $this->campaign->id ), true );
		$record['started'] = time() - Reservation::REVIEW_AFTER - 1;
		update_option( Reservation::key( $this->campaign->id ), wp_json_encode( $record ), false );
		return Reservation::read( $this->campaign->id );
	}

	public function test_atomic_claim_blocks_second_worker() {
		$owner = Reservation::acquire( $this->campaign->id, 'send', 1 );
		try {
			Reservation::acquire( $this->campaign->id, 'send', 1 );
			$this->fail( 'A second worker acquired the active reservation.' );
		} catch ( Reservation_Exception $e ) {
			$this->assertTrue( $e->wait );
		}
		$owner->release();
		$this->assertNull( Reservation::read( $this->campaign->id ) );
	}

	public function test_old_claim_is_not_automatically_reclaimed() {
		Reservation::acquire( $this->campaign->id, 'send', 1 );
		$raw = $this->age_claim();
		try {
			Reservation::acquire( $this->campaign->id, 'send', 1 );
			$this->fail( 'An uncertain delivery was automatically reclaimed.' );
		} catch ( Reservation_Exception $e ) {
			$this->assertFalse( $e->wait );
		}
		$this->assertSame( $raw, Reservation::read( $this->campaign->id ) );
	}

	public function test_stale_owner_cannot_release_new_claim() {
		$old = Reservation::acquire( $this->campaign->id, 'send', 1 );
		delete_option( Reservation::key( $this->campaign->id ) );
		$new = Reservation::acquire( $this->campaign->id, 'send', 2 );
		$raw = Reservation::read( $this->campaign->id );
		try {
			$old->release();
			$this->fail( 'A stale owner removed a replacement reservation.' );
		} catch ( Reservation_Exception $e ) {
			$this->assertSame( $raw, Reservation::read( $this->campaign->id ) );
		}
		$new->release();
	}

	public function test_recovery_requires_permission_and_unchanged_old_claim() {
		remove_action( 'noptin_newsletter_campaign_published', array( Main::class, 'send_newsletter_campaign' ) );
		wp_update_post( array( 'ID' => $this->campaign->id, 'post_status' => 'publish' ) );
		add_action( 'noptin_newsletter_campaign_published', array( Main::class, 'send_newsletter_campaign' ) );
		Reservation::acquire( $this->campaign->id, 'send', 1 );
		$raw = $this->age_claim();
		$this->assertWPError( Reservation::recover( $this->campaign, $raw ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertWPError( Reservation::recover( $this->campaign, 'stale-token' ) );
		$this->assertSame( $raw, Reservation::read( $this->campaign->id ) );
		$actions = Reservation::actions( array(), $this->campaign );
		$this->assertCount( 1, $actions );
		$action = reset( $actions );
		$this->assertTrue( $action['destructive'] );
		$this->assertTrue( call_user_func( $action['callback'], $this->campaign ) );
		$this->assertNull( Reservation::read( $this->campaign->id ) );
		$this->assertNotEmpty( get_post_meta( $this->campaign->id, 'paused', true ) );
		$this->assertFalse( next_scheduled_noptin_background_action( 'noptin_resume_email_campaign', $this->campaign->id ) );
	}

	public function test_recovery_does_not_release_when_pause_fails() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		Reservation::acquire( $this->campaign->id, 'send', 1 );
		$raw = $this->age_claim();
		// Draft campaigns cannot be paused by the bulk sender.
		$this->assertWPError( Reservation::recover( $this->campaign, $raw ) );
		$this->assertSame( $raw, Reservation::read( $this->campaign->id ) );
	}

	public function test_recent_claim_cannot_be_recovered() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		Reservation::acquire( $this->campaign->id, 'send', 1 );
		$this->assertWPError( Reservation::recover( $this->campaign, Reservation::read( $this->campaign->id ) ) );
		$this->assertSame( array(), Reservation::actions( array(), $this->campaign ) );
	}

	public function test_new_claim_refreshes_cached_recipient_batch() {
		$key = 'contacts_to_send';
		update_post_meta( $this->campaign->id, $key, array( 1, 2 ) );
		get_post_meta( $this->campaign->id, $key, true );
		global $wpdb;
		$wpdb->update( $wpdb->postmeta, array( 'meta_value' => maybe_serialize( array( 2 ) ) ), array( 'post_id' => $this->campaign->id, 'meta_key' => $key ) );
		$claim = Reservation::acquire( $this->campaign->id, 'send', 2 );
		$this->assertSame( array( 2 ), get_post_meta( $this->campaign->id, $key, true ) );
		$claim->release();
	}
}
