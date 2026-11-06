<?php

namespace Hizzle\Noptin\Tests\Automation_Rules;

use Hizzle\Noptin\Tasks\Main as Tasks;
use Hizzle\Noptin\Tasks\Run_History;

require_once __DIR__ . '/includes/class-actions-test-case.php';

class Test_Run_Task_Parent extends Actions_Test_Case {

	public function test_descendants_of_both_paths_and_loop_iterations_share_the_root_task() {
		$path          = $this->create_rule( 'path', array( 'path_execution_mode' => 'all' ) );
		$left          = $this->create_rule( 'path_branch', array(), $path->get_id(), 10 );
		$right         = $this->create_rule( 'path_branch', array(), $path->get_id(), 20 );
		$child         = $this->create_rule( 'test_spy_action', array(), $left->get_id() );
		$grandchild    = $this->create_rule( 'test_spy_action', array(), $child->get_id() );
		$loop          = $this->create_rule( 'loop', array( 'loop_over' => 'numbers', 'number_start' => 1, 'number_end' => 2 ), $right->get_id() );
		$body          = $this->create_rule( 'test_spy_action', array(), $loop->get_id(), 10 );
		$body_child    = $this->create_rule( 'test_spy_action', array(), $body->get_id() );
		$loop_end      = $this->create_rule( 'loop_end', array(), $loop->get_id(), 20 );
		$continuation  = $this->create_rule( 'test_spy_action', array(), $loop_end->get_id() );

		$expected = array(
			$left->get_id(),
			$right->get_id(),
			$child->get_id(),
			$grandchild->get_id(),
			$loop->get_id(),
			$body->get_id(),
			$body->get_id(),
			$body_child->get_id(),
			$body_child->get_id(),
			$loop_end->get_id(),
			$continuation->get_id(),
		);

		foreach ( array( 'first', 'second' ) as $submission ) {
			$root = Tasks::create(
				array(
					'hook'           => Run_History::HOOK,
					'primary_id'     => $path->get_id(),
					'subject'        => 'same-submitter@example.test',
					'args'           => array( 'subject' => 'same-submitter@example.test', 'submission' => $submission ),
					'date_scheduled' => time() + HOUR_IN_SECONDS,
				)
			);
			$this->assertTrue( $root->exists() );
			$root->process();
			$this->drain_pending_automation_tasks();

			$root = Tasks::get( $root->get_id() );
			$this->assertSame( 0, $root->get_parent_id() );
			$this->assertSame( 'complete', $root->get_status() );
			$steps = Tasks::query( array( 'hook' => Run_History::HOOK, 'parent_id' => $root->get_id() ) );
			$this->assertEqualsCanonicalizing( $expected, array_map( static function ( $step ) {
				return (int) $step->get_primary_id();
			}, $steps ) );
			foreach ( $steps as $step ) {
				$this->assertSame( $root->get_id(), $step->get_parent_id() );
				$this->assertSame( 'complete', $step->get_status() );
			}
		}

		$this->assertCount( 2, Tasks::query( array( 'hook' => Run_History::HOOK, 'parent_id' => 0 ) ) );
	}
}
