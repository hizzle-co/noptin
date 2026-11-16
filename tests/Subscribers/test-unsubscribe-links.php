<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

use Hizzle\Noptin\Subscribers\Unsubscribe_Links as Links;
use Hizzle\Noptin\Emails\Main as Emails;

/** First-party regression coverage migrated from unsubscribe-links.php. */
class Test_Unsubscribe_Links extends Noptin_Regression_Test_Case {
	/** Verify unsubscribe links using the isolated WordPress test database. */
	public function test_unsubscribe_links() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		remove_all_actions( 'noptin_newsletter_campaign_published' );
		remove_all_actions( 'noptin_send_newsletter' );
		add_filter( 'noptin_saved_task_background_run', '__return_false' );
		remove_action( 'shutdown', array( $GLOBALS['noptin_tasks'], 'run_overdue_tasks_on_shutdown' ), 1 );
		$assert = array( $this, 'assertTrue' );
		$key = 'codex_' . str_replace( '-', '', wp_generate_uuid4() );
		$change = static fn( $segment, $action, $value ) => array( 'segment' => $segment, 'action' => $action, 'values' => array( $segment => $value ) );
		$link = array(
			'key' => $key,
			'changes' => array( $change( 'tags', 'add', array( 'nopuzzles', 'A tag with spaces' ) ) ),
			'message' => '<h1>Custom preferences saved</h1><p>Other emails are unchanged.</p>',
			'redirect' => '',
		);
		$redirect_link = array_merge( $link, array( 'key' => $key . '_redirect', 'redirect' => home_url( '/?codex-preferences-saved=1' ) ) );

		// Filter settings in this process only. Never replace saved site preferences.
		$test_links = array( $link );
		$original_options = get_option( 'noptin_options', array() );
		add_filter( 'pre_option_noptin_options', static function ( $value ) use ( &$test_links, &$original_options ) {
			$options = $original_options;
			$options['custom_unsubscribe_links'] = $test_links;
			return $options;
		} );

		$sanitized = Links::sanitize_settings( array( 'custom_unsubscribe_links' => array( $link, $link, array() ) ) );
		$keys = wp_list_pluck( $sanitized['custom_unsubscribe_links'], 'key' );
		$assert( $keys[0] === $key && count( array_unique( $keys ) ) === 3, 'Missing and duplicate keys must receive unique stable keys.' );
		$assert( 8 === strlen( $keys[1] ) && 8 === strlen( $keys[2] ), 'New server-generated keys should be eight characters.' );
		$assert( Links::sanitize_settings( $sanitized ) === $sanitized, 'Saving twice must not regenerate keys.' );
		$reordered = array( 'custom_unsubscribe_links' => array_reverse( $sanitized['custom_unsubscribe_links'] ) );
		$assert( $reordered === Links::sanitize_settings( $reordered ), 'Reordering must preserve keys.' );
		$settings = noptin()->actions_page->add_options( array() );
		$row = $settings['custom_unsubscribe_links']['customAttributes']['fields']['changes']['customAttributes']['fields']['preference_change'];
		$assert( 'horizontal' === $row['el'], 'Preference-change fields must use the shared horizontal layout.' );
		$tag_field = $row['settings']['values.tags'];
		$assert( 'token' === $tag_field['el'] && get_noptin_subscriber_tags() === $tag_field['suggestions'], 'Tag inputs must load existing subscriber and configured tags as suggestions.' );
		$assert( isset( $settings['pages_unsubscribe']['settings']['pages_unsubscribe_page_message'], $settings['pages_unsubscribe']['settings']['pages_unsubscribe_page'], $settings['custom_unsubscribe_links'] ), 'Default and custom settings must coexist.' );
		$assert( ! isset( $settings['pages_unsubscribe']['settings']['custom_unsubscribe_links'] ) && false === $settings['custom_unsubscribe_links']['customAttributes']['repeaterKey']['collapsible'], 'Custom links must have standalone expanded cards outside the default group.' );
		$assert( ! isset( Links::segments()['status'], Links::segments()['email'] ), 'Link actions must not expose account identity or status.' );

		$decode_url = static function ( $url ) {
			parse_str( wp_parse_url( html_entity_decode( $url ), PHP_URL_QUERY ), $query );
			return json_decode( noptin_decrypt( $query['nv'] ), true );
		};
		Emails::init_current_email_recipient( array( 'email' => 'fixture@example.test', 'subscriber' => 1, 'cid' => 1715 ) );
		$payload = $decode_url( noptin_parse_email_content_tags( '[[unsubscribe_url type="' . $key . '"]]' ) );
		$assert( $payload['unsubscribe_type'] === $key && $payload['email'] === 'fixture@example.test' && 1715 === $payload['cid'], 'Typed merge tags must bind type, recipient, and campaign.' );
		$assert( ! isset( $decode_url( Emails::get_current_unsubscribe_url() )['unsubscribe_type'] ), 'Default link must remain global.' );
		$assert( '' === Emails::get_current_unsubscribe_url( array( 'type' => 'deleted-link' ) ), 'Unknown merge-tag types must not generate a global unsubscribe.' );
		Emails::$current_email_recipient['unsubscribe_type'] = $key;
		$assert( ! isset( $decode_url( Emails::get_current_unsubscribe_url() )['unsubscribe_type'] ), 'Default headers must not inherit a custom type from the current recipient.' );
		$assert( '' === Links::redirect( 'https://default.test/', 'unsubscribe' ), 'A custom message must not inherit the global redirect.' );
		$test_links[0]['redirect'] = home_url( '/preferences-saved/' );
		$assert( home_url( '/preferences-saved/' ) === Links::redirect( '', 'unsubscribe' ), 'Custom redirect missing.' );

		// Additional fields exist only in this process; fake persistence exercises scalar
		// and multiple values without changing the site's schema or existing segments.
		add_filter( 'noptin_subscriber_filters', static function ( $fields ) {
			foreach ( array( 'test_lists' => 'multi_checkbox', 'test_interests' => 'multi_checkbox', 'test_level' => 'dropdown' ) as $merge_tag => $type ) {
				$fields[$merge_tag] = array( 'label' => $merge_tag, 'is_multiple' => 'multi_checkbox' === $type, 'options' => array( 'Puzzle updates' => 'Puzzles', 'Other updates' => 'Other', '0' => 'Zero', '' => 'Empty' ) );
			}
			$fields['test_checkbox'] = array( 'label' => 'Test checkbox', 'is_multiple' => false, 'options' => array( '0' => 'No', '1' => 'Yes' ) );
			$fields['language'] = array( 'label' => 'Language', 'is_multiple' => false, 'options' => array( 'en' => 'English' ) );
			return $fields;
		} );
		add_filter( 'noptin_custom_fields', static function ( $fields ) {
			$fields[] = array( 'merge_tag' => 'test_checkbox', 'type' => 'checkbox', 'label' => 'Test checkbox' );
			return $fields;
		} );
		$segments = Links::segments();
		$assert( ! isset( $segments['status'] ) && ! isset( $segments['language'] ) && ! isset( $segments['test_checkbox'] ), 'Segments must honor the field manager skip list.' );
		$assert( array() === $segments['tags']['options'] && true === $segments['tags']['multiple'], 'Tags must normalize null choices to an empty array.' );
		$assert( true === $segments['test_lists']['multiple'] && false === $segments['test_level']['multiple'], 'Multiplicity must come from the shared field definitions.' );
		$assert( $segments['test_level']['options'][0] === 'Zero' && ! array_key_exists( '', $segments['test_level']['options'] ), 'Choice values and labels must preserve zero and exclude empty keys.' );
		$settings = noptin()->actions_page->add_options( array() );
		$row = $settings['custom_unsubscribe_links']['customAttributes']['fields']['changes']['customAttributes']['fields']['preference_change']['settings'];
		$assert( true === $row['values.test_lists']['multiple'] && false === $row['values.test_level']['multiple'], 'Settings must allow multiple choices only for multiple-value fields.' );
		$assert( ! in_array( '', wp_list_pluck( $row['values.test_lists']['options'], 'value' ), true ), 'Multiple-choice suggestions must exclude the empty placeholder.' );
		$subscriber = new class extends \Hizzle\Store\Record {
			public $data = array( 'status' => 'subscribed', 'tags' => array( 'keep' ), 'test_lists' => array( 'Puzzle updates', 'Other updates' ), 'test_interests' => array( 'Other updates' ), 'test_level' => 'Other updates' );
			public $saves = 0;
			public $fail = false;
			/** Fixture implementation for the regression. */
			public function __construct() { $this->set_object_read( true ); }
			/** Fixture implementation for the regression. */
			public function exists() { return true; }
			/** Fixture implementation for the regression. */
			public function has_prop( $key ) {
				if ( $key instanceof \Hizzle\Store\Prop ) return $key;
				if ( ! array_key_exists( $key, $this->data ) ) return null;
				$multiple = in_array( $key, array( 'tags', 'test_lists', 'test_interests' ), true );
				return new \Hizzle\Store\Prop( 'fixture', array( 'name' => $key, 'type' => 'VARCHAR', 'is_meta_key' => $multiple, 'is_meta_key_multiple' => $multiple ) );
			}
			/** Fixture implementation for the regression. */
			public function save() {
				++$this->saves;
				if ( $this->fail ) return new WP_Error( 'save_failed', 'Fixture save failure' );
				$this->data = array_replace( $this->data, $this->changes );
				$this->changes = array();
				foreach ( $this->data as $field => $value ) {
					if ( is_array( $value ) ) $this->data[$field] = array_values( $value );
				}
				return 1;
			}
		};
		$test_links[0]['changes'] = array(
			$change( 'tags', 'add', array( 'nopuzzles', 'A tag with spaces' ) ),
			$change( 'test_lists', 'remove', 'Puzzle updates' ),
			$change( 'test_interests', 'add', 'Puzzle updates' ),
			$change( 'test_level', 'remove', 'Puzzle updates' ),
		);
		$assert( true === Links::apply( $subscriber, $key ), 'Valid changes failed.' );
		$assert( $subscriber->get( 'status' ) === 'subscribed' && $subscriber->saves === 1, 'Custom links must preserve status and save once.' );
		$assert( array( 'Other updates' ) === $subscriber->get( 'test_lists' ), 'List removal must preserve other memberships.' );
		$assert( in_array( 'Puzzle updates', $subscriber->get( 'test_interests' ), true ) && in_array( 'A tag with spaces', $subscriber->get( 'tags' ), true ), 'Segment addition or tag spaces lost.' );
		$assert( 'Other updates' === $subscriber->get( 'test_level' ), 'Removing a different scalar choice must preserve the current choice.' );
		$before = $subscriber->data;
		$saves = $subscriber->saves;
		$assert( true === Links::apply( $subscriber, $key ) && $before === $subscriber->data && $saves === $subscriber->saves, 'Repeated clicks must be idempotent without firing another save.' );
		$test_links[0]['changes'] = array( $change( 'test_lists', 'add', array( 'Puzzle updates', '0' ) ), $change( 'test_interests', 'remove', array( 'Puzzle updates', 'Other updates' ) ) );
		$assert( true === Links::apply( $subscriber, $key ) && array( 'Other updates', 'Puzzle updates', '0' ) === $subscriber->get( 'test_lists' ) && array() === $subscriber->get( 'test_interests' ), 'Array choices must add and remove multiple values using native setters.' );
		$test_links[0]['changes'] = array( $change( 'test_lists', 'remove', array( 'Puzzle updates', '0' ) ) );
		$assert( true === Links::apply( $subscriber, $key ) && array( 'Other updates' ) === $subscriber->get( 'test_lists' ), 'Array removal must preserve unrelated memberships.' );
		$test_links[0]['changes'] = array( $change( 'test_level', 'add', '0' ) );
		$assert( true === Links::apply( $subscriber, $key ) && '0' === $subscriber->get( 'test_level' ), 'Zero is a valid option.' );
		$test_links[0]['changes'] = array( $change( 'test_level', 'remove', '0' ) );
		$assert( true === Links::apply( $subscriber, $key ) && '' === $subscriber->get( 'test_level' ), 'Removing a matching scalar choice must clear it.' );
		$test_links[0]['changes'] = array( $change( 'tags', 'remove', array( 'nopuzzles' ) ) );
		$assert( true === Links::apply( $subscriber, $key ) && ! in_array( 'nopuzzles', $subscriber->get( 'tags' ), true ) && in_array( 'keep', $subscriber->get( 'tags' ), true ), 'Tag removal must preserve unrelated tags.' );
		foreach ( array( $change( 'status', 'remove', 'subscribed' ), $change( 'test_lists', 'remove', 'deleted' ), $change( 'test_lists', 'add', array( 'Puzzle updates', 'deleted' ) ), $change( 'test_lists', 'add', array() ), $change( 'test_level', 'add', array( 'Puzzle updates' ) ), $change( 'tags', 'invalid', array( 'keep' ) ), 'malformed' ) as $invalid ) {
			$before = $subscriber->data;
			$saves = $subscriber->saves;
			$test_links[0]['changes'] = array( $change( 'tags', 'remove', array( 'keep' ) ), $invalid );
			$assert( is_wp_error( Links::apply( $subscriber, $key ) ) && $subscriber->data === $before && $subscriber->saves === $saves, 'Invalid changes must not partially update the subscriber.' );
		}
		$test_links[0]['changes'] = array( $change( 'tags', 'add', array( 'nopuzzles' ) ) );
		$subscriber->fail = true;
		$assert( is_wp_error( Links::apply( $subscriber, $key ) ), 'Persistence failure must not report success.' );
		$assert( is_wp_error( Links::apply( null, $key ) ) && is_wp_error( Links::apply( $subscriber, 'deleted' ) ), 'Missing subscriber or deleted link must fail closed.' );

		// Verify actual database persistence for the site's existing multiple-choice fields.
		$real = noptin_get_subscriber( 0 );
		$real->set_email( 'codex-unsubscribe-' . $key . '@example.test' );
		$real->set_status( 'subscribed' );
		$real->set( 'tags', array( 'keep' ) );
		$real_changes = array( $change( 'tags', 'add', array( 'nopuzzles' ) ) );
		$expected = array();
		foreach ( Links::segments() as $segment => $details ) {
			if ( ! in_array( $segment, array( 'lists', 'interests' ), true ) || empty( $details['multiple'] ) || empty( $details['options'] ) ) continue;
			$values = array_map( 'strval', array_keys( $details['options'] ) );
			$real->set( $segment, $values );
			$real_changes[] = $change( $segment, 'remove', array_slice( $values, 0, 2 ) );
			$expected[$segment] = array_slice( $values, 2 );
		}
		$saved = $real->save();
		if ( is_wp_error( $saved ) ) throw new RuntimeException( $saved->get_error_message() );
		try {
			$test_links[0]['changes'] = $real_changes;
			$assert( true === Links::apply( $real, $key ), 'Actual subscriber preference update failed.' );
			$reloaded = noptin_get_subscriber( $real->get_id() );
			$assert( 'subscribed' === $reloaded->get_status() && in_array( 'nopuzzles', $reloaded->get( 'tags' ), true ), 'Actual subscriber status or tags incorrect.' );
			foreach ( $expected as $segment => $values ) $assert( $values === $reloaded->get( $segment ), 'Actual membership removal failed for ' . $segment );
		} finally {
			$real->delete();
		}
		// Unknown recipients are recorded only when an Add operation stores a value.
		$original_options['double_optin'] = true;
		$original_options['disable_double_optin_email'] = false;
		$mail_attempts = 0;
		$capture_mail = static function ( $result ) use ( &$mail_attempts ) { ++$mail_attempts; return false; };
		add_filter( 'pre_wp_mail', $capture_mail, PHP_INT_MAX );
		$pending_events = did_action( 'noptin_subscriber_status_set_to_pending' );
		$new_email = 'codex-unsubscribe-new-' . $key . '@example.test';
		$remove_email = 'codex-unsubscribe-remove-' . $key . '@example.test';
		$normal_email = 'codex-unsubscribe-normal-' . $key . '@example.test';
		$campaign_id = wp_insert_post( array( 'post_type' => 'noptin-campaign', 'post_status' => 'draft', 'post_title' => 'Custom opt-out fixture' ) );
		try {
			$test_links[0]['changes'] = array( $change( 'tags', 'add', array( 'nopuzzles' ) ) );
			Emails::$current_email_recipient = array( 'email' => $new_email, 'unsubscribe_type' => $key );
			Emails::$current_email = new \Hizzle\Noptin\Emails\Email( $campaign_id );
			\Hizzle\Noptin\Subscribers\Actions::unsubscribe_user();
			$new = noptin_get_subscriber( $new_email );
			$assert( $new->exists() && 'pending' === $new->get_status() && ! $new->get_confirmed(), 'An unknown Add recipient must become pending and unconfirmed.' );
			$assert( in_array( 'nopuzzles', $new->get( 'tags' ), true ), 'New recipient must retain the added preference.' );
			$assert( 0 === $mail_attempts && $pending_events === did_action( 'noptin_subscriber_status_set_to_pending' ), 'Preference creation must not send default or trigger custom confirmation emails.' );
			$assert( 0 === (int) get_post_meta( $campaign_id, '_noptin_unsubscribed', true ), 'Partial opt-outs must not increment the global unsubscribe counter.' );
			$new_id = $new->get_id();
			\Hizzle\Noptin\Subscribers\Actions::unsubscribe_user();
			$assert( $new_id === noptin_get_subscriber( $new_email )->get_id() && 0 === $mail_attempts, 'Repeat clicks must reuse the pending record without sending mail.' );
			$test_links[0]['changes'] = array( $change( 'tags', 'remove', array( 'nopuzzles' ) ) );
			$assert( true === Links::apply( null, $key, $remove_email ) && ! noptin_get_subscriber( $remove_email )->exists(), 'Remove-only links must not create empty subscribers.' );
			$test_links[0]['changes'] = array( $change( 'tags', 'add', array( 'nopuzzles' ) ), $change( 'tags', 'remove', array( 'nopuzzles' ) ) );
			$assert( true === Links::apply( null, $key, $remove_email ) && ! noptin_get_subscriber( $remove_email )->exists(), 'An Add followed by its removal must not leave an empty subscriber.' );
			$test_links[0]['changes'] = array( $change( 'tags', 'add', array( 'nopuzzles' ) ), $change( 'missing_field', 'add', 'missing' ) );
			$assert( is_wp_error( Links::apply( null, $key, $remove_email ) ) && ! noptin_get_subscriber( $remove_email )->exists(), 'Invalid changes must not create a partial record.' );
			// A normal new pending subscriber should still get the normal confirmation.
			$normal = noptin_get_subscriber( 0 );
			$normal->set_object_read( false );
			$normal->set_email( $normal_email );
			$normal->set_status( 'pending' );
			$normal->set_confirmed( false );
			$normal->set_object_read( true );
			$normal->save();
			$assert( $mail_attempts > 0, 'Suppressing preference confirmation must not suppress other subscribers\' confirmation emails.' );
		} finally {
			foreach ( array( $new_email, $remove_email, $normal_email ) as $fixture_email ) {
				$record = noptin_get_subscriber( $fixture_email );
				if ( $record->exists() ) $record->delete();
			}
			wp_delete_post( $campaign_id, true );
			remove_filter( 'pre_wp_mail', $capture_mail, PHP_INT_MAX );
		}
	}
}
