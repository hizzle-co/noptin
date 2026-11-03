<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** Regression coverage for form double optin. */
class Test_Form_Double_Optin extends Noptin_Regression_Test_Case {

	public function test_saved_form_confirmation_policy() {
		$assert = array( $this, 'assertTrue' );
		$global = false;
		$custom = false;
		$mails = 0;

		$emails = array();
		$attempts = array();
		add_filter( 'get_noptin_option_double_optin', static function () use ( &$global ) { return $global; } );
		add_filter( 'use_custom_noptin_double_optin_email', static function () use ( &$custom ) { return $custom; } );
		add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
		add_filter( 'noptin_disable_cookies', '__return_true' );
		// Count built-in confirmation attempts before rendering/delivery; no emails leave this test.
		add_filter( 'noptin_should_send_double_optin_email', static function ( $send, $subscriber, $force ) use ( &$mails, &$attempts ) {
			$attempts[] = array( $subscriber->get_status(), $force );
			if ( 'pending' === $subscriber->get_status() ) {
				++$mails;
			}
			return false;
		}, PHP_INT_MAX, 3 );
		$form = wp_insert_post( array( 'post_type' => 'noptin-form', 'post_status' => 'publish', 'post_title' => 'Temporary opt-in regression' ) );
		$configure = static function ( $mode, $update = false ) use ( $form ) {
			$settings = array( 'fields' => 'email', 'subscribeAction' => 'message', 'update_existing' => $update );
			if ( null !== $mode ) {
				$settings['doubleOptin'] = $mode;
			}
			update_post_meta( $form, '_noptin_state', $settings );
		};
		$new_email = static function () use ( &$emails ) {
			$email = 'codex-optin-' . wp_generate_uuid4() . '@example.test';
			$emails[] = $email;
			return $email;
		};
		$submit = static function ( $email, $source = null ) use ( $form, &$mails, $assert ) {
			unset( $_GET['noptin_key'] );
			$mails = 0;
			$listener = new \Hizzle\Noptin\Forms\Listener();
			$request = new WP_REST_Request( 'POST', '/noptin/v1/form' );
			$request->set_body_params( array(
				'source' => $source ?? $form,
				'noptin_fields' => array( 'email' => $email ),
				'doubleOptin' => 'disabled', // Public values must not override the saved form.
				'noptin_timestamp' => time() - 5,
				'noptin_submitted' => time(),
				'noptin_ign' => '',
				'noptin_nonce' => wp_create_nonce( 'noptin_subscription_nonce' ),
			) );
			$_REQUEST['noptin_nonce'] = $request['noptin_nonce'];
			if ( null !== $source ) {
				$listener->cached = array( 'fields' => 'email', 'doubleOptin' => 'required' );
			}
			$response = $listener->handle_endpoint( $request )->get_data();
			$assert( $response['success'], 'Submission failed: ' . wp_json_encode( $response ) );
			return noptin_get_subscriber( $email );
		};
		try {
			foreach ( array( false, true ) as $global_value ) {
				$global = $global_value;
				foreach ( array( null, 'global', 'required', 'disabled', 'invalid' ) as $mode ) {
					$configure( $mode );
					$pending = 'required' === $mode || ( 'disabled' !== $mode && $global );
					$record = $submit( $new_email() );
					$assert( $record->get_status() === ( $pending ? 'pending' : 'subscribed' ), 'Incorrect status for ' . var_export( array( $global, $mode ), true ) );
					$assert( $mails === ( $pending ? 1 : 0 ), 'Incorrect confirmation count.' );
					$assert( ! $record->get_confirmed(), 'Single opt-in must not claim email ownership was verified.' );
					$assert( isset( $_GET['noptin_key'] ) === ! $pending, 'Incorrect subscriber cookie eligibility.' );
				}
				$record = $submit( $new_email(), 'shortcode' );
				$assert( $record->get_status() === ( $global ? 'pending' : 'subscribed' ), 'Unsaved form must inherit global setting.' );
				$mails = 0;
				$id = add_noptin_subscriber( array( 'email' => $new_email(), 'source' => 'integration' ) );
				$assert( noptin_get_subscriber( $id )->get_status() === ( $global ? 'pending' : 'subscribed' ), 'Integration must inherit global setting.' );
			}

			foreach ( array( 'required', 'disabled' ) as $mode ) {
				foreach ( array( 'subscribed', 'pending', 'unsubscribed' ) as $status ) {
					foreach ( array( false, true ) as $update ) {
						$configure( $mode, $update );
						$record = noptin_get_subscriber( 0 );
						$record->set_email( $new_email() );
						$record->set_status( $status );
						$record->set_confirmed( 'pending' !== $status );
						$record->save();
						$record = $submit( $record->get_email() );
						$resubscribe = $update && 'unsubscribed' === $status;
						$expected = $resubscribe ? ( 'required' === $mode ? 'pending' : 'subscribed' ) : $status;
						$assert( $record->get_status() === $expected, 'Existing contact status incorrect.' );
						$assert( $mails === ( $resubscribe && 'required' === $mode ? 1 : 0 ), 'Existing contact confirmation count incorrect.' );
						$assert( ! isset( $_GET['noptin_key'] ), 'Existing contact must not expose a confirmation key.' );
					}
				}
			}

			$global = false;
			$custom = true;
			$configure( 'required' );
			$record = $submit( $new_email() );
			$assert( 'pending' === $record->get_status() && 0 === $mails, 'Custom confirmation delivery must stay in control.' );
			$assert( false === noptin_has_enabled_double_optin(), 'Form override must not change global policy.' );

			// Explicit states supplied by imports/programmatic callers must remain authoritative.
			$id = add_noptin_subscriber( array( 'email' => $new_email(), 'source' => $form, 'status' => 'subscribed', 'confirmed' => true ) );
			$record = noptin_get_subscriber( $id );
			$assert( 'subscribed' === $record->get_status() && $record->get_confirmed(), 'Explicit programmatic state must be preserved.' );
			// Dispatch follows persisted status even if the originating form/global setting changed.
			$custom = false;
			$configure( 'disabled' );
			$mails = 0;
			$id = add_noptin_subscriber( array( 'email' => $new_email(), 'source' => $form, 'status' => 'pending' ) );
			$assert( 1 === $mails, 'Pending creation must send even when form/global opt-in is disabled.' );
			$attempts = array();
			$configure( 'required' );
			update_noptin_subscriber( $id, array( 'status' => 'subscribed', 'confirmed' => false ) );
			send_new_noptin_subscriber_double_optin_email( $id );
			$assert( array() === $attempts, 'Non-pending contacts must not receive automatic confirmation.' );
			send_new_noptin_subscriber_double_optin_email( $id, true );
			$assert( array( array( 'subscribed', true ) ) === $attempts, 'Explicit forced resend must remain supported.' );

			// Ordinary updates never infer a resubscription from a contact's historical source.
			update_noptin_subscriber( $id, array( 'confirmed' => true ) );
			update_noptin_subscriber( $id, array( 'status' => 'unsubscribed' ) );
			$mails = 0;
			update_noptin_subscriber( $id, array( 'first_name' => 'Updated' ) );
			$record = noptin_get_subscriber( $id );
			$assert( 'unsubscribed' === $record->get_status() && 0 === $mails, 'Ordinary update must not resubscribe.' );
			update_noptin_subscriber( $id, array(), $form );
			$record = noptin_get_subscriber( $id );
			$assert( 'pending' === $record->get_status() && ! $record->get_confirmed() && 1 === $mails, 'Shared updater must own form resubscription and confirmation.' );
			update_noptin_subscriber( $id, array( 'status' => 'subscribed', 'confirmed' => true ), $form );
			$record = noptin_get_subscriber( $id );
			$assert( 'pending' === $record->get_status() && ! $record->get_confirmed() && 1 === $mails, 'Pending form update must not confirm or resend.' );
			update_noptin_subscriber( $id, array( 'status' => 'subscribed', 'confirmed' => true ) );
			$attempts = array();
			send_new_noptin_subscriber_double_optin_email( $id, true );
			send_new_noptin_subscriber_double_optin_email( 0, true );
			$assert( array() === $attempts, 'Missing/confirmed contacts must not receive even forced resends.' );

			// Custom confirmation delivery and imports retain their suppression behavior.
			$custom = true;
			$mails = 0;
			$id = add_noptin_subscriber( array( 'email' => $new_email(), 'status' => 'pending' ) );
			$assert( 0 === $mails, 'Custom delivery must suppress automatic built-in confirmation.' );
			$attempts = array();
			send_new_noptin_subscriber_double_optin_email( $id, true );
			$assert( array() === $attempts, 'Forced resend must respect custom confirmation delivery.' );
			$custom = false;
			// Simulate the import marker without running unrelated import callbacks.
			$prior_import_count = $GLOBALS['wp_actions']['noptin_subscribers_before_import_item'] ?? null;
			$GLOBALS['wp_actions']['noptin_subscribers_before_import_item'] = 1;
			try {
				add_noptin_subscriber( array( 'email' => $new_email(), 'status' => 'pending' ) );
				$assert( 0 === $mails, 'Imported pending contacts must not receive automatic confirmation.' );
			} finally {
				if ( null === $prior_import_count ) {
					unset( $GLOBALS['wp_actions']['noptin_subscribers_before_import_item'] );
				} else {
					$GLOBALS['wp_actions']['noptin_subscribers_before_import_item'] = $prior_import_count;
				}
			}

		} finally {
			foreach ( $emails as $email ) {
				$record = noptin_get_subscriber( $email );
				if ( $record->exists() ) {
					$record->delete();
				}
			}
			wp_delete_post( $form, true );

		}
	}
}
