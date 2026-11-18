<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** In-memory record with observable getter calls. */
class Noptin_REST_Field_Probe {
	public $calls = array();

	/** Return the properties known to the record collection. */
	public function get_collection() {
		return new class {
			/** Describe the fields available for serialization. */
			public function get_props() {
				return array_map( static function ( $name ) { return (object) array( 'name' => $name ); }, array( 'id', 'expensive', 'enabled', 'labels', 'date' ) );
			}
		};
	}

	/** Fail if the controller eagerly requests every value. */
	public function get_data() {
		throw new RuntimeException( 'REST serialization must select fields before reading their values.' );
	}

	/**
	 * Return a requested value and record its context.
	 *
	 * @param string $key Field name.
	 * @param string $context Requested context.
	 * @return mixed Field value.
	 */
	public function get( $key, $context = 'view' ) {
		$this->calls[] = array( $key, $context );
		$values = array( 'id' => 123, 'hizzlewp_actions' => array(), 'expensive' => 'aggregate', 'enabled' => true, 'labels' => array( 'one', 'two' ), 'date' => new Hizzle\Store\Date_Time( '2026-11-07 14:30:00', new DateTimeZone( 'UTC' ) ) );
		return $values[ $key ];
	}
}

/** Controller that exposes a probe schema without registering routes. */
class Noptin_REST_Field_Controller extends Hizzle\Store\REST_Controller {
	/** Initialize an isolated controller. */
	public function __construct() {
		$this->namespace = 'noptin-probe/v1';
		$this->rest_base = 'records';
	}

	/** Describe the probe response fields and their contexts. */
	public function get_item_schema() {
		$properties = array();
		foreach ( array( 'id', 'hizzlewp_actions', 'expensive', 'enabled', 'labels', 'date' ) as $field ) {
			$properties[ $field ] = array( 'context' => 'expensive' === $field ? array( 'edit' ) : array( 'view', 'edit', 'export' ) );
		}
		return array( 'type' => 'object', 'properties' => $properties );
	}

	/**
	 * Suppress probe response links.
	 *
	 * @param object $record Probe record.
	 * @param WP_REST_Request $request REST request.
	 * @return array Empty links.
	 */
	protected function prepare_links( $record, $request ) {
		return array();
	}
}

/** First-party regression coverage migrated from datastore-rest-fields.php. */
class Test_Datastore_REST_Fields extends Noptin_Regression_Test_Case {
	/** Verify datastore rest fields using the isolated WordPress test database. */
	public function test_rest_fields_are_lazy_and_respect_context() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		remove_all_actions( 'noptin_newsletter_campaign_published' );
		remove_all_actions( 'noptin_send_newsletter' );
		add_filter( 'noptin_saved_task_background_run', '__return_false' );
		remove_action( 'shutdown', array( $GLOBALS['noptin_tasks'], 'run_overdue_tasks_on_shutdown' ), 1 );
		$controller = new Noptin_REST_Field_Controller();
		$probe = new Noptin_REST_Field_Probe();
		/** Assert the expected serialization behavior. */
		$assert = array( $this, 'assertTrue' );
		foreach ( array( 'view', 'edit', 'export' ) as $context ) {
			$request = new WP_REST_Request( 'GET' );
			$request->set_param( 'context', $context );
			$request->set_param( '_fields', 'id' );
			$probe->calls = array();
			$data = $controller->prepare_item_for_response( $probe, $request )->get_data();
			$assert( array( 'id' => 123 ) === $data, 'Restricted response must contain only the requested ID.' );
			$getter_context = 'export' === $context ? 'edit' : $context;
			$assert( array( array( 'id', $getter_context ) ) === $probe->calls, 'Only the requested getter must run; exports read edit values.' );
		}
		$request = new WP_REST_Request( 'GET' );
		$request->set_param( 'context', 'view' );
		$probe->calls = array();
		$data = $controller->prepare_item_for_response( $probe, $request )->get_data();
		$assert( ! array_key_exists( 'expensive', $data ), 'Edit-only values must be absent in view context.' );
		$assert( ! in_array( array( 'expensive', 'view' ), $probe->calls, true ), 'Excluded context must skip the expensive getter.' );
		$assert( isset( $data['hizzlewp_actions'] ), 'Record action metadata must remain available.' );
		$request->set_param( 'context', 'edit' );
		$data = $controller->prepare_item_for_response( $probe, $request )->get_data();
		$assert( 'aggregate' === $data['expensive'], 'Edit context must include its dynamic value without _fields.' );
		$assert( '2026-11-07T14:30:00+00:00' === $data['date'], 'Dates must retain their ISO formatting.' );
		// Column selection must not silently turn editable record values into CSV cells.
		$request->set_param( '_fields', 'id,enabled,labels' );
		$data = $controller->prepare_item_for_response( $probe, $request )->get_data();
		$this->assertSame( true, $data['enabled'] );
		$this->assertSame( array( 'one', 'two' ), $data['labels'] );
		$request->set_param( 'context', 'export' );
		$probe->calls = array();
		$data = $controller->prepare_item_for_response( $probe, $request )->get_data();
		$this->assertSame( array( 'id' => 123, 'enabled' => 1, 'labels' => 'one,two' ), $data );
		$this->assertSame( array( array( 'id', 'edit' ), array( 'enabled', 'edit' ), array( 'labels', 'edit' ) ), $probe->calls );
		$request->set_param( '_fields', null );
		$probe->calls = array();
		$data = $controller->prepare_item_for_response( $probe, $request )->get_data();
		$this->assertArrayNotHasKey( 'expensive', $data, 'An edit-only field must remain unavailable in export context.' );
		$this->assertNotContains( array( 'expensive', 'edit' ), $probe->calls );

		$task = Hizzle\Noptin\Tasks\Main::get( 0 );
		$task->set_hook( 'probe' );
		$task->set_status( 'pending' );
		$assert( null === $task->get_run_status( 'view' ), 'Task run status must be null outside edit context.' );
		$assert( 'pending' === $task->get_run_status( 'edit' ), 'Ordinary tasks must expose their own status in edit context.' );
		$tasks_controller = new Hizzle\Store\REST_Controller( 'noptin', 'tasks' );
		$request = new WP_REST_Request( 'GET' );
		$request->set_param( '_fields', 'id,run_status' );
		$request->set_param( 'context', 'view' );
		$data = $tasks_controller->prepare_item_for_response( $task, $request )->get_data();
		$assert( ! array_key_exists( 'run_status', $data ), 'Task REST view context must omit run status.' );
		$request->set_param( 'context', 'edit' );
		$data = $tasks_controller->prepare_item_for_response( $task, $request )->get_data();
		$assert( 'pending' === $data['run_status'], 'Task REST edit context must include schema run status.' );

		$task->update_meta( 'action_id', 'loop' );
		$task->update_meta( 'iteration', array( 'ordinal' => 1, 'label' => 'Number #3' ) );
		foreach ( array( 'view', 'edit' ) as $context ) {
			$request->set_param( 'context', $context );
			$request->set_param( '_fields', 'id,metadata' );
			$data = $tasks_controller->prepare_item_for_response( $task, $request )->get_data();
			$assert( is_array( $data['metadata'] ), 'REST metadata must remain structured in both contexts.' );
			$assert( 'loop' === $data['metadata']['action_id'] && 'Number #3' === $data['metadata']['iteration']['label'], 'REST metadata must preserve nested history values.' );
		}
		$assert( is_string( $task->get_metadata( 'edit' ) ), 'Database edit serialization must remain intact.' );
	}
}
