<?php

namespace Hizzle\Noptin\Tests\Automation_Rules;

use Hizzle\Noptin\Automation_Rules\Triggers\Date;
use Hizzle\Noptin\Automation_Rules\Triggers\Main as Triggers_Main;
use WP_UnitTestCase;

/**
 * Tests for the date automation rule trigger.
 */
class Test_Date_Trigger extends WP_UnitTestCase {

	/** @var string */
	private $timezone_string;

	/** @var float|string */
	private $gmt_offset;

	public function set_up() {
		parent::set_up();

		$this->timezone_string = get_option( 'timezone_string' );
		$this->gmt_offset      = get_option( 'gmt_offset' );

		noptin()->db()->delete_all( 'tasks' );
		noptin()->db()->delete_all( 'automation_rules' );
	}

	public function tear_down() {
		update_option( 'timezone_string', $this->timezone_string );
		update_option( 'gmt_offset', $this->gmt_offset );

		noptin()->db()->delete_all( 'tasks' );
		noptin()->db()->delete_all( 'automation_rules' );

		parent::tear_down();
	}

	private function use_fixed_midday_timezone() {
		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', 12 - (int) gmdate( 'G' ) );
	}

	private function local_now() {
		return current_datetime();
	}

	private function local_wall_timestamp() {
		return noptin_string_to_timestamp( $this->local_now()->format( 'Y-m-d H:i:s' ) );
	}

	private function local_to_gmt( $datetime ) {
		$date = \DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $datetime, wp_timezone() );
		$this->assertInstanceOf( \DateTimeImmutable::class, $date );

		return $date->getTimestamp();
	}

	private function expected_scheduled_timestamp( $datetime ) {
		return $this->local_to_gmt( $datetime ) + MINUTE_IN_SECONDS;
	}

	private function next_month_date( $day, $time ) {
		$now   = $this->local_now();
		$year  = (int) $now->format( 'Y' );
		$month = (int) $now->format( 'n' ) + 1;

		if ( $month > 12 ) {
			$month = 1;
			++$year;
		}

		$last_day = (int) ( new \DateTimeImmutable( sprintf( '%04d-%02d-01 00:00:00', $year, $month ), wp_timezone() ) )->format( 't' );
		$day      = min( $day, $last_day );

		return sprintf( '%04d-%02d-%02d %s:00', $year, $month, $day, $time );
	}

	private function create_date_rule( $settings, $status = true ) {
		$this->assertInstanceOf( Date::class, Triggers_Main::get( 'date' ) );

		noptin()->db()->delete_all( 'tasks' );

		/** @var \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule */
		$rule = noptin()->db()->get( 0, 'automation_rules' );
		$rule->set_trigger_id( 'date' );
		$rule->set_action_id( 'email' );
		$rule->set_status( $status );
		$rule->set_trigger_settings( $settings );
		$rule->save();

		return $rule;
	}

	private function scheduled_timestamp_for_rule( $settings, $status = true ) {
		$rule = $this->create_date_rule( $settings, $status );

		return Date::get_next_scheduled_timestamp( $rule );
	}

	public function test_daily_rule_schedules_later_today_when_time_is_upcoming() {
		$this->use_fixed_midday_timezone();

		$now      = $this->local_wall_timestamp();
		$run_time = gmdate( 'H:i', $now + HOUR_IN_SECONDS );

		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'daily',
				'time'      => $run_time,
			)
		);

		$this->assertSame(
			$this->expected_scheduled_timestamp( gmdate( 'Y-m-d', $now ) . " $run_time:00" ),
			$scheduled
		);
	}

	public function test_daily_rule_schedules_tomorrow_when_time_has_passed() {
		$this->use_fixed_midday_timezone();

		$now      = $this->local_wall_timestamp();
		$run_time = gmdate( 'H:i', $now - HOUR_IN_SECONDS );

		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'daily',
				'time'      => $run_time,
			)
		);

		$this->assertSame(
			$this->expected_scheduled_timestamp( gmdate( 'Y-m-d', $now + DAY_IN_SECONDS ) . " $run_time:00" ),
			$scheduled
		);
	}

	public function test_weekly_rule_schedules_later_today_when_current_weekday_is_upcoming() {
		$this->use_fixed_midday_timezone();

		$now      = $this->local_wall_timestamp();
		$run_time = gmdate( 'H:i', $now + HOUR_IN_SECONDS );
		$weekday  = (string) gmdate( 'w', $now );

		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'weekly',
				'day'       => $weekday,
				'time'      => $run_time,
			)
		);

		$this->assertSame(
			$this->expected_scheduled_timestamp( gmdate( 'Y-m-d', $now ) . " $run_time:00" ),
			$scheduled
		);
	}

	public function test_weekly_rule_schedules_next_week_when_current_weekday_time_has_passed() {
		$this->use_fixed_midday_timezone();

		$now      = $this->local_wall_timestamp();
		$run_time = gmdate( 'H:i', $now - HOUR_IN_SECONDS );
		$weekday  = (string) gmdate( 'w', $now );

		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'weekly',
				'day'       => $weekday,
				'time'      => $run_time,
			)
		);

		$this->assertSame(
			$this->expected_scheduled_timestamp( gmdate( 'Y-m-d', $now + WEEK_IN_SECONDS ) . " $run_time:00" ),
			$scheduled
		);
	}

	public function test_monthly_rule_schedules_later_today_when_month_day_is_upcoming() {
		$this->use_fixed_midday_timezone();

		$now      = $this->local_wall_timestamp();
		$run_time = gmdate( 'H:i', $now + HOUR_IN_SECONDS );
		$day      = (string) gmdate( 'j', $now );

		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'monthly',
				'date'      => $day,
				'time'      => $run_time,
			)
		);

		$this->assertSame(
			$this->expected_scheduled_timestamp( gmdate( 'Y-m-d', $now ) . " $run_time:00" ),
			$scheduled
		);
	}

	public function test_monthly_rule_schedules_next_month_when_month_day_time_has_passed() {
		$this->use_fixed_midday_timezone();

		$now      = $this->local_wall_timestamp();
		$run_time = gmdate( 'H:i', $now - HOUR_IN_SECONDS );
		$day      = (int) gmdate( 'j', $now );

		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'monthly',
				'date'      => (string) $day,
				'time'      => $run_time,
			)
		);

		$this->assertSame(
			$this->expected_scheduled_timestamp( $this->next_month_date( $day, $run_time ) ),
			$scheduled
		);
	}

	public function test_monthly_rule_schedules_configured_day_in_current_or_next_month() {
		$this->use_fixed_midday_timezone();

		$now      = $this->local_wall_timestamp();
		$run_time = gmdate( 'H:i', $now + HOUR_IN_SECONDS );
		$day      = min( 29, (int) gmdate( 'j', $now ) + 1 );

		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'monthly',
				'date'      => (string) $day,
				'time'      => $run_time,
			)
		);

		$expected = gmdate( 'Y-m', $now ) . sprintf( '-%02d %s:00', $day, $run_time );

		if ( $this->local_to_gmt( $expected ) <= time() ) {
			$expected = $this->next_month_date( $day, $run_time );
		}

		$this->assertSame(
			$this->expected_scheduled_timestamp( $expected ),
			$scheduled
		);
	}

	public function test_yearly_rule_schedules_later_today_when_year_day_is_upcoming() {
		$this->use_fixed_midday_timezone();

		$now      = $this->local_wall_timestamp();
		$run_time = gmdate( 'H:i', $now + HOUR_IN_SECONDS );
		$year_day = (string) ( (int) gmdate( 'z', $now ) + 1 );

		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'yearly',
				'year_day'  => $year_day,
				'time'      => $run_time,
			)
		);

		$this->assertSame(
			$this->expected_scheduled_timestamp( gmdate( 'Y-m-d', $now ) . " $run_time:00" ),
			$scheduled
		);
	}

	public function test_yearly_rule_schedules_next_year_when_year_day_time_has_passed() {
		$this->use_fixed_midday_timezone();

		$now      = $this->local_wall_timestamp();
		$run_time = gmdate( 'H:i', $now - HOUR_IN_SECONDS );
		$year_day = (string) ( (int) gmdate( 'z', $now ) + 1 );

		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'yearly',
				'year_day'  => $year_day,
				'time'      => $run_time,
			)
		);

		$scheduled_local = ( new \DateTimeImmutable( "@$scheduled" ) )->setTimezone( wp_timezone() );

		$this->assertGreaterThan( time(), $scheduled );
		$this->assertGreaterThan( (int) gmdate( 'Y', $now ), (int) $scheduled_local->format( 'Y' ) );
		$this->assertSame( $run_time, $scheduled_local->format( 'H:i' ) );
	}

	public function test_yearly_day_366_schedules_december_31() {
		$this->use_fixed_midday_timezone();

		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'yearly',
				'year_day'  => '366',
				'time'      => '07:00',
			)
		);

		$scheduled_local = ( new \DateTimeImmutable( "@$scheduled" ) )->setTimezone( wp_timezone() );

		$this->assertSame( '12-31', $scheduled_local->format( 'm-d' ) );
		$this->assertSame( '07:01', $scheduled_local->format( 'H:i' ) );
	}

	public function test_x_days_rule_schedules_relative_to_today_when_next_send_is_empty() {
		$this->use_fixed_midday_timezone();

		$now      = $this->local_wall_timestamp();
		$run_time = gmdate( 'H:i', $now + HOUR_IN_SECONDS );

		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'x_days',
				'x_days'    => '3',
				'time'      => $run_time,
			)
		);

		$this->assertSame(
			$this->expected_scheduled_timestamp( gmdate( 'Y-m-d', $now + ( 3 * DAY_IN_SECONDS ) ) . " $run_time:00" ),
			$scheduled
		);
	}

	public function test_x_days_rule_uses_future_next_send() {
		$this->use_fixed_midday_timezone();

		$now       = $this->local_wall_timestamp();
		$next_send = gmdate( 'Y-m-d H:i:s', $now + ( 5 * DAY_IN_SECONDS ) );

		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'x_days',
				'x_days'    => '3',
				'time'      => '07:00',
				'next_send' => $next_send,
			)
		);

		$this->assertSame( $this->expected_scheduled_timestamp( $next_send ), $scheduled );
	}

	public function test_x_days_rule_ignores_past_next_send() {
		$this->use_fixed_midday_timezone();

		$now       = $this->local_wall_timestamp();
		$run_time  = gmdate( 'H:i', $now + HOUR_IN_SECONDS );
		$next_send = gmdate( 'Y-m-d H:i:s', $now - DAY_IN_SECONDS );

		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'x_days',
				'x_days'    => '3',
				'time'      => $run_time,
				'next_send' => $next_send,
			)
		);

		$this->assertSame(
			$this->expected_scheduled_timestamp( gmdate( 'Y-m-d', $now + ( 3 * DAY_IN_SECONDS ) ) . " $run_time:00" ),
			$scheduled
		);
	}

	public function test_skip_days_moves_to_next_allowed_day() {
		$this->use_fixed_midday_timezone();

		$now      = $this->local_wall_timestamp();
		$run_time = gmdate( 'H:i', $now + HOUR_IN_SECONDS );

		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'daily',
				'time'      => $run_time,
				'skip_days' => array( (string) gmdate( 'w', $now ) ),
			)
		);

		$this->assertSame(
			$this->expected_scheduled_timestamp( gmdate( 'Y-m-d', $now + DAY_IN_SECONDS ) . " $run_time:00" ),
			$scheduled
		);
	}

	public function test_skip_days_can_prevent_scheduling_when_all_weekdays_are_skipped() {
		$this->use_fixed_midday_timezone();

		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'daily',
				'time'      => '07:00',
				'skip_days' => array( '0', '1', '2', '3', '4', '5', '6' ),
			)
		);

		$this->assertFalse( $scheduled );
	}

	public function test_manual_rule_is_not_scheduled() {
		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'manual',
			)
		);

		$this->assertFalse( $scheduled );
	}

	public function test_inactive_rule_is_not_scheduled() {
		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'daily',
				'time'      => '07:00',
			),
			false
		);

		$this->assertFalse( $scheduled );
	}

	public function test_future_next_send_uses_target_date_timezone_offset() {
		update_option( 'timezone_string', 'America/New_York' );
		update_option( 'gmt_offset', 0 );

		$year     = (int) current_datetime()->format( 'Y' );
		$next_run = new \DateTimeImmutable( "$year-07-15 09:00:00", wp_timezone() );

		if ( $next_run->getTimestamp() <= time() ) {
			$next_run = $next_run->modify( '+1 year' );
		}

		$scheduled = $this->scheduled_timestamp_for_rule(
			array(
				'frequency' => 'x_days',
				'x_days'    => '30',
				'time'      => '09:00',
				'next_send' => $next_run->format( 'Y-m-d H:i:s' ),
			)
		);

		$this->assertSame( $next_run->getTimestamp() + MINUTE_IN_SECONDS, $scheduled );
	}
}
