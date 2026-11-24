<?php
/** Daily license checks must distinguish invalid keys from service outages. */

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** Verify local license state through real response parsing and daily maintenance. */
class Test_License_Maintenance extends Noptin_Regression_Test_Case {
	/**
	 * Simulate authoritative replies and temporary failures without network access.
	 *
	 * @return array Named responses and expected local-key availability.
	 */
	public static function responses() {
		// The license Worker reports is_active_on_site=false for inactive or expired keys.
		$active = array( 'is_active' => true, 'is_active_on_site' => true, 'has_expired' => false );
		return array(
			'active' => array( array( 'license' => $active ), true ),
			'expired' => array( array( 'license' => array_merge( $active, array( 'has_expired' => true, 'is_active_on_site' => false ) ) ), false ),
			'inactive' => array( array( 'license' => array_merge( $active, array( 'is_active' => false, 'is_active_on_site' => false ) ) ), false ),
			'revoked-on-site' => array( array( 'license' => array_merge( $active, array( 'is_active_on_site' => false ) ) ), false ),
			'not-found' => array( array( 'code' => 'hizzle_licenses_not_found', 'message' => 'License not found.', 'data' => array( 'status' => 404 ) ), false ),
			'timeout' => array( new WP_Error( 'http_request_failed', 'Connection timed out.' ), true ),
			'invalid-json' => array( '<html>Service unavailable</html>', true ),
			'missing-license-payload' => array( array( 'temporary_error' => 'Service unavailable.' ), true ),
			'incomplete-license' => array( array( 'license' => (object) array() ), true ),
			'incomplete-inactive-license' => array( array( 'license' => array( 'is_active' => false ) ), true ),
		);
	}

	/**
	 * Keep local access on failures, and revoke both current and legacy keys on definite invalidation.
	 *
	 * @dataProvider responses
	 * @param mixed $response Mocked license-service response.
	 * @param bool  $expected Whether runtime Premium access should remain enabled.
	 */
	public function test_daily_check_preserves_or_removes_saved_key( $response, $expected ) {
		$key = 'maintenance-fixture';
		$cache = 'noptin_license_' . $key;
		delete_transient( $cache );
		update_option( 'noptin_helper_data', array( 'license_key' => $key, 'active_license_keys' => array( $key ), 'fixture_setting' => 'preserve' ) );
		$requests = 0;
		add_filter( 'pre_http_request', static function ( $preempt, $args, $url ) use ( $response, &$requests ) {
			if ( false === strpos( $url, '/licenses/maintenance-fixture/' ) ) {
				return $preempt;
			}
			++$requests;
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			return array( 'headers' => array(), 'body' => is_string( $response ) ? $response : wp_json_encode( $response ), 'response' => array( 'code' => is_string( $response ) ? 503 : 200, 'message' => 'Test response' ), 'cookies' => array() );
		}, PHP_INT_MAX, 3 );
		try {
			$this->assertTrue( noptin_has_alk() );
			noptin_daily_maintenance();
			$this->assertSame( 1, $requests );
			$this->assertSame( $expected, noptin_has_alk() );
			$settings = get_option( 'noptin_helper_data' );
			$this->assertSame( $expected ? $key : '', $settings['license_key'] );
			$this->assertSame( $expected ? array( $key ) : array(), $settings['active_license_keys'] );
			$this->assertSame( 'preserve', $settings['fixture_setting'] );
			$trigger = Hizzle\Noptin\Automation_Rules\Triggers\Main::get( 'page_published' );
			$rule = noptin_get_automation_rule( 0 );
			$rule->set_action_id( 'path' );
			$this->assertSame( $expected, $trigger->is_rule_valid_for_args( $rule, array(), '', Hizzle\Noptin\Automation_Rules\Actions\Main::get( 'path' ) ) );
			$this->assertSame( 1, $requests, 'Runtime validation must not contact the license server.' );
		} finally {
			delete_transient( $cache );
			foreach ( Hizzle\Noptin\Tasks\Main::query( array( 'hook' => array( 'noptin_refresh_integrations', 'noptin_refresh_email_templates' ), 'per_page' => 100 ) ) as $task ) {
				$task->delete();
			}
		}
	}
}