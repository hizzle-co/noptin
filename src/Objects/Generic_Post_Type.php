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
	public function __construct( $type ) {
		$post_type = get_post_type_object( $type );

		if ( ! $post_type ) {
			_doing_it_wrong( __METHOD__, sprintf( 'Post type %s does not exist.', esc_html( $type ) ), esc_html( noptin()->version ) );
		}

		$this->label          = $post_type->labels->name;
		$this->singular_label = $post_type->labels->singular_name;
		$this->type           = $type;

		// Check if the post type uses a dashicon...
		if ( ! empty( $post_type->menu_icon ) && false !== strpos( $post_type->menu_icon, 'dashicons' ) ) {
			$this->icon = str_replace( 'dashicons-', '', $post_type->menu_icon );
		} else {
			$this->icon = 'admin-post';
		}

		parent::__construct();
	}

	/**
	 * Retrieves available filters.
	 *
	 * @return array
	 */
	public function get_filters() {

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

		$label   = noptin_implode_and( $taxonomies );
		$options = array(
			'author'        => array(
				'label'       => __( 'Author', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'text',
				'description' => __( 'Author ID, or comma-separated list of IDs', 'newsletter-optin-box' ),
				'placeholder' => 'For example, 1, 3, 4, 5',
			),
			'comment_count' => array(
				'label'       => __( 'Comment Count', 'newsletter-optin-box' ),
				'el'          => 'input',
				'type'        => 'text',
				'description' => __( 'Number of comments, with an optional comparison operator.', 'newsletter-optin-box' ),
				'placeholder' => 'For example, >= 5',
			),
			'tax_query'     => array(
				'el'               => 'query_repeater',
				'label'            => $label,
				'customAttributes' => array(
					'fields'    => array(
						'taxonomy'         => array(
							'label'       => __( 'Taxonomy', 'newsletter-optin-box' ),
							'el'          => 'select',
							'options'     => $taxonomies,
							'description' => __( 'Taxonomy to filter by.', 'newsletter-optin-box' ),
							'placeholder' => __( 'Select taxonomy', 'newsletter-optin-box' ),
						),
						'field'            => array(
							'label'       => __( 'Field', 'newsletter-optin-box' ),
							'el'          => 'select',
							'options'     => array(
								'term_id' => __( 'Term ID', 'newsletter-optin-box' ),
								'name'    => __( 'Name', 'newsletter-optin-box' ),
								'slug'    => __( 'Slug', 'newsletter-optin-box' ),
							),
							'description' => __( 'Select term field to filter by.', 'newsletter-optin-box' ),
							'placeholder' => __( 'Select field', 'newsletter-optin-box' ),
							'default'     => 'name',
						),
						'terms'            => array(
							'label'       => __( 'Terms', 'newsletter-optin-box' ),
							'el'          => 'form_token',
							'description' => __( 'Use comma-separated list of terms formatted according to the field option.', 'newsletter-optin-box' ),
						),
						'operator'         => array(
							'label'       => __( 'Operator', 'newsletter-optin-box' ),
							'el'          => 'select',
							'options'     => array(
								'IN'         => __( 'IN', 'newsletter-optin-box' ),
								'NOT IN'     => __( 'NOT IN', 'newsletter-optin-box' ),
								'AND'        => __( 'AND', 'newsletter-optin-box' ),
								'EXISTS'     => __( 'EXISTS', 'newsletter-optin-box' ),
								'NOT EXISTS' => __( 'NOT EXISTS', 'newsletter-optin-box' ),
							),
							'description' => __( 'Operator to compare terms.', 'newsletter-optin-box' ),
							'placeholder' => __( 'Select operator', 'newsletter-optin-box' ),
							'default'     => 'IN',
						),
						'include_children' => array(
							'label'       => __( 'Include Children', 'newsletter-optin-box' ),
							'el'          => 'input',
							'type'        => 'checkbox',
							'description' => __( 'Whether or not to include children for hierarchical taxonomies.', 'newsletter-optin-box' ),
							'default'     => true,
						),
					),
					'openModal' => sprintf(
						/* translators: %s: taxonomies. */
						__( 'Filter by %s', 'newsletter-optin-box' ),
						$label
					),
				),
				'default'          => array(
					'relation' => 'AND',
					'items'    => array(),
				),
			),
			'date_query'    => array(
				'el'               => 'query_repeater',
				'label'            => __( 'Date', 'newsletter-optin-box' ),
				'customAttributes' => array(
					'disable'   => sprintf(
						/* translators: %s: Object type label. */
						__( 'Only show %s published since last send', 'newsletter-optin-box' ),
						$this->label
					),
					'fields'    => array(
						'after'     => array(
							'label'       => __( 'After', 'newsletter-optin-box' ),
							'el'          => 'input',
							'type'        => 'text',
							'placeholder' => sprintf(
								/* translators: %s: Examples. */
								__( 'Examples: %s', 'newsletter-optin-box' ),
								implode(
									', ',
									array(
										gmdate( 'Y-m-d' ),
										'-7 days',
										'1 year ago',
									)
								)
							),
							'description' => sprintf(
								/* translators: %s: Object type label. */
								__( 'Optional. Show %s published after this date.', 'newsletter-optin-box' ),
								$this->label
							),
						),
						'before'    => array(
							'label'       => __( 'Before', 'newsletter-optin-box' ),
							'el'          => 'input',
							'type'        => 'text',
							'placeholder' => sprintf(
								/* translators: %s: Examples. */
								__( 'Examples: %s', 'newsletter-optin-box' ),
								implode(
									', ',
									array(
										gmdate( 'Y-m-d' ),
										'-7 days',
										'1 year ago',
									)
								)
							),
							'description' => sprintf(
								/* translators: %s: Object type label. */
								__( 'Optional. Show %s published before this date.', 'newsletter-optin-box' ),
								$this->label
							),
						),
						'inclusive' => array(
							'label'       => __( 'Inclusive', 'newsletter-optin-box' ),
							'el'          => 'input',
							'type'        => 'checkbox',
							'description' => __( "Include results from dates specified in 'before' or 'after'.", 'newsletter-optin-box' ),
							'default'     => false,
						),
					),
					'openModal' => __( 'Filter by date', 'newsletter-optin-box' ),
				),
				'default'          => array(
					'relation' => 'OR',
					'disabled' => true,
					'items'    => array(),
				),
			),
			'meta_query'    => array(
				'el'               => 'query_repeater',
				'label'            => __( 'Custom fields', 'newsletter-optin-box' ),
				'customAttributes' => array(
					'fields'    => array(
						'key'     => array(
							'label'       => __( 'Meta key', 'newsletter-optin-box' ),
							'el'          => 'input',
							'type'        => 'text',
							'description' => __( 'Custom field key to filter by.', 'newsletter-optin-box' ),
						),
						'compare' => array(
							'label'       => __( 'Compare', 'newsletter-optin-box' ),
							'el'          => 'select',
							'options'     => array(
								'='           => '=',
								'!='          => '!=',
								'>'           => '>',
								'>='          => '>=',
								'<'           => '<',
								'<='          => '<=',
								'LIKE'        => 'LIKE',
								'NOT LIKE'    => 'NOT LIKE',
								'IN'          => 'IN',
								'NOT IN'      => 'NOT IN',
								'BETWEEN'     => 'BETWEEN',
								'NOT BETWEEN' => 'NOT BETWEEN',
								'EXISTS'      => 'EXISTS',
								'NOT EXISTS'  => 'NOT EXISTS',
								'REGEXP'      => 'REGEXP',
								'NOT REGEXP'  => 'NOT REGEXP',
								'RLIKE'       => 'RLIKE',
							),
							'description' => __( 'Operator to test', 'newsletter-optin-box' ),
							'placeholder' => __( 'Select operator', 'newsletter-optin-box' ),
							'default'     => '=',
						),
						'value'   => array(
							'label'       => __( 'Custom field value', 'newsletter-optin-box' ),
							'el'          => 'select',
							'description' => __( "Separate multiple values with a comma when compare is 'IN', 'NOT IN', 'BETWEEN', or 'NOT BETWEEN'.", 'newsletter-optin-box' ),
							'conditions'  => array(
								array(
									'key'      => 'compare',
									'operator' => '!includes',
									'value'    => array( 'EXISTS', 'NOT EXISTS' ),
								),
							),
						),
						'type'    => array(
							'label'   => __( 'Custom field type', 'newsletter-optin-box' ),
							'el'      => 'select',
							'options' => array(
								'NUMERIC'  => __( 'Numeric', 'newsletter-optin-box' ),
								'BINARY'   => __( 'Binary', 'newsletter-optin-box' ),
								'CHAR'     => __( 'String', 'newsletter-optin-box' ),
								'DATE'     => __( 'Date', 'newsletter-optin-box' ),
								'DATETIME' => __( 'Date and time', 'newsletter-optin-box' ),
								'DECIMAL'  => __( 'Decimal', 'newsletter-optin-box' ),
								'SIGNED'   => __( 'Signed', 'newsletter-optin-box' ),
								'TIME'     => __( 'Time', 'newsletter-optin-box' ),
								'UNSIGNED' => __( 'Unsigned', 'newsletter-optin-box' ),
							),
							'default' => 'CHAR',
						),
					),
					'openModal' => __( 'Filter by custom fields', 'newsletter-optin-box' ),
				),
				'default'          => array(
					'relation' => 'AND',
					'items'    => array(),
				),
			),
		);

		// Remove tax query if no taxonomies.
		if ( empty( $taxonomies ) ) {
			unset( $options['tax_query'] );
		}

		return $options;
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
				'post_type' => $this->type,
				'number'    => 10,
				'order'     => 'DESC',
				'orderby'   => 'date',
				'fields'    => 'ids',
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
		$filters = $this->prepare_query_filter( $filters, 'tax_query' );

		// Prepare meta query values.
		$filters = $this->prepare_query_filter( $filters, 'meta_query' );
		if ( ! empty( $filters['meta_query'] ) ) {
			$prepared = array(
				'relation' => 'AND',
			);

			foreach ( $filters['meta_query'] as $index => $meta ) {

				if ( 'relation' === $index ) {
					$prepared['relation'] = $meta;
					continue;
				}

				// Abort if not an array.
				if ( ! is_array( $meta ) ) {
					continue;
				}

				// Ensure value is array if compare is 'IN', 'NOT IN', 'BETWEEN', or 'NOT BETWEEN'.
				if ( isset( $meta['compare'] ) && isset( $meta['value'] ) && in_array( $meta['compare'], array( 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' ), true ) ) {
					$meta['value'] = noptin_parse_list( $meta['value'], true );
				}

				$prepared[] = $meta;
			}

			$filters['meta_query'] = $prepared;
		}

		// If date query is specified, ensure it is enabled.
		$filters = $this->prepare_query_filter( $filters, 'date_query' );

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
						'alt'  => $this->field_to_merge_tag( 'name' ),
						'href' => $is_visible ? $this->field_to_merge_tag( 'url' ) : '',
					),
					'element'     => 'image',
					'settings'    => array(
						'size' => array(
							'label'       => __( 'Image Size', 'newsletter-optin-box' ),
							'el'          => 'image_size_select',
							'description' => __( 'Select the image size to display.', 'newsletter-optin-box' ),
							'placeholder' => __( 'Select image size', 'newsletter-optin-box' ),
							'default'     => 'thumbnail',
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
			$fields[ 'tax_' . $taxonomy ] = array(
				'label' => $label,
				'type'  => 'string',
			);
		}

		// Remove unsupported fields.
		if ( ! post_type_supports( $this->type, 'title' ) ) {
			unset( $fields['title'] );
		}

		if ( ! post_type_supports( $this->type, 'editor' ) ) {
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

		if ( ! post_type_supports( $this->type, 'thumbnail' ) ) {
			unset( $fields['featured_image'] );
		}

		if ( ! $is_visible ) {
			unset( $fields['url'] );
		}

		return apply_filters( 'noptin_post_type_known_custom_fields', $fields, $this->type );
	}

	/**
	 * Returns the template for the list shortcode.
	 */
	protected function get_list_shortcode_template() {
		$template = array();

		if ( post_type_supports( $this->type, 'title' ) ) {
			$template['heading'] = \Hizzle\Noptin\Emails\Admin\Editor::merge_tag_to_block_name( $this->field_to_merge_tag( 'title' ) );
		}

		if ( post_type_supports( $this->type, 'editor' ) ) {
			$template['description'] = $this->field_to_merge_tag( 'excerpt' );
		}

		if ( post_type_supports( $this->type, 'thumbnail' ) ) {
			$template['image'] = \Hizzle\Noptin\Emails\Admin\Editor::merge_tag_to_block_name( $this->field_to_merge_tag( 'featured_image' ) );
		}

		if ( ! is_post_type_viewable( $this->type ) ) {
			$template['button'] = \Hizzle\Noptin\Emails\Admin\Editor::merge_tag_to_block_name( $this->field_to_merge_tag( 'url' ) );
		}
		return $template;
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
				Store::add( new Generic_Post_Type( $type ) );
			}
		}
	}
}
