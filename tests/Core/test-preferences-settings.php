<?php
require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** Regression coverage for built-in and custom preferences destinations. */
class Test_Preferences_Settings extends Noptin_Regression_Test_Case {
	/** Save preferences URLs through REST and check their generated subscriber links. */
	public function test_preferences_destinations() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		rest_get_server();
		$subscriber = noptin_get_subscriber();
		$subscriber->set_email( 'preferences@example.test' );
		$subscriber->set_confirm_key( 'preferences-fixture-key' );
		$defaults = array(
			get_noptin_action_url( 'manage_preferences' ),
			add_query_arg( 'noptin_ns', 'manage_preferences', home_url( '/' ) ),
			add_query_arg( 'noptin_ns', 'manage_preferences', untrailingslashit( home_url( '/' ) ) ),
		);
		$custom = array(
			'https://another-noptin.test?noptin_ns=manage_preferences',
			home_url( '/preferences/?noptin_ns=manage_preferences' ),
			add_query_arg( 'custom', '1', $defaults[1] ),
			$defaults[1] . '#preferences',
			get_noptin_action_url( 'unsubscribe' ),
			get_noptin_action_url( 'manage_preferences', noptin_encrypt( wp_json_encode( array( 'email' => 'other@example.test' ) ) ) ),
		);
		foreach ( array_merge( $defaults, $custom ) as $url ) {
			$is_default = in_array( $url, $defaults, true );
			$this->assertSame( $is_default, noptin_is_default_manage_preferences_url( $url ) );
			$request = new WP_REST_Request( 'POST', '/hizzlewp/v1/settings/noptin_options' );
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( array( 'manage_preferences_url' => $url, 'success_message' => 'Preserve this setting.' ) ) );
			$response = rest_do_request( $request );
			$this->assertSame( 200, $response->get_status() );
			$saved = get_option( 'noptin_options' );
			$this->assertSame( 'Preserve this setting.', $saved['success_message'] );
			if ( $is_default ) {
				$this->assertArrayNotHasKey( 'manage_preferences_url', $saved );
				$this->assertArrayNotHasKey( 'manage_preferences_url', (array) $response->get_data() );
				parse_str( wp_parse_url( $subscriber->get_manage_preferences_url(), PHP_URL_QUERY ), $query );
				$this->assertArrayNotHasKey( 'noptin_ns', $query );
				$this->assertSame( array( 'email' => 'preferences@example.test', 'noptin_ns' => 'manage_preferences' ), json_decode( noptin_decrypt( $query['nv'] ), true ) );
			} else {
				$this->assertSame( $url, $saved['manage_preferences_url'] );
				$this->assertSame( add_query_arg( 'noptin_key', 'preferences-fixture-key', $url ), $subscriber->get_manage_preferences_url() );
			}
		}
		$fields = Hizzle\Noptin\Settings\Menu::get_settings();
		$this->assertArrayNotHasKey( 'default', $fields['subscription_info']['settings']['manage_preferences_url'] );
	}
}