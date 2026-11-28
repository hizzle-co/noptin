<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** Covers segment creates and partial updates through the shared REST endpoint. */
class Test_Segment_Field_Save extends Noptin_Regression_Test_Case {

	/** Authenticate an administrator for the real REST permission callback. */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Send JSON through REST validation and sanitization.
	 *
	 * @param array $values Field settings.
	 * @return WP_REST_Response
	 */
	private function save_field( $values ) {
		$request = new WP_REST_Request( 'POST', '/noptin/v1/subscribers/fields' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $values ) );
		return rest_do_request( $request );
	}

	/** Partial updates retain omitted settings and never append duplicates. */
	public function test_upsert_preserves_omitted_settings() {
		$values = array(
			'merge_tag' => 'regression_segment',
			'label' => 'Original segment',
			'type' => 'dropdown',
			'options' => "Choice with spaces|Choice label\nOther choice",
			'visible' => false,
			'required' => true,
			'predefined' => false,
		);
		$created = $this->save_field( $values );
		$this->assertSame( 200, $created->get_status() );
		$original = $created->get_data()['field'];
		$renamed = $this->save_field( array( 'merge_tag' => 'regression_segment', 'label' => 'Renamed segment' ) );
		$this->assertSame( 200, $renamed->get_status() );
		$this->assertSame( array_replace( $original, array( 'label' => 'Renamed segment' ) ), $renamed->get_data()['field'] );

		$changed = $this->save_field( array(
			'merge_tag' => 'regression_segment',
			'type' => 'multi_checkbox',
			'options' => "New choice with spaces\nAnother choice",
			'visible' => true,
			'required' => false,
			'predefined' => false,
		) );
		$this->assertSame( 200, $changed->get_status() );
		$field = $changed->get_data()['field'];
		$this->assertSame( 'multi_checkbox', $field['type'] );
		$this->assertSame( "New choice with spaces\nAnother choice", $field['options'] );
		$this->assertTrue( $field['visible'] );
		$this->assertFalse( $field['required'] );
		$this->assertFalse( $field['predefined'] );
		$this->assertSame( 'Renamed segment', $field['label'] );
		$this->assertContains( $field, get_noptin_option( 'custom_fields' ) );
		$matches = wp_list_filter( get_noptin_option( 'custom_fields' ), array( 'merge_tag' => 'regression_segment' ) );
		$this->assertCount( 1, $matches );

		$cleared = $this->save_field( array( 'merge_tag' => 'regression_segment', 'options' => '' ) );
		$this->assertSame( 200, $cleared->get_status() );
		$this->assertSame( '', $cleared->get_data()['field']['options'] );
		$this->assertSame( 'multi_checkbox', $cleared->get_data()['field']['type'] );
	}

	/** Invalid partial updates must leave the saved field unchanged. */
	public function test_validation_keeps_saved_values() {
		$created = $this->save_field( array( 'merge_tag' => 'regression_segment', 'label' => 'Original', 'type' => 'dropdown' ) );
		$this->assertSame( 200, $created->get_status() );
		$saved = get_noptin_option( 'custom_fields' );
		foreach ( array( array( 'label' => '   ' ), array( 'type' => 'invalid' ), array( 'options' => array( 'invalid' ) ) ) as $invalid ) {
			$response = $this->save_field( array_merge( array( 'merge_tag' => 'regression_segment' ), $invalid ) );
			$this->assertSame( 400, $response->get_status(), wp_json_encode( array( $invalid, $response->get_data() ) ) );
			$this->assertSame( $saved, get_noptin_option( 'custom_fields' ) );
		}
		$this->assertSame( 400, $this->save_field( array( 'merge_tag' => 'new_segment', 'label' => 'Missing type' ) )->get_status() );
		$this->assertSame( 400, $this->save_field( array( 'merge_tag' => 'new_segment', 'type' => 'dropdown' ) )->get_status() );
	}

	/** Reject Tags before looking up custom fields, even without other settings. */
	public function test_tags_rejected_before_field_lookup() {
		$reads = 0;
		$track_reads = static function ( $fields ) use ( &$reads ) {
			++$reads;
			return $fields;
		};
		add_filter( 'get_noptin_option_custom_fields', $track_reads );
		try {
			foreach ( array( array( 'merge_tag' => 'tags' ), array( 'merge_tag' => 'TAGS' ), array( 'label' => 'Tags' ) ) as $values ) {
				$request = new WP_REST_Request( 'POST' );
				$request->set_body_params( $values );
				$result = Hizzle\Noptin\Subscribers\Fields_REST_API::create_custom_field( $request );
				$this->assertWPError( $result );
				$this->assertSame( 'noptin_protected_field', $result->get_error_code() );
				$this->assertSame( 400, $result->get_error_data()['status'] );
			}
			$this->assertSame( 0, $reads );
		} finally {
			remove_filter( 'get_noptin_option_custom_fields', $track_reads );
		}
	}

	/** Built-in fields and permission checks still apply to the shared path. */
	public function test_protection_and_permissions() {
		$saved = get_noptin_option( 'custom_fields', Hizzle\Noptin\Fields\Main::default_fields() );
		$this->assertSame( 400, $this->save_field( array( 'merge_tag' => 'tags', 'label' => 'Changed', 'type' => 'dropdown' ) )->get_status() );
		$this->assertSame( $saved, get_noptin_option( 'custom_fields', Hizzle\Noptin\Fields\Main::default_fields() ) );
		wp_set_current_user( 0 );
		$this->assertSame( 401, $this->save_field( array( 'merge_tag' => 'not_allowed', 'label' => 'Denied', 'type' => 'dropdown' ) )->get_status() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertSame( 403, $this->save_field( array( 'merge_tag' => 'not_allowed', 'label' => 'Denied', 'type' => 'dropdown' ) )->get_status() );
	}
}
