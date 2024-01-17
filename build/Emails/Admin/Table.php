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
		global $current_screen;

		if ( $current_screen ) {
			$current_screen->post_type = 'noptin-campaign';
		}

		$this->email_type = $email_type;
		$this->per_page   = $this->get_items_per_page( 'noptin_emails_per_page', 25 );

		$this->prepare_query();

		parent::__construct(
			array(
				'screen'   => $current_screen,
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

		$post_type_object = get_post_type_object( 'noptin-campaign' );

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

		if ( isset( $_GET['post_status'] ) ) {
			$query_args['post_status'] = sanitize_text_field( $_GET['post_status'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$query_args = apply_filters( 'manage_noptin_emails_wp_query_args', $query_args, $this );

		// If the current user can't edit others' campaigns, only show their own.
		if ( ! current_user_can( $post_type_object->cap->edit_others_posts ) ) {
			$query_args['author'] = get_current_user_id();
		}

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
				esc_url( $item->get_action_url( 'duplicate_campaign' ) ),
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
				esc_url( $item->get_action_url( 'force_send_campaign' ) ),
				esc_attr__( 'Are you sure you want to send this campaign?', 'newsletter-optin-box' ),
				esc_html__( 'Send Now', 'newsletter-optin-box' )
			),

			'delete'    => sprintf(
				'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
				esc_url( $item->get_action_url( 'delete_campaign' ) ),
				esc_attr__( 'Are you sure you want to delete this campaign?', 'newsletter-optin-box' ),
				esc_html__( 'Delete', 'newsletter-optin-box' )
			),

		);

		if ( ! $item->current_user_can_edit() ) {
			unset( $row_actions['edit'] );
			unset( $row_actions['duplicate'] );
			unset( $row_actions['send'] );
		}

		if ( ! $item->current_user_can_delete() ) {
			unset( $row_actions['delete'] );
		}

		// Sent newsletters are not editable.
		if ( 'newsletter' === $this->email_type->type && $item->is_published() ) {
			unset( $row_actions['send'] );
		}

		$title    = esc_html( $item->name );
		$edit_url = esc_url( $item->current_user_can_edit() ? $item->get_edit_url() : $item->get_preview_url() );
		$title    = "<strong><a href='$edit_url'>$title</a></strong>";

		// Email type.
		$sub_types = $this->email_type->get_sub_types();

		if ( ! empty( $sub_types ) ) {
			if ( isset( $sub_types[ $item->get_sub_type() ] ) ) {

				$title .= sprintf(
					'<p class="description">%s</p>',
					esc_html( $sub_types[ $item->get_sub_type() ]['description'] )
				);

				if ( $item->name !== $sub_types[ $item->get_sub_type() ]['label'] ) {
					$title .= sprintf(
						'<div><span class="noptin-strong">%s</span>: <span>%s</span></div>',
						esc_html__( 'Type', 'newsletter-optin-box' ),
						esc_html( $sub_types[ $item->get_sub_type() ]['label'] )
					);
				}
			} else {
				$title .= sprintf(
					'<div class="noptin-text-error">%s</div>',
					esc_html__( 'Unknown type', 'newsletter-optin-box' )
				);
			}
		}

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

			if ( ! $item->is_mass_mail() ) {
				$recipients = $item->get_recipients();

				if ( empty( $recipients ) ) {
					$title .= '<p class="description" style="color: red;">' . esc_html__( 'No recipients set.', 'newsletter-optin-box' ) . '</p>';
				} else {
					$title .= sprintf(
						'<div><span class="noptin-strong">%s</span>: <span>%s</span></div>',
						esc_html__( 'Recipients', 'newsletter-optin-box' ),
						esc_html( implode( ', ', noptin_parse_list( $recipients, true ) ) )
					);
				}
			}

			if ( ! $item->sends_immediately() ) {

				$title .= sprintf(
					'<div class="noptin-strong noptin-text-success"><span>%s</span>: <span>%s</span></div>',
					esc_html__( 'Delay', 'newsletter-optin-box' ),
					esc_html( $item->get_sends_after() . ' ' . $item->get_sends_after_unit( true ) )
				);
			}
		} elseif ( ! get_post_meta( $item->id, 'completed', true ) ) {

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

		$title = '<div class="noptin-v-stack">' . $title . '</div>';

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
		$statuses = get_post_statuses();

		$app = array(
			'unpublishURL' => $item->get_action_url( 'unpublish_campaign' ),
			'publishURL'   => $item->get_action_url( 'publish_campaign' ),
			'canPublish'   => 'publish' !== $item->status && current_user_can( 'publish_post', $item->id ),
			'canUnpublish' => 'publish' === $item->status && $item->current_user_can_edit(),
			'canEdit'      => $item->current_user_can_edit(),
			'id'           => $item->id,
			'status'       => $item->status,
			'label'        => isset( $statuses[ $item->status ] ) ? $statuses[ $item->status ] : $item->status,
			'emailType'    => $this->email_type->type,
			'completed'    => ! ! get_post_meta( $item->id, 'completed', true ),
		);

		?>
			<div class="noptin-email-status__app" data-app="<?php echo esc_attr( wp_json_encode( $app ) ); ?>">
				<!-- spinner -->
				<span class="spinner" style="visibility: visible; float: none;"></span>
				<!-- /spinner -->
			</div>
		<?php
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

		$action = $this->current_action();

		if ( 'delete' === $action ) {

			foreach ( $_POST['id'] as $id ) {
				$email = new Email( $id );
				if ( $email->current_user_can_delete() ) {
					$email->delete();
				}
			}
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

		if ( $this->has_items() ) {
			echo '<span class="noptin-email-campaigns__editor--add-new__button"></span>';
		}

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
		?>
			<div id="noptin-email-campaigns__editor--add-new__in-table">
				<?php parent::no_items(); ?>
				<!-- spinner -->
				<span class="spinner" style="visibility: visible; float: none;"></span>
				<!-- /spinner -->
			</div>
		<?php
	}
}
