<?php

namespace Hizzle\Noptin\Objects;

/**
 * Container for a post type object type.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Container for a post type object type.
 */
abstract class Post_Type extends Collection {

	/**
	 * @var string object type.
	 */
	public $object_type = 'post_type';

	private static $registered_subject = false;

	/**
	 * Constructor
	 */
	public function __construct() {

		if ( false === self::$registered_subject ) {
			Store::add( new Users( 'post_author', __( 'Authors', 'newsletter-optin-box' ), __( 'Post Author', 'newsletter-optin-box' ) ) );
			self::$registered_subject = true;
		}

		parent::__construct();

		// Fire triggers.
		add_action( 'wp_after_insert_post', array( $this, 'after_insert_post' ), 100, 4 );

		// Deleted.
		add_action( 'before_delete_post', array( $this, 'on_delete' ), 0, 2 );
	}

	/**
	 * Returns a list of available triggers.
	 *
	 * @return array $triggers The triggers.
	 */
	public function get_triggers() {
		return array_merge(
			parent::get_triggers(),
			array(
				$this->type . '_published'   => array(
					'label'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Published', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'When a %s is published', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'subject'     => 'post_author',
				),
				$this->type . '_unpublished' => array(
					'label'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Unpublished', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'When a %s is unpublished', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'subject'     => 'post_author',
				),
				$this->type . '_deleted'     => array(
					'label'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Deleted', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'When a %s is deleted', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'subject'     => 'post_author',
				),
			)
		);
	}

	/**
	 * (Maybe) triggers an event.
	 *
	 * @param int    $user_id The user ID.
	 * @param int    $post_id The post ID.
	 * @param string $event   The event.
	 */
	protected function maybe_trigger( $user_id, $post_id, $event ) {

		$user = get_user_by( 'id', $user_id );

		if ( empty( $user ) ) {
			return;
		}

		$this->trigger(
			$event,
			array(
				'email'      => $user->user_email,
				'object_id'  => $post_id,
				'subject_id' => $user->ID,
				'url'        => get_edit_post_link( $post_id ),
				'activity'   => get_the_title( $post_id ),
			)
		);
	}

	/**
	 * Fired after a post is inserted.
	 *
	 * @param int          $post_id     Post ID.
	 * @param WP_Post      $post        Post object.
	 * @param bool         $update      Whether this is an existing post being updated.
	 * @param null|WP_Post $post_before Null for new posts, the WP_Post object prior
	 *                                  to the update for updated posts.
	 */
	public function after_insert_post( $post_id, $post, $update, $post_before ) {

		// Abort if not our post type.
		if ( $this->type !== $post->post_type ) {
			return;
		}

		$old_status = $post_before ? $post_before->post_status : 'auto-draft';
		$new_status = $post->post_status;

		// Abort if the two match.
		if ( $old_status === $new_status ) {
			return;
		}

		// Are we publishing a post?
		if ( 'publish' === $new_status ) {
			$this->maybe_trigger( $post->post_author, $post_id, $this->type . '_published' );
		}

		// Are we unpublishing a post?
		if ( 'publish' === $old_status ) {
			$this->maybe_trigger( $post->post_author, $post_id, $this->type . '_unpublished' );
		}
	}

	/**
	 * Fired before a post is deleted.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 */
	public function on_delete( $post_id, $post ) {
		if ( $this->type === $post->post_type ) {
			$this->maybe_trigger( $post->post_author, $post_id, $this->type . '_deleted' );
		}
	}
}
