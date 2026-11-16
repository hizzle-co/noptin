<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

use Hizzle\Noptin\Tasks\Main as Tasks;
use Hizzle\Noptin\Automation_Rules\Actions\Main as Actions;

/** Harmless action whose description reads can be counted. */
class Noptin_History_Metadata_Action extends Hizzle\Noptin\Automation_Rules\Actions\Action {
	public static $descriptions = 0;
	/** @inheritdoc */
	public function get_id() { return 'codex_history_metadata_probe'; }
	/** @inheritdoc */
	public function get_name() { return 'History metadata probe'; }
	/** @inheritdoc */
	public function get_description() { ++self::$descriptions; return 'Harmless history metadata probe.'; }
	/** @inheritdoc */
	public function run( $subject, $rule, $args ) { return true; }
}

/** First-party regression coverage migrated from automation-history-metadata.php. */
class Test_Automation_History_Metadata extends Noptin_Regression_Test_Case {
	/** Verify automation history metadata using the isolated WordPress test database. */
	public function test_automation_history_metadata() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		remove_all_actions( 'noptin_newsletter_campaign_published' );
		remove_all_actions( 'noptin_send_newsletter' );
		add_filter( 'noptin_saved_task_background_run', '__return_false' );
		remove_action( 'shutdown', array( $GLOBALS['noptin_tasks'], 'run_overdue_tasks_on_shutdown' ), 1 );
		$original_actions = Actions::$actions;
		if ( ! Actions::exists( 'codex_history_metadata_probe' ) ) {
			Actions::add( new Noptin_History_Metadata_Action() );
		}
		$rules = array();
		/** Assert a history invariant. */
		$assert = array( $this, 'assertTrue' );
		/** Create an owned rule without scheduling a recurring date task. */
		$create = static function ( $action, $parent = 0, $settings = array() ) use ( &$rules ) {
			$rule = noptin()->db()->get( 0, 'automation_rules' );
			$rule->set_trigger_id( 'date' );
			$rule->set_trigger_settings( array( 'frequency' => 'manual' ) );
			$rule->set_action_id( $action );
			$rule->set_action_settings( $settings );
			$rule->set_parent_id( $parent );
			$rule->set_status( true );
			$result = $rule->save();
			if ( is_wp_error( $result ) || ! $rule->get_id() ) throw new RuntimeException( 'Could not create owned history fixture.' );
			$rules[] = $rule->get_id();
			return $rule;
		};
		try {
			$root_rule = $create( 'path', 0, array( 'path_execution_mode' => 'all' ) );
			$left = $create( 'path_branch', $root_rule->get_id() );
			$right = $create( 'path_branch', $root_rule->get_id() );
			$loop = $create( 'loop', $left->get_id(), array( 'loop_over' => 'numbers', 'number_start' => 1, 'number_end' => 2 ) );
			$body = $create( 'codex_history_metadata_probe', $loop->get_id() );
			$inner = $create( 'loop', $body->get_id(), array( 'loop_over' => 'numbers', 'number_start' => 9, 'number_end' => 9 ) );
			$create( 'codex_history_metadata_probe', $inner->get_id() );
			$end = $create( 'loop_end', $loop->get_id() );
			$create( 'codex_history_metadata_probe', $end->get_id() );
			$sibling = $create( 'codex_history_metadata_probe', $right->get_id() );
			$root = Tasks::create( array( 'hook' => 'noptin_run_automation_rule', 'primary_id' => $root_rule->get_id(), 'args' => array( 'subject' => 'history-probe@example.test' ), 'date_scheduled' => time() + HOUR_IN_SECONDS ) );
			$assert( 'path' === $root->get_meta( 'action_id' ), 'Root must be modern before it executes.' );
			$root->process();
			for ( $round = 0; $round < 20; ++$round ) {
				$pending = Tasks::query( array( 'primary_id' => $rules, 'status' => 'pending', 'per_page' => 100 ) );
				if ( ! $pending ) break;
				foreach ( $pending as $task ) $task->process();
			}
			$tasks = Tasks::query( array( 'primary_id' => $rules, 'per_page' => 100 ) );
			$assert( 13 === count( $tasks ), 'Both paths and nested loop iterations must run.' );
			$labels = array();
			foreach ( $tasks as $task ) {
				$assert( 'complete' === $task->get_status(), 'Harmless fixture task must complete.' );
				$assert( null === $task->get_meta( 'automation_history' ), 'Do not persist history wrappers or descriptions.' );
				$assert( noptin_get_automation_rule( $task->get_primary_id() )->get_action_id() === $task->get_meta( 'action_id' ), 'Task must record the action actually executed.' );
				if ( $task->get_id() !== $root->get_id() ) $assert( $root->get_id() === $task->get_parent_id(), 'Descendants and siblings must retain root membership.' );
				if ( $task->get_meta( 'iteration' ) ) $labels[] = $task->get_meta( 'iteration' )['label'];
				if ( $end->get_id() === $task->get_primary_id() ) $assert( $task->get_meta( 'after_loop' ) > 0, 'Continuation must retain its loop link.' );
			}
			sort( $labels );
			$assert( array( 'Number #1', 'Number #2', 'Number #9', 'Number #9' ) === $labels, 'Iteration labels must survive nested loops.' );
			$body_tasks = Tasks::query( array( 'primary_id' => $body->get_id() ) );
			$before = Noptin_History_Metadata_Action::$descriptions;
			$retry = Tasks::retry_task( $body_tasks[0], HOUR_IN_SECONDS );
			$assert( 'codex_history_metadata_probe' === $retry->get_meta( 'action_id' ) && null === $retry->get_meta( 'iteration' ), 'Retries preserve action identity without repeating an iteration label.' );
			$assert( $body_tasks[0]->get_id() === $retry->get_history_parent_id(), 'Retry history parent must remain the attempted task.' );
			$assert( $before === Noptin_History_Metadata_Action::$descriptions, 'Retry scheduling must not render action descriptions.' );
			$queued = Tasks::create( array( 'hook' => 'noptin_run_automation_rule', 'primary_id' => $sibling->get_id(), 'args' => array( 'subject' => 'history-probe@example.test' ), 'date_scheduled' => time() + HOUR_IN_SECONDS ) );
			$assert( 'codex_history_metadata_probe' === $queued->get_meta( 'action_id' ), 'Pending tasks must identify their scheduled action.' );
			$sibling->set_action_id( 'path_branch' );
			$sibling->save();
			$queued->process();
			$assert( 'path_branch' === Tasks::get( $queued->get_id() )->get_meta( 'action_id' ), 'Execution must record the actual action when a queued rule changes.' );
			$index_root = Tasks::create( array( 'hook' => 'noptin_run_automation_rule', 'primary_id' => $loop->get_id(), 'status' => 'complete', 'date_scheduled' => time() + HOUR_IN_SECONDS ) );
			$index_root_id = $index_root->get_id();
			$assert( $index_root_id > 0, 'Iteration index fixture must have an owned root ID.' );
			global $wpdb;
			$table = $index_root->get_collection()->get_db_table_name();
			$values = array();
			$arguments = array( $table );
			for ( $ordinal = 1; $ordinal <= 1000; ++$ordinal ) {
				$values[] = '(%d,%d,%d,%s,%s,%s,%s,%s,%s,%s,%s)';
				$metadata = wp_json_encode( array( 'action_id' => 'codex_history_metadata_probe', 'iteration' => array( 'ordinal' => $ordinal, 'label' => 'Iteration ' . $ordinal ) ) );
				array_push( $arguments, $body->get_id(), $index_root_id, $index_root_id, 'noptin_run_automation_rule', 'complete', '{}', md5( $metadata ), gmdate( 'Y-m-d H:i:s' ), gmdate( 'Y-m-d H:i:s' ), gmdate( 'Y-m-d H:i:s' ), $metadata );
			}
			$inserted = $wpdb->query( $wpdb->prepare( 'INSERT INTO %i (primary_id,parent_id,history_parent_id,hook,status,args,args_hash,date_created,date_modified,date_scheduled,metadata) VALUES ' . implode( ',', $values ), $arguments ) );
			$assert( 1000 === $inserted, 'Create all 1000 owned iterations for the lookup regression.' );
			$before_queries = $wpdb->num_queries;
			$assert( null === $index_root->get_loop_iterations( 'view' ), 'Iteration lookup must not run in view context.' );
			$assert( null === $root->get_loop_iterations( 'edit' ), 'Non-loop tasks must not run the iteration lookup.' );
			$assert( $before_queries === $wpdb->num_queries, 'Unneeded iteration getters must issue no database queries.' );
			$index = (array) $index_root->get_loop_iterations( 'edit' );
			$assert( 1000 === count( $index ) && 'Iteration 1000' === end( $index ), 'The index must include all iterations, including the last one.' );
			$assert( $before_queries + 1 === $wpdb->num_queries, 'The full iteration index must use one lightweight query.' );
		} finally {
			Actions::$actions = $original_actions;
			if ( ! empty( $index_root_id ) ) $wpdb->delete( $table, array( 'parent_id' => $index_root_id ) );
			if ( $rules ) foreach ( Tasks::query( array( 'primary_id' => $rules, 'per_page' => -1 ) ) as $task ) $task->delete();
			foreach ( array_reverse( $rules ) as $id ) noptin_get_automation_rule( $id )->delete();
		}
	}
}
