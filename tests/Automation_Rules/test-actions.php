<?php

namespace Hizzle\Noptin\Tests\Automation_Rules;

use Hizzle\Noptin\Automation_Rules\Actions\Action;
use Hizzle\Noptin\Automation_Rules\Actions\Main as Actions_Main;
use Hizzle\Noptin\Automation_Rules\Automation_Rule;
use Hizzle\Noptin\Automation_Rules\Main as Automation_Rules_Main;
use Hizzle\Noptin\Objects\Collection;
use Hizzle\Noptin\Objects\Store;
use Hizzle\Noptin\Tasks\Main as Tasks_Main;
use WP_Error;
use WP_UnitTestCase;

/**
 * Tests for automation rule actions.
 */
class Test_Actions extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();

		noptin()->db()->delete_all( 'automation_rules' );
		noptin()->db()->delete_all( 'subscribers' );
		noptin()->db()->delete_all( 'tasks' );
		Test_Spy_Action::reset();
		$this->register_test_action();
		$this->register_subscribers_collection_if_missing();
	}

	public function tear_down() {
		Test_Spy_Action::reset();
		noptin()->db()->delete_all( 'automation_rules' );
		noptin()->db()->delete_all( 'subscribers' );
		noptin()->db()->delete_all( 'tasks' );

		parent::tear_down();
	}

	private function register_test_action() {
		if ( ! Actions_Main::exists( 'test_spy_action' ) ) {
			Actions_Main::add( new Test_Spy_Action() );
		}
	}

	private function register_subscribers_collection_if_missing() {
		$collection = class_exists( Store::class ) ? Store::get( 'subscribers' ) : null;

		if ( class_exists( Store::class ) && ( ! $collection || empty( $collection->can_list ) ) ) {
			Store::add( new Test_Subscribers_Collection() );
		}
	}

	private function create_rule( $action_id, $action_settings = array(), $parent_id = 0, $priority = 0, $status = true ) {
		/** @var Automation_Rule $rule */
		$rule = noptin()->db()->get( 0, 'automation_rules' );

		$rule->set_trigger_id( 'date' );
		$rule->set_action_id( $action_id );
		$rule->set_status( $status );
		$rule->set_parent_id( $parent_id );
		$rule->set_priority( $priority );
		$rule->set_trigger_settings(
			array(
				'frequency' => 'manual',
			)
		);
		$rule->set_action_settings( $action_settings );
		$rule->save();

		return $rule;
	}

	private function run_rule( $rule, $subject = 'admin@example.com', $args = array() ) {
		return Automation_Rules_Main::run_automation_rule(
			$rule->get_id(),
			array_merge(
				array(
					'subject' => $subject,
				),
				$args
			)
		);
	}

	private function drain_pending_automation_tasks() {
		for ( $i = 0; $i < 10; $i++ ) {
			$tasks = Tasks_Main::query(
				array(
					'hook'   => 'noptin_run_automation_rule',
					'status' => 'pending',
					'number' => 100,
				)
			);

			if ( empty( $tasks ) || is_wp_error( $tasks ) ) {
				return;
			}

			foreach ( $tasks as $task ) {
				$task->process();
			}
		}
	}

	private function create_loop_with_body( $action_settings, $with_loop_end = false ) {
		$loop = $this->create_rule( 'loop', $action_settings );
		$body = $this->create_rule( 'test_spy_action', array(), $loop->get_id(), 10 );
		$end  = null;

		if ( $with_loop_end ) {
			$end = $this->create_rule( 'loop_end', array(), $loop->get_id(), 20 );
			$this->create_rule( 'test_spy_action', array(), $end->get_id(), 10 );
		}

		return array( $loop, $body, $end );
	}

	private function assert_extra_arg_values( $run, $expected_values ) {
		$this->assertArrayHasKey( 'extra_args', $run['args'] );

		foreach ( $expected_values as $value ) {
			$this->assertContains( $value, array_values( $run['args']['extra_args'] ) );
		}
	}

	private function fixture_path( $file ) {
		return __DIR__ . '/fixtures/' . $file;
	}

	public function test_action_maybe_run_records_run_and_updates_rule_count() {
		$rule   = $this->create_rule( 'test_spy_action' );
		$result = $this->run_rule( $rule, 'person@example.com' );

		$this->assertTrue( $result );
		$this->assertCount( 1, Test_Spy_Action::$runs );
		$this->assertSame( 'person@example.com', Test_Spy_Action::$runs[0]['subject'] );
		$this->assertSame( $rule->get_id(), Test_Spy_Action::$runs[0]['rule_id'] );
		$this->assertSame( $rule->get_id(), $GLOBALS['current_noptin_rule'] );
		$this->assertSame( 'person@example.com', $GLOBALS['current_noptin_email'] );
		$this->assertSame( 1, noptin_get_automation_rule( $rule->get_id() )->get_times_run() );
	}

	public function test_action_maybe_run_respects_can_run() {
		Test_Spy_Action::$can_run = false;

		$rule   = $this->create_rule( 'test_spy_action' );
		$action = Actions_Main::get( 'test_spy_action' );
		$result = $action->maybe_run( 'admin@example.com', $rule, array( 'subject' => 'admin@example.com' ) );

		$this->assertFalse( $result );
		$this->assertCount( 0, Test_Spy_Action::$runs );
		$this->assertSame( 0, noptin_get_automation_rule( $rule->get_id() )->get_times_run() );
	}

	public function test_action_maybe_run_throws_for_wp_error_result() {
		Test_Spy_Action::$return = new WP_Error( 'test_error', 'The test action failed.' );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'The test action failed.' );

		$this->run_rule( $this->create_rule( 'test_spy_action' ) );
	}

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

	public function test_loop_action_loops_over_number_range() {
		list( $loop ) = $this->create_loop_with_body(
			array(
				'loop_over'      => 'numbers',
				'number_start'   => 1,
				'number_end'     => 3,
				'number_step'    => 1,
				'max_iterations' => 0,
			)
		);

		$this->run_rule( $loop );
		$this->drain_pending_automation_tasks();

		$this->assertCount( 3, Test_Spy_Action::$runs );
		$this->assert_extra_arg_values( Test_Spy_Action::$runs[0], array( 1, 0 ) );
		$this->assert_extra_arg_values( Test_Spy_Action::$runs[1], array( 2, 1 ) );
		$this->assert_extra_arg_values( Test_Spy_Action::$runs[2], array( 3, 2 ) );
	}

	public function test_loop_action_respects_number_step_direction_and_max_iterations() {
		list( $loop ) = $this->create_loop_with_body(
			array(
				'loop_over'      => 'numbers',
				'number_start'   => 5,
				'number_end'     => 1,
				'number_step'    => 2,
				'max_iterations' => 2,
			)
		);

		$this->run_rule( $loop );
		$this->drain_pending_automation_tasks();

		$this->assertCount( 2, Test_Spy_Action::$runs );
		$this->assert_extra_arg_values( Test_Spy_Action::$runs[0], array( 5, 0 ) );
		$this->assert_extra_arg_values( Test_Spy_Action::$runs[1], array( 3, 1 ) );
	}

	public function test_loop_action_loops_over_subscribers_collection() {
		add_noptin_subscriber(
			array(
				'email' => 'loop-one@example.com',
				'name'  => 'Loop One',
			)
		);
		add_noptin_subscriber(
			array(
				'email' => 'loop-two@example.com',
				'name'  => 'Loop Two',
			)
		);

		list( $loop ) = $this->create_loop_with_body(
			array(
				'loop_over'      => 'subscribers',
				'max_iterations' => 2,
			)
		);

		$this->run_rule( $loop );
		$this->drain_pending_automation_tasks();

		$this->assertCount( 2, Test_Spy_Action::$runs );
		$this->assertArrayHasKey( 'provided_collections', Test_Spy_Action::$runs[0]['args'] );
		$this->assert_extra_arg_values( Test_Spy_Action::$runs[0], array( 0 ) );
		$this->assert_extra_arg_values( Test_Spy_Action::$runs[1], array( 1 ) );
	}

	/**
	 * @dataProvider file_loop_provider
	 */
	public function test_loop_action_loops_over_files( $type, $file, $first_email, $first_name ) {
		if ( 'xml' === $type && ! class_exists( '\XMLReader' ) && ! function_exists( 'simplexml_load_string' ) ) {
			$this->markTestSkipped( 'XML loop tests require XMLReader or SimpleXML.' );
		}

		list( $loop ) = $this->create_loop_with_body(
			array(
				'loop_over'      => $type,
				'file'           => $this->fixture_path( $file ),
				'max_iterations' => 2,
			)
		);

		$this->run_rule( $loop );
		$this->drain_pending_automation_tasks();

		$this->assertCount( 2, Test_Spy_Action::$runs );
		$this->assert_extra_arg_values( Test_Spy_Action::$runs[0], array( $first_email, $first_name, 0 ) );
		$this->assert_extra_arg_values( Test_Spy_Action::$runs[1], array( 1 ) );
	}

	public function file_loop_provider() {
		return array(
			'csv'  => array( 'csv', 'loop.csv', 'ada@example.com', 'Ada Lovelace' ),
			'json' => array( 'json', 'loop.json', 'grace@example.com', 'Grace Hopper' ),
			'xml'  => array( 'xml', 'loop.xml', 'marie@example.com', 'Marie Curie' ),
		);
	}

	public function test_loop_end_runs_once_after_loop_body() {
		list( $loop, $body ) = $this->create_loop_with_body(
			array(
				'loop_over'      => 'numbers',
				'number_start'   => 1,
				'number_end'     => 2,
				'number_step'    => 1,
				'max_iterations' => 0,
			),
			true
		);

		$this->run_rule( $loop );
		$this->drain_pending_automation_tasks();

		$run_ids = wp_list_pluck( Test_Spy_Action::$runs, 'rule_id' );

		$this->assertCount( 3, $run_ids );
		$this->assertSame( $body->get_id(), $run_ids[0] );
		$this->assertSame( $body->get_id(), $run_ids[1] );
		$this->assertNotSame( $body->get_id(), $run_ids[2] );
	}
}

class Test_Spy_Action extends Action {

	public static $runs = array();

	public static $can_run = true;

	public static $return = true;

	public static function reset() {
		self::$runs    = array();
		self::$can_run = true;
		self::$return  = true;
	}

	public function get_id() {
		return 'test_spy_action';
	}

	public function get_name() {
		return 'Test Spy Action';
	}

	public function can_run( $subject, $rule, $args ) {
		return self::$can_run;
	}

	public function run( $subject, $rule, $args ) {
		self::$runs[] = array(
			'subject' => $subject,
			'rule_id' => $rule->get_id(),
			'args'    => $args,
		);

		return self::$return;
	}
}

class Test_Subscribers_Collection extends Collection {

	public $type = 'subscribers';

	public $label = 'Subscribers';

	public $singular_label = 'Subscriber';

	public $can_list = true;

	public function get_all( $filters ) {
		$args = array();

		if ( ! empty( $filters['number'] ) ) {
			$args['number'] = (int) $filters['number'];
		}

		return noptin_get_subscribers( $args );
	}
}
