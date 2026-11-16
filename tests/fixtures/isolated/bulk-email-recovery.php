<?php
/** Isolated checks of the real bulk sender; no database, scheduling, or delivery. */
namespace Hizzle\Noptin\Emails {
	/** In-memory fixture used only by this isolated regression. */
	class Main {
		/** Fixture implementation for the isolated regression. */
		public static function get_phpmailer_last_error() { return 'Fixture failure'; }
	}
	/** In-memory fixture used only by this isolated regression. */
	class Email {
		public $id;
		public $type = 'newsletter';
		public $name = 'Fixture';
		/** Fixture implementation for the isolated regression. */
		public function __construct( $id ) { $this->id = $id; }
		/** Fixture implementation for the isolated regression. */
		public static function from( $value ) { return $value; }
		/** Fixture implementation for the isolated regression. */
		public function can_send() { return true; }
		/** Fixture implementation for the isolated regression. */
		public function is_mass_mail() { return true; }
		/** Fixture implementation for the isolated regression. */
		public function get_sender() { return 'fixture'; }
		/** Fixture implementation for the isolated regression. */
		public function current_user_can_publish() { return true; }
	}
}
namespace Hizzle\Noptin\Tasks {
	/** In-memory fixture used only by this isolated regression. */
	class Main {
		/** Fixture implementation for the isolated regression. */
		public static function get_next_scheduled_task( $hook ) {
			return $GLOBALS['healthy_tasks'] ? new class {
				/** Fixture implementation for the isolated regression. */
				public function get_date_scheduled() { return new \DateTime( '+5 minutes' ); }
			} : false;
		}
	}
}
namespace {
	define( 'ABSPATH', __DIR__ );
	define( 'MINUTE_IN_SECONDS', 60 );
	define( 'HOUR_IN_SECONDS', 3600 );
	$GLOBALS['meta'] = array();
	$GLOBALS['campaigns'] = array( 1 );
	$GLOBALS['healthy_tasks'] = true;
	$GLOBALS['locked'] = false;
	$GLOBALS['limited'] = false;
	/** Fixture implementation for the isolated regression. */
	function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['meta'][$id][$key] ?? ''; }
	/** Fixture implementation for the isolated regression. */
	function update_post_meta( $id, $key, $value ) { $GLOBALS['meta'][$id][$key] = $value; }
	/** Fixture implementation for the isolated regression. */
	function add_post_meta( $id, $key, $value, $unique = false ) {
		if ( ! isset( $GLOBALS['meta'][$id][$key] ) ) update_post_meta( $id, $key, $value );
	}
	/** Fixture implementation for the isolated regression. */
	function delete_post_meta( $id, $key ) { unset( $GLOBALS['meta'][$id][$key] ); }
	/** Fixture implementation for the isolated regression. */
	function get_posts( $args ) { return $GLOBALS['campaigns']; }
	/** Fixture implementation for the isolated regression. */
	function noptin_get_email_campaign_object( $id ) { return new \Hizzle\Noptin\Emails\Email( $id ); }
	/** Fixture implementation for the isolated regression. */
	function get_post_time( $format, $gmt, $id ) { return time() - 7200; }
	/** Fixture implementation for the isolated regression. */
	function get_option( $key ) { return $GLOBALS['locked'] ? time() : false; }
	/** Fixture implementation for the isolated regression. */
	function delete_option( $key ) { $GLOBALS['locked'] = false; }
	/** Fixture implementation for the isolated regression. */
	function noptin_email_sending_limit_reached() { return $GLOBALS['limited']; }
	/** Fixture implementation for the isolated regression. */
	function apply_filters( $hook, $value, ...$args ) { return $value; }
	/** Fixture implementation for the isolated regression. */
	function is_wp_error( $value ) { return false; }
	/** Fixture implementation for the isolated regression. */
	function has_action( $hook ) { return true; }
	/** Fixture implementation for the isolated regression. */
	function do_action( $hook, ...$args ) {}
	/** Fixture implementation for the isolated regression. */
	function log_noptin_message( $message ) {}
	/** Fixture implementation for the isolated regression. */
	function esc_html( $value ) { return $value; }
	/** Fixture implementation for the isolated regression. */
	function __( $value, $domain = '' ) { return $value; }
	/** Fixture implementation for the isolated regression. */
	function wp_create_nonce( $action ) { return 'fixture'; }
	/** Fixture implementation for the isolated regression. */
	function human_time_diff( $from, $to ) { return '2 hours'; }
	/** Fixture implementation for the isolated regression. */
	function noptin_pause_email_campaign( $id, $reason, $delay = 0 ) { update_post_meta( $id, 'paused', 1 ); }
	require dirname( __DIR__, 3 ) . '/build/Emails/Bulk/Main.php';
	use Hizzle\Noptin\Emails\Bulk\Main;
	$checks = 0;
	$assert = static function ( $ok, $message ) use ( &$checks ) {
		if ( ! $ok ) throw new \RuntimeException( $message );
		++$checks;
	};
	$campaign = noptin_get_email_campaign_object( 1 );
	Main::send_newsletter_campaign( $campaign );
	$start = get_post_meta( 1, '_noptin_sending_started' );
	$assert( $start >= time() - 1, 'Queue entry must record the actual start.' );
	$assert( '' === get_post_meta( 1, '_noptin_last_activity' ), 'Queue entry must not count as a send attempt.' );
	update_post_meta( 1, '_noptin_sending_started', time() - 7200 );
	Main::send_newsletter_campaign( $campaign );
	$assert( get_post_meta( 1, '_noptin_sending_started' ) < time() - 7000, 'Resume must preserve the start.' );
	$sender = new class {
		public $result = true;
		public $observed_activity;
		/** Fixture implementation for the isolated regression. */
		public function send( $campaign, $recipient ) {
			$this->observed_activity = get_post_meta( $campaign->id, '_noptin_last_activity' );
			if ( 'throw' === $this->result ) throw new \RuntimeException( 'Fixture failure' );
			return $this->result;
		}
	};
	Main::$senders['fixture'] = $sender;
	$recovery = Main::get_foreground_recovery_data();
	$assert( 1 === $recovery['campaignId'] && 0 === $recovery['lastActivity'], 'Zero sends must recover despite healthy recurring tasks, without inventing activity.' );
	update_post_meta( 1, '_noptin_last_activity', time() );
	$assert( false !== Main::get_foreground_recovery_data(), 'Recent unsuccessful attempts must not hide prolonged zero sends.' );
	update_post_meta( 1, '_noptin_sending_started', time() );
	$assert( false === Main::get_foreground_recovery_data(), 'Fresh campaigns must not trigger recovery.' );
	$GLOBALS['campaigns'] = array( 2, 1 );
	update_post_meta( 2, '_noptin_sending_started', time() );
	update_post_meta( 1, '_noptin_sending_started', time() - 7200 );
	$assert( false === Main::get_foreground_recovery_data(), 'A queued campaign must not bypass an earlier sending campaign.' );
	$GLOBALS['campaigns'] = array( 1 );
	$GLOBALS['locked'] = true;
	$assert( false === Main::get_foreground_recovery_data(), 'A live sender lock must suppress recovery.' );
	$GLOBALS['locked'] = false;
	$GLOBALS['limited'] = true;
	$assert( false === Main::get_foreground_recovery_data(), 'Sending limits must suppress recovery.' );
	$GLOBALS['limited'] = false;
	update_post_meta( 1, '_noptin_sends', 1 );
	update_post_meta( 1, '_noptin_last_activity', time() - 7200 );
	$assert( false === Main::get_foreground_recovery_data(), 'Existing nonzero-send recovery must still check task health.' );
	$GLOBALS['healthy_tasks'] = false;
	$assert( false !== Main::get_foreground_recovery_data(), 'Stale activity with unhealthy tasks must still recover.' );
	delete_post_meta( 1, '_noptin_sending_started' );
	delete_post_meta( 1, '_noptin_sends' );
	$assert( false !== Main::get_foreground_recovery_data(), 'Legacy campaigns must retain recovery.' );
	$send = new \ReflectionMethod( Main::class, 'send_campaign' );
	$send->setAccessible( true );
	delete_post_meta( 1, '_noptin_last_activity' );
	$send->invoke( null, false, $campaign );
	$assert( '' === get_post_meta( 1, '_noptin_last_activity' ), 'Empty batches must not update activity.' );
	foreach ( array( true, false, 'throw' ) as $result ) {
		$sender->result = $result;
		update_post_meta( 1, '_noptin_last_activity', 1 );
		$send->invoke( null, 'fixture@example.test', $campaign );
		$assert( 1 === $sender->observed_activity, 'Activity must not advance before the sender returns or throws.' );
		$assert( get_post_meta( 1, '_noptin_last_activity' ) >= time() - 1, 'Success, failure, and exceptions must record completed attempts.' );
	}
	echo "Passed $checks bulk sender checks.\n";
}
