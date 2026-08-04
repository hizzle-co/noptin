<?php

namespace Hizzle\Noptin\Tests\Automation_Rules;

require_once __DIR__ . '/includes/class-actions-test-case.php';

use Hizzle\Noptin\Emails\Email;
use Hizzle\Noptin\Tasks\Main as Tasks_Main;

/**
 * Tests for deferring recipient-specific automation emails.
 */
class Test_Email_Deferral extends Actions_Test_Case {

	/**
	 * Campaign IDs created by a test.
	 *
	 * @var int[]
	 */
	private $campaign_ids = array();

	public function tear_down() {
		foreach ( $this->campaign_ids as $campaign_id ) {
			wp_delete_post( $campaign_id, true );
		}

		parent::tear_down();
	}

	public function test_bulk_email_is_not_deferred_for_pending_trigger_subject() {
		$this->create_pending_subscriber( 'author@example.com' );
		$rule = $this->create_email_rule( 'noptin', '' );

		$task = $this->schedule_rule( $rule, 'author@example.com' );

		$this->assertSame( 'pending', $task->get_status() );
	}

	public function test_direct_email_to_admin_is_not_deferred_for_pending_trigger_subject() {
		$this->create_pending_subscriber( 'author@example.com' );
		$rule = $this->create_email_rule( 'manual_recipients', 'admin@example.com' );

		$task = $this->schedule_rule( $rule, 'author@example.com' );

		$this->assertSame( 'pending', $task->get_status() );
	}

	public function test_direct_email_is_deferred_when_an_actual_recipient_is_pending() {
		$this->create_pending_subscriber( 'subscriber@example.com' );
		$rule = $this->create_email_rule( 'manual_recipients', 'admin@example.com, subscriber@example.com' );

		$task = $this->schedule_rule( $rule, 'author@example.com' );

		$this->assertSame( 'manual', $task->get_status() );
		$this->assertSame( 'author@example.com', $task->get_subject() );
		$this->assertSame( 'subscriber@example.com', $task->get_meta( 'deferred_recipient' ) );

		$subscriber = noptin_get_subscriber( 'subscriber@example.com' );
		$subscriber->set_status( 'subscribed' );
		$subscriber->save();

		$this->assertSame( 'pending', Tasks_Main::get( $task->get_id() )->get_status() );
	}

	private function create_email_rule( $sender, $recipients ) {
		$campaign = new Email(
			array(
				'author'  => 1,
				'type'    => 'newsletter',
				'status'  => 'publish',
				'name'    => 'Automation email deferral test',
				'subject' => 'Test subject',
				'content' => 'Test content',
				'options' => array(
					'email_sender'   => $sender,
					'recipients'     => $recipients,
					'email_type'     => 'normal',
					'content_normal' => 'Test content',
					'template'       => 'paste',
				),
			)
		);
		$campaign->save();
		$this->campaign_ids[] = $campaign->id;

		return $this->create_rule(
			'email',
			array( 'automated_email_id' => $campaign->id )
		);
	}

	private function create_pending_subscriber( $email ) {
		add_noptin_subscriber(
			array(
				'email'  => $email,
				'status' => 'pending',
			)
		);
	}

	private function schedule_rule( $rule, $subject ) {
		$trigger = $rule->get_trigger();
		$args    = $trigger->prepare_trigger_args( $subject, array() );
		$result  = Tasks_Main::run_automation_rule( $subject, $rule, $args, $trigger );

		$this->assertTrue( $result );

		$tasks = Tasks_Main::query(
			array(
				'hook'       => 'noptin_run_automation_rule',
				'primary_id' => $rule->get_id(),
			)
		);
		$this->assertCount( 1, $tasks );

		return $tasks[0];
	}
}
