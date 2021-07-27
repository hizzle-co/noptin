<?php

/**
 * Displays a list of all lists.
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Lists table class.
 *
 * @ignore
 * @since 1.5.1
 */
class Noptin_List_Providers_Table extends WP_List_Table {

	/**
	 * Lists to display on this page.
	 *
	 * @var   Noptin_List_Providers
	 * @since 1.5.1
	 */
	public $providers;

	/**
	 * Lists to display on this page.
	 *
	 * @var   Noptin_List_Provider[]
	 * @since 1.5.1
	 */
	public $items;

	/**
	 * Total lists
	 *
	 * @var   string
	 * @since 1.5.1
	 */
	public $total;

	/**
	 *  Constructor function.
	 *
	 * @param Noptin_List_Providers $providers
	 */
	public function __construct( $providers ) {

		parent::__construct(
			array(
				'singular' => 'id',
				'plural'   => 'ids',
			)
		);

		$this->providers = $providers;
		$this->items     = $providers->get_lists();
		$this->total     = count( $this->items );

	}

	/**
	 * Default columns.
	 *
	 * @param Noptin_List_Provider $item        item.
	 * @param string $column_name column name.
	 */
	public function column_default( $item, $column_name ) {

		if ( is_callable( array( $item, "get_$column_name" ) ) ) {
			return esc_html( call_user_func( array( $item, "get_$column_name" ) ) );
		}

		return "&mdash;";
	}

	/**
	 * Displays the name of list.
	 *
	 * @param  Noptin_List_Provider $list list.
	 * @return string
	 */
	public function column_name( $list ) {
		$name     = esc_html( $list->get_name() );
		$list_url = add_query_arg( 'list', $list->get_id(), admin_url( 'admin.php?page=noptin-' + $this->providers->get_id() ) );
		$refresh  = add_query_arg( 'noptin_provider_action', 'refresh', $list_url );
		$list_url = esc_url( $list_url );

		$row_actions = array(
			'configure' => '<a href="' . $list_url . '">' . __( 'View', 'newsletter-optin-box' ) . '</a>',
			'refresh'   => '<a href="' . wp_nonce_url( $refresh, 'noptin_provider_action' ) . '">' . __( 'Refresh Data', 'newsletter-optin-box' ) . '</a>',
		);

		$row_actions = $this->row_actions( $row_actions );
		$name        = "<a href='$list_url'>$name</a>";
		return "<div><strong>$name</strong>$row_actions</div>";

	}

	/**
	 * Displays the id of list.
	 *
	 * @param  Noptin_List_Provider $list list.
	 * @return string
	 */
	public function column_id( $list ) {
		$id = esc_html( $list->get_id() );
		return "<code>$id</code>";
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
	function prepare_items() {

		$per_page = 1000;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->set_pagination_args(
			array(
				'total_items' => $this->total,
				'per_page'    => $per_page,
				'total_pages' => ceil( $this->total / $per_page ),
			)
		);

	}

	/**
	 * Table columns.
	 *
	 * @return array
	 */
	function get_columns() {
		return $this->providers->get_list_columns();
	}

	/**
	 * Table sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array();
	}

	/**
	 * Message to be displayed when there are no items
	 */
	public function no_items() {

		$url = $this->providers->get_url();

		if ( empty( $url ) ) {
			parent::no_items();
			return;
		}

		$url = esc_url( $url );

		echo "<div style='min-height: 320px; display: flex; align-items: center; justify-content: center; flex-flow: column;'>";
		echo "<span class='dashicons dashicons-email' style='font-size: 100px; height: 100px; width: 100px; color: #00acc1; line-height: 100px;'></span>";		
		printf(
			/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
			__( '%1$sCreate your first list%2$s', 'newsletter-optin-box' ),
			"<div style='margin-top: 10px;'><a style='font-size: 16px;' href='$url'>",
			'</a></div>'
		);

		echo '</div>';
	}

}
