<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** Subscriber abilities exercised through the WordPress ability registry. */
class Test_Noptin_Subscriber_Abilities extends Noptin_Regression_Test_Case {
	/** @var int[] Subscriber fixtures owned by this test. */
	private $subscriber_ids = array();

	/** Prepare an administrator and require the Abilities API. */
	public function set_up() {
		parent::set_up();
		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'The WordPress Abilities API is required.' );
		}
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/** Remove subscriber fixtures and their caches. */
	public function tear_down() {
		foreach ( $this->subscriber_ids as $id ) {
			$subscriber = noptin_get_subscriber( $id );
			if ( $subscriber->exists() ) {
				$subscriber->delete( true );
			}
		}
		noptin_flush_subscriber_caches();
		parent::tear_down();
	}

	/**
	 * Execute a registered ability and fail clearly on unexpected errors.
	 *
	 * @param string $slug Ability slug.
	 * @param array  $input Ability input.
	 * @return array
	 */
	private function execute( $slug, $input ) {
		$ability = wp_get_ability( 'noptin/' . $slug );
		$this->assertNotNull( $ability );
		$result = $ability->execute( $input );
		$this->assertNotWPError( $result );
		return $result;
	}

	/** Create a uniquely addressed subscriber with a full name. @return array */
	private function create_subscriber() {
		$record = $this->execute(
			'create-subscriber',
			array( 'email' => 'ability+' . wp_generate_uuid4() . '@example.test', 'name' => 'Test Person', 'status' => 'inactive' )
		);
		$this->subscriber_ids[] = $record['id'];
		return $record;
	}

	/** All subscriber tools are discoverable and enforce subscriber management permissions. */
	public function test_registration_and_permissions() {
		foreach ( array( 'list-subscribers', 'get-subscriber', 'create-subscriber', 'update-subscriber', 'delete-subscriber', 'bulk-update-subscribers', 'bulk-delete-subscribers', 'run-subscriber-action', 'get-subscriber-tab', 'aggregate-subscribers', 'get-subscriber-field-options' ) as $slug ) {
			$ability = wp_get_ability( 'noptin/' . $slug );
			$this->assertNotNull( $ability, $slug );
			$this->assertTrue( $ability->get_meta()['show_in_rest'] );
			$this->assertTrue( $ability->get_meta()['mcp']['public'] );
			wp_set_current_user( 0 );
			// Read abilities may validate their identifier before checking collection access.
			$input = 'get-subscriber' === $slug ? array( 'id' => 1 ) : array();
			$this->assertFalse( $ability->check_permissions( $input ), $slug );
		}
		$user = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user );
		$ability = wp_get_ability( 'noptin/list-subscribers' );
		$this->assertFalse( $ability->check_permissions() );
		wp_get_current_user()->add_cap( get_noptin_collection_capability( 'subscribers' ) );
		$this->assertTrue( $ability->check_permissions() );
	}

	/** CRUD preserves ID/email lookups, full names, partial updates and collection response fields. */
	public function test_subscriber_lifecycle() {
		$created = $this->create_subscriber();
		$this->assertSame( 'Test', $created['first_name'] );
		$this->assertSame( 'Person', $created['last_name'] );
		$by_id = $this->execute( 'get-subscriber', array( 'id' => $created['id'] ) );
		$by_email = $this->execute( 'get-subscriber', array( 'email' => $created['email'] ) );
		// Action tokens and avatar fallback colors are randomized on each response.
		$random_fields = array_fill_keys( array( 'unsubscribe_url', 'resubscribe_url', 'confirm_subscription_url', 'manage_preferences_url', 'avatar_url' ), true );
		foreach ( $random_fields as $field => $unused ) {
			$this->assertNotEmpty( $by_id[ $field ] );
			$this->assertNotEmpty( $by_email[ $field ] );
		}
		$this->assertSame( array_diff_key( $by_id, $random_fields ), array_diff_key( $by_email, $random_fields ) );
		foreach ( noptin()->db()->store->get( 'subscribers' )->get_props() as $prop ) {
			$this->assertArrayHasKey( $prop->name, $by_id );
		}
		$this->assertNotEmpty( $by_id['edit_url'] );
		$updated = $this->execute( 'update-subscriber', array( 'id' => $created['id'], 'first_name' => 'Updated' ) );
		$this->assertSame( 'Updated', $updated['first_name'] );
		$this->assertSame( 'inactive', $updated['status'] );
		$this->assertWPError( wp_get_ability( 'noptin/create-subscriber' )->execute( array( 'email' => $created['email'] ) ) );
		$this->assertTrue( $this->execute( 'delete-subscriber', array( 'id' => $created['id'] ) )['deleted'] );
		$this->assertWPError( wp_get_ability( 'noptin/get-subscriber' )->execute( array( 'id' => $created['id'] ) ) );
	}

	/** Invalid selectors and writes cannot reach a mutation callback. */
	public function test_input_validation() {
		$get = wp_get_ability( 'noptin/get-subscriber' );
		foreach ( array( array(), array( 'id' => 0 ), array( 'email' => 'invalid' ) ) as $input ) {
			$this->assertWPError( $get->validate_input( $input ) );
		}
		// Selector exclusivity is enforced by callbacks for compatibility with MCP clients.
		$ambiguous = array( 'id' => 1, 'email' => 'a@example.test' );
		$permission = $get->check_permissions( $ambiguous );
		$this->assertWPError( $permission );
		$this->assertSame( 'hizzle_store_invalid_identifier', $permission->get_error_code() );
		// The Abilities API reports incorrect usage and wraps callback permission errors.
		$this->setExpectedIncorrectUsage( 'WP_Ability::execute' );
		$result = $get->execute( $ambiguous );
		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
		$this->assertWPError( wp_get_ability( 'noptin/update-subscriber' )->validate_input( array( 'id' => 1, 'edit_url' => 'https://example.test' ) ) );
		$this->assertWPError( wp_get_ability( 'noptin/bulk-delete-subscribers' )->validate_input( array( 'include' => array() ) ) );
	}

	/** List pagination and aggregate counts operate on the same explicit fixture selection. */
	public function test_listing_and_aggregation() {
		$this->create_subscriber();
		$this->create_subscriber();
		$input = array( 'include' => $this->subscriber_ids, 'per_page' => 1, 'orderby' => 'id', 'order' => 'asc' );
		$first = $this->execute( 'list-subscribers', $input );
		$second = $this->execute( 'list-subscribers', $input + array( 'paged' => 2 ) );
		$this->assertSame( 2, $first['total'] );
		$this->assertSame( 2, $first['total_pages'] );
		$this->assertSame( $this->subscriber_ids[0], $first['subscribers'][0]['id'] );
		$this->assertSame( $this->subscriber_ids[1], $second['subscribers'][0]['id'] );
		$summary = $this->execute( 'aggregate-subscribers', array( 'include' => $this->subscriber_ids, 'aggregate' => array( 'id' => 'COUNT' ), 'groupby' => 'status' ) );
		$row = (array) $summary['results'][0];
		$this->assertSame( 2, (int) $row['count_id'] );
	}

	/** Bulk changes respect selection, including blocked subscribers. */
	public function test_bulk_actions_and_tabs() {
		$first = $this->create_subscriber();
		$other = $this->create_subscriber();
		$this->execute( 'run-subscriber-action', array( 'id' => $first['id'], 'action' => 'block_subscriber' ) );
		$this->assertSame( 'blocked', noptin_get_subscriber( $first['id'] )->get_status() );
		$this->execute( 'bulk-update-subscribers', array( 'include' => array( $first['id'] ), 'bulk_update' => array( 'name' => 'Changed Person' ) ) );
		$this->assertSame( 'Changed', noptin_get_subscriber( $first['id'] )->get_first_name() );
		$this->assertSame( 'Test', noptin_get_subscriber( $other['id'] )->get_first_name() );
		foreach ( array( 'emails', 'activity' ) as $tab ) {
			$this->assertArrayHasKey( 'results', $this->execute( 'get-subscriber-tab', array( 'id' => $first['id'], 'tab_id' => $tab ) ) );
		}
		$this->execute( 'bulk-delete-subscribers', array( 'include' => array( $first['id'] ) ) );
		$this->assertFalse( noptin_get_subscriber( $first['id'] )->exists() );
		$this->assertTrue( noptin_get_subscriber( $other['id'] )->exists() );
	}

	/** Option discovery returns exact configured tag values containing spaces. */
	public function test_field_options() {
		update_option( 'noptin_subscriber_tags', array( 'Ability Test Tag' ) );
		$options = $this->execute( 'get-subscriber-field-options', array( 'field' => 'tags' ) );
		$this->assertContains( array( 'value' => 'Ability Test Tag', 'label' => 'Ability Test Tag' ), $options['options'] );
		$this->assertWPError( wp_get_ability( 'noptin/get-subscriber-field-options' )->execute( array( 'field' => 'email' ) ) );
	}

	/** Discovery preserves collection labels, enum labels, and open-ended tag inputs. */
	public function test_choice_schemas_and_labels() {
		$ability = wp_get_ability( 'noptin/list-subscribers' );
		$this->assertStringContainsString( 'Noptin email newsletter subscribers', $ability->get_label() );
		$create = wp_get_ability( 'noptin/create-subscriber' );
		$tags = $create->get_input_schema()['properties']['tags'];
		$this->assertStringContainsString( 'noptin/get-subscriber-field-options', $tags['description'] );
		$this->assertNotWPError( $create->validate_input( array(
			'email' => 'tag-test@example.test',
			'tags'  => array( 'New ' . wp_generate_uuid4() ),
		) ) );
		$statuses = $this->execute( 'get-subscriber-field-options', array( 'field' => 'status' ) );
		foreach ( noptin_get_subscriber_statuses() as $value => $label ) {
			$this->assertContains( array( 'value' => $value, 'label' => $label ), $statuses['options'] );
		}
	}
}
