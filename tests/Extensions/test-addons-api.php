<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** First-party regression coverage migrated from addons-api.php. */
class Test_Addons_API extends Noptin_Regression_Test_Case {
	/** Verify addons api using the isolated WordPress test database. */
	public function test_addons_api() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		remove_all_actions( 'noptin_newsletter_campaign_published' );
		remove_all_actions( 'noptin_send_newsletter' );
		add_filter( 'noptin_saved_task_background_run', '__return_false' );
		remove_action( 'shutdown', array( $GLOBALS['noptin_tasks'], 'run_overdue_tasks_on_shutdown' ), 1 );
		$check_addons_api = array( $this, 'assertTrue' );
		wp_set_current_user( 0 );
		$response = rest_do_request( new WP_REST_Request( 'GET', '/noptin/v1/addons' ) );
		$check_addons_api( 401 === $response->get_status(), 'Anonymous readers must not receive installed plugin data.' );
		wp_set_current_user( $admin_id );
		$removed = rest_do_request( new WP_REST_Request( 'GET', '/noptin/v1/addons/events/triggers' ) );
		$check_addons_api( 404 === $removed->get_status(), 'Public event discovery must not register a local proxy route.' );
		$response = rest_do_request( new WP_REST_Request( 'GET', '/noptin/v1/addons' ) );
		$state = $response->get_data();
		$check_addons_api( 200 === $response->get_status(), 'Administrators can refresh add-on registrations.' );
		$check_addons_api( ! isset( $state['license_key'] ), 'A refresh must not restore the boot-time license key.' );
		$check_addons_api( is_array( $state['triggers'] ) && is_array( $state['actions'] ), 'Both local event registries are returned.' );
		foreach ( array( 'triggers' => \Hizzle\Noptin\Automation_Rules\Triggers\Main::all(), 'actions' => \Hizzle\Noptin\Automation_Rules\Actions\Main::all() ) as $kind => $events ) {
		    foreach ( $events as $event ) {
		        if ( $event->depricated ) { continue; }
		        $serialized = $state[ $kind ][ $event->get_id() ];
		        foreach ( array( 'integration', 'connection' ) as $owner ) {
		            $expected = empty( $event->$owner ) ? null : str_replace( '_', '-', $event->$owner );
		            $check_addons_api( array_key_exists( $owner, $serialized ) && $expected === $serialized[ $owner ], 'Local event ownership must be explicit and use normalized slugs.' );
		        }
		    }
		}
		$check_addons_api( is_array( $state['emailTypes']['automation']['sub_types'] ), 'Automated email choices can refresh after activation.' );
		foreach ( $state['connections'] as $connection ) {
		    $check_addons_api( isset( $connection['hasSettings'] ) && is_bool( $connection['hasSettings'] ), 'Connection settings availability must be explicit.' );
		    $check_addons_api( array_key_exists( 'isConnected', $connection ) && ( is_bool( $connection['isConnected'] ) || null === $connection['isConnected'] ), 'Connection status must be explicit, not inferred from a badge.' );
		}
		foreach ( $state['integrations'] as $integration ) {
		    $check_addons_api( isset( $integration['hasSettings'] ) && is_bool( $integration['hasSettings'] ), 'Integration settings availability must be explicit.' );
		    $check_addons_api( isset( $integration['plan'] ) && ! isset( $integration['isFree'] ), 'Integrations expose a plan without a redundant free flag.' );
		}
		$fields = \Hizzle\Noptin\Settings\Menu::prepare_settings();
		foreach ( array_merge( $state['connections'], $state['integrations'] ) as $addon ) {
			if ( ! $addon['hasSettings'] ) {
				$this->assertNull( $addon['settingsPath'], $addon['slug'] . ' must not advertise an unavailable settings path.' );
				continue;
			}
			$this->assertIsString( $addon['settingsPath'] );
			$path = explode( '.', $addon['settingsPath'] );
			$settings = $fields[ $path[0] ]['sub_sections'][ $path[1] ]['settings'] ?? array();
			if ( isset( $path[2] ) ) {
				$settings = $settings[ $path[2] ]['settings'] ?? array();
			}
			$this->assertNotEmpty( $settings, $addon['slug'] . ' must link to an actual settings form.' );
			foreach ( array_keys( $settings ) as $key ) {
				$this->assertDoesNotMatchRegularExpression( '/^noptin_.+_install$/', $key, 'Installation placeholders must not be settings forms.' );
			}
		}
	}
}
