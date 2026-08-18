<?php

namespace Hizzle\Noptin\Integrations\WordPress_Comment_Form;

defined( 'ABSPATH' ) || exit;

/**
 * Container for a WordPress comment.
 */
class Comment extends \Hizzle\Noptin\Objects\Record {

	/**
	 * @var \WP_Comment|null The external object.
	 */
	public $external;

	/**
	 * Class constructor.
	 *
	 * @param int|\WP_Comment $comment Comment ID or object.
	 */
	public function __construct( $comment ) {
		$this->external = is_numeric( $comment ) ? get_comment( $comment ) : $comment;
	}

	/**
	 * Checks whether the comment exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return $this->external instanceof \WP_Comment && ! empty( $this->external->comment_ID );
	}

	/**
	 * Retrieves a comment field.
	 *
	 * @param string $field Field name.
	 * @param array  $args  Formatting arguments.
	 * @return mixed
	 */
	public function get( $field, $args = array() ) {
		if ( ! $this->exists() ) {
			return null;
		}

		if ( 0 === strpos( $field, 'parent_' ) ) {
			$parent = get_comment( $this->external->comment_parent );
			return $parent ? $this->get_comment_field( $parent, substr( $field, 7 ) ) : null;
		}

		if ( 0 === strpos( $field, 'current_' ) ) {
			return $this->get_comment_field( $this->external, substr( $field, 8 ) );
		}

		if ( 'meta' === $field ) {
			$key = isset( $args['key'] ) ? $args['key'] : '';
			return '' === $key ? null : get_comment_meta( $this->external->comment_ID, $key, true );
		}

		if ( 'post_id' !== $field && 0 === strpos( $field, 'post_' ) ) {
			return $this->get_post_field( $field );
		}

		return $this->get_comment_field( $this->external, $field );
	}

	/**
	 * Retrieves a field from a comment object.
	 *
	 * @param \WP_Comment $comment Comment object.
	 * @param string      $field   Field name.
	 * @return mixed
	 */
	private function get_comment_field( $comment, $field ) {
		$map = array(
			'id'           => 'comment_ID',
			'comment_id'   => 'comment_ID',
			'post_id'      => 'comment_post_ID',
			'parent_id'    => 'comment_parent',
			'author'       => 'comment_author',
			'author_email' => 'comment_author_email',
			'author_url'   => 'comment_author_url',
			'author_ip'    => 'comment_author_IP',
			'date'         => 'comment_date',
			'date_gmt'     => 'comment_date_gmt',
			'content'      => 'comment_content',
			'karma'        => 'comment_karma',
			'approved'     => 'comment_approved',
			'agent'        => 'comment_agent',
			'type'         => 'comment_type',
			'user_id'      => 'user_id',
		);

		if ( 'url' === $field ) {
			return get_comment_link( $comment );
		}

		$key = isset( $map[ $field ] ) ? $map[ $field ] : $field;
		return isset( $comment->{$key} ) ? $comment->{$key} : null;
	}

	/**
	 * Retrieves a field from the commented post.
	 *
	 * @param string $field Field name.
	 * @return mixed
	 */
	private function get_post_field( $field ) {
		$post = get_post( $this->external->comment_post_ID );

		if ( ! $post ) {
			return null;
		}

		switch ( $field ) {
			case 'post_url':
				return get_permalink( $post );
			case 'post_excerpt':
				return get_the_excerpt( $post );
			case 'post_comment_count':
				return $post->comment_count;
			default:
				return isset( $post->{$field} ) ? $post->{$field} : null;
		}
	}

	/**
	 * Provides the commented post's author.
	 *
	 * @param string $collection Collection type.
	 * @return int
	 */
	public function provide( $collection ) {
		if ( 'post_author' === $collection && $this->exists() ) {
			$post = get_post( $this->external->comment_post_ID );
			return $post ? (int) $post->post_author : 0;
		}

		if ( 'comment' === $collection && $this->exists() ) {
			return (int) $this->external->comment_parent;
		}

		if ( 'comment_author' === $collection && $this->exists() ) {
			return (int) $this->external->comment_ID;
		}

		return parent::provide( $collection );
	}
}
