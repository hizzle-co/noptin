<?php

	namespace Hizzle\Noptin\Emails\Admin;

	defined( 'ABSPATH' ) || exit;

	/**
	 * @var array $query_args
	 */

	// Run pending tasks.
	do_action( 'noptin_tasks_run_pending' );

	// Send pending emails.
	\Hizzle\Noptin\Emails\Bulk\Main::send_pending();

	// Prepare the email type.
	$email_type  = \Hizzle\Noptin\Emails\Main::get_email_type( $query_args['noptin_email_type'] );
	$email_types = \Hizzle\Noptin\Emails\Main::get_email_types();

	// Prepare items.
	$table = new Table( $email_type );
	$table->prepare_items();

	// Check if we have trash campaigns.
	$trash_count = wp_count_posts( 'noptin-campaign' );
	$parent      = ( empty( $email_type->parent_type ) || empty( $_GET['noptin_parent_id'] ) ) ? false : noptin_get_email_campaign_object( (int) $_GET['noptin_parent_id'] );
	$app         = '';

if ( $parent ) {
	$app = wp_json_encode(
		array(
			'title'      => $parent->name,
			'status'     => $parent->status,
			'id'         => $parent->id,
			'modalTitle' => sprintf(
				/* translators: %s: email type label */
				esc_html__( 'Edit %s', 'newsletter-optin-box' ),
				isset( $email_types[ $email_type->parent_type ] ) ? $email_types[ $email_type->parent_type ]->label : __( 'Campaign', 'newsletter-optin-box' )
			),
		)
	);
}
?>

<div class="wrap noptin noptin-email-campaigns noptin-<?php echo sanitize_html_class( $email_type->type ); ?> noptin-<?php echo sanitize_html_class( $email_type->type ); ?>-main" id="noptin-wrapper">

	<?php

		// Print pending notices.
		noptin()->admin->show_notices();

		// Check if sending has been paused due to limits.
		if ( noptin_email_sending_limit_reached() ) {
			$message = sprintf(
				'<h3>%s</h3>',
				__( 'Email sending has been paused', 'newsletter-optin-box' )
			);

			$message .= '<p>' .
				sprintf(
					/* translators: %1$s: number of emails allowed to be sent per period, %2$s: time period */
					esc_html__( 'You’ve sent %1$s emails in the past %2$s, triggering the sending limit.', 'newsletter-optin-box' ),
					'<strong>' . esc_html( noptin_max_emails_per_period() ) . '</strong>',
					'<strong>' . esc_html( human_time_diff( time() + noptin_get_email_sending_rolling_period() ) ) . '</strong>'
				) . '</p>';

			$next_send = noptin_get_next_email_send_time();

			if ( $next_send ) {
				$message .= ' <p>' .
					sprintf(
						/* translators: %s: time until next email can be sent */
						esc_html__( 'Email sending will resume in %s.', 'newsletter-optin-box' ),
						'<strong>' . esc_html( human_time_diff( $next_send ) ) . '</strong>'
					) . '</p>';
			}

			noptin()->admin->print_notice( 'error', $message );
		}

		// Check if external CRON is not being used.
		$can_display_cron_notice = ! apply_filters( 'noptin_display_cron_notice', get_user_meta( get_current_user_id(), 'noptin_dismiss_cron_notice', true ) );
		if ( $can_display_cron_notice && ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) ) {
			$message = sprintf(
				'<h3>%s</h3>',
				__( 'WP-Cron is being used for email sending', 'newsletter-optin-box' )
			);

			$message .= '<p>' . __( 'Your site is using WordPress\' built-in cron system to send emails. This may cause delays in email delivery if your site is cached or doesn\'t receive regular traffic.', 'newsletter-optin-box' ) . '</p>';

			$message .= '<p>' . sprintf(
				/* translators: %s: link to documentation */
				__( 'For better email delivery, consider setting up a real cron job. <a href="%s" target="_blank">Learn more</a>', 'newsletter-optin-box' ),
				noptin_get_guide_url( 'External CRON', 'sending-emails/how-to-set-up-an-external-cron-job-in-wordpress-and-speed-up-email-sending/' )
			) . '</p>';

			noptin()->admin->print_notice(
				'warning',
				$message,
				wp_nonce_url(
					add_query_arg( array() ),
					'noptin_dismiss_cron_notice',
					'noptin_dismiss_cron_notice'
				)
			);
		}
	?>

	<?php if ( isset( $_REQUEST['s'] ) && strlen( $_REQUEST['s'] ) ) : ?>
		<div style="padding-top: 9px;">
			<?php
				printf(
					/* translators: %s: Search query. */
					__( 'Search results for: %s' ),
					'<strong>' . esc_html( sanitize_text_field( rawurldecode( $_REQUEST['s'] ) ) ) . '</strong>'
				);
			?>
		</div>
	<?php endif; ?>

	<!-- Display tabs -->
	<div class="nav-tab-wrapper noptin-nav-tab-wrapper">
		<?php

		foreach ( $email_types as $email_type_data ) {

			if ( ! empty( $email_type_data->parent_type ) ) {
				continue;
			}

			// Hide trash if it's empty.
			if ( 'trash' === $email_type_data->type && empty( $trash_count->trash ) ) {
				continue;
			}

			printf(
				'<a href="%s" class="%s">%s</a>',
				esc_url(
					add_query_arg(
						array(
							'page'              => 'noptin-email-campaigns',
							'noptin_email_type' => rawurlencode( $email_type_data->type ),
						),
						admin_url( '/admin.php' )
					)
				),
				( $email_type->type === $email_type_data->type || $email_type->type === $email_type_data->child_type ) ? 'nav-tab nav-tab-active' : 'nav-tab',
				esc_html( $email_type_data->plural_label )
			);

		}

		?>
	</div>

	<?php if ( $parent ) : ?>
		<div id="noptin-email-campaigns-parent">
			<h1 class="wp-heading-inline">
				<?php echo esc_html( $parent->name ); ?>
			</h1>
			<span id="noptin-email-campaigns-parent_edit" data-app="<?php echo esc_attr( $app ); ?>">
				<span class="spinner" style="visibility: visible; float: none;"></span>
			</span>
			<a href="<?php echo esc_url( $parent->get_base_url() ); ?>" class="page-title-action"><?php esc_html_e( 'Back', 'newsletter-optin-box' ); ?></a>
			<hr class="wp-header-end">
		</div>
	<?php endif; ?>

	<div>
		<?php $table->views(); ?>
	</div>

	<!-- Display actual content -->
	<div class="noptin-email-campaigns-tab-content">
		<form id="noptin-email-campaigns-table" method="get">
			<?php $table->search_box( __( 'Search Campaigns', 'newsletter-optin-box' ), 'post' ); ?>
			<?php foreach ( $query_args as $key => $value ) : ?>
				<?php if ( is_scalar( $value ) && ! in_array( $key, array( 's', '_wpnonce', '_wp_http_referer', 'action', 'action2' ), true ) ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"/>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php $table->display(); ?>
		</form>
	</div>

</div>
