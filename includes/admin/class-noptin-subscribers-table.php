<?php

/**
 * Email subscribers table class.
 */
class Noptin_Subscribers_Table extends \Hizzle\Store\List_Table {

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
	 * Constructor function.
	 *
	 */
	public function __construct() {
		parent::__construct( \Hizzle\Store\Collection::instance( 'noptin_subscribers' ) );
	}

	/**
	 * Displays the subscriber column.
	 *
	 * @param  \Hizzle\Noptin\DB\Subscriber $item item.
	 * @return string
	 */
	public function column_email( $item ) {

		// Fetch email.
		$email = $item->get_email();

		if ( ! is_string( $email ) || ! is_email( $email ) ) {
			$email  = __( '(no email)', 'newsletter-optin-box' );
			$avatar = '<span class="dashicons dashicons-admin-users"></span>';
		} else {
			$avatar = get_avatar( $email, 32, '', $email );
		}

		return sprintf(
			'<div class="noptin-wrap-primary">
				<div class="noptin-record-image">%s</div>
				<div class="noptin-record-name">
					<div class="row-title">
						<a href="%s">#%s %s</a>
					</div>
				</div>
			</div>',
			$avatar,
			esc_url( $item->get_edit_url() ),
			(int) $item->get_id(),
			esc_html( $email )
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

		$columns['source']       = __( 'Subscribed Via', 'newsletter-optin-box' );
		$columns['date_created'] = __( 'Added', 'newsletter-optin-box' );

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
			'source'       => array( 'source', false ),
			'status'       => array( 'active', false ),
			'email'        => array( 'email', false ),
			'first_name'   => array( 'first_name', false ),
			'last_name'    => array( 'last_name', false ),
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
