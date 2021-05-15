<?php defined( 'ABSPATH' ) || exit(); ?>

<nav class="nav-tab-wrapper noptin-nav-tab-wrapper">
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=noptin-addons' ) ); ?>" class="nav-tab"><?php esc_html_e( 'Browse Addons', 'newsletter-optin-box' ); ?></a>

	<?php
		$count_html = Noptin_COM_Updater::get_updates_count_html();
		/* translators: %s: Noptin.com Helper tab count HTML. */
		$menu_title = sprintf( __( 'Noptin.com Helper %s', 'newsletter-optin-box' ), $count_html );
	?>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=noptin-addons&section=helper' ) ); ?>" class="nav-tab nav-tab-active"><?php echo wp_kses_post( $menu_title ); ?></a>
</nav>
