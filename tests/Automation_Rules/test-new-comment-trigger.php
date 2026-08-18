<?php

namespace Hizzle\Noptin\Tests\Automation_Rules;

use Hizzle\Noptin\Automation_Rules\Triggers\Main as Triggers_Main;
use WP_UnitTestCase;

/**
 * Tests for the new-comment automation trigger.
 */
class Test_New_Comment_Trigger extends WP_UnitTestCase {

	/**
	 * Ensures legacy comment and post merge tags remain usable.
	 */
	public function test_deprecated_merge_tags_resolve_to_comment_data() {
		$author_id = self::factory()->user->create(
			array(
				'display_name' => 'Post Author',
				'user_email'   => 'post-author@example.com',
			)
		);
		$post_id   = self::factory()->post->create(
			array(
				'post_author'  => $author_id,
				'post_title'   => 'Commented Post',
				'post_content' => 'Post content.',
				'post_excerpt' => 'Post excerpt.',
				'post_date'    => '2024-01-02 03:04:05',
				'post_name'    => 'commented-post',
			)
		);
		$comment_id = self::factory()->comment->create(
			array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => 'Comment Author',
				'comment_author_email' => 'comment-author@example.com',
				'comment_author_url'   => 'https://example.com/comment-author',
				'comment_author_IP'    => '192.0.2.10',
				'comment_content'      => 'A top-level comment.',
				'comment_type'         => 'comment',
				'comment_approved'     => 1,
			)
		);

		$trigger = Triggers_Main::get( 'new_comment' );
		$this->assertNotNull( $trigger );

		$prepared = $trigger->unserialize_trigger_args(
			array(
				'email'      => 'comment-author@example.com',
				'object_id'  => $comment_id,
				'subject_id' => $comment_id,
			)
		);
		$tags = $prepared['smart_tags'];
		$post = get_post( $post_id );

		$expected = array(
			'comment_id'           => (string) $comment_id,
			'comment_author'       => 'Comment Author',
			'comment_author_email' => 'comment-author@example.com',
			'comment_author_url'   => 'https://example.com/comment-author',
			'comment_author_ip'    => '192.0.2.10',
			'comment_content'      => 'A top-level comment.',
			'comment_type'         => 'comment',
			'post_id'              => (string) $post_id,
			'post_author_id'       => (string) $author_id,
			'post_author_name'     => 'Post Author',
			'post_author_email'    => 'post-author@example.com',
			'post_date'            => $post->post_date,
			'post_title'           => 'Commented Post',
			'post_url'             => get_permalink( $post_id ),
			'post_excerpt'         => 'Post excerpt.',
			'post_content'         => 'Post content.',
			'post_status'          => $post->post_status,
			'post_password'        => $post->post_password,
			'post_name'            => $post->post_name,
			'post_modified'        => $post->post_modified,
			'post_type'            => $post->post_type,
			'post_comment_count'   => (string) $post->comment_count,
		);

		foreach ( $expected as $merge_tag => $value ) {
			$this->assertSame( $value, $tags->replace_in_text_field( "[[{$merge_tag}]]" ), $merge_tag );
		}
	}
}
