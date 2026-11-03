<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** Regression coverage for text field sanitization. */
class Test_Text_Field_Sanitization extends Noptin_Regression_Test_Case {

	public function test_text_field_sanitization() {
		$assert = array( $this, 'assertTrue' );
		$property = static function ( $class, $type, $extra = array() ) {
			$field = new $class( $type );
			$schema = $field->filter_db_schema( array(), array_merge( array( 'type' => $type, 'merge_tag' => 'fixture', 'label' => 'Fixture' ), $extra ) );
			return new Hizzle\Store\Prop( 'noptin_subscribers', array_merge( $schema['fixture'], array( 'name' => 'fixture' ) ) );
		};
		$text = $property( Hizzle\Noptin\Fields\Types\Text::class, 'text' );
		$shortcode_calls = 0;
		add_shortcode( 'noptin_sanitizer_probe', static function () use ( &$shortcode_calls ) { ++$shortcode_calls; return 'must not execute'; } );
		foreach ( array(
			array( '&lt;img src=x onerror=alert(1)&gt;', '' ),
			array( '&#60;img src=x onerror=alert(1)&#62;', '' ),
			array( 'before&lt;script&gt;alert(1)&lt;/script&gt;after', 'beforeafter' ),
			array( 'Tom &amp; Jerry &quot;hello&quot;', 'Tom & Jerry "hello"' ),
			array( 'Zo&euml; &amp; Jos&eacute;', 'Zoë & José' ),
			array( "  one\n two\tthree  ", 'one two three' ),
			array( '&amp;lt;example&amp;gt;', '&lt;example&gt;' ),
			array( 'Before [[subscriber.first_name]] After', 'Before After' ),
			array( '[[posts the_template_path="/harmless/marker.txt"]]', '' ),
			array( 'Before [[posts title="[[blog_name]]"]] After', 'Before After' ),
			array( 'Before &#91;&#91;posts&#93;&#93; After', 'Before After' ),
			array( 'Before &amp;#91;&amp;#91;posts&amp;#93;&amp;#93; After', 'Before After' ),
			array( 'Before {subscriber.first_name} {{blog_name}} After', 'Before After' ),
			array( 'Before [gallery ids="1,2"] After', 'Before After' ),
			array( 'Before [noptin_sanitizer_probe] After', 'Before After' ),
			array( 'Before [noptin_unknown_shortcode /] After', 'Before After' ),
			array( 'Before [noptin_unknown_shortcode]hidden[/noptin_unknown_shortcode] After', 'Before After' ),
			array( 'Before [outer][inner]hidden[/inner][/outer] After', 'Before After' ),
			array( 'Before &#91;gallery&#93; After', 'Before After' ),
			array( 'Before [gal<b>lery</b>] After', 'Before After' ),
			array( 'Before [gal%20lery] After', 'Before After' ),
			array( 'Before [[gal{blog_name}lery]] After', 'Before After' ),
			array( 'Ordinary (parentheses) and {"key":"value"}', 'Ordinary (parentheses) and {"key":"value"}' ),
			array( 0, '0' ),
			array( false, '' ),
			array( array( 'invalid' ), '' ),
			array( new stdClass(), '' ),
			array( null, null ),
		) as list( $input, $expected ) ) {
			$assert( $expected === $text->sanitize( $input ), 'Text property normalization failed.' );
		}
		$assert( 0 === $shortcode_calls, 'Sanitizing text must never execute shortcode callbacks.' );
		remove_shortcode( 'noptin_sanitizer_probe' );
		$textarea = $property( Hizzle\Noptin\Fields\Types\Textarea::class, 'textarea' );
		$assert( "Hello\n&lt;example&gt; &amp; team" === $textarea->sanitize( "<b>Hello</b>\n&lt;example&gt; &amp; team" ), 'Textarea must preserve encoded text and line breaks.' );
		$assert( 'Hello [[posts]] [gallery]' === $textarea->sanitize( 'Hello [[posts]] [gallery]' ), 'Textarea behavior must remain unchanged.' );
		$email = $property( Hizzle\Noptin\Fields\Types\Email::class, 'email' );
		$assert( $email->sanitize_callback[0] instanceof Hizzle\Noptin\Fields\Types\Email && 'person+tag@example.test' === $email->sanitize( 'person+tag@example.test' ), 'Email must retain its specialized callback.' );
		$number = $property( Hizzle\Noptin\Fields\Types\Number::class, 'number' );
		$assert( empty( $number->sanitize_callback ) && 3.75 === $number->sanitize( '3.75' ), 'Number must retain native numeric conversion.' );
		$date = $property( Hizzle\Noptin\Fields\Types\Date::class, 'date' );
		$assert( empty( $date->sanitize_callback ) && '2026-10-30' === $date->sanitize( '2026-10-30' ), 'Date must retain native date conversion.' );
		$datetime = $property( Hizzle\Noptin\Fields\Types\Date_Time::class, 'date_time' );
		$assert( empty( $datetime->sanitize_callback ) && '2026-10-30T11:12:13' === $datetime->sanitize( '2026-10-30T11:12:13' ), 'Datetime must retain its existing property sanitization.' );
		$dropdown = $property( Hizzle\Noptin\Fields\Types\Dropdown::class, 'dropdown', array( 'options' => "A &amp; B\nOther" ) );
		$assert( 'A &amp; B' === $dropdown->sanitize( 'A &amp; B' ), 'Choice values must not be entity-decoded.' );
		$subscriber = noptin_get_subscriber();
		$subscriber->set_first_name( '&lt;img src=x onerror=alert(1)&gt;' );
		$subscriber->set_last_name( 'O&apos;Brien' );
		$assert( '' === $subscriber->get_first_name( 'edit' ) && "O'Brien" === $subscriber->get_last_name( 'edit' ), 'Name setters must use the schema callback.' );
		$subscriber = noptin_get_subscriber();
		$subscriber->set_name( 'Tom &amp; Jerry' );
		$assert( 'Tom & Jerry' === $subscriber->get_name( 'edit' ), 'Full names must use the text sanitizer.' );
		$subscriber = noptin_get_subscriber();
		$subscriber->set_name( 'Tom [[posts]] [gallery] Jerry' );
		$assert( 'Tom Jerry' === $subscriber->get_name( 'edit' ), 'Full names must strip merge tags and shortcodes.' );
		$subscriber->set_first_name( str_repeat( 'é', 110 ) );
		$assert( 100 === mb_strlen( $subscriber->get_first_name( 'edit' ) ), 'Name length limits must remain effective.' );
		$form = 0;
		$signup_email = 'codex-text-sanitization-' . wp_generate_uuid4() . '@example.test';
		try {
			$form = wp_insert_post( array( 'post_type' => 'noptin-form', 'post_status' => 'publish', 'post_title' => 'Temporary text sanitization regression' ) );
			update_post_meta( $form, '_noptin_state', array( 'fields' => 'first_name,last_name,email', 'doubleOptin' => 'disabled', 'subscribeAction' => 'message', 'update_existing' => true ) );
			$request = new WP_REST_Request( 'POST', '/noptin/v1/form' );
			$request->set_body_params( array(
				'source' => $form,
				'noptin_fields' => array( 'email' => $signup_email, 'first_name' => '&lt;img src=x onerror=alert(1)&gt;', 'last_name' => 'O&apos;Brien' ),
				'noptin_timestamp' => time() - 5,
				'noptin_submitted' => time(),
				'noptin_ign' => '',
				'noptin_nonce' => wp_create_nonce( 'noptin_subscription_nonce' ),
			) );
			$_REQUEST['noptin_nonce'] = $request['noptin_nonce'];
			$listener = new Hizzle\Noptin\Forms\Listener();
			$response = $listener->handle_endpoint( $request )->get_data();
			$assert( ! empty( $response['success'] ), 'Public signup failed.' );
			$saved = noptin_get_subscriber( $signup_email );
			$assert( $saved->exists() && '' === $saved->get_first_name( 'edit' ) && "O'Brien" === $saved->get_last_name( 'edit' ), 'Public signup must persist decoded plain names.' );
			// Resubmit through the public signup handler, then verify the persisted values.
			$request->set_param( 'noptin_fields', array( 'email' => $signup_email, 'first_name' => 'Jane [[posts the_template_path="/harmless/marker.txt"]]', 'last_name' => 'Doe [noptin_unknown_shortcode /]' ) );
			$listener = new Hizzle\Noptin\Forms\Listener();
			$response = $listener->handle_endpoint( $request )->get_data();
			$assert( ! empty( $response['success'] ), 'Public signup with tag-like input must still succeed.' );
			$saved = noptin_get_subscriber( $signup_email );
			$assert( 'Jane' === $saved->get_first_name( 'edit' ) && 'Doe' === $saved->get_last_name( 'edit' ), 'Public signup must persist names without executable syntax.' );
			$saved->set_first_name( 'Zo&euml;' );
			$saved->set_last_name( '&#60;img src=x onerror=alert(2)&#62; [[posts]] [gallery]' );
			$saved->save();
			$reloaded = noptin_get_subscriber( $signup_email );
			$assert( 'Zoë' === $reloaded->get_first_name( 'edit' ) && '' === $reloaded->get_last_name( 'edit' ), 'Updating an existing subscriber must use the same normalization.' );

		} finally {
			$cleanup = noptin_get_subscriber( $signup_email );
			if ( $cleanup->exists() ) $cleanup->delete();
			if ( $form ) wp_delete_post( $form, true );

		}
	}
}
