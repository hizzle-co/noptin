<?php

use Hizzle\Noptin\Automation_Rules\Smart_Tags;

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** Tests subscriber tag merging through automations and the CSV import endpoint. */
class Test_Subscriber_Upsert_Tags extends Noptin_Regression_Test_Case {

	/** @var string[] Email addresses to clean up. */
	private $emails = array();

	/** Restore the batch flag and remove subscriber fixtures. */
	public function tear_down() {
		unset( $GLOBALS['noptin_subscribers_batch_action'] );
		foreach ( $this->emails as $email ) {
			$subscriber = noptin_get_subscriber( $email );
			if ( $subscriber->exists() ) {
				$subscriber->delete( true );
			}
		}
		parent::tear_down();
	}

	/** Legacy add settings retain existing tags, and explicit removals win on updates. */
	public function test_create_or_update_automation_merges_and_removes_tags() {
		$email     = $this->new_address();
		$new_email = $this->new_address();
		$id        = add_noptin_subscriber( array( 'email' => $email, 'tags' => array( 'Existing tag', 'Remove me' ) ) );
		$this->assertIsInt( $id );

		$rule = noptin()->db()->get( 0, 'automation_rules' );
		$rule->set_action_id( 'subscribe' );
		$action = $rule->get_action();
		$this->assertNotNull( $action );
		$this->assertSame( 'Tags to add', $action->get_settings()['tags']['label'] );
		$this->assertSame( 'Tags to remove', $action->get_settings()['tags::remove']['label'] );

		$args = array( 'smart_tags' => new Smart_Tags( null, null ) );
		$rule->set_action_settings(
			array(
				'email'           => $email,
				'update_existing' => true,
				'tags'            => 'New tag, Existing tag, New tag',
			)
		);
		$this->assertNotWPError( $action->run( null, $rule, $args ) );
		$this->assertSame( array( 'Existing tag', 'Remove me', 'New tag' ), noptin_get_subscriber( $id )->get( 'tags' ) );

		$settings                 = $rule->get_action_settings();
		$settings['tags::remove'] = 'Remove me, New tag';
		$rule->set_action_settings( $settings );
		$this->assertNotWPError( $action->run( null, $rule, $args ) );
		$this->assertNotWPError( $action->run( null, $rule, $args ) );
		$this->assertSame( array( 'Existing tag' ), noptin_get_subscriber( $id )->get( 'tags' ) );

		$settings['email'] = $new_email;
		$rule->set_action_settings( $settings );
		$this->assertIsInt( $action->run( null, $rule, $args ) );
		$this->assertSame( array( 'New tag', 'Existing tag' ), noptin_get_subscriber( $new_email )->get( 'tags' ) );

		$settings['tags']         = '0';
		$settings['tags::remove'] = '';
		$rule->set_action_settings( $settings );
		$this->assertNotWPError( $action->run( null, $rule, $args ) );
		$this->assertContains( '0', noptin_get_subscriber( $new_email )->get( 'tags' ) );

		$settings['tags']         = '';
		$settings['tags::remove'] = '0';
		$rule->set_action_settings( $settings );
		$this->assertNotWPError( $action->run( null, $rule, $args ) );
		$this->assertSame( array( 'New tag', 'Existing tag' ), noptin_get_subscriber( $new_email )->get( 'tags' ) );
	}

	/** The CSV batch preserves existing tags and creates new subscribers with unique tags. */
	public function test_import_merges_tags_and_blank_cells_leave_tags_intact() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$email     = $this->new_address();
		$new_email = $this->new_address();
		$id        = add_noptin_subscriber( array( 'email' => $email, 'tags' => array( 'Existing tag' ) ) );
		$this->assertIsInt( $id );

		$request = new WP_REST_Request( 'POST', '/noptin/v1/subscribers/batch' );
		$request->set_body_params(
			array(
				'update' => true,
				'import' => array(
					array( 'email' => $email, 'tags' => 'Added tag, Existing tag, Added tag' ),
					array( 'email' => $email, 'tags' => ' , , ' ),
					array( 'email' => $email, 'tags' => '0' ),
					array( 'email' => $new_email, 'tags' => 'New tag, New tag' ),
				),
			)
		);
		$response = rest_do_request( $request );
		$this->assertFalse( $response->is_error(), wp_json_encode( $response->get_data() ) );
		$rows = $response->get_data()['import'];
		$this->assertCount( 4, $rows );
		$this->assertSame( array( 'updated' => true, 'id' => $id ), $rows[0]['data'] );
		$this->assertSame( array( 'updated' => true, 'id' => $id ), $rows[1]['data'] );
		$this->assertSame( array( 'updated' => true, 'id' => $id ), $rows[2]['data'] );
		$this->assertTrue( $rows[3]['data']['created'] );
		$this->assertSame( array( 'Existing tag', 'Added tag', '0' ), noptin_get_subscriber( $id )->get( 'tags' ) );
		$this->assertSame( array( 'New tag' ), noptin_get_subscriber( $new_email )->get( 'tags' ) );

		$request = new WP_REST_Request( 'POST', '/noptin/v1/subscribers/batch' );
		$request->set_body_params(
			array(
				'update' => false,
				'import' => array( array( 'email' => $email, 'tags' => 'Skipped tag' ) ),
			)
		);
		$response = rest_do_request( $request );
		$this->assertFalse( $response->is_error(), wp_json_encode( $response->get_data() ) );
		$this->assertSame( array( 'skipped' => true ), $response->get_data()['import'][0]['data'] );
		$this->assertSame( array( 'Existing tag', 'Added tag', '0' ), noptin_get_subscriber( $id )->get( 'tags' ) );

		// An explicit batch update can still replace the complete token list.
		$request = new WP_REST_Request( 'POST', '/noptin/v1/subscribers/batch' );
		$request->set_body_params(
			array(
				'update' => array(
					array( 'id' => $id, 'tags' => array( 'Replacement' ) ),
				),
			)
		);
		$response = rest_do_request( $request );
		$this->assertFalse( $response->is_error(), wp_json_encode( $response->get_data() ) );
		$this->assertSame( array( 'Replacement' ), noptin_get_subscriber( $id )->get( 'tags' ) );
	}

	/** Both import inputs contribute additions for string and array representations. */
	public function test_import_combines_base_and_explicit_additions() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$email = $this->new_address();
		$id    = add_noptin_subscriber( array( 'email' => $email, 'tags' => array( 'Existing tag' ) ) );

		foreach ( array(
			array( 'Member, Shared', array( 'Volunteer', 'Shared', 'Literal, comma' ) ),
			array( array( 'Member', 'Shared' ), 'Volunteer, Shared' ),
			array( 'Member, Shared', 'Volunteer, Shared' ),
			array( array( 'Member', 'Shared' ), array( 'Volunteer', 'Shared' ) ),
		) as $inputs ) {
			$request = new WP_REST_Request( 'POST', '/noptin/v1/subscribers/batch' );
			$request->set_body_params(
				array(
					'update' => true,
					'import' => array(
						array( 'email' => $email, 'tags' => $inputs[0], 'tags::add' => $inputs[1] ),
					),
				)
			);
			$response = rest_do_request( $request );
			$this->assertFalse( $response->is_error(), wp_json_encode( $response->get_data() ) );
			$this->assertSame( array( 'updated' => true, 'id' => $id ), $response->get_data()['import'][0]['data'] );
			$this->assertSame(
				array( 'Existing tag', 'Member', 'Shared', 'Volunteer', 'Literal, comma' ),
				noptin_get_subscriber( $id )->get( 'tags' )
			);
		}
	}

	/** Multi-value fields use the same rules even when they aren't token fields. */
	public function test_non_token_multi_value_field_normalizes_values() {
		$record = new class extends \Hizzle\Store\Record {
			/** Initialize a stored multi-select field. */
			public function __construct() {
				$this->data['choices'] = array( 'Keep me' );
				$this->set_object_read( true );
			}

			/** Resolve the fixture's field without a database schema change. */
			public function has_prop( $key ) {
				if ( $key instanceof \Hizzle\Store\Prop ) {
					return $key;
				}

				return 'choices' === $key ? new \Hizzle\Store\Prop(
					'fixture',
					array(
						'name'                 => 'choices',
						'type'                 => 'VARCHAR',
						'is_meta_key'          => true,
						'is_meta_key_multiple' => true,
						'is_tokens'            => false,
					)
				) : null;
			}
		};

		$record->set( 'choices::add', ' , ' );
		$record->set( 'choices::remove', array( '' ) );
		$this->assertSame( array(), $record->get_changes() );

		$record->set( 'choices', array( 'Keep me', 'New choice', '', ' New choice ', '0' ) );
		$this->assertSame( array( 'Keep me', 'New choice', '0' ), $record->get( 'choices' ) );
		$record->set( 'choices::remove', 'New choice' );
		$this->assertSame( array( 'Keep me', '0' ), $record->get( 'choices' ) );
		$record->set( 'choices', array() );
		$this->assertSame( array(), $record->get( 'choices' ) );

		$changes = array(
			'choices::remove' => array( 'Remove me' ),
			'choices'         => array( 'Keep me', 'Remove me' ),
			'choices::add'    => array( 'Added', 'Remove me' ),
		);
		$record->set_props( $changes );
		$this->assertSame( array( 'Keep me', 'Added' ), $record->get( 'choices' ) );
		$record->set_props( array_reverse( $changes, true ) );
		$this->assertSame( array( 'Keep me' ), $record->get( 'choices' ) );
		$record->set_props( array( 'choices::remove' => null ), true );
		$this->assertSame( array( 'Keep me' ), $record->get( 'choices' ) );
	}

	/** Public list parsing filters blanks after trimming and preserves zero. */
	public function test_list_parser_filters_empty_values() {
		$this->assertSame(
			array( 'Member', '0', 'Member' ),
			\Hizzle\Store\Record::parse_list( ' , Member, , 0, Member, ', true )
		);
		$this->assertSame(
			array( 'Member', '0', 'Literal, comma' ),
			\Hizzle\Store\Record::parse_list( array( '', ' Member ', ' ', '0', 'Literal, comma' ), true )
		);
		$this->assertSame( array( '0' ), \Hizzle\Store\Record::parse_list( '0', true ) );
		$this->assertSame( array(), \Hizzle\Store\Record::parse_list( ' , ', true ) );
		$this->assertSame( array( 'Member', 'Volunteer', '0' ), \Hizzle\Store\Record::parse_list( ' Member Volunteer, 0 ' ) );
	}

	/** Generate and track a test email address. */
	private function new_address() {
		$email          = wp_generate_uuid4() . '@example.test';
		$this->emails[] = $email;
		return $email;
	}
}
