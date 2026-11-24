<?php
/** End-to-end form submission policy across subscriber and confirmation states. */

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

use Hizzle\Noptin\Emails\Email;
use Hizzle\Noptin\Forms\Listener;

/** Exercise saved forms through their public REST submission handler. */
class Test_Form_Submissions extends Noptin_Regression_Test_Case {

	/**
	 * Submit every subscriber/global/form/update/delivery combination.
	 * Both transports are intercepted after rendering; no mail leaves the suite.
	 */
	public function test_submission_matrix() {
		$global = false;
		$custom = false;
		$setting_up = false;
		$deliveries = array();
		$pending_events = array();
		$campaign_id = 0;
		$rule = null;
		$cases = 0;
		// Custom subscriber confirmation campaigns require a verified Premium license.
		add_filter( 'pre_option_noptin_helper_data', static function () { return array( 'license_key' => 'form-confirmation-test' ); }, PHP_INT_MAX );
		add_filter( 'pre_transient_noptin_license_form-confirmation-test', static function () {
			return (object) array( 'is_active' => true, 'is_active_on_site' => true, 'has_expired' => false );
		}, PHP_INT_MAX );

		add_filter( 'get_noptin_option_double_optin', static function () use ( &$global ) { return $global; } );
		add_filter( 'use_custom_noptin_double_optin_email', static function () use ( &$custom ) { return $custom; } );
		add_filter( 'get_noptin_option_double_optin_email_subject', static function () { return 'Built-in form confirmation'; } );
		add_filter( 'noptin_disable_cookies', '__return_true' );
		add_filter( 'noptin_should_send_double_optin_email', static function ( $send ) use ( &$setting_up ) { return $send && ! $setting_up; } );
		add_filter( 'noptin_email_sending_function', static function () use ( &$deliveries ) {
			return static function ( $recipients, $subject, $message, $headers, $attachments ) use ( &$deliveries ) {
				$deliveries[] = compact( 'recipients', 'subject', 'message' );
				return true;
			};
		}, PHP_INT_MAX );
		add_action( 'noptin_subscriber_status_set_to_pending', static function ( $subscriber, $previous ) use ( &$pending_events ) {
			$pending_events[] = array( $subscriber->get_id(), (bool) $subscriber->get_confirmed(), $previous );
		}, 100, 2 );
		// Target only this test's actual custom email rule, including during fixture creation.
		add_filter( 'noptin_automation_trigger_args', static function ( $args, $trigger ) use ( &$custom, &$setting_up, &$rule ) {
			if ( 'noptin_subscriber_status_set_to_pending' === $trigger->get_id() ) {
				$args['rule_id'] = $custom && ! $setting_up && $rule ? $rule->get_id() : 0;
			}
			return $args;
		}, 100, 2 );

		try {
			wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
			$content = '<p>Confirm your subscription: <a href="[[subscriber.confirm_subscription_url]]">Confirm</a></p>';
			$campaign = new Email( array(
				'author' => get_current_user_id(),
				'type' => 'automation',
				'status' => 'publish',
				'name' => 'Form matrix custom confirmation',
				'subject' => 'Custom form confirmation [[subscriber.email]]',
				'content' => $content,
				'options' => array(
					'automation_type' => 'automation_rule_noptin_subscriber_status_set_to_pending',
					'email_sender' => 'manual_recipients',
					'recipients' => '[[subscriber.email]]',
					'email_type' => 'normal',
					'content_normal' => $content,
					'template' => 'paste',
				),
			) );
			$campaign->save();
			$campaign_id = $campaign->id;
			$this->assertGreaterThan( 0, $campaign_id );
			$rule = noptin_get_automation_rule( (int) $campaign->get( 'automation_rule' ) );
			$this->assertFalse( is_wp_error( $rule ) );
			$this->assertTrue( $rule->exists() );
			$this->assertSame( 'noptin_subscriber_status_set_to_pending', $rule->get_trigger_id() );
			$this->assertSame( 'email', $rule->get_action_id() );
			wp_set_current_user( 0 );

			foreach ( array_merge( array( 'new' ), array_keys( noptin_get_subscriber_statuses() ) ) as $status ) {
				foreach ( array( false, true ) as $global_value ) {
					$global = $global_value;
					foreach ( array( null, 'required', 'disabled' ) as $mode ) {
						foreach ( array( false, true ) as $update ) {
							foreach ( array( false, true ) as $custom_value ) {
								$custom = $custom_value;
								// Already-subscribed single-opt-in contacts can still be unconfirmed.
								foreach ( 'new' === $status ? array( false ) : array( false, true ) as $confirmed ) {
									$label = wp_json_encode( compact( 'status', 'global', 'mode', 'update', 'custom', 'confirmed' ) );
									$form_id = 0;
									$email = 'form-matrix-' . wp_generate_uuid4() . '@example.test';
									try {
										$setting_up = true;
										$original_id = 0;
										if ( 'new' !== $status ) {
											$record = noptin_get_subscriber( 0 );
											$record->set_email( $email );
											$record->set_first_name( 'Saved name' );
											$record->set_confirmed( $confirmed );
											$record->set_status( $status );
											$this->assertFalse( is_wp_error( $record->save() ), $label );
											$original_id = $record->get_id();
											$this->assertSame( $status, noptin_get_subscriber( $original_id )->get_status(), $label );
										} else {
											$this->assertFalse( noptin_get_subscriber( $email )->exists(), $label );
										}
										$form_id = self::factory()->post->create( array( 'post_type' => 'noptin-form', 'post_status' => 'publish' ) );
										$settings = array( 'fields' => 'email,first_name', 'subscribeAction' => 'message', 'update_existing' => $update );
										if ( null !== $mode ) {
											$settings['doubleOptin'] = $mode;
										}
										update_post_meta( $form_id, '_noptin_state', $settings );
										$setting_up = false;
										$deliveries = array();
										$pending_events = array();
										unset( $_GET['noptin_key'] );
										list( $listener, $response ) = $this->submit_form( $form_id, $email, $label );
										$record = noptin_get_subscriber( $email );
										$effective_optin = null === $mode ? $global : 'required' === $mode;
										$is_subscription = in_array( $status, array( 'new', 'unsubscribed' ), true );
										$needs_email = $is_subscription && $effective_optin;
										$expected_status = $is_subscription ? ( $effective_optin ? 'pending' : 'subscribed' ) : $status;
										$this->assertTrue( $record->exists(), $label );
										$this->assertSame( $expected_status, $record->get_status(), $label );
										$this->assertSame( $needs_email ? false : $confirmed, (bool) $record->get_confirmed(), $label );
										$this->assertSame( 'new' === $status || $update ? 'Submitted name' : 'Saved name', $record->get_first_name(), $label );
										$this->assertSame( $is_subscription ? 'subscribed' : ( $update ? 'updated' : 'already_subscribed' ), $listener->last_event, $label );
										if ( $original_id ) {
											$this->assertSame( $original_id, $record->get_id(), $label );
										}
										$this->assertSame( $record->get_id(), (int) get_noptin_subscriber_id_by_email( $email ), $label );
										$this->assertSame( $global, noptin_has_enabled_double_optin(), $label );
										$this->assertSame( $settings, get_post_meta( $form_id, '_noptin_state', true ), $label );
										$this->assertSame( 'new' === $status && ! $effective_optin, isset( $_GET['noptin_key'] ), $label );
										$this->assertCount( $needs_email ? 1 : 0, $deliveries, $label );
										$this->assertCount( $needs_email ? 1 : 0, $pending_events, $label );
										if ( $needs_email ) {
											$this->assertSame( $custom ? 'Custom form confirmation ' . $email : 'Built-in form confirmation', $deliveries[0]['subject'], $label );
											$this->assertStringContainsString( $email, wp_json_encode( $deliveries[0]['recipients'] ), $label );
											$this->assertStringContainsString( 'href=', $deliveries[0]['message'], $label );
											$this->assertStringNotContainsString( '[[subscriber.confirm_subscription_url]]', $deliveries[0]['message'], $label );
											$this->assertSame( false, $pending_events[0][1], $label );
											$this->assertSame( $record->get_id(), $pending_events[0][0], $label );
											if ( 'unsubscribed' === $status ) {
												$this->assertSame( 'unsubscribed', $pending_events[0][2], $label );
											}
											$this->assertStringNotContainsString( $record->get_confirm_key(), wp_json_encode( $response ), $label );
										}

										// A second submission cannot confirm a pending record or resend either email.
										$deliveries = array();
										$pending_events = array();
										unset( $_GET['noptin_key'] );
										$this->submit_form( $form_id, $email, $label );
										$this->assertSame( $expected_status, noptin_get_subscriber( $email )->get_status(), $label );
										$this->assertCount( 0, $deliveries, $label );
										$this->assertCount( 0, $pending_events, $label );
										$this->assertArrayNotHasKey( 'noptin_key', $_GET, $label );
										if ( $needs_email ) {
											confirm_noptin_subscriber_email( $record->get_id() );
											$record = noptin_get_subscriber( $email );
											$this->assertSame( 'subscribed', $record->get_status(), $label );
											$this->assertTrue( (bool) $record->get_confirmed(), $label );
											$this->assertCount( 0, $deliveries, $label );
										}
										++$cases;
									} finally {
										$setting_up = false;
										$record = noptin_get_subscriber( $email );
										if ( $record->exists() ) {
											$record->delete();
										}
										if ( $form_id ) {
											wp_delete_post( $form_id, true );
										}
									}
								}
							}
						}
					}
				}
			}
			$this->assertSame( ( 1 + 2 * count( noptin_get_subscriber_statuses() ) ) * 2 * 3 * 2 * 2, $cases );
		} finally {
			if ( $rule && ! is_wp_error( $rule ) && $rule->exists() ) {
				// Remove only jobs owned by this fixture, including jobs from failed assertions.
				do {
					$tasks = Hizzle\Noptin\Tasks\Main::query( array( 'hook' => 'noptin_run_automation_rule', 'primary_id' => $rule->get_id(), 'per_page' => 100 ) );
					foreach ( $tasks as $task ) {
						$task->delete();
					}
				} while ( count( $tasks ) === 100 );
				$rule->delete();
			}
			if ( $campaign_id ) {
				wp_delete_post( $campaign_id, true );
			}
		}
	}

	/**
	 * Submit saved form settings, with a forged public opt-in value to verify policy ownership.
	 *
	 * @param int    $form_id Saved form ID.
	 * @param string $email Submitted address.
	 * @param string $label Matrix case for assertion failures.
	 * @return array Listener and REST response data.
	 */
	private function submit_form( $form_id, $email, $label ) {
		$listener = new Listener();
		$request = new WP_REST_Request( 'POST', '/noptin/v1/form' );
		$request->set_body_params( array(
			'source' => $form_id,
			'noptin_fields' => array( 'email' => $email, 'first_name' => 'Submitted name' ),
			'doubleOptin' => 'disabled',
			'noptin_timestamp' => time() - 5,
			'noptin_submitted' => time(),
			'noptin_ign' => '',
			'noptin_nonce' => wp_create_nonce( 'noptin_subscription_nonce' ),
		) );
		$_REQUEST['noptin_nonce'] = $request['noptin_nonce'];
		$response = $listener->handle_endpoint( $request )->get_data();
		$this->assertTrue( $response['success'], $label . ': ' . wp_json_encode( $response ) );
		return array( $listener, $response );
	}
}
