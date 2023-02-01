<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fired when there is a new reply to a comment.
 *
 * @since 1.11.1
 */
class Noptin_Comment_Reply_Trigger extends Noptin_New_Comment_Trigger {

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'new_comment_reply';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'New Comment Reply', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( "When someone replies to someone else's comment", 'newsletter-optin-box' );
	}

	/**
	 * Returns an array of known smart tags.
	 *
	 * @since 1.9.0
	 * @return array
	 */
	public function get_known_smart_tags() {

		return array_merge(
			parent::get_known_smart_tags(),
			array(
				'reply_id'           => array(
					'description'       => __( 'The reply ID', 'newsletter-optin-box' ),
					'example'           => 'reply_id',
					'conditional_logic' => 'number',
				),
				'reply_author'       => array(
					'description'       => __( 'The reply author', 'newsletter-optin-box' ),
					'example'           => 'reply_author',
					'conditional_logic' => 'string',
				),
				'reply_author_email' => array(
					'description'       => __( 'The reply author email', 'newsletter-optin-box' ),
					'example'           => 'reply_author_email',
					'conditional_logic' => 'string',
				),
				'reply_author_url'   => array(
					'description'       => __( 'The reply author URL', 'newsletter-optin-box' ),
					'example'           => 'reply_author_url',
					'conditional_logic' => 'string',
				),
				'reply_author_ip'    => array(
					'description'       => __( 'The reply author IP', 'newsletter-optin-box' ),
					'example'           => 'reply_author_ip',
					'conditional_logic' => 'string',
				),
				'reply_content'      => array(
					'description'       => __( 'The reply content', 'newsletter-optin-box' ),
					'example'           => 'reply_content',
					'conditional_logic' => 'string',
				),
				'reply_type'  	     => array(
					'description'       => __( 'The reply type', 'newsletter-optin-box' ),
					'example'           => 'reply_type',
					'conditional_logic' => 'string',
				),
			)
		);
	}

	/**
	 * Called when someone leaves a comment.
	 *
	 * @param int    $id             The comment ID.
	 * @param string $comment_status Comment status.
	 */
	public function maybe_trigger( $id, $comment_status ) {

		// Check if the comment is approved.
		if ( 'approve' !== $comment_status && '1' !== $comment_status ) {
			return;
		}

		$comment = get_comment( $id );

		// Bail if this is not a reply.
		if ( empty( $comment ) || empty( $comment->comment_parent ) ) {
			return;
		}

		// Trigger for the parent comment.
		$parent_comment = get_comment( $comment->comment_parent );

		if ( $parent_comment ) {
			$this->trigger_for_parent( $comment, $parent_comment );
		}
	}

	/**
	 * Called when someone leaves a comment.
	 *
	 * @param WP_Comment $comment Comment object.
	 * @param WP_Comment $parent_comment Parent comment object.
	 */
	public function trigger_for_parent( $comment, $parent_comment ) {

		// Bail if the commentor is the same as the parent commentor.
		if ( $comment->comment_author_email === $parent_comment->comment_author_email ) {
			return;
		}

		$args = get_object_vars( $parent_comment );

		foreach ( get_object_vars( $comment ) as $key => $value ) {
			$args[ str_replace( 'comment_', 'reply_', $key ) ] = $value;
		}

		// Add post data.
		if ( ! empty( $parent_comment->comment_post_ID ) ) {
			$post = get_post( $parent_comment->comment_post_ID );

			if ( $post ) {
				$args = array_merge( $args, $this->prepare_post_smart_tags( $post ) );
			}
		}

		// Convert all keys to lowercase.
		$args = array_change_key_case( $args, CASE_LOWER );

		$this->trigger( $comment->comment_author_email, $args );
	}
}
