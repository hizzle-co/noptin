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

		// Campaigns to display on every page.
		$per_page = 10;

		// Prepare query params.
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

		/**
         * Displays a given column
         *
         * @param array $this The admin instance
         */
		do_action( "noptin_display_newsletters_table_$column_name", $item );

	}

	/**
	 * Displays the newsletter name
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_title( $item ) {

		$row_actions         = array();

		$preview_url            = esc_url( get_noptin_action_url( 'preview_email', $item->ID, true ) );
		$row_actions['_preview'] = '<a href="' . $preview_url . '" target="_blank" >' . __( 'Preview', 'newsletter-optin-box' ) . '</a>';

		$edit_url			 = esc_url( get_noptin_newsletter_campaign_url( $item->ID ) );
		$row_actions['edit'] = '<a href="' . $edit_url . '">' . __( 'Edit', 'newsletter-optin-box' ) . '</a>';

		$row_actions['delete'] = '<a class="noptin-delete-campaign" href="#" data-id="' . $item->ID .'">' . __( 'Delete', 'newsletter-optin-box' ) . '</a>';

		$title = esc_html( $item->post_title );

		$title = "<div><strong><a href='$edit_url'>$title</a></strong></div>";

		return sprintf( '%s %s', $title, $this->row_actions( $row_actions ) );
	}

	/**
	 * Displays the newsletter status
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_status( $item ) {
		$status = 'Draft';

		if( 'future' == $item->post_status ) {
			$status = 'Scheduled';
		}

		if( 'publish' == $item->post_status ) {

			if( get_post_meta( $item->ID, 'completed', true ) ) {
				$status = 'Sent';
			} else {
				$status = '<strong style="color: #00796b;">Sending</strong>';
			}

		}

		echo "<span>$status</span>";
	}

	/**
	 * Displays the newsletter's date sent day
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_date_sent( $item ) {
		$date = '&mdash;';

		if( 'future' == $item->post_status ) {
			$date = 'Scheduled <br /> ' . $item->post_date;
		}

		if( 'publish' == $item->post_status ) {
			$date = date_i18n( get_option( 'date_format' ), strtotime( $item->post_date ) );
		}

		$title = esc_attr( $item->post_date );
		echo "<abbr title='$title'>$date</abbr>";
	}

	/**
	 * Links to the subscribers overview page
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function maybe_link( $count, $meta, $value ) {

		if( empty( $count ) ) {
			return 0;
		}

		$url    = esc_url( add_query_arg( array(
			'meta_key'   => $meta,
			'meta_value' => $value,
		), get_noptin_subscribers_overview_url() ) );

		return "<a href='$url' title='View Subscribers'>$count</a>";

	}

	/**
	 * Displays the campaign recipients
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_recipients( $item ) {


		$sent   = (int) get_post_meta( $item->ID, '_noptin_sends', true );
		$sent   = $this->maybe_link( $sent, "_campaign_{$item->ID}" , '1' );

		$failed = (int) get_post_meta( $item->ID, '_noptin_fails', true );
		$failed   = $this->maybe_link( $failed, "_campaign_{$item->ID}" , '0' );

		if( empty( $failed ) ) {
			return $sent;
		}

		return "$sent ($failed failed)";

	}

	/**
	 * Displays the campaign opens
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_opens( $item ) {

		$opens  = (int) get_post_meta( $item->ID, '_noptin_opens', true );
		return $this->maybe_link( $opens, "_campaign_{$item->ID}_opened" , '1' );

	}

	/**
	 * Displays the campaign clicks
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_clicks( $item ) {

		$clicks  = (int) get_post_meta( $item->ID, '_noptin_clicks', true );
		return $this->maybe_link( $clicks, "_campaign_{$item->ID}_clicked" , '1' );

	}

	/**
	 * This is how checkbox column renders.
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="id[]" value="%s" />', esc_html( $item->ID ) );
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
	 * Generate the table rows
	 *
	 * @since 1.1.2
	 */
	public function display_rows() {
		foreach ( $this->query->get_posts() as $post ) {
			$this->single_row( $post );
		}
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
			'status' 		=> __( 'Status', 'newsletter-optin-box' ), // draft,scheduled,sending,completed.
			'recipients' 	=> __( 'Recipients', 'newsletter-optin-box' ),
			'opens'		    => __( 'Opens', 'newsletter-optin-box' ),
			'clicks'		=> __( 'Clicks', 'newsletter-optin-box' ),
			'date_sent'  	=> __( 'Sent on', 'newsletter-optin-box' ),

		);
		return apply_filters( "manage_noptin_newsletters_table_columns", $columns );
	}

	/**
	 * Table sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable = array(
			'id'            => array( 'id', true ),
			'title' 		=> array( 'post_title', true ),
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

}


