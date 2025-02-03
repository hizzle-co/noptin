<?php

namespace Hizzle\Noptin\Tests\Tasks;

use Hizzle\Noptin\Tasks\Main;
use WP_UnitTestCase;

class MainTest extends WP_UnitTestCase {

    /** @var \Hizzle\Noptin\Tasks\Main */
    protected $main;

    public function setUp(): void {
        parent::setUp();
        $this->main = $GLOBALS['noptin_tasks'];
    }

    public function test_filter_cron_schedules() {
        $schedules = Main::filter_cron_schedules([]);

        $this->assertArrayHasKey('every_minute', $schedules);
        $this->assertEquals(60, $schedules['every_minute']['interval']);
    }

    public function test_schedule_task() {
        $hook = 'test_hook';
        $args = ['value'];

        $task = Main::schedule_task($hook, $args);

        $this->assertInstanceOf('Hizzle\Noptin\Tasks\Task', $task);
        $this->assertEquals($hook, $task->get_hook());
        $this->assertEquals('pending', $task->get_status());
        $this->assertEquals(wp_json_encode($args), $task->get_args());
    }

    public function test_get_next_scheduled_task() {
        $hook = 'test_hook';
        $args = ['value'];

        // Schedule a task
        Main::schedule_task($hook, $args);

        // Get next scheduled task
        $next_task = Main::get_next_scheduled_task($hook, $args);

        $this->assertInstanceOf('Hizzle\Noptin\Tasks\Task', $next_task);
        $this->assertEquals($hook, $next_task->get_hook());

        // Verify task with wrong args is not found
        $wrong_args = ['wrong_args'];
        $task_wrong_args = Main::get_next_scheduled_task($hook, $wrong_args);
        $this->assertFalse($task_wrong_args);
    }

    public function test_delete_scheduled_task() {
        $hook = 'test_hook';
        $args = ['value'];

        // Schedule a task
        Main::schedule_task($hook, $args);

        // Delete the task
        Main::delete_scheduled_task($hook, $args);

        // Verify task was deleted
        $next_task = Main::get_next_scheduled_task($hook, $args);
        $this->assertFalse($next_task);
    }

    public function test_get_statuses() {
        $statuses = Main::get_statuses();

        $expected_statuses = ['pending', 'running', 'failed', 'canceled', 'complete'];
        foreach ($expected_statuses as $status) {
            $this->assertArrayHasKey($status, $statuses);
        }
    }

    public function test_retry_task() {
        // Create and save original task
        $original_task = Main::schedule_task('test_hook', ['test' => 'value']);

        // Retry the task
        $retried_task = Main::retry_task($original_task, 0);

        // Verify retried task properties
        $this->assertInstanceOf('Hizzle\Noptin\Tasks\Task', $retried_task);
        $this->assertEquals($original_task->get_hook(), $retried_task->get_hook());
        $this->assertEquals('pending', $retried_task->get_status());
        $this->assertNotEquals($original_task->get_id(), $retried_task->get_id());
    }

    public function test_add_tasks_table() {
        $schema = Main::add_tasks_table([]);

        $this->assertArrayHasKey('tasks', $schema);
        $this->assertEquals('\Hizzle\Noptin\Tasks\Task', $schema['tasks']['object']);

        // Verify required columns exist
        $required_columns = ['id', 'hook', 'status', 'args', 'date_created', 'date_modified', 'date_scheduled'];
        foreach ($required_columns as $column) {
            $this->assertArrayHasKey($column, $schema['tasks']['props']);
        }
    }

    public function test_filter_tasks_collection_js_params() {
        $params = [
            'hidden' => [],
            'schema' => [
                ['name' => 'hook'],
                ['name' => 'other']
            ]
        ];

        $filtered = Main::filter_tasks_collection_js_params($params);

        $this->assertContains('args_hash', $filtered['hidden']);
        $this->assertContains('date_modified', $filtered['hidden']);
        $this->assertEquals('hook', $filtered['schema'][0]['name']);
        $this->assertTrue($filtered['schema'][0]['is_primary']);
    }

    public function test_background_action_functions() {
        $hook = 'test_background_hook';
        $args = ['value'];

        // Test do_noptin_background_action
        $task = do_noptin_background_action($hook, ...$args);
        $this->assertInstanceOf('Hizzle\Noptin\Tasks\Task', $task);
        $this->assertEquals($hook, $task->get_hook());
        $this->assertEquals('pending', $task->get_status());
        $this->assertEquals(wp_json_encode($args), $task->get_args());

        // Test next_scheduled_noptin_background_action
        $next_time = next_scheduled_noptin_background_action($hook, ...$args);
        $this->assertNotFalse($next_time);
        $this->assertIsNumeric($next_time);

        // Test schedule_noptin_background_action
        $future_time = time() + 3600; // 1 hour from now
        $scheduled_task = schedule_noptin_background_action($future_time, $hook, ...$args);

        $this->assertInstanceOf('Hizzle\Noptin\Tasks\Task', $scheduled_task);
        $this->assertEquals($hook, $scheduled_task->get_hook());
        $this->assertEquals('pending', $scheduled_task->get_status());

        // Test schedule_noptin_recurring_background_action
        $interval = 3600; // 1 hour
        $recurring_task = schedule_noptin_recurring_background_action($interval, $future_time, $hook, $args);
        $this->assertInstanceOf('Hizzle\Noptin\Tasks\Task', $recurring_task);
        $this->assertEquals($hook, $recurring_task->get_hook());
        $this->assertEquals('pending', $recurring_task->get_status());
        $this->assertEquals($interval, $recurring_task->get_meta('interval'));

        // Test delete_noptin_background_action
        delete_noptin_background_action($hook, ...$args);
        $this->assertFalse(next_scheduled_noptin_background_action($hook, ...$args));

    }
}
