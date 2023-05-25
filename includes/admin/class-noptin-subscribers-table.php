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
	 * Displays available actions.
	 *
	 * @param  \Hizzle\Noptin\DB\Subscriber $item item.
	 * @return string
	 */
	public function column_actions( $item ) {

		$actions = array(
			'edit'   => array(
				'url'   => $item->get_edit_url(),
				'label' => __( 'Edit', 'newsletter-optin-box' ),
				'icon'  => 'dashicons dashicons-edit',
			),
			'delete' => array(
				'label' => __( 'Delete', 'newsletter-optin-box' ),
				'icon'  => 'dashicons dashicons-trash',
			),
		);

		$html = '';

		foreach ( $actions as $action => $data ) {

			$html .= sprintf(
				'<a href="%s" title="%s" class="noptin-tip noptin-record-action noptin-record-action__%s">%s</a>',
				empty( $data['url'] ) ? '#' : esc_url( $data['url'] ),
				empty( $data['label'] ) ? '' : esc_attr( $data['label'] ),
				esc_attr( $action ),
				sprintf(
					'<span class="%s" aria-label="%s"></span>',
					empty( $data['icon'] ) ? 'dashicons dashicons-admin-generic' : esc_attr( $data['icon'] ),
					empty( $data['label'] ) ? '' : esc_attr( $data['label'] )
				)
			);

		}

		$status = sprintf(
			'<label class="noptin-record-action__switch-wrapper noptin-tip" title="%s">
				<input type="checkbox" class="noptin-toggle-subscription-status" %s>
				<span class="noptin-record-action__switch"></span>
			</label>',
			esc_attr( __( 'Activate or deactivate this subscriber', 'newsletter-optin-box' ) ),
			checked( ! empty( $item->is_active() ), true, false )
		);

		return '<div class="noptin-record-actions">' . $status . $html . '</div>';

	}

	/**
	 * [OPTIONAL] Return array of bulk actions if has any
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array();
	}

	/**
	 * Table columns.
	 *
	 * @return array
	 */
	public function get_columns() {

		$columns = array_merge(
			array(
				'email' => __( 'Email', 'newsletter-optin-box' ),
			),
			$this->get_custom_fields(),
			array(
				'status'       => __( 'Status', 'newsletter-optin-box' ),
				'source'       => __( 'Source', 'newsletter-optin-box' ),
				'date_created' => __( 'Added', 'newsletter-optin-box' ),
				'actions'      => __( 'Actions', 'newsletter-optin-box' ),
			)
		);

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
			'status'       => array( 'status', false ),
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

}
