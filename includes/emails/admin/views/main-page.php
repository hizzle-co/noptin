<?php

	defined( 'ABSPATH' ) || exit;

	/**
	 * @var string $tab
	 * @var string $section
	 * @var array $tabs
	 */

	// If we're displaying a list of campaigns, prepare them.
	if ( 'main' === $section ) {

		// Send pending emails.
		noptin()->bulk_emails()->send_pending();

		// Inlcude the list table.
		include plugin_dir_path( dirname( __FILE__ ) ) . 'class-list-table.php';

		// Prepare items.
		$table = new Noptin_Email_List_Table();
		$table->prepare_items();

	}

?>
<div class="wrap noptin noptin-email-campaigns noptin-<?php echo sanitize_html_class( $tab ); ?> noptin-<?php echo sanitize_html_class( $tab ); ?>-<?php echo sanitize_html_class( $section ); ?>" id="noptin-wrapper">

	<?php if ( 'main' !== $section ) : ?>

		<!-- Page Title -->
		<h1 class="wp-heading-inline" style="font-size: 29px; font-weight: 500"><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<!-- Action buttons -->
		<?php if ( 'edit_campaign' === $section ) : ?>
			<a href="<?php echo esc_url( remove_query_arg( 'campaign', add_query_arg( 'sub_section', 'new_campaign' ) ) ); ?>" class="page-title-action"><?php echo esc_html_e( 'Add New', 'newsletter-optin-box' ); ?></a>
		<?php endif; ?>

		<!-- Title area end -->
		<hr class="wp-header-end">

	<?php endif; ?>

	<?php

		// Print pending notices.
		noptin()->admin->show_notices();

		// Check if sending has been paused due to limits.
		$emails_sent_this_hour = (int) get_transient( 'noptin_emails_sent_' . gmdate( 'YmdH' ) );
		$email_sending_limit   = get_noptin_option( 'per_hour', 0 );

		if ( ! empty( $email_sending_limit ) && $emails_sent_this_hour >= $email_sending_limit ) {

			$message = sprintf(
				/* translators: %1$s: number of emails sent this hour, %2$s: number of emails allowed to be sent per hour */
				esc_html__( 'Sending has been paused due to sending limits. %1$s emails have been sent this hour. You can send %2$s emails per hour.', 'newsletter-optin-box' ),
				'<strong>' . esc_html( $emails_sent_this_hour ) . '</strong>',
				'<strong>' . esc_html( $email_sending_limit ) . '</strong>'
			);

			noptin()->admin->print_notice( 'error', $message );

		}
	?>

	<?php if ( 'main' === $section ) : ?>
		<!-- Display tabs -->
		<div class="nav-tab-wrapper noptin-nav-tab-wrapper">
			<?php

				foreach ( $tabs as $key => $label ) {

					printf(
						'<a href="%s" class="%s">%s</a>',
						esc_url(
							add_query_arg(
								array(
									'page'    => 'noptin-email-campaigns',
									'section' => rawurlencode( $key ),
								),
								admin_url( '/admin.php' )
							)
						),
						$tab === $key ? 'nav-tab nav-tab-active' : 'nav-tab',
						esc_html( $label )
					);

				}

			?>
		</div>
	<?php endif; ?>

	<!-- Display actual content -->
	<div class="noptin-email-campaigns-tab-content">
		<?php
			// Runs before displaying the email campaigns page.
			do_action( 'noptin_before_email_campaigns_page' );

			// Runs when displaying a specific tab's content.
			do_action( "noptin_before_email_campaigns_tab_$tab", $tabs );

			// Runs when displaying a specific tab's sub-section content.
			do_action( "noptin_email_campaigns_tab_{$tab}_{$section}", $tabs );

			// Overview page.
			if ( 'main' === $section ) {

				// Do we have any campaigns?
				if ( ! $table->has_items() ) {
					include plugin_dir_path( __FILE__ ) . $tab . '/view-no-campaigns.php';
				} else {
					include plugin_dir_path( __FILE__ ) . $tab . '/view-campaigns.php';
				}
			}

			// New campaign page.
			if ( 'new_campaign' === $section ) {
				include plugin_dir_path( __FILE__ ) . $tab . '/view-new-campaign.php';
			}

			// Runs after displaying a specific tab's content.
			do_action( "noptin_email_campaigns_tab_$tab", $tabs );

			// Runs after displaying the email campaigns page.
			do_action( 'noptin_after_email_campaigns_page' );
		?>
	</div>

</div>
