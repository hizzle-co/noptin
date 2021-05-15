<?php defined( 'ABSPATH' ) || exit(); ?>

<div class="wrap noptin noptin_addons_wrap noptin-helper">
	<?php require Noptin_COM_Helper::get_view_filename( 'html-section-nav.php' ); ?>
	<h1 class="screen-reader-text"><?php esc_html_e( 'Noptin Addons', 'newsletter-optin-box' ); ?></h1>
	<?php require Noptin_COM_Helper::get_view_filename( 'html-section-notices.php' ); ?>

	<div class="start-container">
		<svg xmlns="http://www.w3.org/2000/svg" width="196" height="196" viewBox="0 0 24 24" class="bg-icon"><path d="M12.042 23.648c-7.813 0-12.042-4.876-12.042-11.171 0-6.727 4.762-12.125 13.276-12.125 6.214 0 10.724 4.038 10.724 9.601 0 8.712-10.33 11.012-9.812 6.042-.71 1.108-1.854 2.354-4.053 2.354-2.516 0-4.08-1.842-4.08-4.807 0-4.444 2.921-8.199 6.379-8.199 1.659 0 2.8.876 3.277 2.221l.464-1.632h2.338c-.244.832-2.321 8.527-2.321 8.527-.648 2.666 1.35 2.713 3.122 1.297 3.329-2.58 3.501-9.327-.998-12.141-4.821-2.891-15.795-1.102-15.795 8.693 0 5.611 3.95 9.381 9.829 9.381 3.436 0 5.542-.93 7.295-1.948l1.177 1.698c-1.711.966-4.461 2.209-8.78 2.209zm-2.344-14.305c-.715 1.34-1.177 3.076-1.177 4.424 0 3.61 3.522 3.633 5.252.239.712-1.394 1.171-3.171 1.171-4.529 0-2.917-3.495-3.434-5.246-.134z"></path></svg>
		<div class="text">

			<?php if ( ! empty( $_GET['noptin-helper-status'] ) && 'helper-disconnected' === $_GET['noptin-helper-status'] ) : ?>
				<p><strong><?php esc_html_e( 'Sorry to see you go.', 'newsletter-optin-box' ); ?></strong> <?php esc_html_e( 'Feel free to reconnect again using the button below.', 'newsletter-optin-box' ); ?></p>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Manage your licenses, get faster support, important product notifications, and updates, all from the convenience of your WordPress dashboard', 'newsletter-optin-box' ); ?></h2>
			<p><?php esc_html_e( 'Once connected to Noptin.com, your purchases and available updates will be listed here.', 'newsletter-optin-box' ); ?></p>
			<p><a class="button button-primary button-helper-connect" href="<?php echo esc_url( $connect_url ); ?>"><?php esc_html_e( 'Connect', 'newsletter-optin-box' ); ?></a></p>
		</div>
	</div>

</div>
