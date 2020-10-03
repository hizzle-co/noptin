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
	 * @var   int
	 * @since 1.1.2
	 */
	public $total;

	/**
	 * Number of subscribers to display per page.
	 *
	 * @var   int
	 * @since 1.3.4
	 */
	public $per_page = 10;

	/**
	 *  Constructor function.
	 */
	public function __construct() {

		$per_page = absint( get_user_meta( get_current_user_id(), 'noptin_subscribers_per_page', true) );

		if ( ! empty( $per_page ) ) {
			$this->per_page = $per_page;
		}

		parent::__construct(
			array(
				'singular' => 'id',
				'plural'   => 'ids',
			)
		);

		$this->process_bulk_action();

		$this->prepare_query();

		$this->base_url = remove_query_arg( array( 'delete-subscriber', '_wpnonce' ) );

	}

	/**
	 *  Processes a bulk action.
	 */
	public function process_bulk_action() {

		$action = 'bulk-' . $this->_args['plural'];

		if ( empty( $_POST['id'] ) || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $action ) ) {
			return;
		}

		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		$action = $this->current_action();

		if ( 'delete' === $action ) {

			foreach ( $_POST['id'] as $id ) {
				delete_noptin_subscriber( $id );
			}

			noptin()->admin->show_info( __( 'The selected subscribers have been deleted.', 'newsletter-optin-box' ) );

		}

		if ( 'activate' === $action ) {

			foreach ( $_POST['id'] as $id ) {
				update_noptin_subscriber( intval( $id ), array( 'active' => 0 ) );
			}

			noptin()->admin->show_info( __( 'The selected subscribers have been activated.', 'newsletter-optin-box' ) );

		}

		if ( 'deactivate' === $action ) {

			foreach ( $_POST['id'] as $id ) {
				update_noptin_subscriber( intval( $id ), array( 'active' => 1 ) );
			}

			noptin()->admin->show_info( __( 'The selected subscribers have been marked as in-active.', 'newsletter-optin-box' ) );

		}

	}

	/**
	 *  Prepares the display query
	 */
	public function prepare_query() {

		$query  = array();
		$fields = array(
			'subscriber_status',
			'meta_query',
			'email_status',
			'date_query',
			'meta_key',
			'meta_value',
			'meta_compare',
			'orderby',
			'order',
			'paged'
		);

		foreach ( $fields as $field ) {
			if ( ! empty( $_GET[ $field ] ) ) {
				$query[ $field ] = map_deep( $_GET[ $field ], 'urldecode' );
			}
		}

		if ( empty( $query['meta_query'] ) || ! is_array( $query['meta_query'] ) ) {
			$query['meta_query'] = array();
		}

		// Number of subscribers to retrieve.
		$query['number'] = $this->per_page;

		// Subscriber via.
		if ( ! empty( $_GET['_subscriber_via'] ) ) {
			$query['meta_query'][] = array(
				'key'   => '_subscriber_via',
				'value' => $_GET['_subscriber_via'],
			);
		}

		// Search.
		if ( ! empty( $_POST['s'] ) ) {
			$query['search'] = $_POST['s'];
		}

		$subscribers = new Noptin_Subscriber_Query( $query );

		// Fetch the subscribers.
		$this->items = $subscribers->get_results();
		$this->total = (int) $subscribers->get_total();

	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 1.1.2
	 *
	 * @param Noptin_Subscriber $item The current item.
	 */
	public function single_row( $item ) {
		echo '<tr>';
		$this->single_row_columns( $item );
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
		 * Runs when displaying a subscriber's field.
		 *
		 * @param Noptin_Subscriber $item The current subscriber.
		 */
		do_action( "noptin_display_subscribers_table_$column_name", $item );

	}

	/**
	 * Displays the subscribers name
	 *
	 * @param  Noptin_Subscriber $subscriber subscriber.
	 * @return HTML
	 */
	public function column_subscriber( $subscriber ) {

		$row_actions = array();
		$view_url    = esc_url(
			add_query_arg( 
				array(
					'subscriber' => $subscriber->id,
					'return'     => urlencode( $this->base_url ),
				),
				admin_url( 'admin.php?page=noptin-subscribers' ) )
		);

		$row_actions['view'] = '<a href="' . $view_url . '">' . __( 'View', 'newsletter-optin-box' ) . '</a>';

		$email       = sanitize_email( $subscriber->email );
		$delete_url  = esc_url(
			wp_nonce_url(
				add_query_arg( 'delete-subscriber', $subscriber->id ),
				'noptin-subscriber'
			)
		);
		$delete_text = __( 'Delete', 'newsletter-optin-box' );

		$row_actions['delete'] = "<a class='noptin-delete-single-subscriber' data-email='$email' href='$delete_url'>$delete_text</a>";

		$row_actions = $this->row_actions( $row_actions );

		$avatar = esc_url( get_avatar_url( $email ) );
		$avatar = "<img src='$avatar' height='32' width='32'/>";
		$name   = '';

		if ( ! empty( $subscriber->first_name ) ) {
			$name = sanitize_text_field( $subscriber->first_name );
		}

		if ( ! empty( $subscriber->second_name ) ) {
			$name .= ' ' . sanitize_text_field( $subscriber->second_name );
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
	 * @param  Noptin_Subscriber $subscriber subscriber.
	 * @return HTML
	 */
	public function column_status( $subscriber ) {

		$status = empty( $subscriber->active ) ? __( 'Active', 'newsletter-optin-box' ) : __( 'Inactive', 'newsletter-optin-box' );
		$class  = empty( $subscriber->active ) ? 'status-active' : 'status-inactive';

		return "<span class='$class'>$status</span>";
	}

	/**
	 * Displays the subscriber's subscription date
	 *
	 * @param  Noptin_Subscriber $subscriber subscriber.
	 * @return HTML
	 */
	public function column_date_created( $subscriber ) {
		return date_i18n( get_option( 'date_format' ), strtotime( $subscriber->date_created ) );
	}

	/**
	 * This is how checkbox column renders.
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="id[]" value="%s" />', esc_html( $item->id ) );
	}

	/**
	 * [OPTIONAL] Return array of bult actions if has any
	 *
	 * @return array
	 */
	function get_bulk_actions() {

		$actions = array(
			'delete'     => __( 'Delete', 'newsletter-optin-box' ),
			'activate'   => __( 'Mark as active', 'newsletter-optin-box' ),
			'deactivate' => __( 'Mark as in-active', 'newsletter-optin-box' ),
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
		return ! empty( $this->total );
	}

	/**
	 * Fetch data from the database to render on view.
	 */
	function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->set_pagination_args(
			array(
				'total_items' => $this->total,
				'per_page'    => $this->per_page,
				'total_pages' => ceil( $this->total / $this->per_page ),
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
			'id'           => array( 'id', true ),
			'date_created' => array( 'date_created', true ),
			'subscriber'   => array( 'email', true ),
			'status'       => array( 'active', false ),
		);

		/**
		 * Filters the sortable columns in the newsletter overview table.
		 *
		 * @param array $sortable An array of sortable columns.
		 */
		return apply_filters( 'manage_noptin_newsletters_sortable_table_columns', $sortable );
	}

}
