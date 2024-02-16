<?php

namespace Hizzle\Noptin\Objects;

defined( 'ABSPATH' ) || exit;

/**
 * Container for a generic post type.
 */
class Generic_Post_Type extends Post_Type {

	/**
	 * @var string the record class.
	 */
	public $record_class = '\Hizzle\Noptin\Objects\Generic_Post';

	/**
	 * @var string integration.
	 */
	public $integration = 'wordpress'; // phpcs:ignore

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function __construct( $type, $init = false ) {
		$post_type = get_post_type_object( $type );

		if ( ! $post_type ) {
			_doing_it_wrong( __METHOD__, sprintf( 'Post type %s does not exist.', esc_html( $type ) ), esc_html( noptin()->version ) );
		}

		$this->label          = $post_type->labels->name;
		$this->singular_label = $post_type->labels->singular_name;
		$this->type           = $type;

		// Check if the post type uses a dashicon...
		if ( 'database' === $this->icon ) {
			if ( ! empty( $post_type->menu_icon ) && false !== strpos( $post_type->menu_icon, 'dashicons' ) ) {
				$this->icon = str_replace( 'dashicons-', '', $post_type->menu_icon );
			} else {
				$this->icon = 'admin-post';
			}
		}

		// Remove unsupported fields.
		if ( $post_type && $init ) {

			if ( ! post_type_supports( $this->type, 'title' ) ) {
				$this->title_field = '';
			}

			if ( ! post_type_supports( $this->type, 'editor' ) ) {
				$this->description_field = '';
			}

			if ( ! post_type_supports( $this->type, 'thumbnail' ) ) {
				$this->image_field = '';
			}

			if ( ! is_post_type_viewable( $this->type ) ) {
				$this->url_field = '';
			}
		}

		parent::__construct();
	}

	/**
	 * Retrieves available filters.
	 *
	 * @return array
	 */
	public function get_filters() {

		return array_merge(
			$this->generate_date_filters(),
			$this->generate_taxonomy_filters( $this->type ),
			array(
				'author'        => array(
					'label'       => __( 'Author', 'newsletter-optin-box' ),
					'el'          => 'input',
					'type'        => 'text',
					'description' => __( 'Author ID, or comma-separated list of IDs', 'newsletter-optin-box' ),
					'placeholder' => 'For example, 1, 3, 4, -5',
				),
				'comment_count' => array(
					'label'       => __( 'Comment Count', 'newsletter-optin-box' ),
					'el'          => 'input',
					'type'        => 'text',
					'description' => __( 'Number of comments, with an optional comparison operator.', 'newsletter-optin-box' ),
					'placeholder' => 'For example, >= 5',
				),
				's'             => array(
					'label'       => __( 'Search by keyword', 'newsletter-optin-box' ),
					'el'          => 'input',
					'type'        => 'text',
					'description' => __( 'Prepending a term with a hyphen will exclude posts matching that term.', 'newsletter-optin-box' ),
					'placeholder' => __( 'For example, pillow -sofa', 'newsletter-optin-box' ),
				),
			)
		);
	}

	/**
	 * Retrieves matching posts.
	 *
	 * @param array $filters The available filters.
	 * @return int[] $users The user IDs.
	 */
	public function get_all( $filters ) {

		$filters = array_merge(
			array(
				'post_type'           => $this->type,
				'number'              => 10,
				'order'               => 'DESC',
				'orderby'             => 'date',
				'fields'              => 'ids',
				'ignore_sticky_posts' => true,
			),
			$filters
		);

		// Convert number to numberposts.
		if ( isset( $filters['number'] ) ) {
			$filters['numberposts'] = $filters['number'];
			unset( $filters['number'] );
		}

		// Separate operator from comment count.
		if ( ! empty( $filters['comment_count'] ) && ! is_numeric( $filters['comment_count'] ) ) {
			// Split first non-numeric characters.
			if ( preg_match( '/^([^0-9]*)(.*)$/', trim( $filters['comment_count'] ), $matches ) ) {
				$filters['comment_count'] = array(
					'compare' => trim( $matches[1] ),
					'value'   => trim( $matches[2] ),
				);
			} else {
				unset( $filters['comment_count'] );
			}
		}

		// Prepare tax query values.
		$filters = $this->prepare_tax_query_filter( $filters );

		// If date query is specified, ensure it is enabled.
		$filters = $this->prepare_date_query_filter( $filters );

		return get_posts( array_filter( $filters ) );
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {

		$is_visible = is_post_type_viewable( $this->type );
		$fields     = array(
			'id'             => array(
				'label' => __( 'ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'parent'         => array(
				'label' => __( 'Parent ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'author'         => array(
				'label' => __( 'Author ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'date'           => array(
				'label' => __( 'Date', 'newsletter-optin-box' ),
				'type'  => 'date',
			),
			'title'          => array(
				'label' => __( 'Title', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s Title', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'Displays the %s title.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'icon'        => 'heading',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'element'     => 'heading',
					'linksTo'     => $is_visible ? $this->field_to_merge_tag( 'url' ) : null,
				),
			),
			'excerpt'        => array(
				'label' => __( 'Excerpt', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Excerpt', 'newsletter-optin-box' ),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'Displays the %s excerpt.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'icon'        => 'editor-alignleft',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
				),
			),
			'content'        => array(
				'label' => __( 'Content', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'status'         => array(
				'label' => __( 'Status', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'comment_status' => array(
				'label'   => __( 'Comment Status', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => array(
					'open'   => __( 'Open', 'newsletter-optin-box' ),
					'closed' => __( 'Closed', 'newsletter-optin-box' ),
				),
			),
			'url'            => array(
				'label' => __( 'URL', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Read More', 'newsletter-optin-box' ),
					'description' => sprintf(
						/* translators: %s: Object type label. */
						__( 'Displays a button link to the %s.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label )
					),
					'icon'        => 'welcome-view-site',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'defaults'    => array(
						'text' => __( 'Read More', 'newsletter-optin-box' ),
						'url'  => $this->field_to_merge_tag( 'url' ),
					),
					'element'     => 'button',
				),
			),
			'ping_status'    => array(
				'label'   => __( 'Ping Status', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => array(
					'open'   => __( 'Open', 'newsletter-optin-box' ),
					'closed' => __( 'Closed', 'newsletter-optin-box' ),
				),
			),
			'slug'           => array(
				'label' => __( 'Slug', 'newsletter-optin-box' ),
				'type'  => 'string',
			),
			'comment_count'  => array(
				'label' => __( 'Comment Count', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'featured_image' => array(
				'label' => __( 'Featured Image URL', 'newsletter-optin-box' ),
				'type'  => 'string',
				'block' => array(
					'title'       => __( 'Featured Image', 'newsletter-optin-box' ),
					'description' => __( 'Displays the featured image.', 'newsletter-optin-box' ),
					'icon'        => 'camera',
					'metadata'    => array(
						'ancestor' => array( $this->context ),
					),
					'defaults'    => array(
						'alt'  => $this->field_to_merge_tag( 'title' ),
						'href' => $is_visible ? $this->field_to_merge_tag( 'url' ) : '',
					),
					'element'     => 'image',
					'settings'    => array(
						'size' => array(
							'label'       => __( 'Resolution', 'newsletter-optin-box' ),
							'el'          => 'image_size_select',
							'description' => __( 'Select the image size to display.', 'newsletter-optin-box' ),
							'placeholder' => __( 'Select image size', 'newsletter-optin-box' ),
							'default'     => 'large',
						),
					),
				),
			),
			'meta'           => $this->meta_key_tag_config(),
		);

		$taxonomies = wp_list_pluck(
			wp_list_filter(
				get_object_taxonomies( $this->type, 'objects' ),
				array(
					'public' => true,
				)
			),
			'label',
			'name'
		);

		foreach ( $taxonomies as $taxonomy => $label ) {
			$icon = 'marker';

			// Check if taxonomy contains the word category.
			if ( false !== strpos( $taxonomy, 'category' ) ) {
				$icon = 'category';
			}

			// Check if taxonomy contains the word tag.
			if ( false !== strpos( $taxonomy, 'tag' ) ) {
				$icon = 'tag';
			}

			$fields[ 'tax_' . $taxonomy ] = $this->taxonomy_tag_config(
				$label,
				sprintf(
					/* translators: %s: Object type label. */
					__( 'Displays the %1$s %2$s.', 'newsletter-optin-box' ),
					strtolower( $this->singular_label ),
					strtolower( $label )
				),
				$icon
			);
		}

		// Remove unsupported fields.
		if ( 'title' !== $this->title_field ) {
			unset( $fields['title'] );
		}

		if ( 'excerpt' !== $this->description_field ) {
			unset( $fields['content'] );
			unset( $fields['excerpt'] );
		}

		if ( ! post_type_supports( $this->type, 'author' ) ) {
			unset( $fields['author'] );
		}

		if ( ! post_type_supports( $this->type, 'comments' ) ) {
			unset( $fields['comment_status'] );
			unset( $fields['comment_count'] );
		}

		if ( ! post_type_supports( $this->type, 'trackbacks' ) ) {
			unset( $fields['ping_status'] );
		}

		if ( 'featured_image' !== $this->image_field ) {
			unset( $fields['featured_image'] );
		}

		if ( 'url' !== $this->url_field ) {
			unset( $fields['url'] );
		}

		return apply_filters( 'noptin_post_type_known_custom_fields', $fields, $this->type );
	}

	/**
	 * Adds generic post types.
	 *
	 */
	public static function register() {
		do_action( 'noptin_register_post_type_objects' );

		$args = array(
			'public'  => true,
			'show_ui' => true,
		);

		foreach ( get_post_types( $args ) as $type ) {
			if ( ! Store::exists( $type ) && 'attachment' !== $type ) {
				Store::add( new Generic_Post_Type( $type, true ) );
			}
		}
	}
}
