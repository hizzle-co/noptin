<?php
namespace Hizzle\Noptin\Emails\Bulk;

defined( 'ABSPATH' ) || exit;

/** Persistent campaign claim: independent of the runner lease and DB session. */
class Reservation {

	const REVIEW_AFTER = 300;
	private $key;
	private $value;

	public static function key( $campaign_id ) {
		return 'noptin_bulk_reservation_' . (int) $campaign_id;
	}

	/** Read from the database, not another worker's cached option value. */
	public static function read( $campaign_id ) {
		global $wpdb;
		$value = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", self::key( $campaign_id ) ) );
		if ( $wpdb->last_error ) {
			throw new Reservation_Exception( 'Could not read the email delivery reservation. Sending is held.' );
		}
		return $value;
	}

	/** The unique option name makes claiming atomic across PHP workers. */
	public static function acquire( $campaign_id, $operation, $recipient = null ) {
		global $wpdb;
		$claim        = new self();
		$claim->key   = self::key( $campaign_id );
		$claim->value = wp_json_encode( array( 'token' => wp_generate_uuid4(), 'started' => time(), 'operation' => $operation, 'recipient' => $recipient ) );
		$inserted     = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", $claim->key, $claim->value ) );
		if ( 1 !== $inserted ) {
			$existing = json_decode( (string) self::read( $campaign_id ), true );
			if ( false === $inserted || ! is_array( $existing ) ) {
				throw new Reservation_Exception( 'Could not reserve the email queue. Sending is held; check database errors before resuming.' );
			}
			$wait = ! empty( $existing['started'] ) && time() - $existing['started'] < self::REVIEW_AFTER;
			throw new Reservation_Exception( 'An earlier email operation has not finished. Stop the earlier worker and check delivery records before releasing its reservation; retrying an unknown delivery may send a duplicate.', $wait );
		}
		// Queue metadata may have been cached before this worker acquired the claim.
		$claim->assert_owned();
		wp_cache_delete( $campaign_id, 'post_meta' );
		return $claim;
	}

	public function assert_owned() {
		global $wpdb;
		$current = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $this->key ) );
		if ( $wpdb->last_error || $current !== $this->value ) {
			throw new Reservation_Exception( 'Email reservation ownership could not be verified. Sending is held.' );
		}
	}

	public function release() {
		global $wpdb;
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $this->key, $this->value ) );
		wp_cache_delete( $this->key, 'options' );
		if ( 1 !== $deleted ) {
			throw new Reservation_Exception( 'Could not release the email delivery reservation. Sending is held for review.' );
		}
	}

	/** An explicit, permission-checked release; it never resumes the campaign. */
	public static function recover( $email, $expected ) {
		if ( ! $email->current_user_can_publish() ) {
			return new \WP_Error( 'rest_forbidden', 'You cannot recover this campaign.', array( 'status' => 403 ) );
		}
		$current = self::read( $email->id );
		$data    = json_decode( (string) $current, true );
		if ( ! is_array( $data ) || ! hash_equals( (string) $expected, (string) $current ) || time() - (int) ( $data['started'] ?? 0 ) < self::REVIEW_AFTER ) {
			return new \WP_Error( 'noptin_reservation_changed', 'The reservation is recent or changed. Refresh and review before releasing it.', array( 'status' => 409 ) );
		}
		noptin_pause_email_campaign( $email->id, 'Reservation released after review. The campaign remains paused. Resume only after checking whether the interrupted delivery was accepted.', 0 );
		delete_noptin_background_action( 'noptin_resume_email_campaign', $email->id );
		wp_cache_delete( $email->id, 'post_meta' );
		if ( ! get_post_meta( $email->id, 'paused', true ) ) {
			return new \WP_Error( 'noptin_pause_failed', 'Could not pause the campaign. Its reservation has been retained.', array( 'status' => 409 ) );
		}
		$claim        = new self();
		$claim->key   = self::key( $email->id );
		$claim->value = $current;
		try {
			$claim->release();
		} catch ( Reservation_Exception $e ) {
			return new \WP_Error( 'noptin_reservation_changed', $e->getMessage(), array( 'status' => 409 ) );
		}
		return true;
	}

	/** Reuse the existing campaign action UI and its confirmation/REST permissions. */
	public static function actions( $actions, $email ) {
		if ( ! $email || ! $email->current_user_can_publish() ) {
			return $actions;
		}
		try {
			$raw  = self::read( $email->id );
			$data = json_decode( (string) $raw, true );
		} catch ( Reservation_Exception $e ) {
			return $actions;
		}
		if ( is_array( $data ) && time() - (int) ( $data['started'] ?? 0 ) >= self::REVIEW_AFTER ) {
			$actions[ 'release-reservation-' . substr( hash( 'sha256', $raw ), 0, 16 ) ] = array(
				'label'               => __( 'Release interrupted send after checking delivery', 'newsletter-optin-box' ),
				'destructive'         => true,
				'permission_callback' => static function ( $campaign ) { return $campaign->current_user_can_publish(); },
				'callback'            => static function ( $campaign ) use ( $raw ) { return self::recover( $campaign, $raw ); },
			);
		}
		return $actions;
	}
}
