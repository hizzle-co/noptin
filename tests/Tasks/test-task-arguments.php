<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

use Hizzle\Noptin\Tasks\Main;

/** Covers hidden task fields and argument validation through packaged code. */
class Test_Task_Arguments extends Noptin_Regression_Test_Case {

	/**
	 * Creates a future manual task through the real REST endpoint.
	 *
	 * @param array $params Request body overrides.
	 * @return WP_REST_Response Response.
	 */
	private function create_task( $params = array() ) {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$request = new WP_REST_Request( 'POST', '/noptin/v1/tasks' );
		$request->set_body_params( array_merge( array(
			'hook'           => 'noptin_test_task_arguments',
			'status'         => 'manual',
			'date_scheduled' => gmdate( 'c', time() + DAY_IN_SECONDS ),
		), $params ) );
		return rest_get_server()->dispatch( $request );
	}

	/** Hidden non-null fields remain typed and writable but are optional for clients. */
	public function test_hidden_fields_are_optional_in_collection_and_endpoint_schema() {
		$controller = Hizzle\Noptin\Core\Main::$controllers['tasks'];
		$collection = $controller->fetch_collection();
		$schema = $collection->get_rest_schema()['properties'];
		$this->assertContains( 'args_hash', $collection->hidden );
		$this->assertFalse( $schema['args_hash']['required'] );
		$this->assertSame( 'string', $schema['args_hash']['type'] );
		$this->assertFalse( $schema['args_hash']['readonly'] );
		$this->assertTrue( $schema['hook']['required'] );
		$this->assertTrue( $collection->get_props()['args_hash']->get_rest_schema()['required'] );
		$args = $controller->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE );
		$this->assertEmpty( $args['args_hash']['required'] ?? false );
		$this->assertTrue( $args['hook']['required'] );
		$response = $this->create_task( array( 'args_hash' => str_repeat( 'a', 33 ) ) );
		$this->assertSame( 400, $response->get_status() );
	}

	/** Dashboard creation must generate a hash without requiring hidden form values. */
	public function test_rest_creation_generates_missing_hash() {
		foreach ( array( '[]', '["first",2]', '{"subscriber_id":123}' ) as $args ) {
			$response = $this->create_task( array( 'args' => $args ) );
			$this->assertSame( 201, $response->get_status(), wp_json_encode( $response->get_data() ) );
			$task = Main::get( $response->get_data()['id'] );
			$this->assertSame( $args, $task->get_args() );
			$this->assertSame( md5( $args ), $task->get_args_hash() );
		}
	}

	/** Empty UI input and omitted arguments represent a hook with no arguments. */
	public function test_empty_arguments_default_to_an_empty_array() {
		foreach ( array( array(), array( 'args' => '' ), array( 'args' => null ) ) as $params ) {
			$response = $this->create_task( $params );
			$this->assertSame( 201, $response->get_status(), wp_json_encode( $response->get_data() ) );
			$task = Main::get( $response->get_data()['id'] );
			$this->assertSame( '[]', $task->get_args() );
			$this->assertSame( md5( '[]' ), $task->get_args_hash() );
		}
	}

	/** Invalid JSON and scalar JSON must fail before saving or running work. */
	public function test_invalid_arguments_are_rejected_on_create() {
		$response = $this->create_task( array( 'args' => '["original"]' ) );
		$this->assertSame( 201, $response->get_status() );
		$id = $response->get_data()['id'];
		$count = Main::query( array(), 'count' );
		foreach ( array( 'not json', '[1,', 'null', 'false', '123', '"text"' ) as $args ) {
			$response = $this->create_task( array( 'args' => $args ) );
			$this->assertSame( 400, $response->get_status() );
			$this->assertSame( 'invalid_task_args', $response->get_data()['code'] );
			$this->assertSame( '["original"]', Main::get( $id )->get_args() );
		}
		$this->assertSame( $count, Main::query( array(), 'count' ) );
	}

	/** PHP callers retain associative arrays and explicit scheduler deduplication hashes. */
	public function test_php_saves_validate_arguments_and_preserve_explicit_hashes() {
		$task = Main::get( 0 );
		$task->set_hook( 'noptin_test_task_arguments' );
		$task->set_status( 'manual' );
		$task->set_date_scheduled( time() + DAY_IN_SECONDS );
		$task->set_args( array( 'subscriber_id' => 123 ) );
		$hash = md5( 'scheduler task identity' );
		$task->set_args_hash( $hash );
		$this->assertFalse( is_wp_error( $task->save() ) );
		$this->assertSame( $hash, Main::get( $task->get_id() )->get_args_hash() );
		$task->set_args( '["updated"]' );
		$task->set_args_hash( '' );
		$this->assertFalse( is_wp_error( $task->save() ) );
		$this->assertSame( md5( '["updated"]' ), Main::get( $task->get_id() )->get_args_hash() );
		$task->set_args( 'invalid' );
		$this->assertFalse( is_wp_error( $task->save() ) );
		$this->assertSame( 'invalid', Main::get( $task->get_id() )->get_args() );
	}

	/** Existing pending tasks with malformed arguments must still record lifecycle changes. */
	public function test_existing_invalid_arguments_do_not_block_status_changes() {
		$response = $this->create_task();
		$this->assertSame( 201, $response->get_status() );
		$id = $response->get_data()['id'];
		foreach ( array( 'not json', '[1,', 'null', 'false', '123', '"text"' ) as $args ) {
			$task = Main::get( $id );
			$task->set_args( $args );
			$task->set_status( 'pending' );
			$this->assertSame( $id, $task->save() );
			$task = Main::get( $id );
			$this->assertSame( $args, $task->get_args() );
			$this->assertSame( 'pending', $task->get_status() );
			$task->set_status( 'failed' );
			$this->assertSame( $id, $task->save() );
			$this->assertSame( 'failed', Main::get( $id )->get_status() );
			$this->assertSame( $args, Main::get( $id )->get_args() );
		}
	}
}