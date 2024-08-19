<?php
/**
 * Displays a list of all emails
 */

namespace Hizzle\Noptin\Emails\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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

		if ( $this->email_type->supports_menu_order ) {
			$this->per_page = 1000;
		}

		parent::__construct(
			array(
				'screen'   => $current_screen,
				'singular' => 'id',
				'plural'   => 'ids',
			)
		);

		$this->process_bulk_action();
		$this->prepare_query();
	}

	/**
	 *  Prepares the display query
	 */
	public function prepare_query() {
		global $noptin_campaigns_query;

		if ( $this->email_type->upsell ) {
			return;
		}

		$post_type_object = get_post_type_object( 'noptin-campaign' );

		// Prepare query params.
		$orderby = empty( $_GET['orderby'] ) ? 'id' : sanitize_text_field( $_GET['orderby'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order   = empty( $_GET['order'] ) ? 'desc' : sanitize_text_field( $_GET['order'] );  // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $this->email_type->supports_menu_order ) {
			$orderby = 'menu_order';
			$order   = 'asc';
		}

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

		// Trash campaigns.
		if ( 'trash' === $this->email_type->type ) {
			$query_args['post_status'] = 'trash';
			unset( $query_args['meta_key'], $query_args['meta_value'] );
		}

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
	 * Generates content for a single row of the table.
	 *
	 * @since 3.1.0
	 *
	 * @param  Email $item The current item
	 */
	public function single_row( $item ) {
		echo '<tr data-id="' . esc_attr( $item->id ) . '" id="noptin-email-campaign--' . esc_attr( $item->id ) . '">';
		$this->single_row_columns( $item );
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
		 * Displays a given column
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_display_emails_table_column', $column_name, $item, $this );

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

		$preview_url = $item->get_preview_url();

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
				esc_url( $preview_url ),
				esc_html__( 'Preview', 'newsletter-optin-box' )
			),

			'send'      => sprintf(
				'<a href="%s" style="color: green;" onclick="return confirm(\'%s\');">%s</a>',
				esc_url( $item->get_action_url( 'force_send_campaign' ) ),
				esc_attr__( 'Are you sure you want to send this campaign?', 'newsletter-optin-box' ),
				esc_html__( 'Send Now', 'newsletter-optin-box' )
			),

			'trash'     => sprintf(
				'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
				esc_url( $item->get_action_url( 'trash_campaign' ) ),
				esc_attr__( 'Are you sure you want to trash this campaign?', 'newsletter-optin-box' ),
				esc_html__( 'Trash', 'newsletter-optin-box' )
			),

		);

		if ( empty( $preview_url ) ) {
			unset( $row_actions['_preview'] );
		}

		if ( ! $item->current_user_can_edit() ) {
			unset( $row_actions['edit'] );
			unset( $row_actions['duplicate'] );
			unset( $row_actions['send'] );
		} elseif ( ! current_user_can( 'publish_post', $item->id ) ) {
			unset( $row_actions['send'] );
		}

		if ( ! $item->current_user_can_delete() ) {
			unset( $row_actions['delete'] );
		}

		// Sent newsletters are not editable.
		if ( ! $item->is_mass_mail() || $item->supports_timing() || ( 'newsletter' === $this->email_type->type && $item->is_published() ) ) {
			unset( $row_actions['send'] );
		}

		// If trash, only show delete and restore.
		$is_trash = 'trash' === $this->email_type->type;
		if ( $is_trash ) {
			$row_actions = array(
				'restore' => sprintf(
					'<a href="%s">%s</a>',
					esc_url( $item->get_action_url( 'restore_campaign' ) ),
					esc_html__( 'Restore', 'newsletter-optin-box' )
				),
				'delete'  => sprintf(
					'<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
					esc_url( $item->get_action_url( 'delete_campaign' ) ),
					esc_attr__( 'Are you sure you want to delete this campaign?', 'newsletter-optin-box' ),
					esc_html__( 'Delete', 'newsletter-optin-box' )
				),
			);
		}

		$item_name = $item->name;
		$sub_type  = $this->email_type->get_sub_type( $item->get_sub_type() );

		if ( empty( $item_name ) ) {
			$item_name = $item->subject;
		}

		if ( empty( $item_name ) && ! empty( $sub_type ) ) {
			$item_name = $sub_type['label'];
		}

		if ( empty( $item_name ) ) {
			$item_name = __( '(no title)', 'newsletter-optin-box' );
		}

		$title = esc_html( $item_name );

		// Don't link if trash.
		if ( $is_trash || ( empty( $preview_url ) && ! $item->current_user_can_edit() ) ) {
			$title = "<strong>$title</strong>";
		} else {
			$edit_url = esc_url( $item->current_user_can_edit() ? $item->get_edit_url() : $preview_url );
			$title    = "<strong><a href='$edit_url'>$title</a></strong>";
		}

		// Email type.
		if ( false !== $sub_type ) {
			if ( null !== $sub_type ) {
				$title .= sprintf(
					'<p class="description">%s</p>',
					esc_html( $sub_type['description'] )
				);

				// Custom description.
				$description = wp_kses_post( apply_filters( 'noptin_' . $item->type . '_table_about_' . $item->get_sub_type(), '', $item, $this ) );

				if ( ! empty( $description ) ) {
					$title .= "<div>$description</div>";
				}
			} else {
				$title .= sprintf(
					'<div class="noptin-text-error">%s</div>',
					esc_html__( 'Unknown type', 'newsletter-optin-box' )
				);
			}
		}

		// If name  is different from the subject, show the subject.
		if ( $item_name !== $item->subject && ! empty( $item->subject ) && ! empty( $preview_url ) ) {
			$title .= sprintf(
				'<div><span class="noptin-strong">%s</span>: <span>%s</span></div>',
				esc_html__( 'Subject', 'newsletter-optin-box' ),
				esc_html( $item->subject )
			);
		}

		if ( empty( $item->subject ) && ! empty( $preview_url ) ) {
			$title .= sprintf(
				'<p class="description" style="color: red;">%s</p>',
				esc_html__( 'No subject', 'newsletter-optin-box' )
			);
		}

		// Recipients.
		if ( $item->supports( 'supports_recipients' ) ) {
			$sender = $item->get_sender();

			if ( 'manual_recipients' === $item->get_sender() ) {
				$recipients = $item->get_recipients();

				if ( ! empty( $recipients ) ) {
					$title .= sprintf(
						'<div><span class="noptin-strong">%s</span>: <span>%s</span></div>',
						esc_html__( 'Recipients', 'newsletter-optin-box' ),
						esc_html( implode( ', ', noptin_parse_list( $recipients, true ) ) )
					);
				} else {
					$title .= sprintf(
						'<p class="description" style="color: red;">%s</p>',
						esc_html__( 'No recipients', 'newsletter-optin-box' )
					);
				}
			} elseif ( ! empty( $sender ) ) {
				$senders = get_noptin_email_senders();

				if ( isset( $senders[ $sender ] ) ) {
					$title .= sprintf(
						'<div><span class="noptin-strong">%s</span>: <span>%s</span></div>',
						esc_html__( 'Recipients', 'newsletter-optin-box' ),
						esc_html( $senders[ $sender ] )
					);
				} else {
					$title .= sprintf(
						'<p class="description" style="color: red;">%s</p>',
						esc_html__( 'Unknown sender', 'newsletter-optin-box' )
					);
				}
			} else {
				$title .= sprintf(
					'<p class="description" style="color: red;">%s</p>',
					esc_html__( 'No recipients', 'newsletter-optin-box' )
				);
			}
		}

		// Delay.
		if ( ! $item->sends_immediately() && ! $is_trash ) {
			$title .= sprintf(
				'<div><span class="noptin-strong">%s</span>: <span>%s</span></div>',
				esc_html__( 'Delay', 'newsletter-optin-box' ),
				esc_html( $item->get_sends_after() . ' ' . $item->get_sends_after_unit( true ) )
			);
		}

		// Error.
		if ( ! $is_trash && 'automation' === $this->email_type->type && $item->is_published() ) {
			$error = get_post_meta( $item->id, '_bulk_email_last_error', true );

			if ( is_array( $error ) ) {
				$title .= sprintf(
					'<p class="description" style="color: red;">%s</p>',
					esc_html( $error['message'] )
				);

				delete_post_meta( $item->id, '_bulk_email_last_error' );
			}
		}

		if ( ! $is_trash && 'newsletter' === $this->email_type->type && ! get_post_meta( $item->id, 'completed', true ) ) {
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

				// Add a link to help troubleshoot the error.
				$title .= sprintf(
					'<p class="noptin-strong description">%s</p>',
					sprintf(
						'<a href="%s" target="_blank" class="noptin-text-success">%s</a>',
						esc_url( 'https://noptin.com/guide/sending-emails/how-to-fix-emails-not-sending/' ),
						esc_html__( 'Learn how to troubleshoot email sending errors', 'newsletter-optin-box' )
					)
				);
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
			'status' => $item->status,
			'label'  => isset( $statuses[ $item->status ] ) ? $statuses[ $item->status ] : $item->status,
			'action' => $this->get_email_action( $item ),
		);

		if ( 'publish' === $item->status && 'newsletter' === $item->type ) {
			if ( '' !== get_post_meta( $item->id, 'completed', true ) ) {
				$app['label'] = __( 'Sent', 'newsletter-optin-box' );
			} elseif ( '' !== get_post_meta( $item->id, 'paused', true ) ) {
				$app['label'] = __( 'Paused', 'newsletter-optin-box' );
			} else {
				$app['label'] = __( 'Sending', 'newsletter-optin-box' );
			}
		}

		?>
			<div class="noptin-email-status__app" data-app="<?php echo esc_attr( wp_json_encode( $app ) ); ?>">
				<!-- spinner -->
				<span class="spinner" style="visibility: visible; float: none;"></span>
				<!-- /spinner -->
			</div>
		<?php
	}

	/**
	 * Displays the campaign status
	 *
	 * @param  Email $item item.
	 * @return array
	 */
	private function get_email_action( $item ) {

		if ( 'publish' === $item->status && current_user_can( 'publish_post', $item->id ) && 'newsletter' === $item->type ) {

			// Resend the newsletter.
			if ( '' !== get_post_meta( $item->id, 'completed', true ) ) {
				return array(
					'actionUrl'        => $item->get_action_url( 'resend_campaign' ),
					'buttonText'       => __( 'Resend', 'newsletter-optin-box' ),
					'modalTitle'       => __( 'Resend Campaign', 'newsletter-optin-box' ),
					'modalDescription' => __( 'Are you sure you want to resend this campaign?', 'newsletter-optin-box' ),
					'icon'             => 'update',
				);
			}

			// Resume a paused newsletter.
			if ( '' !== get_post_meta( $item->id, 'paused', true ) ) {
				return array(
					'actionUrl'        => $item->get_action_url( 'resend_campaign' ),
					'buttonText'       => __( 'Resume', 'newsletter-optin-box' ),
					'modalTitle'       => __( 'Resume Campaign', 'newsletter-optin-box' ),
					'modalDescription' => __( 'Are you sure you want to resume sending this campaign?', 'newsletter-optin-box' ),
					'icon'             => 'controls-play',
				);
			}

			// Pause a running newsletter.
			if ( '' === get_post_meta( $item->id, 'paused', true ) ) {
				return array(
					'actionUrl'        => $item->get_action_url( 'pause_campaign' ),
					'buttonText'       => __( 'Pause', 'newsletter-optin-box' ),
					'modalTitle'       => __( 'Pause Campaign', 'newsletter-optin-box' ),
					'modalDescription' => __( 'Are you sure you want to pause sending this campaign?', 'newsletter-optin-box' ),
					'icon'             => 'controls-pause',
				);
			}
		}

		// isDestructive
		return null;
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
		return $item->get_send_count();
	}

	/**
	 * Displays the campaign opens
	 *
	 * @param  Email $item item.
	 */
	public function column_opens( $item ) {

		$sends   = $item->get_send_count();
		$opens   = $item->get_open_count();
		$percent = ( $sends && $opens ) ? round( ( $opens / $sends ) * 100, 2 ) : 0;

		$this->display_stat(
			$opens,
			$percent
		);
	}

	/**
	 * Displays the campaign clicks
	 *
	 * @param  Email $item item.
	 */
	public function column_clicks( $item ) {

		$sends   = $item->get_send_count();
		$clicks  = $item->get_click_count();
		$percent = ( $sends && $clicks ) ? round( ( $clicks / $sends ) * 100, 2 ) : 0;

		$this->display_stat(
			$clicks,
			$percent
		);
	}

	/**
	 * Displays the campaign revenue
	 *
	 * @param  Email $item item.
	 * @return string
	 */
	public function column_revenue( $item ) {

		$revenue = (float) get_post_meta( $item->id, '_revenue', true );
		if ( noptin_has_active_license_key() ) {
			$callback  = apply_filters( 'noptin_format_price_callback', '', $revenue );
			$formatted = empty( $callback ) ? $revenue : call_user_func( $callback, $revenue );

			if ( 0 === $revenue ) {
				return $formatted;
			}

			return sprintf(
				'<span class="noptin-strong">%s</span>',
				$formatted
			);
		}

		return sprintf(
			'<span title="%s" class="noptin-tip dashicons dashicons-lock"></span>',
			esc_attr__( 'Activate your license key to start tracking', 'newsletter-optin-box' )
		);
	}

	/**
	 * Displays the campaign unsubscribes
	 *
	 * @param  Email $item item.
	 */
	public function column_unsubscribed( $item ) {

		$sends        = $item->get_send_count();
		$unsubscribed = $item->get_unsubscribe_count();
		$percent      = ( $sends && $unsubscribed ) ? round( ( $unsubscribed / $sends ) * 100, 2 ) : 0;

		$this->display_stat(
			$unsubscribed,
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

		if ( $percent > 0 && $percent < 101 ) {
			printf(
				'<div class="noptin-stat">%s</div><p class="noptin-stat-percent">%s%%</p>',
				esc_html( $value ),
				esc_html( $percent )
			);
		} else {
			echo esc_html( $value );
		}
	}

	/**
	 * Displays a button to re-order the email.
	 *
	 * @return string
	 */
	public function column_menu_order() {

		if ( ! $this->email_type->supports_menu_order ) {
			return '';
		}

		return sprintf(
			'<span class="noptin-tip dashicons dashicons-move" style="cursor: move;" title="%s"></span>',
			esc_attr__( 'Re-order', 'newsletter-optin-box' )
		);
	}

	/**
	 * [OPTIONAL] Return array of bult actions if has any
	 *
	 * @return array
	 */
	public function get_bulk_actions() {

		if ( 'trash' === $this->email_type->type ) {
			$actions = array(
				'restore' => __( 'Restore', 'newsletter-optin-box' ),
				'delete'  => __( 'Delete', 'newsletter-optin-box' ),
			);
		} else {
			$actions = array(
				'publish' => __( 'Publish', 'newsletter-optin-box' ),
				'trash'   => __( 'Move to Trash', 'newsletter-optin-box' ),
			);
		}

		return apply_filters( 'manage_noptin_emails_table_bulk_actions', $actions );
	}

	/**
	 * Whether the table has items to display or not
	 *
	 * @return bool
	 */
	public function has_items() {
		return $this->email_type->upsell ? false : $this->query->have_posts();
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

		$this->set_pagination_args(
			array(
				'total_items' => $this->query ? $this->query->found_posts : 0,
				'per_page'    => $this->per_page,
				'total_pages' => $this->query ? $this->query->max_num_pages : 0,
			)
		);
	}

	/**
	 * @return string
	 */
	public function current_action() {
		if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) {
			return 'delete_all';
		}

		return parent::current_action();
	}

	/**
	 *  Processes a bulk action.
	 */
	public function process_bulk_action() {

		$action = 'bulk-' . $this->_args['plural'];

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $action ) ) {
			return;
		}

		$action = $this->current_action();

		// Check if we're deleting all trash campaigns.
		if ( 'delete_all' === $action ) {
			$trash_campaigns = get_posts(
				array(
					'post_type'   => 'noptin-campaign',
					'post_status' => 'trash',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			);

			foreach ( $trash_campaigns as $id ) {
				$email = new Email( $id );
				if ( $email->current_user_can_delete() ) {
					$email->delete();
				}
			}

			noptin()->admin->show_info( __( 'All trash campaigns have been deleted.', 'newsletter-optin-box' ) );
			return;
		}

		if ( empty( $_POST['id'] ) ) {
			return;
		}

		$notice = '';
		foreach ( $_POST['id'] as $id ) {
			$email = new Email( $id );

			// Abort if not exists.
			if ( ! $email->exists() ) {
				continue;
			}

			switch ( $action ) {
				case 'publish':
					if ( current_user_can( 'publish_post', $email->id ) ) {
						wp_publish_post( $email->id );
					}

					$notice = __( 'The selected campaigns have been published.', 'newsletter-optin-box' );
					break;
				case 'trash':
					if ( $email->current_user_can_delete() ) {
						$email->trash();
					}

					$notice = __( 'The selected campaigns have been trashed.', 'newsletter-optin-box' );
					break;
				case 'restore':
					if ( $email->current_user_can_edit() ) {
						$email->restore();
					}

					$notice = __( 'The selected campaigns have been restored.', 'newsletter-optin-box' );
					break;
				case 'delete':
					if ( $email->current_user_can_delete() ) {
						$email->delete();
					}

					$notice = __( 'The selected campaigns have been deleted.', 'newsletter-optin-box' );
					break;
			}
		}

		if ( ! empty( $notice ) ) {
			noptin()->admin->show_info( $notice );
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
			'revenue'      => sprintf(
				'%s <span data-tooltip-content="#noptin-revenue-tooltip-content" class="noptin-tip dashicons dashicons-info"></span>',
				__( 'Revenue', 'newsletter-optin-box' )
			),
			'unsubscribed' => __( 'Unsubscribed', 'newsletter-optin-box' ),
			'date_sent'    => __( 'Date', 'newsletter-optin-box' ),
			'menu_order'   => '&nbsp;',
		);

		if ( 'trash' === $this->email_type->type ) {
			unset( $columns['status'] );
		}

		if ( ! $this->email_type->supports_menu_order ) {
			unset( $columns['menu_order'] );
		}

		// Remove tracking stats.
		$track_campaign_stats = get_noptin_option( 'track_campaign_stats', true );

		if ( empty( $track_campaign_stats ) ) {
			unset( $columns['opens'] );
			unset( $columns['clicks'] );
			unset( $columns['revenue'] );
		}

		if ( ! noptin_supports_ecommerce_tracking() || ! get_noptin_option( 'enable_ecommerce_tracking', true ) ) {
			unset( $columns['revenue'] );
		}

		return apply_filters( 'manage_noptin_emails_table_columns', $columns, $this );
	}

	/**
	 * Table sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {

		if ( $this->email_type->supports_menu_order ) {
			return array();
		}

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

		if ( $this->email_type->upsell ) {
			return;
		}

		if ( 'trash' === $this->email_type->type ) {

			if ( $this->has_items() ) {
				$button_name = 'top' === $which ? 'delete_all' : 'delete_all2';
				submit_button( __( 'Empty Trash', 'newsletter-optin-box' ), 'apply', $button_name, false );
			}
			return;
		}

		if ( $this->has_items() ) {
			echo '<span class="noptin-email-campaigns__editor--add-new__button"></span>';
		}
	}

	/**
	 * Message to be displayed when there are no items
	 */
	public function no_items() {

		if ( 'trash' === $this->email_type->type ) {
			parent::no_items();
			return;
		}

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
