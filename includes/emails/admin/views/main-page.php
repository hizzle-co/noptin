<?php

	defined( 'ABSPATH' ) || exit;

	/**
	 * @var string $tab
	 * @var string $section
	 * @var array $tabs
	 */

	 // If we're displaying a list of campaigns, prepare them.
	if ( 'main' === $section ) {

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
		<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<!-- Action buttons -->
		<?php if ( 'edit_campaign' === $section ): ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'sub_section' => 'new_campaign', 'campaign' => false ) ) ); ?>" class="page-title-action"><?php echo _e( 'Add New', 'newsletter-optin-box' ); ?></a>
		<?php endif; ?>

		<!-- Title area end -->
		<hr class="wp-header-end">

	<?php endif; ?>

	<!-- Print pending notices -->
	<?php noptin()->admin->show_notices(); ?>

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
									'section' => urlencode( $key ),
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
