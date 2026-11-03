<?php

namespace Hizzle\Noptin\Tests\Subscribers;

use WP_UnitTestCase;
use WP_REST_Request;
use Hizzle\Noptin\Subscribers\Bounce_Handler;

/** Bounce webhooks update independent, active subscriber fixtures. */
class Test_BounceHandler extends WP_UnitTestCase {

	private $subscriber_id;
	private $email;

	public function set_up() {
		parent::set_up();
		add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
		$this->email = 'bounce-' . wp_generate_uuid4() . '@example.test';
		$subscriber = noptin_get_subscriber();
		$subscriber->set_email( $this->email );
		$subscriber->set_status( 'subscribed' );
		$subscriber->set_confirmed( true );
		$subscriber->save();
		$this->subscriber_id = $subscriber->get_id();
		$this->assertGreaterThan( 0, $this->subscriber_id );
	}

	public function tear_down() {
		try {
			if ( $this->subscriber_id ) {
				noptin_get_subscriber( $this->subscriber_id )->delete();
			}
		} finally {
			remove_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
			parent::tear_down();
		}
	}

	public function testMailgunBounceHandling() {
		$request = new WP_REST_Request( 'POST', '/noptin/v1/bounce_handler/mailgun/dummy_code' );
		$request->set_body_params( array(
			'event-data' => array(
				'event' => 'failed',
				'severity' => 'permanent',
				'recipient' => $this->email,
				'user-variables' => array( 'noptin_campaign_id' => 123 ),
			),
		) );
		Bounce_Handler::handle_mailgun( $request );
		$this->assertSame( 'bounced', noptin_get_subscriber( $this->subscriber_id )->get_status() );
	}

	public function testSendgridSpamComplaint() {
		$request = new WP_REST_Request( 'POST', '/noptin/v1/bounce_handler/sendgrid/dummy_code' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array(
			array( 'event' => 'spamreport', 'email' => $this->email ),
		) ) );
		Bounce_Handler::handle_sendgrid( $request );
		$subscriber = noptin_get_subscriber( $this->subscriber_id );
		$this->assertSame( 'complained', $subscriber->get_status(), 'Subscriber was not marked as complained.' );
		$this->assertFalse( $subscriber->is_active(), 'Spam complaints must stop subscription delivery.' );
	}

	public function testElasticEmailUnsubscribe() {
		$request = new WP_REST_Request( 'POST', '/noptin/v1/bounce_handler/elasticemail/dummy_code' );
		$request->set_body_params( array(
			'status' => 'unsubscribed',
			'to' => $this->email,
			'category' => 'ManualCancel',
		) );
		Bounce_Handler::handle_elasticemail( $request );
		$this->assertSame( 'unsubscribed', noptin_get_subscriber( $this->subscriber_id )->get_status() );
	}
}
