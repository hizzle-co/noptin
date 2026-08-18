<?php

namespace Hizzle\Noptin\Tests\Automation_Rules;

use Hizzle\Noptin\Automation_Rules\Triggers\Main as Triggers_Main;
use WP_UnitTestCase;

/**
 * Tests for the comment-reply automation trigger.
 */
class Test_Comment_Reply_Trigger extends WP_UnitTestCase {

	/**
	 * Ensures legacy parent-comment and reply merge tags resolve correctly.
	 */
	public function test_deprecated_merge_tags_distinguish_parent_and_reply() {
		$post_id = self::factory()->post->create(
			array(
				'post_title'   => 'Post With Reply',
				'post_content' => 'Reply post content.',
				'post_excerpt' => 'Reply post excerpt.',
			)
		);
		$parent_id = self::factory()->comment->create(
			array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => 'Parent Author',
				'comment_author_email' => 'parent@example.com',
				'comment_author_url'   => 'https://example.com/parent',
				'comment_author_IP'    => '192.0.2.20',
				'comment_content'      => 'Parent comment.',
				'comment_type'         => 'comment',
				'comment_approved'     => 1,
			)
		);
		$reply_id = self::factory()->comment->create(
			array(
				'comment_post_ID'      => $post_id,
				'comment_parent'       => $parent_id,
				'comment_author'       => 'Reply Author',
				'comment_author_email' => 'reply@example.com',
				'comment_author_url'   => 'https://example.com/reply',
				'comment_author_IP'    => '192.0.2.21',
				'comment_content'      => 'Reply comment.',
				'comment_type'         => 'comment',
				'comment_approved'     => 1,
			)
		);

		$trigger = Triggers_Main::get( 'new_comment_reply' );
		$this->assertNotNull( $trigger );

		$prepared = $trigger->unserialize_trigger_args(
			array(
				'email'      => 'reply@example.com',
				'object_id'  => $reply_id,
				'subject_id' => $reply_id,
				'provides'   => array(
					'comment.parent'   => $parent_id,
					'commentor.parent' => $parent_id,
				),
			)
		);
		$tags = $prepared['smart_tags'];

		$expected = array(
			'comment_id'           => (string) $parent_id,
			'comment_author'       => 'Parent Author',
			'comment_author_email' => 'parent@example.com',
			'comment_author_url'   => 'https://example.com/parent',
			'comment_author_ip'    => '192.0.2.20',
			'comment_content'      => 'Parent comment.',
			'comment_type'         => 'comment',
			'reply_id'             => (string) $reply_id,
			'reply_author'         => 'Reply Author',
			'reply_author_email'   => 'reply@example.com',
			'reply_author_url'     => 'https://example.com/reply',
			'reply_author_ip'      => '192.0.2.21',
			'reply_content'        => 'Reply comment.',
			'reply_type'           => 'comment',
		);

		foreach ( $expected as $merge_tag => $value ) {
			$this->assertSame( $value, $tags->replace_in_text_field( "[[{$merge_tag}]]" ), $merge_tag );
		}
	}
}
