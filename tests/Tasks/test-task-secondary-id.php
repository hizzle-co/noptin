<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

use Hizzle\Noptin\Tasks\Main;
use Hizzle\Noptin\Tasks\Run_History;

/** First-party regression coverage migrated from task-secondary-id.php. */
class Test_Task_Secondary_ID extends Noptin_Regression_Test_Case {
	/** Verify task secondary id using the isolated WordPress test database. */
	public function test_task_secondary_id() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		remove_all_actions( 'noptin_newsletter_campaign_published' );
		remove_all_actions( 'noptin_send_newsletter' );
		add_filter( 'noptin_saved_task_background_run', '__return_false' );
		remove_action( 'shutdown', array( $GLOBALS['noptin_tasks'], 'run_overdue_tasks_on_shutdown' ), 1 );
		$tasks = array();
		$assert = array( $this, 'assertTrue' );
		$rule = new class {
			/** Fixture implementation for the regression. */
			public function get_id() { return 987654321; }
			/** Fixture implementation for the regression. */
			public function get_action_id() { return 'task_secondary_id_fixture_action'; }
			/** Fixture implementation for the regression. */
			public function get_trigger_id() { return 'task_secondary_id_fixture_trigger'; }
			/** Fixture implementation for the regression. */
			public function get_delay() { return HOUR_IN_SECONDS; }
		};
		$trigger = new class {
			/** Fixture implementation for the regression. */
			public function get_subject_email( ...$args ) { return 'nobody@example.test'; }
			/** Fixture implementation for the regression. */
			public function serialize_trigger_args( $args ) { return $args; }
			/** Fixture implementation for the regression. */
			public function get_id() { return 'task_secondary_id_fixture_trigger'; }
		};

		try {
			$probe = Main::get( 0 );
			foreach ( array( 'parent_id', 'history_parent_id', 'retry_of' ) as $field ) {
				foreach ( array( null, '', 0, '0', '-42', '123' ) as $input ) {
					$probe->{ 'set_' . $field }( $input );
					$expected = absint( $input ) ?: null;
					foreach ( array( 'view', 'edit' ) as $context ) {
						$assert( $expected === $probe->{ 'get_' . $field }( $context ), "$field must return the setter-normalized value in either context." );
					}
				}
			}
			global $wpdb;
			$table = $wpdb->prefix . 'noptin_tasks';
			$column = $wpdb->get_row( "SHOW COLUMNS FROM `$table` LIKE 'secondary_id'" );
			$assert( $column && 0 === strpos( strtolower( $column->Type ), 'bigint' ), 'Secondary IDs must retain the numeric database schema.' );
			foreach ( array( 'parent_id', 'history_parent_id', 'retry_of' ) as $field ) {
				$column = $wpdb->get_row( $wpdb->prepare( "SHOW COLUMNS FROM `$table` LIKE %s", $field ) );
				$assert( $column && 'YES' === $column->Null && null === $column->Default, "$field must be nullable and default to NULL." );
			}
			$root_ids = array();
			foreach ( array( 'default' => null, 'invalid' => 'custom_action', 'numeric' => 123, 'numeric_string' => '456', 'zero' => 0, 'empty' => '', 'null' => null ) as $case => $value ) {
				$args = array( 'fixture' => wp_generate_uuid4() );
				if ( 'default' !== $case ) $args['automation_rule_secondary_id'] = $value;
				$assert( true === Main::run_automation_rule( null, $rule, $args, $trigger ), "Failed to schedule $case task." );
				$found = Main::query( array( 'hook' => 'noptin_run_automation_rule', 'primary_id' => $rule->get_id(), 'args' => wp_json_encode( $args ) ) );
				$assert( ! is_wp_error( $found ) && 1 === count( $found ), "Could not find $case task." );
				$task = $found[0];
				$tasks[] = $task;
				$assert( 'task_secondary_id_fixture_action' === $task->get_meta( 'action_id' ), 'Scheduling must save the action ID directly.' );
				$assert( null === $task->get_meta( 'automation_history' ), 'Scheduling must not save a history wrapper or description.' );
				$empty_relationships = Main::query( array( 'id' => $task->get_id(), 'parent_id' => null, 'history_parent_id' => null, 'retry_of' => null ) );
				$assert( ! is_wp_error( $empty_relationships ) && 1 === count( $empty_relationships ), 'New root tasks must store absent relationships as SQL NULL.' );
				$assert( null === $task->get_parent_id() && ! in_array( $task->get_id(), $root_ids, true ), 'New schedules for the same subject must have distinct roots.' );
				$root_ids[] = $task->get_id();
				$expected = is_numeric( $value ) ? absint( $value ) : null;
				$assert( $expected === $task->get_secondary_id(), "Secondary ID lost after saving $case task." );
				$matches = Main::query( array( 'id' => $task->get_id(), 'secondary_id' => $expected ) );
				$assert( ! is_wp_error( $matches ) && 1 === count( $matches ), "Secondary ID lookup failed for $case task." );
				$task->set_secondary_id( null );
				$assert( ! is_wp_error( $task->save() ) && null === Main::get( $task->get_id() )->get_secondary_id(), 'Null secondary IDs must remain nullable.' );
			}
			$child_args = array( 'fixture' => wp_generate_uuid4() );
			$assert( true === Main::run_automation_rule( null, $rule, $child_args + array( Main::AUTOMATION_PARENT_TASK => $root_ids[0] ), $trigger ), 'Failed to schedule a follow-up task.' );
			$found = Main::query( array( 'hook' => 'noptin_run_automation_rule', 'primary_id' => $rule->get_id(), 'args' => wp_json_encode( $child_args ) ) );
			$assert( ! is_wp_error( $found ) && 1 === count( $found ), 'The run ID should stay outside the serialized trigger args.' );
			$tasks[] = $found[0];
			$assert( $root_ids[0] === $found[0]->get_parent_id(), 'The follow-up task must retain the root task ID.' );
			$retry = Main::retry_task( $found[0], HOUR_IN_SECONDS );
			$tasks[] = $retry;
			$assert( $retry->exists() && $root_ids[0] === $retry->get_parent_id() && $found[0]->get_id() === $retry->get_retry_of(), 'The retry must retain the root task ID and original attempt ID.' );
			$original = $found[0];
			$original->set_status( 'running' );
			$original->update_meta( 'interval', DAY_IN_SECONDS );
			$reschedule = new ReflectionMethod( $original, 'maybe_reschedule' );
			$reschedule->setAccessible( true );
			$reschedule->invoke( $original );
			$recurrence = Main::get( (int) $original->get_meta( 'rescheduled' ) );
			$tasks[] = $recurrence;
			$assert( $recurrence->exists() && 'pending' === $recurrence->get_status(), 'The interval task must schedule a pending occurrence.' );
			$assert( $root_ids[0] === $recurrence->get_parent_id() && null === $recurrence->get_retry_of(), 'An interval occurrence must retain the root task ID and start a new attempt.' );
			$date_rule = new class {
				/** Fixture implementation for the regression. */
				public function get_id() { return 987654322; }
				/** Fixture implementation for the regression. */
				public function get_status() { return true; }
				/** Fixture implementation for the regression. */
				public function get_trigger_settings() { return array( 'frequency' => 'daily', 'time' => '23:59' ); }
				/** Fixture implementation for the regression. */
				public function get_trigger_setting( $key ) { return $this->get_trigger_settings()[ $key ] ?? ''; }
				/** Fixture implementation for the regression. */
				public function get_action_id() { throw new RuntimeException( 'Date task scheduling must not read the action ID.' ); }
			};
			$date_trigger = ( new ReflectionClass( Hizzle\Noptin\Automation_Rules\Triggers\Date::class ) )->newInstanceWithoutConstructor();
			$date_trigger->schedule_rule( $date_rule );
			$found = Main::query( array( 'hook' => 'noptin_run_automation_rule', 'primary_id' => $date_rule->get_id(), 'lookup_key' => 'date' ) );
			$assert( ! is_wp_error( $found ) && 1 === count( $found ), 'Date rule task was not scheduled.' );
			$tasks[] = $found[0];
			$assert( null === $found[0]->get_secondary_id(), 'Date rule tasks must not store the action ID.' );
			$assert( null === $found[0]->get_parent_id(), 'Each scheduled date occurrence must be a root task.' );
			$assert( null === $found[0]->get_interval(), 'Date occurrences must use the Date scheduler rather than a task interval.' );

			// Independent roots sharing a subject must paginate without including their steps.
			$history_rule_id = 987654323;
			for ( $i = 0; $i < 11; ++$i ) {
				$root = Main::create( array(
					'hook'       => Run_History::HOOK,
					'primary_id' => $history_rule_id,
					'subject'    => 'same-submitter@example.test',
					'status'     => 'failed',
					'args'       => array( 'fixture' => wp_generate_uuid4() ),
				) );
				$assert( ! is_wp_error( $root ) && $root->exists(), 'Could not create a history root.' );
				$tasks[] = $root;
			}
			$retry = Main::retry_task( $root, HOUR_IN_SECONDS );
			$tasks[] = $retry;
			$assert( $root->get_id() === $retry->get_parent_id() && 'pending' === Run_History::get_status( $root ), 'A pending root retry must remain in its run and take precedence over the failure.' );
			$retry->set_status( 'complete' );
			$retry->save();
			$assert( 'complete' === Run_History::get_status( $root ), 'A completed retry must resolve the original failure.' );

					$previous_user = get_current_user_id();
			wp_set_current_user( $admin_id );
			try {
				$get_tasks = static function ( $args ) use ( $assert ) {
					$request = new WP_REST_Request( 'GET', '/noptin/v1/tasks' );
					$request->set_query_params( $args + array( 'hook' => Run_History::HOOK ) );
					$response = rest_do_request( $request );
					$assert( 200 === $response->get_status(), 'The tasks endpoint must respond successfully: ' . wp_json_encode( $response->get_data() ) );
					return $response;
				};
				$history_args = array( 'context' => 'edit', 'primary_id' => array( $history_rule_id ), 'parent_id_is_null' => 'true', 'retry_of_is_null' => 'true', 'history_parent_id_is_null' => 'true', 'per_page' => 10, '_fields' => 'id,run_status' );
				$first_page = $get_tasks( $history_args + array( 'page' => 1 ) );
				$second_page = $get_tasks( $history_args + array( 'page' => 2 ) );
				$assert( count( $first_page->get_data() ) === 10 && 2 === (int) $first_page->get_headers()['X-WP-TotalPages'], 'The first page must contain ten root tasks.' );
				$assert( count( $second_page->get_data() ) === 1, 'The second page must contain only the last root task.' );
				$assert( ! array_intersect( wp_list_pluck( $first_page->get_data(), 'id' ), wp_list_pluck( $second_page->get_data(), 'id' ) ), 'Root pages must not overlap.' );
				$filtered = $get_tasks( $history_args + array( 'run_task_status' => 'complete' ) )->get_data();
				$assert( array( $root->get_id() ) === wp_list_pluck( $filtered, 'id' ) && 'complete' === $filtered[0]['run_status'], 'The run filter and summary must account for a completed child.' );
				$assert( array() === $get_tasks( $history_args + array( 'status' => 'complete' ) )->get_data(), 'The existing status filter must still match only the task itself.' );
				$steps = $get_tasks( array( 'parent_id' => $root->get_id(), 'retry_of_is_null' => 'false', 'history_parent_id_is_null' => 'false', 'per_page' => 25 ) )->get_data();
				$assert( array( $retry->get_id() ) === wp_list_pluck( $steps, 'id' ), 'Expanding a run must load only its child tasks.' );
			} finally {
				wp_set_current_user( $previous_user );
			}
		} finally {
			foreach ( $tasks as $task ) $task->delete( true );
		}
	}
}
