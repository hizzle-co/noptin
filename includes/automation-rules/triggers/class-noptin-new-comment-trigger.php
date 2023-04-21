<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fired when there is a new comment.
 *
 * @since 1.11.1
 */
class Noptin_New_Comment_Trigger extends Noptin_Abstract_Trigger {

	/**
	 * @var string
	 */
	public $category = 'WordPress';

	/**
	 * @var string
	 */
	public $integration = 'registration-form';

	/**
	 * Constructor.
	 *
	 * @since 1.11.1
	 * @return string
	 */
	public function __construct() {
		add_action( 'wp_set_comment_status', array( $this, 'maybe_trigger' ), 1000, 2 );
		add_action( 'wp_insert_comment', array( $this, 'maybe_trigger_for_new_comment' ), 1000, 2 );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'new_comment';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'New Comment', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'When someone leaves a comment', 'newsletter-optin-box' );
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
			$this->get_post_smart_tags(),
			array(
				'comment_id'           => array(
					'description'       => __( 'The comment ID', 'newsletter-optin-box' ),
					'example'           => 'comment_id',
					'conditional_logic' => 'number',
				),
				'comment_author'       => array(
					'description'       => __( 'The comment author', 'newsletter-optin-box' ),
					'example'           => 'comment_author',
					'conditional_logic' => 'string',
				),
				'comment_author_email' => array(
					'description'       => __( 'The comment author email', 'newsletter-optin-box' ),
					'example'           => 'comment_author_email',
					'conditional_logic' => 'string',
				),
				'comment_author_url'   => array(
					'description'       => __( 'The comment author URL', 'newsletter-optin-box' ),
					'example'           => 'comment_author_url',
					'conditional_logic' => 'string',
				),
				'comment_author_ip'    => array(
					'description'       => __( 'The comment author IP', 'newsletter-optin-box' ),
					'example'           => 'comment_author_ip',
					'conditional_logic' => 'string',
				),
				'comment_content'      => array(
					'description'       => __( 'The comment content', 'newsletter-optin-box' ),
					'example'           => 'comment_content',
					'conditional_logic' => 'string',
				),
				'comment_type'  	   => array(
					'description'       => __( 'The comment type', 'newsletter-optin-box' ),
					'example'           => 'comment_type',
					'conditional_logic' => 'string',
				),
			)
		);
	}

	/**
	 * Triggers for new comment.
	 *
	 * @param int $id The comment ID.
	 * @param WP_Comment $comment The comment object.
	 */
	public function maybe_trigger_for_new_comment( $id, $comment ) {

		// Bail if not approved.
		if ( '1' !== $comment->comment_approved ) {
			return;
		}

		$this->maybe_trigger( $id, 'approve' );
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

		// Bail if this is a reply.
		if ( empty( $comment ) || ! empty( $comment->comment_parent ) ) {
			return;
		}

		$args = get_object_vars( $comment );

		// Add post data.
		if ( ! empty( $comment->comment_post_ID ) ) {
			$post = get_post( $comment->comment_post_ID );

			if ( $post ) {
				$args = array_merge( $args, $this->prepare_post_smart_tags( $post ) );
			}
		}

		// Convert all keys to lowercase.
		$args = array_change_key_case( $args, CASE_LOWER );

		$this->trigger( $comment->comment_author_email, $args );
	}

	/**
	 * Serializes the trigger args.
	 *
	 * @since 1.11.1
	 * @param array $args The args.
	 * @return false|array
	 */
	public function serialize_trigger_args( $args ) {
		return array(
			'comment_id' => $args['comment_id'],
		);
	}

	/**
	 * Unserializes the trigger args.
	 *
	 * @since 1.11.1
	 * @param array $args The args.
	 * @return array|false
	 */
	public function unserialize_trigger_args( $args ) {
		$comment = get_comment( $args['comment_id'] );

		if ( empty( $comment ) ) {
			throw new Exception( 'The comment no longer exists' );
		}

		$args = get_object_vars( $comment );

		// Add post data.
		if ( ! empty( $comment->comment_post_ID ) ) {
			$post = get_post( $comment->comment_post_ID );

			if ( $post ) {
				$args = array_merge( $args, $this->prepare_post_smart_tags( $post ) );
			}
		}

		// Convert all keys to lowercase.
		$args = array_change_key_case( $args, CASE_LOWER );

		return $this->prepare_trigger_args( $comment->comment_author_email, $args );
	}
}
