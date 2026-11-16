<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** First-party regression coverage migrated from settings-api.php. */
class Test_Settings_API extends Noptin_Regression_Test_Case {
	/** Verify settings api using the isolated WordPress test database. */
	public function test_settings_api() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		remove_all_actions( 'noptin_newsletter_campaign_published' );
		remove_all_actions( 'noptin_send_newsletter' );
		add_filter( 'noptin_saved_task_background_run', '__return_false' );
		remove_action( 'shutdown', array( $GLOBALS['noptin_tasks'], 'run_overdue_tasks_on_shutdown' ), 1 );
		$registry = new ReflectionProperty( \Hizzle\WordPress\SettingsAPI::class, 'options' );
		$registry->setAccessible( true );
		$original_registry = $registry->getValue();
		$option = 'hizzlewp_settings_codex_test_' . wp_generate_password( 12, false, false );
		$check_settings_api = array( $this, 'assertTrue' );
		$settings_api_request = static function ( $method, $suffix = '', $body = null, $selector = null ) use ( $option ) {
			$request = new WP_REST_Request( $method, '/hizzlewp/v1/settings/' . $option . $suffix );
			if ( null !== $selector ) $request->set_query_params( array( 'hizzlewp_field' => $selector ) );
			if ( null !== $body ) { $request->set_header( 'content-type', 'application/json' ); $request->set_body( wp_json_encode( $body ) ); }
			return rest_do_request( $request );
		};

		try {
			$sanitize = static function ( $values ) {
				if ( isset( $values['client'] ) ) $values['client'] = trim( $values['client'] );
				return $values;
			};
			register_setting( 'codex_settings_test', $option, array( 'sanitize_callback' => $sanitize ) );
			update_option( $option, array( 'client' => 'old', 'unrelated' => 'keep', 'nested' => array( 'key' => 'old', 'sibling' => 'keep' ), 'rows' => array( 'a', 'b' ) ) );
			\Hizzle\WordPress\ScriptManager::register_settings( $option, array( 'fields' => static function () use ( $option ) {
				return array( 'general' => array( 'label' => 'General', 'sub_sections' => array( 'main' => array( 'label' => 'Main', 'settings' => array(
					'group' => array( 'el' => 'settings_group', 'settings' => array(
						'client' => array( 'el' => 'input' ),
						'nested.key' => array( 'el' => 'input' ),
						'rows' => array( 'el' => 'repeater', 'children' => array( 'label' => array( 'el' => 'input' ) ) ),
						'connect' => array( 'el' => 'button', 'buttonProps' => array( 'href' => 'https://example.test/' . ( (array) get_option( $option ) )['client'] ) ),
						'locked' => array( 'el' => 'input', 'disabled' => true ),
					) ),
					'holder' => array( 'el' => 'horizontal', 'children' => array( 'child_1' => array( 'el' => 'input' ) ) ),
				) ) ) ) );
			} ) );
			$response = $settings_api_request( 'GET' );
			$check_settings_api( 200 === $response->get_status() && 'keep' === $response->get_data()->unrelated, 'Read registered option.' );
			$response = $settings_api_request( 'GET', '/fields', null, 'general.main.group' );
			$check_settings_api( isset( $response->get_data()->group['settings']['client'] ), 'Resolve abbreviated group selector.' );
			$response = $settings_api_request( 'GET', '/fields', null, 'general.sub_sections.main.settings.holder.children.child_1' );
			$check_settings_api( isset( $response->get_data()->child_1 ), 'Resolve explicit nested children selector.' );
			$response = $settings_api_request( 'GET', '', null, 'general.main.group.nested.key' );
			$check_settings_api( array( 'key' => 'old' ) === $response->get_data()->nested, 'Read only selected dotted value.' );
			$response = $settings_api_request( 'POST', '', array( 'client' => ' new ' ), 'general.main.group' );
			$check_settings_api( 200 === $response->get_status() && 'new' === get_option( $option )['client'] && 'keep' === get_option( $option )['unrelated'], 'Sanitize and merge selected fields.' );
			$response = $settings_api_request( 'GET', '/fields', null, 'general.main.group' );
			$check_settings_api( 'https://example.test/new' === $response->get_data()->group['settings']['connect']['buttonProps']['href'], 'Rebuild fields using newly saved option.' );
			$response = $settings_api_request( 'PUT', '', array( 'nested' => array( 'key' => 'new' ) ), 'general.main.group.nested.key' );
			$check_settings_api( 200 === $response->get_status() && array( 'key' => 'new', 'sibling' => 'keep' ) === get_option( $option )['nested'], 'Merge dotted values without changing siblings.' );
			$response = $settings_api_request( 'POST', '', array( 'rows' => array() ), 'general.main.group' );
			$check_settings_api( 200 === $response->get_status() && array() === get_option( $option )['rows'], 'Replace lists, including clearing them.' );
			$response = $settings_api_request( 'POST', '', array( 'client' => 'new' ), 'general.main.group' );
			$check_settings_api( 200 === $response->get_status(), 'Identical sanitized saves are successful no-ops.' );
			remove_filter( "sanitize_option_$option", $sanitize );
			update_option( $option, (object) array_merge( get_option( $option ), array( 'nested' => (object) array( 'key' => 'new', 'sibling' => 'keep' ) ) ) );
			add_filter( "sanitize_option_$option", $sanitize );
			$response = $settings_api_request( 'PUT', '', array( 'nested' => array( 'key' => 'object-update' ) ), 'general.main.group.nested.key' );
			$check_settings_api( 200 === $response->get_status() && 'keep' === get_option( $option )['nested']['sibling'] && 'keep' === get_option( $option )['unrelated'], 'Preserve siblings in object-valued options and nested maps.' );
			foreach ( array( array( 'unrelated' => 'change' ), array( 'nested' => array( 'sibling' => 'change' ) ), array( 'locked' => 'change' ) ) as $patch ) {
				$response = $settings_api_request( 'POST', '', $patch, 'general.main.group' );
				$check_settings_api( 400 === $response->get_status(), 'Reject updates outside writable selected fields.' );
			}
			$response = $settings_api_request( 'GET', '/fields', null, 'general.main.missing' );
			$check_settings_api( 404 === $response->get_status(), 'Unknown field returns 404.' );
			$response = $settings_api_request( 'GET', '/fields', null, 'general.main.group.rows.children.label' );
			$check_settings_api( 200 === $response->get_status() && isset( $response->get_data()->label ), 'Inspect repeater child definitions.' );
			$response = $settings_api_request( 'POST', '', array( 'label' => 'wrong-root-value' ), 'general.main.group.rows.children.label' );
			$check_settings_api( 400 === $response->get_status() && ! isset( get_option( $option )['label'] ), 'Reject ambiguous repeater child updates instead of creating unrelated root values.' );
			$response = $settings_api_request( 'PUT', '', array( 'label' => 'wrong-root-value' ), 'general.main.group.rows.label' );
			$check_settings_api( 400 === $response->get_status(), 'Abbreviated selectors cannot bypass repeater row ambiguity checks.' );
			$response = $settings_api_request( 'GET', '/fields', null, 'general..main' );
			$check_settings_api( 400 === $response->get_status(), 'Invalid field selector returns 400.' );
			$response = rest_do_request( new WP_REST_Request( 'GET', '/hizzlewp/v1/settings/blogname' ) );
			$check_settings_api( 404 === $response->get_status(), 'Unregistered WordPress options are inaccessible.' );
			$response = $settings_api_request( 'POST', '', array( 'a', 'b' ) );
			$check_settings_api( 400 === $response->get_status(), 'Reject non-object update bodies.' );
			$response = rest_do_request( new WP_REST_Request( 'GET', '/hizzlewp/v1/settings/noptin_options' ) );
			$check_settings_api( 200 === $response->get_status() && is_array( $response->get_data()->custom_fields ), 'Read Noptin values through its registered editor callback.' );
			$request = new WP_REST_Request( 'GET', '/hizzlewp/v1/settings/noptin_options/fields' );
			$request->set_query_params( array( 'hizzlewp_field' => 'general.main' ) );
			$response = rest_do_request( $request );
			$check_settings_api( 200 === $response->get_status() && count( (array) $response->get_data() ) > 0, 'Read actual Noptin fields through the registered callback.' );
			$request = new WP_REST_Request( 'GET', '/hizzlewp/v1/settings/noptin_options/fields' );
			$response = rest_do_request( $request );
			$fields = (array) $response->get_data();
			$check_settings_api( 200 === $response->get_status() && isset( $fields['emails']['sub_sections']['main']['settings']['general_email_info']['settings']['from_email'] ), 'Include the Emails tab and sender settings in REST requests.' );
			$check_settings_api( isset( $fields['emails']['sub_sections']['double_opt_in']['settings'] ), 'Include Double Opt-In settings in REST requests.' );
			wp_set_current_user( 0 );
			$response = $settings_api_request( 'POST', '', array( 'client' => 'unauthorized' ) );
			$check_settings_api( in_array( $response->get_status(), array( 401, 403 ), true ) && 'new' === get_option( $option )['client'], 'Unauthenticated writes are rejected without changes.' );
			$response = $settings_api_request( 'GET', '/fields' );
			$check_settings_api( in_array( $response->get_status(), array( 401, 403 ), true ), 'Unauthenticated field reads are rejected.' );
		} finally {
			$registry->setValue( null, $original_registry );
			delete_option( $option );
			unregister_setting( 'codex_settings_test', $option );
		}
	}
}
