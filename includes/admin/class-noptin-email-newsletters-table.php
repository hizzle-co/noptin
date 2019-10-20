<?php
/**
 * Displays a list of all email newsletters
 *
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * email newsletters table class.
 */
class Noptin_Email_Newsletters_Table extends WP_List_Table {

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
	 *  Constructor function.
	 */
	public function __construct() {
		global $status, $page;

		$this->prepare_query();

		parent::__construct(
			array(
				'singular' => 'id',
				'plural'   => 'ids',
			)
		);

		$this->base_url = admin_url( 'admin.php?page=noptin-email-campaigns' );

	}

	/**
	 *  Prepares the display query
	 */
	public function prepare_query() {
		global $noptin_campaigns_query;

		//Campaigns to display on every page
		$per_page = 10;

		//Prepare query params
		$paged     = empty( $_GET['paged'] )   ? 1 : $_GET['paged'];
		$orderby   = empty( $_GET['orderby'] ) ? 'id' : $_GET['orderby'];
		$order     = empty( $_GET['order'] )   ? 'desc' : $_GET['order'];

		$query_args = array(
			'post_type' 	=> 'noptin-campaign',
			'post_status'   => array( 'pending', 'draft', 'future', 'publish' ),
			'meta_key'   	=> 'campaign_type',
			'meta_value' 	=> 'newsletter',
			'orderby' 		=> $orderby,
			'order'   		=> $order,
			'posts_per_page'=> $per_page,
			'paged'			=> $paged,
		);
		$query_args = apply_filters( "manage_noptin_newsletters_wp_query_args", $query_args );

		$noptin_campaigns_query = new WP_Query( $query_args  );
		$this->query = $noptin_campaigns_query;

	}

	/**
	 * Default columns.
	 *
	 * @param object $item        item.
	 * @param string $column_name column name.
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * This is how id column renders.
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_template_name( $item ) {

		$row_actions['edit'] = '<a href="' . wp_nonce_url(
			add_query_arg(
				array(
					'action'     => WCF_ACTION_EMAIL_TEMPLATES,
					'sub_action' => WCF_SUB_ACTION_EDIT_EMAIL_TEMPLATES,
					'id'         => $item['id'],
				),
				$this->base_url
			),
			WCF_EMAIL_TEMPLATES_NONCE
		) . '">' . __( 'Edit', 'newsletter-optin-box' ) . '</a>';

		$row_actions['delete'] = '<a onclick="return confirm(\'Are you sure to delete this email template?\');" href="' . wp_nonce_url(
			add_query_arg(
				array(
					'action'     => WCF_ACTION_EMAIL_TEMPLATES,
					'sub_action' => WCF_SUB_ACTION_DELETE_EMAIL_TEMPLATES,
					'id'         => $item['id'],
				),
				$this->base_url
			),
			WCF_EMAIL_TEMPLATES_NONCE
		) . '">' . __( 'Delete', 'newsletter-optin-box' ) . '</a>';

		$row_actions['clone'] = '<a href="' . wp_nonce_url(
			add_query_arg(
				array(
					'action'     => WCF_ACTION_EMAIL_TEMPLATES,
					'sub_action' => WCF_SUB_ACTION_CLONE_EMAIL_TEMPLATES,
					'id'         => $item['id'],
				),
				$this->base_url
			),
			WCF_EMAIL_TEMPLATES_NONCE
		) . '">' . __( 'Clone', 'newsletter-optin-box' ) . '</a>';

		return sprintf( '%s %s', esc_html( $item['template_name'] ), $this->row_actions( $row_actions ) );
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
		return $this->query->have_posts();
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
				'total_items' => $this->query->found_posts,
				'per_page'    => $per_page,
				'total_pages' => $this->query->max_num_pages,
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
			'title' 		=> __( 'Email Subject', 'newsletter-optin-box' ),
			'status' 		=> __( 'Status', 'newsletter-optin-box' ), //draft,sending,completed - (draft,pending,publish)
			'recipients' 	=> __( 'Recipients', 'newsletter-optin-box' ),
			'stats'		    => __( 'Opens/Clicks', 'newsletter-optin-box' ),
			'date_sent'  	=> __( 'Sent on', 'newsletter-optin-box' ),

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


