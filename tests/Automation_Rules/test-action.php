<?php

namespace Hizzle\Noptin\Tests\Automation_Rules;

require_once __DIR__ . '/includes/class-actions-test-case.php';

use Hizzle\Noptin\Automation_Rules\Actions\Main as Actions_Main;
use WP_Error;

/**
 * Tests for the base automation action behavior.
 */
class Test_Action extends Actions_Test_Case {

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
}
