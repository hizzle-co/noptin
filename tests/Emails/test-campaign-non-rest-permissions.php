<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** Regression coverage for campaign writes outside the REST controller. */
class Test_Campaign_Non_REST_Permissions extends Noptin_Regression_Test_Case {

	/** XML-RPC must reject an Author before inserting a campaign. */
	public function test_author_xmlrpc_creation_is_denied() {
		require_once ABSPATH . WPINC . '/class-IXR.php';
		require_once ABSPATH . WPINC . '/class-wp-xmlrpc-server.php';
		add_filter( 'get_noptin_option_allow_editors', '__return_false' );
		$password = wp_generate_password();
		$id = self::factory()->user->create( array( 'role' => 'author', 'user_pass' => $password ) );
		$server = new wp_xmlrpc_server();
		$result = $server->wp_newPost( array( 1, get_userdata( $id )->user_login, $password, array(
			'post_type' => 'noptin-campaign',
			'post_status' => 'publish',
			'post_title' => 'Forbidden XML-RPC newsletter',
			'post_content' => 'Forbidden newsletter body',
			'custom_fields' => array( array( 'key' => 'campaign_type', 'value' => 'newsletter' ) ),
		) ) );
		$this->assertInstanceOf( IXR_Error::class, $result );
		$this->assertSame( 401, $result->code );
	}

	/** Authors must not acquire campaign permissions from standard post caps. */
	public function test_author_cannot_create_or_edit_campaigns() {
		add_filter( 'get_noptin_option_allow_editors', '__return_false' );
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$author = self::factory()->user->create( array( 'role' => 'author' ) );
		wp_set_current_user( $admin );
		$id = wp_insert_post( array( 'post_type' => 'noptin-campaign', 'post_status' => 'draft', 'post_author' => $author, 'post_title' => 'Campaign permission fixture' ) );
		wp_set_current_user( $author );
		$this->assertTrue( current_user_can( 'publish_posts' ) );
		$this->assertFalse( current_user_can( get_post_type_object( 'noptin-campaign' )->cap->create_posts ) );
		$this->assertFalse( Hizzle\Noptin\Emails\Main::current_user_can_create_new_campaign() );
		$this->assertFalse( current_user_can( 'edit_post', $id ) );
		$this->assertFalse( current_user_can( 'delete_post', $id ) );
		$this->assertFalse( user_can( $author, 'edit_post', $id ) );
		wp_set_current_user( $admin );
		$this->assertTrue( current_user_can( get_post_type_object( 'noptin-campaign' )->cap->create_posts ) );
		$this->assertTrue( current_user_can( 'edit_post', $id ) );
	}

	/** Direct inserts and updates must not dispatch unauthorized email hooks. */
	public function test_direct_publication_checks_campaign_type_before_dispatch() {
		add_filter( 'get_noptin_option_allow_editors', '__return_false' );
		remove_all_actions( 'noptin_newsletter_campaign_published' );
		remove_all_actions( 'noptin_automation_campaign_published' );
		$published = array();
		$saved = array();
		add_action( 'noptin_newsletter_campaign_published', static function ( $email ) use ( &$published ) { $published[] = $email->id; } );
		add_action( 'noptin_automation_campaign_published', static function ( $email ) use ( &$published ) { $published[] = $email->id; } );
		add_action( 'noptin_newsletter_campaign_saved', static function ( $email ) use ( &$saved ) { $saved[] = $email->id; } );
		$author = self::factory()->user->create( array( 'role' => 'author' ) );
		wp_set_current_user( $author );
		foreach ( array( 'publish', 'draft', 'future' ) as $status ) {
			$id = wp_insert_post( array(
				'post_type' => 'noptin-campaign',
				'post_status' => $status,
				'post_author' => $author,
				'post_title' => 'Unauthorized legacy newsletter',
				'post_content' => 'Unauthorized body',
				'meta_input' => array( 'campaign_type' => 'newsletter' ),
				'post_date' => 'future' === $status ? gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ) : '',
			) );
			$this->assertGreaterThan( 0, $id );
			$this->assertSame( 'draft', get_post_status( $id ) );
			if ( 'draft' === $status ) {
				wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
			} elseif ( 'future' === $status ) {
				wp_set_current_user( 0 );
				wp_publish_post( $id );
				wp_set_current_user( $author );
			}
		}
		$this->assertSame( array(), $published );
		$this->assertSame( array(), $saved );

		$user = get_userdata( $author );
		$user->add_cap( 'manage_noptin_campaign_type_newsletter' );
		wp_set_current_user( 0 );
		wp_set_current_user( $author );
		$this->assertTrue( current_user_can( get_post_type_object( 'noptin-campaign' )->cap->create_posts ) );
		foreach ( array( 'newsletter', 'automation' ) as $type ) {
			$id = wp_insert_post( array(
				'post_type' => 'noptin-campaign',
				'post_status' => 'publish',
				'post_title' => 'Type-specific permission fixture',
				'meta_input' => array( 'campaign_type' => $type ),
			) );
			$this->assertSame( 'newsletter' === $type, in_array( $id, $published, true ) );
		}
		$this->assertCount( 1, $published );

		$id = wp_insert_post( array(
			'post_type' => 'noptin-campaign',
			'post_status' => 'future',
			'post_title' => 'Authorized scheduled campaign',
			'meta_input' => array( 'campaign_type' => 'newsletter' ),
			'post_author' => $author,
			'post_date' => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
		) );
		wp_set_current_user( 0 );
		wp_publish_post( $id );
		$this->assertContains( $id, $published, 'Authorized scheduled campaigns must still publish without a logged-in user.' );
	}
}
