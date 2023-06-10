<?php

namespace Hizzle\Store;

/**
 * Displays a list of all records.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table class.
 */
class List_Table extends \WP_List_Table {

	/**
	 * Collection
	 *
	 * @var   Collection
	 * @since 1.0.0
	 */
	public $collection;

	/**
	 * Query
	 *
	 * @var   Query
	 * @since 1.0.0
	 */
	public $query;

	/**
	 * Total records
	 *
	 * @var   int
	 * @since 1.0.0
	 */
	public $total;

	/**
	 * Per page.
	 *
	 * @var   int
	 * @since 1.0.0
	 */
	public $per_page = 25;

	/**
	 * Errors
	 *
	 * @var   \WP_Error
	 * @since 1.0.0
	 */
	public $errors;

	/**
	 * Constructor function.
	 *
	 * @param Collection $collection Collection.
	 */
	public function __construct( $collection ) {

		parent::__construct(
			array(
				'singular' => $collection->get_singular_name(),
				'plural'   => $collection->get_name(),
			)
		);

		$this->errors     = new \WP_Error();
		$this->collection = $collection;
		$this->per_page   = $this->get_items_per_page( $collection->hook_prefix( 'per_page' ), 25 );

		$this->process_bulk_action();

		$this->prepare_query();

		$this->prepare_items();
	}

	/**
	 *  Processes a bulk action.
	 */
	public function process_bulk_action() {

		$action = 'bulk-' . $this->_args['plural'];

		// Check nonce.
		if ( empty( $_POST['id'] ) || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $action ) ) {
			return false;
		}

		// Check capability.
		if ( ! current_user_can( $this->collection->capabillity ) ) {
			return false;
		}

		$action = $this->current_action();

		if ( 'delete' === $action ) {

			$deleted = 0;
			foreach ( $_POST['id'] as $id ) {
				try {
					$record = $this->collection->get( (int) $id );
					$record->delete();
					$deleted++;
				} catch ( Store_Exception $e ) {
					$this->errors->add( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
				}
			}

			if ( $deleted > 0 ) {
				// translators: %d is the number of records deleted.
				$this->errors->add( 'deleted', sprintf( _n( '%d record deleted.', '%d records deleted.', $deleted, 'hizzle-store' ), $deleted ) );
			}
		}

		do_action( $this->collection->hook_prefix( 'process_bulk_action' ), $action, $this );
	}

	/**
	 *  Prepares the display query
	 */
	public function prepare_query() {

		// Run the query.
		try {
			$this->query = $this->collection->query( $this->get_query_args() );
		} catch ( Store_Exception $e ) {
			$this->errors->add( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}

		$this->total = $this->query->get_total();
		$this->items = $this->query->get_results();

	}

	/**
	 *  Returns the query args.
	 */
	public function get_query_args() {
		$args = array(
			'page'     => $this->get_pagenum(),
			'per_page' => $this->per_page,
			'orderby'  => isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'id', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'order'    => isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'search'   => isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);

		foreach ( array_keys( $this->collection->get_props() ) as $prop ) {
			if ( isset( $_GET[ $this->collection->hook_prefix( $prop ) ] ) && '' !== $_GET[ $this->collection->hook_prefix( $prop ) ] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$args[ $prop ] = sanitize_text_field( rawurldecode( $_GET[ $this->collection->hook_prefix( $prop ) ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}

		return $args;
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 1.2.8
	 *
	 * @param \Hizzle\Noptin\DB\Automation_Rule $item The current item.
	 */
	public function single_row( $item ) {
		$class_name = str_replace( '_', '-', $this->collection->hook_prefix( $item->get_id(), true ) );

		echo '<tr class="' . esc_attr( sanitize_html_class( $class_name ) ) . '" data-id="' . absint( $item->get_id() ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';

	}

	/**
	 * Displays a column.
	 *
	 * @param Record $item        item.
	 * @param string $column_name column name.
	 */
	public function column_default( $item, $column_name ) {

		// Render value.
		$value = $item->display_prop( $column_name );

		// Allow plugins to display custom columns.
		return apply_filters( $this->collection->hook_prefix( "table_column_$column_name" ), $value, $item );
	}

	/**
	 * This is how checkbox column renders.
	 *
	 * @param  Record $item item.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="id[]" value="%s" />', esc_html( $item->get_id() ) );
	}

	/**
	 * [OPTIONAL] Return array of bulk actions if has any
	 *
	 * @return array
	 */
	public function get_bulk_actions() {

		$actions = array(
			'delete' => __( 'Delete', 'hizzle-store' ),
		);

		/**
		 * Filters the bulk table actions shown on the list table.
		 *
		 * @param array $actions An array of bulk actions.
		 */
		return apply_filters( 'manage_' . $this->collection->hook_prefix( 'table_bulk_actions' ), $actions );

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
	public function prepare_items() {

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
	 * Prints notices.
	 */
	public function display_notices() {

		foreach ( (array) $this->errors->errors as $code => $messages ) {
			foreach ( $messages as $message ) {
				$this->display_notice( $code, $message );
			}
		}

	}

	/**
	 * Prints a single admin notice.
	 */
	protected function display_notice( $code, $message ) {

		$class = sanitize_html_class( $this->collection->get_namespace() );

		if ( 'success' === $code ) {
			$class .= ' notice notice-success is-dismissible';
		} else {
			$class .= ' notice notice-error is-dismissible ' . sanitize_html_class( $code );
		}

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );

	}

	/**
	 * Fetches filters for the table.
	 *
	 */
	protected function get_filters() {

		$filters = array();

		foreach ( $this->collection->get_props() as $prop ) {

			$choices = $prop->get_choices();

			if ( ! empty( $choices ) ) {

				$filters[ $this->collection->hook_prefix( $prop->name ) ] = array_merge(
					array( '' => $prop->description ),
					$choices
				);
			}
		}

		return $filters;

	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param string $which
	 */
	public function extra_tablenav( $which ) {

		$filters = $this->get_filters();

		if ( empty( $filters ) || 'top' !== $which ) {
			return;
		}

		$args = array();
		foreach ( array_keys( $filters ) as $prop ) {
			if ( isset( $_GET[ $prop ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$args[ $prop ] = map_deep( urlencode_deep( $_GET[ $prop ] ), 'sanitize_text_field' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} else {
				$args[ $prop ] = '';
			}
		}

		?>
		<div class="alignleft actions">

			<?php foreach ( $filters as $filter => $options ) : ?>
				<select name="<?php echo esc_attr( $filter ); ?>" id="<?php echo esc_attr( $this->collection->hook_prefix( $filter ) ); ?>-filter">
					<?php foreach ( $options as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $args[ $filter ], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php endforeach; ?>

			<?php
				do_action( $this->collection->hook_prefix( 'restrict_manage_records' ), $which, $this );
				submit_button( __( 'Filter', 'hizzle-store' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
			?>

		</div>
		<?php

	}
}
