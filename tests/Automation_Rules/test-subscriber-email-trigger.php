<?php

namespace Hizzle\Noptin\Tests\Automation_Rules;

use Hizzle\Noptin\Emails\Email;
use WP_UnitTestCase;

/**
 * Tests subscriber-triggered automated emails.
 */
class Test_Subscriber_Email_Trigger extends WP_UnitTestCase {

	/**
	 * Campaign IDs created by a test.
	 *
	 * @var int[]
	 */
	private $campaign_ids = array();

	public function set_up() {
		parent::set_up();

		noptin()->db()->delete_all( 'automation_rules' );
		noptin()->db()->delete_all( 'subscribers' );
		noptin()->db()->delete_all( 'tasks' );
		\Noptin_Test_Email_Sender::reset();
		\Noptin_Test_Email_Sender::register();
	}

	public function tear_down() {
		\Noptin_Test_Email_Sender::unregister();
		\Noptin_Test_Email_Sender::reset();

		foreach ( $this->campaign_ids as $campaign_id ) {
			wp_delete_post( $campaign_id, true );
		}

		noptin()->db()->delete_all( 'automation_rules' );
		noptin()->db()->delete_all( 'subscribers' );
		noptin()->db()->delete_all( 'tasks' );

		parent::tear_down();
	}

	public function test_subscriber_merge_tags_use_trigger_subscriber_for_specific_recipients() {
		$recipient_email = 'recipient@example.com';
		$trigger_email   = 'new-subscriber@example.com';

		add_noptin_subscriber(
			array(
				'email'  => $recipient_email,
				'name'   => 'Fixed Recipient',
				'status' => 'subscribed',
			)
		);

		$campaign = new Email(
			array(
				'author'  => 1,
				'type'    => 'automation',
				'status'  => 'publish',
				'name'    => 'Subscriber status email test',
				'subject' => 'Welcome [[subscriber.email]]',
				'content' => 'Triggered for [[subscriber.email]] ([[subscriber.name]])',
				'options' => array(
					'automation_type' => 'automation_rule_noptin_subscriber_status_set_to_subscribed',
					'email_sender'    => 'manual_recipients',
					'recipients'      => $recipient_email,
					'email_type'      => 'normal',
					'content_normal'  => 'Triggered for [[subscriber.email]] ([[subscriber.name]])',
					'template'        => 'paste',
				),
			)
		);
		$campaign->save();
		$this->campaign_ids[] = $campaign->id;

		$rule = noptin_get_automation_rule( (int) $campaign->get( 'automation_rule' ) );
		$this->assertFalse( is_wp_error( $rule ) );
		$this->assertTrue( $rule->exists() );
		$this->assertSame( 'noptin_subscriber_status_set_to_subscribed', $rule->get_trigger_id() );
		$this->assertSame( 'email', $rule->get_action_id() );
		$this->assertSame( $campaign->id, (int) $rule->get_action_setting( 'automated_email_id' ) );

		$subscriber_id = add_noptin_subscriber(
			array(
				'email'  => $trigger_email,
				'name'   => 'Trigger Subscriber',
				'status' => 'pending',
			)
		);
		$subscriber    = noptin_get_subscriber( $subscriber_id );
		$subscriber->set_status( 'subscribed' );
		$subscriber->save();

		$this->assertSame( array( $recipient_email ), \Noptin_Test_Email_Sender::$recipients );
		$this->assertSame( 'Welcome ' . $trigger_email, \Noptin_Test_Email_Sender::$subject );
		$this->assertStringContainsString( 'Triggered for ' . $trigger_email . ' (Trigger Subscriber)', \Noptin_Test_Email_Sender::$message );
		$this->assertStringNotContainsString( 'Fixed Recipient', \Noptin_Test_Email_Sender::$message );
	}
}
