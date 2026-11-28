<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** Segment abilities use the same validation and mutations as the REST endpoints. */
class Test_Segment_Abilities extends Noptin_Regression_Test_Case {

	/** Require the Abilities API and authenticate an administrator. */
	public function set_up() {
		parent::set_up();
		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'The WordPress Abilities API is required.' );
		}
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Execute a registered segment ability.
	 *
	 * @param string $slug Ability slug.
	 * @param array  $args Input settings.
	 * @return array
	 */
	private function execute( $slug, $args ) {
		$ability = wp_get_ability( 'noptin/' . $slug );
		$this->assertNotNull( $ability );
		$result = $ability->execute( $args );
		$this->assertNotWPError( $result );
		return $result;
	}

	/** Both mutations are discoverable, permission checked, and described as writes. */
	public function test_registration_and_permissions() {
		foreach ( array( 'save-subscriber-segment', 'delete-subscriber-segment' ) as $slug ) {
			$ability = wp_get_ability( 'noptin/' . $slug );
			$this->assertNotNull( $ability );
			$this->assertTrue( $ability->get_meta()['show_in_rest'] );
			$this->assertTrue( $ability->get_meta()['mcp']['public'] );
			$this->assertTrue( $ability->get_meta()['annotations']['destructive'] );
			$this->assertFalse( $ability->get_meta()['annotations']['readonly'] );
			$this->assertStringContainsString( 'newsletter subscriber', $ability->get_label() );
			$this->assertStringContainsString( 'Noptin email newsletter subscriber', $ability->get_description() );
			wp_set_current_user( 0 );
			$this->assertFalse( $ability->check_permissions( array( 'merge_tag' => 'example' ) ) );
		}
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		foreach ( array( 'save-subscriber-segment', 'delete-subscriber-segment' ) as $slug ) {
			$this->assertFalse( wp_get_ability( 'noptin/' . $slug )->check_permissions( array( 'merge_tag' => 'example' ) ) );
		}
		wp_get_current_user()->add_cap( get_noptin_collection_capability( 'subscribers' ) );
		$this->assertTrue( wp_get_ability( 'noptin/save-subscriber-segment' )->check_permissions() );
		$this->assertTrue( wp_get_ability( 'noptin/delete-subscriber-segment' )->check_permissions() );
	}

	/** The schema exposes REST settings without callbacks or update-overwriting defaults. */
	public function test_schema_and_validation() {
		$ability = wp_get_ability( 'noptin/save-subscriber-segment' );
		$schema = $ability->get_input_schema();
		$this->assertSame( array( 'merge_tag' ), $schema['required'] );
		foreach ( Hizzle\Noptin\Subscribers\Fields_REST_API::get_custom_field_args() as $name => $definition ) {
			$this->assertSame( $definition['type'], $schema['properties'][$name]['type'] );
			$this->assertArrayNotHasKey( 'sanitize_callback', $schema['properties'][$name] );
			$this->assertArrayNotHasKey( 'validate_callback', $schema['properties'][$name] );
			$this->assertArrayNotHasKey( 'default', $schema['properties'][$name] );
		}
		$this->assertSame( array( 'dropdown', 'multi_checkbox' ), $schema['properties']['type']['enum'] );
		$this->assertNotWPError( $ability->validate_input( array( 'merge_tag' => 'example', 'label' => 'Renamed' ) ) );
		foreach ( array( array( 'label' => 'Missing identifier' ), array( 'merge_tag' => '' ), array( 'merge_tag' => 'example', 'type' => 'invalid' ), array( 'merge_tag' => 'example', 'options' => array( 'invalid' ) ), array( 'merge_tag' => 'example', 'unexpected' => true ) ) as $invalid ) {
			$this->assertWPError( $ability->validate_input( $invalid ) );
		}
		$this->assertWPError( wp_get_ability( 'noptin/delete-subscriber-segment' )->validate_input( array() ) );
	}

	/** Saves create, rename, and change supplied settings; deletion removes the definition. */
	public function test_segment_lifecycle() {
		$key = 'ability_segment';
		$created = $this->execute( 'save-subscriber-segment', array( 'merge_tag' => $key, 'label' => 'Original', 'type' => 'dropdown', 'options' => "Choice with spaces|Choice label\nAnother choice" ) );
		$this->assertSame( $key, $created['field']['merge_tag'] );
		$renamed = $this->execute( 'save-subscriber-segment', array( 'merge_tag' => $key, 'label' => 'Renamed' ) );
		$this->assertSame( 'Renamed', $renamed['field']['label'] );
		$this->assertSame( $created['field']['type'], $renamed['field']['type'] );
		$this->assertSame( $created['field']['options'], $renamed['field']['options'] );
		$changed = $this->execute( 'save-subscriber-segment', array( 'merge_tag' => $key, 'type' => 'multi_checkbox', 'options' => 'Updated choice with spaces', 'visible' => false, 'required' => true, 'predefined' => false ) );
		$this->assertSame( 'multi_checkbox', $changed['field']['type'] );
		$this->assertSame( 'Updated choice with spaces', $changed['field']['options'] );
		$this->assertFalse( $changed['field']['visible'] );
		$this->assertTrue( $changed['field']['required'] );
		$this->assertFalse( $changed['field']['predefined'] );
		$this->assertSame( 'Renamed', $changed['field']['label'] );
		$this->assertCount( 1, wp_list_filter( get_noptin_option( 'custom_fields' ), array( 'merge_tag' => $key ) ) );
		$cleared = $this->execute( 'save-subscriber-segment', array( 'merge_tag' => $key, 'options' => '', 'required' => false ) );
		$this->assertSame( '', $cleared['field']['options'] );
		$this->assertFalse( $cleared['field']['required'] );
		// Real tool calls initialize schema in separate HTTP requests. Expose this
		// process-local field as the next request's subscriber filter metadata would.
		add_filter( 'noptin_subscriber_filters', static function ( $fields ) use ( $key ) {
			$fields[ $key ] = array( 'label' => 'Renamed', 'is_multiple' => true, 'options' => array() );
			return $fields;
		} );
		$deleted = $this->execute( 'delete-subscriber-segment', array( 'merge_tag' => $key ) );
		$this->assertSame( array( 'value' => $key, 'deleted' => true ), $deleted );
		$this->assertCount( 0, wp_list_filter( get_noptin_option( 'custom_fields' ), array( 'merge_tag' => $key ) ) );
	}

	/** Tags remains protected through abilities as well as direct REST requests. */
	public function test_tags_protection() {
		$saved = get_noptin_option( 'custom_fields', Hizzle\Noptin\Fields\Main::default_fields() );
		foreach ( array( 'save-subscriber-segment', 'delete-subscriber-segment' ) as $slug ) {
			$result = wp_get_ability( 'noptin/' . $slug )->execute( array( 'merge_tag' => 'tags' ) );
			$this->assertWPError( $result );
			$this->assertSame( 'noptin_protected_field', $result->get_error_code() );
		}
		$this->assertSame( $saved, get_noptin_option( 'custom_fields', Hizzle\Noptin\Fields\Main::default_fields() ) );
	}
}
