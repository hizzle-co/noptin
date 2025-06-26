<?php
// phpcs:ignoreFile

namespace AutomateWoo;

use Automattic\WooCommerce\Admin\Features\Features as WCAdminFeatures;

/**
 * @class Post_Types
 */
class Post_Types {


	static function init() {
		add_action( 'init', [ __CLASS__, 'register_post_types' ], 5 );
		add_action( 'init', [ __CLASS__, 'register_post_status' ] );

		add_filter( 'post_updated_messages', [ __CLASS__, 'register_post_updated_messages' ] );
		add_filter( 'bulk_post_updated_messages', [ __CLASS__, 'register_bulk_post_updated_messages' ], 10, 2 );
	}


	static function register_post_types() {
		$is_wc_admin_nav = class_exists( WCAdminFeatures::class ) & WCAdminFeatures::is_enabled( 'navigation' );

		register_post_type( 'aw_workflow',
			apply_filters( 'automatewoo_register_post_type_aw_workflow', [
					'labels'              => [
						'name'               => __( 'Workflows', 'automatewoo' ),
						'singular_name'      => __( 'Workflow', 'automatewoo' ),
						'menu_name'          => _x( 'Workflows', 'Admin menu name', 'automatewoo' ),
						'add_new'            => __( 'Add Workflow', 'automatewoo' ),
						'add_new_item'       => __( 'Add New Workflow', 'automatewoo' ),
						'edit'               => __( 'Edit', 'automatewoo' ),
						'edit_item'          => __( 'Edit Workflow', 'automatewoo' ),
						'new_item'           => __( 'New Workflow', 'automatewoo' ),
						'view'               => __( 'View Workflow', 'automatewoo' ),
						'view_item'          => __( 'View Workflow', 'automatewoo' ),
						'search_items'       => __( 'Search Workflows', 'automatewoo' ),
						'not_found'          => __( 'No Workflows found', 'automatewoo' ),
						'not_found_in_trash' => __( 'No Workflows found in trash', 'automatewoo' ),
					],
					'public' => false,
					'show_ui'             => true,
					'capability_type'     => 'shop_order',
					'map_meta_cap'        => true,
					'publicly_queryable'  => false,
					'exclude_from_search' => true,
					// Only enable show_in_menu when it's required for WC Admin nav to work
					'show_in_menu'        => $is_wc_admin_nav,
					'hierarchical'        => false,
					'show_in_nav_menus'   => false,
					'rewrite'             => false,
					'query_var'           => false,
					'supports'            => [ 'title', 'page-attributes' ],
					'has_archive'         => false,
				]
			)
		);

		do_action('automatewoo_after_register_post_types');
	}


	/**
	 *
	 */
	static function register_post_status() {
		register_post_status( 'aw-disabled', [
			'label' => __( 'Disabled', 'automatewoo' ),
			'public' => false,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label_count' => _n_noop( 'Disabled <span class="count">(%s)</span>', 'Disabled <span class="count">(%s)</span>', 'automatewoo' ),
		]);
	}

	/**
	 * Change messages when a post type is updated.
	 *
	 * @param  array $messages
	 * @return array
	 */
	static function register_post_updated_messages( $messages ) {
		$post = get_post();

		$messages['aw_workflow'] = [
			0 => '', // Unused. Messages start at index 1.
			1 => __( 'Workflow updated.', 'automatewoo' ),
			2 => __( 'Custom field updated.', 'automatewoo' ),
			3 => __( 'Custom field deleted.', 'automatewoo' ),
			4 => __( 'Workflow updated.', 'automatewoo' ),
			// translators: placeholder is previous workflow title
			5 => isset( $_GET['revision'] ) ? sprintf( _x( 'Workflow restored to revision from %s', 'used in workflow updated messages', 'automatewoo' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __( 'Workflow updated.', 'automatewoo' ),
			7 => __( 'Workflow saved.', 'automatewoo' ),
			8 => __( 'Workflow submitted.', 'automatewoo' ),
			// translators: php date string, see http://php.net/date
			9 => sprintf( __( 'Workflow scheduled for: %1$s.', 'automatewoo' ), '<strong>' . date_i18n( _x( 'M j, Y @ G:i', 'used in "Workflow scheduled for <date>"', 'automatewoo' ), strtotime( $post->post_date ) ) . '</strong>' ),
			10 => __( 'Workflow draft updated.', 'automatewoo' ),
		];

		return $messages;
	}

	/**
	 * Specify custom bulk actions messages for different post types.
	 *
	 * @param  array $bulk_messages Array of messages.
	 * @param  array $bulk_counts Array of how many objects were updated.
	 * @return array
	 */
	static public function register_bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {
		$bulk_messages['aw_workflow'] = [
			/* translators: %s: workflow count */
			'updated'   => _n( '%s workflow updated.', '%s workflows updated.', $bulk_counts['updated'], 'automatewoo' ),
			/* translators: %s: workflow count */
			'locked'    => _n( '%s workflow not updated, somebody is editing it.', '%s workflows not updated, somebody is editing them.', $bulk_counts['locked'], 'automatewoo' ),
			/* translators: %s: workflow count */
			'deleted'   => _n( '%s workflow permanently deleted.', '%s workflows permanently deleted.', $bulk_counts['deleted'], 'automatewoo' ),
			/* translators: %s: workflow count */
			'trashed'   => _n( '%s workflow moved to the Trash.', '%s workflows moved to the Trash.', $bulk_counts['trashed'], 'automatewoo' ),
			/* translators: %s: workflow count */
			'untrashed' => _n( '%s workflow restored from the Trash.', '%s workflows restored from the Trash.', $bulk_counts['untrashed'], 'automatewoo' ),
		];

		return $bulk_messages;
	}

}
