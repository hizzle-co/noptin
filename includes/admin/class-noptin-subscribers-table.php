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
			$query['search'] = urldecode( $_POST['s'] );
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
	 * @param Noptin_Subscriber $subscriber Subscriber.
	 * @param string $column_name column name.
	 */
	public function column_default( $subscriber, $column_name ) {

		$all_fields = wp_list_pluck( get_noptin_custom_fields(), 'type', 'merge_tag' );

		if ( isset( $all_fields[ $column_name ] ) ) {
			echo wp_kses_post(
					format_noptin_custom_field_value(
					$subscriber->get( $column_name ),
					$all_fields[ $column_name ],
					$subscriber
				)
			);
		}

		/**
		 * Runs when displaying a subscriber's field.
		 *
		 * @param Noptin_Subscriber $item The current subscriber.
		 */
		do_action( "noptin_display_subscribers_table_$column_name", $subscriber );

	}

	/**
	 * Displays the subscribers name
	 *
	 * @param  Noptin_Subscriber $subscriber subscriber.
	 * @return HTML
	 */
	public function column_email( $subscriber ) {

		return sprintf(
			'<div class="row-title"><strong><a href="%s">#%s %s</a></strong></div>',
			esc_url( add_query_arg( 'subscriber', $subscriber->id, admin_url( 'admin.php?page=noptin-subscribers' ) ) ),
			(int) $subscriber->id,
			sanitize_email( $subscriber->email )
		);

	}

	/**
	 * Displays the subscriber's status
	 *
	 * @param  Noptin_Subscriber $subscriber subscriber.
	 * @return HTML
	 */
	public function column_status( $subscriber ) {

		return sprintf(
			'<span class="noptin-badge %s">%s</span>',
			$subscriber->is_active() ? 'success' : '',
			$subscriber->is_active() ? __( 'Subscribed', 'newsletter-optin-box' ) : __( 'Pending', 'newsletter-optin-box' )
		);

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
	 * Displays the subscriber's subscription source
	 *
	 * @param  Noptin_Subscriber $subscriber subscriber.
	 * @return HTML
	 */
	public function column__subscriber_via( $subscriber ) {
		return wp_kses_post( noptin_format_subscription_source( $subscriber->_subscriber_via ) );
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
	public function get_columns() {

		$columns = array(
			'cb' => '<input type="checkbox" />',
		);

		foreach ( $this->get_custom_fields() as $key => $label ) {

			$columns[ $key ] = $label;
			if ( 'email' === $key ) {
				$columns['status'] = __( 'Status', 'newsletter-optin-box' );
			}

		}

		if ( ! isset( $columns['status'] ) ) {
			$columns['status'] = __( 'Status', 'newsletter-optin-box' );
		}

		$columns['_subscriber_via'] = __( 'Source', 'newsletter-optin-box' );
		$columns['date_created']    = __( 'Added', 'newsletter-optin-box' );

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
			'id'              => array( 'id', true ),
			'date_created'    => array( 'date_created', true ),
			'_subscriber_via' => array( '_subscriber_via', false ),
			'status'          => array( 'active', false ),
			'email'           => array( 'email', false ),
			'first_name'      => array( 'first_name', false ),
			'last_name'       => array( 'second_name', false ),
		);

		/**
		 * Filters the sortable columns in the newsletter overview table.
		 *
		 * @param array $sortable An array of sortable columns.
		 */
		return apply_filters( 'manage_noptin_newsletters_sortable_table_columns', $sortable );
	}

	/**
	 * Returns an array of custom fields.
	 *
	 * @return array
	 */
	public function get_custom_fields() {
		$fields = array();

		foreach ( get_noptin_custom_fields() as $field ) {

			if ( ! empty( $field['subs_table'] ) ) {
				$fields[ $field['merge_tag'] ] = $field['label'];
			}

		}

		return $fields;
	}

	/**
	 * Generates the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			parent::display_tablenav( $which );
			echo '<div id="noptin-subscribers-table-wrap">';
		} else {
			echo '</div>';
			parent::display_tablenav( $which );
		}
	}

}
