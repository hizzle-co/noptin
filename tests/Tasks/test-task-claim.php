<?php

namespace Hizzle\Noptin\Tests\Tasks;

use Hizzle\Noptin\Tasks\Main;
use WP_UnitTestCase;

/** Tests real database claims with independently loaded, stale task objects. */
class Test_Task_Claim extends WP_UnitTestCase {

    private function create_task() {
        $task = Main::get(0);
        $task->set_hook('noptin_test_atomic_claim');
        $task->set_args('{}');
        $task->set_status('pending');
        // Prevent save() from executing it before the test explicitly calls process().
        $task->set_date_scheduled(time() + HOUR_IN_SECONDS);
        $task->save();
        return $task;
    }

    public function test_competing_stale_objects_execute_only_once() {
        global $wpdb;
        $first = $this->create_task();
        $second = Main::get($first->get_id());
        $this->assertNotSame($first, $second);
        $this->assertSame('pending', $second->get_status());
        $runs = 0;
        $before_calls = 0;
        $callback = function () use (&$runs) { ++$runs; };
        $before = function ($task) use ($first, $second, &$before_calls, $wpdb) {
            if ($task->get_id() !== $first->get_id()) {
                return;
            }
            ++$before_calls;
            $table = $task->get_collection()->get_db_table_name();
            $this->assertSame('running', $wpdb->get_var($wpdb->prepare("SELECT status FROM $table WHERE id = %d", $task->get_id())));
            // Interleave another worker after the claim but before the callback.
            if (1 === $before_calls) {
                $second->process();
            }
        };
        add_action('noptin_test_atomic_claim', $callback);
        add_action('noptin_tasks_before_execute', $before);
        try {
            $first->process();
            $second->process(); // Its stale pending state must not reopen a completed row.
            $this->assertSame(1, $runs);
            $this->assertSame(1, $before_calls);
            $this->assertSame('complete', Main::get($first->get_id())->get_status());
            $this->assertCount(2, Main::get($first->get_id())->get_logs());
        } finally {
            remove_action('noptin_test_atomic_claim', $callback);
            remove_action('noptin_tasks_before_execute', $before);
        }
    }

    public function test_non_pending_and_missing_rows_cannot_execute() {
        global $wpdb;
        $runs = 0;
        $callback = function () use (&$runs) { ++$runs; };
        add_action('noptin_test_atomic_claim', $callback);
        try {
            foreach (array('running', 'complete', 'failed', 'canceled', 'manual', null) as $status) {
                $task = $this->create_task();
                $table = $task->get_collection()->get_db_table_name();
                if (null === $status) {
                    $wpdb->delete($table, array('id' => $task->get_id()));
                } else {
                    $wpdb->update($table, array('status' => $status), array('id' => $task->get_id()));
                }
                // Deliberately leave the object and object cache showing pending.
                $task->process();
            }
            Main::get(0)->process();
            $this->assertSame(0, $runs);
        } finally {
            remove_action('noptin_test_atomic_claim', $callback);
        }
    }

    public function test_database_error_prevents_execution() {
        global $wpdb;
        $task = $this->create_task();
        $table = $task->get_collection()->get_db_table_name();
        $runs = 0;
        $callback = function () use (&$runs) { ++$runs; };
        $break_claim = function ($sql) use ($table) {
            if (0 === strpos($sql, "UPDATE `$table` SET") && false !== strpos($sql, "'running'")) {
                return 'INVALID SQL FOR TASK CLAIM TEST';
            }
            return $sql;
        };
        add_action('noptin_test_atomic_claim', $callback);
        add_filter('query', $break_claim);
        $previous = $wpdb->suppress_errors(true);
        try {
            $task->process();
            $this->assertNotEmpty($wpdb->last_error);
            $this->assertSame(0, $runs);
            $this->assertSame('pending', $task->get_status());
        } finally {
            $wpdb->suppress_errors($previous);
            remove_filter('query', $break_claim);
            remove_action('noptin_test_atomic_claim', $callback);
        }
    }

    public function test_failed_task_can_be_retried_as_a_new_task() {
        $task = $this->create_task();
        $fail = function () { throw new \Exception('Expected claim test failure'); };
        add_action('noptin_test_atomic_claim', $fail);
        try {
            $task->process();
            $this->assertSame('failed', Main::get($task->get_id())->get_status());
        } finally {
            remove_action('noptin_test_atomic_claim', $fail);
        }
        $retry = Main::retry_task($task, HOUR_IN_SECONDS);
        $runs = 0;
        $callback = function () use (&$runs) { ++$runs; };
        add_action('noptin_test_atomic_claim', $callback);
        try {
            $retry->process();
            $this->assertNotEquals($task->get_id(), $retry->get_id());
            $this->assertSame(1, $runs);
            $this->assertSame('complete', Main::get($retry->get_id())->get_status());
        } finally {
            remove_action('noptin_test_atomic_claim', $callback);
        }
    }
}
