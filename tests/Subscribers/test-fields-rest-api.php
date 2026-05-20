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
		global $wpdb;

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

		// Check that the response is not an error.
		$this->assertNotWPError( $response, 'Response should not be a WP_Error' );

		// Check that the response indicates one updated record.
		$this->assertSame( 1, $response->get_data()['updated'], 'Response should indicate one updated record' );

		// Check that the old tag has been replaced in the unassigned options.
		$unassigned = get_option( 'noptin_subscriber_tags', array() );
		$this->assertContains( 'new-tag', $unassigned, 'New tag should be in unassigned options' );
		$this->assertNotContains( 'old-tag', $unassigned, 'Old tag should not be in unassigned options' );

		// Check that the subscriber's tags have been updated in DB.
		$subscriber_tags = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value
				 FROM {$wpdb->prefix}noptin_subscriber_meta
				 WHERE noptin_subscriber_id = %d
				 AND meta_key = %s",
				$subscriber_id,
				'tags'
			)
		);
		$this->assertSame( 1, count( $subscriber_tags ), 'Subscriber should have one tag after update' );
		$this->assertContains( 'new-tag', $subscriber_tags, 'Subscriber should have the new tag' );
		$this->assertNotContains( 'old-tag', $subscriber_tags, 'Subscriber should not have the old tag' );
	}

	public function test_delete_field_option_removes_tag_and_relationships() {
		global $wpdb;

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

		// Check that the response is not an error.
		$this->assertNotWPError( $response, 'Response should not be a WP_Error' );

		// Check that the response indicates one deleted record.
		$this->assertSame( 1, $response->get_data()['deleted'], 'Response should indicate one deleted record' );

		$subscriber_tags = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value
				 FROM {$wpdb->prefix}noptin_subscriber_meta
				 WHERE noptin_subscriber_id = %d
				 AND meta_key = %s",
				$subscriber_id,
				'tags'
			)
		);
		$this->assertNotContains( 'to-delete', $subscriber_tags, 'Deleted tag should not be in subscriber tags' );
		$this->assertContains( 'stay', $subscriber_tags, 'Tag that should remain should still be in subscriber tags' );

		$unassigned = get_option( 'noptin_subscriber_tags', array() );
		$this->assertNotContains( 'to-delete', $unassigned, 'Deleted tag should not be in unassigned options' );
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
		$this->assertNotWPError( $response, 'Response should not be a WP_Error' );

		$this->assertSame( 2, $response->get_data()['updated'], 'Response should indicate two updated records' );

		$tags_a = noptin_get_subscriber( $subscriber_a )->get( 'tags' );
		$tags_b = noptin_get_subscriber( $subscriber_b )->get( 'tags' );

		$this->assertSame( array( 'target' ), array_values( $tags_a ), 'Subscriber A should have only the target tag after merge' );
		$this->assertSame( array( 'target' ), array_values( $tags_b ), 'Subscriber B should have only the target tag after merge' );

		$unassigned = get_option( 'noptin_subscriber_tags', array() );
		$this->assertContains( 'target', $unassigned, 'Target tag should still be in unassigned options' );
		$this->assertNotContains( 'source-a', $unassigned, 'Source A tag should not be in unassigned options' );
		$this->assertNotContains( 'source-b', $unassigned, 'Source B tag should not be in unassigned options' );
	}
}
