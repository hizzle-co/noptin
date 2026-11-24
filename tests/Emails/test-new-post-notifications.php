<?php
/** Regression coverage for Classic new-post notifications and their newsletter copies. */

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

use Hizzle\Noptin\Emails\Email;
use Hizzle\Noptin\Tasks\Main as Tasks;

/** Test notification generation without running newsletter delivery. */
class Test_New_Post_Notifications extends Noptin_Regression_Test_Case {

	/**
	 * Keep each post type independently runnable, even when the other case fails.
	 *
	 * @return array Named post-type cases.
	 */
	public static function post_types() {
		$cases = array();
		foreach ( array( 'page', 'post' ) as $post_type ) {
			$cases[ $post_type ] = array( $post_type );
		}
		return $cases;
	}

	/**
	 * Publish a supported fixture type and inspect its resulting saved newsletter.
	 *
	 * @dataProvider post_types
	 * @param string $post_type Source post type.
	 */
	public function test_classic_notification_preserves_options_and_post_content( $post_type ) {
		$campaign_ids = array();
		$post_ids = array();
		$rule_ids = array();
		$task_ids = array();
		$published_newsletters = array();
		$licensed = true;
		add_filter( 'pre_option_noptin_helper_data', static function () use ( &$licensed ) { return $licensed ? array( 'license_key' => 'notification-test-key' ) : array(); }, PHP_INT_MAX );
		// Preserve real newsletter publication, but stop before queueing delivery.
		remove_all_actions( 'noptin_newsletter_campaign_published' );
		remove_all_actions( 'noptin_send_newsletter' );
		add_action( 'noptin_newsletter_campaign_published', static function ( $email ) use ( &$published_newsletters ) { $published_newsletters[] = $email->id; } );
		add_filter( 'noptin_saved_task_background_run', '__return_true', PHP_INT_MAX );
		remove_action( 'shutdown', array( $GLOBALS['noptin_tasks'], 'run_overdue_tasks_on_shutdown' ), 1 );
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		add_action( 'wp_after_insert_post', static function ( $id, $post, $update ) use ( &$campaign_ids ) {
			if ( ! $update && 'noptin-campaign' === $post->post_type ) {
				$campaign_ids[] = $id;
			}
		}, 10, 3 );

		try {
			// A saved premium automation can remain after license expiry. Configure it
			// legitimately, then test runtime gating without granting any entitlement.
			$licensed = 'page' === $post_type;
			$content = '<h2>[[post.title]]</h2>[[post.content]]';
			$automation = new Email( array(
				'author' => $admin_id,
				'type' => 'automation',
				'status' => 'publish',
				'name' => 'Classic ' . $post_type . ' notification',
				'subject' => 'New article: [[post.title]]',
				'options' => array(
					'automation_type' => 'automation_rule_' . $post_type . '_published',
					'email_sender' => 'noptin',
					'email_type' => 'normal',
					'template' => 'paste',
					'content_normal' => $content,
					'heading' => 'Article: [[post.title]]',
					'preview_text' => 'Read [[post.title]]',
					'footer_text' => 'Notification fixture footer',
					'from_name' => 'Notification fixture',
					'from_email' => 'newsletter@example.test',
					'reply_to' => 'reply@example.test',
					'custom_css' => '.fixture { color: #123456; }',
					'fixture_options' => array( 'enabled' => true, 'count' => 3, 'labels' => array( 'one', 'two' ) ),
				),
			) );
			$automation->save();
			$automation = new Email( $automation->id );
			$this->assertSame( 'publish', $automation->status, $post_type );
			$this->assertSame( 'normal', $automation->get_email_type(), $post_type );
			$this->assertSame( 'paste', $automation->get_template(), $post_type );
			$rule = noptin_get_automation_rule( (int) $automation->get( 'automation_rule' ) );
			$this->assertFalse( is_wp_error( $rule ), $post_type );
			$this->assertTrue( $rule->exists(), $post_type );
			$rule_ids[] = $rule->get_id();
			$this->assertSame( $post_type . '_published', $rule->get_trigger_id(), $post_type );
			$this->assertTrue( (bool) $rule->get_status(), $post_type );
			$this->assertSame( 'page' === $post_type, (bool) $rule->get_trigger()->forcePremium, $post_type );
			$source_options = $automation->options;

			$licensed = false;
			$this->assertFalse( noptin_has_alk(), 'Generation must run without a Premium license.' );
			$title = 'Published ' . $post_type . ' notification fixture';
			$body = '<p>Unique notification body for ' . $post_type . '.</p>';
			$post_id = self::factory()->post->create( array( 'post_type' => $post_type, 'post_status' => 'publish', 'post_author' => $admin_id, 'post_title' => $title, 'post_content' => $body ) );
			$post_ids[] = $post_id;
			$this->assertSame( 'publish', get_post_status( $post_id ) );

			// Execute the actual job scheduled by publication, without sleeping ten seconds
			// or invoking the notification hook as a substitute for its scheduler.
			$scheduled = array_filter( Tasks::query( array( 'hook' => 'noptin_force_trigger_new_post_notification', 'per_page' => 100 ) ), static function ( $task ) use ( $post_id ) { return array( $post_id ) === json_decode( $task->get_args(), true ); } );
			$this->assertCount( 1, $scheduled, $post_type . ' must schedule its publication event.' );
			foreach ( $scheduled as $task ) {
				$task_ids[] = $task->get_id();
				$task->process();
				$this->assertSame( 'complete', Tasks::get( $task->get_id() )->get_status(), $post_type . ': ' . wp_json_encode( $task->get_logs() ) );
			}
			foreach ( Tasks::query( array( 'hook' => 'noptin_run_automation_rule', 'primary_id' => $rule->get_id(), 'per_page' => 100 ) ) as $task ) {
				$task_ids[] = $task->get_id();
				if ( 'pending' === $task->get_status() ) {
					$task->process();
				}
				if ( 'post' === $post_type ) {
					$this->assertSame( 'complete', Tasks::get( $task->get_id() )->get_status(), wp_json_encode( $task->get_logs() ) );
				}
			}

			$newsletters = get_posts( array( 'post_type' => 'noptin-campaign', 'post_status' => 'any', 'post_parent' => $automation->id, 'numberposts' => -1, 'meta_key' => 'campaign_type', 'meta_value' => 'newsletter' ) );
			$this->assertCount( 'post' === $post_type ? 1 : 0, $newsletters, $post_type );
			if ( 'page' === $post_type ) {
				return;
			}
			$newsletter = new Email( $newsletters[0]->ID );
			$this->assertSame( 'publish', $newsletter->status );
			$this->assertContains( $newsletter->id, $published_newsletters );
			$this->assertSame( 'newsletter', $newsletter->type );
			$this->assertSame( $automation->id, $newsletter->parent_id );
			$this->assertSame( 'normal', $newsletter->options['email_type'] );
			$this->assertSame( 'paste', $newsletter->options['template'] );
			$this->assertSame( 'noptin', $newsletter->get_sender() );
			$this->assertSame( 'New article: ' . $title, $newsletter->get_subject() );
			$this->assertNotSame( $newsletter->name, $newsletter->get_subject() );
			$this->assertNotSame( '', trim( $newsletter->get_content() ) );
			$this->assertStringContainsString( $title, $newsletter->get_content() );
			$this->assertStringContainsString( 'Unique notification body for post.', $newsletter->get_content() );
			$this->assertStringNotContainsString( '[[post.', $newsletter->get_content() );
			$this->assertSame( $post_id, (int) $newsletter->get( 'source_post_id' ) );
			$this->assertSame( 'Article: ' . $title, $newsletter->get( 'heading' ) );
			$this->assertSame( 'Read ' . $title, $newsletter->get( 'preview_text' ) );
			// Contextual text is expanded and unused composition formats are intentionally
			// cleared; every other saved option must survive the newsletter copy exactly.
			$rendered = array( 'subject', 'heading', 'preview_text', 'content_normal' );
			foreach ( array_keys( get_noptin_email_types() ) as $format ) {
				$rendered[] = 'content_' . $format;
			}
			foreach ( $source_options as $key => $value ) {
				if ( ! in_array( $key, $rendered, true ) ) {
					$this->assertArrayHasKey( $key, $newsletter->options, $key );
					$this->assertSame( $value, $newsletter->options[ $key ], $key );
				}
			}
			$this->assertSame( $source_options, ( new Email( $automation->id ) )->options, 'Generation must preserve the source automation.' );
			wp_update_post( array( 'ID' => $post_id, 'post_content' => $body . '<p>Updated.</p>' ) );
			$this->assertCount( 1, get_posts( array( 'post_type' => 'noptin-campaign', 'post_status' => 'any', 'post_parent' => $automation->id, 'numberposts' => -1 ) ), 'Editing an already-published post must not create another newsletter.' );
		} finally {
			foreach ( array_unique( $task_ids ) as $id ) {
				Tasks::get( $id )->delete();
			}
			foreach ( $rule_ids as $id ) {
				noptin_get_automation_rule( $id )->delete();
			}
			foreach ( array_reverse( array_unique( $campaign_ids ) ) as $id ) {
				wp_delete_post( $id, true );
			}
			foreach ( $post_ids as $id ) {
				wp_delete_post( $id, true );
			}
		}
	}
}