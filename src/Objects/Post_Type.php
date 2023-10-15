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

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct();

		// Created.
		add_action( 'wp_after_insert_post', array( $this, 'on_create' ), 100, 3 );

		// Deleted.
		add_action( 'before_delete_post', array( $this, 'on_delete' ), 100, 2 );
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
				$this->type . '_created' => array(
					'label'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Created', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'When a %s is created', 'newsletter-optin-box' ),
						$this->singular_label
					),
				),
				$this->type . '_deleted' => array(
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
				),
			)
		);
	}

	/**
	 * Fired after a post is created.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 * @param bool     $update  Whether this is an existing post being updated or not.
	 */
	public function on_create( $post_id, $post, $update ) {

		if ( $update || $this->type !== $post->post_type ) {
			return;
		}

		$user = get_user_by( 'id', $post->post_author );

		if ( empty( $user ) ) {
			return;
		}

		$this->trigger(
			$this->type . '_created',
			array(
				'email'      => $user->user_email,
				'post_id'    => $post_id,
				'subject_id' => $user->ID,
				'url'        => get_edit_post_link( $post_id ),
				'activity'   => $post->post_title,
			)
		);
	}

	/**
	 * Fired before a post is deleted.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 */
	public function on_delete( $post_id, $post ) {
		if ( $this->type !== $post->post_type ) {
			return;
		}

		$user = get_user_by( 'id', $post->post_author );

		if ( empty( $user ) ) {
			return;
		}

		$this->trigger(
			$this->type . '_deleted',
			array(
				'email'      => $user->user_email,
				'object_id'  => $post_id,
				'subject_id' => $user->ID,
				'url'        => get_edit_post_link( $post_id ),
				'activity'   => $post->post_title,
			)
		);
	}
}
