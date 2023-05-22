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

		$per_page = absint( get_user_meta( get_current_user_id(), 'noptin_subscribers_per_page', true ) );

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

		if ( 'resend_confirmation' === $action ) {

			foreach ( $_POST['id'] as $id ) {
				send_new_noptin_subscriber_double_optin_email( $id, false, true );
			}

			noptin()->admin->show_info( __( 'The selected subscribers have been sent a confirmation email.', 'newsletter-optin-box' ) );

		}
	}

	/**
	 *  Prepares the display query
	 */
	public function prepare_query() {

		$query  = array( 'meta_query' => array() );

		$filters = $this->get_selected_subscriber_filters();

		// Handle custom fields.
		foreach ( get_noptin_custom_fields() as $custom_field ) {

			// Limit to checkboxes, dropdowns, language and radio buttons.
			if ( in_array( $custom_field['type'], array( 'checkbox', 'dropdown', 'radio', 'language' ), true ) ) {

				// Fetch the appropriate filter.
				$filter = isset( $filters[ $custom_field['merge_tag'] ] ) ? $filters[ $custom_field['merge_tag'] ] : '';

				// Filter.
				if ( '' !== $filter ) {
					$query['meta_query'][] = array(
						'key'   => $custom_field['merge_tag'],
						'value' => $filter,
					);
				}
			}
		}

		// Subscription source.
		if ( ! empty( $filters['subscription_source'] ) ) {

			$query['meta_query'][] = array(
				'key'   => '_subscriber_via',
				'value' => sanitize_text_field( $filters['subscription_source'] ),
			);

		}

		// Subscriber status.
		if ( ! empty( $filters['subscription_status'] ) ) {
			$query['subscriber_status'] = sanitize_text_field( $filters['subscription_status'] );
		}

		$query_fields = array(
			'subscriber_status',
			'meta_query',
			'email_status',
			'date_query',
			'orderby',
			'order',
			'paged',
		);
		foreach ( $query_fields as $field ) {
			if ( ! empty( $_GET[ $field ] ) ) {
				$query[ $field ] = noptin_clean( urldecode_deep( $_GET[ $field ] ) );
			}
		}

		// Clean order_by.
		$custom_fields                    = $this->get_custom_fields();
		$custom_fields['_subscriber_via'] = '';

		if ( isset( $query['orderby'] ) && isset( $custom_fields[ $query['orderby'] ] ) && ! in_array( $query['orderby'], array( 'first_name', 'last_name', 'email', 'date_created', 'active' ), true ) ) {
			$query['meta_key'] = $query['orderby'];
			$query['orderby']  = 'meta_value';
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
				'value' => sanitize_text_field( urldecode( $_GET['_subscriber_via'] ) ),
			);
		}

		// Meta key.
		if ( ! empty( $_GET['meta_key'] ) ) {

			$mq = array(
				'key' => sanitize_text_field( urldecode( $_GET['meta_key'] ) ),
			);

			if ( isset( $_GET['meta_value'] ) ) {
				$mq['value'] = sanitize_text_field( urldecode( $_GET['meta_value'] ) );
			}

			if ( isset( $_GET['meta_compare'] ) ) {
				$mq['compare'] = sanitize_text_field( urldecode( $_GET['meta_compare'] ) );
			}

			$query['meta_query'][] = $mq;

		}

		// Search.
		if ( ! empty( $_POST['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$query['search'] = sanitize_text_field( urldecode( $_POST['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
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

			$value = format_noptin_custom_field_value(
				$subscriber->get( $column_name ),
				$all_fields[ $column_name ],
				$subscriber
			);

			echo is_scalar( $value ) ? wp_kses_post( $value ) : '';
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
	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="id[]" value="%s" />', esc_html( $item->id ) );
	}

	/**
	 * [OPTIONAL] Return array of bult actions if has any
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {

		$actions = array(
			'send_email'          => __( 'Send Email', 'newsletter-optin-box' ),
			'resend_confirmation' => __( 'Send Confirmation Email', 'newsletter-optin-box' ),
			'delete'              => __( 'Delete', 'newsletter-optin-box' ),
			'activate'            => __( 'Mark as active', 'newsletter-optin-box' ),
			'deactivate'          => __( 'Mark as in-active', 'newsletter-optin-box' ),
		);

		if ( use_custom_noptin_double_optin_email() ) {
			unset( $actions['resend_confirmation'] );
		}

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
			'last_name'       => array( 'last_name', false ),
		);

		foreach ( array_keys( $this->get_custom_fields() ) as $custom_field ) {
			$sortable[ $custom_field ] = array( $custom_field, false );
		}

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

	/**
	 * Returns an array of selected subscriber filters.
	 *
	 * @since 1.7.4
	 *
	 * @return array $array
	 */
	public function get_selected_subscriber_filters() {

		$action = 'bulk-' . $this->_args['plural'];

		if ( ! empty( $_POST['noptin-filters'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $action ) ) {
			return noptin_clean( $_POST['noptin-filters'] );
		}

		return array();
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @since 3.1.0
	 *
	 * @param string $which
	 */
	public function extra_tablenav( $which ) {

		// Prepare selected filters.
		$selected_filters = $this->get_selected_subscriber_filters();

		// Currently, unsubscribed subscribers are treated as pending.
		$filters = array(
			'subscription_status' => array(
				'label'   => __( 'Status', 'newsletter-optin-box' ),
				'options' => array(
					'active'   => __( 'Subscribed', 'newsletter-optin-box' ),
					'inactive' => __( 'Pending', 'newsletter-optin-box' ),
				),
			),

			'subscription_source' => array(
				'label'   => __( 'Subscribed Via', 'newsletter-optin-box' ),
				'options' => noptin_get_subscription_sources(),
			),
		);

		// Use radio, select and checkboxes as filters.
		foreach ( get_noptin_custom_fields() as $custom_field ) {

			// Checkbox
			if ( 'checkbox' === $custom_field['type'] ) {

				$filters[ $custom_field['merge_tag'] ] = array(
					'label'   => $custom_field['label'],
					'options' => array(
						'1' => __( 'Yes', 'newsletter-optin-box' ),
						'0' => __( 'No', 'newsletter-optin-box' ),
					),
				);

				// Select && Radio
			} elseif ( 'dropdown' === $custom_field['type'] || 'radio' === $custom_field['type'] ) {

				if ( ! empty( $custom_field['options'] ) ) {
					$filters[ $custom_field['merge_tag'] ] = array(
						'label'   => $custom_field['label'],
						'options' => noptin_newslines_to_array( $custom_field['options'] ),
					);
				}
			} elseif ( 'language' === $custom_field['type'] ) {

				$filters[ $custom_field['merge_tag'] ] = array(
					'label'   => $custom_field['label'],
					'options' => apply_filters( 'noptin_multilingual_active_languages', array() ),
				);
			}
		}

		?>
		<div class="alignleft actions">
			<?php if ( 'top' === $which ) : ?>

				<?php foreach ( $filters as $filter => $data ) : ?>
					<select name="noptin-filters[<?php echo esc_attr( $filter ); ?>]" id="noptin_filter_<?php echo esc_attr( $filter ); ?>">
						<option value="" <?php selected( ! isset( $selected_filters[ $filter ] ) || '' === $selected_filters[ $filter ] ); ?>><?php echo esc_html( wp_strip_all_tags( $data['label'] ) ); ?></option>
						<?php foreach ( $data['options'] as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( isset( $selected_filters[ $filter ] ) && $value === $selected_filters[ $filter ] ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php endforeach; ?>

				<?php
					do_action( 'noptin_restrict_manage_subscribers', $this, $which );

					submit_button( __( 'Filter', 'newsletter-optin-box' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
				?>
			<?php endif; ?>
		</div>
		<?php

	}

}
