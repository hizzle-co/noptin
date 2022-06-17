<?php defined( 'ABSPATH' ) || exit; ?>

<nav class="nav-tab-wrapper" style="margin-bottom: 20px; margin-top: 20px; ">

	<a
		class="nav-tab <?php echo 0 === count( array_intersect_key( Noptin_Subscribers_Admin::get_components(), $_GET ) ) ? 'nav-tab-active' : ''; ?> "
		href="<?php echo esc_url( admin_url( 'admin.php?page=noptin-subscribers' ) ); ?>"
	><?php esc_html_e( 'Subscribers', 'newsletter-optin-box' ); ?></a>

	<?php

		foreach ( Noptin_Subscribers_Admin::get_components() as $component_id => $component ) :

			if ( empty( $component['show_on_tabs'] ) ) {
				continue;
			}

			$url = 'custom_fields' === $component_id ? admin_url( 'admin.php?page=noptin-settings&tab=fields' ) : add_query_arg( $component_id, 'true', admin_url( 'admin.php?page=noptin-subscribers' ) );

			printf(
				'<a href="%s" class="nav-tab %s noptin-subscriber-tab-%s">%s</a>',
				esc_url( $url ),
				( ! empty( $_GET[ $component_id ] ) ) ? 'nav-tab-active' : '',
				esc_attr( $component_id ),
				esc_html( $component['label'] )
			);

		endforeach;

	?>

</nav>
