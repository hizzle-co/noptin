<?php
/**
 * Displays a list of all email subscribers
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Email subscribers table class.
 */
class Noptin_Subscribers_Table extends WP_List_Table {

	/**
	 * URL of this page
	 *
	 * @var   string
	 * @since 1.1.2
	 */
	public $base_url;

	/**
	 * Query
	 *
	 * @var   string
	 * @since 1.1.2
	 */
	public $query;

	/**
	 * Total Subscribers
	 *
	 * @var   string
	 * @since 1.1.2
	 */
	public $total_subscribers;

	/**
	 *  Constructor function.
	 */
	public function __construct() {

		$this->prepare_query();

		parent::__construct(
			array(
				'singular' => 'id',
				'plural'   => 'ids',
			)
		);

		$this->base_url = admin_url( 'admin.php?page=noptin-subscribers' );

		if ( ! empty( $_GET['_subscriber_via'] ) ) {
			$this->base_url = add_query_arg( '_subscriber_via', $_GET['_subscriber_via'], $this->base_url );
		}

	}

	/**
	 *  Prepares the display query
	 */
	public function prepare_query() {

		// Campaigns to display on every page.
		$per_page = 10;

		// Prepare query params.
		$paged   = empty( $_GET['paged'] ) ? 1 : $_GET['paged'];
		$orderby = empty( $_GET['orderby'] ) ? 'id' : $_GET['orderby'];
		$order   = empty( $_GET['order'] ) ? 'desc' : $_GET['order'];
		$via     = empty( $_GET['_subscriber_via'] ) ? false : $_GET['_subscriber_via'];

		$meta_key   = empty( $_GET['meta_key'] ) ? false : $_GET['meta_key'];
		$meta_value = empty( $_GET['meta_value'] ) ? false : $_GET['meta_value'];

		if ( ! empty( $_GET['_subscriber_via'] ) ) {
			$meta_key   = '_subscriber_via';
			$meta_value = $_GET['_subscriber_via'];
		}

		// Fetch the subscribers.
		$this->items  = noptin()->admin->get_subscribers( $paged, $meta_key, $meta_value );

		$this->total_subscribers = (int) get_noptin_subscribers_count( '', $meta_key, $meta_value );

	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 1.1.2
	 *
	 * @param object $item The current item.
	 */
	public function single_row( $item ) {
		echo '<tr>';
		$this->single_row_columns( (array) $item );
		echo '</tr>';
	}

	/**
	 * Default columns.
	 *
	 * @param object $item        item.
	 * @param string $column_name column name.
	 */
	public function column_default( $item, $column_name ) {

		/**
		 * Runs after displaying the subscribers overview page.
		 *
		 * @param array $this The admin instance
		 */
		do_action( "noptin_display_subscribers_table_$column_name", $item );

	}

	/**
	 * Displays the subscribers name
	 *
	 * @param  array $subscriber subscriber.
	 * @return HTML
	 */
	public function column_subscriber( $subscriber ) {

		$row_actions = array();
		$view_url    = esc_url(
			add_query_arg( 
				array(
					'subscriber' => $subscriber['id'],
					'return'     => urlencode( $this->base_url ),
				),
				admin_url( 'admin.php?page=noptin-subscribers' ) )
		);

		$row_actions['view'] = '<a href="' . $view_url . '">' . __( 'View', 'newsletter-optin-box' ) . '</a>';

		$email       = sanitize_email( $subscriber['email'] );
		$delete_url  = esc_url(
			wp_nonce_url(
				add_query_arg( 'delete-subscriber', $subscriber['id'] ),
				'noptin-subscriber'
			)
		);
		$delete_text = __( 'Delete', 'newsletter-optin-box' );

		$row_actions['delete'] = "<a class='noptin-delete-single-subscriber' data-email='$email' href='$delete_url'>$delete_text</a>";

		$row_actions = $this->row_actions( $row_actions );

		$avatar = esc_url( get_avatar_url( $email ) );
		$avatar = "<img src='$avatar' height='32' width='32'/>";
		$name   = '';

		if ( ! empty( $subscriber['first_name'] ) ) {
			$name = sanitize_text_field( $subscriber['first_name'] );
		}

		if ( ! empty( $subscriber['second_name'] ) ) {
			$name .= ' ' . sanitize_text_field( $subscriber['second_name'] );
		}

		if ( ! empty( $name ) ) {
			$name = "<div style='overflow: hidden;height: 18px;'>$name</div>";
		}

		$email = "<div class='row-title'><a href='$view_url'>$email</a></div>";

		return "<div style='display: flex;'><div>$avatar</div><div style='margin-left: 10px;'>$name<strong>$email</strong>$row_actions</div></div>";

	}

	/**
	 * Displays the subscriber's status
	 *
	 * @param  array $subscriber subscriber.
	 * @return HTML
	 */
	public function column_status( $subscriber ) {

		$status = empty( $subscriber['active'] ) ? __( 'Active', 'newsletter-optin-box' ) : __( 'Inactive', 'newsletter-optin-box' );
		$class  = empty( $subscriber['active'] ) ? 'status-active' : 'status-inactive';

		return "<span class='$class'>$status</span>";
	}

	/**
	 * Displays the subscriber's subscription date
	 *
	 * @param  array $subscriber subscriber.
	 * @return HTML
	 */
	public function column_date_created( $subscriber ) {
		return date_i18n( get_option( 'date_format' ), strtotime( $subscriber['date_created'] ) );
	}

	/**
	 * This is how checkbox column renders.
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="id[]" value="%s" />', esc_html( $item['id'] ) );
	}

	/**
	 * [OPTIONAL] Return array of bult actions if has any
	 *
	 * @return array
	 */
	function get_bulk_actions() {

		$actions = array(
			'delete' => __( 'Delete', 'newsletter-optin-box' ),
		);

		/**
		 * Filters the bulk table actions shown on Newsletter tables.
		 *
		 * @param array $actions An array of bulk actions.
		 */
		return apply_filters( 'manage_noptin_newsletters_table_bulk_actions', $actions );

	}

	/**
	 * Whether the table has items to display or not
	 *
	 * @return bool
	 */
	public function has_items() {
		return ! empty( $this->total_subscribers );
	}

	/**
	 * Fetch data from the database to render on view.
	 */
	function prepare_items() {

		$per_page = 10;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$this->set_pagination_args(
			array(
				'total_items' => $this->total_subscribers,
				'per_page'    => $per_page,
				'total_pages' => ceil( $this->total_subscribers / $per_page ),
			)
		);

	}

	/**
	 * Table columns.
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'subscriber'   => __( 'Subscriber', 'newsletter-optin-box' ),
			'status'       => __( 'Status', 'newsletter-optin-box' ),
			'date_created' => __( 'Subscription Date', 'newsletter-optin-box' ),

		);

		/**
		 * Filters the columns shown in a newsletter table.
		 *
		 * @param array $columns Newsletter table columns.
		 */
		return apply_filters( 'manage_noptin_newsletters_table_columns', $columns );
	}

	/**
	 * Table sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable = array(
			'id' => array( 'id', true ),
		);

		/**
		 * Filters the sortable columns in the newsletter overview table.
		 *
		 * @param array $sortable An array of sortable columns.
		 */
		return apply_filters( 'manage_noptin_newsletters_sortable_table_columns', $sortable );
	}

}
