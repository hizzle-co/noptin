<?php
/**
 * Displays a list of all email subscribers
 *
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * email subscribers table class.
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

		if(! empty( $_GET['_subscriber_via'] ) ) {
			$this->base_url = add_query_arg( '_subscriber_via', $_GET['_subscriber_via'],$this->base_url );
		}

	}

	/**
	 *  Prepares the display query
	 */
	public function prepare_query() {

		//Campaigns to display on every page
		$per_page = 10;

		//Prepare query params
		$paged     		= empty( $_GET['paged'] )   			 ? 1      : $_GET['paged'];
		$orderby   		= empty( $_GET['orderby'] ) 			 ? 'id'   : $_GET['orderby'];
		$order     		= empty( $_GET['order'] )   			 ? 'desc' : $_GET['order'];
		$via       		= empty( $_GET['_subscriber_via'] )   ? false  : $_GET['_subscriber_via'];

		//Fetch the subscribers
		$noptin_admin      = Noptin_Admin::instance();
		$this->items 	   = $noptin_admin->get_subscribers( $paged, '_subscriber_via', $via );

		$this->total_subscribers = (int) get_noptin_subscribers_count( '', '_subscriber_via', $via );

	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 1.1.2
	 *
	 * @param object $item The current item
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

		$row_actions         = array();
		$view_url			 = esc_url( wp_nonce_url(
			add_query_arg( 'subscriber', $subscriber['id'],$this->base_url ),
			'noptin-subscriber'
		) );

		$row_actions['view'] = '<a href="' . $view_url . '">' . __( 'View', 'newsletter-optin-box' ) . '</a>';

		$row_actions['delete'] = '<a onclick="return confirm(\'Are you sure to delete this subscriber?\');" href="' . esc_url( wp_nonce_url(
			add_query_arg( 'delete-subscriber', $subscriber['id'],$this->base_url ),
			'noptin-subscriber'
		)) . '">' . __( 'Delete', 'newsletter-optin-box' ) . '</a>';

		$row_actions = $this->row_actions( $row_actions );

		$email = sanitize_text_field( $subscriber['email'] );
		$avatar= esc_url( get_avatar_url( $email ) );
		$avatar= "<img src='$avatar' height='32' width='32'/>";
		$name  = '';

		if(! empty( $subscriber['first_name'] ) ) {
			$name  = sanitize_text_field( $subscriber['first_name'] );
		}

		if(! empty( $subscriber['second_name'] ) ) {
			$name  .= ' ' . sanitize_text_field( $subscriber['second_name'] );
		}

		if(! empty( $name ) ) {
			$name  = "<div style='overflow: hidden;height: 18px;'>$name</div>";
		}

		$email = "<div><a href='$view_url'>$email</a></div>";


		return "<div style='display: flex;'><div>$avatar</div><div style='margin-left: 10px;'>$name<strong>$email</strong>$row_actions</div></div>";

	}

	/**
	 * Displays the subscriber's status
	 *
	 * @param  array $subscriber subscriber.
	 * @return HTML
	 */
	public function column_status( $subscriber ) {

		$status = empty( $subscriber['active'] ) ? __( 'Active',  'newsletter-optin-box' ) : __( 'Inactive',  'newsletter-optin-box' );
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
		return date( 'D, jS M Y', strtotime( $subscriber['date_created'] ));
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
		return apply_filters( "manage_noptin_newsletters_table_bulk_actions", $actions );

	}

	/**
	 * Whether the table has items to display or not
	 *
	 * @return bool
	 */
	public function has_items() {
		return $this->total_subscribers != 0;
	}

	/**
	 * Fetch data from the database to render on view.
	 *
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
			'cb'            => '<input type="checkbox" />',
			'subscriber' 	=> __( 'Subscriber', 'newsletter-optin-box' ),
			'status' 		=> __( 'Status', 'newsletter-optin-box' ),
			'date_created' 	=> __( 'Subscription Date', 'newsletter-optin-box' ),

		);
		return apply_filters( "manage_noptin_newsletters_table_columns", $columns );
	}


	/**
	 * Column name trigger_time.
	 *
	 * @param  object $item item.
	 * @return string
	 */
	function column_trigger_time( $item ) {

		return sprintf(
			'%d %s',
			esc_html( $item['frequency'] ),
			' - ' . esc_html( $item['frequency_unit'] )
		);
	}

	/**
	 * Column name trigger_time.
	 *
	 * @param  object $item item.
	 * @return string
	 */
	function column_is_activated( $item ) {

		return sprintf( '%s', esc_html( $item['is_activated'] ? 'YES' : 'NO' ) );
	}

	/**
	 * Table sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable = array(
			'id'            => array( 'id', true ),
			'title' 		=> array( 'Email Subject', true ),
		);
		return apply_filters( "manage_noptin_newsletters_sortable_table_columns", $sortable );
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 */
	public function no_items() {
		$add_new_campaign_url = get_noptin_new_newsletter_campaign_url();

		printf(
			__( '%sSend your subscribers a new email%s', 'newsletter-optin-box' ),
			"<a class='no-campaign-create-new-campaign' href='$add_new_campaign_url'>",
			'</a>'
		);
	}

	/**
	 * Processes bulk actions
	 */
	function process_bulk_action() {
		global $wpdb;
		$action     = filter_input( INPUT_GET, 'sub_action', FILTER_SANITIZE_STRING );

		if ( 'delete' === $action ) {
			$ids = array();

			if ( isset( $_REQUEST['id'] ) && is_array( $_REQUEST['id'] ) ) {
				$ids = array_map( 'intval', $_REQUEST['id'] );
			}

			foreach( $ids as $id ) {
				wp_delete_post( $id, true );
			}

		}

	}


}


