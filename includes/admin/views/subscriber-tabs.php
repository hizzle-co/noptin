<nav class="nav-tab-wrapper" style="margin-bottom: 20px; margin-top: 20px; ">

	<a
		class="nav-tab <?php echo 0 === count( array_intersect_key( Noptin_Subscribers_Admin::get_components(), $_GET ) ) ? 'nav-tab-active' : ''; ?> "
		href="<?php echo esc_url( admin_url( 'admin.php?page=noptin-subscribers' ) ); ?>"
	><?php esc_html_e( 'Subscribers', 'newsletter-optin-box' ); ?></a>

	<?php

		foreach ( Noptin_Subscribers_Admin::get_components() as $id => $component ) :

			if ( empty( $component['show_on_tabs'] ) ) {
				continue;
			}

			$id    = esc_attr( $id );
			$label = esc_html( $component['label'] );
			$url   = 'custom_fields' === $id ? admin_url( 'admin.php?page=noptin-settings&tab=fields' ) : add_query_arg( $id, 'true', admin_url( 'admin.php?page=noptin-subscribers' ) );

			printf(
				'<a href="%s" class="nav-tab %s noptin-subscriber-tab-%s">%s</a>',
				esc_url( $url ),
				( ! empty( $_GET[ $id ] ) ) ? 'nav-tab-active' : '',
				esc_attr( $id ),
				$label
			);

		endforeach;

	?>

</nav>
