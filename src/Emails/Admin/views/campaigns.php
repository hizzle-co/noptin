<?php

	namespace Hizzle\Noptin\Emails\Admin;

	defined( 'ABSPATH' ) || exit;

	/**
	 * @var array $query_args
	 */

	// Send pending emails.
	noptin()->bulk_emails()->send_pending();

	// Prepare the email type.
	$email_type  = \Hizzle\Noptin\Emails\Main::get_email_type( $query_args['noptin_email_type'] );
	$email_types = \Hizzle\Noptin\Emails\Main::get_email_types();

	// Prepare items.
	$table = new Table( $email_type );
	$table->prepare_items();

	// Do we have any campaigns?
	if ( ! $table->has_items() ) {
		$query_args['noptin_campaign']       = 0;
		$query_args['noptin_is_first_email'] = 1;
		include plugin_dir_path( __FILE__ ) . 'campaign.php';
		return;
	}

?>

<div class="wrap noptin noptin-email-campaigns noptin-<?php echo sanitize_html_class( $email_type->type ); ?> noptin-<?php echo sanitize_html_class( $email_type->type ); ?>-main" id="noptin-wrapper">

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

	<!-- Display tabs -->
	<div class="nav-tab-wrapper noptin-nav-tab-wrapper">
		<?php

			foreach ( $email_types as $email_type_data ) {

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
					$email_type->type === $email_type_data->type ? 'nav-tab nav-tab-active' : 'nav-tab',
					esc_html( $email_type_data->plural_label )
				);

			}

		?>
	</div>

	<!-- Display actual content -->
	<div class="noptin-email-campaigns-tab-content">
		<form id="noptin-email-campaigns-table" method="post" style="margin-top: 30px;">
			<?php foreach ( $query_args as $key => $value ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"/>
			<?php endforeach; ?>
			<?php $table->display(); ?>
		</form>
	</div>

</div>
