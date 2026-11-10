<?php

namespace Hizzle\Noptin\Tests\Automation_Rules;

require_once __DIR__ . '/includes/class-actions-test-case.php';

/**
 * Tests for the loop automation action.
 */
class Test_Loop_Action extends Actions_Test_Case {

	/** Verify loaded records, ID resolution, and empty IDs in a global post context. */
	public function test_collection_iteration_labels_resolve_ids_and_accept_loaded_records() {
		$action = new \Hizzle\Noptin\Automation_Rules\Actions\Loop();
		$label = new \ReflectionMethod( $action, 'collection_iteration_label' );
		$label->setAccessible( true );

		$posts = new \Hizzle\Noptin\Objects\Generic_Post_Type( 'post' );
		$id = self::factory()->post->create( array( 'post_title' => 'Iteration title', 'post_status' => 'publish' ) );
		$this->assertContains( $id, $posts->get_all( array() ) );
		$this->assertSame( 'Iteration title', $label->invoke( $action, $id, $posts ) );
		$this->assertSame( 'Iteration title', $label->invoke( $action, $posts->get( $id ), $posts ) );
		wp_update_post( array( 'ID' => $id, 'post_title' => '' ) );
		$this->assertSame( 'ID #' . $id, $label->invoke( $action, $id, $posts ) );
		$had_post = array_key_exists( 'post', $GLOBALS );
		$previous_post = $GLOBALS['post'] ?? null;
		$GLOBALS['post'] = get_post( $id );
		try {
			foreach ( array( 0, '0', '', null, false ) as $empty_id ) {
				$this->assertSame( '', $label->invoke( $action, $empty_id, $posts ), 'Empty IDs must not resolve the global post.' );
			}
		} finally {
			if ( $had_post ) {
				$GLOBALS['post'] = $previous_post;
			} else {
				unset( $GLOBALS['post'] );
			}
		}

		$users = new \Hizzle\Noptin\Objects\Users( 'user', 'Users', 'User' );
		$user_id = self::factory()->user->create( array( 'user_email' => 'loop-label@example.test', 'display_name' => 'Loop person' ) );
		$this->assertSame( 'loop-label@example.test', $label->invoke( $action, $user_id, $users ) );
		$person = $users->get( $user_id );
		$this->assertSame( 'loop-label@example.test', $label->invoke( $action, $person, $users ) );
		$person->external->user_email = '';
		$users->title_field = 'display_name';
		$this->assertSame( 'Loop person', $label->invoke( $action, $person, $users ) );
	}

	public function test_loop_action_loops_over_number_range() {
		list( $loop ) = $this->create_loop_with_body(
			array(
				'loop_over'      => 'numbers',
				'number_start'   => 1,
				'number_end'     => 3,
				'number_step'    => 1,
				'max_iterations' => 0,
			)
		);

		$this->run_rule( $loop );
		$this->drain_pending_automation_tasks();

		$this->assertCount( 3, Test_Spy_Action::$runs );
		$this->assert_extra_arg_values( Test_Spy_Action::$runs[0], array( 1, 0 ) );
		$this->assert_extra_arg_values( Test_Spy_Action::$runs[1], array( 2, 1 ) );
		$this->assert_extra_arg_values( Test_Spy_Action::$runs[2], array( 3, 2 ) );
	}

	public function test_loop_action_respects_number_step_direction_and_max_iterations() {
		list( $loop ) = $this->create_loop_with_body(
			array(
				'loop_over'      => 'numbers',
				'number_start'   => 5,
				'number_end'     => 1,
				'number_step'    => 2,
				'max_iterations' => 2,
			)
		);

		$this->run_rule( $loop );
		$this->drain_pending_automation_tasks();

		$this->assertCount( 2, Test_Spy_Action::$runs );
		$this->assert_extra_arg_values( Test_Spy_Action::$runs[0], array( 5, 0 ) );
		$this->assert_extra_arg_values( Test_Spy_Action::$runs[1], array( 3, 1 ) );
	}

	public function test_loop_action_loops_over_subscribers_collection() {
		add_noptin_subscriber(
			array(
				'email' => 'loop-one@example.com',
				'name'  => 'Loop One',
			)
		);
		add_noptin_subscriber(
			array(
				'email' => 'loop-two@example.com',
				'name'  => 'Loop Two',
			)
		);

		list( $loop ) = $this->create_loop_with_body(
			array(
				'loop_over'      => 'subscribers',
				'max_iterations' => 2,
			)
		);

		$this->assertSame( 'subscribers', $loop->get_action_setting( 'loop_over' ) );

		$this->run_rule( $loop );
		$this->drain_pending_automation_tasks();

		$this->assertCount( 2, Test_Spy_Action::$runs );
		$this->assertArrayHasKey( 'provided_collections', Test_Spy_Action::$runs[0]['args'] );
		$this->assert_extra_arg_values( Test_Spy_Action::$runs[0], array( 0 ) );
		$this->assert_extra_arg_values( Test_Spy_Action::$runs[1], array( 1 ) );
	}

	/**
	 * @dataProvider file_loop_provider
	 */
	public function test_loop_action_loops_over_files( $type, $file, $first_email, $first_name ) {
		if ( 'xml' === $type && ! class_exists( '\XMLReader' ) && ! function_exists( 'simplexml_load_string' ) ) {
			$this->markTestSkipped( 'XML loop tests require XMLReader or SimpleXML.' );
		}

		// The plugin checkout may be outside ABSPATH in the test environment.
		$fixture = trailingslashit( ABSPATH ) . 'noptin-loop-' . wp_generate_uuid4() . '.' . $type;

		try {
			$this->assertTrue( copy( $this->fixture_path( $file ), $fixture ) );

			list( $loop ) = $this->create_loop_with_body(
				array(
					'loop_over'      => $type,
					'file'           => $fixture,
					'max_iterations' => 2,
				)
			);

			$this->run_rule( $loop );
			$this->drain_pending_automation_tasks();

			$this->assertCount( 2, Test_Spy_Action::$runs );
			$this->assert_extra_arg_values( Test_Spy_Action::$runs[0], array( $first_email, $first_name, 0 ) );
			$this->assert_extra_arg_values( Test_Spy_Action::$runs[1], array( 1 ) );
		} finally {
			wp_delete_file( $fixture );
		}
	}

	public function file_loop_provider() {
		return array(
			'csv'  => array( 'csv', 'loop.csv', 'ada@example.com', 'Ada Lovelace' ),
			'json' => array( 'json', 'loop.json', 'grace@example.com', 'Grace Hopper' ),
			'xml'  => array( 'xml', 'loop.xml', 'marie@example.com', 'Marie Curie' ),
		);
	}

	public function test_loop_end_runs_once_after_loop_body() {
		list( $loop, $body ) = $this->create_loop_with_body(
			array(
				'loop_over'      => 'numbers',
				'number_start'   => 1,
				'number_end'     => 2,
				'number_step'    => 1,
				'max_iterations' => 0,
			),
			true
		);

		$this->run_rule( $loop );
		$this->drain_pending_automation_tasks();

		$run_ids = wp_list_pluck( Test_Spy_Action::$runs, 'rule_id' );

		$this->assertCount( 3, $run_ids );
		$this->assertSame( $body->get_id(), $run_ids[0] );
		$this->assertSame( $body->get_id(), $run_ids[1] );
		$this->assertNotSame( $body->get_id(), $run_ids[2] );
	}
}
