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

?>

<div id="noptin-campaign-server-notices" hidden>

	<?php

		// Print pending notices.
		\Hizzle\Noptin\Admin\Main::show_notices();

		// Warn users when email delivery is disabled on a staging site.
		if ( \Hizzle\Noptin\Emails\Main::is_staging_email_disabled() ) {
			$message = 'Noptin email delivery is disabled because this site is marked as staging. Emails are recorded as sent so campaigns and automations can continue, but no messages are delivered.';

			if ( current_user_can_manage_noptin() ) {
				$message .= ' ' . sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( admin_url( 'admin.php?page=noptin-settings&hizzlewp_path=%2Femails%2Fmain' ) ),
					'Review email settings'
				);
			}

			printf(
				'<div class="notice notice-warning noptin-notice"><p><strong>%1$s</strong> %2$s</p></div>',
				'Staging email delivery is disabled.',
				wp_kses_post( $message )
			);
		}
		?>

		<?php

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

			\Hizzle\Noptin\Admin\Main::print_notice( 'error', $message );
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

				\Hizzle\Noptin\Admin\Main::print_notice(
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

</div>
<div id="noptin-email-campaigns__app"></div>
