<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** Discovery and entitlement coverage for automation metadata abilities. */
class Test_Noptin_Automation_Event_Abilities extends Noptin_Regression_Test_Case {
	/** @var object Simulated cached license response. */
	private $license;

	/** Prepare isolated license-service responses without network requests. */
	public function set_up() {
		parent::set_up();
		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'The WordPress Abilities API is required.' );
		}
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->license = (object) array( 'is_active' => true, 'is_active_on_site' => true, 'has_expired' => false );
		add_filter( 'pre_option_noptin_helper_data', array( $this, 'license_options' ) );
		add_filter( 'pre_transient_noptin_license_ability-test', array( $this, 'license_response' ) );
	}

	/** Supply a disposable key without changing stored options. @return array */
	public function license_options() {
		return array( 'license_key' => 'ability-test' );
	}

	/** Return the test's cached service response. @return object|WP_Error */
	public function license_response() {
		return $this->license;
	}

	/**
	 * Execute a registered metadata ability.
	 *
	 * @param string $slug Ability slug.
	 * @param array  $input Tool input.
	 * @return mixed
	 */
	private function execute( $slug, $input = array() ) {
		$ability = wp_get_ability( 'noptin/' . $slug );
		$this->assertNotNull( $ability );
		return $ability->execute( $input );
	}

	/** All metadata abilities require automation management permission and support REST/MCP. */
	public function test_registration_and_permissions() {
		foreach ( array( 'list-automation-events', 'get-automation-event-settings', 'get-automation-merge-tags' ) as $slug ) {
			$ability = wp_get_ability( 'noptin/' . $slug );
			$this->assertNotNull( $ability );
			$this->assertTrue( $ability->get_meta()['show_in_rest'] );
			$this->assertTrue( $ability->get_meta()['mcp']['public'] );
			$this->assertTrue( $ability->get_meta()['annotations']['readonly'] );
			wp_set_current_user( 0 );
			$this->assertFalse( $ability->check_permissions() );
		}
		$user = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user );
		$this->assertFalse( wp_get_ability( 'noptin/list-automation-events' )->check_permissions() );
		get_user_by( 'id', $user )->add_cap( get_noptin_collection_capability( 'automation_rules' ) );
		$this->assertTrue( wp_get_ability( 'noptin/list-automation-events' )->check_permissions() );
	}

	/** Discovery returns concrete IDs, excludes email actions and supports search and kind. */
	public function test_discovery() {
		$result = $this->execute( 'list-automation-events', array( 'kind' => 'action', 'include_locked' => true ) );
		$this->assertNotWPError( $result );
		$events = array_column( $result['events'], null, 'id' );
		$this->assertArrayHasKey( 'loop', $events );
		$this->assertArrayNotHasKey( 'email', $events );
		foreach ( $events as $event ) {
			$this->assertSame( 'action', $event['kind'] );
			$this->assertNotNull( Hizzle\Noptin\Automation_Rules\Actions\Main::get( $event['id'] ) );
		}
		$result = $this->execute( 'list-automation-events', array( 'kind' => 'trigger', 'search' => 'noptin_subscriber_added_to_tags' ) );
		$this->assertContains( 'noptin_subscriber_added_to_tags', array_column( $result['events'], 'id' ) );
		$this->assertWPError( $this->execute( 'get-automation-event-settings', array( 'kind' => 'action', 'id' => 'email' ) ) );
		$this->assertWPError( $this->execute( 'get-automation-event-settings', array( 'kind' => 'trigger', 'id' => 'missing_ability_fixture' ) ) );
	}

	/** Inactive, expired, deactivated and failed license checks deny premium metadata. */
	public function test_license_states_and_free_exceptions() {
		$loop = array( 'kind' => 'action', 'id' => 'loop' );
		$this->assertNotWPError( $this->execute( 'get-automation-event-settings', $loop ) );
		foreach ( array(
			(object) array( 'is_active' => false, 'is_active_on_site' => true ),
			(object) array( 'is_active' => true, 'is_active_on_site' => true, 'has_expired' => true ),
			(object) array( 'is_active' => true, 'is_active_on_site' => false ),
			new WP_Error( 'test_service_failure', 'Unavailable' ),
		) as $license ) {
			$this->license = $license;
			$this->assertWPError( $this->execute( 'get-automation-event-settings', $loop ) );
			$this->assertNotWPError( $this->execute( 'get-automation-event-settings', array( 'kind' => 'trigger', 'id' => 'noptin_subscriber_added_to_tags' ) ) );
			$list = $this->execute( 'list-automation-events', array( 'kind' => 'action', 'include_locked' => true ) );
			$events = array_column( $list['events'], null, 'id' );
			$this->assertFalse( $events['loop']['available'] );
			$this->assertNotEmpty( $events['loop']['reason'] );
			$list = $this->execute( 'list-automation-events', array( 'kind' => 'action' ) );
			$this->assertNotContains( 'loop', array_column( $list['events'], 'id' ) );
		}
	}

	/** Settings and merge tags reuse the editor's public metadata handlers. */
	public function test_settings_and_merge_tags() {
		$id = 'noptin_subscriber_added_to_tags';
		$settings = $this->execute( 'get-automation-event-settings', array( 'kind' => 'trigger', 'id' => $id ) );
		$this->assertNotWPError( $settings );
		$this->assertArrayHasKey( 'settings', $settings );
		foreach ( array( 'automation_trigger' => $id, 'collection' => 'subscriber' ) as $type => $source ) {
			$request = new WP_REST_Request();
			$request->set_param( 'type', $type );
			$request->set_param( 'id', $source );
			$expected = Hizzle\Noptin\Misc\Main::get_merge_tags( $request );
			$this->assertNotWPError( $expected );
			$actual = $this->execute( 'get-automation-merge-tags', array( 'type' => $type, 'id' => $source ) );
			$this->assertSame( $expected->get_data(), $actual['merge_tags'] );
		}
		$this->assertWPError( $this->execute( 'get-automation-merge-tags', array( 'type' => 'automated_email', 'id' => 'email' ) ) );
	}

	/** The core REST run endpoint enforces permissions before discovery. */
	public function test_rest_execution() {
		$request = new WP_REST_Request( 'GET', '/wp-abilities/v1/abilities/noptin/list-automation-events/run' );
		$request->set_param( 'input', array( 'kind' => 'action', 'search' => 'path' ) );
		$this->assertSame( 200, rest_do_request( $request )->get_status() );
		wp_set_current_user( 0 );
		$this->assertContains( rest_do_request( $request )->get_status(), array( 401, 403 ) );
	}
}
