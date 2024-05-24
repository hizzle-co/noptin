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

	<!-- Display actual content -->
	<div class="noptin-email-campaigns-tab-content">
		<form id="noptin-email-campaigns-table" method="post">
			<?php foreach ( $query_args as $key => $value ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"/>
			<?php endforeach; ?>
			<?php $table->display(); ?>
		</form>
	</div>

</div>

<div style="display: none;">
	<div id="noptin-revenue-tooltip-content">
		<?php
			if ( noptin_has_active_license_key() ) {
				esc_html_e( 'Revenue is tracked when someone makes a purchase within 2 weeks of clicking on a link in a campaign', 'newsletter-optin-box' );
			} else {
				esc_html_e( 'Activate your license key to track and view revenue made per campaign', 'newsletter-optin-box' );
			}
		?>
		<br />
		<a class="button button-primary" href="<?php echo esc_url( noptin_get_upsell_url( 'guide/sending-emails/tracking-revenue-generated-per-email/', 'email-campaigns', 'revenue-tracking' ) ); ?>" target="_blank"><?php esc_html_e( 'Learn more', 'newsletter-optin-box' ); ?></a>
	</div>
</div>
