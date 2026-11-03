<?php

namespace Hizzle\Noptin\Tests\Forms;

use Hizzle\Noptin\Forms\Listener;
use WP_REST_Request;
use WP_UnitTestCase;

/** Saved redirect choices preserve new and existing subscriber behavior. */
class Test_Form_Existing_Redirect extends WP_UnitTestCase {

	private $subscriber;
	private $request_globals;
	private $server_globals;

	public function set_up() {
		parent::set_up();
		$this->request_globals = $_REQUEST;
		$this->server_globals = $_SERVER;
		$_SERVER['HTTP_ACCEPT'] = 'application/json';
		add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
		add_filter( 'noptin_disable_cookies', '__return_true' );
		add_filter( 'noptin_has_enabled_double_optin', '__return_true' );
		$this->subscriber = noptin_get_subscriber( 0 );
		$this->subscriber->set_email( 'form-redirect-' . wp_generate_uuid4() . '@example.test' );
		$this->subscriber->set_status( 'subscribed' );
		$this->subscriber->set_confirmed( true );
		$this->subscriber->save();
	}

	public function tear_down() {
		try {
			$record = noptin_get_subscriber( $this->subscriber->get_email() );
			if ( $record->exists() ) {
				$record->delete();
			}
		} finally {
			$_REQUEST = $this->request_globals;
			$_SERVER = $this->server_globals;
			remove_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
			remove_filter( 'noptin_disable_cookies', '__return_true' );
			remove_filter( 'noptin_has_enabled_double_optin', '__return_true' );
			parent::tear_down();
		}
	}

	private function assert_redirect( $settings, $expected, $label ) {
		$listener = new Listener();
		$listener->cached = $settings;
		$request = new WP_REST_Request( 'POST', '/noptin/v1/form' );
		$request->set_body_params( array(
			'noptin_fields' => array( 'email' => $this->subscriber->get_email() ),
			'noptin_timestamp' => time() - 5,
			'noptin_submitted' => time(),
			'noptin_ign' => '',
			'noptin_nonce' => wp_create_nonce( 'noptin_subscription_nonce' ),
		) );
		$_REQUEST['noptin_nonce'] = $request['noptin_nonce'];
		$response = $listener->handle_endpoint( $request )->get_data();
		$this->assertTrue( $response['success'], $label . ': ' . wp_json_encode( $response ) );
		$this->assertSame( $expected, $response['data']['redirect_url'] ?? '', $label );
		$this->assertSame( $expected, $listener->get_redirect_url(), $label );
	}

	public function test_existing_and_new_subscriber_redirects() {
		$normal = home_url( '/confirm/' );
		$existing = home_url( '/already-subscribed/' );
		$base = array(
			'fields' => 'email',
			'subscribeAction' => 'redirect',
			'redirectUrl' => $normal,
			'redirectAlreadySubscribed' => true,
			'alreadySubscribedRedirectUrl' => $existing,
		);
		$this->assert_redirect( $base, $existing, 'Subscribed contact' );
		$this->assert_redirect( array_merge( $base, array( 'update_existing' => true ) ), $existing, 'Updated subscribed contact' );
		$legacy = $base;
		unset( $legacy['redirectAlreadySubscribed'], $legacy['alreadySubscribedRedirectUrl'] );
		$this->assert_redirect( $legacy, $normal, 'Old form without new settings' );
		$this->assert_redirect( array_merge( $base, array( 'redirectAlreadySubscribed' => false ) ), $normal, 'Disabled override retains URL' );
		$this->assert_redirect( array_merge( $base, array( 'alreadySubscribedRedirectUrl' => '  ' ) ), '', 'Blank override prevents redirect' );
		$this->assert_redirect( array_merge( $base, array( 'subscribeAction' => 'message' ) ), '', 'Message mode' );
		$legacy['redirect'] = $normal;
		unset( $legacy['redirectUrl'], $legacy['subscribeAction'] );
		$this->assert_redirect( $legacy, $normal, 'Legacy redirect setting' );
		foreach ( array( 'pending', 'unsubscribed' ) as $status ) {
			foreach ( array( false, true ) as $update ) {
				$this->subscriber->set_status( $status );
				$this->subscriber->set_confirmed( false );
				$this->subscriber->save();
				$this->assert_redirect( array_merge( $base, array( 'update_existing' => $update ) ), $normal, $status . ' contact' );
			}
		}
		$this->subscriber->delete();
		$this->assert_redirect( $base, $normal, 'New contact' );
	}
}
