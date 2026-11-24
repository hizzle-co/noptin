<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

use Hizzle\Noptin\Subscribers\Bounce_Handler;

/** Exercises authenticated bounce endpoints without external HTTP or mail. */
class Test_Bounce_Endpoint extends Noptin_Regression_Test_Case {
	/** @var array Captured HTTP requests. */
	private $requests = array();
	/** @var int Mock HTTP response status. */
	private $http_status = 200;

	/** Registers an HTTP interceptor that never contacts a real service. */
	public function set_up() {
		parent::set_up();
		remove_filter( 'pre_http_request', array( $this, 'block_regression_http' ), PHP_INT_MAX );
		add_filter( 'pre_http_request', array( $this, 'intercept_http' ), PHP_INT_MAX, 3 );
	}

	/**
	 * Records HTTP arguments and supplies an isolated response.
	 *
	 * @param mixed  $preempt Existing response.
	 * @param array  $args HTTP arguments.
	 * @param string $url Requested URL.
	 * @return array
	 */
	public function intercept_http( $preempt, $args, $url ) {
		$this->requests[] = array( 'url' => $url, 'args' => $args );
		return array( 'headers' => array(), 'body' => '', 'response' => array( 'code' => $this->http_status, 'message' => '' ) );
	}

	/**
	 * Sends raw JSON through the real public REST route.
	 *
	 * @param string $service Provider slug.
	 * @param array  $payload Provider payload.
	 * @param string $content_type Request content type.
	 * @return WP_REST_Response
	 */
	private function request( $service, $payload, $content_type = 'application/json' ) {
		$request = new WP_REST_Request( 'POST', '/noptin/v1/bounce_handler/' . $service . '/' . noptin_encrypt( 'noptin' ) );
		$request->set_header( 'Content-Type', $content_type );
		$request->set_body( wp_json_encode( $payload ) );
		return rest_do_request( $request );
	}

	/** Rejects dangerous confirmation targets before making any HTTP request. */
	public function test_ses_rejects_non_sns_urls_without_network_requests() {
		$query = '/?Action=ConfirmSubscription&TopicArn=arn:aws:sns:us-east-1:123456789012:test&Token=test';
		foreach ( array(
			'http://sns.us-east-1.amazonaws.com' . $query,
			'https://127.0.0.1' . $query,
			'https://169.254.169.254/latest/meta-data/',
			'https://[::1]' . $query,
			'https://sns.us-east-1.amazonaws.com.attacker.test' . $query,
			'https://bucket.s3.amazonaws.com' . $query,
			'https://user:pass@sns.us-east-1.amazonaws.com' . $query,
			'https://sns.us-east-1.amazonaws.com:444' . $query,
			'https://sns.us-east-1.amazonaws.com/redirect' . $query,
			'https://sns.us-east-1.amazonaws.com' . $query . '#fragment',
			'https://sns.us-east-1.amazonaws.com/?Action=Publish&TopicArn=test&Token=test',
			'https://sns.us-east-1.amazonaws.com/?Action=ConfirmSubscription',
			null,
			array( 'https://sns.us-east-1.amazonaws.com' . $query ),
		) as $url ) {
			$response = $this->request( 'ses', array( 'Type' => 'SubscriptionConfirmation', 'SubscribeURL' => $url ), 'text/plain; charset=UTF-8' );
			$this->assertSame( 400, $response->get_status() );
		}
		$this->assertSame( array(), $this->requests );
	}

	/** Valid SNS URLs use safe HTTP, refuse redirects, and surface remote failures. */
	public function test_ses_confirmation_uses_safe_http_without_redirects() {
		$url = 'https://sns.us-east-1.amazonaws.com/?Action=ConfirmSubscription&TopicArn=arn:aws:sns:us-east-1:123456789012:test&Token=test';
		$response = $this->request( 'ses', array( 'Type' => 'SubscriptionConfirmation', 'SubscribeURL' => $url ), 'text/plain; charset=UTF-8' );
		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $this->requests );
		$this->assertTrue( $this->requests[0]['args']['reject_unsafe_urls'] );
		$this->assertSame( 0, $this->requests[0]['args']['redirection'] );
		$this->assertTrue( $this->requests[0]['args']['sslverify'] );
		$this->http_status = 302;
		$this->assertSame( 502, $this->request( 'ses', array( 'Type' => 'SubscriptionConfirmation', 'SubscribeURL' => $url ) )->get_status() );
		$this->assertCount( 2, $this->requests, 'A redirect response must not trigger another fetch.' );
	}

	/**
	 * Provider event fixtures cover permanent changes and ignored temporary events.
	 *
	 * @return array
	 */
	public function provider_events() {
		return array(
			array( 'brevo', array( 'event' => 'hard_bounce' ), 'bounced' ),
			array( 'brevo', array( 'event' => 'spam' ), 'complained' ),
			array( 'brevo', array( 'event' => 'unsubscribed' ), 'unsubscribed' ),
			array( 'brevo', array( 'event' => 'soft_bounce' ), 'subscribed' ),
			array( 'brevo', array( 'event' => 'deferred' ), 'subscribed' ),
			array( 'smtp2go', array( 'event' => 'bounce', 'bounce' => 'hard' ), 'bounced' ),
			array( 'smtp2go', array( 'event' => 'bounce', 'bounce' => 'soft' ), 'subscribed' ),
			array( 'smtp2go', array( 'event' => 'spam' ), 'complained' ),
			array( 'smtp2go', array( 'event' => 'unsubscribe' ), 'unsubscribed' ),
			array( 'postal', array( 'event' => 'MessageBounced', 'payload' => array( 'original_message' => array() ) ), 'bounced' ),
			array( 'postal', array( 'event' => 'MessageDeliveryFailed', 'payload' => array( 'status' => 'HardFail', 'message' => array() ) ), 'bounced' ),
			array( 'postal', array( 'event' => 'MessageDeliveryFailed', 'payload' => array( 'status' => 'SoftFail', 'message' => array() ) ), 'subscribed' ),
			array( 'postal', array( 'event' => 'MessageDelayed', 'payload' => array( 'message' => array() ) ), 'subscribed' ),
			array( 'tosend', array( 'type' => 'bounced', 'data' => array( 'is_hard_bounce' => true ) ), 'bounced' ),
			array( 'tosend', array( 'type' => 'bounced', 'data' => array( 'is_hard_bounce' => false ) ), 'subscribed' ),
			array( 'tosend', array( 'type' => 'bounced', 'data' => array( 'is_hard_bounce' => true, 'sender_fault' => true ) ), 'subscribed' ),
			array( 'tosend', array( 'type' => 'complaint', 'data' => array() ), 'complained' ),
			array( 'cloudflare', array( 'type' => 'cf.email.sending.message.bounced', 'payload' => array( 'bounce' => array( 'type' => 'hard' ) ) ), 'bounced' ),
			array( 'cloudflare', array( 'type' => 'cf.email.sending.message.complained' ), 'complained' ),
			array( 'cloudflare', array( 'type' => 'cf.email.sending.message.bounced', 'payload' => array( 'bounce' => array( 'type' => 'soft' ) ) ), 'subscribed' ),
			array( 'cloudflare', array( 'type' => 'cf.email.sending.message.bounced' ), 'subscribed' ),
			array( 'cloudflare', array( 'type' => 'cf.email.sending.message.deferred' ), 'subscribed' ),
			array( 'cloudflare', array( 'type' => 'cf.email.sending.message.failed' ), 'subscribed' ),
			array( 'cloudflare', array( 'type' => 'cf.email.sending.message.rejected' ), 'subscribed' ),
			array( 'cloudflare', array( 'type' => 'cf.email.sending.message.delivered' ), 'subscribed' ),
		);
	}

	/**
	 * @dataProvider provider_events
	 * @param string $service Provider.
	 * @param array  $payload Event.
	 * @param string $expected Expected subscriber status.
	 */
	public function test_provider_event_through_rest( $service, $payload, $expected ) {
		$subscriber = noptin_get_subscriber();
		$subscriber->set_email( 'bounce-' . wp_generate_uuid4() . '@example.test' );
		$subscriber->set_status( 'subscribed' );
		$subscriber->save();
		$email = $subscriber->get_email();
		$payload['email'] = $email;
		$payload['rcpt'] = $email;
		$payload['data']['email'] = $email;
		if ( 'cloudflare' === $service ) {
			$payload['payload']['recipient'] = $email;
		}
		if ( isset( $payload['payload']['original_message'] ) ) {
			$payload['payload']['original_message']['to'] = $email;
		} elseif ( isset( $payload['payload']['message'] ) ) {
			$payload['payload']['message']['to'] = $email;
		}
		$this->assertSame( 200, $this->request( $service, $payload )->get_status() );
		$this->assertSame( $expected, noptin_get_subscriber( $subscriber->get_id() )->get_status() );
		$this->assertSame( array(), $this->requests );
	}

	/** The selector keeps webhook URLs and per-provider guide links separate. */
	public function test_conditional_settings_and_legacy_url_compatibility() {
		$settings = Bounce_Handler::add_settings( array( 'general_email_info' => array( 'settings' => array() ) ) );
		$this->assertArrayNotHasKey( 'bounce_webhook_url', $settings['general_email_info']['settings'] );
		$group = $settings['bounce_handler_settings'];
		$this->assertSame( 'main', $group['sub_section'] );
		$services = Bounce_Handler::get_supported_services();
		$this->assertCount( 12, $services );
		foreach ( $services as $slug => $service ) {
			$field = $group['settings'][ 'bounce_handler_url_' . $slug ];
			$this->assertTrue( $field['readonly'] );
			$this->assertSame( $service['url'], $field['default'] );
			$this->assertStringContainsString( '/bounce_handler/' . $slug . '/', $field['default'] );
			$this->assertSame( $slug, $field['conditions'][0]['value'] );
			$this->assertStringContainsString( '/bounce-handling/', $field['description'] );
		}
	}

	/** Malformed envelopes are ignored and the private route still requires its code. */
	public function test_cloudflare_malformed_events_and_authentication() {
		foreach ( array(
			array(),
			array( 'type' => array( 'cf.email.sending.message.complained' ) ),
			array( 'type' => 'cf.email.sending.message.complained', 'payload' => 'invalid' ),
			array( 'type' => 'cf.email.sending.message.complained', 'payload' => array( 'recipient' => array( 'bad@example.test' ) ) ),
			array( 'type' => 'cf.email.sending.message.bounced', 'payload' => array( 'bounce' => 'hard' ) ),
		) as $payload ) {
			$response = $this->request( 'cloudflare', $payload );
			$this->assertSame( 200, $response->get_status() );
			$this->assertTrue( $response->get_data() );
		}
		$request = new WP_REST_Request( 'POST', '/noptin/v1/bounce_handler/cloudflare/invalid-code' );
		$this->assertContains( rest_do_request( $request )->get_status(), array( 401, 403 ) );
	}

	/** SparkPost batch nesting must be resolved afresh for every recipient. */
	public function test_sparkpost_batch_and_temporary_sendgrid_postmark_events() {
		$ids = array();
		$events = array();
		foreach ( range( 1, 2 ) as $index ) {
			$subscriber = noptin_get_subscriber();
			$subscriber->set_email( 'batch-' . wp_generate_uuid4() . '@example.test' );
			$subscriber->set_status( 'subscribed' );
			$subscriber->save();
			$ids[] = $subscriber->get_id();
			$events[] = array( 'msys' => array( 'message_event' => array( 'type' => 'out_of_band', 'rcpt_to' => $subscriber->get_email() ) ) );
		}
		$this->assertSame( 200, $this->request( 'sparkpost', $events )->get_status() );
		foreach ( $ids as $id ) {
			$this->assertSame( 'bounced', noptin_get_subscriber( $id )->get_status() );
		}
		$subscriber = noptin_get_subscriber( $ids[0] );
		$subscriber->set_status( 'subscribed' );
		$subscriber->save();
		$this->request( 'sendgrid', array( array( 'event' => 'bounce', 'type' => 'blocked', 'email' => $subscriber->get_email() ) ) );
		$this->assertSame( 'subscribed', noptin_get_subscriber( $ids[0] )->get_status() );
		$this->request( 'postmark', array( 'RecordType' => 'Bounce', 'Inactive' => false, 'Email' => $subscriber->get_email() ) );
		$this->assertSame( 'subscribed', noptin_get_subscriber( $ids[0] )->get_status() );
	}
}
