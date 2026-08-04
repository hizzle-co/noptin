<?php

namespace Hizzle\Noptin\Tests\Automation_Rules;

require_once __DIR__ . '/includes/class-actions-test-case.php';

use Hizzle\Noptin\Tasks\Main as Tasks_Main;

/**
 * Tests for continuing workflows when an action is unavailable.
 */
class Test_Pass_Through_Action extends Actions_Test_Case {

	public function set_up() {
		parent::set_up();
		delete_option( 'noptin_logged_messages' );
	}

	public function tear_down() {
		delete_option( 'noptin_logged_messages' );
		parent::tear_down();
	}

	public function test_unavailable_action_continues_to_child_rules() {
		$parent = $this->create_rule( 'removed_integration_action', array( '_noptin_stop_on_failure' => true ) );
		$child  = $this->create_rule( 'test_spy_action', array(), $parent->get_id() );

		$parent->get_trigger()->trigger(
			'person@example.com',
			array( 'rule_id' => $parent->get_id() )
		);
		$this->drain_pending_automation_tasks();

		$this->assertCount( 1, Test_Spy_Action::$runs );
		$this->assertSame( $child->get_id(), Test_Spy_Action::$runs[0]['rule_id'] );
		$this->assertSame( 1, noptin_get_automation_rule( $parent->get_id() )->get_times_run() );

		$warnings = array_filter(
			get_logged_noptin_messages(),
			function ( $message ) {
				return 'warning' === $message['level'];
			}
		);
		$this->assertNotEmpty( $warnings );
		$this->assertStringContainsString( 'removed_integration_action', end( $warnings )['msg'] );
	}

	public function test_unavailable_action_preserves_delay() {
		$parent = $this->create_rule( 'removed_integration_action' );
		$this->create_rule( 'test_spy_action', array(), $parent->get_id() );
		$parent->set_delay( HOUR_IN_SECONDS );
		$parent->save();

		$started_at = time();
		$parent->get_trigger()->trigger(
			'person@example.com',
			array( 'rule_id' => $parent->get_id() )
		);

		$tasks = Tasks_Main::query(
			array(
				'hook'       => 'noptin_run_automation_rule',
				'status'     => 'pending',
				'primary_id' => $parent->get_id(),
			)
		);

		$this->assertCount( 1, $tasks );
		$this->assertGreaterThanOrEqual( $started_at + HOUR_IN_SECONDS, $tasks[0]->get_date_scheduled()->getTimestamp() );
		$this->assertCount( 0, Test_Spy_Action::$runs );
	}

	public function test_unavailable_action_respects_conditional_logic() {
		$parent = $this->create_rule( 'removed_integration_action' );
		$this->create_rule( 'test_spy_action', array(), $parent->get_id() );
		$parent->set_trigger_settings(
			array(
				'frequency'         => 'manual',
				'conditional_logic' => array(
					'enabled' => true,
					'action'  => 'allow',
					'type'    => 'all',
					'rules'   => array(
						array(
							'type'      => 'current_language',
							'condition' => 'is',
							'value'     => 'not-a-real-locale',
						),
					),
				),
			)
		);
		$parent->save();

		$parent->get_trigger()->trigger(
			'person@example.com',
			array( 'rule_id' => $parent->get_id() )
		);

		$tasks = Tasks_Main::query(
			array(
				'hook'   => 'noptin_run_automation_rule',
				'status' => 'pending',
			)
		);
		$this->assertEmpty( $tasks );
		$this->assertCount( 0, Test_Spy_Action::$runs );
	}

	public function test_unavailable_leaf_action_does_not_schedule_a_task() {
		$rule = $this->create_rule( 'removed_integration_action' );

		$rule->get_trigger()->trigger(
			'person@example.com',
			array( 'rule_id' => $rule->get_id() )
		);

		$tasks = Tasks_Main::query(
			array(
				'hook'   => 'noptin_run_automation_rule',
				'status' => 'pending',
			)
		);
		$this->assertEmpty( $tasks );
	}

	public function test_invalid_trigger_still_fails() {
		$parent = $this->create_rule( 'removed_integration_action' );
		$this->create_rule( 'test_spy_action', array(), $parent->get_id() );
		$parent->set_trigger_id( 'removed_integration_trigger' );
		$parent->save();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid or unregistered trigger' );
		$this->run_rule( $parent );
	}
}
