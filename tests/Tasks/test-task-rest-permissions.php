<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

use Hizzle\Noptin\Tasks\Main;

/** Verifies task operators can manage jobs without defining executable work. */
class Test_Task_REST_Permissions extends Noptin_Regression_Test_Case {

	/**
	 * Creates a task-only operator without broad Noptin or WordPress access.
	 *
	 * @return int User ID.
	 */
	private function create_operator() {
		add_filter( 'get_noptin_option_allow_editors', '__return_false' );
		$id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		get_userdata( $id )->add_cap( 'manage_noptin_tasks' );
		wp_set_current_user( $id );
		$this->assertTrue( current_user_can( 'manage_noptin_tasks' ) );
		$this->assertFalse( current_user_can( 'manage_noptin' ) );
		$this->assertFalse( current_user_can( 'manage_options' ) );
		return $id;
	}

	/**
	 * Dispatches a real task REST request.
	 *
	 * @param string $method HTTP method.
	 * @param string $path   Path relative to the task collection.
	 * @param array  $params Request body.
	 * @return WP_REST_Response Response.
	 */
	private function request( $method, $path = '', $params = array() ) {
		$request = new WP_REST_Request( $method, '/noptin/v1/tasks' . $path );
		$request->set_body_params( $params );
		return rest_do_request( $request );
	}

	/** Task-only operators must be denied across all definition write paths. */
	public function test_task_definitions_cannot_be_written_through_rest() {
		$operator = $this->create_operator();
		$runs = 0;
		remove_all_actions( 'noptin_reset_data' );
		add_action( 'noptin_reset_data', static function () use ( &$runs ) { ++$runs; } );
		$task = Main::schedule_task( 'noptin_test_server_owned_task', array( 'trusted' ), HOUR_IN_SECONDS );
		$this->assertInstanceOf( Hizzle\Noptin\Tasks\Task::class, $task );
		$id = $task->get_id();
		$payload = array(
			'hook' => 'noptin_reset_data',
			'args_hash' => md5( 'task-rest-permission-fixture' ),
			'args' => wp_json_encode( array( 'attacker input' ) ),
			'status' => 'pending',
			'date_scheduled' => gmdate( 'c', time() - HOUR_IN_SECONDS ),
		);
		$count = Main::query( array(), 'count' );
		foreach ( array( $operator ) as $user_id ) {
			wp_set_current_user( $user_id );
			$this->assertSame( 403, $this->request( 'POST', '', $payload )->get_status() );
			foreach ( array( $payload, array( 'args' => '["changed"]', 'subject' => 'victim@example.test', 'primary_id' => 123, 'secondary_id' => 456 ) ) as $edits ) {
				$this->assertSame( 403, $this->request( 'POST', '/' . $id, $edits )->get_status() );
				$this->assertSame( 403, $this->request( 'PATCH', '', array( 'include' => array( $id ), 'bulk_update' => $edits ) )->get_status() );
			}
			foreach ( array( 'create', 'import', 'update' ) as $operation ) {
				$response = $this->request( 'POST', '/batch', array( $operation => array( array_merge( $payload, 'update' === $operation ? array( 'id' => $id ) : array() ) ) ) );
				$this->assertSame( 200, $response->get_status() );
				$this->assertTrue( $response->get_data()[ $operation ][0]['is_error'] );
				$this->assertSame( 403, $response->get_data()[ $operation ][0]['data']['data']['status'] );
			}
		}
		$this->assertSame( $count, Main::query( array(), 'count' ) );
		$stored = Main::get( $id );
		$this->assertSame( 'noptin_test_server_owned_task', $stored->get_hook() );
		$this->assertSame( '["trusted"]', $stored->get_args() );
		$this->assertNull( $stored->get_subject() );
		$this->assertNull( $stored->get_primary_id() );
		$this->assertSame( 0, $runs );
		$schema = $this->request( 'GET', '/collection_schema' );
		$this->assertSame( 200, $schema->get_status() );
		$this->assertSame( array( 'create' => false, 'edit' => false, 'delete' => true ), $schema->get_data()['permissions'] );
	}

	/** Administrators retain task creation and editing through every write path. */
	public function test_administrator_can_create_and_edit_task_definitions() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$payload = array(
			'hook' => 'noptin_test_admin_task',
			'args' => '["admin"]',
			'args_hash' => md5( 'admin-task-fixture' ),
			'status' => 'pending',
			'date_scheduled' => gmdate( 'c', time() + DAY_IN_SECONDS ),
		);
		$response = $this->request( 'POST', '', $payload );
		$this->assertSame( 201, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$id = $response->get_data()['id'];
		$this->assertSame( 200, $this->request( 'POST', '/' . $id, array( 'args' => '["edited"]' ) )->get_status() );
		$this->assertSame( '["edited"]', Main::get( $id )->get_args() );
		$this->assertSame( 200, $this->request( 'PATCH', '', array( 'include' => array( $id ), 'bulk_update' => array( 'args' => '["bulk"]' ) ) )->get_status() );
		$this->assertSame( '["bulk"]', Main::get( $id )->get_args() );
		foreach ( array( 'create', 'update', 'import' ) as $operation ) {
			$item = array_merge( $payload, array( 'args_hash' => md5( $operation ), 'args' => wp_json_encode( array( $operation ) ) ) );
			if ( 'update' === $operation ) {
				$item['id'] = $id;
			}
			$response = $this->request( 'POST', '/batch', array( $operation => array( $item ) ) );
			$this->assertSame( 200, $response->get_status() );
			$this->assertFalse( $response->get_data()[ $operation ][0]['is_error'] );
		}
		$this->assertSame( array( 'create' => true, 'edit' => true, 'delete' => true ), $this->request( 'GET', '/collection_schema' )->get_data()['permissions'] );
	}

	/** Collections can grant independent operation caps and preserve legacy defaults. */
	public function test_granular_collection_capabilities_and_legacy_fallback() {
		$this->create_operator();
		$controller = Hizzle\Noptin\Core\Main::$controllers['tasks'];
		$collection = $controller->fetch_collection();
		$original = $collection->capabilities;
		try {
			$collection->capabilities = array( 'create' => 'create_test_tasks', 'edit' => 'edit_test_tasks' );
			$this->assertFalse( $controller->check_record_permissions( 'create' ) );
			$this->assertFalse( $controller->check_record_permissions( 'edit' ) );
			$this->assertFalse( $controller->check_record_permissions( 'batch' ) );
			$this->assertTrue( $controller->check_record_permissions( 'remote_action' ) );
			wp_get_current_user()->add_cap( 'create_test_tasks' );
			$this->assertTrue( $controller->check_record_permissions( 'create' ) );
			$this->assertFalse( $controller->check_record_permissions( 'edit' ) );
			wp_get_current_user()->add_cap( 'edit_test_tasks' );
			$this->assertTrue( $controller->check_record_permissions( 'edit' ) );
			$this->assertTrue( $controller->check_record_permissions( 'batch' ) );
			$collection->capabilities = array();
			$this->assertTrue( $controller->check_record_permissions( 'create' ) );
			$this->assertTrue( $controller->check_record_permissions( 'edit' ) );
			wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
			$this->assertSame( 403, $this->request( 'GET' )->get_status() );
		} finally {
			$collection->capabilities = $original;
		}
	}

	/** Legacy post collections must not gain edit-others or UI restrictions. */
	public function test_legacy_post_collection_preserves_bulk_and_ui_permissions() {
		$controller = Hizzle\Noptin\Core\Main::$controllers['tasks'];
		$collection = $controller->fetch_collection();
		$original_caps = $collection->capabilities;
		$original_type = $collection->post_type;
		$contexts = array();
		$record_context = static function ( $allowed, $context ) use ( &$contexts ) {
			$contexts[] = $context;
			return $allowed;
		};
		try {
			$collection->capabilities = array();
			$collection->post_type = 'post';
			$author = self::factory()->user->create( array( 'role' => 'author' ) );
			wp_set_current_user( $author );
			$this->assertTrue( current_user_can( 'publish_posts' ) );
			$this->assertFalse( current_user_can( 'edit_others_posts' ) );
			$request = new WP_REST_Request( 'PATCH', '/noptin/v1/tasks' );
			$request->set_body_params( array( 'bulk_update' => array( 'args' => '[]' ) ) );
			add_filter( 'hizzle_store_rest_check_permissions', $record_context, 10, 2 );
			$this->assertTrue( $controller->bulk_update_items_permissions_check( $request ) );
			$this->assertTrue( $controller->batch_items_permissions_check( $request ) );
			$this->assertSame( array( 'create', 'create' ), $contexts );
			remove_filter( 'hizzle_store_rest_check_permissions', $record_context );
			$this->assertSame( array(), (array) $controller->get_collection_table_schema()->get_data()['permissions'] );
			wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
			$this->assertArrayHasKey( '/noptin/tasks/import', $controller->get_admin_app_routes() );
			$this->assertInstanceOf( WP_Error::class, $controller->bulk_update_items_permissions_check( $request ) );
			$legacy_controller = new Noptin_Legacy_Action_Permission_Controller( 'noptin', 'tasks' );
			$this->assertSame( 'legacy_edit_override', $legacy_controller->remote_action_permissions_check( $request )->get_error_code() );
		} finally {
			remove_filter( 'hizzle_store_rest_check_permissions', $record_context );
			$collection->capabilities = $original_caps;
			$collection->post_type = $original_type;
		}
	}

	/** Granular edit access adds to WordPress's individual post permissions. */
	public function test_granular_post_collection_keeps_individual_object_checks() {
		$controller = Hizzle\Noptin\Core\Main::$controllers['tasks'];
		$collection = $controller->fetch_collection();
		$original_caps = $collection->capabilities;
		$original_type = $collection->post_type;
		try {
			$collection->capabilities = array( 'edit' => 'edit_test_tasks' );
			$collection->post_type = 'post';
			$author = self::factory()->user->create( array( 'role' => 'author' ) );
			$other = self::factory()->user->create( array( 'role' => 'author' ) );
			$owned = self::factory()->post->create( array( 'post_author' => $author ) );
			$foreign = self::factory()->post->create( array( 'post_author' => $other ) );
			wp_set_current_user( $author );
			$request = new WP_REST_Request( 'PATCH', '/noptin/v1/tasks' );
			$this->assertInstanceOf( WP_Error::class, $controller->bulk_update_items_permissions_check( $request ) );
			wp_get_current_user()->add_cap( 'edit_test_tasks' );
			$this->assertTrue( $controller->bulk_update_items_permissions_check( $request ) );
			$this->assertTrue( $controller->check_record_permissions( 'edit', $owned ) );
			$this->assertFalse( $controller->check_record_permissions( 'edit', $foreign ) );
		} finally {
			$collection->capabilities = $original_caps;
			$collection->post_type = $original_type;
		}
	}

	/** Callback guards also honor a legacy controller returning boolean false. */
	public function test_direct_write_callbacks_honor_boolean_permission_denials() {
		$this->create_operator();
		$controller = new Noptin_Boolean_Denial_Controller( 'noptin', 'tasks' );
		$request = new WP_REST_Request( 'POST', '/noptin/v1/tasks' );
		foreach ( array( 'create_item', 'update_item', 'delete_item' ) as $method ) {
			$result = $controller->$method( $request );
			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertSame( 403, $result->get_error_data()['status'] );
		}
	}

	/** Subscriber operators can delete with a separate grant but cannot define jobs. */
	public function test_subscriber_operator_delete_capability_and_conditional_batches() {
		$this->create_operator();
		$collection = Hizzle\Noptin\Core\Main::$controllers['tasks']->fetch_collection();
		$original = $collection->capabilities;
		$collection->capabilities['delete'] = 'delete_noptin_tasks';
		$collection->capabilities['delete_multiple'] = 'delete_noptin_tasks';
		$ids = array();
		$imports = 0;
		add_action( 'noptin_tasks_before_import_item', static function () use ( &$imports ) { ++$imports; } );
		try {
			$this->assertContains( 'subscriber', wp_get_current_user()->roles );
			foreach ( range( 1, 4 ) as $index ) {
				$task = Main::schedule_task( 'noptin_test_server_owned_task', array( 'trusted', $index ), HOUR_IN_SECONDS );
				$ids[] = $task->get_id();
			}
			$this->assertSame( 200, $this->request( 'GET' )->get_status() );
			$this->assertSame( 200, $this->request( 'GET', '/' . $ids[0] )->get_status() );
			$this->assertSame( 403, $this->request( 'DELETE', '/' . $ids[0] )->get_status() );
			$this->assertSame( 403, $this->request( 'DELETE', '', array( 'include' => array( $ids[0], $ids[1] ) ) )->get_status() );
			$denied = $this->request( 'POST', '/batch', array( 'delete' => array( $ids[0] ) ) );
			$this->assertSame( 200, $denied->get_status() );
			$this->assertTrue( $denied->get_data()['delete'][0]['is_error'] );
			$this->assertTrue( Main::get( $ids[0] )->exists() );

			wp_get_current_user()->add_cap( 'delete_noptin_tasks' );
			$this->assertFalse( current_user_can( 'manage_options' ) );
			$this->assertSame( 204, $this->request( 'DELETE', '', array( 'include' => array( $ids[0], $ids[1] ) ) )->get_status() );
			$this->assertFalse( Main::get( $ids[0] )->exists() );
			$this->assertFalse( Main::get( $ids[1] )->exists() );
			$this->assertTrue( Main::get( $ids[3] )->exists() );
			$payload = array(
				'hook' => 'noptin_reset_data',
				'args_hash' => md5( 'conditional-task-batch' ),
				'args' => '["untrusted"]',
				'status' => 'pending',
				'date_scheduled' => gmdate( 'c', time() + DAY_IN_SECONDS ),
			);
			$this->assertSame( 403, $this->request( 'POST', '', $payload )->get_status() );
			$this->assertSame( 403, $this->request( 'POST', '/' . $ids[3], $payload )->get_status() );
			$count = Main::query( array(), 'count' );
			$response = $this->request( 'POST', '/batch', array(
				'create' => array( $payload ),
				'import' => array( $payload ),
				'update' => array( array_merge( $payload, array( 'id' => $ids[3] ) ) ),
				'bulk_update' => array( 'query' => array( 'include' => array( $ids[3] ) ), 'merge' => $payload ),
				'delete' => array( $ids[2] ),
			) );
			$this->assertSame( 200, $response->get_status() );
			$data = $response->get_data();
			foreach ( array( 'create', 'import', 'update' ) as $action ) {
				$this->assertNotEmpty( $data[ $action ] );
				foreach ( $data[ $action ] as $result ) {
					$this->assertTrue( $result['is_error'] );
					$this->assertSame( 403, $result['data']['data']['status'] );
				}
			}
			$this->assertFalse( $data['delete'][0]['is_error'] );
			$this->assertSame( $ids[2], $data['delete'][0]['data'] );
			$this->assertFalse( Main::get( $ids[2] )->exists() );
			$this->assertEquals( $count - 1, Main::query( array(), 'count' ) );
			$this->assertSame( 'noptin_test_server_owned_task', Main::get( $ids[3] )->get_hook() );
			$this->assertSame( '["trusted",4]', Main::get( $ids[3] )->get_args() );
			$this->assertSame( 0, $imports, 'Denied imports must not fire import hooks.' );

			// A bulk-delete grant cannot bypass a separately denied individual delete.
			$collection->capabilities['delete'] = 'delete_other_noptin_tasks';
			$response = $this->request( 'POST', '/batch', array( 'delete' => array( $ids[3] ) ) );
			$this->assertTrue( $response->get_data()['delete'][0]['is_error'] );
			$this->assertSame( 403, $response->get_data()['delete'][0]['data']['data']['status'] );
			$this->assertTrue( Main::get( $ids[3] )->exists() );
		} finally {
			$collection->capabilities = $original;
		}
	}

	/** Operators keep lifecycle controls, with only the server's stored arguments. */
	public function test_operator_can_manage_server_created_tasks() {
		$this->create_operator();
		$seen = array();
		add_action( 'noptin_test_server_owned_task', static function ( $value ) use ( &$seen ) { $seen[] = $value; } );
		$task = Main::schedule_task( 'noptin_test_server_owned_task', array( 'trusted' ), HOUR_IN_SECONDS );
		$id = $task->get_id();
		$this->assertSame( 200, $this->request( 'GET', '/' . $id )->get_status() );
		$this->assertSame( 200, $this->request( 'POST', '/' . $id . '/remote-action/run', array( 'hook' => 'noptin_reset_data', 'args' => '["attacker"]' ) )->get_status() );
		$this->assertSame( array( 'trusted' ), $seen );
		$this->assertSame( 'complete', Main::get( $id )->get_status() );
		$this->assertSame( 200, $this->request( 'POST', '/' . $id . '/remote-action/re_run' )->get_status() );
		$this->assertSame( array( 'trusted', 'trusted' ), $seen );
		$pending = Main::schedule_task( 'noptin_test_server_owned_task', array( 'cancel' ), HOUR_IN_SECONDS );
		$this->assertSame( 200, $this->request( 'POST', '/' . $pending->get_id() . '/remote-action/cancel' )->get_status() );
		$this->assertSame( 'canceled', Main::get( $pending->get_id() )->get_status() );
		$this->assertSame( 204, $this->request( 'DELETE', '/' . $pending->get_id() )->get_status() );
		$this->assertFalse( Main::get( $pending->get_id() )->exists() );
	}

	/** The destructive action itself must reject task-only operators. */
	public function test_reset_data_requires_the_tools_capability() {
		$this->create_operator();
		$this->assertFalse( current_user_can( get_noptin_capability() ) );
		$this->assertFalse( defined( 'NOPTIN_RESETING_DATA' ) );
		Hizzle\Noptin\Admin\Tools::reset_data();
		$this->assertFalse( defined( 'NOPTIN_RESETING_DATA' ) );
	}
}

/** Fixture proving legacy remote actions honor a controller's edit override. */
class Noptin_Legacy_Action_Permission_Controller extends \Hizzle\Store\REST_Controller {
	/**
	 * Denies through the legacy edit callback without looking up a record.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		return new \WP_Error( 'legacy_edit_override', 'Legacy edit override.', array( 'status' => 403 ) );
	}
}

/** Fixture for controllers using WordPress's boolean-denial convention. */
class Noptin_Boolean_Denial_Controller extends \Hizzle\Store\REST_Controller {
	/** Denies creation. @param \WP_REST_Request $request Request. @return bool */
	public function create_item_permissions_check( $request ) {
		return false;
	}
	/** Denies editing. @param \WP_REST_Request $request Request. @return bool */
	public function update_item_permissions_check( $request ) {
		return false;
	}
	/** Denies deletion. @param \WP_REST_Request $request Request. @return bool */
	public function delete_item_permissions_check( $request ) {
		return false;
	}
}
