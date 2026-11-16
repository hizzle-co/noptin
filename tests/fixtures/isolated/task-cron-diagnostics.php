<?php
/** Exercise real runner scheduling with isolated WordPress scheduling/cache/database doubles. */
define( 'ABSPATH', __DIR__ );
define( 'MINUTE_IN_SECONDS', 60 );
/** In-memory fixture used only by this isolated regression. */
class WP_Error {
	private $code;
	private $message;
	/** Fixture implementation for the isolated regression. */
	public function __construct( $code, $message ) { $this->code = $code; $this->message = $message; }
	/** Fixture implementation for the isolated regression. */
	public function get_error_code() { return $this->code; }
	/** Fixture implementation for the isolated regression. */
	public function get_error_message() { return $this->message; }
}
/** Fixture implementation for the isolated regression. */
function is_wp_error( $value ) { return $value instanceof WP_Error; }
/** Fixture implementation for the isolated regression. */
function noptin() {
	return new class {
		/** Fixture implementation for the isolated regression. */
		public function db() { return $this; }
		/** Fixture implementation for the isolated regression. */
		public function query( ...$args ) {
			return $GLOBALS['tasks'] ? array( new class {
				/** Fixture implementation for the isolated regression. */
				public function get_date_scheduled() { return new DateTimeImmutable( '@' . $GLOBALS['run_at'] ); }
			} ) : array();
		}
	};
}
/** Fixture implementation for the isolated regression. */
function wp_next_scheduled( $hook ) { return $GLOBALS['existing']; }
/** Fixture implementation for the isolated regression. */
function wp_unschedule_event( $timestamp, $hook ) { $GLOBALS['unscheduled'][] = $timestamp; }
/** Fixture implementation for the isolated regression. */
function wp_schedule_event( $timestamp, $schedule, $hook, $args, $return_error ) {
	$GLOBALS['schedule_calls']++;
	$GLOBALS['wpdb']->last_error = $GLOBALS['write_error'];
	return $GLOBALS['schedule_result'];
}
/** Fixture implementation for the isolated regression. */
function wp_cache_delete( $key, $group ) { $GLOBALS['cache_deletes'][] = array( $key, $group ); }
/** Fixture implementation for the isolated regression. */
function wp_get_scheduled_event( $hook, $args ) {
	if ( 'noptin_run_tasks' !== $hook || array() !== $args ) throw new RuntimeException( 'Wrong event identity.' );
	if ( array( array( 'cron', 'options' ), array( 'alloptions', 'options' ) ) !== $GLOBALS['cache_deletes'] ) throw new RuntimeException( 'Schedule check must bypass cached cron options.' );
	$GLOBALS['checks_after_failure']++;
	$GLOBALS['wpdb']->last_error = 'Later lookup changed the database error';
	return $GLOBALS['event'];
}
/** Fixture implementation for the isolated regression. */
function log_noptin_message( $message ) { $GLOBALS['logs'][] = $message; }
require dirname( __DIR__, 3 ) . '/build/Tasks/Main.php';
$runner = ( new ReflectionClass( \Hizzle\Noptin\Tasks\Main::class ) )->newInstanceWithoutConstructor();
$wpdb = (object) array( 'last_error' => '' );
$assertions = 0;
$assert = static function ( $ok, $message ) use ( &$assertions ) {
	if ( ! $ok ) throw new RuntimeException( $message );
	++$assertions;
};
$reset = static function () {
	$GLOBALS['tasks'] = true;
	$GLOBALS['run_at'] = time() + 600;
	$GLOBALS['existing'] = false;
	$GLOBALS['event'] = false;
	$GLOBALS['write_error'] = '';
	$GLOBALS['schedule_result'] = new WP_Error( 'could_not_set', 'The cron event list could not be saved.' );
	$GLOBALS['logs'] = $GLOBALS['cache_deletes'] = $GLOBALS['unscheduled'] = array();
	$GLOBALS['schedule_calls'] = $GLOBALS['checks_after_failure'] = 0;
};
foreach ( array( 0, 30, -30 ) as $offset ) {
	$reset();
	$event = (object) array( 'schedule' => 'every_minute', 'timestamp' => $run_at + $offset );
	$runner->ensure_cron_scheduled();
	$assert( array() === $logs && 1 === $checks_after_failure, 'A matching competing schedule must suppress the save warning.' );
}
$reset();
$write_error = 'Fixture database write failure';
$runner->ensure_cron_scheduled();
$assert( 1 === count( $logs ) && false !== strpos( $logs[0], '[could_not_set]' ) && false !== strpos( $logs[0], $write_error ), 'Missing events must log the original scheduling code and database error.' );
$assert( false === strpos( $logs[0], 'Later lookup' ), 'A verification query must not replace the original database error.' );
$reset();
$runner->ensure_cron_scheduled();
$assert( false !== strpos( $logs[0], 'none reported' ), 'Missing events without a database error must say so.' );
foreach ( array( array( 'hourly', 0 ), array( 'every_minute', 3600 ) ) as $wrong_event ) {
	$reset();
	$event = (object) array( 'schedule' => $wrong_event[0], 'timestamp' => $run_at + $wrong_event[1] );
	$runner->ensure_cron_scheduled();
	$assert( 1 === count( $logs ), 'An incorrect recurrence or distant timestamp must not hide a missing expected event.' );
}
$reset();
$schedule_result = true;
$runner->ensure_cron_scheduled();
$assert( 0 === $checks_after_failure && array() === $cache_deletes && array() === $logs, 'Successful scheduling needs no recovery lookup or cache invalidation.' );
$reset();
$existing = $run_at;
$runner->ensure_cron_scheduled();
$assert( 0 === $schedule_calls && array() === $logs, 'An existing timely runner must remain a no-op.' );
$reset();
$tasks = false;
$existing = $run_at;
$runner->ensure_cron_scheduled();
$assert( 0 === $schedule_calls && array( $run_at ) === $unscheduled, 'No pending tasks must still unschedule the runner.' );
echo "Passed $assertions task cron diagnostic checks.\n";
