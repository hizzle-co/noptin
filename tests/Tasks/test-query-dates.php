<?php
/**
 * Regression coverage for machine-readable task query dates.
 *
 * @package Noptin
 */

namespace Hizzle\Noptin\Tests\Tasks;

use Hizzle\Noptin\Tasks\Main;
use WP_UnitTestCase;

/** Verify task cutoffs retain their instant without localized display formatting. */
class Test_Task_Query_Dates extends WP_UnitTestCase {

	/**
	 * Persist a task without allowing save() to process an overdue fixture.
	 *
	 * @param int $timestamp The scheduled Unix timestamp.
	 * @param string $status The task status.
	 * @return \Hizzle\Noptin\Tasks\Task The stored task.
	 */
	private function create_task_at( $timestamp, $status = 'pending' ) {
		global $wpdb;
		$task = Main::get( 0 );
		$task->set_hook( 'noptin_test_query_dates' );
		$task->set_args( wp_json_encode( array( 'fixture' => wp_generate_uuid4() ) ) );
		$task->set_status( $status );
		$task->set_date_scheduled( time() + DAY_IN_SECONDS );
		$task->save();
		$this->assertGreaterThan( 0, $task->get_id() );

		// Move it after save so this test exercises selection without running actions.
		$table = $task->get_collection()->get_db_table_name();
		$this->assertSame( 1, $wpdb->update( $table, array( 'date_scheduled' => gmdate( 'Y-m-d H:i:s', $timestamp ) ), array( 'id' => $task->get_id() ) ) );
		return $task;
	}

	/**
	 * Delete only the records created by this test.
	 *
	 * @param array $tasks The fixture tasks.
	 */
	private function delete_fixture_tasks( $tasks ) {
		foreach ( $tasks as $task ) {
			$task = Main::get( $task->get_id() );
			if ( $task->exists() ) {
				$task->delete();
			}
		}
	}

	/**
	 * Site timezones covering UTC, positive, negative, and fractional offsets.
	 *
	 * @return array The timezone cases.
	 */
	public static function timezone_cases() {
		return array(
			'utc'        => array( 'UTC' ),
			'nairobi'    => array( 'Africa/Nairobi' ),
			'new_york'   => array( 'America/New_York' ),
			'berlin'     => array( 'Europe/Berlin' ),
			'kolkata'    => array( 'Asia/Kolkata' ),
		);
	}

	/**
	 * The actual runner must select overdue pending rows and leave other rows alone.
	 *
	 * @dataProvider timezone_cases
	 * @param string $timezone The site's timezone.
	 */
	public function test_pending_runner_selects_only_due_pending_tasks( $timezone ) {
		$previous_timezone = get_option( 'timezone_string' );
		$tasks             = array();
		$callback_runs     = 0;
		$display_calls     = 0;
		$run               = static function () use ( &$callback_runs ) { ++$callback_runs; };
		$translate         = static function ( $date ) use ( &$display_calls ) {
			++$display_calls;
			return 'localized display date';
		};
		$scope             = static function ( $query ) use ( &$tasks ) {
			$query->query_vars['include'] = array_map( static function ( $task ) { return $task->get_id(); }, $tasks );
		};
		update_option( 'timezone_string', $timezone );
		add_action( 'noptin_test_query_dates', $run );
		try {
			$now = time();
			$tasks[] = $this->create_task_at( $now - 2 * HOUR_IN_SECONDS );
			$tasks[] = $this->create_task_at( $now - 5 * MINUTE_IN_SECONDS );
			$tasks[] = $this->create_task_at( $now + 5 * MINUTE_IN_SECONDS );
			$tasks[] = $this->create_task_at( $now + 2 * HOUR_IN_SECONDS );
			$tasks[] = $this->create_task_at( $now - HOUR_IN_SECONDS, 'complete' );
			$tasks[] = $this->create_task_at( $now - HOUR_IN_SECONDS, 'running' );
			$tasks[] = $this->create_task_at( $now - HOUR_IN_SECONDS, 'canceled' );
			$tasks[] = $this->create_task_at( $now - HOUR_IN_SECONDS, 'failed' );
			$expected = array( $tasks[0]->get_id(), $tasks[1]->get_id() );
			$selected = array();

			add_action( 'noptin_tasks_before_prepare_query', $scope );
			add_filter( 'wp_date', $translate );
			$next = new \ReflectionMethod( $GLOBALS['noptin_tasks'], 'get_next_task' );
			$next->setAccessible( true );
			for ( $i = 0; $i < count( $expected ); ++$i ) {
				$task = $next->invoke( $GLOBALS['noptin_tasks'] );
				$this->assertInstanceOf( '\Hizzle\Noptin\Tasks\Task', $task );
				$this->assertContains( $task->get_id(), $expected );
				$this->assertSame( 'pending', $task->get_status() );
				$selected[] = $task->get_id();
				$this->assertTrue( $task->delete() );
			}
			$this->assertEqualsCanonicalizing( $expected, $selected );
			$this->assertFalse( $next->invoke( $GLOBALS['noptin_tasks'] ) );
			$this->assertTrue( Main::get( $tasks[2]->get_id() )->exists() );
			$this->assertTrue( Main::get( $tasks[3]->get_id() )->exists() );
			$this->assertSame( 0, $display_calls );
			$this->assertSame( 0, $callback_runs, 'Creating and selecting fixtures must not execute them.' );
		} finally {
			remove_action( 'noptin_tasks_before_prepare_query', $scope );
			remove_filter( 'wp_date', $translate );
			remove_action( 'noptin_test_query_dates', $run );
			$this->delete_fixture_tasks( $tasks );
			update_option( 'timezone_string', $previous_timezone );
		}
	}

	/**
	 * Exercise the real failure-query caller while preventing task execution.
	 *
	 * @dataProvider timezone_cases
	 * @param string $timezone The site's timezone.
	 */
	public function test_failure_cutoff_ignores_display_filters( $timezone ) {
		$previous_timezone = get_option( 'timezone_string' );
		$display_calls     = 0;
		$captured          = array();
		$timeout           = WEEK_IN_SECONDS;
		$translate         = static function ( $date ) use ( &$display_calls ) {
			++$display_calls;
			return 'localized display date';
		};
		$capture           = static function ( $results, $query ) use ( &$captured ) {
			$captured[] = $query->query_vars;
			return array();
		};

		update_option( 'timezone_string', $timezone );
		add_filter( 'wp_date', $translate );
		add_filter( 'noptin_tasks_pre_query', $capture, PHP_INT_MAX, 2 );
		try {
			// Ensure the display filter really would corrupt a machine date.
			$this->assertSame( 'localized display date', wp_date( DATE_ATOM ) );
			$display_calls = 0;
			$earliest      = time() - $timeout;
			Main::mark_failures( $timeout );
			$latest = time() - $timeout;

			$this->assertCount( 1, $captured );
			$this->assertSame( 'running', $captured[0]['status'] );
			$this->assertSame( 0, $display_calls, 'Query cutoffs must bypass display filters.' );
			$value = $captured[0]['date_modified_before'];
			$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $value );
			$date = new \DateTimeImmutable( $value );
			$this->assertGreaterThanOrEqual( $earliest, $date->getTimestamp() );
			$this->assertLessThanOrEqual( $latest, $date->getTimestamp() );
			$this->assertSame( ( new \DateTimeZone( $timezone ) )->getOffset( $date ), $date->getOffset() );
		} finally {
			remove_filter( 'noptin_tasks_pre_query', $capture, PHP_INT_MAX );
			remove_filter( 'wp_date', $translate );
			update_option( 'timezone_string', $previous_timezone );
		}
	}

	/**
	 * Fixed instants that distinguish elapsed time from local clock arithmetic.
	 *
	 * @return array Timezone, starting instant, elapsed seconds, and expected result.
	 */
	public static function transition_cases() {
		return array(
			'week_across_fall_back' => array( 'America/New_York', '2026-11-05T17:00:00Z', WEEK_IN_SECONDS, '2026-10-29T13:00:00-04:00' ),
			'week_across_spring_forward' => array( 'America/New_York', '2026-03-12T16:00:00Z', WEEK_IN_SECONDS, '2026-03-05T11:00:00-05:00' ),
			'first_repeated_hour' => array( 'America/New_York', '2026-11-01T05:30:56Z', 0, '2026-11-01T01:30:56-04:00' ),
			'second_repeated_hour' => array( 'America/New_York', '2026-11-01T06:30:56Z', 0, '2026-11-01T01:30:56-05:00' ),
			'europe_first_repeated_hour' => array( 'Europe/Berlin', '2026-10-25T00:30:56Z', 0, '2026-10-25T02:30:56+02:00' ),
			'europe_second_repeated_hour' => array( 'Europe/Berlin', '2026-10-25T01:30:56Z', 0, '2026-10-25T02:30:56+01:00' ),
			'fractional_offset' => array( 'Asia/Kolkata', '2026-07-15T10:04:56Z', 300, '2026-07-15T15:29:56+05:30' ),
			'fixed_offset_week' => array( 'Africa/Nairobi', '2026-07-15T12:34:56Z', WEEK_IN_SECONDS, '2026-07-08T15:34:56+03:00' ),
		);
	}

	/**
	 * Real database selection must preserve one-second boundaries through DST changes.
	 *
	 * @dataProvider transition_cases
	 * @param string $timezone The site's timezone.
	 * @param string $now A fixed starting instant.
	 * @param int $seconds The elapsed duration to subtract.
	 * @param string $expected The expected local cutoff.
	 */
	public function test_pending_query_preserves_seconds_across_timezone_changes( $timezone, $now, $seconds, $expected ) {
		$previous_timezone = get_option( 'timezone_string' );
		$tasks             = array();
		update_option( 'timezone_string', $timezone );
		try {
			$cutoff = ( new \DateTimeImmutable( $now ) )->getTimestamp() - $seconds;
			$tasks[] = $this->create_task_at( $cutoff - 1 );
			$tasks[] = $this->create_task_at( $cutoff );
			$tasks[] = $this->create_task_at( $cutoff + 1 );
			$tasks[] = $this->create_task_at( $cutoff - 1, 'complete' );
			$value = current_datetime()->setTimestamp( $cutoff )->format( DATE_ATOM );
			$this->assertSame( $expected, $value );
			$selected = Main::query(
				array(
					'include'               => array_map( static function ( $task ) { return $task->get_id(); }, $tasks ),
					'status'                => 'pending',
					'date_scheduled_before' => $value,
				)
			);
			$this->assertCount( 1, $selected );
			$this->assertSame( $tasks[0]->get_id(), $selected[0]->get_id() );
		} finally {
			$this->delete_fixture_tasks( $tasks );
			update_option( 'timezone_string', $previous_timezone );
		}
	}

	/**
	 * Ensure task-style formatting preserves exact seconds across timezone changes.
	 *
	 * @dataProvider transition_cases
	 * @param string $timezone The site's timezone.
	 * @param string $now A fixed starting instant.
	 * @param int $seconds The elapsed duration to subtract.
	 * @param string $expected The expected local date with its historical offset.
	 */
	public function test_cutoff_round_trip_across_timezone_changes( $timezone, $now, $seconds, $expected ) {
		$previous_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', $timezone );
		try {
			$timestamp = ( new \DateTimeImmutable( $now ) )->getTimestamp() - $seconds;
			$value     = current_datetime()->setTimestamp( $timestamp )->format( DATE_ATOM );
			$this->assertSame( $expected, $value );
			$this->assertSame( $timestamp, ( new \DateTimeImmutable( $value ) )->getTimestamp() );
		} finally {
			update_option( 'timezone_string', $previous_timezone );
		}
	}
}
