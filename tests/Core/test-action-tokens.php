<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** Regression coverage for action tokens. */
class Test_Action_Tokens extends Noptin_Regression_Test_Case {

	public function test_authenticated_and_legacy_action_links() {
		$assert = array( $this, 'assertTrue' );
		$page = new Noptin_Page();
		$request = static function ( $url, $overrides = array() ) {
			parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
			$_GET = array_merge( $query, $overrides );
			$_REQUEST = $_GET;
			return $_GET;
		};
		$data = array( 'email' => 'fixture@example.test', 'subscriber' => '2', 'cid' => 7, 'track' => true );
		$legacy = noptin_encrypt( wp_json_encode( $data ) );
		$tokens = array();
		foreach ( array( 'unsubscribe', 'resubscribe', 'confirm', 'manage_preferences', 'view_in_browser', 'email_click', 'email_open' ) as $action ) {
			$url = get_noptin_action_url( $action, $legacy, 'view_in_browser' === $action );
			$query = $request( $url );
			$tokens[] = $query['nv'];
			$assert( ! isset( $query['noptin_ns'] ) && ! isset( $query['nte'] ) && 0 === strpos( $query['nv'], 'v1:' ), 'New URLs must carry only an authenticated action token.' );
			$expected = array_merge( $data, array( 'noptin_ns' => $action ) );
			if ( 'view_in_browser' === $action ) $expected['nte'] = 1;
			$assert( json_decode( noptin_decrypt( $query['nv'] ), true ) === $expected, 'Payload must bind action, template selection, and complete recipient data.' );
			$assert( $page->use_empty_template() === ( 'view_in_browser' === $action ), 'Template selection must follow the authenticated payload.' );
			$assert( $page->get_request_action() === $action && $page->get_request_recipient() === $data, 'Token-only routing or recipient context failed.' );
			$assert( is_noptin_actions_page() && noptin_is_action_url( $url ), 'New URLs must be detected as action links.' );
			$request( $url, array( 'noptin_ns' => 'other_action', 'noptin_action' => 'other_action', 'na' => 'other_action' ) );
			$assert( $page->get_request_action() === $action, 'Query-string actions must not override bound actions.' );
		}
		$assert( count( array_unique( $tokens ) ) === 7, 'Different actions must have different tokens.' );
		foreach ( array( false, true ) as $empty_template ) {
			$input = noptin_encrypt_action_data( array_merge( $data, array( 'noptin_ns' => 'unsubscribe', 'nte' => 1 ) ) );
			$query = $request( get_noptin_action_url( 'view_in_browser', $input, $empty_template ), array( 'nte' => $empty_template ? '0' : '1' ) );
			$decoded = json_decode( noptin_decrypt( $query['nv'] ), true );
			$assert( $page->use_empty_template() === $empty_template, 'Query flags must not change a new token template choice.' );
			$assert( $empty_template ? 1 === $decoded['nte'] : ! array_key_exists( 'nte', $decoded ), 'Only enabled template selection should be stored as integer 1.' );
			$assert( $data === $page->get_request_recipient(), 'Template metadata must not leak into recipient data.' );
		}
		foreach ( array( null, false, true, 0, '1', 2, array() ) as $invalid_template ) {
			$request( home_url( '/' ), array( 'nv' => noptin_encrypt_action_data( array_merge( $data, array( 'noptin_ns' => 'view_in_browser', 'nte' => $invalid_template ) ) ), 'nte' => '1' ) );
			$assert( ! $page->use_empty_template(), 'Only integer 1 in an authenticated token enables the empty template.' );
		}
		$request( home_url( '/' ), array( 'nv' => $legacy, 'noptin_ns' => 'view_in_browser', 'nte' => '0' ) );
		$assert( $page->use_empty_template(), 'Legacy links must preserve the existing presence-based query flag.' );
		$request( home_url( '/' ), array( 'nv' => $legacy, 'noptin_ns' => 'view_in_browser' ) );
		$assert( ! $page->use_empty_template(), 'Legacy links without a template flag must use the full template.' );
		$first = get_noptin_action_url( 'unsubscribe', $legacy );
		$assert( $first !== get_noptin_action_url( 'unsubscribe', $legacy ), 'Repeated generation must use fresh encryption nonces.' );
		$query = $request( $first );
		$wrong_key = static function () { return 'isolated-wrong-key'; };
		add_filter( 'salt', $wrong_key );
		$assert( '' === noptin_decrypt( $query['nv'] ), 'Tokens must not authenticate with a different site key.' );
		remove_filter( 'salt', $wrong_key );
		$packed = base64_decode( strtr( substr( $query['nv'], 3 ), '-_', '+/' ), true );
		foreach ( array( 0, 12, 28, strlen( $packed ) - 1 ) as $offset ) {
			$changed = $packed;
			$changed[$offset] = chr( ord( $changed[$offset] ) ^ 1 );
			$token = 'v1:' . rtrim( strtr( base64_encode( $changed ), '+/', '-_' ), '=' );
			$request( home_url( '/' ), array( 'nv' => $token, 'noptin_ns' => 'manage_preferences' ) );
			$assert( '' === noptin_decrypt( $token ) && '' === $page->get_request_action() && array() === $page->get_request_recipient(), 'Tampering must fail authentication without a legacy fallback.' );
		}
		foreach ( array( 'v1:', 'v1:invalid!', substr( $query['nv'], 0, -10 ), 'v2:' . substr( $query['nv'], 3 ) ) as $token ) {
			$request( home_url( '/' ), array( 'nv' => $token, 'noptin_ns' => 'manage_preferences' ) );
			$assert( '' === $page->get_request_action() && array() === $page->get_request_recipient(), 'Malformed or unknown-version tokens must fail closed.' );
		}
		foreach ( array( null, '', array(), 'bad action', false ) as $action ) {
			$request( home_url( '/' ), array( 'nv' => noptin_encrypt_action_data( array_merge( $data, array( 'noptin_ns' => $action ) ) ), 'noptin_ns' => 'unsubscribe' ) );
			$assert( '' === $page->get_request_action() && array() === $page->get_request_recipient(), 'Invalid embedded actions must never fall back to the URL.' );
		}
		$request( home_url( '/' ), array( 'nv' => noptin_encrypt_action_data( $data ), 'noptin_ns' => 'unsubscribe' ) );
		$assert( '' === $page->get_request_action() && array() === $page->get_request_recipient(), 'New tokens without an action must fail closed.' );
		foreach ( array( 'unsubscribe', 'view_in_browser', 'manage_preferences', 'confirm' ) as $action ) {
			$request( home_url( '/' ), array( 'nv' => $legacy, 'noptin_ns' => $action ) );
			$assert( $action === $page->get_request_action() && $data === $page->get_request_recipient(), 'Legacy encrypted links must retain query-string routing.' );
		}
		$request( home_url( '/' ), array( 'nv' => $legacy, 'na' => 'unsubscribe' ) );
		$assert( 'unsubscribe' === $page->get_request_action(), 'Legacy na routing failed.' );
		$request( home_url( '/' ), array( 'nv' => array( 'malformed' ), 'noptin_ns' => array() ) );
		$assert( '' === $page->get_request_value() && '' === $page->get_request_action(), 'Array query parameters must not cause PHP errors.' );
		$generator = new Hizzle\Noptin\Emails\Generate\Generator( array( 'recipient' => $data ) );
		foreach ( array( $first, add_query_arg( array( 'noptin_ns' => 'unsubscribe', 'nv' => $legacy ), home_url( '/' ) ) ) as $url ) {
			$matches = array( '', '', $url, '' );
			$assert( str_contains( $generator->make_links_trackable_callback( $matches ), $url ), 'Action links must not be wrapped in click tracking.' );
			$matches[0] = '<a href="' . $url . '">Action</a>';
			$assert( $matches[0] === $generator->add_utm_parameters_callback( $matches ), 'Action links must not gain UTM parameters.' );
		}
		$tracked = $generator->make_links_trackable_callback( array( '', '', 'https://example.test/story', '' ) );
		preg_match( "/href='([^']+)'/", $tracked, $match );
		$request( $match[1] );
		$assert( 'email_click' === $page->get_request_action() && 'https://example.test/story' === $page->get_request_recipient()['to'], 'Ordinary links must still receive action-bound click tracking.' );
		$subscriber = noptin_get_subscriber();
		$subscriber->set_email( $data['email'] );
		$setting = get_noptin_action_url( 'manage_preferences' );
		$override = static function () use ( &$setting ) { return $setting; };
		add_filter( 'get_noptin_option_manage_preferences_url', $override );
		foreach ( array( $setting, add_query_arg( 'noptin_ns', 'manage_preferences', home_url( '/' ) ) ) as $setting ) {
			$request( $subscriber->get_manage_preferences_url() );
			$assert( 'manage_preferences' === $page->get_request_action() && $data['email'] === $page->get_request_recipient()['email'], 'Saved default preferences URLs must generate personalized new tokens.' );
		}
		$setting = 'https://example.test/preferences/?noptin_ns=manage_preferences';
		$assert( str_starts_with( $subscriber->get_manage_preferences_url(), $setting . '&noptin_key=' ), 'Custom preferences URLs must retain their own destination.' );
		remove_filter( 'get_noptin_option_manage_preferences_url', $override );
		$temporary = noptin_get_subscriber();
		add_filter( 'noptin_disable_cookies', '__return_true' );
		try {
			$temporary->set_email( 'codex-action-token-' . wp_generate_uuid4() . '@example.test' );
			$temporary->set_confirm_key( md5( wp_generate_uuid4() ) );
			$temporary->set_status( 'pending' );
			$temporary->set_confirmed( false );
			$temporary->save();
			$assert( $temporary->exists(), 'Temporary subscriber creation failed.' );
			$request( home_url( '/' ), array( 'noptin_ns' => 'confirm', 'nv' => $temporary->get_confirm_key() ) );
			$assert( 'confirm' === $page->get_request_action() && $temporary->get_id() === (int) $page->get_request_recipient()['subscriber'], 'Raw legacy confirmation keys must still identify their subscriber.' );
			$request( $temporary->get_confirm_subscription_url() );
			Hizzle\Noptin\Subscribers\Actions::handle_confirm( $page );
			$temporary = noptin_get_subscriber( $temporary->get_id() );
			$assert( $temporary->get_confirmed() && $temporary->is_active(), 'Authenticated confirmation links must confirm the intended subscriber.' );
			$temporary->set_status( 'unsubscribed' );
			$temporary->save();
			$request( $temporary->get_resubscribe_url() );
			Hizzle\Noptin\Emails\Main::init_current_email_recipient( $page->get_request_recipient() );
			Hizzle\Noptin\Subscribers\Actions::resubscribe_user();
			$assert( noptin_get_subscriber( $temporary->get_id() )->is_active(), 'Authenticated resubscribe links must restore the intended subscriber.' );
		} finally {
			if ( $temporary->exists() ) $temporary->delete();
			remove_filter( 'noptin_disable_cookies', '__return_true' );
		}
		$request( home_url( '/' ) );
		$GLOBALS['wp']->query_vars['noptin_newsletter'] = 'unsubscribe';
		$page->listen();
		$assert( false !== has_action( 'template_redirect', array( $page, 'load_actions_page' ) ), 'Legacy pretty links must still route during parse_request, before WP_Query is populated.' );
		unset( $GLOBALS['wp']->query_vars['noptin_newsletter'] );
	}
}
