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

	public function test_defaults_ignore_unsaved_email_without_matching_subtype() {
		$email = new Email( array( 'type' => 'automation' ) );

		$this->assertSame( '', $email->get( 'missing_property' ) );
	}

	public function test_get_sub_type_uses_only_the_sub_type_filter() {
		$property_filter_calls = 0;
		$property_filter       = function ( $value, $key ) use ( &$property_filter_calls ) {
			if ( 'automation_type' === $key ) {
				++$property_filter_calls;
			}

			return $value;
		};
		$sub_type_filter       = function () {
			return 'filtered_sub_type';
		};

		add_filter( 'noptin_get_email_prop', $property_filter, 1000, 2 );
		add_filter( 'noptin_automation_email_sub_type_original_sub_type', $sub_type_filter );

		try {
			$email = new Email(
				array(
					'type'            => 'automation',
					'automation_type' => 'original_sub_type',
				)
			);

			$this->assertSame( 'filtered_sub_type', $email->get_sub_type() );
			$this->assertSame( 0, $property_filter_calls );
		} finally {
			remove_filter( 'noptin_get_email_prop', $property_filter, 1000 );
			remove_filter( 'noptin_automation_email_sub_type_original_sub_type', $sub_type_filter );
		}
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
