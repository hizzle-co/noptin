<?php
/** Regression coverage for form management authorization. */
require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** Checks the real post and REST capability boundaries. */
class Test_Form_Permissions extends Noptin_Regression_Test_Case {

	/** Restores REST metadata registrations cleared by WordPress between tests. */
	public function set_up() {
		parent::set_up();
		Hizzle\Noptin\Forms\Main::register_post_meta();
	}

	/** Dispatches a JSON REST request as the current test user.
	 *
	 * @param string $method HTTP method.
	 * @param string $path Route suffix.
	 * @param array  $body Request body.
	 * @return WP_REST_Response
	 */
	private function request( $method, $path = '', $body = array() ) {
		$request = new WP_REST_Request( $method, '/wp/v2/noptin-form' . $path );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );
		return rest_do_request( $request );
	}

	/** Forms-only users can reach the parent menu and Forms, while publishing roles cannot. */
	public function test_forms_menu_access() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$keys = array( 'menu', 'submenu', 'admin_page_hooks', '_registered_pages', '_parent_pages', '_wp_submenu_nopriv' );
		$saved = array();
		foreach ( $keys as $key ) {
			$saved[ $key ] = array( array_key_exists( $key, $GLOBALS ), $GLOBALS[ $key ] ?? null );
		}
		try {
			foreach ( array( 'author', 'editor', 'form_manager', 'legacy_manager', 'administrator', 'enabled_editor' ) as $kind ) {
				add_filter( 'get_noptin_option_allow_editors', 'enabled_editor' === $kind ? '__return_true' : '__return_false' );
				$role = in_array( $kind, array( 'author', 'editor', 'administrator' ), true ) ? $kind : ( 'enabled_editor' === $kind ? 'editor' : '' );
				$user = self::factory()->user->create( array( 'role' => $role ) );
				if ( 'form_manager' === $kind || 'legacy_manager' === $kind ) {
					get_user_by( 'id', $user )->add_cap( 'form_manager' === $kind ? 'manage_noptin_forms' : 'manage_noptin' );
				}
				wp_set_current_user( $user );
				$allowed = ! in_array( $kind, array( 'author', 'editor' ), true );
				$this->assertSame( $allowed, current_user_can( get_noptin_menu_capability() ), $kind );
				if ( 'form_manager' === $kind ) {
					$this->assertFalse( current_user_can( get_noptin_capability() ) );
					$this->assertSame( 'manage_noptin_forms', get_noptin_menu_capability() );
				}
				$GLOBALS['submenu']['noptin'] = array();
				Hizzle\Noptin\Forms\Admin\Main::forms_menu();
				$forms = array_values( array_filter( $GLOBALS['submenu']['noptin'], static function ( $item ) {
					return 'edit.php?post_type=noptin-form' === $item[2];
				} ) );
				$this->assertCount( $allowed ? 1 : 0, $forms, $kind );
				if ( $allowed ) {
					$this->assertSame( 'manage_noptin_forms', $forms[0][1] );
				}
			}
		} finally {
			foreach ( $saved as $key => list( $exists, $value ) ) {
				if ( $exists ) {
					$GLOBALS[ $key ] = $value;
				} else {
					unset( $GLOBALS[ $key ] );
				}
			}
		}
	}

	/** Ordinary publishing roles cannot manage forms, including their own old forms. */
	public function test_post_roles_cannot_manage_forms() {
		add_filter( 'get_noptin_option_allow_editors', '__return_false' );
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		foreach ( array( 'subscriber', 'contributor', 'author', 'editor' ) as $role ) {
			$user = self::factory()->user->create( array( 'role' => $role ) );
			wp_set_current_user( $user );
			$this->assertFalse( current_user_can( get_noptin_capability() ) );
			$response = $this->request( 'POST', '', array( 'status' => 'publish', 'meta' => array( '_noptin_optin_type' => 'popup', '_noptin_state' => array( 'showEverywhere' => true, 'is_unsubscribe' => true ) ) ) );
			$this->assertSame( 403, $response->get_status(), $role );
			foreach ( array( $user, $admin ) as $owner ) {
				foreach ( array( 'draft', 'publish', 'private' ) as $status ) {
					$id = self::factory()->post->create( array( 'post_type' => 'noptin-form', 'post_status' => $status, 'post_author' => $owner ) );
					$this->assertFalse( current_user_can( 'edit_post', $id ), $role );
					$this->assertFalse( current_user_can( 'delete_post', $id ), $role );
					foreach ( array( '_noptin_optin_type', '_noptin_state', '_noptin_subscribers_count', '_noptin_form_views' ) as $key ) {
						$this->assertFalse( current_user_can( 'edit_post_meta', $id, $key ), $role . ' ' . $key );
					}
					$response = $this->request( 'POST', '/' . $id, array( 'title' => 'Forbidden', 'meta' => array( '_noptin_state' => array( 'is_unsubscribe' => true ) ) ) );
					$this->assertSame( 403, $response->get_status(), $role );
					$this->assertSame( 403, $this->request( 'DELETE', '/' . $id, array( 'force' => true ) )->get_status(), $role );
					$this->assertSame( $status, get_post_status( $id ) );
					$this->assertSame( array(), (array) get_post_meta( $id, '_noptin_state', true ) );
				}
			}
		}
	}

	/** Administrators, legacy managers, dedicated managers, and opted-in editors retain access. */
	public function test_authorized_form_management() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		foreach ( array( 'administrator', 'legacy_manager', 'form_manager', 'editor' ) as $kind ) {
			add_filter( 'get_noptin_option_allow_editors', 'editor' === $kind ? '__return_true' : '__return_false' );
			$user = self::factory()->user->create( array( 'role' => in_array( $kind, array( 'administrator', 'editor' ), true ) ? $kind : '' ) );
			if ( 'legacy_manager' === $kind || 'form_manager' === $kind ) {
				get_user_by( 'id', $user )->add_cap( 'legacy_manager' === $kind ? 'manage_noptin' : 'manage_noptin_forms' );
			}
			wp_set_current_user( $user );
			$response = $this->request( 'POST', '', array( 'title' => 'Authorized form', 'status' => 'publish', 'meta' => array( '_noptin_optin_type' => 'popup', '_noptin_state' => array( 'showEverywhere' => true, 'is_unsubscribe' => true ) ) ) );
			$this->assertSame( 201, $response->get_status(), $kind . ': ' . wp_json_encode( $response->get_data() ) );
			$id = $response->get_data()['id'];
			$this->assertSame( 'publish', get_post_status( $id ) );
			$this->assertSame( 'popup', get_post_meta( $id, '_noptin_optin_type', true ) );
			$this->assertTrue( ( (array) get_post_meta( $id, '_noptin_state', true ) )['is_unsubscribe'] );
			$other = self::factory()->post->create( array( 'post_type' => 'noptin-form', 'post_status' => 'publish', 'post_author' => $admin ) );
			$this->assertTrue( current_user_can( 'edit_post_meta', $other, '_noptin_state' ) );
			$response = $this->request( 'POST', '/' . $other, array( 'title' => 'Authorized update', 'meta' => array( '_noptin_state' => array( 'title' => 'Updated copy' ) ) ) );
			$this->assertSame( 200, $response->get_status(), $kind );
			$this->assertSame( 'Updated copy', ( (array) get_post_meta( $other, '_noptin_state', true ) )['title'] );
			$this->assertSame( 200, $this->request( 'DELETE', '/' . $other, array( 'force' => true ) )->get_status(), $kind );
			$this->assertSame( 200, $this->request( 'DELETE', '/' . $id, array( 'force' => true ) )->get_status(), $kind );
		}
	}
}
