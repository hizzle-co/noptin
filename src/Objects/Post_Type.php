<?php

namespace Hizzle\Noptin\Objects;

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
	 * @var string $can_list Can list.
	 */
	public $can_list = true;

	public $title_field       = 'title';
	public $description_field = 'excerpt';
	public $image_field       = 'featured_image';
	public $url_field         = 'url';
	public $meta_field        = 'date';

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
		add_action( 'noptin_force_trigger_new_post_notification', array( $this, 'force_trigger_for_post_published' ) );

		// Deleted.
		add_action( 'before_delete_post', array( $this, 'on_delete' ), 0, 2 );
	}

	/**
	 * Returns a list of available triggers.
	 *
	 * @return array $triggers The triggers.
	 */
	public function get_triggers() {
		$content_normal = '';
		$blocks         = '';

		if ( ! empty( $this->image_field ) ) {
			$content_normal .= sprintf(
				'<p>%s</p>',
				$this->field_to_merge_tag(
					$this->image_field,
					array(
						'size'   => 'large',
						'format' => 'image',
						'alt'    => $this->field_to_merge_tag( $this->title_field ),
					)
				)
			);

			$blocks .= $this->featured_image_block();
		}

		if ( ! empty( $this->description_field ) ) {
			$content_normal .= sprintf(
				'<p>%s</p>',
				$this->field_to_merge_tag( $this->description_field )
			);

			$block_name = \Hizzle\Noptin\Emails\Admin\Editor::merge_tag_to_block_name( $this->field_to_merge_tag( $this->description_field ) );
			$blocks    .= sprintf(
				'<!-- wp:%1$s {"anchor":"%2$s"} --><p class="wp-block-%2$s noptin-block__margin-wrapper %2$s">%3$s</p><!-- /wp:%1$s -->',
				$block_name,
				str_replace( '/', '-', $block_name ),
				$this->field_to_merge_tag( $this->description_field )
			);
		}

		if ( ! empty( $this->url_field ) ) {
			$content_normal .= sprintf(
				'<div>[[button url="%s" text="%s"]]</div><p>%s</p><p>%s</p>',
				$this->field_to_merge_tag( $this->url_field ),
				__( 'Continue Reading', 'newsletter-optin-box' ),
				__( "If that doesn't work, copy and paste the following link into your browser:", 'newsletter-optin-box' ),
				$this->field_to_merge_tag( $this->url_field )
			);

			$blocks .= sprintf(
				'%s%s%s',
				$this->read_more_block(),
				noptin_email_wrap_paragraph_block( __( "If that doesn't work, copy and paste the following link into your browser:", 'newsletter-optin-box' ) ),
				noptin_email_wrap_paragraph_block( $this->field_to_merge_tag( $this->url_field ) )
			);
		}

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
						strtolower( $this->singular_label )
					),
					'subject'     => 'post_author',
					'mail_config' => array(
						'object_type' => $this->object_type,
						'label'       => ucwords(
							sprintf(
								/* translators: %s: Object type label. */
								__( 'New %s notification', 'newsletter-optin-box' ),
								$this->singular_label
							)
						),
						'defaults'    => array(
							'subject'        => $this->field_to_merge_tag( $this->title_field ),
							'heading'        => $this->field_to_merge_tag( $this->title_field ),
							'preview_text'   => sprintf(
								/* translators: %s: Object type label. */
								__( 'A new %s has been published on [[blog_name]].', 'newsletter-optin-box' ),
								strtolower( $this->singular_label )
							),
							'blocks'         => $blocks,
							'content_normal' => $content_normal,
						),
					),
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
						strtolower( $this->singular_label )
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
						strtolower( $this->singular_label )
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
				'post_meta'  => array(
					'id'  => $post_id,
					'key' => $this->type . '_published' === $event ? 'noptin_sent_notification_campaign' : '',
				),
			)
		);
	}

	/**
	 * Retrieves a test object args.
	 *
	 * @since 3.0.0
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule
	 * @throws \Exception
	 * @return array
	 */
	public function get_test_args( $rule ) {

		// Fetch latest id.
		$post = current(
			get_posts(
				array(
					'post_type'   => $this->type,
					'numberposts' => 1,
				)
			)
		);

		if ( empty( $post ) ) {
			$post = current(
				get_posts(
					array(
						'post_type'   => $this->type,
						'numberposts' => 1,
						'post_status' => 'any',
					)
				)
			);
		}

		if ( empty( $post ) ) {
			throw new \Exception( 'No test ' . esc_html( strtolower( $this->singular_label ) ) . ' found' );
		}

		return array(
			'object_id'  => $post->ID,
			'subject_id' => $post->post_author,
		);
	}

	/**
	 * Return if auto-saving or not
	 *
	 * @return bool True if mid auto-save. False if not mid auto-save.
	 */
	protected function doing_autosave() {
		return defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE;
	}

	/**
	 * (Maybe) triggers a create hook.
	 *
	 */
	protected function maybe_trigger_create_hook( $user_id, $post_id, $post, $post_before, $hook ) {

		// Abort if not our post type.
		if ( $this->type !== $post->post_type || wp_is_post_revision( $post ) ) {
			return;
		}

		$old_status = $post_before ? $post_before->post_status : 'auto-draft';
		$new_status = $post->post_status;

		if ( $this->doing_autosave() && 'auto-draft' === $old_status ) {
			update_post_meta( $post_id, $hook, 'yes' );
			return;
		}

		if ( $old_status === $new_status ) {
			return;
		}

		if ( 'yes' === get_post_meta( $post_id, $hook, true ) ) {
			$this->maybe_trigger( $user_id, $post_id, $hook );
			return delete_post_meta( $post_id, $hook );
		}

		if ( 'auto-draft' === $old_status ) {
			$this->maybe_trigger( $user_id, $post_id, $hook );
		}
	}

	/**
	 * Fired after a post is inserted.
	 *
	 * @param int           $post_id     Post ID.
	 * @param \WP_Post      $post        Post object.
	 * @param bool          $update      Whether this is an existing post being updated.
	 * @param null|\WP_Post $post_before Null for new posts, the WP_Post object prior
	 *                                  to the update for updated posts.
	 */
	public function after_insert_post( $post_id, $post, $update, $post_before ) {

		// Abort if not our post type.
		if ( wp_is_post_revision( $post ) || $this->type !== $post->post_type ) {
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
	 * Fired after a post is inserted.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function force_trigger_for_post_published( $post ) {

		// Abort if not our post type.
		if ( wp_is_post_revision( $post ) || $this->type !== $post->post_type ) {
			return;
		}

		$this->maybe_trigger( $post->post_author, $post->ID, $this->type . '_published' );
	}

	/**
	 * Fired before a post is deleted.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 */
	public function on_delete( $post_id, $post ) {
		if ( $this->type === $post->post_type && ! wp_is_post_revision( $post ) ) {
			$this->maybe_trigger( $post->post_author, $post_id, $this->type . '_deleted' );
		}
	}

	/**
	 * Prepares a single item.
	 *
	 * @param int $item The item.
	 */
	public function prepare_item( $item ) {
		$this->current_item = $this->get( $item );

		if ( $this->current_item->exists() ) {
			$GLOBALS['post'] = $this->current_item->external; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $this->current_item->external );
		}
	}

	protected function taxonomies() {
		return wp_list_pluck(
			wp_list_filter(
				get_object_taxonomies( $this->type, 'objects' ),
				array(
					'public' => true,
				)
			),
			'label',
			'name'
		);
	}

	protected function taxonomy_tag_config( $label, $description, $icon ) {

		return array(
			'label'       => $label,
			'description' => $description,
			'type'        => 'string',
			'block'       => array(
				'title'       => $label,
				'description' => $description,
				'icon'        => $icon,
				'metadata'    => array(
					'ancestor' => array( $this->context ),
				),
				'settings'    => array(
					'link'    => array(
						'label'       => __( 'Link', 'newsletter-optin-box' ),
						'el'          => 'input',
						'type'        => 'checkbox',
						'description' => __( 'Whether to link to the term.', 'newsletter-optin-box' ),
					),
					'default' => array(
						'label'       => __( 'Default Value', 'newsletter-optin-box' ),
						'el'          => 'input',
						'type'        => 'text',
						'description' => __( 'The default value to display if not set.', 'newsletter-optin-box' ),
					),
				),
				'element'     => 'div',
			),
		);
	}
}
