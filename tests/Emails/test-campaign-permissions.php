<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

use Hizzle\Noptin\Emails\Email;
use Hizzle\Noptin\Emails\Admin\Main as Campaign_Admin;

class Noptin_Campaign_Test_Redirect extends RuntimeException {}

/** Regression coverage for campaign permissions. */
class Test_Campaign_Permissions extends Noptin_Regression_Test_Case {

	public function test_campaign_type_authorization_and_draft_duplicates() {
		$assert = array( $this, 'assertTrue' );
		$type_registry = new ReflectionProperty( Hizzle\Noptin\Emails\Main::class, 'types' );
		$type_registry->setAccessible( true );
		$original_types = $type_registry->getValue();
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$ids      = array();
		$user_id  = 0;

		$mail     = 0;
		// Track inserts even if a failed assertion discovers an unexpected successful write.
		add_action( 'wp_after_insert_post', static function ( $id, $post, $update ) use ( &$ids ) {
			if ( ! $update && 'noptin-campaign' === $post->post_type ) {
				$ids[] = $id;
			}
		}, 10, 3 );
		add_filter( 'get_noptin_option_allow_editors', '__return_false' );
		add_filter( 'pre_wp_mail', static function () use ( &$mail ) { ++$mail; return false; }, PHP_INT_MAX );
		remove_all_actions( 'noptin_newsletter_campaign_published' );
		remove_all_actions( 'noptin_send_newsletter' );

		$request = static function ( $method, $path, $params = array() ) {
			$request = new WP_REST_Request( $method, '/wp/v2/noptin-campaign' . $path );
			$request->set_param( 'context', 'edit' );
			$request->set_body_params( $params );
			return rest_do_request( $request );
		};
		$create = static function ( $type = 'newsletter', $parent = 0 ) use ( &$ids, $admin_id ) {
			$id = wp_insert_post(
				array(
					'post_type'   => 'noptin-campaign',
					'post_status' => 'draft',
					'post_title'  => 'Campaign permission fixture',
					'post_author' => $admin_id,
					'post_parent' => $parent,
					'meta_input'  => array(
						'campaign_type' => $type,
						'campaign_data' => array( 'subject' => 'Permission fixture', 'email_sender' => 'manual_recipients' ),
					),
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				throw new RuntimeException( $id->get_error_message() );
			}
			$ids[] = $id;
			return $id;
		};
		$redirect = static function () { throw new Noptin_Campaign_Test_Redirect(); };

		try {
			wp_set_current_user( $admin_id );
			$user_id = wp_insert_user( array( 'user_login' => 'noptin-permissions-' . wp_generate_password( 10, false ), 'user_pass' => wp_generate_password(), 'role' => '' ) );
			if ( is_wp_error( $user_id ) ) {
				throw new RuntimeException( $user_id->get_error_message() );
			}
			$user = new WP_User( $user_id );
			foreach ( array( 'read', 'edit_posts', 'edit_others_posts', 'edit_published_posts', 'publish_posts', 'delete_posts', 'delete_others_posts', 'delete_published_posts', 'manage_noptin_campaign_type_newsletter' ) as $cap ) {
				$user->add_cap( $cap );
			}
			$newsletter = $create();
			$automation = $create( 'automation' );
			$legacy     = $create();
			delete_post_meta( $legacy, 'campaign_type' );
			wp_set_current_user( $user_id );
			$assert( current_user_can( 'edit_post', $automation ) && current_user_can( 'delete_post', $automation ), 'Fixture must have underlying WordPress object permissions.' );
			$assert( ! current_user_can_manage_noptin_campaign_type( 'automation' ), 'Fixture must lack automation access.' );

			$before = get_posts( array( 'post_type' => 'noptin-campaign', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );
			foreach ( array( 'draft', 'publish' ) as $status ) {
				$response = $request( 'POST', '', array( 'campaign_type' => 'newsletter', 'status' => $status, 'meta' => array( 'campaign_type' => 'automation', 'automation_type' => 'anything' ) ) );
				$assert( 403 === $response->get_status(), 'Conflicting create type must be denied before insertion.' );
			}
			$after = get_posts( array( 'post_type' => 'noptin-campaign', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );
			$assert( $before === $after, 'Denied creates must not leave campaigns behind.' );
			foreach ( array(
				array( 'campaign_type' => 'newsletter', 'meta' => array( 'campaign_type' => 'automation' ) ),
				array( 'meta' => array( 'campaign_type' => 'automation' ) ),
				array( 'meta' => array( 'automation_type' => 'anything' ) ),
			) as $params ) {
				$assert( 403 === $request( 'POST', '/' . $newsletter, $params )->get_status(), 'Unauthorized retyping or subtype write must be denied.' );
			}
			foreach ( array(
				array( 'campaign_type' => 'newsletter', 'title' => 'Forbidden' ),
				array( 'meta' => array( 'campaign_type' => 'newsletter' ) ),
				array( 'meta' => array( 'campaign_type' => null ) ),
				array( 'campaign_type' => 'newsletter', 'status' => 'publish' ),
			) as $params ) {
				$assert( 403 === $request( 'POST', '/' . $automation, $params )->get_status(), 'Stored automation type must prevent spoofed updates and retyping.' );
			}
			$assert( 403 === $request( 'POST', '/' . $automation . '/send-test', array( 'campaign_type' => 'newsletter', 'email' => 'nobody@example.test' ) )->get_status(), 'Spoofed send-test must be denied.' );
			$assert( 403 === $request( 'POST', '/' . $automation . '/autosaves', array( 'title' => 'Forbidden autosave' ) )->get_status(), 'Autosaves must enforce stored-type access.' );
			$assert( 403 === $request( 'GET', '/' . $automation )->get_status(), 'Restricted reads must remain denied.' );
			$assert( 403 === $request( 'DELETE', '/' . $automation, array( 'force' => true ) )->get_status(), 'Restricted deletes must remain denied.' );
			$email = new Email( $automation );
			$assert( ! $email->current_user_can_edit() && ! $email->current_user_can_delete() && ! $email->current_user_can_publish(), 'Shared email helpers must enforce stored types.' );
			$email->type = 'newsletter';
			$assert( ! $email->current_user_can_edit(), 'Mutable email properties must not override the saved type.' );

			// Reuse one nonce across campaign IDs and commands, as a legitimate list user can.
			$nonce = wp_create_nonce( 'noptin_email_action' );
			add_filter( 'wp_redirect', $redirect );
			foreach ( array( 'force_send', 'resend', 'pause', 'duplicate', 'delete', 'trash', 'restore' ) as $action ) {
				$_REQUEST = array( 'noptin_campaign' => $automation, 'noptin_email_action' => $action . '_campaign', 'noptin_email_action_nonce' => $nonce );
				try {
					Campaign_Admin::maybe_do_action();
					$assert( false, 'Legacy action must reject the unauthorized campaign.' );
				} catch ( Noptin_Campaign_Test_Redirect $e ) {
					$assert( 'draft' === get_post_status( $automation ) && 'automation' === get_post_meta( $automation, 'campaign_type', true ) && '' === get_post_meta( $automation, 'paused', true ), 'Legacy rejection must leave the campaign unchanged.' );
				}
			}
			$_REQUEST = array();
			remove_filter( 'wp_redirect', $redirect );
			$assert( 0 === $mail, 'Denied requests must not attempt mail delivery.' );
			$assert( 200 === $request( 'POST', '/' . $legacy, array( 'title' => 'Legacy newsletter updated' ) )->get_status(), 'Legacy newsletters without type metadata must remain editable.' );
			$assert( 200 === $request( 'POST', '/' . $newsletter, array( 'status' => 'publish' ) )->get_status(), 'Authorized newsletter publishing must succeed.' );
			// Broad Noptin access remains compatible without changing WordPress capabilities.
			foreach ( array( 'manage_noptin', 'manage_noptin_campaigns' ) as $cap ) {
				$user->add_cap( $cap );
				wp_set_current_user( 0 );
				wp_set_current_user( $user_id );
				$assert( 200 === $request( 'POST', '/' . $automation, array( 'title' => 'Broad manager update' ) )->get_status(), 'Legacy and campaign managers must retain automation access.' );
				$user->remove_cap( $cap );
			}
			wp_set_current_user( 0 );
			wp_set_current_user( $user_id );
			remove_filter( 'get_noptin_option_allow_editors', '__return_false' );
			add_filter( 'get_noptin_option_allow_editors', '__return_true' );
			$assert( 200 === $request( 'POST', '/' . $automation, array( 'title' => 'Opted-in editor update' ) )->get_status(), 'Opted-in editors must retain access.' );
			remove_filter( 'get_noptin_option_allow_editors', '__return_true' );
			add_filter( 'get_noptin_option_allow_editors', '__return_false' );
			$user->remove_cap( 'publish_posts' );
			wp_set_current_user( 0 );
			wp_set_current_user( $user_id );
			$draft = $create();
			$assert( 403 === $request( 'POST', '/' . $draft, array( 'status' => 'publish' ) )->get_status(), 'Type access must not grant missing WordPress publish permission.' );
			$assert( 'draft' === get_post_status( $draft ), 'Denied publication must preserve the draft.' );

			// Administrators must retain Gutenberg publishing and normal type changes.
			wp_set_current_user( $admin_id );
			$assert( 'publish_posts' === get_post_type_object( 'noptin-campaign' )->cap->publish_posts, 'Keep standard WordPress publishing capabilities.' );
			foreach ( array( $newsletter, $automation ) as $id ) {
				$response = $request( 'GET', '/' . $id );
				$assert( 200 === $response->get_status() && isset( $response->get_links()['https://api.w.org/action-publish'] ), 'Administrator REST response must retain the Gutenberg publish action.' );
				$assert( 200 === $request( 'POST', '/' . $id, array( 'status' => 'publish' ) )->get_status(), 'Administrator must publish either campaign type.' );
			}
			foreach ( array( 'newsletter', 'automation' ) as $type ) {
				$response = $request( 'POST', '', array( 'status' => 'publish', 'title' => 'Admin create fixture', 'meta' => array( 'campaign_type' => $type, 'campaign_data' => array( 'subject' => 'Admin create fixture', 'email_sender' => 'manual_recipients' ) ) ) );
				$assert( 201 === $response->get_status(), 'Administrator must be able to create published campaigns of either type.' );
				$ids[] = $response->get_data()['id'];
			}
			$assert( 200 === $request( 'POST', '/' . $newsletter, array( 'meta' => array( 'campaign_type' => 'automation' ) ) )->get_status(), 'Administrators must retain intentional retyping.' );
			$response = $request( 'POST', '/' . $automation . '/list-action', array( 'action' => 'duplicate' ) );
			$assert( 200 === $response->get_status(), 'Administrator duplication must succeed.' );
			$ids[] = $response->get_data()['id'];
			$assert( 'draft' === get_post_status( end( $ids ) ), 'Published automation copies must require activation.' );

			// Recursive legacy operations must not copy or delete an inaccessible child.
			$parent = $create();
			$child  = $create( 'automation', $parent );
			wp_publish_post( $child );
			wp_set_current_user( $user_id );
			add_filter( 'wp_redirect', $redirect );
			foreach ( array( 'admin_duplicate_campaign', 'admin_delete_campaign' ) as $method ) {
				try {
					Campaign_Admin::$method( new Email( $parent ) );
				} catch ( Noptin_Campaign_Test_Redirect $e ) {
					$assert( (bool) get_post( $parent ) && (bool) get_post( $child ), 'Recursive actions must leave inaccessible descendants intact.' );
				}
			}
			remove_filter( 'wp_redirect', $redirect );
			wp_set_current_user( $admin_id );
			// Core duplication should handle parent/child types without loading an add-on.
			Hizzle\Noptin\Emails\Main::register_email_type( array(
				'type' => 'sequence',
				'plural' => 'sequences',
				'label' => 'Sequence fixture',
				'child_type' => 'sequence_email',
				'supports_recipients' => false,
			) );
			Hizzle\Noptin\Emails\Main::register_email_type( array(
				'type' => 'sequence_email',
				'plural' => 'sequence_emails',
				'label' => 'Sequence email fixture',
				'parent_type' => 'sequence',
				'supports_recipients' => false,
			) );
			$sequence = $create( 'sequence' );
			$step     = $create( 'sequence_email', $sequence );
			wp_publish_post( $step );
			$copy  = ( new Email( $sequence ) )->duplicate();
			$ids[] = $copy->id;
			$copied_children = get_posts( array( 'post_type' => 'noptin-campaign', 'post_parent' => $copy->id, 'post_status' => 'any', 'numberposts' => -1 ) );
			foreach ( $copied_children as $copied_child ) {
				$ids[] = $copied_child->ID;
				$assert( 'draft' === $copied_child->post_status, 'Copied sequence children must remain drafts.' );
			}
			$assert( 1 === count( $copied_children ), 'Sequence duplication must still copy its children.' );
			$second_copy = $copy->duplicate();
			$assert( 1 === count( $second_copy->get_children( 'any' ) ), 'Duplicating a draft sequence copy must preserve its draft steps.' );

		} finally {
			$type_registry->setValue( null, $original_types );
			remove_filter( 'wp_redirect', $redirect );
			$_REQUEST = array();
			wp_set_current_user( $admin_id );
			foreach ( array_reverse( array_unique( $ids ) ) as $id ) {
				wp_delete_post( $id, true );
			}
			if ( $user_id && ! is_wp_error( $user_id ) ) {
				wp_delete_user( $user_id );
			}
			wp_set_current_user( 0 );
			wp_delete_user( $admin_id );
		}
	}

	public function test_campaign_without_data_metadata_loads_legacy_fields() {
		$post_id = wp_insert_post( array(
			'post_type' => 'noptin-campaign',
			'post_status' => 'draft',
			'post_title' => 'Legacy campaign fixture',
			'post_content' => 'Legacy email body',
			'meta_input' => array( 'campaign_type' => 'newsletter' ),
		) );
		$this->assertGreaterThan( 0, $post_id );
		$missing_data = static function ( $value, $object_id, $meta_key, $single ) use ( $post_id ) {
			if ( $post_id === $object_id && 'campaign_data' === $meta_key ) {
				return $single ? '' : array();
			}
			return $value;
		};
		// Exercise missing metadata even when REST has registered a default value.
		add_filter( 'default_post_metadata', $missing_data, PHP_INT_MAX, 4 );
		try {
			// The insertion hook also loads the campaign before REST metadata is saved.
			delete_post_meta( $post_id, 'campaign_data' );
			$this->assertSame( array(), get_post_meta( $post_id, 'campaign_data' ) );
			$email = new Email( $post_id );
			$this->assertSame( $post_id, $email->id );
			$this->assertSame( 'Legacy campaign fixture', $email->subject );
			$this->assertStringContainsString( 'Legacy email body', $email->options['content_normal'] );
		} finally {
			remove_filter( 'default_post_metadata', $missing_data, PHP_INT_MAX );
			wp_delete_post( $post_id, true );
		}
	}
}
