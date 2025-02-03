<?php

namespace Hizzle\Noptin\Tests\Tasks;

use Hizzle\Noptin\Tasks\Main;
use WP_UnitTestCase;

class Test_Tasks extends WP_UnitTestCase {

    /** @var \Hizzle\Noptin\Tasks\Task */
    protected $task;

    public function setUp(): void {
        parent::setUp();
        $this->task = Main::get(0);
    }

    public function test_get_set_hook() {
        $hook = 'test_hook';
        $this->task->set_hook($hook);
        $this->assertEquals($hook, $this->task->get_hook());
    }

    public function test_get_set_subject() {
        $subject = 'test_subject';
        $this->task->set_subject($subject);
        $this->assertEquals($subject, $this->task->get_subject());
    }

    public function test_get_set_primary_id() {
        $id = 123;
        $this->task->set_primary_id($id);
        $this->assertEquals($id, $this->task->get_primary_id());
    }

    public function test_get_set_status() {
        $status = 'pending';
        $this->task->set_status($status);
        $this->assertEquals($status, $this->task->get_status());
    }

    public function test_get_set_args() {
        $args = ['key' => 'value'];
        $this->task->set_args(wp_json_encode($args));
        $this->assertEquals(wp_json_encode($args), $this->task->get_args());
    }

    public function test_get_set_single_arg() {
        $args = ['key' => 'value'];
        $this->task->set_args(wp_json_encode($args));

        // Test getting existing arg
        $this->assertEquals('value', $this->task->get_arg('key'));

        // Test getting non-existent arg
        $this->assertNull($this->task->get_arg('nonexistent'));

        // Test setting new arg
        $this->task->set_arg('new_key', 'new_value');
        $this->assertEquals('new_value', $this->task->get_arg('new_key'));
    }

    public function test_has_expired() {
        // Test with past date
        $this->task->set_date_scheduled(time() - 3600);
        $this->assertTrue($this->task->has_expired());

        // Test with future date
        $this->task->set_date_scheduled(time() + 3600);
        $this->assertFalse($this->task->has_expired());
    }

    public function test_get_set_logs() {
        // Test empty logs
        $this->assertEmpty($this->task->get_logs());

        // Test adding log
        $message = 'Test log message';
        $this->task->add_log($message);
        $logs = $this->task->get_logs();
        $this->assertCount(1, $logs);
        $this->assertEquals($message, $logs[0]['message']);

        // Test get last log
        $this->assertEquals($message, $this->task->get_last_log());
    }

    public function test_clone() {
        $this->task->set_hook('test_hook');
        $this->task->set_status('pending');
        $this->task->set_args(wp_json_encode(['test' => 'value']));
        $this->task->save();

        $cloned_task = $this->task->clone();

        // Verify cloned task has same properties but different ID
        $this->assertEquals($this->task->get_hook(), $cloned_task->get_hook());
        $this->assertEquals($this->task->get_status(), $cloned_task->get_status());
        $this->assertEquals($this->task->get_args(), $cloned_task->get_args());
        $this->assertNotEquals($this->task->get_id(), $cloned_task->get_id());
    }
}
