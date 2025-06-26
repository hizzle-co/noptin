<?php
/**
 * Forms API: Forms Admin Table.
 *
 * Contains the main admin list table class for Noptin forms.
 *
 * @since   2.3.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Forms\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles displaying the list of forms in the admin area.
 *
 * @since 1.1.1
 */
class List_Table {

	/**
	 * Class Constructor.
	 */
	public static function init() {

		// Add some actions.
		add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
		add_action( 'noptin_reset_form_stats', array( __CLASS__, 'reset_form_stats' ) );
		add_action( 'noptin_duplicate_form', array( __CLASS__, 'duplicate_form' ) );

		// Filter form columns.
		add_filter( 'manage_noptin-form_posts_columns', array( __CLASS__, 'manage_form_columns' ) );

		// Display columns.
		add_action( 'manage_noptin-form_posts_custom_column', array( __CLASS__, 'display_form_columns' ), 10, 2 );

		// Custom form filters.
		add_action( 'restrict_manage_posts', array( __CLASS__, 'custom_filters' ), 10 );

		// Apply our custom form filters.
		add_filter( 'parse_query', array( __CLASS__, 'apply_custom_filters' ), 10 );

		// Add custom sortable colums.
		add_filter( 'manage_edit-noptin-form_sortable_columns', array( __CLASS__, 'add_sortable_columns' ) );
	}

	/**
	 * Filters the array of row action links on the Posts list table.
	 *
	 * @param string[] $actions An array of row action links. Defaults are 'Edit', 'Quick Edit', 'Restore', 'Trash', 'Delete Permanently', 'Preview', and 'View'.
	 * @param \WP_Post  $post    The post object.
	 */
	public static function row_actions( $actions, $post ) {

		if ( 'noptin-form' === $post->post_type ) {

			$actions = array_merge(
				array(
					'_preview' => sprintf(
						'<a href="%s">%s</a>',
						esc_url(
							add_query_arg(
								array(
									'noptin_preview_form' => $post->ID,
								),
								site_url( '/', 'admin' )
							)
						),
						esc_html__( 'Preview', 'newsletter-optin-box' )
					),
					'reset' => sprintf(
						'<a href="%s">%s</a>',
						esc_url(
							wp_nonce_url(
								add_query_arg(
									array(
										'noptin_admin_action' => 'noptin_reset_form_stats',
										'form_id' => $post->ID,
									)
								),
								'noptin-reset-nonce',
								'noptin-reset-nonce'
							)
						),
						esc_html( __( 'Reset Stats', 'newsletter-optin-box' ) )
					),
					'duplicate' => sprintf(
						'<a href="%s">%s</a>',
						esc_url(
							wp_nonce_url(
								add_query_arg(
									array(
										'noptin_admin_action' => 'noptin_duplicate_form',
										'form_id' => $post->ID,
									)
								),
								'noptin-duplicate-nonce',
								'noptin-duplicate-nonce'
							)
						),
						esc_html( __( 'Duplicate', 'newsletter-optin-box' ) )
					),
				),
				$actions
			);

		}

		return $actions;
	}

	/**
	 * Duplicates a form.
	 *
	 */
	public static function duplicate_form() {

		if ( empty( $_GET['form_id'] ) || empty( $_GET['noptin-duplicate-nonce'] ) || ! wp_verify_nonce( $_GET['noptin-duplicate-nonce'], 'noptin-duplicate-nonce' ) ) {
			return;
		}

		$form = new \Hizzle\Noptin\Forms\Form( $_GET['form_id'] );

		if ( $form->exists() ) {
			$form->duplicate();
			wp_safe_redirect( get_edit_post_link( $form->id, 'raw' ) );
			exit;
		}
	}

	/**
	 * Resets form stats.
	 *
	 */
	public static function reset_form_stats() {

		if ( empty( $_GET['form_id'] ) || empty( $_GET['noptin-reset-nonce'] ) || ! wp_verify_nonce( $_GET['noptin-reset-nonce'], 'noptin-reset-nonce' ) ) {
			return;
		}

		update_post_meta( $_GET['form_id'], '_noptin_form_views', 0 );
		update_post_meta( $_GET['form_id'], '_noptin_subscribers_count', 0 );

		noptin()->admin->show_success( __( 'Form stats reset successfully', 'newsletter-optin-box' ) );

		wp_safe_redirect( remove_query_arg( array( 'noptin_admin_action', 'form_id', 'noptin-reset-nonce' ) ) );
		exit;
	}

	/**
	 * Filters the form columns.
	 *
	 * @param array $columns The opt-in forms overview table columns.
	 * @return array
	 */
	public static function manage_form_columns( $columns ) {

		unset( $columns['author'] );
		unset( $columns['date'] );
		$columns['title']         = __( 'Form Name', 'newsletter-optin-box' );
		$columns['type']          = __( 'Form Type', 'newsletter-optin-box' );
		$columns['impressions']   = __( 'Impressions', 'newsletter-optin-box' );
		$columns['subscriptions'] = __( 'Subscriptions', 'newsletter-optin-box' );
		$columns['conversion']    = __( 'Conversion Rate', 'newsletter-optin-box' );
		return $columns;
	}

	/**
	 * Displays a column.
	 *
	 * @param string $column  The column being displayed.
	 * @param int    $post_id The post being displayed.
	 * @return void
	 */
	public static function display_form_columns( $column, $post_id ) {

		switch ( $column ) {
			case 'subscriptions':
				$subs = (int) get_post_meta( $post_id, '_noptin_subscribers_count', true );
				if ( empty( $subs ) ) {

					// Ensure that there is always a subscriber count for sorting by count.
					update_post_meta( $post_id, '_noptin_subscribers_count', 0 );

				} else {

					// Link to the list of subscribers who signed up using this specific form.
					$subs = sprintf(
						'<a href="%s" title="View the list of subscribers who signed up using this form.">%d</a>',
						esc_url(
							add_query_arg(
								array(
									'hizzlewp_filters' => rawurlencode(
										wp_json_encode(
											array(
												'source' => (string) $post_id,
											)
										)
									),
								),
								get_noptin_subscribers_overview_url()
							)
						),
						(int) $subs
					);
				}

				echo wp_kses_post( $subs );

				break;

			case 'type':
				$types    = array(
					'sidebar'  => _x( 'Widget', 'Subscription forms that are meant to appear in a widget area', 'newsletter-optin-box' ),
					'inpost'   => _x( 'Shortcode', 'Subscription forms that are embedded in posts using shortcodes', 'newsletter-optin-box' ),
					'popup'    => _x( 'Popup', 'Subscription forms that appear in a popup', 'newsletter-optin-box' ),
					'slide_in' => _x( 'Sliding', 'Subscription forms that slide into view', 'newsletter-optin-box' ),
				);
				$type = get_post_meta( $post_id, '_noptin_optin_type', true );

				if ( empty( $types[ $type ] ) ) {
					echo '<strong>' . esc_html( $type ) . '</strong>';
				} else {
					echo '<strong>' . esc_html( $types[ $type ] ) . '</strong>';
				}

				if ( 'inpost' === $type ) {

					printf(
						'<br><input title="%s" style="color: #607D8B;" onClick="this.select();" type="text" value="[noptin form=%d]" readonly="readonly" />',
						esc_attr__( 'Use this shortcode to display the form on your website', 'newsletter-optin-box' ),
						(int) $post_id
					);

				}
				break;

			case 'impressions':
				$impressions = get_post_meta( $post_id, '_noptin_form_views', true );
				if ( '' === $impressions ) {
					update_post_meta( $post_id, '_noptin_form_views', 0 );
				}

				echo (int) $impressions;

				break;

			case 'conversion':
				$subscriptions = (int) get_post_meta( $post_id, '_noptin_subscribers_count', true );
				$impressions   = (int) get_post_meta( $post_id, '_noptin_form_views', true );
				$conversion    = ( $subscriptions && $impressions ) ? ( $subscriptions * 100 / $impressions ) : 0;
				$conversion    = empty( $conversion ) ? 0 : round( $conversion, 4 ) . '%';
				echo esc_html( $conversion );
				break;

		}
	}

	/**
	 * Registers custom opt-in form filters.
	 *
	 * @param string $post_type The post type being displayed.
	 * @return void
	 */
	public static function custom_filters( $post_type ) {

		// Make sure this is our post type.
		if ( 'noptin-form' !== $post_type ) {
			return;
		}

		// Filter by form type.
		$form_types = array(
			'sidebar'  => __( 'Widget Forms', 'newsletter-optin-box' ),
			'inpost'   => __( 'Shortcode Forms', 'newsletter-optin-box' ),
			'popup'    => __( 'Popup Forms', 'newsletter-optin-box' ),
			'slide_in' => __( 'Sliding Forms', 'newsletter-optin-box' ),
		);

		$form_type = 'all';

		if ( isset( $_REQUEST['form_type'] ) && ! empty( $form_types[ $_REQUEST['form_type'] ] ) ) {
			$form_type = $_REQUEST['form_type'];
		}

		// build a custom dropdown list of values to filter by.
		echo '<select id="noptin-form-type" name="form_type">';
		echo '<option value="all">' . esc_html__( 'All Forms', 'newsletter-optin-box' ) . ' </option>';

		foreach ( $form_types as $key => $title ) {

			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $key, $form_type, false ),
				esc_html( $title )
			);

		}

		echo '</select>';
	}

	/**
	 * Applies our custom filters and custom sorting.
	 *
	 * @param WP_Query $query The query used to retrieve the opt-forms.
	 * @return WP_Query
	 */
	public static function apply_custom_filters( $query ) {

		// Make sure this is our query.
		if ( ! is_admin() || ! $query->is_main_query() || empty( $query->query['post_type'] ) || 'noptin-form' !== $query->query['post_type'] ) {
			return $query;
		}

		// Filter by form type.
		$form_types = array(
			'sidebar'  => __( 'Widget Forms', 'newsletter-optin-box' ),
			'inpost'   => __( 'Shortcode Forms', 'newsletter-optin-box' ),
			'popup'    => __( 'Popup Forms', 'newsletter-optin-box' ),
			'slide_in' => __( 'Sliding Forms', 'newsletter-optin-box' ),
		);

		if ( isset( $_REQUEST['form_type'] ) && ! empty( $form_types[ $_REQUEST['form_type'] ] ) ) {

			$form_type = esc_attr( $_REQUEST['form_type'] );

			if ( empty( $query->query_vars['meta_query'] ) ) {
				$query->query_vars['meta_query'] = array();
			}

			// modify the query_vars.
			$query->query_vars['meta_query'][] = array(
				'key'     => '_noptin_optin_type',
				'value'   => $form_type,
				'compare' => '=',
				'type'    => 'CHAR',
			);

		}

		// Order by impressions.
		if ( 'impressions' === $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'meta_key', '_noptin_form_views' );
			$query->set( 'meta_type', 'numeric' );
		}

		// Order by subscriptions.
		if ( 'subscriptions' === $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'meta_key', '_noptin_subscribers_count' );
			$query->set( 'meta_type', 'numeric' );
		}

		return $query;
	}

	/**
	 * Adds more sortable columns to the forms list
	 *
	 * @see Noptin_Post_Types::apply_custom_filters()
	 * @param array $columns An array of sortable form columns.
	 * @return array
	 */
	public static function add_sortable_columns( $columns ) {

		$columns['impressions']   = 'impressions';
		$columns['subscriptions'] = 'subscriptions';
		return $columns;
	}
}
