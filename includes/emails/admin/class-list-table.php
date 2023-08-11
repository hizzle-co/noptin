<?php
/**
 * Displays a list of all emails
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Email list table class.
 */
class Noptin_Email_List_Table extends WP_List_Table {

	/**
	 * Current collection.
	 *
	 * @var   string
	 * @since 1.7.0
	 */
	public $collection_type = 'newsletter'; // newsletter, automation

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

		if ( isset( $_GET['section'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->collection_type = rtrim( sanitize_key( $_GET['section'] ), 's' );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$this->prepare_query();

		parent::__construct(
			array(
				'singular' => 'id',
				'plural'   => 'ids',
			)
		);

	}

	/**
	 *  Prepares the display query
	 */
	public function prepare_query() {
		global $noptin_campaigns_query;

		// Campaigns to display on every page.
		$per_page = 10;

		// Prepare query params.
		$paged   = empty( $_GET['paged'] ) ? 1 : (int) $_GET['paged']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby = empty( $_GET['orderby'] ) ? 'id' : sanitize_text_field( $_GET['orderby'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order   = empty( $_GET['order'] ) ? 'desc' : sanitize_text_field( $_GET['order'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$query_args = array(
			'post_type'      => 'noptin-campaign',
			'post_status'    => array( 'pending', 'draft', 'future', 'publish' ),
			'meta_key'       => 'campaign_type',
			'meta_value'     => $this->collection_type,
			'orderby'        => $orderby,
			'order'          => $order,
			'posts_per_page' => $per_page,
			'paged'          => $paged,
		);
		$query_args = apply_filters( 'manage_noptin_emails_wp_query_args', $query_args, $this );

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
		do_action( "noptin_display_emails_table_$column_name", $item, $this );

	}

	/**
	 * Displays the newsletter name
	 *
	 * @param  Noptin_Newsletter_Email|Noptin_Automated_Email $item item.
	 * @return HTML
	 */
	public function column_title( $item ) {

		// Prepare row actions.
		$row_actions = array(

			'edit'      => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $item->get_edit_url() ),
				esc_html__( 'Edit', 'newsletter-optin-box' )
			),

			'duplicate' => sprintf(
				'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
				$item->get_duplication_url(), // This is alread escaped via wp_nonce_url.
				esc_attr__( 'Are you sure you want to duplicate this campaign?', 'newsletter-optin-box' ),
				esc_html__( 'Duplicate', 'newsletter-optin-box' )
			),

			'_preview'  => sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( $item->get_preview_url() ),
				esc_html__( 'Preview', 'newsletter-optin-box' )
			),

			'send'      => sprintf(
				'<a href="%s" style="color: green;" onclick="return confirm(\'%s\');">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'noptin_admin_action' => 'noptin_force_send_campaign',
							'noptin_nonce'        => wp_create_nonce( 'noptin_force_send_campaign' ),
						),
						$item->get_edit_url()
					)
				),
				esc_attr__( 'Are you sure you want to send this campaign?', 'newsletter-optin-box' ),
				esc_html__( 'Send Now', 'newsletter-optin-box' )
			),

			'delete'    => sprintf(
				'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
				$item->get_delete_url(), // This is alread escaped via wp_nonce_url.
				esc_attr__( 'Are you sure you want to delete this campaign?', 'newsletter-optin-box' ),
				esc_html__( 'Delete', 'newsletter-optin-box' )
			),

		);

		// Sent newsletters are not editable.
		if ( 'newsletter' === $this->collection_type && $item->is_published() ) {
			unset( $row_actions['edit'] );
			unset( $row_actions['send'] );
			$edit_url = $item->get_preview_url();
		} else {
			$edit_url = $item->get_edit_url();

			if ( 'automation' === $this->collection_type ) {

				if ( ! $item->is_published() || 'post_digest' !== $item->type ) {
					unset( $row_actions['send'] );
				}
			}
		}

		$title = $item->get( 'custom_title' );
		$title = empty( $title ) ? esc_html( $item->name ) : $title;

		$title = "<div><strong><a href='$edit_url'>$title</a></strong></div>";

		// About automation.
		if ( 'automation' === $this->collection_type ) {
			$description = wp_kses_post( apply_filters( 'noptin_automation_table_about_' . $item->type, '', $item, $this ) );

			$rule = new Noptin_Automation_Rule( absint( $item->get( 'automation_rule' ) ) );

			if ( $item->is_automation_rule() && $rule->exists() ) {
				$trigger = noptin()->automation_rules->get_trigger( $rule->trigger_id );

				if ( $trigger ) {
					$description .= '<br />' . $trigger->get_rule_table_description( $rule );
				}
			}

			if ( ! empty( $description ) ) {
				$title .= "<p class='description'>$description</div>";
			}

			if ( ! $item->sends_immediately() ) {

				$title .= sprintf(
					'<br /><span class="noptin-rule-meta" style="color: green;font-weight: 600;"><span class="noptin-rule-meta-key">%s</span>: <span class="noptin-rule-meta-value">%s</span></span>',
					esc_html__( 'Delay', 'newsletter-optin-box' ),
					esc_html( $item->get_sends_after() . ' ' . $item->get_sends_after_unit( true ) )
				);
			}
		} elseif ( $item->is_published() && ! get_post_meta( $item->id, 'completed', true ) ) {

			$error = get_post_meta( $item->id, '_bulk_email_last_error', true );

			if ( is_array( $error ) ) {
				$title .= sprintf(
					'<p class="description" style="color: red;">%s</p>',
					sprintf(
						// translators: %s is the error message.
						__( 'An error occurred while sending this campaign. The last error was: %s', 'newsletter-optin-box' ),
						sprintf(
							// translators: %1$s is the error type, %2$s is the error message, %3$s is the error file and %4$s is the error line.
							__( '%1$s: %2$s in %3$s on line %4$s', 'newsletter-optin-box' ),
							'<strong>Error</strong>',
							esc_html( $error['message'] ),
							isset( $error['file'] ) ? esc_html( $error['file'] ) : 'Uknown',
							isset( $error['line'] ) ? esc_html( $error['line'] ) : 'Uknown'
						)
					)
				);
				delete_post_meta( $item->id, '_bulk_email_last_error' );
			}
		}

		// Row actions.
		$row_actions = apply_filters( 'noptin_email_row_actions', $row_actions, $item, $this );
		if ( ! empty( $row_actions ) ) {
			$title .= '<div class="row-actions">' . $this->row_actions( $row_actions ) . '</div>';
		}

		return $title;
	}

	/**
	 * This is how checkbox column renders.
	 *
	 * @param  Noptin_Newsletter_Email|Noptin_Automated_Email $item item.
	 * @return HTML
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="id[]" value="%s" />', esc_html( $item->id ) );
	}

	/**
	 * Displays the campaign status
	 *
	 * @param  Noptin_Newsletter_Email|Noptin_Automated_Email $item item.
	 * @return void
	 */
	public function column_status( $item ) {
		$status = __( 'Draft', 'newsletter-optin-box' );
		$class  = 'noptin-badge';

		if ( 'future' === $item->status ) {
			$status = __( 'Scheduled', 'newsletter-optin-box' );
			$class  = 'noptin-badge notification';
		}

		if ( 'publish' === $item->status ) {

			if ( get_post_meta( $item->id, 'completed', true ) ) {
				$status = __( 'Sent', 'newsletter-optin-box' );
				$class  = 'noptin-badge info';
			} else {
				$status = __( 'Sending', 'newsletter-optin-box' );
				$class  = 'noptin-badge success';

				if ( 'newsletter' === $this->collection_type ) {
					$status .= '&mdash;<a class="noptin-stop-campaign" href="#" data-id="' . $item->id . '">' . __( 'stop', 'newsletter-optin-box' ) . '</a>';
				}
			}
		}

		$status = apply_filters( 'noptin_admin_table_email_status', $status, $item );

		printf(
			'<span class="%s">%s</span>',
			esc_attr( $class ),
			wp_kses_post( $status )
		);
	}

	/**
	 * Displays the newsletter's date sent day
	 *
	 * @param  Noptin_Newsletter_Email $item item.
	 * @return void
	 */
	public function column_date_sent( $item ) {

		if ( 'future' === $item->status ) {

			// In case CRON is not working.
			if ( strtotime( $item->created ) < current_time( 'timestamp' ) ) {
				wp_publish_post( $item );
			}
		}

		printf(
			'<abbr title="%s" class="noptin-email-date-sent">%s</abbr>',
			esc_attr( $item->created ),
			esc_html( date_i18n( get_option( 'date_format' ), strtotime( $item->created ) ) )
		);

	}

	/**
	 * Displays the campaign recipients
	 *
	 * @param  Noptin_Newsletter_Email|Noptin_Automated_Email $item item.
	 * @return int
	 */
	public function column_recipients( $item ) {
		$total = (int) get_post_meta( $item->id, '_noptin_sends', true ) + (int) get_post_meta( $item->id, '_noptin_fails', true );
		return apply_filters( 'noptin_email_recipients', $total, $item );
	}

	/**
	 * Displays the campaign type
	 *
	 * @param  Noptin_Automated_Email $item item.
	 * @return HTML
	 */
	public function column_type( $item ) {

		if ( isset( noptin()->emails->automated_email_types->types[ $item->type ] ) ) {
			return noptin()->emails->automated_email_types->types[ $item->type ]->get_name();
		}

		return __( 'Unknown', 'newsletter-optin-box' );
	}

	/**
	 * Displays the campaign opens
	 *
	 * @param  Noptin_Newsletter_Email|Noptin_Automated_Email $item item.
	 * @return HTML
	 */
	public function column_opens( $item ) {

		$sends   = $this->column_recipients( $item );
		$opens   = (int) get_post_meta( $item->id, '_noptin_opens', true );
		$percent = ( $sends && $opens ) ? round( ( $opens / $sends ) * 100, 2 ) : 0;

		return $this->display_stat(
			apply_filters( 'noptin_email_opens', $opens, $item ),
			$percent
		);
	}

	/**
	 * Displays the campaign clicks
	 *
	 * @param  Noptin_Newsletter_Email|Noptin_Automated_Email $item item.
	 * @return HTML
	 */
	public function column_clicks( $item ) {

		$sends   = $this->column_recipients( $item );
		$clicks  = (int) get_post_meta( $item->id, '_noptin_clicks', true );
		$percent = ( $sends && $clicks ) ? round( ( $clicks / $sends ) * 100, 2 ) : 0;

		return $this->display_stat(
			apply_filters( 'noptin_email_clicks', $clicks, $item ),
			$percent
		);
	}

	/**
	 * Displays the campaign unsubscribes
	 *
	 * @param  Noptin_Newsletter_Email|Noptin_Automated_Email $item item.
	 * @return HTML
	 */
	public function column_unsubscribed( $item ) {

		$sends        = $this->column_recipients( $item );
		$unsubscribed = (int) get_post_meta( $item->id, '_noptin_unsubscribed', true );
		$percent      = ( $sends && $unsubscribed ) ? round( ( $unsubscribed / $sends ) * 100, 2 ) : 0;

		return $this->display_stat(
			apply_filters( 'noptin_email_unsubscribed', $unsubscribed, $item ),
			$percent
		);

	}

	/**
	 * Displays a stat with percentage
	 *
	 * @param  int $value value.
	 * @param  float $percent total.
	 * return HTML
	 */
	public function display_stat( $value, $percent ) {

		if ( $percent > 0 && $percent < 100 ) {
			return sprintf(
				'<div class="noptin-stat">%s</div><p class="noptin-stat-percent">%s%%</p>',
				$value,
				$percent
			);
		}

		return $value;
	}

	/**
	 * [OPTIONAL] Return array of bult actions if has any
	 *
	 * @return array
	 */
	public function get_bulk_actions() {

		$actions = array(
			'delete' => __( 'Delete', 'newsletter-optin-box' ),
		);
		return apply_filters( 'manage_noptin_emails_table_bulk_actions', $actions );

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
			$post = noptin_get_email_campaign_object( $post->ID );
			if ( empty( $post ) ) {
				continue;
			}

			$this->single_row( $post );
		}
	}

	/**
	 * Fetch data from the database to render on view.
	 */
	public function prepare_items() {

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
				wp_delete_post( intval( $id ), true );
			}

			noptin()->admin->show_info( __( 'The selected campaigns have been deleted.', 'newsletter-optin-box' ) );

		}

	}

	/**
	 * Table columns.
	 *
	 * @return array
	 */
	public function get_columns() {

		// Prepare columns.
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'title'        => 'newsletter' === $this->collection_type ? __( 'Email Subject', 'newsletter-optin-box' ) : __( 'Name', 'newsletter-optin-box' ),
			'type'         => __( 'Type', 'newsletter-optin-box' ),
			'status'       => __( 'Status', 'newsletter-optin-box' ),
			'recipients'   => __( 'Sent', 'newsletter-optin-box' ),
			'opens'        => __( 'Opened', 'newsletter-optin-box' ),
			'clicks'       => __( 'Clicked', 'newsletter-optin-box' ),
			'unsubscribed' => __( 'Unsubscribed', 'newsletter-optin-box' ),
			'date_sent'    => __( 'Date', 'newsletter-optin-box' ),
		);

		// Remove tracking stats.
		$track_campaign_stats = get_noptin_option( 'track_campaign_stats', true );

		if ( empty( $track_campaign_stats ) ) {
			unset( $columns['opens'] );
			unset( $columns['clicks'] );
		}

		// Remove automation details for newsletters.
		if ( 'automation' !== $this->collection_type ) {
			unset( $columns['type'] );
		} else {
			unset( $columns['date_sent'] );
		}

		return apply_filters( 'manage_noptin_emails_table_columns', $columns, $this );
	}

	/**
	 * Table sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable = array(
			'id'        => array( 'id', true ),
			'title'     => array( 'post_title', true ),
			'date_sent' => array( 'post_date', true ),
		);
		return apply_filters( 'manage_noptin_emails_sortable_table_columns', $sortable );
	}

	/**
     * Extra controls to be displayed between bulk actions and pagination
     *
     * @since 3.1.0
     * @access protected
     */
    public function extra_tablenav( $which ) {

		printf(
			'<a class="button button-primary" href="%s">%s</a>',
			esc_url(
				add_query_arg(
					array(
						'page'        => 'noptin-email-campaigns',
						'section'     => $this->collection_type . 's',
						'sub_section' => 'new_campaign',
					),
					admin_url( '/admin.php' )
				)
			),
			esc_html__( 'New Campaign', 'newsletter-optin-box' )
		);

	}

}
