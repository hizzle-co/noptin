<?php

/**
 * Displays a list of all custom content
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Custom content table class.
 */
class Noptin_Custom_Content_Table extends WP_List_Table {

	/**
	 * Total Subscribers
	 *
	 * @var   int
	 * @since 1.1.2
	 */
	public $total;

	/**
	 *  Constructor function.
	 */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => 'id',
				'plural'   => 'ids',
			)
		);

		$this->items = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'objects'
		);
		$this->total = count( $this->items );

	}

	/**
	 * Displays the post type title.
	 *
	 * @param  WP_Post_Type $post_type post type.
	 * @return string
	 */
	public function column_title( $post_type ) {

		return sprintf(
			'<div class="row-title"><strong><a href="%s">%s</a></strong></div>',
			esc_url( admin_url( 'edit.php?post_type=' . $post_type->name ) ),
			esc_html( $post_type->label )
		);

	}

	/**
	 * Displays the post type slug.
	 *
	 * @param  WP_Post_Type $post_type post type.
	 * @return string
	 */
	public function column_slug( $post_type ) {
		return esc_html( $post_type->name );
	}

	/**
	 * Displays the post type taxonomies.
	 *
	 * @param  WP_Post_Type $post_type post type.
	 * @return string
	 */
	public function column_taxonomies( $post_type ) {

		$taxonomies = get_object_taxonomies( $post_type->name, 'objects' );
		$taxonomies = array_map(
			function( $taxonomy ) {
				return sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'edit-tags.php?taxonomy=' . $taxonomy->name ) ),
					esc_html( $taxonomy->label ) . ' (' . esc_html( $taxonomy->name ) . ')'
				);
			},
			$taxonomies
		);

		return '<ul><li>' . implode( '</li><li>', $taxonomies ) . '</li></ul>';
	}

	/**
	 * Whether the table has items to display or not
	 *
	 * @return bool
	 */
	public function has_items() {
		return ! empty( $this->total );
	}

	/**
	 * Fetch data from the database to render on view.
	 */
	public function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->set_pagination_args(
			array(
				'total_items' => $this->total,
				'per_page'    => $this->total,
				'total_pages' => 1,
			)
		);

	}

	/**
	 * Table columns.
	 *
	 * @return array
	 */
	public function get_columns() {

		return array(
			'title'      => __( 'Post Type', 'newsletter-optin-box' ),
			'slug'       => __( 'Slug', 'newsletter-optin-box' ),
			'taxonomies' => __( 'Taxonomies (slugs)', 'newsletter-optin-box' ),
		);
	}
}
