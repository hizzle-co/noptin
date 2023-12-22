<?php
/**
 * Displays a list of all emails
 */

namespace Hizzle\Noptin\Emails\Admin;

use Hizzle\Noptin\Emails\Email;

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Email list table class.
 */
class Table extends \WP_List_Table {

	/**
	 * Current email type.
	 *
	 * @var   \Hizzle\Noptin\Emails\Type
	 * @since 2.3.0
	 */
	public $email_type;

	/**
	 * Number of items per page.
	 *
	 * @var   int
	 * @since 2.3.0
	 */
	public $per_page;

	/**
	 * Query
	 *
	 * @var   \WP_Query
	 * @since 1.1.2
	 */
	public $query;

	/**
	 * Constructor function.
	 *
	 * @param \Hizzle\Noptin\Emails\Type $email_type email type.
	 */
	public function __construct( $email_type ) {

		$this->email_type = $email_type;
		$this->per_page   = $this->get_items_per_page( 'noptin_emails_per_page', 25 );

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

		// Prepare query params.
		$orderby = empty( $_GET['orderby'] ) ? 'id' : sanitize_text_field( $_GET['orderby'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order   = empty( $_GET['order'] ) ? 'desc' : sanitize_text_field( $_GET['order'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$query_args = array(
			'post_type'      => 'noptin-campaign',
			'post_status'    => array( 'pending', 'draft', 'future', 'publish' ),
			'meta_key'       => 'campaign_type',
			'meta_value'     => $this->email_type->type,
			'orderby'        => $orderby,
			'order'          => $order,
			'posts_per_page' => $this->per_page,
			'paged'          => $this->get_pagenum(),
		);

		if ( isset( $_GET['noptin_parent_id'] ) ) {
			$query_args['post_parent'] = intval( $_GET['noptin_parent_id'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		$query_args = apply_filters( 'manage_noptin_emails_wp_query_args', $query_args, $this );

		$noptin_campaigns_query = new \WP_Query( $query_args );
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
	 * Displays the campaign name
	 *
	 * @param  Email $item item.
	 * @return string
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

		if ( ! $item->is_mass_mail() || 'post_notifications' === $item->get_sub_type() ) {
			unset( $row_actions['send'] );
		}

		// Sent newsletters are not editable.
		if ( 'newsletter' === $this->email_type->type && $item->is_published() ) {
			unset( $row_actions['send'] );
		}

		$title    = esc_html( $item->name );
		$edit_url = esc_url( $item->get_edit_url() );
		$title    = "<div><strong><a href='$edit_url'>$title</a></strong></div>";

		// About automation.
		if ( 'automation' === $this->email_type->type ) {
			$description = wp_kses_post( apply_filters( 'noptin_automation_table_about_' . $item->type, '', $item, $this ) );

			$rule = noptin_get_automation_rule( absint( $item->get( 'automation_rule' ) ) );

			if ( $item->is_automation_rule() && ! is_wp_error( $rule ) && $rule->exists() ) {
				$trigger = $rule->get_trigger();

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
	 * @param  Email $item item.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="id[]" value="%s" />', esc_html( $item->id ) );
	}

	/**
	 * Displays the campaign status
	 *
	 * @param  Email $item item.
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
				$class  = 'noptin-badge success';

				if ( 'newsletter' === $this->email_type->type ) {
					$status  = __( 'Sending', 'newsletter-optin-box' );
					$status .= '&mdash;<a class="noptin-stop-campaign" href="#" data-id="' . $item->id . '">' . __( 'stop', 'newsletter-optin-box' ) . '</a>';
				} else {
					$status = __( 'Active', 'newsletter-optin-box' );
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
	 * Displays the email's date sent day
	 *
	 * @param  Email $item item.
	 * @return void
	 */
	public function column_date_sent( $item ) {
		return noptin_format_date( $item->created );
	}

	/**
	 * Displays the campaign recipients
	 *
	 * @param  Email $item item.
	 * @return int
	 */
	public function column_recipients( $item ) {
		$total = (int) get_post_meta( $item->id, '_noptin_sends', true ) + (int) get_post_meta( $item->id, '_noptin_fails', true );
		return apply_filters( 'noptin_email_recipients', $total, $item );
	}

	/**
	 * Displays the campaign type
	 *
	 * @param  Email $item item.
	 * @return string
	 */
	public function column_type( $item ) {

		$sub_types = $this->email_type->get_sub_types();

		if ( isset( $sub_types[ $item->get_sub_type() ] ) ) {
			return $sub_types[ $item->get_sub_type() ];
		}

		return __( 'Unknown', 'newsletter-optin-box' );
	}

	/**
	 * Displays the campaign opens
	 *
	 * @param  Email $item item.
	 * @return string
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
	 * @param  Email $item item.
	 * @return string
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
	 * @param  Email $item item.
	 * @return string
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
	 * @return string
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
			$post = new Email( $post->ID );
			$this->single_row( $post );
		}
	}

	/**
	 * Fetch data from the database to render on view.
	 */
	public function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$this->set_pagination_args(
			array(
				'total_items' => $this->query->found_posts,
				'per_page'    => $this->per_page,
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
				$email = new Email( $id );
				$email->delete();
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
			'title'        => __( 'Campaign', 'newsletter-optin-box' ),
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

		// Sub types.
		$sub_types = $this->email_type->get_sub_types();
		if ( empty( $sub_types ) ) {
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
			esc_url( add_query_arg( 'noptin_campaign', 0 ) ),
			esc_html__( 'New Campaign', 'newsletter-optin-box' )
		);

		// If this is a sub type, add a link to go back to the main type.
		if ( ! empty( $this->email_type->parent_type ) ) {
			printf(
				'<a class="button" href="%s">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'noptin_email_type' => rawurlencode( $this->email_type->parent_type ),
						),
						admin_url( '/admin.php?page=noptin-email-campaigns' )
					)
				),
				esc_html__( 'Back', 'newsletter-optin-box' )
			);
		}
	}

	/**
	 * Message to be displayed when there are no items
	 */
	public function no_items() {

		echo "<div style='min-height: 320px; display: flex; align-items: center; justify-content: center; flex-flow: column;'>";
		echo '<svg width="100" height="100" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd"><path style="fill: #039be5;" d="M6.72 20.492c1.532.956 3.342 1.508 5.28 1.508 1.934 0 3.741-.55 5.272-1.503l1.24 1.582c-1.876 1.215-4.112 1.921-6.512 1.921-2.403 0-4.642-.708-6.52-1.926l1.24-1.582zm17.28-1.492h-6c0-1.105.895-2 2-2h2c.53 0 1.039.211 1.414.586s.586.883.586 1.414zm-18 0h-6c0-1.105.895-2 2-2h2c.53 0 1.039.211 1.414.586s.586.883.586 1.414zm6-11c-3.037 0-5.5 2.462-5.5 5.5 0 3.037 2.463 5.5 5.5 5.5s5.5-2.463 5.5-5.5c0-3.038-2.463-5.5-5.5-5.5zm.306 1.833h-.612v.652c-1.188.164-1.823.909-1.823 1.742 0 1.49 1.74 1.717 2.309 1.982.776.347.632 1.069-.07 1.229-.609.137-1.387-.103-1.971-.33l-.278 1.005c.546.282 1.201.433 1.833.444v.61h.612v-.644c1.012-.142 1.834-.7 1.833-1.75 0-1.311-1.364-1.676-2.41-2.167-.635-.33-.555-1.118.355-1.171.505-.031 1.024.119 1.493.284l.221-1.007c-.554-.168-1.05-.245-1.492-.257v-.622zm8.694 2.167c1.242 0 2.25 1.008 2.25 2.25s-1.008 2.25-2.25 2.25-2.25-1.008-2.25-2.25 1.008-2.25 2.25-2.25zm-18 0c1.242 0 2.25 1.008 2.25 2.25s-1.008 2.25-2.25 2.25-2.25-1.008-2.25-2.25 1.008-2.25 2.25-2.25zm5-11.316v2.149c-2.938 1.285-5.141 3.942-5.798 7.158l-2.034-.003c.732-4.328 3.785-7.872 7.832-9.304zm8 0c4.047 1.432 7.1 4.976 7.832 9.304l-2.034.003c-.657-3.216-2.86-5.873-5.798-7.158v-2.149zm-1 6.316h-6c0-1.105.895-2 2-2h2c.53 0 1.039.211 1.414.586s.586.883.586 1.414zm-3-7c1.242 0 2.25 1.008 2.25 2.25s-1.008 2.25-2.25 2.25-2.25-1.008-2.25-2.25 1.008-2.25 2.25-2.25z"/></svg>';

		echo '<div style="margin-top: 40px; text-align: center;"><p class="description" style="font-size: 16px;">';
		esc_html_e( 'Click the button below to add your first email.', 'newsletter-optin-box' );
		echo '</p>';

		printf(
			/* Translators: %1$s Opening link tag, %2$s Closing link tag. */
			esc_html__( '%1$sAdd email%2$s', 'newsletter-optin-box' ),
			"<p><a style='margin: 20px auto;' class='no-rule-create-new-automation-rule button button-primary' href='" . esc_url( add_query_arg( 'noptin_campaign', '0' ) ) . "'>",
			'</a></p>'
		);

		echo '</div></div>';
	}
}
