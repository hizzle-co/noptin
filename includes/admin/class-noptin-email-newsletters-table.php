<?php
/**
 * Displays a list of all email newsletters
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Email newsletters table class.
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
	 * @var   WP_Query
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
		$paged   = empty( $_GET['paged'] ) ? 1 : $_GET['paged'];
		$orderby = empty( $_GET['orderby'] ) ? 'id' : $_GET['orderby'];
		$order   = empty( $_GET['order'] ) ? 'desc' : $_GET['order'];

		$query_args = array(
			'post_type'      => 'noptin-campaign',
			'post_status'    => array( 'pending', 'draft', 'future', 'publish' ),
			'meta_key'       => 'campaign_type',
			'meta_value'     => 'newsletter',
			'orderby'        => $orderby,
			'order'          => $order,
			'posts_per_page' => $per_page,
			'paged'          => $paged,
		);
		$query_args = apply_filters( 'manage_noptin_newsletters_wp_query_args', $query_args );

		$noptin_campaigns_query = new WP_Query( $query_args );
		$this->query            = $noptin_campaigns_query;

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

		$row_actions = array();

		$preview_url             = esc_url( get_noptin_action_url( 'preview_email', $item->ID, true ) );
		$row_actions['_preview'] = '<a href="' . $preview_url . '" target="_blank" >' . __( 'Preview', 'newsletter-optin-box' ) . '</a>';

		if ( 'publish' != $item->post_status ) {
			$edit_url            = esc_url( get_noptin_newsletter_campaign_url( $item->ID ) );
			$row_actions['edit'] = '<a href="' . $edit_url . '">' . __( 'Edit', 'newsletter-optin-box' ) . '</a>';
		} else {
			$edit_url = $preview_url;
		}

		$duplication_url = esc_url( add_query_arg( 'duplicate_campaign', $item->ID ) );
		$row_actions['duplicate'] = '<a class="noptin-duplicate-campaign" href="' . $duplication_url . '" data-id="' . $item->ID . '">' . __( 'Duplicate', 'newsletter-optin-box' ) . '</a>';

		$row_actions['delete'] = '<a class="noptin-delete-campaign" href="#" data-id="' . $item->ID . '">' . __( 'Delete', 'newsletter-optin-box' ) . '</a>';

		$title = esc_html( get_post_meta( $item->ID, 'custom_title', true ) );
		$title = empty( $title ) ? esc_html( $item->post_title ) : $title;

		$title = "<div><strong><a href='$edit_url'>$title</a></strong></div>";

		return sprintf( '%s %s', $title, $this->row_actions( apply_filters( 'noptin_campaign_row_actions', $row_actions, $item ) ) );
	}

	/**
	 * Displays the newsletter status
	 *
	 * @param  WP_Post $item item.
	 * @return void
	 */
	public function column_status( $item ) {
		$status = 'Draft';

		if ( 'future' === $item->post_status ) {
			$status = 'Scheduled';
		}

		if ( 'publish' === $item->post_status ) {

			if ( get_post_meta( $item->ID, 'completed', true ) ) {
				$status = 'Sent';
			} else {
				$status = '<strong style="color: #00796b;">Sending</strong>&mdash;';
				$status .= '<a class="noptin-stop-campaign" href="#" data-id="' . $item->ID . '">' . __( 'stop', 'newsletter-optin-box' ) . '</a>';
			}

		}

		$status = apply_filters( 'noptin_newsletter_status', $status, $item );
		echo "<span>$status</span>";
	}

	/**
	 * Displays the newsletter's date sent day
	 *
	 * @param  object $item item.
	 * @return void
	 */
	public function column_date_sent( $item ) {
		$date = '&mdash;';

		if ( 'future' === $item->post_status ) {
			$date = 'Scheduled to send in <br /> ' . human_time_diff( strtotime( $item->post_date ), current_time( 'timestamp' ) );

			if ( strtotime( $item->post_date ) < current_time( 'timestamp' ) ) {
				wp_publish_post( $item );
			}

		}

		if ( 'publish' === $item->post_status ) {
			$date = date_i18n( get_option( 'date_format' ), strtotime( $item->post_date ) );
		}

		$title = esc_attr( $item->post_date );
		echo "<abbr title='$title'>$date</abbr>";
	}

	/**
	 * Links to the subscribers overview page.
	 *
	 * @param  int    $count The number to link.
	 * @param  string $meta The subscriber meta key to filter by.
	 * @param  string $value The subscriber meta value to filter by.
	 * @return HTML
	 */
	public function maybe_link( $count, $meta, $value ) {

		if ( empty( $count ) ) {
			return 0;
		}

		$url = esc_url(
			add_query_arg(
				array(
					'meta_key'   => $meta,
					'meta_value' => $value,
				),
				get_noptin_subscribers_overview_url()
			)
		);

		return "<a href='$url' title='View Subscribers'>$count</a>";

	}

	/**
	 * Displays the campaign recipients
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_recipients( $item ) {

		$sent = (int) get_post_meta( $item->ID, '_noptin_sends', true );
		$sent = $this->maybe_link( $sent, "_campaign_{$item->ID}", '1' );

		$failed = (int) get_post_meta( $item->ID, '_noptin_fails', true );
		$failed = $this->maybe_link( $failed, "_campaign_{$item->ID}", '0' );

		$sent   = empty( $failed ) ? $sent : "$sent ($failed failed)";

		return apply_filters( 'noptin_newsletter_recipients', $sent, $item );

	}

	/**
	 * Displays the campaign opens
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_opens( $item ) {

		$opens = (int) get_post_meta( $item->ID, '_noptin_opens', true );
		$opens = $this->maybe_link( $opens, "_campaign_{$item->ID}_opened", '1' );
		return apply_filters( 'noptin_newsletter_opens', $opens, $item );

	}

	/**
	 * Displays the campaign clicks
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_clicks( $item ) {

		$clicks = (int) get_post_meta( $item->ID, '_noptin_clicks', true );
		$clicks = $this->maybe_link( $clicks, "_campaign_{$item->ID}_clicked", '1' );
		return apply_filters( 'noptin_newsletter_clicks', $clicks, $item );

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
		return apply_filters( 'manage_noptin_newsletters_table_bulk_actions', $actions );

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
			'cb'         => '<input type="checkbox" />',
			'title'      => __( 'Email Subject', 'newsletter-optin-box' ),
			'status'     => __( 'Status', 'newsletter-optin-box' ), // draft,scheduled,sending,completed.
			'recipients' => __( 'Recipients', 'newsletter-optin-box' ),
			'opens'      => __( 'Opens', 'newsletter-optin-box' ),
			'clicks'     => __( 'Clicks', 'newsletter-optin-box' ),
			'date_sent'  => __( 'Sent on', 'newsletter-optin-box' ),

		);

		$track_campaign_stats = get_noptin_option( 'track_campaign_stats', true );

		if ( empty( $track_campaign_stats ) ) {
			unset( $columns['opens'] );
			unset( $columns['clicks'] );
		}

		return apply_filters( 'manage_noptin_newsletters_table_columns', $columns );
	}

	/**
	 * Table sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable = array(
			'id'    => array( 'id', true ),
			'title' => array( 'post_title', true ),
		);
		return apply_filters( 'manage_noptin_newsletters_sortable_table_columns', $sortable );
	}

	/**
	 * Message to be displayed when there are no items
	 */
	public function no_items() {
		$add_new_campaign_url = get_noptin_new_newsletter_campaign_url();

		echo "<div style='min-height: 320px; display: flex; align-items: center; justify-content: center; flex-flow: column;'>";
		echo "<span class='dashicons dashicons-email' style='font-size: 100px; height: 100px; width: 100px; color: #00acc1; line-height: 100px;'></span>";
		
		printf(
			/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
			__( '%1$sSend your subscribers a new email%2$s', 'newsletter-optin-box' ),
			"<a class='no-campaign-create-new-campaign' href='$add_new_campaign_url'>",
			'</a>'
		);

		echo "<p class='description'>Or <a style='color: #616161; text-decoration: underline;' href='https://noptin.com/guide/sending-emails' target='_blank'>" . __( 'Learn more', 'newsletter-optin-box' ) . "</a></p>";
		echo '</div>';

	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 1.2.9
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {

		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}
		?>
	<div class="tablenav <?php echo esc_attr( $which ); ?>">

		<?php if ( $this->has_items() ) : ?>
		<div class="alignleft actions bulkactions">
			<?php $this->bulk_actions( $which ); ?>
		</div>

		<a href="<?php echo esc_url( get_noptin_new_newsletter_campaign_url() ); ?>" class="button button-primary create-new-campaign"><?php _e( 'Send A New Email', 'newsletter-optin-box' ); ?></a>
			<?php
		endif;

		$this->extra_tablenav( $which );
		$this->pagination( $which );
		?>

		<br class="clear" />
	</div>
		<?php
	}

}


