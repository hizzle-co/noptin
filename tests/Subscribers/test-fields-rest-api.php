<?php

namespace Hizzle\Noptin\Tests\Subscribers;

use Hizzle\Noptin\Subscribers\Fields_REST_API;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Tests for field options REST operations.
 */
class Test_Fields_REST_API extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();

		delete_option( 'noptin_subscriber_tags' );
		noptin()->db()->delete_all( 'subscribers' );
	}

	public function tear_down() {
		delete_option( 'noptin_subscriber_tags' );
		noptin()->db()->delete_all( 'subscribers' );

		parent::tear_down();
	}

	public function test_get_field_options_returns_tag_counts_and_unassigned_values() {
		add_noptin_subscriber(
			array(
				'email' => 'one@example.com',
				'tags'  => array( 'alpha', 'beta' ),
			)
		);

		add_noptin_subscriber(
			array(
				'email' => 'two@example.com',
				'tags'  => array( 'alpha' ),
			)
		);

		update_option( 'noptin_subscriber_tags', array( 'gamma' ), false );

		$request  = new WP_REST_Request( 'GET', '/noptin/v1/subscribers/fields/tags' );
		$request->set_param( 'field', 'tags' );
		$response = Fields_REST_API::get_field_options( $request );

		$data   = $response->get_data();
		$by_key = array();

		foreach ( $data as $item ) {
			$by_key[ $item['value'] ] = $item;
		}

		$this->assertArrayHasKey( 'alpha', $by_key );
		$this->assertArrayHasKey( 'beta', $by_key );
		$this->assertArrayHasKey( 'gamma', $by_key );
		$this->assertSame( 2, (int) $by_key['alpha']['count'] );
		$this->assertSame( 1, (int) $by_key['beta']['count'] );
		$this->assertSame( 0, (int) $by_key['gamma']['count'] );
	}

	public function test_create_field_option_adds_new_unassigned_tag() {
		$request = new WP_REST_Request( 'POST', '/noptin/v1/subscribers/fields/tags' );
		$request->set_param( 'field', 'tags' );
		$request->set_param( 'value', 'new-tag' );

		$response = Fields_REST_API::create_field_option( $request );

		$this->assertTrue( $response->get_data() );
		$this->assertContains( 'new-tag', get_option( 'noptin_subscriber_tags', array() ) );
	}

	public function test_update_field_option_renames_tag_everywhere() {
		$subscriber_id = add_noptin_subscriber(
			array(
				'email' => 'rename@example.com',
				'tags'  => array( 'old-tag' ),
			)
		);

		update_option( 'noptin_subscriber_tags', array( 'old-tag', 'unused' ), false );

		$request = new WP_REST_Request( 'POST', '/noptin/v1/subscribers/fields/tags/old-tag' );
		$request->set_param( 'field', 'tags' );
		$request->set_param( 'option', 'old-tag' );
		$request->set_param( 'value', 'new-tag' );

		$response = Fields_REST_API::update_field_option( $request );
		$data     = $response->get_data();

		$this->assertSame( 1, $data['updated'] );

		$subscriber_tags = noptin_get_subscriber( $subscriber_id )->get( 'tags' );
		$this->assertContains( 'new-tag', $subscriber_tags );
		$this->assertNotContains( 'old-tag', $subscriber_tags );

		$unassigned = get_option( 'noptin_subscriber_tags', array() );
		$this->assertContains( 'new-tag', $unassigned );
		$this->assertNotContains( 'old-tag', $unassigned );
	}

	public function test_delete_field_option_removes_tag_and_relationships() {
		$subscriber_id = add_noptin_subscriber(
			array(
				'email' => 'delete@example.com',
				'tags'  => array( 'to-delete', 'stay' ),
			)
		);

		update_option( 'noptin_subscriber_tags', array( 'to-delete', 'stay' ), false );

		$request = new WP_REST_Request( 'DELETE', '/noptin/v1/subscribers/fields/tags/to-delete' );
		$request->set_param( 'field', 'tags' );
		$request->set_param( 'option', 'to-delete' );

		$response = Fields_REST_API::delete_field_option( $request );
		$data     = $response->get_data();

		$this->assertSame( 1, $data['deleted'] );

		$subscriber_tags = noptin_get_subscriber( $subscriber_id )->get( 'tags' );
		$this->assertNotContains( 'to-delete', $subscriber_tags );
		$this->assertContains( 'stay', $subscriber_tags );

		$unassigned = get_option( 'noptin_subscriber_tags', array() );
		$this->assertNotContains( 'to-delete', $unassigned );
	}

	public function test_merge_field_options_merges_source_tags_into_target() {
		$subscriber_a = add_noptin_subscriber(
			array(
				'email' => 'merge-a@example.com',
				'tags'  => array( 'source-a' ),
			)
		);

		$subscriber_b = add_noptin_subscriber(
			array(
				'email' => 'merge-b@example.com',
				'tags'  => array( 'source-b', 'target' ),
			)
		);

		update_option( 'noptin_subscriber_tags', array( 'source-a', 'source-b', 'target' ), false );

		$request = new WP_REST_Request( 'POST', '/noptin/v1/subscribers/fields/tags/target/merge' );
		$request->set_param( 'field', 'tags' );
		$request->set_param( 'target_option', 'target' );
		$request->set_param( 'source_options', array( 'source-a', 'source-b' ) );

		$response = Fields_REST_API::merge_field_options( $request );

		$this->assertTrue( $response->get_data()['merged'] );

		$tags_a = noptin_get_subscriber( $subscriber_a )->get( 'tags' );
		$tags_b = noptin_get_subscriber( $subscriber_b )->get( 'tags' );

		$this->assertSame( array( 'target' ), array_values( $tags_a ) );
		$this->assertSame( array( 'target' ), array_values( $tags_b ) );

		$unassigned = get_option( 'noptin_subscriber_tags', array() );
		$this->assertContains( 'target', $unassigned );
		$this->assertNotContains( 'source-a', $unassigned );
		$this->assertNotContains( 'source-b', $unassigned );
	}
}
