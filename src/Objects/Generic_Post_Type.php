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

		if ( is_null( $post_type ) ) {
			_doing_it_wrong( __METHOD__, sprintf( 'Post type %s does not exist.', esc_html( $type ) ), esc_html( noptin()->version ) );
		} else {
			$this->label          = $post_type->labels->name;
			$this->singular_label = $post_type->labels->singular_name;
		}

		$this->type = $type;

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

		if ( $post_type && post_type_supports( $this->type, 'author' ) ) {
			$this->provides[] = 'post_author';
		}
	}

	/**
	 * Retrieves available filters.
	 *
	 * @return array
	 */
	public function get_filters() {

		$filters = array_merge(
			$this->generate_date_filters(),
			$this->generate_taxonomy_filters( $this->type, $this->taxonomies() ),
			array(
				'lang'          => array(
					'label'                => __( 'Language', 'newsletter-optin-box' ),
					'el'                   => 'select',
					'description'          => __( 'The language to filter posts by.', 'newsletter-optin-box' ),
					'options'              => noptin_get_available_languages(),
					'canSelectPlaceholder' => true,
					'placeholder'          => __( 'Any', 'newsletter-optin-box' ),
				),
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

		if ( ! noptin_is_multilingual() ) {
			unset( $filters['lang'] );
		}

		return apply_filters( 'noptin_post_type_filters', $filters, $this->type );
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
				'post_type'        => $this->type,
				'number'           => 10,
				'order'            => 'DESC',
				'orderby'          => 'date',
				'fields'           => 'ids',
				'suppress_filters' => false,
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

		if ( ! empty( $filters['lang'] ) ) {
			$code = noptin_convert_language_locale_to_slug( $filters['lang'] );
			$filters['lang'] = empty( $code ) ? substr( $filters['lang'], 0, 2 ) : $code;

			if ( ! empty( $filters['lang'] ) ) {
				$filters['suppress_filters'] = false;
			}
		}

		$filters = apply_filters( 'noptin_post_type_get_all_filters', $filters, $this->type );
		$filters = array_filter( $filters, function( $value ) {
			return $value !== '' && $value !== null;
		} );

		do_action( 'noptin_post_type_get_all_before_query', $filters, $this->type );
		$posts = get_posts( $filters );
		do_action( 'noptin_post_type_get_all_after_query', $posts, $this->type, $filters );

		// Debug the query later.
		if ( defined( 'NOPTIN_IS_TESTING' ) && NOPTIN_IS_TESTING && ! empty( $GLOBALS['wpdb']->last_query ) ) {
			noptin_error_log( $filters, 'Post collection args' );
			noptin_error_log( $GLOBALS['wpdb']->last_query, 'Post collection query' );
			noptin_error_log( count( $posts ), 'Post collection posts' );
		}

		return $posts;
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	public function get_fields() {

		$action     = 'create_or_update_' . $this->type;
		$is_visible = is_post_type_viewable( $this->type );
		$fields     = array(
			'id'             => array(
				'label'        => __( 'ID', 'newsletter-optin-box' ),
				'type'         => 'number',
				'actions'      => array( $action, 'delete_' . $this->type ),
				'action_props' => array(
					$action                 => array(
						'label'        => __( 'Post ID', 'newsletter-optin-box' ),
						'description'  => __( 'Leave blank to create a new post.', 'newsletter-optin-box' ),
						'show_in_meta' => true,
					),
					'delete_' . $this->type => array(
						'label'       => __( 'Post ID or Slug', 'newsletter-optin-box' ),
						'description' => __( 'Specify a post ID or slug', 'newsletter-optin-box' ),
						'required'    => true,
					),
				),
			),
			'parent'         => array(
				'label' => __( 'Parent ID', 'newsletter-optin-box' ),
				'type'  => 'number',
			),
			'author'         => array(
				'label'        => __( 'Author ID', 'newsletter-optin-box' ),
				'type'         => 'number',
				'deprecated'   => 'post_author_id',
				'actions'      => array( $action ),
				'action_props' => array(
					$action => array(
						'label'        => __( 'Author ID or email address', 'newsletter-optin-box' ),
						'description'  => __( 'If an email address is provided, the user will be created if they do not already exist.', 'newsletter-optin-box' ),
						'show_in_meta' => true,
					),
				),
			),
			'date'           => array(
				'label' => __( 'Date', 'newsletter-optin-box' ),
				'type'  => 'date',
			),
			'title'          => array(
				'label'        => __( 'Title', 'newsletter-optin-box' ),
				'type'         => 'string',
				'actions'      => array( $action ),
				'show_in_meta' => true,
				'block'        => array(
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
				'label'   => __( 'Excerpt', 'newsletter-optin-box' ),
				'type'    => 'string',
				'actions' => array( $action ),
				'block'   => array(
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
					'settings'    => array(
						'words' => array(
							'label'       => __( 'Words', 'newsletter-optin-box' ),
							'el'          => 'input',
							'type'        => 'number',
							'description' => __( 'The maximum number of words to display.', 'newsletter-optin-box' ),
							'placeholder' => '20',
							'default'     => 20,
						),
					),
				),
			),
			'content'        => array(
				'label'   => __( 'Content', 'newsletter-optin-box' ),
				'type'    => 'string',
				'actions' => array( $action ),
			),
			'status'         => array(
				'label'   => __( 'Status', 'newsletter-optin-box' ),
				'type'    => 'string',
				'actions' => array( $action ),
				'options' => array_merge(
					get_post_statuses(),
					array(
						'future' => __( 'Scheduled', 'newsletter-optin-box' ),
					)
				),
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
				'label'        => __( 'Slug', 'newsletter-optin-box' ),
				'type'         => 'string',
				'actions'      => array( $action ),
				'action_props' => array(
					$action => array(
						'description' => __( 'If provided and a post with the same slug exists, the post will be updated.', 'newsletter-optin-box' ),
					),
				),
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
						'fallback' => array(
							'label'       => __( 'Fallback', 'newsletter-optin-box' ),
							'el'          => 'input',
							'type'        => 'text',
							'description' => __( 'The fallback image URL to use for posts without a featured image.', 'newsletter-optin-box' ),
							'placeholder' => 'https://images.pexels.com/photos/2238318/pexels-photo-2238318.jpeg?auto=compress&cs=tinysrgb&w=1200',
						),
					),
				),
			),
			'language'       => array(
				'label'   => __( 'Language (Locale)', 'newsletter-optin-box' ),
				'type'    => 'string',
				'options' => noptin_get_available_languages(),
			),
			'meta'           => $this->meta_key_tag_config(),
		);

		foreach ( $fields as $key => $args ) {
			if ( empty( $args['deprecated'] ) ) {
				$fields[ $key ]['deprecated'] = 'post_' . $key;
			}
		}

		foreach ( $this->taxonomies() as $taxonomy => $label ) {
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

			if ( ! Generic_Post::is_taxonomy_linkable( $taxonomy ) ) {
				unset( $fields[ 'tax_' . $taxonomy ]['block']['settings']['link'] );
			}

			$fields[ 'tax_' . $taxonomy ]['actions']      = array( $action );
			$fields[ 'tax_' . $taxonomy ]['action_props'] = array(
				$action => array(
					'description'  => sprintf(
						/* translators: %s: Object type label. */
						__( 'Enter a comma-separated list of %1$s %2$s.', 'newsletter-optin-box' ),
						strtolower( $this->singular_label ),
						false === strpos( strtolower( $label ), strtolower( $this->singular_label ) . ' ' ) ? strtolower( $label ) : str_replace( strtolower( $this->singular_label ) . ' ', '', strtolower( $label ) )
					),
					'show_in_meta' => true,
				),
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

		$exclude = apply_filters( 'noptin_post_type_exclude', array( 'attachment', 'elementor_library', 'e-floating-buttons' ) );

		foreach ( get_post_types( $args ) as $type ) {
			$register = apply_filters( 'noptin_register_post_type_object_' . $type, true );
			if ( $register && ! Store::exists( $type ) && ! in_array( $type, $exclude, true ) ) {
				Store::add( new Generic_Post_Type( $type, true ) );
			}
		}
	}

	/**
	 * Returns the template for the list shortcode.
	 */
	protected function get_list_shortcode_template() {
		$template = parent::get_list_shortcode_template();

		if ( 'date' === $this->meta_field ) {
			$template['meta'] = sprintf(
				// translators: %s: Date and time.
				__( '%1$s at %2$s', 'newsletter-optin-box' ),
				$this->field_to_merge_tag( 'date', 'format="date"' ),
				$this->field_to_merge_tag( 'date', 'format="time"' )
			);
		}

		return $template;
	}

	/**
	 * Returns a list of available (actions).
	 *
	 * @return array $actions The actions.
	 */
	public function get_actions() {
		$is_vowel = in_array( strtolower( $this->singular_label[0] ), array( 'a', 'e', 'i', 'o', 'u' ), true );
		return array_merge(
			parent::get_actions(),
			array(
				'create_or_update_' . $this->type => array(
					'id'          => 'create_or_update_' . $this->type,
					'label'       => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Create or Update', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description' => $is_vowel ?
						sprintf(
							/* translators: %s: Object type label. */
							__( 'Create or update an %s', 'newsletter-optin-box' ),
							strtolower( $this->singular_label )
						) :
						sprintf(
							/* translators: %s: Object type label. */
							__( 'Create or update a %s', 'newsletter-optin-box' ),
							strtolower( $this->singular_label )
						),
					'callback'    => array( $this, 'create_post' ),
				),
			),
			array(
				'delete_' . $this->type => array(
					'id'             => 'delete_' . $this->type,
					'label'          => sprintf(
						/* translators: %s: Object type label. */
						__( '%s > Delete', 'newsletter-optin-box' ),
						$this->singular_label
					),
					'description'    => $is_vowel ?
						sprintf(
							/* translators: %s: Object type label. */
							__( 'Delete an %s', 'newsletter-optin-box' ),
							strtolower( $this->singular_label )
						) :
						sprintf(
							/* translators: %s: Object type label. */
							__( 'Delete a %s', 'newsletter-optin-box' ),
							strtolower( $this->singular_label )
						),
					'callback'       => array( $this, 'delete_post' ),
					'extra_settings' => array(
						'force_delete' => array(
							'label'       => __( 'Force Delete', 'newsletter-optin-box' ),
							'description' => __( 'Whether to bypass the trash and force delete the post.', 'newsletter-optin-box' ),
							'el'          => 'input',
							'type'        => 'checkbox',
							'default'     => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Fetches post args.
	 *
	 * @param array $args
	 */
	protected function prepare_create_post_args( $args ) {
		$post_info = array(
			'post_type'  => $this->type,
			'meta_input' => array(),
			'tax_input'  => array(),
		);

		foreach ( $args as $key => $value ) {
			if ( 'id' === $key ) {
				if ( is_numeric( $value ) ) {
					$post_info['ID'] = $value;
				}

				$existing = get_page_by_path( sanitize_title( $value ), OBJECT, $this->type );

				if ( $existing ) {
					$post_info['ID'] = $existing->ID;
				}
				continue;
			}

			if ( 'slug' === $key ) {
				$value    = sanitize_title( $value );
				$existing = get_page_by_path( $value, OBJECT, $this->type );

				if ( $existing ) {
					$post_info['ID'] = $existing->ID;
				} else {
					$post_info['post_name'] = $value;
				}

				continue;
			}

			if ( 'author' === $key ) {
				if ( is_email( $value ) ) {
					$user = Users::get_or_create_from_email( $value );

					if ( is_wp_error( $user ) ) {
						return $user;
					}

					$post_info['post_author'] = $user;
				} else {
					$post_info['post_author'] = $value;
				}

				continue;
			}

			if ( in_array( $key, array( 'title', 'excerpt', 'content', 'status', 'parent', 'date' ), true ) ) {
				$post_info[ "post_$key" ] = $value;
				continue;
			}

			// Handle taxonomies.
			if ( 0 === strpos( $key, 'tax_' ) ) {
				$taxonomy = substr( $key, 4 );
				$terms    = noptin_parse_list( $value, true );

				if ( 'post_tag' === $taxonomy ) {
					$post_info['tags_input'] = $terms;
					continue;
				}

				// If terms are not ids, convert to ids.
				$prepared = array();

				foreach ( $terms as $term ) {
					if ( is_numeric( $term ) ) {
						$prepared[] = (int) $term;
					} else {
						$term = get_term_by( 'name', sanitize_text_field( $term ), $taxonomy );

						if ( $term ) {
							$prepared[] = (int) $term->term_id;
						} else {
							$term = get_term_by( 'slug', sanitize_title( $term ), $taxonomy );

							if ( $term ) {
								$prepared[] = (int) $term->term_id;
							}
						}
					}
				}

				if ( ! empty( $prepared ) ) {
					if ( 'category' === $taxonomy ) {
						$post_info['post_category'] = $prepared;
					} else {
						$post_info['tax_input'][ $taxonomy ] = array_unique( array_filter( $prepared ) );
					}
				}

				continue;
			}

			// Handle custom fields.
			$post_info['meta_input'][ $key ] = $value;
		}

		$post_info = apply_filters( 'noptin_post_prepare_create_post_args', $post_info, $args );

		if ( empty( $post_info['meta_input'] ) ) {
			unset( $post_info['meta_input'] );
		}

		return $post_info;
	}

	/**
	 * Creates or updates a post.
	 *
	 * @param array $args
	 */
	public function create_post( $args ) {

		$post_info = wp_slash( $this->prepare_create_post_args( $args ) );
		if ( ! empty( $post_info['ID'] ) ) {
			$post = wp_update_post( $post_info, true );
		} else {
			$post = wp_insert_post( $post_info, true );
		}

		if ( ! is_wp_error( $post ) ) {
			if ( ! empty( $post_info['tax_input'] ) ) {
				foreach ( $post_info['tax_input'] as $taxonomy => $tags ) {

					$taxonomy_obj = get_taxonomy( $taxonomy );

					if ( $taxonomy_obj ) {

						if ( empty( $tags ) && ! empty( $tax_object->default_term ) ) {
							$default_term_id = get_option( 'default_term_' . $taxonomy );
							if ( ! empty( $default_term_id ) ) {
								$tags = array( (int) $default_term_id );
							}
						}

						if ( ! empty( $tags ) ) {
							wp_set_post_terms( $post, $tags, $taxonomy );
						}
					}
				}
			}

			if ( empty( $post_info['ID'] ) ) {
				$this->after_create_post( get_post( $post ) );
			}
		}

		return $post;
	}

	/**
	 * Called after a post is created.
	 *
	 * @param \WP_Post $post
	 */
	public function after_create_post( $post ) {}

	/**
	 * Deletes a post.
	 *
	 * @param array $args
	 */
	public function delete_post( $args ) {

		if ( empty( $args['id'] ) ) {
			return new \WP_Error( 'noptin_invalid_post_id', 'Post ID is required.' );
		}

		if ( is_numeric( $args['id'] ) ) {
			$post = get_post( $args['id'] );
		} else {
			$post = get_page_by_path( $args['id'], OBJECT, $this->type );
		}

		if ( ! $post ) {
			return new \WP_Error( 'noptin_post_not_found', 'Post not found.' );
		}

		return wp_delete_post( $post->ID, ! empty( $args['force_delete'] ) );
	}
}
