<?php

namespace Hizzle\Noptin\Tests\Automation_Rules;

use Hizzle\Noptin\Automation_Rules\Actions\Action;
use Hizzle\Noptin\Automation_Rules\Actions\Main as Actions_Main;
use Hizzle\Noptin\Automation_Rules\Automation_Rule;
use Hizzle\Noptin\Automation_Rules\Main as Automation_Rules_Main;
use Hizzle\Noptin\Objects\Collection;
use Hizzle\Noptin\Objects\Store;
use Hizzle\Noptin\Tasks\Main as Tasks_Main;
use WP_UnitTestCase;

/**
 * Shared helpers for automation action tests.
 */
abstract class Actions_Test_Case extends WP_UnitTestCase {

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

	protected function create_rule( $action_id, $action_settings = array(), $parent_id = 0, $priority = 0, $status = true ) {
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

	protected function run_rule( $rule, $subject = 'admin@example.com', $args = array() ) {
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

	protected function drain_pending_automation_tasks() {
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

	protected function create_loop_with_body( $action_settings, $with_loop_end = false ) {
		$loop = $this->create_rule( 'loop', $action_settings );
		$body = $this->create_rule( 'test_spy_action', array(), $loop->get_id(), 10 );
		$end  = null;

		if ( $with_loop_end ) {
			$end = $this->create_rule( 'loop_end', array(), $loop->get_id(), 20 );
			$this->create_rule( 'test_spy_action', array(), $end->get_id(), 10 );
		}

		return array( $loop, $body, $end );
	}

	protected function assert_extra_arg_values( $run, $expected_values ) {
		$this->assertArrayHasKey( 'extra_args', $run['args'] );

		foreach ( $expected_values as $value ) {
			$this->assertContains( $value, array_values( $run['args']['extra_args'] ) );
		}
	}

	protected function fixture_path( $file ) {
		return dirname( __DIR__ ) . '/fixtures/' . $file;
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

	public function get_description() {
		return 'A test action that spies on its runs.';
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

	public function get_fields() {
		return array();
	}
}
