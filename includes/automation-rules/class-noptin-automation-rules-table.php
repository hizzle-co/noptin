<?php
/**
 * Displays a list of all automation rules
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Automation rules table class.
 */
class Noptin_Automation_Rules_Table extends WP_List_Table {

	/**
	 * URL of this page
	 *
	 * @var   string
	 * @since 1.2.8
	 */
	public $base_url;

	/**
	 * Query
	 *
	 * @var   string
	 * @since 1.2.8
	 */
	public $query;

	/**
	 * Total Automations
	 *
	 * @var   string
	 * @since 1.2.8
	 */
	public $total;

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

		$this->base_url = admin_url( 'admin.php?page=noptin-automation-rules' );

	}

	/**
	 *  Prepares the display query
	 */
	public function prepare_query() {
		global $wpdb;

		$table       = noptin()->automation_rules->get_table();
		$paged       = empty( $_GET['paged'] ) ? 1 : (int) $_GET['paged'];
		$offset      = ( $paged - 1 ) * 10;
        $this->items = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d,10", $offset )
		);
		$this->total = $wpdb->get_var( "SELECT COUNT(`id`) FROM $table" );

	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 1.2.8
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
		 * Runs when displaying an automation rule's table.
		 *
		 * @param array $item The current rule.
		 */
		do_action( "noptin_display_automation_rule_table_$column_name", $item );

	}

	/**
	 * Displays the subscriber's status
	 *
	 * @param  Noptin_Automation_Rule $item item.
	 * @return HTML
	 */
	public function column_status( $item ) {

		$status = ! empty( $item->status ) ? __( 'Active', 'newsletter-optin-box' ) : __( 'Inactive', 'newsletter-optin-box' );
		$class  = ! empty( $item->status ) ? 'status-active' : 'status-inactive';

		return "<span class='$class'>$status</span>";
	}

	/**
	 * Displays the number of run times.
	 *
	 * @param  Noptin_Automation_Rule $item item.
	 * @return HTML
	 */
	public function column_times_run( $item ) {
		return $item->times_run;
	}

	/**
	 * Displays the rule's modification date
	 *
	 * @param  Noptin_Automation_Rule $item item.
	 * @return HTML
	 */
	public function column_updated_at( $item ) {
		
		$updated   = strtotime( $item->updated_at );
		$time_diff = current_time( 'timestamp' ) - $updated;

		if ( $updated && $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
			/* translators: %s: Human-readable time difference. */
			return sprintf( __( '%s ago', 'newsletter-optin-box' ), human_time_diff( $updated, current_time( 'timestamp' ) ) );
		} else {
			return date( __( 'Y/m/d g:i:s a' ), $updated );
		}

	}
	
	/**
	 * Displays the rule's creation date
	 *
	 * @param  Noptin_Automation_Rule $item item.
	 * @return HTML
	 */
	public function column_created_at( $item ) {

		$created   = strtotime( $item->created_at );
		$time_diff = current_time( 'timestamp' ) - $created;

		if ( $created && $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
			/* translators: %s: Human-readable time difference. */
			return sprintf( __( '%s ago', 'newsletter-optin-box' ), human_time_diff( $created, current_time( 'timestamp' ) ) );
		} else {
			return date( __( 'Y/m/d g:i:s a' ), $created );
		}

	}

	/**
	 * This is how checkbox column renders.
	 *
	 * @param  Noptin_Automation_Rule $item item.
	 * @return HTML
	 */
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="id[]" value="%s" />', esc_html( $item->id ) );
	}

	/**
	 * [OPTIONAL] Return array of bulk actions if has any
	 *
	 * @return array
	 */
	function get_bulk_actions() {

		$actions = array(
			'delete'     => __( 'Delete', 'newsletter-optin-box' ),
			'activate'   => __( 'Activate', 'newsletter-optin-box' ),
			'deactivate' => __( 'Deactivate', 'newsletter-optin-box' ),
		);

		/**
		 * Filters the bulk table actions shown on automation rules tables.
		 *
		 * @param array $actions An array of bulk actions.
		 */
		return apply_filters( 'manage_noptin_automation_rules_table_bulk_actions', $actions );

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

		$per_page = 10;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

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
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'trigger_id' => __( 'Trigger', 'newsletter-optin-box' ),
			'action_id'  => __( 'Action', 'newsletter-optin-box' ),
			'status'     => __( 'Status', 'newsletter-optin-box' ),
			'times_run'  => __( 'Times Run', 'newsletter-optin-box' ),
			'created_at' => __( 'Created At', 'newsletter-optin-box' ),
			'updated_at' => __( 'Updated At', 'newsletter-optin-box' ),
		);

		/**
		 * Filters the columns shown in an automation rules table.
		 *
		 * @param array $columns Automations rules table columns.
		 */
		return apply_filters( 'manage_noptin_automation_rules_table_columns', $columns );
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
		 * Filters the sortable columns in the automation rules table.
		 *
		 * @param array $sortable An array of sortable columns.
		 */
		return apply_filters( 'manage_noptin_automation_rules_sortable_table_columns', $sortable );
	}

	/**
	 * Message to be displayed when there are no items
	 */
	public function no_items() {
		$add_new_rule = add_query_arg( 'create', '1' );

		echo "<div style='min-height: 320px; display: flex; align-items: center; justify-content: center; flex-flow: column;'>";
		echo '<svg width="100" height="100" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd"><path style="fill: #039be5;" d="M6.72 20.492c1.532.956 3.342 1.508 5.28 1.508 1.934 0 3.741-.55 5.272-1.503l1.24 1.582c-1.876 1.215-4.112 1.921-6.512 1.921-2.403 0-4.642-.708-6.52-1.926l1.24-1.582zm17.28-1.492h-6c0-1.105.895-2 2-2h2c.53 0 1.039.211 1.414.586s.586.883.586 1.414zm-18 0h-6c0-1.105.895-2 2-2h2c.53 0 1.039.211 1.414.586s.586.883.586 1.414zm6-11c-3.037 0-5.5 2.462-5.5 5.5 0 3.037 2.463 5.5 5.5 5.5s5.5-2.463 5.5-5.5c0-3.038-2.463-5.5-5.5-5.5zm.306 1.833h-.612v.652c-1.188.164-1.823.909-1.823 1.742 0 1.49 1.74 1.717 2.309 1.982.776.347.632 1.069-.07 1.229-.609.137-1.387-.103-1.971-.33l-.278 1.005c.546.282 1.201.433 1.833.444v.61h.612v-.644c1.012-.142 1.834-.7 1.833-1.75 0-1.311-1.364-1.676-2.41-2.167-.635-.33-.555-1.118.355-1.171.505-.031 1.024.119 1.493.284l.221-1.007c-.554-.168-1.05-.245-1.492-.257v-.622zm8.694 2.167c1.242 0 2.25 1.008 2.25 2.25s-1.008 2.25-2.25 2.25-2.25-1.008-2.25-2.25 1.008-2.25 2.25-2.25zm-18 0c1.242 0 2.25 1.008 2.25 2.25s-1.008 2.25-2.25 2.25-2.25-1.008-2.25-2.25 1.008-2.25 2.25-2.25zm5-11.316v2.149c-2.938 1.285-5.141 3.942-5.798 7.158l-2.034-.003c.732-4.328 3.785-7.872 7.832-9.304zm8 0c4.047 1.432 7.1 4.976 7.832 9.304l-2.034.003c-.657-3.216-2.86-5.873-5.798-7.158v-2.149zm-1 6.316h-6c0-1.105.895-2 2-2h2c.53 0 1.039.211 1.414.586s.586.883.586 1.414zm-3-7c1.242 0 2.25 1.008 2.25 2.25s-1.008 2.25-2.25 2.25-2.25-1.008-2.25-2.25 1.008-2.25 2.25-2.25z"/></svg>';
		
		printf(
			/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
			__( '%1$sCreate your first automation rule%2$s', 'newsletter-optin-box' ),
			"<div style='margin-top: 40px;'><a style='font-size: 16px;' class='no-rule-create-new-automation-rule' href='$add_new_rule'>",
			'</a></div>'
		);

		echo "<p class='description'>Or <a style='color: #616161; text-decoration: underline;' href='https://noptin.com/guide/automation-rules' target='_blank'>" . __( 'Learn more', 'newsletter-optin-box' ) . "</a></p>";
		echo '</div>';
	}
	

}


