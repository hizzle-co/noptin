<?php

namespace Hizzle\Noptin\Tests\Subscribers;

use Hizzle\Noptin\Automation_Rules\Automation_Rule;
use Hizzle\Noptin\Emails\Email;
use Hizzle\Noptin\Subscribers\Fields_REST_API;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Tests for field options REST operations.
 */
class Test_Fields_REST_API extends WP_UnitTestCase {

	/** @var int[] Post IDs of email campaigns created during a test. */
	private $created_campaign_ids = array();

	public function set_up() {
		parent::set_up();

		delete_option( 'noptin_subscriber_tags' );
		noptin()->db()->delete_all( 'subscribers' );
	}

	public function tear_down() {
		delete_option( 'noptin_subscriber_tags' );
		noptin()->db()->delete_all( 'subscribers' );
		noptin()->db()->delete_all( 'automation_rules' );

		foreach ( $this->created_campaign_ids as $id ) {
			wp_delete_post( $id, true );
		}

		$this->created_campaign_ids = array();

		parent::tear_down();
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Creates and saves an email campaign whose noptin_subscriber_options
	 * reference the given tag value under the given field.
	 *
	 * @param string       $field      Field merge tag (e.g. 'tags').
	 * @param string|array $tag_value  Value stored in noptin_subscriber_options.
	 * @return Email
	 */
	private function create_campaign_with_subscriber_option( $field, $tag_value ) {
		$email = new Email(
			array(
				'author'  => 1,
				'type'    => 'newsletter',
				'status'  => 'draft',
				'name'    => 'Test Campaign',
				'subject' => 'Test Subject',
				'content' => 'Test Content',
				'options' => array(
					'email_sender'              => 'noptin',
					'email_type'                => 'normal',
					'template'                  => 'paste',
					'content_normal'            => 'Test',
					'noptin_subscriber_options' => array(
						$field => $tag_value,
					),
				),
			)
		);

		$email->save();
		$this->created_campaign_ids[] = $email->id;
		return $email;
	}

	/**
	 * Creates and saves an automation rule with the given tag value wired into
	 * trigger settings, action settings, and conditional logic.
	 *
	 * @param string $field      Field merge tag.
	 * @param string $tag_value  Tag value to embed.
	 * @return Automation_Rule
	 */
	private function create_rule_with_tag( $field, $tag_value ) {
		/**
		 * @var Automation_Rule $rule
		 */
		$rule = noptin()->db()->get( 0, 'automation_rules' );

		$rule->set_trigger_id( 'noptin_subscriber_status_set_to_subscribed' );
		$rule->set_action_id( 'add_to_tags' );

		$rule->set_trigger_settings(
			array(
				$field              => array( $tag_value ),
				'conditional_logic' => array(
					'enabled' => true,
					'action'  => 'allow',
					'rules'   => array(
						array(
							'type'  => 'subscriber_field',
							'full'  => '[[' . $field . ']]',
							'value' => $tag_value,
						),
					),
				),
			)
		);

		$rule->set_action_settings(
			array(
				$field => array( $tag_value ),
			)
		);

		$rule->save();
		return $rule;
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

		// Create an email campaign and automation rule that reference the old tag.
		$campaign = $this->create_campaign_with_subscriber_option( 'tags', array( 'old-tag' ) );
		$rule     = $this->create_rule_with_tag( 'tags', 'old-tag' );

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

		// Check that the subscriber's tags have been updated (re-fetched fresh from DB).
		$subscriber_tags = noptin_get_subscriber( $subscriber_id )->get( 'tags' );
		$this->assertSame( 1, count( $subscriber_tags ), 'Subscriber should have one tag after update' );
		$this->assertContains( 'new-tag', $subscriber_tags, 'Subscriber should have the new tag' );
		$this->assertNotContains( 'old-tag', $subscriber_tags, 'Subscriber should not have the old tag' );

		// Check that the email campaign subscriber options were updated.
		$updated_campaign = noptin_get_email_campaign_object( $campaign->id );
		$options          = wp_json_encode( $updated_campaign->options['noptin_subscriber_options'] );
		$this->assertStringContainsString( 'new-tag', $options, 'Campaign subscriber options should reference the new tag' );
		$this->assertStringNotContainsString( 'old-tag', $options, 'Campaign subscriber options should not reference the old tag' );

		// Check that the automation rule settings were updated.
		$updated_rule            = noptin_get_automation_rule( $rule->get_id() );
		$trigger_settings        = wp_json_encode( $updated_rule->get_trigger_settings() );
		$action_settings         = wp_json_encode( $updated_rule->get_action_settings() );
		$conditional_logic = wp_json_encode( $updated_rule->get_conditional_logic() );
		$this->assertStringContainsString( 'new-tag', $trigger_settings, 'Rule trigger settings should reference the new tag' );
		$this->assertStringNotContainsString( 'old-tag', $trigger_settings, 'Rule trigger settings should not reference the old tag' );
		$this->assertStringContainsString( 'new-tag', $action_settings, 'Rule action settings should reference the new tag' );
		$this->assertStringNotContainsString( 'old-tag', $action_settings, 'Rule action settings should not reference the old tag' );
		$this->assertStringContainsString( 'new-tag', $conditional_logic, 'Rule conditional logic should reference the new tag' );
		$this->assertStringNotContainsString( 'old-tag', $conditional_logic, 'Rule conditional logic should not reference the old tag' );
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

		// Check that the response is not an error.
		$this->assertNotWPError( $response, 'Response should not be a WP_Error' );

		// Check that the response indicates one deleted record.
		$this->assertSame( 1, $response->get_data()['deleted'], 'Response should indicate one deleted record' );

		$subscriber_tags = noptin_get_subscriber( $subscriber_id )->get( 'tags' );
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

		// Create an email campaign and automation rules that reference the source tags.
		$campaign = $this->create_campaign_with_subscriber_option( 'tags', array( 'source-a', 'source-b' ) );
		$rule_a   = $this->create_rule_with_tag( 'tags', 'source-a' );
		$rule_b   = $this->create_rule_with_tag( 'tags', 'source-b' );

		$request = new WP_REST_Request( 'POST', '/noptin/v1/subscribers/fields/tags/target/merge' );
		$request->set_param( 'field', 'tags' );
		$request->set_param( 'target_option', 'target' );
		$request->set_param( 'source_options', array( 'source-a', 'source-b' ) );

		$response = Fields_REST_API::merge_field_options( $request );
		$this->assertNotWPError( $response, 'Response should not be a WP_Error' );

		$this->assertSame( 2, $response->get_data()['updated'], 'Response should indicate two updated records' );

		// Subscriber tags should all be replaced with target.
		$tags_a = noptin_get_subscriber( $subscriber_a )->get( 'tags' );
		$tags_b = noptin_get_subscriber( $subscriber_b )->get( 'tags' );

		$this->assertSame( array( 'target' ), array_values( $tags_a ), 'Subscriber A should have only the target tag after merge' );
		$this->assertSame( array( 'target' ), array_values( $tags_b ), 'Subscriber B should have only the target tag after merge' );

		// Unassigned options: target kept, sources removed.
		$unassigned = get_option( 'noptin_subscriber_tags', array() );
		$this->assertContains( 'target', $unassigned, 'Target tag should still be in unassigned options' );
		$this->assertNotContains( 'source-a', $unassigned, 'Source A tag should not be in unassigned options' );
		$this->assertNotContains( 'source-b', $unassigned, 'Source B tag should not be in unassigned options' );

		// Email campaign subscriber options: sources replaced with target.
		$updated_campaign = noptin_get_email_campaign_object( $campaign->id );
		$campaign_tags    = $updated_campaign->options['noptin_subscriber_options']['tags'] ?? array();
		$this->assertContains( 'target', $campaign_tags, 'Campaign should reference the target tag after merge' );
		$this->assertNotContains( 'source-a', $campaign_tags, 'Campaign should not reference source-a after merge' );
		$this->assertNotContains( 'source-b', $campaign_tags, 'Campaign should not reference source-b after merge' );

		// Automation rule A: source-a renamed to target in all three settings.
		$updated_rule_a            = noptin_get_automation_rule( $rule_a->get_id() );
		$trigger_tags_a            = $updated_rule_a->get_trigger_settings()['tags'] ?? array();
		$action_tags_a             = $updated_rule_a->get_action_settings()['tags'] ?? array();
		$conditional_logic_value_a = $updated_rule_a->get_conditional_logic()['rules'][0]['value'] ?? '';
		$this->assertContains( 'target', $trigger_tags_a, 'Rule A trigger settings should reference the target tag' );
		$this->assertNotContains( 'source-a', $trigger_tags_a, 'Rule A trigger settings should not reference source-a' );
		$this->assertContains( 'target', $action_tags_a, 'Rule A action settings should reference the target tag' );
		$this->assertNotContains( 'source-a', $action_tags_a, 'Rule A action settings should not reference source-a' );
		$this->assertSame( 'target', $conditional_logic_value_a, 'Rule A conditional logic value should be the target tag' );

		// Automation rule B: source-b renamed to target in all three settings.
		$updated_rule_b            = noptin_get_automation_rule( $rule_b->get_id() );
		$trigger_tags_b            = $updated_rule_b->get_trigger_settings()['tags'] ?? array();
		$action_tags_b             = $updated_rule_b->get_action_settings()['tags'] ?? array();
		$conditional_logic_value_b = $updated_rule_b->get_conditional_logic()['rules'][0]['value'] ?? '';
		$this->assertContains( 'target', $trigger_tags_b, 'Rule B trigger settings should reference the target tag' );
		$this->assertNotContains( 'source-b', $trigger_tags_b, 'Rule B trigger settings should not reference source-b' );
		$this->assertContains( 'target', $action_tags_b, 'Rule B action settings should reference the target tag' );
		$this->assertNotContains( 'source-b', $action_tags_b, 'Rule B action settings should not reference source-b' );
		$this->assertSame( 'target', $conditional_logic_value_b, 'Rule B conditional logic value should be the target tag' );
	}
}
