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
		$body_child    = $this->create_rule( 'loop', array( 'loop_over' => 'numbers', 'number_start' => 9, 'number_end' => 9 ), $body->get_id() );
		$inner_body    = $this->create_rule( 'test_spy_action', array(), $body_child->get_id() );
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
			$inner_body->get_id(),
			$inner_body->get_id(),
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
			$this->assertSame( 'path', $root->get_meta( 'action_id' ) );
			$this->assertNull( $root->get_meta( 'automation_history' ) );
			$root->process();
			$this->drain_pending_automation_tasks();

			$root = Tasks::get( $root->get_id() );
			$this->assertNull( $root->get_parent_id() );
			$this->assertSame( 'complete', $root->get_status() );
			$steps = Tasks::query( array( 'hook' => Run_History::HOOK, 'parent_id' => $root->get_id() ) );
			$this->assertEqualsCanonicalizing( $expected, array_map( static function ( $step ) {
				return (int) $step->get_primary_id();
			}, $steps ) );
			$this->assertSame( 'path', $root->get_meta( 'action_id' ) );
			$by_rule = array();
			foreach ( $steps as $step ) {
				$by_rule[ $step->get_primary_id() ][] = $step;
			}
			$this->assertSame( $root->get_id(), $by_rule[ $left->get_id() ][0]->get_history_parent_id() );
			$this->assertSame( $root->get_id(), $by_rule[ $right->get_id() ][0]->get_history_parent_id() );
			$this->assertSame( $by_rule[ $child->get_id() ][0]->get_id(), $by_rule[ $grandchild->get_id() ][0]->get_history_parent_id() );
			$loop_task = $by_rule[ $loop->get_id() ][0];
			$loop_end_task = $by_rule[ $loop_end->get_id() ][0];
			$this->assertSame( $loop_task->get_history_parent_id(), $loop_end_task->get_history_parent_id() );
			$this->assertSame( $loop_task->get_id(), $loop_end_task->get_meta( 'after_loop' ) );
			$labels = array();
			foreach ( $by_rule[ $body->get_id() ] as $iteration ) {
				$this->assertSame( $loop_task->get_id(), $iteration->get_history_parent_id() );
				$labels[] = $iteration->get_meta( 'iteration' )['label'];
				$inner_loops = Tasks::query( array( 'history_parent_id' => $iteration->get_id() ) );
				$this->assertCount( 1, $inner_loops );
				$inner_iterations = Tasks::query( array( 'history_parent_id' => $inner_loops[0]->get_id() ) );
				$this->assertCount( 1, $inner_iterations );
				$this->assertSame( 'Number #9', $inner_iterations[0]->get_meta( 'iteration' )['label'] );
			}
			$this->assertEqualsCanonicalizing( array( 'Number #1', 'Number #2' ), $labels );
			$retry = Tasks::retry_task( $by_rule[ $body->get_id() ][0], HOUR_IN_SECONDS );
			$this->assertSame( $by_rule[ $body->get_id() ][0]->get_id(), $retry->get_history_parent_id() );
			$this->assertNull( $retry->get_meta( 'iteration' ) );

			foreach ( $steps as $step ) {
				$this->assertSame( $root->get_id(), $step->get_parent_id() );
				$this->assertNotEmpty( $step->get_meta( 'action_id' ) );
				$this->assertNull( $step->get_meta( 'automation_history' ) );
				$this->assertSame( 'complete', $step->get_status() );
			}
		}

		$this->assertCount( 2, Tasks::query( array( 'hook' => Run_History::HOOK, 'parent_id_is_null' => true ) ) );
	}
}
