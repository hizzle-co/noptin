<?php

namespace Hizzle\Noptin\Tests\Automation_Rules;

require_once __DIR__ . '/includes/class-actions-test-case.php';

/**
 * Tests for the path automation action.
 */
class Test_Path_Action extends Actions_Test_Case {

	public function test_path_action_runs_all_matching_branches() {
		$path    = $this->create_rule( 'path', array( 'path_execution_mode' => 'all' ) );
		$branch1 = $this->create_rule( 'path_branch', array(), $path->get_id(), 10 );
		$branch2 = $this->create_rule( 'path_branch', array(), $path->get_id(), 20 );
		$body1   = $this->create_rule( 'test_spy_action', array(), $branch1->get_id(), 10 );
		$body2   = $this->create_rule( 'test_spy_action', array(), $branch2->get_id(), 10 );

		$this->run_rule( $path );
		$this->drain_pending_automation_tasks();

		$this->assertSame(
			array( $body1->get_id(), $body2->get_id() ),
			wp_list_pluck( Test_Spy_Action::$runs, 'rule_id' )
		);
	}

	public function test_path_action_can_stop_after_first_matching_branch() {
		$path    = $this->create_rule( 'path', array( 'path_execution_mode' => 'first' ) );
		$branch1 = $this->create_rule( 'path_branch', array(), $path->get_id(), 10 );
		$branch2 = $this->create_rule( 'path_branch', array(), $path->get_id(), 20 );
		$body1   = $this->create_rule( 'test_spy_action', array(), $branch1->get_id(), 10 );
		$this->create_rule( 'test_spy_action', array(), $branch2->get_id(), 10 );

		$this->run_rule( $path );
		$this->drain_pending_automation_tasks();

		$this->assertSame( array( $body1->get_id() ), wp_list_pluck( Test_Spy_Action::$runs, 'rule_id' ) );
	}

	public function test_path_action_skips_non_branch_children() {
		$path          = $this->create_rule( 'path', array( 'path_execution_mode' => 'all' ) );
		$active_branch = $this->create_rule( 'path_branch', array(), $path->get_id(), 20 );
		$active_body   = $this->create_rule( 'test_spy_action', array(), $active_branch->get_id(), 10 );

		$this->create_rule( 'test_spy_action', array(), $path->get_id(), 5 );

		$this->run_rule( $path );
		$this->drain_pending_automation_tasks();

		$this->assertSame( array( $active_body->get_id() ), wp_list_pluck( Test_Spy_Action::$runs, 'rule_id' ) );
	}
}
