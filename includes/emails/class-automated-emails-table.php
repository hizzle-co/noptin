<?php
/**
 * Displays a list of all email automations
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * email automations table class.
 */
class Noptin_Email_Automations_Table extends WP_List_Table {

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
		$paged   = empty( $_GET['paged'] ) ? 1 : $_GET['paged'];
		$orderby = empty( $_GET['orderby'] ) ? 'id' : $_GET['orderby'];
		$order   = empty( $_GET['order'] ) ? 'desc' : $_GET['order'];

		$query_args = array(
			'post_type'      => 'noptin-campaign',
			'post_status'    => array( 'pending', 'draft', 'future', 'publish' ),
			'meta_key'       => 'campaign_type',
			'meta_value'     => 'automation',
			'orderby'        => $orderby,
			'order'          => $order,
			'posts_per_page' => $per_page,
			'paged'          => $paged,
		);
		$query_args = apply_filters( 'manage_noptin_automations_wp_query_args', $query_args );

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
		do_action( "noptin_display_automations_table_$column_name", $item, $this );

	}

	/**
	 * Displays the automation name
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_title( $item ) {

		$row_actions         = array();
		$edit_url            = esc_url( get_noptin_automation_campaign_url( $item->ID ) );
		$row_actions['edit'] = '<a href="' . $edit_url . '">' . __( 'Edit', 'newsletter-optin-box' ) . '</a>';

		$row_actions['delete'] = '<a class="noptin-delete-campaign" href="#" data-id="' . $item->ID . '">' . __( 'Delete', 'newsletter-optin-box' ) . '</a>';

		$title = esc_html( $item->post_title );
		$extra = '';

		if ( 'publish' !== $item->post_status ) {
			$extra = '&mdash; ' . __( 'Inactive', 'newsletter-optin-box' );
		}
		$title = "<div><strong><a href='$edit_url'>$title</a> $extra</strong></div>";

		return sprintf( '%s %s', $title, $this->row_actions( $row_actions ) );
	}

	/**
	 * Links to the subscribers overview page
	 *
	 * @param  object $item item.
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
	 * Displays the automation details
	 *
	 * @param  object $item item.
	 * @return HTML
	 */
	public function column_about( $item ) {
		$type  = esc_html( get_post_meta( $item->ID, 'automation_type', true ) );
		$about = "Automation Type: $type";
		return apply_filters( 'noptin_automation_table_about', $about, $type, $item, $this );
	}

	/**
	 * Displays the automation type.
	 *
	 * @param  object $item item.
	 * @return HTML
	 * @since 1.2.9
	 */
	public function column_type( $item ) {
		echo esc_html( get_post_meta( $item->ID, 'automation_type', true ) );
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
		return apply_filters( 'manage_noptin_automations_table_bulk_actions', $actions );

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
			'cb'    => '<input type="checkbox" />',
			'title' => __( 'Name', 'newsletter-optin-box' ),
			'type'  => __( 'Type', 'newsletter-optin-box' ),
			'about' => __( 'About', 'newsletter-optin-box' ),

		);
		return apply_filters( 'manage_noptin_automations_table_columns', $columns );
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
		return apply_filters( 'manage_noptin_automations_sortable_table_columns', $sortable );
	}

	/**
	 * Message to be displayed when there are no items
	 */
	public function no_items() {
		$title  = esc_attr__( 'Create A New Automation', 'newsletter-optin-box' );
		$anchor = esc_attr__( 'Create a new automated email', 'newsletter-optin-box' );
		echo "<a title='$title' class='no-campaign-create-new-campaign noptin-create-new-automation-campaign' href='#'>$anchor</a>";
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

		<a href="#" class="button button-primary noptin-create-new-automation-campaign"><?php _e( 'Create New Automation', 'newsletter-optin-box' ); ?></a>
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
